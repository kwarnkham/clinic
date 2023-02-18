<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_create_a_product(): void
    {
        $item = Item::factory()->create();
        $productData = Product::factory()->make();
        $response = $this->actingAs($this->admin)->postJson('api/products', ['item_id' => $item->id, ...$productData->toArray()]);
        $response->assertCreated();
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', $productData->toArray());
    }
}
