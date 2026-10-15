<?php

namespace App\Http\Controllers;

use App\Models\FacultyProfile;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\SectionAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = TeachingAssignment::query()->with(['faculty.user', 'subject', 'section'])->orderByDesc('id');
        if ($user->role === 'faculty') {
            // Faculty see only their own assignments (backend-enforced scope).
            $q->where('faculty_profile_id', $user->facultyProfile?->id);
        } else {
            if ($request->filled('section_id')) {
                $q->where('section_id', $request->integer('section_id'));
            }
            if ($request->filled('school_year_id')) {
                $q->where('school_year_id', $request->integer('school_year_id'));
            }
        }
        return response()->json($q->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'faculty_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);
        $faculty = User::findOrFail($data['faculty_user_id']);
        if ($faculty->role !== 'faculty' || ! $faculty->isActive()) {
            return response()->json(['message' => 'Assignee must be an active faculty account.'], 422);
        }
        // school_year_id derives from the section — never mismatched by input.
        $section = Section::findOrFail($data['section_id']);
        $profile = FacultyProfile::firstOrCreate(
            ['user_id' => $faculty->id], ['employee_number' => 'EMP-' . $faculty->id]);
        $assignment = TeachingAssignment::firstOrCreate(
            ['faculty_profile_id' => $profile->id, 'subject_id' => $data['subject_id'],
             'section_id' => $section->id, 'school_year_id' => $section->school_year_id],
            ['status' => $data['status'] ?? 'active']);
        return response()->json($assignment->load(['faculty.user', 'subject', 'section']), $assignment->wasRecentlyCreated ? 201 : 200);
    }

    public function show(TeachingAssignment $teachingAssignment): JsonResponse
    {
        return response()->json($teachingAssignment->load(['faculty.user', 'subject', 'section']));
    }

    public function update(Request $request, TeachingAssignment $teachingAssignment): JsonResponse
    {
        $data = $request->validate([
            'subject_id' => ['sometimes', 'integer', 'exists:subjects,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);
        $teachingAssignment->update($data);
        return response()->json($teachingAssignment->refresh());
    }

    public function destroy(TeachingAssignment $teachingAssignment): JsonResponse
    {
        if ($teachingAssignment->schedules()->exists() || $teachingAssignment->gradeSubmissions()->exists()) {
            return response()->json(['message' => 'Assignment has schedules or grades and cannot be deleted.'], 409);
        }
        $teachingAssignment->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // Students enrolled in this assignment's section. Faculty: own only.
    public function students(Request $request, TeachingAssignment $teachingAssignment): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'admin'
            && $teachingAssignment->faculty_profile_id !== $user->facultyProfile?->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $section = $teachingAssignment->section;
        if (! SectionAccess::canManage($user, $section)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $rows = $section->enrollments()->with('student.user')->get()->map(fn ($e) => [
            'enrollment_id' => $e->id,
            'name' => $e->student->user->name,
            'username' => $e->student->user->username,
            'student_number' => $e->student->student_number,
            'status' => $e->status,
        ]);
        return response()->json($rows);
    }
}
