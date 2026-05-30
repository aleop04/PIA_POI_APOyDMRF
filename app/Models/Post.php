<?php

namespace App\Models;

use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Post extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'available_from',
        'available_to',
        'opening_days',
        'is_active',
        'views',
    ];

    protected $casts = [
        'opening_days' => 'array',
        'is_active' => 'boolean',
        'views' => 'integer',
    ];

    // Usuario dueño de la publicación
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Fotos de la publicación
    public function photos(): HasMany
    {
        return $this->hasMany(PostPhoto::class);
    }

    // Comentarios
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    // Calificaciones
    public function ratings(): HasMany
    {
        return $this->hasMany(PostRating::class);
    }

    // Ubicación polimórfica
    public function location(): MorphOne
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}