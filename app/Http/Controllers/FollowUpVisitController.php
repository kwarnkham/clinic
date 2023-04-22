<?php

namespace App\Http\Controllers;

use App\Models\FollowUpVisit;

class FollowUpVisitController extends Controller
{
    public function index()
    {
        $query =  FollowUpVisit::query()->with(['followUp', 'visit.patient'])->where('status', 1)->orderBy('due_on');
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }

    public function finish(FollowUpVisit $followUpVisit)
    {
        $followUpVisit->update(['status' => 2]);

        return response()->json([
            'follow_up_visit' => $followUpVisit
        ]);
    }
}
