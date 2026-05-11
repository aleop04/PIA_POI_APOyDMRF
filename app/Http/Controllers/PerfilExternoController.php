<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

use Illuminate\Http\Request;

class PerfilExternoController extends Controller
{
    public function show(User $user)
    {
        return Inertia::render('Perfilexterno', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'bio' => $user->bio,
                'profile_photo' => $user->profile_photo,
                'cover_photo' => $user->cover_photo,
                'total_points' => $user->total_points,
            ],

            // futuras tablas
            'badges' => [],
            'posts' => [],
        ]);
    }
}
