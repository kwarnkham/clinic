<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AdminTest extends TestCase
{
    public function test_create_user()
    {
        $roles = Role::all();
        $roles->each(function ($role) {
            $userData = User::factory()->make();
            $response = $this->actingAs($this->admin)->postJson('api/admin/users', [
                ...$userData->toArray(),
                'password' => 'password',
                'password_confirmation' => 'password',
                'role_id' => $role->id
            ]);
            $response->assertCreated();
            $this->assertDatabaseHas('users', $userData->toArray());
        });

        $this->assertDatabaseCount('users', $roles->count() + 1);
        $this->assertDatabaseCount('role_user', $roles->count() + $this->admin->roles->count());
    }
}
