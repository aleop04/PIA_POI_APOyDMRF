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
        Schema::create('post_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')
            ->constrained('posts')
            ->cascadeOnDelete();

            // Ruta de la imagen guardada
            $table->string('file_path');

            // Nombre original opcional
            $table->string('original_name')->nullable();

            // Para ordenar las fotos
            // La de order = 0 puede ser la principal
            $table->unsignedInteger('order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_photos');
    }
};
