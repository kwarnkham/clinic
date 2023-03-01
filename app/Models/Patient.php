<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    public static function generateCode()
    {
        $year = now()->year;
        $latestPatient = static::query()->whereYear('created_at', $year)->latest('id')->first();

        if (is_null($latestPatient)) {
            $code = '1';
            while (strlen($code) < 7) {
                $code = '0'.$code;
            }
        } else {
            $code = (int) substr($latestPatient->code, 5) + 1;
            while (strlen($code) < 7) {
                $code = '0'.$code;
            }
        }

        return 'CPI'.substr($year, 2).$code;
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function scopeFilter(Builder $query, array $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('code', 'like', '%'.$search.'%');
            })
        );
    }
}
