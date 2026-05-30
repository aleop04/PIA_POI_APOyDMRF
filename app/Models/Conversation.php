<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\GroupTask;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'name',
        'photo',
        'created_by',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    } 

    public function groupTasks(): HasMany
    {
        return $this->hasMany(GroupTask::class);
    }

}
