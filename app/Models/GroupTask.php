<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GroupTask extends Model
{
    protected $fillable = [
        'conversation_id',
        'description',
        'type',
        'required_amount',
        'points',
        'created_by',
    ];

    protected $casts = [
        'required_amount' => 'integer',
        'points' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_task_user')
            ->withPivot([
                'progress',
                'completed_at',
                'verified_at',
                'claimed_at',
            ])
            ->withTimestamps();
    }
}