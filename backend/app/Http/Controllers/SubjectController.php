<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Subject::orderBy('subject_code')->paginate(20));
    }

    public function store(SubjectRequest $request): JsonResponse
    {
        return response()->json(Subject::create($request->validated()), 201);
    }

    public function show(Subject $subject): JsonResponse
    {
        return response()->json($subject);
    }

    public function update(SubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject->update($request->validated());
        return response()->json($subject->refresh());
    }

    public function destroy(Subject $subject): JsonResponse
    {
        if (TeachingAssignment::where('subject_id', $subject->id)->exists()) {
            return response()->json(['message' => 'Subject has teaching assignments and cannot be deleted.'], 409);
        }
        $subject->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
