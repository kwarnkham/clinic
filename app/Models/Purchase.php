<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Purchase extends Model
{
    use HasFactory;

    public function productVisits()
    {
        return $this->belongsToMany(ProductVisit::class)
            ->withTimestamps()
            ->withPivot([
                'quantity',
            ]);
    }

    public function reduceStock(int $quantity, ProductVisit $productVisit): int
    {
        if ($quantity <= $this->stock) {
            $this->stock -= $quantity;
            $this->save();
            $productVisit->purchases()->attach([$this->id => ['quantity' => $quantity]]);

            return 0;
        } else {
            $stock = $this->stock;
            $this->stock = 0;
            $this->save();
            $productVisit->purchases()->attach([$this->id => ['quantity' => $stock]]);

            return $quantity - $stock;
        }
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->orWhereRelation('purchasable', 'name', 'like', '%'.$search.'%');
            })
        );

        $query->when(
            $filters['purchasable_id'] ?? null,
            fn (Builder $query, $purchasable_id) => $query->where('purchasable_id', $purchasable_id)
        );

        $query->when(
            $filters['purchasable_type'] ?? null,
            fn (Builder $query, $purchasable_type) => $query->where('purchasable_type', $purchasable_type)
        );
    }
}
