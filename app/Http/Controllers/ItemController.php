<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $filters = request()->validate([
            'search' => ['sometimes', 'required']
        ]);
        $query = Item::query()->filter($filters);
        return response()->json(['data' => $query->paginate(request()->per_page ?? 20)]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'unique:items,name'],
            'description' => ['nullable']
        ]);
        $item = Item::create($data);
        return response()->json(['item' => $item], ResponseStatus::CREATED->value);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): JsonResponse
    {
        return response()->json(['item' => $item]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', Rule::unique('items', 'name')->ignore($item->id)],
            'description' => ['nullable']
        ]);
        $item->update($data);
        return response()->json(['item' => $item]);
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Item $item): RedirectResponse
    // {
    //     //
    // }
}
