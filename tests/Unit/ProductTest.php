<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_validate_discount()
    {
        $price = fake()->numberBetween(1, 10);
        $product = Product::factory()
            ->for(Item::factory())
            ->create(['sale_price' => $price]);
        $this->assertTrue($product->validateDiscount($price));
        $this->assertTrue($product->validateDiscount($price / 2));
        $this->assertFalse($product->validateDiscount($price + 1));
        $this->assertFalse($product->validateDiscount($price + 0.1));
    }
}
