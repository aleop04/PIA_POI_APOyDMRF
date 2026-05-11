<?php

namespace App\Models;

use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Publicacion extends Model
{
    protected $table = 'publicaciones'; // 

    protected $fillable = [
        'titulo',
        'descripcion',
    ];

    public function location(): MorphOne
    {
        return $this->morphOne(Location::class, 'locatable');
    }
}