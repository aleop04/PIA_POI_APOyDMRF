<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'comment',
        'is_edited',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
    ];

    // Publicación comentada
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Usuario que comentó
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
