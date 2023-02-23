<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'name' => ['required'],
            'description' => ['nullable'],
            'sale_price' => ['required'],
        ]);
        $product = Product::create($data);
        return response()->json([
            'product' => $product->fresh()
        ], ResponseStatus::CREATED->value);
    }

    public function index(): JsonResponse
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required'],
            'item_id' => ['exists:items,id']
        ]);
        $query = Product::query()->filter($filters);
        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }

    public function purchase(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'expired_on' => ['nullable', 'date']
        ]);
        $purchase = DB::transaction(function () use ($product, $data) {
            $purchase = $product->purchases()->create(
                [
                    ...$data,
                    'stock' => $data['quantity']
                ]
            );
            $product->stock += $data['quantity'];
            $product->save();
            return $purchase;
        });

        return response()->json([
            'purchase' => $purchase
        ], ResponseStatus::CREATED->value);
    }
}
