<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $conversations = Auth::user()
            ->conversations()
            ->with(['users', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->orderByDesc('conversations.created_at')
            ->get();

        $conversations->each(function ($conversation) {
            $conversation->messages->each(function ($message) {
                if ($message->body_encrypted && $message->iv && $message->tag) {
                    $message->body = $this->decryptMessage(
                        $message->body_encrypted,
                        $message->iv,
                        $message->tag
                    );
                } else {
                    $message->body = $message->body_encrypted;
                }
            });
        });

        return inertia('Chats', [
            'conversations' => $conversations,
            'openConversationId' => request()->query('conversation'),
        ]);
    }

    public function show(Conversation $conversation)
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );

        $messages = $conversation->messages()
            ->with('sender:id,first_name,last_name,username,profile_photo')
            ->oldest()
            ->get()
            ->map(function ($message) {
                if ($message->body_encrypted && $message->iv && $message->tag) {
                    $message->body = $this->decryptMessage(
                        $message->body_encrypted,
                        $message->iv,
                        $message->tag
                    );
                } else {
                    $message->body = $message->body_encrypted;
                }

                return $message;
            });

        return response()->json([
            'conversation' => $conversation->load('users'),
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        abort_unless(
            $conversation->users()->where('users.id', Auth::id())->exists(),
            403
        );

        $validated = $request->validate([
            'body' => ['required', 'string'],
            'type' => ['required', 'in:text,image,audio,file,location,system'],
        ]);

        $encrypted = $this->encryptMessage($validated['body']);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'type' => $validated['type'],
            'body_encrypted' => $encrypted['ciphertext'],
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ]);

        $message->load('sender:id,first_name,last_name,username,profile_photo');

        $message->body = $validated['body'];

        return response()->json([
            'message' => $message,
        ]);
    }

    // SISTEMA ENCRIPTADO DE MENSAJES AES-256GCM
    private function encryptMessage($plaintext)
    {
        $key = hash('sha256', config('app.key'), true);
        $iv = random_bytes(12);

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
        ];
    }
    //DESENCRIPTADO DE MENSAJES AES-256GCM
    private function decryptMessage($ciphertext, $iv, $tag)
    {
        $key = hash('sha256', config('app.key'), true);

        return openssl_decrypt(
            base64_decode($ciphertext),
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($iv),
            base64_decode($tag)
        );
    }
}