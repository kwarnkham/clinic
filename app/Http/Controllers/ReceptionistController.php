<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
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
        return response()->json(['patient' => $patient], ResponseStatus::CREATED->value);
    }
}
