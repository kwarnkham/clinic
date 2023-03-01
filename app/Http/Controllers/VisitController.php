<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\VisitStatus;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                },
            ],
        ]);
        $query = Visit::query()->latest('id')->with(['patient'])->filter($filters);

        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'with_book_fees' => ['sometimes', 'boolean'],
        ]);
        $visit = Visit::create(collect($data)->except('with_book_fees')->toArray());
        if (array_key_exists('with_book_fees', $data) && $data['with_book_fees']) {
            $visit->addBookFees();
        }

        return response()->json(['visit' => $visit]);
    }

    public function recordProduct(Request $request, Visit $visit): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->roles->contains(fn ($role) => in_array($role->name, ['admin', 'cashier', 'pharmacist'])),
            ResponseStatus::BAD_REQUEST->value,
            'Action is not authorized'
        );
        abort_if(
            in_array($visit->status, [VisitStatus::CANCELED->value, VisitStatus::COMPLETED->value]),
            ResponseStatus::BAD_REQUEST->value,
            'Visit is completed or canceled'
        );
        abort_if(
            $request->status == VisitStatus::COMPLETED && $visit->status != VisitStatus::CONFIRMED->value,
            ResponseStatus::BAD_REQUEST->value,
            'Can only complete a confirmed visit'
        );
        abort_if(
            $request->status == VisitStatus::CONFIRMED && $visit->status != VisitStatus::PRODUCTS_ADDED->value,
            ResponseStatus::BAD_REQUEST->value,
            'Can only confirm if cashier added products'
        );
        abort_if(
            $request->status == VisitStatus::CONFIRMED->value && !$user->hasRole('pharmacist') && !$user->hasRole('admin'),
            ResponseStatus::BAD_REQUEST->value,
            'Action is not authorized'
        );
        abort_if(
            in_array($request->status, [
                VisitStatus::PRODUCTS_ADDED->value,
                VisitStatus::COMPLETED->value,
                VisitStatus::CANCELED->value,
            ]) && !$user->hasRole('cashier') && !$user->hasRole('admin'),
            ResponseStatus::BAD_REQUEST->value,
            'Action is not authorized'
        );
        $data = $request->validate([
            'status' => ['required', 'numeric', 'in:' . VisitStatus::toString()],
            'products' => ['array', Rule::requiredIf($request->status != 5)],
            'products.*' => [Rule::requiredIf($request->status != 5), 'array'],
            'products.*.id' => [Rule::requiredIf($request->status != 5), 'exists:products,id', 'distinct'],
            'products.*.quantity' => [Rule::requiredIf($request->status != 5), 'numeric'],
            'products.*.discount' => ['nullable', 'numeric'],
            'discount' => ['sometimes', 'numeric', 'required'],
        ]);

        $products = Product::query()
            ->with(['item'])
            ->whereIn('id', array_values(array_map(
                fn ($value) => $value['id'],
                $data['products']
            )))
            ->get();

        $data['products'] = array_map(function ($productData) use ($products, $visit) {
            $product = $products->first(fn ($v) => $v->id == $productData['id']);

            if (array_key_exists('discount', $productData)) {
                abort_unless(
                    $product->validateDiscount($productData['discount']),
                    ResponseStatus::BAD_REQUEST->value,
                    'Discount cannot be greater than the sale price'
                );
            }

            $existedProduct = $visit->products->first(fn ($val) => $val->id == $productData['id']);
            $existedQuantity = $existedProduct ? $existedProduct->pivot->quantity : 0;
            abort_unless(
                $product->validateQuantity($productData['quantity'] - $existedQuantity),
                ResponseStatus::BAD_REQUEST->value,
                'Quantity cannot be greater than the stock'
            );

            return $product->populate($productData);
        }, $data['products']);

        DB::transaction(function () use ($visit, $data) {
            Product::reverseStock($visit->products);
            $visit->products()->sync(
                collect($data['products'])->mapWithKeys(fn ($v) => [$v['id'] => [
                    'name' => $v['name'],
                    'description' => $v['description'],
                    'sale_price' => $v['sale_price'],
                    'last_purchase_price' => $v['last_purchase_price'],
                    'quantity' => $v['quantity'],
                    'discount' => $v['discount'] ?? 0,
                ]])->toArray()
            );

            $visit->load(['products.item']);

            foreach ($data['products'] as $productData) {
                $product = $visit->products->first(fn ($v) => $v->id == $productData['id']);
                $product->reduceStock($productData['quantity']);
            }

            $visit->status = $data['status'];
            $visit->amount = array_reduce($data['products'], function ($carry, $productData) {
                return (
                    ($productData['sale_price'] - ($productData['discount'] ?? 0)) * $productData['quantity']) + $carry;
            }, 0);
            if (array_key_exists('discount', $data)) {
                $visit->discount = $data['discount'];
            }
            $visit->save();
        });

        return response()->json(['visit' => $visit->load(['products', 'patient'])]);
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
            'visit' => $visit->load(['products', 'patient']),
        ]);
    }
}
