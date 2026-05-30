<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Inertia\Inertia;
use App\Models\RewardRedemption;

class PerfilExternoController extends Controller
{
    public function show(User $user)
    {
        $posts = Post::with([
                'user:id,username,profile_photo',
                'photos',
                'location',
            ])
            ->withAvg('ratings', 'rating')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->get();

        $badges = RewardRedemption::query()
            ->where('user_id', $user->id)
            ->whereHas('reward', function ($query) {
                $query->where('type', 'badge');
            })
            ->with('reward')
            ->latest('redeemed_at')
            ->get()
            ->map(function ($redemption) {
                return [
                    'id' => $redemption->reward->id,
                    'name' => $redemption->reward->name,
                    'image' => $redemption->reward->image
                        ? '/storage/' . $redemption->reward->image
                        : null,
                ];
            });

        return Inertia::render('Perfilexterno', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'profile_photo' => $user->profile_photo,
                'cover_photo' => $user->cover_photo,
                'total_points' => $user->total_points,
            ],

            'badges' => $badges,

            // publicaciones reales del usuario externo
            'posts' => $posts,
        ]);
    }
}