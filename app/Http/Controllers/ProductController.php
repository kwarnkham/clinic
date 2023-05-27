<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Enums\VisitStatus;
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
        $filters = request()->validate([
            'date' => ['required', 'date']
        ]);
        $data = DB::table('product_stock')
            ->join('products', 'products.id', '=', 'product_stock.product_id')
            ->whereDate('product_stock.updated_at', '=', $filters['date'])
            ->select([
                'product_stock.product_id',
                'product_stock.stock',
                'products.name',
                'products.sale_price',
                'products.last_purchase_price',
                'products.description',
                'product_stock.updated_at as date'
            ])
            ->get();
        return response()->json(['data' => $data]);
    }

    // public function report()
    // {
    //     $filters = request()->validate([
    //         'from' => ['sometimes', 'required', 'date'],
    //         'to' => ['sometimes', 'required', 'date']
    //     ]);
    //     $data = DB::table('products')
    //         ->select(['id', 'name', 'last_purchase_price', 'sale_price', 'stock'])
    //         ->get();

    //     $data->transform(function ($value) use ($filters) {
    //         $purchases = DB::table('purchases')
    //             ->where([
    //                 ['status', '=', PurchaseStatus::NORMAL->value],
    //                 ['purchasable_id', '=', $value->id],
    //                 ['purchasable_type', '=', 'App\\Models\\Product'],
    //             ])->whereDate('updated_at', '>=', $filters['from'])
    //             ->whereDate('updated_at', '<=', $filters['to'])
    //             ->select([
    //                 DB::raw('SUM(price*quantity) as total_purchase_amount'),
    //                 DB::raw('SUM(quantity) as total_purchase_quantity')
    //             ])->first();

    //         $value->total_purchase_amount = $purchases->total_purchase_amount;
    //         $value->total_purchase_quantity = $purchases->total_purchase_quantity;

    //         $sales = DB::table('product_visit')
    //             ->join('visits', 'visits.id', '=', 'product_visit.visit_id')
    //             ->where([
    //                 ['visits.status', '=', VisitStatus::COMPLETED->value],
    //                 ['product_visit.product_id', '=', $value->id],
    //             ])->whereDate('product_visit.updated_at', '>=', $filters['from'])
    //             ->whereDate('product_visit.updated_at', '<=', $filters['to'])
    //             ->select([
    //                 DB::raw('SUM((product_visit.sale_price-product_visit.discount)*product_visit.quantity) as total_sale_amount'),
    //                 DB::raw('SUM(product_visit.quantity) as total_sale_quantity'),
    //             ])->first();

    //         $value->total_sale_amount = $sales->total_sale_amount;
    //         $value->total_sale_quantity = $sales->total_sale_quantity;
    //         $value->from = $filters['from'];
    //         $value->to = $filters['to'];
    //         return $value;
    //     });

    //     return response()->json(['data' => $data]);
    // }
}
