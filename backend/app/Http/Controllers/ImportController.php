<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Services\SectionAccess;
use App\Services\StudentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    private function target(Request $request): Section|JsonResponse
    {
        $request->validate(['section_id' => ['required', 'integer', 'exists:sections,id']]);
        $section = Section::findOrFail($request->integer('section_id'));
        $user = $request->user();
        if (! in_array($user->role, ['admin', 'faculty'], true) || ! SectionAccess::canManage($user, $section)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        return $section;
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'mimes:csv,txt,xlsx,xls']]);
        $target = $this->target($request);
        if ($target instanceof JsonResponse) {
            return $target;
        }
        try {
            $rows = StudentImportService::readRows($request->file('file'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        if (! $rows) {
            return response()->json(['message' => 'No data rows found.'], 422);
        }
        return response()->json(StudentImportService::preview($rows, $target) + [
            'section_id' => $target->id,
            'note' => 'Preview only — nothing written. Commit re-validates everything.',
        ]);
    }

    public function commit(Request $request): JsonResponse
    {
        $request->validate(['rows' => ['required', 'array', 'max:500']]);
        $target = $this->target($request);
        if ($target instanceof JsonResponse) {
            return $target;
        }
        return response()->json(StudentImportService::commit(
            $request->input('rows'), $target, $request->user()->id));
    }
}
