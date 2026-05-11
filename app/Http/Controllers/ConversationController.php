<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\User;

class ConversationController extends Controller
{
    public function updateName(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationMember($conversation);

        abort_unless($conversation->type === 'group', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $conversation->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'conversation' => $conversation->load('users'),
        ]);
    }

    public function updatePhoto(Request $request, Conversation $conversation)
    {
        $this->authorizeConversationMember($conversation);

        abort_unless($conversation->type === 'group', 403);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        if ($conversation->photo) {
            Storage::disk('public')->delete($conversation->photo);
        }

        $path = $validated['photo']->store('conversation-photos', 'public');

        $conversation->update([
            'photo' => $path,
        ]);

        $conversation->load('users');

        $conversation->photo = Storage::url($path);

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:2', 'max:4'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([Auth::id()]),
            ],
        ]);

        $userIds = collect($validated['user_ids'])
            ->unique()
            ->values()
            ->all();

        abort_if(count($userIds) < 2 || count($userIds) > 4, 422);

        // Todos los integrantes del grupo: usuario actual + usuarios seleccionados
        $allUserIds = collect([Auth::id(), ...$userIds])
            ->unique()
            ->values();

        // Obtener usuarios en el mismo orden
        $users = User::whereIn('id', $allUserIds)
            ->get()
            ->sortBy(fn ($user) => $allUserIds->search($user->id))
            ->values();

        // Nombre base: usernames de todos
        $baseName = $users
            ->pluck('username')
            ->join(', ');

        // Buscar grupos existentes con exactamente los mismos integrantes
        $existingSameGroups = Conversation::where('type', 'group')
            ->with('users:id')
            ->get()
            ->filter(function ($conversation) use ($allUserIds) {
                $conversationUserIds = $conversation->users
                    ->pluck('id')
                    ->sort()
                    ->values();

                return $conversationUserIds->toArray() === $allUserIds
                    ->sort()
                    ->values()
                    ->toArray();
            });

        // Si ya existe uno igual, agregar 2, 3, etc.
        $groupNumber = $existingSameGroups->count() + 1;

        $groupName = $groupNumber === 1
            ? $baseName
            : $baseName . ' ' . $groupNumber;

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $groupName,
            'created_by' => Auth::id(),
        ]);

        $conversation->users()->attach($allUserIds->all());

        return response()->json([
            'conversation' => $conversation->load('users'),
        ]);
    }

    public function storePrivate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        $userIds = collect([Auth::id(), (int) $validated['user_id']])
            ->unique()
            ->values();

        $existingConversationQuery = Auth::user()
            ->conversations()
            ->where('type', 'private')
            ->has('users', '=', $userIds->count());

        foreach ($userIds as $userId) {
            $existingConversationQuery->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            });
        }

        $existingConversation = $existingConversationQuery->first();

        if ($existingConversation) {
            return response()->json([
                'conversation' => $existingConversation->load('users'),
            ]);
        }

        $conversation = Conversation::create([
            'type' => 'private',
            'created_by' => Auth::id(),
        ]);

        $conversation->users()->attach($userIds->all());

        return response()->json([
            'conversation' => $conversation->load('users'),
        ]);
    }

    private function authorizeConversationMember(Conversation $conversation): void
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );
    }
}