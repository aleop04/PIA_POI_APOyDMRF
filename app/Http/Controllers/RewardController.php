<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\UserPoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RewardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $redeemedBadgeIds = RewardRedemption::query()
            ->where('user_id', $user->id)
            ->whereHas('reward', function ($query) {
                $query->where('type', 'badge');
            })
            ->pluck('reward_id');

        $rewards = Reward::query()
            ->where('is_active', true)
            ->where(function ($query) use ($redeemedBadgeIds) {
                $query->where('type', 'discount')
                    ->orWhere(function ($query) use ($redeemedBadgeIds) {
                        $query->where('type', 'badge')
                            ->whereNotIn('id', $redeemedBadgeIds);
                    });
            })
            ->orderBy('type')
            ->orderBy('cost_points')
            ->get();

        $redemptions = RewardRedemption::query()
            ->where('user_id', $user->id)
            ->with('reward')
            ->latest('redeemed_at')
            ->get();

        return Inertia::render('Recompensas', [
            'userPoints' => $user->total_points,
            'rewards' => $rewards,
            'redemptions' => $redemptions,
        ]);
    }

    public function redeem(Request $request, Reward $reward)
    {
        $user = Auth::user();

        return DB::transaction(function () use ($user, $reward) {
            $reward = Reward::query()
                ->where('id', $reward->id)
                ->lockForUpdate()
                ->firstOrFail();

            $user = $user->fresh();

            if (!$reward->is_active) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Esta recompensa no está disponible.',
                ], 422);
            }

            if ($reward->type === 'badge') {
                $alreadyRedeemed = RewardRedemption::query()
                    ->where('user_id', $user->id)
                    ->where('reward_id', $reward->id)
                    ->exists();

                if ($alreadyRedeemed) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Ya tienes esta insignia.',
                    ], 422);
                }
            }

            if ($user->total_points < $reward->cost_points) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tienes suficientes puntos.',
                ], 422);
            }

            if ($reward->stock !== null && $reward->stock <= 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Esta recompensa está agotada.',
                ], 422);
            }

            RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->cost_points,
                'redeemed_at' => now(),
            ]);

            $user->decrement('total_points', $reward->cost_points);

            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }

            UserPoints::create([
                'user_id' => $user->id,
                'points' => -$reward->cost_points,
                'reason' => "Canje de recompensa: {$reward->name}",
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Recompensa canjeada correctamente.',
                'userPoints' => $user->fresh()->total_points,
                'rewardType' => $reward->type,
            ]);
        });
    }
}