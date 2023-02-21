<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory;

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function purchases(): MorphMany
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    public function visits(): BelongsToMany
    {
        return $this->belongsToMany(Visit::class)
            ->withTimestamps()
            ->withPivot([
                'name',
                'description',
                'sale_price',
                'last_purchase_price',
                'stock',
                'quantity',
                'discount'
            ]);
    }

    public function validateDiscount(int|float $discount): bool
    {
        return $this->sale_price >= $discount;
    }

    public function validateQuantity(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }

    public function populate(array $productData): array
    {
        $productData['name'] = $this->name;
        $productData['description'] = $this->description;
        $productData['sale_price'] = $this->sale_price;
        $productData['last_purchase_price'] = $this->last_purchase_price;
        $productData['stock'] = $this->stock;
        return $productData;
    }

    public function reduceStock(int $quantity): bool
    {
        $this->stock -= $quantity;
        return $this->save();
    }
}
