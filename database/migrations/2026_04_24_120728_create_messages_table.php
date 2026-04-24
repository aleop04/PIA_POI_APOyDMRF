<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['text', 'image', 'audio', 'file', 'location', 'system']);
            $table->text('body_encrypted')->nullable();
            $table->string('iv')->nullable();
            $table->string('tag')->nullable(); // aes-256-gcm para encriptado
            $table->timestamps();
            // cargar mensajes d una conversación ordenados x fecha
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
