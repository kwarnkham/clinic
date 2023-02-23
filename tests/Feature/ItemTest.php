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

    public function test_update_item()
    {
        $item = Item::factory()->create();
        $updatedItem = Item::factory()->make();
        $response = $this->actingAs($this->admin)->putJson('api/items/' . $item->id, $updatedItem->toArray());
        $response->assertOk();

        $this->assertDatabaseHas('items', $updatedItem->toArray());
    }
}
