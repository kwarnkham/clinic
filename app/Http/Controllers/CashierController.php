<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    public function recordProduct(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'products' => ['required', 'array'],
            'products.*' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id', 'distinct'],
            'products.*.quantity' => ['required', 'numeric'],
            'products.*.discount' => ['nullable', 'numeric']
        ]);

        $products = Product::query()
            ->whereIn('id', array_values(array_map(
                fn ($value) => $value['id'],
                $data['products']
            )))
            ->get();

        $data['products'] = array_map(function ($productData) use ($products) {
            $product = $products->first(fn ($v) => $v->id == $productData['id']);

            abort_unless(
                $product->validateDiscount($productData['discount']),
                ResponseStatus::BAD_REQUEST->value,
                'Discount cannot be greater than the sale price'
            );

            return $product->populate($productData);
        }, $data['products']);

        $visit->products()->attach(
            collect($data['products'])->mapWithKeys(fn ($v) => [$v['id'] => $v])->toArray()
        );

        return response()->json(['visit' => $visit]);
    }
}
