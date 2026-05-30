<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoints extends Model
{
    protected $table = 'user_points';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'group_task_id',
        'points',
        'reason',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function groupTask(): BelongsTo
    {
        return $this->belongsTo(GroupTask::class);
    }
}