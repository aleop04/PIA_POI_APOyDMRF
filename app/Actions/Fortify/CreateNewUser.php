<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {

        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'username' => ['required', 'string', 'min:4', 'max:20', 'regex:/^[A-Za-z0-9._]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),

            // ubicación
            'formatted_address' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'place_id' => ['nullable', 'string'],

        ])->validate();

        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $user->location()->updateOrCreate(
            [],
            [
                'formatted_address' => $input['formatted_address'],
                'lat' => $input['lat'] ?? null,
                'lng' => $input['lng'] ?? null,
                'place_id' => $input['place_id'] ?? null,
            ]
        );

        return $user;
    }
}