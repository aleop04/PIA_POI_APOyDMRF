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




Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

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

    Route::inertia('/publicaciones/crear', 'CrearPublicacion')->name('posts.create');
    
    Route::get('/recompensas', function () {
        return Inertia::render('Recompensas');
    });

    Route::get('/buscar', function (Request $request) {
        return Inertia::render('ResultadosBusqueda', [
            'q' => $request->query('q'),
            'resultados' => [],
        ]);
    })->name('buscar');

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