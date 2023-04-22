<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\FollowUpVisit;
use App\Models\VisitType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FollowUpController extends Controller
{
    public function store()
    {
        $data = request()->validate([
            'visit_type_id' => ['required', 'exists:visit_types,id'],
            'name' => ['required'],
            'due_in_days' => ['required', 'numeric']
        ]);

        FollowUp::create($data);

        return response()->json([
            'visit_type' => VisitType::with(['followUps'])->find($data['visit_type_id'])
        ]);
    }

    public function update(FollowUp $followUp)
    {
        $data = request()->validate([
            'name' => ['required'],
            'due_in_days' => ['required', 'numeric']
        ]);
        DB::transaction(function () use ($data, $followUp) {
            if ($data['due_in_days'] != $followUp->due_in_days) {
                $query = FollowUpVisit::where('follow_up_id', $followUp->id);
                $query->get()->each(function ($v) use ($data) {
                    $v->due_on = (new Carbon($v->count_from))->addDays($data['due_in_days']);
                    $v->save();
                });
            }
            $followUp->update($data);
        });


        return response()->json([
            'visit_type' => VisitType::with(['followUps'])->find($followUp->visit_type_id)
        ]);
    }

    public function destroy(FollowUp $followUp)
    {
        FollowUpVisit::where('follow_up_id', $followUp->id)->delete();
        $followUp->delete();

        return response()->json([
            'visit_type' => VisitType::with(['followUps'])->find($followUp->visit_type_id)
        ]);
    }
}
