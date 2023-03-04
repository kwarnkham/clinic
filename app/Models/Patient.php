<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::deleted(function (Patient $patient) {
            $patient->visits()->with(['products'])
                ->whereIn('status', [
                    VisitStatus::PENDING->value,
                    VisitStatus::PRODUCTS_ADDED->value,
                    VisitStatus::CONFIRMED->value,
                ])->get()->each(function ($visit) {
                    DB::transaction(function () use ($visit) {
                        $visit->status = VisitStatus::CANCELED->value;
                        $visit->save();
                        Product::reverseStock($visit->products);
                    });
                });
        });
    }

    public static function generateCode()
    {
        $year = now()->year;
        $latestPatient = static::query()->whereYear('created_at', $year)->latest('id')->first();

        if (is_null($latestPatient)) {
            $code = '1';
            while (strlen($code) < 7) {
                $code = '0' . $code;
            }
        } else {
            $code = (int) substr($latestPatient->code, 5) + 1;
            while (strlen($code) < 7) {
                $code = '0' . $code;
            }
        }

        return 'CPI' . substr($year, 2) . $code;
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
                $query->where('code', 'like', '%' . $search . '%');
            })
        );
    }
}
