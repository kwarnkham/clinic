<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleTest extends TestCase
{

    public function test_list_roles(): void
    {
        $this->actingAs($this->admin)->getJson('api/roles')->assertOk();
    }
}
