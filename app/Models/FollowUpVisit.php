<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FollowUpVisit extends Pivot
{
    use HasFactory;

    public function followUp()
    {
        return $this->belongsTo(FollowUp::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}
