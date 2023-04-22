<?php

namespace App\Http\Controllers;

use App\Models\VisitType;
use Illuminate\Validation\Rule;

class VisitTypeController extends Controller
{
    public function store()
    {
        $data = request()->validate([
            'name' => ['required', 'unique:visit_types,name']
        ]);

        $visitType = VisitType::create($data);

        return response()->json(['visit_type' => $visitType]);
    }

    public function show(VisitType $visitType)
    {
        return response()->json(['visit_type' => $visitType->load(['followUps'])]);
    }

    public function update(VisitType $visitType)
    {
        $data = request()->validate([
            'name' => [
                'required',
                Rule::unique('visit_types', 'name')->ignoreModel($visitType)
            ]
        ]);
        $visitType->update($data);
        return response()->json(['visit_type' => $visitType]);
    }

    public function index()
    {
        $filters = request()->validate([
            'hasFollowUps' => ['boolean']
        ]);
        $query = VisitType::query()->filter($filters);
        return response()->json(['visit_types' => $query->get()]);
    }
}
