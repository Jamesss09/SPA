<?php

namespace App\Services;

use App\Models\Section;
use App\Models\User;

// Phase 4 record-level access: faculty may manage only their assigned
// section(s) — sections they teach (via teaching assignments) or advise.
// Admins may manage all sections. This is the ownership-check pattern for
// later phases (schedules, grades).
class SectionAccess
{
    public static function canManage(User $user, Section $section): bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->role !== 'faculty' || ! $user->facultyProfile) {
            return false;
        }
        if ((int) $section->adviser_faculty_id === $user->id) {
            return true;
        }
        return $section->teachingAssignments()
            ->where('faculty_profile_id', $user->facultyProfile->id)
            ->where('status', 'active')
            ->exists();
    }
}
