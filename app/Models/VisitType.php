<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitType extends Model
{
    use HasFactory;

    public function followUps()
    {
        return $this->hasMany(FollowUp::class)->orderBy('due_in_days');
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['hasFollowUps'] ?? null,
            fn (Builder $query) => $query->whereHas('followUps')
        );
    }

    public function visits()
    {
        return $this->belongsToMany(Visit::class, 'visit_and_type')
            ->withTimestamps();
    }
}
