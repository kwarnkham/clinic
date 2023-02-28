<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{

    public function test_login(): void
    {
        $user = User::factory()->create();
        $this->postJson('api/login', [
            'username' => $user->username,
            'password' => 'password'
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_check_token()
    {
        $this->actingAs($this->admin)->getJson('api/users/token')->assertOk();
    }

    public function test_logout()
    {
        $this->actingAs($this->admin)->deleteJson('api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_change_password()
    {
        $user = User::factory()->create();
        $password = fake()->lastName();
        $user = User::where('id', $user->id)->first();

        $this->actingAs(User::where('id', $user->id)->first())->postJson('api/change-password', [
            'password' => 'password',
            'new_password' => $password,
            'new_password_confirmation' => $password
        ])->assertOk();

        $this->actingAs($user)->postJson('api/login', [
            'username' => $user->username,
            'password' => $password
        ])->assertOk();
    }

    public function test_reset_password()
    {
        $user = User::factory()->create();
        $password = fake()->lastName();
        $user = User::where('id', $user->id)->first();

        $this->actingAs(User::where('id', $user->id)->first())->postJson('api/change-password', [
            'password' => 'password',
            'new_password' => $password,
            'new_password_confirmation' => $password
        ])->assertOk();

        $this->actingAs($user)->postJson('api/login', [
            'username' => $user->username,
            'password' => $password
        ])->assertOk();

        $this->actingAs($this->admin)->postJson('api/users/' . $user->id . '/reset-password')->assertOk();

        $this->actingAs($user)->postJson('api/login', [
            'username' => $user->username,
            'password' => 'password'
        ])->assertOk();
    }
}
