<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CashierTest extends TestCase
{

    public function test_add_products_to_a_visit(): void
    {
        $patient = Patient::factory()->create();
        $count = rand(1, 10);
        $quantity = rand(1, 10);
        $purchase_price = rand(1000, 10000);
        $sale_price = $purchase_price + $purchase_price * 0.2;
        $discount = $sale_price * 0.1;
        $products = Product::factory($count)
            ->for(Item::factory())
            ->has(Purchase::factory()->state([
                'quantity' => $quantity,
                'stock' => $quantity,
                'price' => $purchase_price
            ]))
            ->create([
                'sale_price' => $sale_price,
                'stock' => $quantity
            ]);

        $this->actingAs($this->recepitonist)
            ->postJson('api/receptionist/patients', $patient->toArray());
        $visit = Visit::first();
        $response = $this->actingAs($this->cashier)
            ->postJson('api/cashier/visits/' . $visit->id . '/products', [
                'products' => $products->map(fn ($product) => [
                    'id' => $product->id,
                    'quantity' => $quantity,
                    'discount' => $discount
                ])->values()->toArray()
            ]);
        $response->assertOk();
        $this->assertDatabaseCount('product_visit', $count);
        $this->assertDatabaseHas('product_visit', [...$products->only(['name', 'description', 'sale_price', 'latest_purchase_price', 'stock'])->toArray(), 'quantity' => $quantity, 'discount' => $discount]);
        $products->fresh()->each(function ($product) {
            $this->assertEquals($product->stock, 0);
        });
    }
}
