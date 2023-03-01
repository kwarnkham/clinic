<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_list_roles(): void
    {
        $this->actingAs($this->admin)->getJson('api/roles')->assertOk();
    }
}
