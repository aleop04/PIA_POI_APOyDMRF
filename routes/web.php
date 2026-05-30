<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\SocketUserController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\DestinarioPerfilController;
use App\Http\Controllers\PerfilExternoController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\GroupTaskController;
use App\Http\Controllers\RewardController;



Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [PostController::class, 'dashboard'])
        ->name('dashboard');

    // Perfil
    Route::get('/perfil', [DestinarioPerfilController::class, 'show'])->name('perfil');
    Route::patch('/perfil', [DestinarioPerfilController::class, 'update'])
        ->name('perfil.update');
    Route::post('/perfil/profile-photo', [DestinarioPerfilController::class, 'updateProfilePhoto'])
        ->name('perfil.profile-photo');
    Route::post('/perfil/cover-photo', [DestinarioPerfilController::class, 'updateCoverPhoto'])
        ->name('perfil.cover-photo');
    Route::delete('/perfil', [DestinarioPerfilController::class, 'destroy'])
        ->name('perfil.destroy');

    // Perfil externo
    Route::get('/usuarios/{user}', [PerfilExternoController::class, 'show'])
    ->name('usuarios.show');

    // Publicaciones
    Route::get('/publicaciones', [PostController::class, 'index'])
        ->name('posts.index');

    Route::get('/publicaciones/crear', function () {
        return Inertia::render('CrearPublicacion');
    })->name('posts.create');

    Route::post('/publicaciones', [PostController::class, 'store'])
        ->name('posts.store');

    Route::get('/publicaciones/{post}', [PostController::class, 'show'])
        ->name('posts.show');

    Route::delete('/publicaciones/{post}', [PostController::class, 'destroy'])
        ->name('posts.destroy');

    Route::post('/publicaciones/{post}/comentarios', [PostController::class, 'comment'])
        ->name('posts.comment');
    
    Route::patch('/publicaciones/comentarios/{comment}', [PostController::class, 'updateComment'])
        ->name('posts.comment.update');

    Route::delete('/publicaciones/comentarios/{comment}', [PostController::class, 'deleteComment'])
        ->name('posts.comment.delete');

    Route::post('/publicaciones/{post}/valorar', [PostController::class, 'rate'])
        ->name('posts.rate');
    
    // Recompensas
    Route::get('/recompensas', [RewardController::class, 'index'])
        ->name('rewards.index');

    Route::post('/recompensas/{reward}/canjear', [RewardController::class, 'redeem'])
        ->name('rewards.redeem');

    // Resultados de búsqueda
    Route::get('/buscar', [PostController::class, 'search'])
        ->name('buscar');

    // Tareas grupales
    Route::get('/chats/{conversation}/tasks', [GroupTaskController::class, 'index'])
        ->name('group-tasks.index');

    Route::post('/chats/{conversation}/tasks', [GroupTaskController::class, 'store'])
        ->name('group-tasks.store');

    // Chats
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{conversation}', [ChatController::class, 'show'])->name('chats.show');

    Route::post('/chats/{conversation}/messages', [ChatController::class, 'sendMessage'])
        ->name('chats.messages.store');

    Route::get('/users/search', [UserSearchController::class, 'index'])->name('users.search');

    Route::post('/conversations/private', [ConversationController::class, 'storePrivate'])
        ->name('conversations.private.store');

    Route::post('/conversations/group', [ConversationController::class, 'storeGroup'])
        ->name('conversations.group.store');

    Route::patch('/conversations/{conversation}/name', [ConversationController::class, 'updateName'])
        ->name('conversations.updateName');

    Route::post('/conversations/{conversation}/photo', [ConversationController::class, 'updatePhoto'])
        ->name('conversations.updatePhoto');
});

//socket
    Route::post('/socket/user-offline', [SocketUserController::class, 'offline'])
        ->withoutMiddleware([VerifyCsrfToken::class]);

require __DIR__ . '/settings.php';