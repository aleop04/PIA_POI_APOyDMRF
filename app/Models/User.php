<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\Location;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostRating;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordCustomNotification;
use App\Notifications\VerifyEmailCustomNotification;

class User extends Authenticatable implements MustVerifyEmail
{

    protected $fillable = [
        'first_name', 
        'last_name', 
        'username', 
        'email', 
        'password', 
        'bio',            
        'profile_photo',  
        'cover_photo'    
    ];

    protected $hidden = [
        'password', 
        'remember_token'
    ];

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

     // Publicaciones creadas por el usuario
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Comentarios realizados por el usuario
    public function postComments()
    {
        return $this->hasMany(PostComment::class);
    }

    // Calificaciones realizadas por el usuario
    public function postRatings()
    {
        return $this->hasMany(PostRating::class);
    }

    public function rewardRedemptions()
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function points()
    {
        return $this->hasMany(UserPoints::class);
    }

    public function groupTasks()
    {
        return $this->belongsToMany(GroupTask::class, 'group_task_user')
            ->withPivot([
                'progress',
                'completed_at',
                'verified_at',
                'claimed_at',
            ])
            ->withTimestamps();
    }

}
