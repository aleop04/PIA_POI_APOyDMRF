<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class Location extends Model
{
    protected $fillable = [
        'formatted_address',
        'lat',
        'lng',
        'place_id',
    ];

    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }
}
