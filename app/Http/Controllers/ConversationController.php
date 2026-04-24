<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConversationController extends Controller
{
    public function updateName(Request $request, Conversation $conversation)
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );

        abort_unless($conversation->type === 'group', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ]);

        $conversation->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    public function updatePhoto(Request $request, Conversation $conversation)
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );

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

        return response()->json([
            'photo' => Storage::url($path),
            'conversation' => $conversation,
        ]);
    }

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:2'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => null,
            'created_by' => Auth::id(),
        ]);

        $conversation->users()->attach([
            Auth::id(),
            ...$validated['user_ids'],
        ]);

        return response()->json([
            'conversation' => $conversation->load('users'),
        ]);
    }

    public function storePrivate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        abort_if((int) $validated['user_id'] === Auth::id(), 422);

        $existingConversation = Auth::user()
            ->conversations()
            ->where('type', 'private')
            ->whereHas('users', function ($query) use ($validated) {
                $query->where('users.id', $validated['user_id']);
            })
            ->first();

        if ($existingConversation) {
            return response()->json([
                'conversation' => $existingConversation->load('users'),
                'user' => $existingConversation->users
                    ->where('id', $validated['user_id'])
                    ->first(),
            ]);
        }

        $conversation = Conversation::create([
            'type' => 'private',
            'created_by' => Auth::id(),
        ]);

        $conversation->users()->attach([
            Auth::id(),
            $validated['user_id'],
        ]);

        return response()->json([
            'conversation' => $conversation->load('users'),
            'user' => $conversation->users
                ->where('id', $validated['user_id'])
                ->first(),
        ]);
    }

}
