<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'username' => ['required', 'unique:users,username'],
            'password' => ['required', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id']
        ]);

        $user = User::create(collect($data)->except('role_id')->toArray());
        $user->roles()->attach($data['role_id']);

        return response()->json([
            'user' => $user,
        ], ResponseStatus::CREATED->value);
    }
}
