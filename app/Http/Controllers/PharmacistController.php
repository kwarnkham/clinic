<?php

namespace App\Http\Controllers;

use App\Enums\VisitStatus;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacistController extends Controller
{
    public function confirmProduts(Visit $visit): JsonResponse
    {
        $visit->status = VisitStatus::CONFIRMED->value;
        $visit->save();
        return response()->json(['visit' => $visit]);
    }
}
