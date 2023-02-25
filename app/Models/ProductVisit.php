<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductVisit extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    public function purchases()
    {
        return $this->belongsToMany(
            Purchase::class,
            'product_visit_purchase',
            foreignPivotKey: 'product_visit_id'
        )
            ->withTimestamps()
            ->withPivot([
                'quantity'
            ]);
    }
}
