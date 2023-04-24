<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\FollowUpVisit;
use App\Models\Visit;
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
        DB::transaction(function () use ($data) {
            $followUp = FollowUp::create($data);

            $visits = Visit::query()
                ->whereRelation('visitTypes', 'visit_type_id', $data['visit_type_id'])
                ->with(['followUps'])->get();

            $visits->each(function ($visit) use ($followUp) {
                $visit->followUps()->attach([
                    $followUp->id => [
                        'due_on' => (new Carbon($visit->followUps->first()->pivot->count_from))->addDays($followUp->due_in_days),
                        'count_from' => $visit->followUps->first()->pivot->count_from
                    ]
                ]);
            });
        });


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
        if (
            FollowUp::where('visit_type_id', $followUp->visit_type_id)
            ->where('id', '!=', $followUp->id)
            ->count() == 0
        ) {
            VisitType::find($followUp->visit_type_id)->visits()->detach();
        }
        $followUp->delete();

        return response()->json([
            'visit_type' => VisitType::with(['followUps'])->find($followUp->visit_type_id)
        ]);
    }
}
