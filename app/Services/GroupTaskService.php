<?php

namespace App\Services;

use App\Models\GroupTask;
use App\Models\PostRating;
use App\Models\User;
use App\Models\UserPoints;
use Illuminate\Support\Facades\DB;

class GroupTaskService
{
    public static function registerIncrementProgress(
        int $userId,
        string $type,
        ?int $conversationId = null
    ): void {
        DB::transaction(function () use ($userId, $type, $conversationId) {
            $conversationIds = DB::table('conversation_user')
                ->where('user_id', $userId)
                ->pluck('conversation_id');

            $tasksQuery = GroupTask::query()
                ->where('type', $type)
                ->whereIn('conversation_id', $conversationIds);

            if ($conversationId !== null) {
                $tasksQuery->where('conversation_id', $conversationId);
            }

            $tasks = $tasksQuery->get();

            foreach ($tasks as $task) {
                self::incrementUserTaskProgress($task, $userId);
            }
        });
    }

    public static function recalculateReviewProgress(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $conversationIds = DB::table('conversation_user')
                ->where('user_id', $userId)
                ->pluck('conversation_id');

            $tasks = GroupTask::query()
                ->where('type', 'comment_and_review')
                ->whereIn('conversation_id', $conversationIds)
                ->get();

            foreach ($tasks as $task) {
                $taskUser = $task->users()
                    ->where('users.id', $userId)
                    ->first();

                if (!$taskUser || $taskUser->pivot->completed_at) {
                    continue;
                }

                $validReviewsCount = self::countValidReviewsAfterTaskCreated($userId, $task);

                $newProgress = min($validReviewsCount, $task->required_amount);
                $completed = $newProgress >= $task->required_amount;

                $task->users()->updateExistingPivot($userId, [
                    'progress' => $newProgress,
                    'completed_at' => $completed ? now() : null,
                    'updated_at' => now(),
                ]);

                if ($completed) {
                    self::rewardGroupIfEveryoneCompleted($task);
                }
            }
        });
    }

    private static function incrementUserTaskProgress(GroupTask $task, int $userId): void
    {
        $taskUser = $task->users()
            ->where('users.id', $userId)
            ->first();

        if (!$taskUser || $taskUser->pivot->completed_at) {
            return;
        }

        $newProgress = min(
            ((int) $taskUser->pivot->progress) + 1,
            $task->required_amount
        );

        $completed = $newProgress >= $task->required_amount;

        $task->users()->updateExistingPivot($userId, [
            'progress' => $newProgress,
            'completed_at' => $completed ? now() : null,
            'updated_at' => now(),
        ]);

        if ($completed) {
            self::rewardGroupIfEveryoneCompleted($task);
        }
    }

    private static function countValidReviewsAfterTaskCreated(int $userId, GroupTask $task): int
    {
        return PostRating::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $task->created_at)
            ->whereHas('post.comments', function ($query) use ($userId, $task) {
                $query->where('user_id', $userId)
                    ->where('created_at', '>=', $task->created_at);
            })
            ->distinct('post_id')
            ->count('post_id');
    }

    private static function rewardGroupIfEveryoneCompleted(GroupTask $task): void
    {
        $task->load('users');

        $members = $task->users;

        if ($members->isEmpty()) {
            return;
        }

        $everyoneCompleted = $members->every(function ($user) {
            return $user->pivot->completed_at !== null;
        });

        if (!$everyoneCompleted) {
            return;
        }

        $alreadyClaimed = $members->every(function ($user) {
            return $user->pivot->claimed_at !== null;
        });

        if ($alreadyClaimed) {
            return;
        }

        foreach ($members as $member) {
            if ($member->pivot->claimed_at !== null) {
                continue;
            }

            User::where('id', $member->id)
                ->increment('total_points', $task->points);

            UserPoints::create([
                'user_id' => $member->id,
                'conversation_id' => $task->conversation_id,
                'group_task_id' => $task->id,
                'points' => $task->points,
                'reason' => "Tarea grupal completada: {$task->description}",
            ]);

            $task->users()->updateExistingPivot($member->id, [
                'claimed_at' => now(),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}