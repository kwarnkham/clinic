<?php

namespace Tests\Feature;

use Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_list_purchases()
    {
        $this->actingAs($this->admin)->getJson('api/purchases')->assertOk();
    }
}
