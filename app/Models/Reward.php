<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'cost_points',
        'stock',
        'image',
        'discount_value',
        'is_active',
    ];

    protected $casts = [
        'cost_points' => 'integer',
        'stock' => 'integer',
        'discount_value' => 'integer',
        'is_active' => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }
}