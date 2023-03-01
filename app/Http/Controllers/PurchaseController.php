<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Models\Purchase;

class PurchaseController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required'],
            'purchasable_type' => ['sometimes', 'required'],
            'purchasable_id' => ['sometimes', 'required'],
        ]);
        $query = Purchase::query()->with(['purchasable'])->latest('id')->filter($filters);

        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }

    public function cancel(Purchase $purchase)
    {
        abort_if($purchase->status == PurchaseStatus::CANCELED->value, ResponseStatus::BAD_REQUEST->value, 'Purchase has already been canceled');

        abort_if($purchase->stock != $purchase->quantity, ResponseStatus::BAD_REQUEST->value, 'Cannot be canceled. Order exists');

        $purchase->status = PurchaseStatus::CANCELED->value;
        $purchase->save();

        $product = $purchase->purchasable;
        $product->stock -= $purchase->quantity;
        $product->save();

        return response()->json(['purchase' => $purchase->load(['purchasable'])]);
    }
}
