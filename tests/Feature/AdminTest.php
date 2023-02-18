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
        $existedUsers = User::count();
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

        $this->assertDatabaseCount('users', $roles->count() + $existedUsers);
        $this->assertDatabaseCount(
            'role_user',
            $roles->count() +
                $this->admin->roles->count() +
                $this->recepitonist->roles->count() +
                $this->cashier->roles->count()
        );
    }

    public function test_assign_a_role_to_a_user()
    {
        $user = User::factory()->create();
        $role = Role::latest()->first();

        $this->actingAs($this->admin)->postJson(
            'api/admin/users/' . $user->id . '/role',
            ['role_id' => $role->id]
        )->assertOk();

        $this->assertDatabaseHas('role_user', ['user_id' => $user->id, 'role_id' => $role->id]);
    }

    public function test_remove_a_role_from_a_user()
    {
        $user = User::factory()->create();
        $role = Role::latest()->first();

        $this->actingAs($this->admin)->deleteJson(
            'api/admin/users/' . $user->id . '/role',
            ['role_id' => $role->id]
        )->assertOk();

        $this->assertDatabaseMissing('role_user', ['user_id' => $user->id, 'role_id' => $role->id]);
    }
}
