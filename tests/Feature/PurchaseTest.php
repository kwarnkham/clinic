<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PurchaseTest extends TestCase
{

    public function test_make_a_product_purchase(): void
    {
        $product = Product::factory()->for(Item::factory())->create();
        $price = fake()->numberBetween(1000, 10000);
        $quantity = fake()->numberBetween(1, 10);
        $response = $this->actingAs($this->admin)->postJson(
            "api/products/$product->id/purchase",
            [
                'quantity' => $quantity,
                'price' => $price
            ]
        );
        $response->assertCreated();
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseHas('purchases', [
            'quantity' => $quantity,
            'price' => $price,
            'purchasable_id' => $product->id,
            'purchasable_type' => Product::class
        ]);
    }
}
