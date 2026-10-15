<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\FacultyProfile;
use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Phase 4 acceptance: import preview, duplicates, correct account/enrollment.
class EnrollmentImportTest extends TestCase
{
    use RefreshDatabase;

    private array $fx = [];

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create(['username' => 'admin.i', 'role' => 'admin', 'status' => 'active']);
        $facUser = User::factory()->create(['username' => 'fac.i', 'role' => 'faculty', 'status' => 'active']);
        $profile = FacultyProfile::create(['user_id' => $facUser->id, 'employee_number' => 'EMP-1']);
        $year = SchoolYear::create(['name' => 'A.Y. 2026–2027', 'status' => 'active']);
        $level = GradeLevel::create(['name' => 'Grade 7', 'level_number' => 7]);
        $room = Room::create(['room_name' => 'Room 101']);
        $section = Section::create(['school_year_id' => $year->id, 'grade_level_id' => $level->id, 'name' => 'St. Anne', 'room_id' => $room->id]);
        $other = Section::create(['school_year_id' => $year->id, 'grade_level_id' => $level->id, 'name' => 'St. Jude']);
        $subject = Subject::create(['subject_code' => 'MATH7', 'subject_name' => 'Mathematics 7']);
        \App\Models\TeachingAssignment::create(['faculty_profile_id' => $profile->id,
            'subject_id' => $subject->id, 'section_id' => $section->id, 'school_year_id' => $year->id]);
        $this->fx = compact('admin', 'facUser', 'profile', 'year', 'level', 'room', 'section', 'other', 'subject');
    }

    private function csv(array $rows): UploadedFile
    {
        $csv = "last_name,first_name,birth_date,student_number\n" . implode("\n", $rows);
        return UploadedFile::fake()->createWithContent('list.csv', $csv);
    }

    private function preview(array $rows, ?User $as = null, ?int $sectionId = null)
    {
        $as ??= $this->fx['admin'];
        return $this->actingAs($as)->postJson('/api/enrollments/import/preview', [
            'section_id' => $sectionId ?? $this->fx['section']->id,
            'file' => $this->csv($rows),
        ]);
    }

    public function test_preview_classifies_rows(): void
    {
        // Seed one existing student enrolled elsewhere (conflict case).
        $existing = User::factory()->create(['username' => 'old.one', 'role' => 'student', 'status' => 'active']);
        $prof = StudentProfile::create(['user_id' => $existing->id, 'student_number' => '2026-0099']);
        Enrollment::create(['student_profile_id' => $prof->id,
            'school_year_id' => $this->fx['year']->id, 'section_id' => $this->fx['other']->id]);

        // And one existing student unenrolled (matched case).
        $free = User::factory()->create(['username' => 'free.one', 'role' => 'student', 'status' => 'active']);
        StudentProfile::create(['user_id' => $free->id, 'student_number' => '2026-0100']);

        $res = $this->preview([
            'Enquig,John,2014-05-01,',          // new, generated ID
            'Reyes,Ana,,2026-0200',              // new with provided ID
            'Free,One,,2026-0100',              // matched
            'Free,One,,2026-0100',              // duplicate in file
            ',NoLast,,',                        // error: missing name
            'Old,One,,2026-0099',               // error: enrolled elsewhere
        ])->assertOk();

        $byRow = collect($res['rows'])->keyBy('row');
        $this->assertSame('ok', $byRow[1]['status']);
        $this->assertSame('ok', $byRow[2]['status']);
        $this->assertSame('matched', $byRow[3]['status']);
        $this->assertSame('duplicate', $byRow[4]['status']);
        $this->assertSame('error', $byRow[5]['status']);
        $this->assertSame('error', $byRow[6]['status']);
        $this->assertSame(2, $res['created']);
        $this->assertSame('Enquig.john', $byRow[1]['username']);
    }

    public function test_commit_creates_accounts_and_enrollments(): void
    {
        $preview = $this->preview(['Enquig,John,2014-05-01,', 'Dela Cruz,Jane,,'])->assertOk();
        $commit = $this->actingAs($this->fx['admin'])->postJson('/api/enrollments/import/commit', [
            'section_id' => $this->fx['section']->id,
            'rows' => $preview['rows'],
        ])->assertOk();

        $this->assertSame(2, $commit['created']);
        $john = User::where('username', 'Enquig.john')->firstOrFail();
        $this->assertTrue($john->must_change_password);
        $this->assertTrue(Hash::check($john->studentProfile->student_number, $john->password));
        $this->assertSame($this->fx['section']->id, $john->studentProfile->enrollments()->first()->section_id);
        $this->assertSame($this->fx['year']->id, $john->studentProfile->enrollments()->first()->school_year_id);
        $this->assertNotNull($commit['import_batch_id']);
    }

    public function test_username_collision_gets_deterministic_suffix(): void
    {
        $u = User::factory()->create(['username' => 'Enquig.john', 'role' => 'student', 'status' => 'active']);
        StudentProfile::create(['user_id' => $u->id, 'student_number' => '2026-0001']);

        $preview = $this->preview(['Enquig,John,,'])->assertOk();
        $this->assertSame('Enquig.john2', $preview['rows'][0]['username']);
    }

    public function test_faculty_scoped_to_assigned_sections(): void
    {
        // Guest first: actingAs() persists on the app for the rest of the test.
        $this->postJson('/api/enrollments/import/preview', [
            'section_id' => $this->fx['section']->id, 'file' => $this->csv(['New,Kid,,']),
        ])->assertUnauthorized();

        $fac = $this->fx['facUser'];
        // Own section OK.
        $this->preview(['New,Kid,,'], $fac)->assertOk();
        // Other section forbidden.
        $this->preview(['New,Kid,,'], $fac, $this->fx['other']->id)->assertForbidden();
        // Students forbidden everywhere.
        $stu = User::factory()->create(['username' => 's.i', 'role' => 'student', 'status' => 'active']);
        $this->preview(['New,Kid,,'], $stu)->assertForbidden();
    }

    public function test_teaching_assignments_admin_crud_and_faculty_scope(): void
    {
        $a = $this->fx['admin'];
        $newFac = User::factory()->create(['username' => 'new.fac', 'role' => 'faculty', 'status' => 'active']);

        // Admin creates assignment by faculty user id; profile auto-created.
        $created = $this->actingAs($a)->postJson('/api/teaching-assignments', [
            'faculty_user_id' => $newFac->id, 'subject_id' => $this->fx['subject']->id,
            'section_id' => $this->fx['other']->id,
        ])->assertCreated();
        $this->assertNotNull(FacultyProfile::where('user_id', $newFac->id)->first());

        // Faculty sees only own assignments.
        $mine = $this->actingAs($this->fx['facUser'])->getJson('/api/teaching-assignments')->assertOk();
        $this->assertSame(1, $mine['total']);

        // Non-faculty assignee rejected.
        $stu = User::factory()->create(['username' => 's2.i', 'role' => 'student', 'status' => 'active']);
        $this->actingAs($a)->postJson('/api/teaching-assignments', [
            'faculty_user_id' => $stu->id, 'subject_id' => $this->fx['subject']->id,
            'section_id' => $this->fx['other']->id,
        ])->assertStatus(422);

        // Class lists: own vs foreign.
        $this->actingAs($this->fx['facUser'])
            ->getJson('/api/teaching-assignments/' . $this->fx['section']->teachingAssignments()->first()->id . '/students')
            ->assertOk();
        $this->actingAs($this->fx['facUser'])
            ->getJson('/api/teaching-assignments/' . $created['id'] . '/students')
            ->assertForbidden();
        $this->actingAs($this->fx['facUser'])
            ->getJson('/api/sections/' . $this->fx['other']->id . '/students')
            ->assertForbidden();
    }
}
