<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRating extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'rating',
    ];

    // Publicación calificada
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Usuario que calificó
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}