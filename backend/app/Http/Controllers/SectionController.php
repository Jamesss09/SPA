<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\TeachingAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Section::query()->with(['gradeLevel', 'room'])->orderByDesc('id');
        foreach (['school_year_id', 'grade_level_id'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->integer($f));
            }
        }
        return response()->json($q->paginate(20));
    }

    public function store(SectionRequest $request): JsonResponse
    {
        return response()->json(Section::create($request->validated()), 201);
    }

    public function show(Section $section): JsonResponse
    {
        return response()->json($section->load(['gradeLevel', 'room', 'schoolYear']));
    }

    public function update(SectionRequest $request, Section $section): JsonResponse
    {
        $section->update($request->validated());
        return response()->json($section->refresh());
    }

    public function destroy(Section $section): JsonResponse
    {
        if (Enrollment::where('section_id', $section->id)->exists()
            || TeachingAssignment::where('section_id', $section->id)->exists()) {
            return response()->json(['message' => 'Section has enrollments or teaching assignments and cannot be deleted.'], 409);
        }
        $section->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
