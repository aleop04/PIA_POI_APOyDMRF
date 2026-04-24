<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Models\Publicacion;
use Illuminate\Http\Request;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;



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
    Route::inertia('/chats', 'Chats')->name('chats');
    Route::inertia('/perfil', 'Perfil')->name('perfil');
    Route::inertia('/publicaciones/crear', 'CrearPublicacion')->name('posts.create');

    // Route::get('/buscar', function (Request $request) {
    //     $q = trim($request->query('q', ''));

    //     $resultados = Publicacion::query()
    //         ->when($q !== '', function ($query) use ($q) {
    //             $query->where(function ($subQuery) use ($q) {
    //                 $subQuery
    //                     ->where('titulo', 'like', "%{$q}%")
    //                     ->orWhere('descripcion', 'like', "%{$q}%")
    //                     ->orWhereHas('location', function ($locationQuery) use ($q) {
    //                         $locationQuery->where('formatted_address', 'like', "%{$q}%");
    //                     });
    //             });
    //         })
    //         ->with('location')
    //         ->get();

    //     return Inertia::render('ResultadosBusqueda', [
    //         'q' => $q,
    //         'resultados' => $resultados,
    //     ]);
    // })->name('buscar');

    Route::get('/buscar', function (Request $request) {
        return Inertia::render('ResultadosBusqueda', [
            'q' => $request->query('q'),
            'resultados' => [],
        ]);
    })->name('buscar');

    Route::get('/users/search', [UserSearchController::class, 'index'])
    ->name('users.search');

    Route::get('/chats', [ChatController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('chats.index');

    Route::get('/chats/{conversation}', [ChatController::class, 'show'])
    ->name('chats.show');

    Route::post('/chats/{conversation}/messages', [ChatController::class, 'sendMessage'])
    ->name('chats.messages.store');

    Route::put('/conversations/{conversation}/name', [ConversationController::class, 'updateName'])
    ->name('conversations.updateName');

    Route::post('/conversations/{conversation}/photo', [ConversationController::class, 'updatePhoto'])
    ->name('conversations.updatePhoto');

    Route::post('/conversations/group', [ConversationController::class, 'storeGroup'])
    ->middleware(['auth', 'verified']);

    Route::post('/conversations/private', [ConversationController::class, 'storePrivate'])
    ->middleware(['auth', 'verified']);

});

require __DIR__.'/settings.php';