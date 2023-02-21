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
}
