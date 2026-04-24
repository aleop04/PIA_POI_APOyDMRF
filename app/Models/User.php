<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\Location;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordCustomNotification;
use App\Notifications\VerifyEmailCustomNotification;



#[Fillable(['first_name', 'last_name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function location(): MorphOne
    {
        return $this->morphOne(Location::class, 'locatable');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailCustomNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordCustomNotification($token));
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

}
