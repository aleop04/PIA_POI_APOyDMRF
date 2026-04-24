<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;


class ChatController extends Controller
{
    public function index()
    {
        $conversations = Auth::user()
        ->conversations()
        ->whereHas('messages')
        ->with(['users', 'messages' => function ($query) {
            $query->latest()->limit(1);
        }])
        ->latest()
        ->get();

        return inertia('Chats', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation)
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );

        return response()->json([
            'conversation' => $conversation->load('users'),
            'messages' => $conversation->messages()
                ->with('sender:id,first_name,last_name,username')
                ->oldest()
                ->get(),
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );

        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'type' => 'text',
            'body_encrypted' => $request->body,
        ]);

        return response()->json([
            'message' => $message->load('sender:id,first_name,last_name,username'),
        ]);
    }

}
