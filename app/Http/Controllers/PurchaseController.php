<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

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

    public function report()
    {
        $filters = request()->validate([
            'from' => ['sometimes', 'required', 'date'],
            'to' => ['sometimes', 'required', 'date']
        ]);
        $data = DB::table('purchases')
            ->join('products', 'products.id', '=', 'purchases.purchasable_id')
            ->where([
                ['purchases.purchasable_type', '=', 'App\\Models\\Product'],
                ['purchases.created_at', '>=', $filters['from']],
                ['purchases.created_at', '<=', $filters['to']],
            ])
            ->select([
                'purchases.id',
                'products.name',
                'purchases.quantity',
                'purchases.stock',
                'purchases.price',
                'purchases.status',
                'purchases.expired_on'
            ])->where('item_id', '!=', 1)->get();
        return response()->json(['data' => $data]);
    }
}
