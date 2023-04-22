<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\VisitStatus;
use App\Events\ProductAddedToVisit;
use App\Events\VisitCanceled;
use App\Events\VisitCompleted;
use App\Events\VisitConfirmed;
use App\Events\VisitCreated;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Visit extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Visit $visit) {
            VisitCreated::dispatch($visit->unsetRelations());
        });

        static::updated(function (Visit $visit) {
            if ($visit->status == VisitStatus::PRODUCTS_ADDED->value)            ProductAddedToVisit::dispatch($visit->unsetRelations());
            else if ($visit->status == VisitStatus::CONFIRMED->value)
                VisitConfirmed::dispatch($visit->unsetRelations());
            else if ($visit->status == VisitStatus::COMPLETED->value)
                VisitCompleted::dispatch($visit->unsetRelations());
            else if ($visit->status == VisitStatus::CANCELED->value)
                VisitCanceled::dispatch($visit->unsetRelations());
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visitTypes()
    {
        return $this->belongsToMany(VisitType::class, 'visit_and_type')
            ->withTimestamps();
    }

    public function followUps()
    {
        return $this->belongsToMany(FollowUp::class)
            ->withPivot(['due_on', 'status'])
            ->withTimestamps()
            ->using(FollowUpVisit::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps()
            ->withPivot([
                'id',
                'name',
                'description',
                'sale_price',
                'last_purchase_price',
                'quantity',
                'discount',
            ])->using(ProductVisit::class);
    }

    public function addBookFees()
    {
        $product = Product::query()
            ->whereRelation('item', 'type', '=', ItemType::NON_STOCKED->value)
            ->first();

        $this->products()->attach($product->id, [
            'name' => $product->name,
            'description' => $product->description,
            'sale_price' => $product->sale_price,
            'quantity' => 1,
        ]);

        $this->amount += $product->sale_price;
        $this->save();
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when(
            $filters['statuses'] ?? null,
            fn (Builder $query, $statuses) => $query->whereIn('status', explode(',', $statuses))
        );

        $query->when(
            $filters['type'] ?? null,
            fn (Builder $query, $type) => $query->whereRelation('visitTypes', 'visit_type_id', $type)
        );
    }
}
