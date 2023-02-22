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
}
