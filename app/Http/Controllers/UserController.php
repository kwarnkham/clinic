<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function checkToken()
    {
        return response()->json([
            'user' => request()->user()
        ]);
    }

    public function index()
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required']
        ]);
        $query = User::query()->latest('id')->filter($filters);
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 20)
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'username' => ['required', 'unique:users,username'],
            'role_id' => ['required', 'exists:roles,id']
        ]);

        $user = User::create(
            [
                ...collect($data)->except('role_id')->toArray(),
                'password' => bcrypt(('password'))
            ]
        );

        $user->roles()->attach($data['role_id']);

        return response()->json([
            'user' => $user->load(['roles']),
        ], ResponseStatus::CREATED->value);
    }

    public function toggleRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id' => ['exists:roles,id']
        ]);

        abort_if($data['role_id'] == 1, ResponseStatus::BAD_REQUEST->value, 'Cannot modify admin role');

        $user->roles()->toggle($data['role_id']);

        return response()->json([
            'user' => $user->load(['roles'])
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => ['required', Rule::unique('users', 'username')->ignore($user->id)],
            'name' => ['required']
        ]);

        $user->update($data);

        return response()->json([
            'user' => $user->load(['roles'])
        ]);
    }
}
