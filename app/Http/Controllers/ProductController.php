<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function purchase(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
        ]);
        $purchase = $product->purchases()->create($data);
        return response()->json([
            'purchase' => $purchase
        ], ResponseStatus::CREATED->value);
    }
}
