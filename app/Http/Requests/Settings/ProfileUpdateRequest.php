<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-]+$/u'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:20',
                'regex:/^[A-Za-z0-9._]+$/',
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],

            // ubicación
            'formatted_address' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'place_id' => ['nullable', 'string'],
        ];
    }
}