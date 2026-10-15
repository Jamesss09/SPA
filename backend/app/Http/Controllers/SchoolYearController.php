<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolYearRequest;
use App\Models\SchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SchoolYearController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(SchoolYear::orderByDesc('id')->paginate(20));
    }

    public function store(SchoolYearRequest $request): JsonResponse
    {
        $year = DB::transaction(function () use ($request) {
            $year = SchoolYear::create($request->validated());
            if (($request->validated()['status'] ?? 'active') === 'active') {
                $this->deactivateOthers($year->id);
            }
            return $year;
        });
        return response()->json($year, 201);
    }

    public function show(SchoolYear $schoolYear): JsonResponse
    {
        return response()->json($schoolYear->loadCount('sections', 'gradingPeriods'));
    }

    public function update(SchoolYearRequest $request, SchoolYear $schoolYear): JsonResponse
    {
        $schoolYear = DB::transaction(function () use ($request, $schoolYear) {
            $schoolYear->update($request->validated());
            if (($request->validated()['status'] ?? $schoolYear->status) === 'active') {
                $this->deactivateOthers($schoolYear->id);
            }
            return $schoolYear->refresh();
        });
        return response()->json($schoolYear);
    }

    public function destroy(SchoolYear $schoolYear): JsonResponse
    {
        if ($schoolYear->sections()->exists() || $schoolYear->gradingPeriods()->exists()) {
            return response()->json(['message' => 'Year has sections or grading periods and cannot be deleted.'], 409);
        }
        $schoolYear->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // Decision: single active year, enforced in code (plan left this open).
    private function deactivateOthers(int $keepId): void
    {
        SchoolYear::where('id', '!=', $keepId)->where('status', 'active')->update(['status' => 'inactive']);
    }
}
