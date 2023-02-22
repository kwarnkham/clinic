<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $user = User::where('username', $data['username'])->first();
        abort_if(is_null($user) || !Hash::check($data['password'], $user->password), ResponseStatus::BAD_REQUEST->value, 'Invalid Info');
        $user->tokens()->delete();
        $token = $user->createToken('');
        return response()->json(['token' => $token->plainTextToken, 'user' => $user]);
    }

    public function logout(): JsonResponse
    {
        request()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
