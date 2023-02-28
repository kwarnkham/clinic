<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_create_user()
    {
        $roles = Role::all();
        $existedUsers = User::count();
        $roles->each(function ($role) {
            $userData = User::factory()->make();
            $response = $this->actingAs($this->admin)->postJson('api/users', [
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
                $this->cashier->roles->count() +
                $this->pharmacist->roles->count()
        );
    }

    public function test_toggle_role_of_a_user()
    {
        $user = User::factory()->create();
        $role = Role::latest()->first();

        $this->actingAs($this->admin)->postJson(
            'api/users/' . $user->id . '/role',
            ['role_id' => $role->id]
        )->assertOk();

        $this->assertDatabaseHas('role_user', ['user_id' => $user->id, 'role_id' => $role->id]);

        $this->actingAs($this->admin)->postJson(
            'api/users/' . $user->id . '/role',
            ['role_id' => $role->id]
        )->assertOk();

        $this->assertDatabaseMissing('role_user', ['user_id' => $user->id, 'role_id' => $role->id]);
    }

    public function test_update_user()
    {
        $user = User::factory()->create();
        $userData = User::factory()->make();
        $this->actingAs($this->admin)->putJson('api/users/' . $user->id, $userData->toArray())->assertOk();
        $this->assertDatabaseHas('users', $userData->toArray());
    }


    public function test_list_users(): void
    {
        $this->actingAs($this->admin)->getJson('api/users')->assertOk();
    }
}
