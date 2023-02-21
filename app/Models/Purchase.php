<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    public function reduceStock(int $quantity): bool
    {
        $this->stock -= $quantity;
        return $this->save();
    }
}
