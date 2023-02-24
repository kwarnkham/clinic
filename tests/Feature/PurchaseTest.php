<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Models\Item;
use App\Models\Product;
use App\Models\Purchase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_list_purchases()
    {
        $this->actingAs($this->admin)->getJson('api/purchases')->assertOk();
    }

    public function test_cancel_a_purchase()
    {
        $stock = fake()->numberBetween(1, 10);
        $purchase = Purchase::factory()->for(Product::factory()->for(Item::factory())->state([
            'stock' => $stock
        ]), 'purchasable')->create(['quantity' => $stock]);


        $this->actingAs($this->admin)->postJson('api/purchases/' . $purchase->id . '/cancel')->assertOk();

        $this->assertEquals($purchase->fresh()->status, PurchaseStatus::CANCELED->value);
        $this->assertEquals(Product::latest('id')->first()->stock, 0);
    }
}
