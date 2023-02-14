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

    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => ['exists:roles,id']
        ]);

        $user->roles()->attach($data['role_id']);

        return response()->json([
            'user' => $user->load(['roles'])
        ]);
    }

    public function removeRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => ['exists:roles,id']
        ]);

        $user->roles()->detach($data['role_id']);

        return response()->json([
            'user' => $user->load(['roles'])
        ]);
    }
}
