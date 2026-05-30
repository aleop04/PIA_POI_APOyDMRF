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
        Schema::create('post_ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')
            ->constrained('posts')
            ->cascadeOnDelete();

            $table->foreignId('user_id')
            ->constrained('users')
            ->cascadeOnDelete();

            // Calificación, por ejemplo de 1 a 5
            $table->unsignedTinyInteger('rating');

            $table->timestamps();
            $table->unique(['post_id', 'user_id']); // para que el usuario solo pueda calificar una vez cada publicación
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_ratings');
    }
};
