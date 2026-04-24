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
        //datos de usuario
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // parte d registro e inicio d sesion
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('username', 20)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // parte d perfil
            $table->text('bio')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('cover_photo')->nullable();
            //estado d conexion en chats
            $table->timestamp('last_seen_at')->nullable();
            // total de puntos
            $table->integer('total_points')->default(0);
            // recordar sesion login
            $table->rememberToken();
            // fecha d cuando se registro y cuando edito perfil
            $table->timestamps();
            $table->softDeletes();
        });

        //recuperacion de contra
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        //guarda sesion de usuario para mantenerlo logueado
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
