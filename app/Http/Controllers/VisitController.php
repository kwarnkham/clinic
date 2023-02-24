<?php

namespace App\Http\Controllers;

use App\Enums\ItemType;
use App\Enums\ResponseStatus;
use App\Enums\VisitStatus;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'statuses' => [
                'sometimes',
                function (string $attribute, string $value, $fail) {
                    $statuses = explode(',', $value);
                    foreach ($statuses as $status) {
                        if (!in_array($status, VisitStatus::toArray())) {
                            $fail("The {$attribute} is invalid.");
                        }
                    }
                }
            ]
        ]);
        $query = Visit::query()->latest('id')->with(['patient'])->filter($filters);
        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'with_book_fees' => ['sometimes', 'boolean']
        ]);
        $visit = Visit::create(collect($data)->except('with_book_fees')->toArray());
        if (array_key_exists('with_book_fees', $data) && $data['with_book_fees']) {
            $visit->addBookFees();
        }
        return response()->json(['visit' => $visit]);
    }

    public function recordProduct(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'products' => ['required', 'array'],
            'products.*' => ['required', 'array'],
            'products.*.id' => ['required', 'exists:products,id', 'distinct'],
            'products.*.quantity' => ['required', 'numeric'],
            'products.*.discount' => ['nullable', 'numeric'],
            'discount' => ['sometimes', 'numeric', 'required']
        ]);

        $products = Product::query()
            ->with(['item'])
            ->whereIn('id', array_values(array_map(
                fn ($value) => $value['id'],
                $data['products']
            )))
            ->get();

        $data['products'] = array_map(function ($productData) use ($products) {
            $product = $products->first(fn ($v) => $v->id == $productData['id']);

            if (array_key_exists('discount', $productData))
                abort_unless(
                    $product->validateDiscount($productData['discount']),
                    ResponseStatus::BAD_REQUEST->value,
                    'Discount cannot be greater than the sale price'
                );

            abort_unless(
                $product->validateQuantity($productData['quantity']),
                ResponseStatus::BAD_REQUEST->value,
                'Quantity cannot be greater than the stock'
            );

            return $product->populate($productData);
        }, $data['products']);

        DB::transaction(function () use ($visit, $data, $products) {
            $visit->products()->sync(
                collect($data['products'])->mapWithKeys(fn ($v) => [$v['id'] => $v])->toArray()
            );
            foreach ($data['products'] as $productData) {
                $product = $products->first(fn ($v) => $v->id == $productData['id']);
                $product->reduceStock($productData['quantity']);
            }
            $visit->status = VisitStatus::PRODUCTS_ADDED->value;
            $visit->amount = array_reduce($data['products'], function ($carry, $productData) {
                return (
                    ($productData['sale_price'] - ($productData['discount'] ?? 0)) * $productData['quantity']) + $carry;
            }, 0);
            if (array_key_exists('discount', $data))
                $visit->discount = $data['discount'];
            $visit->save();
        });

        return response()->json(['visit' => $visit->load(['products'])]);
    }

    public function confirmProduct(Visit $visit): JsonResponse
    {
        $visit->status = VisitStatus::CONFIRMED->value;
        $visit->save();
        return response()->json(['visit' => $visit]);
    }

    public function completeVisit(Visit $visit): JsonResponse
    {
        $visit->status = VisitStatus::COMPLETED->value;
        $visit->save();
        return response()->json(['visit' => $visit]);
    }

    public function cancelVisit(Visit $visit): JsonResponse
    {
        $visit->status = VisitStatus::CANCELED->value;
        $visit->save();
        return response()->json(['visit' => $visit]);
    }

    public function show(Visit $visit)
    {
        return response()->json([
            'visit' => $visit->load(['products', 'patient'])
        ]);
    }
}
