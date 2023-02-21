<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Models\Item;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Visit;
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
        // all products are at stock zero
        $products = Product::factory($count)
            ->for(Item::factory())
            ->create([
                'sale_price' => $sale_price,
            ]);

        // make product purchase to increase stock
        $products->each(function ($product) use ($quantity, $purchase_price) {
            $this->actingAs($this->admin)->postJson(
                "api/products/$product->id/purchase",
                [
                    'quantity' => $quantity,
                    'price' => $purchase_price,
                    'expired_on' => now()->addMonths(3)->format('Y-m-d')
                ]
            );
        });

        $this->assertDatabaseCount('purchases', $products->count());

        // make product purchase again with different expire date
        $products->each(function ($product) use ($quantity, $purchase_price) {
            $this->actingAs($this->admin)->postJson(
                "api/products/$product->id/purchase",
                [
                    'quantity' => $quantity,
                    'price' => $purchase_price,
                    'expired_on' => now()->addMonth()->format('Y-m-d')
                ]
            );
        });

        $this->assertDatabaseCount('purchases', $products->count() * 2);

        // record a new visit
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
        $this->assertEquals(VisitStatus::PRODUCTS_ADDED->value, $visit->fresh()->status);

        $products->fresh()->load(['purchases'])->each(function ($product) use ($quantity) {
            $this->assertEquals($product->stock, $quantity);

            $this->assertEquals($product->purchases()->orderBy('expired_on', 'asc')->first()->stock, 0);
            $this->assertEquals($product->purchases()->orderBy('expired_on', 'desc')->first()->stock, $quantity);

            $this->assertEquals($product->purchases()->first()->stock, $quantity);
            $this->assertEquals($product->purchases()->latest('id')->first()->stock, 0);
        });
    }
}
