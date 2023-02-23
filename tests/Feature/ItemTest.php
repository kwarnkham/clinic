<?php

namespace Tests\Feature;

use App\Models\Item;
use Tests\TestCase;

class ItemTest extends TestCase
{
    public function test_add_an_item()
    {
        $itemData = Item::factory()->make();
        $existedItems = Item::count();
        $response = $this->actingAs($this->admin)->postJson('api/items', $itemData->toArray());
        $response->assertCreated();
        $this->assertDatabaseHas('items', $itemData->toArray());
        $this->assertDatabaseCount('items', 1 + $existedItems);
    }

    public function test_list_items()
    {
        $this->actingAs($this->admin)->getJson('api/items')->assertOk();
    }
}
