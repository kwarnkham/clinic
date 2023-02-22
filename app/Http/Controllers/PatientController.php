<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\VisitStatus;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'age' => ['required'],
            'gender' => ['required', 'boolean'],
            'phone' => ['nullable'],
            'address' => ['nullable'],
            'with_book_fees' => ['boolean', 'nullable']
        ]);

        $data['code'] = Patient::generateCode();
        $patient = Patient::create(collect($data)->except('with_book_fees')->toArray());
        $visit = $patient->visits()->create([
            'status' => VisitStatus::PENDING->value,
            'amount' => 0
        ]);
        if (array_key_exists('with_book_fees', $data) && $data['with_book_fees']) $visit->addBookFees();

        return response()->json(['patient' => $patient], ResponseStatus::CREATED->value);
    }

    public function index()
    {
        return response()->json([
            'data' => Patient::query()->latest('id')->paginate()
        ]);
    }
}
