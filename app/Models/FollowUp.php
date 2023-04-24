<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    public function visits()
    {
        return $this->belongsToMany(Visit::class)
            ->withPivot(['due_on', 'status', 'count_from'])
            ->withTimestamps()
            ->using(FollowUpVisit::class);
    }

    public function visitType()
    {
        return $this->belongsTo(VisitType::class);
    }
}
