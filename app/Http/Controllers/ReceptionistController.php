<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Enums\VisitStatus;
use App\Models\Patient;
use Illuminate\Http\Request;

class ReceptionistController extends Controller
{
    public function registerPatient(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'age' => ['required'],
            'gender' => ['required', 'boolean'],
            'phone' => ['sometimes', 'required'],
            'address' => ['sometimes', 'required']
        ]);
        $data['code'] = Patient::generateCode();
        $patient = Patient::create($data);
        $patient->visits()->create([
            'status' => VisitStatus::PENDING->value,
            'amount' => 0
        ]);
        return response()->json(['patient' => $patient], ResponseStatus::CREATED->value);
    }
}
