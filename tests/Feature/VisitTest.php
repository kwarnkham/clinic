<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Models\Item;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ProductVisit;
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
            'patient_id' => $patient->id,
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
            'with_book_fees' => 1,
        ]);
        $response->assertOk();
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', ['patient_id' => $patient->id]);
        $this->assertDatabaseCount('product_visit', 1);
        $this->assertEquals(Product::first()->sale_price, Visit::first()->amount);
    }

    public function test_add_products_to_a_visit(): void
    {
        //register a patient with book fees
        $patientData = Patient::factory()->make();
        $response = $this->actingAs($this->recepitonist)->postJson('api/patients', [
            ...$patientData->toArray(),
            'with_book_fees' => 1,
        ]);
        $response->assertCreated();

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('product_visit', 1);

        //prepare test data
        $visit = Visit::first();
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
                    'expired_on' => now()->addMonths(3)->format('Y-m-d'),
                ]
            );
            $this->actingAs($this->admin)->postJson(
                "api/products/$product->id/purchase",
                [
                    'quantity' => $quantity,
                    'price' => $purchase_price,
                    'expired_on' => now()->addMonths(2)->format('Y-m-d'),
                ]
            );
            $this->actingAs($this->admin)->postJson(
                "api/products/$product->id/purchase",
                [
                    'quantity' => $quantity,
                    'price' => $purchase_price,
                    'expired_on' => now()->addMonths(4)->format('Y-m-d'),
                ]
            );
        });
        $this->assertDatabaseCount('purchases', $products->count() * 3);

        //prepare visit product data
        $productVisitData = $products->map(fn ($product) => [
            'id' => $product->id,
            'quantity' => $quantity * 2,
            'discount' => $discount,
        ])->values()->toArray();

        for ($i = 0; $i < 2; $i++) {
            $response = $this->actingAs($this->cashier)
                ->postJson('api/visits/' . $visit->id . '/products', [
                    'products' => $productVisitData,
                    'discount' => $visitDiscount,
                    'status' => VisitStatus::PRODUCTS_ADDED->value,
                ]);

            $response->assertOk();
            $this->assertDatabaseCount('product_visit', $count);
            $this->assertDatabaseCount('product_visit', ProductVisit::count());
            $this->assertDatabaseHas('product_visit', [
                ...$products->only([
                    'name',
                    'description',
                    'sale_price',
                    'latest_purchase_price',
                ])->toArray(), 'quantity' => $quantity * 2, 'discount' => $discount,
            ]);
            $this->assertDatabaseCount('product_visit_purchase', $products->count() * 2);
            $this->assertEquals(VisitStatus::PRODUCTS_ADDED->value, $visit->fresh()->status);
            $this->assertTrue(
                abs($visit->fresh()->amount -
                    $products->reduce(fn ($carry, $v) => $carry + (($v->sale_price - $discount) * $quantity * 2), 0)) < 1
            );

            $products->fresh(['purchases'])->each(function ($product) use ($quantity) {
                $this->assertEquals($product->stock, $quantity);
                $this->assertEquals($product->purchases()->orderBy('expired_on', 'asc')->first()->stock, 0);
                $this->assertEquals($product->purchases()->orderBy('expired_on', 'desc')->first()->stock, $quantity);
            });
        }

        // add a new product
        $product = Product::factory()
            ->for(Item::factory())
            ->create([
                'sale_price' => $sale_price,
            ]);

        $this->actingAs($this->admin)->postJson(
            "api/products/$product->id/purchase",
            [
                'quantity' => $quantity,
                'price' => $purchase_price,
                'expired_on' => now()->addMonths(3)->format('Y-m-d'),
            ]
        );
        $this->assertDatabaseCount('purchases', ($products->count() * 3) + 1);

        $productVisitData = $products->map(fn ($product) => [
            'id' => $product->id,
            'quantity' => $quantity * 2,
            'discount' => $discount,
        ])->values()->toArray();

        array_push($productVisitData, [
            'id' => $product->id,
            'quantity' => $quantity,
        ]);

        $response = $this->actingAs($this->cashier)
            ->postJson('api/visits/' . $visit->id . '/products', [
                'products' => $productVisitData,
                'discount' => $visitDiscount,
                'status' => VisitStatus::PRODUCTS_ADDED->value,
            ]);

        $response->assertOk();

        $this->assertDatabaseCount('product_visit', $count + 1);
        $this->assertDatabaseCount('product_visit', ProductVisit::count());

        $this->assertDatabaseCount('product_visit_purchase', ($products->count() * 2) + 1);
        $this->assertEquals(VisitStatus::PRODUCTS_ADDED->value, $visit->fresh()->status);

        $amount = $products->reduce(fn ($carry, $v) => $carry + (($v->sale_price - $discount) * $quantity * 2), 0) + ($product->sale_price * $quantity);
        $this->assertTrue(abs($visit->fresh()->amount - $amount) < 1);
        $this->assertEquals($product->fresh()->stock, 0);
        Product::with(['purchases'])->get()->each(function ($product) {
            $purchase = $product->purchases()->orderBy('expired_on', 'asc')->first();
            if ($purchase) {
                $this->assertEquals($purchase->stock, 0);
            }
        });

        //cancel the visit
        $response = $this->actingAs($this->cashier)
            ->postJson('api/visits/' . $visit->id . '/products', [
                'products' => [],
                'status' => VisitStatus::CANCELED->value,
            ]);
        $response->assertOk();
        $this->assertEquals($visit->fresh()->status, VisitStatus::CANCELED->value);
        $this->assertEquals($visit->products->count(), 0);
        $this->assertDatabaseCount('product_visit_purchase', 0);
        $this->assertDatabaseCount('product_visit', 0);

        $this->assertEquals(
            $product->fresh(['purchases'])->stock,
            $product->purchases->reduce(fn ($carry, $p) => $carry + $p->stock, 0)
        );
        $this->assertEquals(
            $product->fresh()->stock,
            $product->purchases->reduce(fn ($carry, $p) => $carry + $p->quantity, 0)
        );

        $products->fresh(['purchases'])->each(function ($product) {
            $this->assertEquals(
                $product->fresh()->stock,
                $product->purchases->reduce(fn ($carry, $p) => $carry + $p->stock, 0)
            );
            $this->assertEquals(
                $product->fresh()->stock,
                $product->purchases->reduce(fn ($carry, $p) => $carry + $p->quantity, 0)
            );
        });
    }

    public function test_list_visits()
    {
        $this->actingAs($this->admin)->getJson('api/visits')->assertOk();
    }
}
