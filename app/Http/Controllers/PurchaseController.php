<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required'],
            'purchasable_type' => ['sometimes', 'required'],
            'purchasable_id' => ['sometimes', 'required']
        ]);
        $query = Purchase::query()->with(['purchasable'])->filter($filters);
        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }
}
