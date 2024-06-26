<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

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
                'id',
                'name',
                'description',
                'sale_price',
                'last_purchase_price',
                'quantity',
                'discount',
            ])->using(ProductVisit::class);;
    }

    public function validateDiscount(int|float $discount): bool
    {
        return $this->sale_price >= $discount;
    }

    public function validateQuantity(int $quantity): bool
    {
        if ($this->item->type == ItemType::NON_STOCKED->value) {
            return true;
        }

        return $this->stock >= $quantity;
    }

    public function populate(array $productData): array
    {
        $productData['name'] = $this->name;
        $productData['description'] = $this->description;
        $productData['sale_price'] = $this->sale_price;
        $productData['last_purchase_price'] = $this->last_purchase_price;

        return $productData;
    }

    public function reduceStock(int $quantity): bool
    {
        if ($this->item->type == ItemType::NON_STOCKED->value) {
            return true;
        }
        $this->stock -= $quantity;

        return DB::transaction(function () use ($quantity) {
            $purchases = $this->purchases()->orderBy('expired_on', 'asc')->where('stock', '>', 0)->get();
            $purchases->each(function (Purchase $purchase) use (&$quantity) {
                if ($quantity > 0) {
                    $quantity = $purchase->reduceStock($quantity, $this->pivot);
                }
            });

            return $this->save();
        });
    }

    public static function reverseStock(Collection $products)
    {
        $products->load(['item']);

        return $products->each(function ($product) {
            if ($product->item->type == ItemType::STOCKED->value) {
                $product->pivot->purchases->load(['purchasable']);
                $product->pivot->purchases->each(function ($purchase) {
                    $purchase->stock += $purchase->pivot->quantity;
                    $purchase->save();
                    $product = $purchase->purchasable;
                    $product->stock += $purchase->pivot->quantity;
                    $product->save();
                    $purchase->pivot->delete();
                });
            }
        });
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereRelation('item', 'name', 'like', '%' . $search . '%');
            })
        );

        $query->when(
            $filters['item_id'] ?? null,
            fn (Builder $query, $item_id) => $query->where('item_id', $item_id)
        );

        $query->when(
            $filters['limit'] ?? null,
            fn (Builder $query, $limit) => $query->take($limit)
        );

        if (array_key_exists('max_stock', $filters))
            $query->where('stock', '<=', $filters['max_stock']);
    }
}
