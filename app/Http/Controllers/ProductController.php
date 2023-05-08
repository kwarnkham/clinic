<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\ResponseStatus;
use App\Models\Item;
use App\Models\Product;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'name' => ['required', 'unique:products,name'],
            'description' => ['nullable'],
            'sale_price' => ['required', 'numeric'],
        ]);
        $product = Product::create($data);

        return response()->json([
            'product' => $product->fresh(),
        ], ResponseStatus::CREATED->value);
    }

    public function index(): JsonResponse
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required'],
            'item_id' => ['sometimes', 'exists:items,id'],
            'max_stock' => ['sometimes', 'numeric', 'required'],
        ]);
        $query = Product::query()->latest('id')->filter($filters);
        if (
            !array_key_exists('item_id', $filters) ||
            (array_key_exists('item_id', $filters) && in_array($filters['item_id'], Item::query()->where('type', ItemType::STOCKED->value)->pluck('id')->toArray()))
        ) {
            $query->whereRelation('item', 'type', ItemType::STOCKED->value);
        }

        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }

    public function search(): JsonResponse
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required'],
            'limit' => ['sometimes', 'required', 'numeric'],
        ]);
        $query = Product::query()->filter($filters);

        return response()->json(['products' => $query->get()]);
    }

    public function purchase(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'expired_on' => ['nullable', 'date'],
        ]);
        $purchase = DB::transaction(function () use ($product, $data) {
            $purchase = $product->purchases()->create(
                [
                    ...$data,
                    'stock' => $data['quantity'],
                ]
            );
            $product->stock += $data['quantity'];
            $product->save();

            return $purchase;
        });

        $product->last_purchase_price = $data['price'];
        $product->save();

        return response()->json([
            'purchase' => $purchase->load(['purchasable']),
        ], ResponseStatus::CREATED->value);
    }

    public function show(Product $product)
    {
        return response()->json(['product' => $product]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($request->validate([
            'name' => ['required', Rule::unique('products', 'name')->ignore($product->id)],
            'description' => ['nullable'],
            'sale_price' => ['required', 'numeric'],
        ]));

        return response()->json([
            'product' => $product,
        ]);
    }

    public function report()
    {
        $data = DB::table('products')
            ->select(['id', 'name', 'stock', 'last_purchase_price', 'sale_price'])
            ->where('item_id', '!=', 1)
            ->get();
        return response()->json(['data' => $data]);
    }
}
