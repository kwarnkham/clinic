<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use App\Models\Purchase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_create_a_product(): void
    {
        $item = Item::factory()->create();
        $productData = Product::factory()->make();
        $existedProducts = Product::count();
        $response = $this->actingAs($this->admin)->postJson('api/products', ['item_id' => $item->id, ...$productData->toArray()]);
        $response->assertCreated();
        $this->assertDatabaseCount('products', 1 + $existedProducts);
        $this->assertDatabaseHas('products', $productData->toArray());
        $this->assertEquals($response->json()['product']['stock'], 0);
        $this->assertNull($response->json()['product']['last_purchase_price']);
    }

    public function test_make_a_product_purchase(): void
    {
        $product = Product::factory()->for(Item::factory())->create();
        $price = fake()->numberBetween(1000, 10000);
        $quantity = fake()->numberBetween(1, 10);
        $response = $this->actingAs($this->admin)->postJson(
            "api/products/$product->id/purchase",
            [
                'quantity' => $quantity,
                'price' => $price,
            ]
        );
        $response->assertCreated();
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseHas('purchases', [
            'quantity' => $quantity,
            'stock' => $quantity,
            'price' => $price,
            'purchasable_id' => $product->id,
            'purchasable_type' => Product::class,
        ]);
        $this->assertEquals($quantity, $product->fresh()->stock);
        $this->assertEquals($product->fresh()->last_purchase_price, Purchase::first()->price);
    }

    public function test_list_products()
    {
        $this->actingAs($this->admin)->getJson('api/products')->assertOk();
    }

    public function test_show_product()
    {
        $product = Product::factory()->for(Item::factory())->create();
        $this->actingAs($this->admin)->getJson('api/products/'.$product->id)->assertOk();
    }

    public function test_search_product()
    {
        $this->actingAs($this->admin)->getJson('api/products/search')->assertOk();
    }

    public function test_update_product_info()
    {
        $product = Product::factory()->for(Item::factory())->create();
        $productData = Product::factory()->make();
        $this->actingAs($this->admin)->putJson(
            'api/products/'.$product->id,
            $productData->toArray()
        )->assertOk();

        $this->assertDatabaseHas('products', $productData->toArray());
    }
}
