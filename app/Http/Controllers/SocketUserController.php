<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SocketUserController extends Controller
{
    public function offline(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        User::where('id', $request->user_id)->update([
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
        ]);
    }
}