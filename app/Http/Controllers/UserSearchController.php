<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->query('q', ''));

        if ($search === '') {
            return response()->json([]);
        }

        $users = User::query()
            ->where('username', 'like', "%{$search}%")
            ->limit(8)
            ->get([
                'id',
                'first_name',
                'last_name',
                'username',
            ]);

        return response()->json($users);
    }
}