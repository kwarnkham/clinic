<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Visit extends Model
{
    use HasFactory;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
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

    public function addBookFees()
    {
        $product = Product::query()
            ->whereRelation('item', 'type', '=', ItemType::NON_STOCKED->value)
            ->where('name', 'Record book')
            ->first();

        $this->products()->attach($product->id, [
            'name' => $product->name,
            'description' => $product->description,
            'sale_price' => $product->sale_price,
            'stock' => 1,
            'quantity' => 1
        ]);

        $this->amount += $product->sale_price;
        $this->save();
    }
}
