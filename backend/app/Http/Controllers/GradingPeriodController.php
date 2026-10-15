<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradingPeriodRequest;
use App\Models\GradeSubmission;
use App\Models\GradingPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradingPeriodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = GradingPeriod::query()->orderBy('school_year_id')->orderBy('quarter_number');
        if ($request->filled('school_year_id')) {
            $q->where('school_year_id', $request->integer('school_year_id'));
        }
        return response()->json($q->paginate(20));
    }

    public function store(GradingPeriodRequest $request): JsonResponse
    {
        return response()->json(GradingPeriod::create($request->validated()), 201);
    }

    public function show(GradingPeriod $gradingPeriod): JsonResponse
    {
        return response()->json($gradingPeriod);
    }

    public function update(GradingPeriodRequest $request, GradingPeriod $gradingPeriod): JsonResponse
    {
        $gradingPeriod->update($request->validated());
        return response()->json($gradingPeriod->refresh());
    }

    public function destroy(GradingPeriod $gradingPeriod): JsonResponse
    {
        if (GradeSubmission::where('grading_period_id', $gradingPeriod->id)->exists()) {
            return response()->json(['message' => 'Period has grade submissions and cannot be deleted.'], 409);
        }
        $gradingPeriod->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
