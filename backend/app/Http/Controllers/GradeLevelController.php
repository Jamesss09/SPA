<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeLevelRequest;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class GradeLevelController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(GradeLevel::orderBy('level_number')->get());
    }

    public function store(GradeLevelRequest $request): JsonResponse
    {
        return response()->json(GradeLevel::create($request->validated()), 201);
    }

    public function show(GradeLevel $gradeLevel): JsonResponse
    {
        return response()->json($gradeLevel);
    }

    public function update(GradeLevelRequest $request, GradeLevel $gradeLevel): JsonResponse
    {
        $gradeLevel->update($request->validated());
        return response()->json($gradeLevel->refresh());
    }

    public function destroy(GradeLevel $gradeLevel): JsonResponse
    {
        if (Section::where('grade_level_id', $gradeLevel->id)->exists()
            || Subject::where('grade_level_id', $gradeLevel->id)->exists()) {
            return response()->json(['message' => 'Level is in use by sections or subjects and cannot be deleted.'], 409);
        }
        $gradeLevel->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
