<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Models\Item;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Visit;
use Tests\TestCase;

class VisitTest extends TestCase
{

    public function test_show_a_visit()
    {
        $visit = Visit::factory()->for(Patient::factory())->create();
        $this->actingAs($this->admin)->getJson('api/visits/' . $visit->id)->assertOk();
    }
    public function test_record_a_visit()
    {
        $patient = Patient::factory()->create();
        $response = $this->actingAs($this->recepitonist)->postJson('api/visits', [
            'patient_id' => $patient->id
        ]);
        $response->assertOk();
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', ['patient_id' => $patient->id]);
    }

    public function test_record_a_visit_with_book_fees()
    {
        $patient = Patient::factory()->create();
        $response = $this->actingAs($this->recepitonist)->postJson('api/visits', [
            'patient_id' => $patient->id,
            'with_book_fees' => 1
        ]);
        $response->assertOk();
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', ['patient_id' => $patient->id]);
        $this->assertDatabaseCount('product_visit', 1);
        $this->assertEquals(Product::first()->sale_price, Visit::first()->amount);
    }

    public function test_add_products_to_a_visit(): void
    {
        $patient = Patient::factory()->create();
        $count = rand(1, 10);
        $quantity = rand(1, 10);
        $purchase_price = rand(1000, 10000);
        $sale_price = $purchase_price + $purchase_price * 0.2;
        $discount = $sale_price * 0.1;
        $visitDiscount = rand(1, 5);
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
            ->postJson('api/visits', [
                'patient_id' => $patient->id,
                'with_book_fees' => 1
            ]);

        $this->assertDatabaseCount('product_visit', 1);

        $visit = Visit::first();

        $productVisitData = $products->map(fn ($product) => [
            'id' => $product->id,
            'quantity' => $quantity,
            'discount' => $discount
        ])->values()->toArray();

        $response = $this->actingAs($this->cashier)
            ->postJson('api/visits/' . $visit->id . '/products', [
                'products' => $productVisitData,
                'discount' => $visitDiscount
            ]);

        $response->assertOk();
        $this->assertDatabaseCount('product_visit', $count);
        $this->assertDatabaseHas('product_visit', [...$products->only(['name', 'description', 'sale_price', 'latest_purchase_price', 'stock'])->toArray(), 'quantity' => $quantity, 'discount' => $discount]);
        $this->assertEquals(VisitStatus::PRODUCTS_ADDED->value, $visit->fresh()->status);
        $this->assertTrue(
            abs($visit->fresh()->amount -
                $products->reduce(fn ($carry, $v) => $carry + (($v->sale_price - $discount) * $quantity), 0)) < 1
        );
        $products->fresh()->load(['purchases'])->each(function ($product) use ($quantity) {
            $this->assertEquals($product->stock, $quantity);

            $this->assertEquals($product->purchases()->orderBy('expired_on', 'asc')->first()->stock, 0);
            $this->assertEquals($product->purchases()->orderBy('expired_on', 'desc')->first()->stock, $quantity);

            $this->assertEquals($product->purchases()->first()->stock, $quantity);
            $this->assertEquals($product->purchases()->latest('id')->first()->stock, 0);
        });
    }

    public function test_confirm_products_in_a_visit()
    {
        $visit = Visit::factory()->for(Patient::factory())->create();
        $response = $this->actingAs($this->pharmacist)->postJson('api/visits/' . $visit->id . '/confirm');
        $response->assertOk();
        $this->assertEquals($visit->fresh()->status, VisitStatus::CONFIRMED->value);
    }

    public function test_complete_a_visit()
    {
        $visit = Visit::factory()->for(Patient::factory())->create();
        $response = $this->actingAs($this->cashier)->postJson('api/visits/' . $visit->id . '/complete');
        $response->assertOk();
        $this->assertEquals($visit->fresh()->status, VisitStatus::COMPLETED->value);
    }

    public function test_cancel_a_visit()
    {
        $visit = Visit::factory()->for(Patient::factory())->create();
        $response = $this->actingAs($this->cashier)->postJson('api/visits/' . $visit->id . '/cancel');
        $response->assertOk();
        $this->assertEquals($visit->fresh()->status, VisitStatus::CANCELED->value);
    }

    public function test_list_visits()
    {
        $this->actingAs($this->admin)->getJson('api/visits')->assertOk();
    }
}
