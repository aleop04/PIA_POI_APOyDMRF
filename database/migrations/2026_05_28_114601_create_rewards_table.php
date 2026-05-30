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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('type', ['badge', 'discount']);

            $table->unsignedInteger('cost_points');

            $table->unsignedInteger('stock')->nullable();

            $table->string('image')->nullable();

            $table->unsignedInteger('discount_value')->nullable();

            $table->boolean('is_active')->default(true);
    
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
