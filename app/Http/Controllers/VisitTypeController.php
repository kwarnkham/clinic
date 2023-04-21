<?php

namespace App\Http\Controllers;

use App\Models\VisitType;
use Illuminate\Http\Request;

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

    public function index()
    {
        return response()->json(['visit_types' => VisitType::all()]);
    }
}
