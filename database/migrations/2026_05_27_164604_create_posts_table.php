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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // usuario que creó la publicación
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // información principal
            $table->string('title', 100);
            $table->text('description');

            // horario disponible
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();

            // días disponibles
            $table->json('opening_days')->nullable();

            // estado publicación
            $table->boolean('is_active')->default(true);

            // visitas + popular
            $table->unsignedInteger('views')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
