<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPhoto extends Model
{
    protected $fillable = [
        'post_id',
        'file_path',
        'original_name',
        'order',
    ];

    protected $appends = [
        'url',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}