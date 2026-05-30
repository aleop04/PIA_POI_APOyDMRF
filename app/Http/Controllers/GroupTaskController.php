<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupTaskController extends Controller
{
    public function index(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->type !== 'group') {
            abort(404);
        }

        $isMember = $conversation->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$isMember) {
            abort(403, 'No tienes permiso para ver estas tareas grupales.');
        }

        $tasks = $conversation->groupTasks()
            ->with('creator:id,username,profile_photo')
            ->latest()
            ->get()
            ->map(function ($task) use ($user) {
                $taskUser = $task->users()
                    ->where('users.id', $user->id)
                    ->first();

                $pivot = $taskUser?->pivot;

                $membersCount = $task->users()->count();

                $completedCount = $task->users()
                    ->wherePivotNotNull('completed_at')
                    ->count();

                $isGroupCompleted = $membersCount > 0 && $membersCount === $completedCount;

                return [
                    'id' => $task->id,
                    'description' => $task->description,
                    'type' => $task->type,
                    'required_amount' => $task->required_amount,
                    'points' => $task->points,
                    'created_at' => $task->created_at,

                    'creator' => $task->creator,

                    'progress' => $pivot?->progress ?? 0,
                    'completed_at' => $pivot?->completed_at,
                    'claimed_at' => $pivot?->claimed_at,

                    'members_count' => $membersCount,
                    'completed_count' => $completedCount,
                    'is_group_completed' => $isGroupCompleted,
                ];
            });

        return response()->json($tasks);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->type !== 'group') {
            abort(404);
        }

        $isMember = $conversation->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$isMember) {
            abort(403, 'No tienes permiso para crear tareas grupales en esta conversación.');
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:comment_and_review,publish,send_messages'],
            'required_amount' => ['required', 'integer', 'min:1'],
            'points' => ['required', 'integer', 'min:1'],
        ]);

        $duplicatedActiveTask = $conversation->groupTasks()
            ->where('type', $validated['type'])
            ->where('required_amount', $validated['required_amount'])
            ->where('points', $validated['points'])
            ->whereHas('users', function ($query) {
                $query->whereNull('group_task_user.claimed_at');
            })
            ->exists();

        if ($duplicatedActiveTask) {
            return response()->json([
                'ok' => false,
                'message' => 'Esta tarea ya está activa en el grupo. Podrás agregarla de nuevo cuando el grupo la complete.',
            ], 422);
        }

        $task = $conversation->groupTasks()->create([
            'description' => $validated['description'],
            'type' => $validated['type'],
            'required_amount' => $validated['required_amount'],
            'points' => $validated['points'],
            'created_by' => $user->id,
        ]);

        $memberIds = $conversation->users()
            ->pluck('users.id')
            ->toArray();

        $task->users()->syncWithoutDetaching(
            collect($memberIds)
                ->mapWithKeys(fn ($memberId) => [
                    $memberId => [
                        'progress' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ])
                ->toArray()
        );

        return response()->json([
            'ok' => true,
            'task' => $task->load('creator:id,username,profile_photo'),
        ]);
    }
}