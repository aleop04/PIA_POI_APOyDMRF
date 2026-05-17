<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            ->with([
                'sender:id,first_name,last_name,username,profile_photo',
                'attachments',
            ])
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

                $message->attachments->transform(function ($attachment) {
                    $attachment->url = asset('storage/' . $attachment->file_path);

                    return $attachment;
                });

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
            'type' => ['required', 'in:text,image,file,audio,location'],

            'body' => ['required_if:type,text', 'nullable', 'string'],

            'lat' => ['required_if:type,location', 'numeric'],
            'lng' => ['required_if:type,location', 'numeric'],

            'image' => ['required_if:type,image', 'image', 'max:5120'],

            'file' => [
                'required_if:type,file',
                'file',
                'max:10240',
            ],

            'audio' => [
                'required_if:type,audio',
                'file',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/webm,audio/ogg,audio/mp4,audio/aac,video/webm,video/mp4',
                'max:10240',
            ],
        ]);

        $bodyToEncrypt = $validated['body'] ?? null;

        if ($validated['type'] === 'location') {
            $bodyToEncrypt = 'https://www.google.com/maps?q='
                . $validated['lat']
                . ','
                . $validated['lng'];
        }

        $encrypted = null;

        if ($bodyToEncrypt) {
            $encrypted = $this->encryptMessage($bodyToEncrypt);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::id(),
            'type' => $validated['type'],
            'body_encrypted' => $encrypted['ciphertext'] ?? null,
            'iv' => $encrypted['iv'] ?? null,
            'tag' => $encrypted['tag'] ?? null,
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $path = $file->store('chat-images', 'public');

            $message->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $path = $file->store('chat-files', 'public');

            $message->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        if ($request->hasFile('audio')) {
            $file = $request->file('audio');

            $path = $file->store('chat-audios', 'public');

            $message->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $message->load([
            'sender:id,first_name,last_name,username,profile_photo',
            'attachments',
        ]);

        $message->body = $bodyToEncrypt;

        $message->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/' . $attachment->file_path);

            return $attachment;
        });

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