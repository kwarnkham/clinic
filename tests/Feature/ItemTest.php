<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ItemTest extends TestCase
{
    public function test_add_an_item()
    {
        $itemData = Item::factory()->make();
        $response = $this->actingAs($this->admin)->postJson('api/items', $itemData->toArray());
        $response->assertCreated();
        $this->assertDatabaseHas('items', $itemData->toArray());
        $this->assertDatabaseCount('items', 1);
    }
}
