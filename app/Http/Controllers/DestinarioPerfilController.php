<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class DestinarioPerfilController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user()->load('location');
        return Inertia::render('Perfil', [
            'user' => [
                'id' => $request->user()->id,
                'first_name' => $request->user()->first_name,
                'last_name' => $request->user()->last_name,
                'username' => $request->user()->username,
                'email' => $request->user()->email,
                'bio' => $request->user()->bio,
                'profile_photo' => $request->user()->profile_photo,
                'cover_photo' => $request->user()->cover_photo,
                'total_points' => $request->user()->total_points,
                'location' => $user->location?->formatted_address,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'username' => ['required', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
            'formatted_address' => ['nullable', 'string'],
            'lat' => ['nullable'],
            'lng' => ['nullable'],
            'place_id' => ['nullable'],

            // CONTRASEÑA 
            'password' => [
            'nullable',
            'string',
            Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
            'confirmed',
            ],
        ]);

        $user = $request->user();

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'bio' => $validated['bio'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->save();
        }

        if (!empty($validated['password'])) {
        $user->password = Hash::make($validated['password']);
        $user->save();
        }

        $user->update($data);

        if (!empty($validated['formatted_address'])) {
            $user->location()->updateOrCreate([], [
                'formatted_address' => $validated['formatted_address'],
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'place_id' => $validated['place_id'] ?? null,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        $user->update(['profile_photo' => $path]);

        return back(); 
    }

    public function updateCoverPhoto(Request $request)
    {
        $request->validate([
            'cover_photo' => ['required', 'image', 'max:10240'],
        ]);

        $path = $request->file('cover_photo')->store('cover-photos', 'public');

        $request->user()->update([
            'cover_photo' => $path,
        ]);

        return back(); 
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

}
