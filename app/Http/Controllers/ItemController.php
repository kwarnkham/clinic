<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'name' => ['required'],
            'description' => ['nullable']
        ]);
        $item = Item::create($data);
        return response()->json(['item' => $item], ResponseStatus::CREATED->value);
    }

    /**
     * Display the specified resource.
     */
    // public function show(Item $item): Response
    // {
    //     //
    // }


    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, Item $item): RedirectResponse
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Item $item): RedirectResponse
    // {
    //     //
    // }
}
