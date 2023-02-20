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
}
