<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PostComment;
use Inertia\Inertia;
use App\Services\GroupTaskService;
use App\Support\LocationSearch;

class PostController extends Controller
{
    // Mostrar listado de publicaciones
    // Por ahora NO es necesario tener una vista Index.
    // Redirigimos al dashboard temporalmente para evitar errores.
    public function index(Request $request)
    {
        return redirect()->route('dashboard');
    }

    // Crear publicación
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],

            'available_from' => ['nullable', 'date_format:H:i'],
            'available_to' => ['nullable', 'date_format:H:i'],

            'opening_days' => ['nullable', 'array'],

            // Ubicación, por ahora opcional.
            // Cuando conectemos AddressAutocomplete, estos campos sí se enviarán.
            'formatted_address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'place_id' => ['nullable', 'string'],

            // Fotos
            'photos' => ['nullable', 'array', 'max:4'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $post = DB::transaction(function () use ($request, $validated) {
            $post = Post::create([
                'user_id' => Auth::id(),

                'title' => $validated['title'],
                'description' => $validated['description'],

                'available_from' => $validated['available_from'] ?? null,
                'available_to' => $validated['available_to'] ?? null,

                'opening_days' => $validated['opening_days'] ?? [],
                'is_active' => true,
            ]);

            if (!empty($validated['formatted_address'])) {
                $post->location()->create([
                    'formatted_address' => $validated['formatted_address'],
                    'lat' => $validated['lat'] ?? null,
                    'lng' => $validated['lng'] ?? null,
                    'place_id' => $validated['place_id'] ?? null,
                ]);
            }

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $file) {
                    $path = $file->store('post-photos', 'public');

                    PostPhoto::create([
                        'post_id' => $post->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'order' => $index,
                    ]);
                }
            }

            return $post;
        });

        GroupTaskService::registerIncrementProgress(Auth::id(), 'publish');

        return response()->json([
            'ok' => true,
            'post' => [
                'id' => $post->id,
            ],
        ]);
    }

    // Mostrar una publicación específica
    public function show(Post $post)
    {
        if (!$post->is_active) {
            abort(404);
        }

        $post->increment('views');

        $post->load([
            'user:id,username,profile_photo',
            'photos',
            'location',
            'comments' => function ($query) {
                $query->latest();
            },
            'comments.user:id,username,profile_photo',
            'ratings',
        ]);

        $avgRating = round(
            $post->ratings->avg('rating') ?? 0,
            1
        );

        $userRating = $post->ratings
            ->where('user_id', Auth::id())
            ->first()?->rating;

        return Inertia::render('Publicacion', [
            'post' => $post,
            'avgRating' => $avgRating,
            'userRating' => $userRating,
            'isOwner' => $post->user_id === Auth::id(),
            'authUserId' => Auth::id(),
        ]);
    }

    // Comentar publicación
    
    public function comment(Request $request, Post $post)
    {
        if (!$post->is_active) {
            abort(404);
        }

        if ($post->user_id === Auth::id()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes comentar tu propia publicación.',
            ], 403);
        }

        $alreadyCommented = $post->comments()
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyCommented) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya comentaste esta publicación. Puedes editar o eliminar tu comentario.',
            ], 422);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        $comment = $post->comments()->create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
        ]);

        GroupTaskService::recalculateReviewProgress(auth()->id());

        $comment->load('user:id,username,profile_photo');

        return response()->json([
            'ok' => true,
            'comment' => $comment,
        ]);
    }

    public function updateComment(Request $request, PostComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes editar este comentario.',
            ], 403);
        }

        if (!$comment->post || !$comment->post->is_active) {
            abort(404);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        $comment->update([
            'comment' => $validated['comment'],
            'is_edited' => true,
        ]);

        $comment->load('user:id,username,profile_photo');

        return response()->json([
            'ok' => true,
            'comment' => $comment,
        ]);
    }

    public function deleteComment(PostComment $comment)
    {
        if ($comment->user_id !== Auth::id()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar este comentario.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'ok' => true,
            'commentId' => $comment->id,
        ]);
    }

    // Calificar publicación
    public function rate(Request $request, Post $post)
    {
        if (!$post->is_active) {
            abort(404);
        }

        if ($post->user_id === Auth::id()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes valorar tu propia publicación.',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $post->ratings()->updateOrCreate(
            [
                'post_id' => $post->id,
                'user_id' => Auth::id(),
            ],
            [
                'rating' => $validated['rating'],
            ]
        );

        GroupTaskService::recalculateReviewProgress(auth()->id());

        $avgRating = round(
            $post->ratings()->avg('rating') ?? 0,
            1
        );

        return response()->json([
            'ok' => true,
            'avgRating' => $avgRating,
            'userRating' => $validated['rating'],
        ]);
    }

    // Dashboard / carrusel
    public function dashboard()
    {
        $topPosts = Post::with([
                'user:id,username,profile_photo',
                'photos',
                'location',
            ])
            ->withAvg('ratings', 'rating')
            ->where('is_active', true)
            ->orderByDesc('ratings_avg_rating')
            ->take(3)
            ->get();

        $posts = Post::with([
                'user:id,username,profile_photo',
                'photos',
                'location',
            ])
            ->withAvg('ratings', 'rating')
            ->where('is_active', true)
            ->whereNotIn('id', $topPosts->pluck('id'))
            ->inRandomOrder()
            ->take(9)
            ->get();

        return Inertia::render('Dashboard', [
            'topPosts' => $topPosts,
            'posts' => $posts,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $placeId = $request->query('place_id');

        $locationTerms = LocationSearch::termsFromQuery($q);

        $resultados = Post::with([
                'user:id,username,profile_photo',
                'photos',
                'location',
            ])
            ->withAvg('ratings', 'rating')
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q, $placeId, $locationTerms) {
                $query->where(function ($subQuery) use ($q, $placeId, $locationTerms) {
                    $subQuery
                        ->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('location', function ($locationQuery) use ($q, $placeId, $locationTerms) {
                            $locationQuery->where(function ($locationSubQuery) use ($q, $placeId, $locationTerms) {
                                if (!empty($placeId)) {
                                    $locationSubQuery->where('place_id', $placeId);
                                }

                                $locationSubQuery->orWhere('formatted_address', 'like', "%{$q}%");

                                foreach ($locationTerms as $term) {
                                    if (mb_strlen($term) >= 2) {
                                        $locationSubQuery->orWhere('formatted_address', 'like', "%{$term}%");
                                    }
                                }
                            });
                        });
                });
            })
            ->latest()
            ->get();

        return Inertia::render('ResultadosBusqueda', [
            'q' => $q,
            'resultados' => $resultados,
        ]);
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            return response()->json([
                'ok' => false,
                'message' => 'No puedes eliminar esta publicación.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}