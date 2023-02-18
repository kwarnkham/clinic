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
            'product' => $product
        ], ResponseStatus::CREATED->value);
    }
}
