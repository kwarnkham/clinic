<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitType extends Model
{
    use HasFactory;

    public function followUps()
    {
        return $this->hasMany(FollowUp::class)->orderBy('due_in_days');
    }
}
