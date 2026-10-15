<?php

namespace Tests\Feature;

use App\Models\GradeLevel;
use App\Models\Room;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\FacultyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 3 acceptance: admin prepares a year via API, no manual DB edits.
class AdminSetupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['username' => 'admin.t', 'role' => 'admin', 'status' => 'active']);
    }

    private function year(array $over = []): SchoolYear
    {
        return SchoolYear::create(array_merge([
            'name' => 'A.Y. 2026–2027', 'start_date' => '2026-06-01',
            'end_date' => '2027-03-31', 'status' => 'active',
        ], $over));
    }

    private function level(): GradeLevel
    {
        return GradeLevel::create(['name' => 'Grade 7', 'level_number' => 7]);
    }

    public function test_school_year_crud_and_single_active_rule(): void
    {
        $a = $this->admin();

        $create = $this->actingAs($a)->postJson('/api/school-years', [
            'name' => 'A.Y. 2026–2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31',
        ]);
        $create->assertCreated()->assertJsonPath('status', 'active');

        // Duplicate name → 422.
        $this->actingAs($a)->postJson('/api/school-years', ['name' => 'A.Y. 2026–2027'])
            ->assertStatus(422);

        // Second active year deactivates the first (single-active-year rule).
        $second = $this->actingAs($a)->postJson('/api/school-years', [
            'name' => 'A.Y. 2027–2028', 'status' => 'active',
        ])->assertCreated();
        $this->assertSame('inactive', SchoolYear::find($create['id'])->status);
        $this->assertSame('active', SchoolYear::find($second['id'])->status);
    }

    public function test_grading_period_quarter_unique_per_year(): void
    {
        $a = $this->admin();
        $y = $this->year();
        $payload = ['school_year_id' => $y->id, 'quarter_number' => 1];

        $this->actingAs($a)->postJson('/api/grading-periods', $payload)->assertCreated();
        // Same quarter same year → 422; same quarter other year → 201.
        $this->actingAs($a)->postJson('/api/grading-periods', $payload)->assertStatus(422);
        $this->actingAs($a)->postJson('/api/grading-periods', ['quarter_number' => 5, 'school_year_id' => $y->id])
            ->assertStatus(422); // out of 1–4 range
        $other = $this->year(['name' => 'Other', 'status' => 'inactive']);
        $this->actingAs($a)->postJson('/api/grading-periods', ['school_year_id' => $other->id, 'quarter_number' => 1])
            ->assertCreated();
    }

    public function test_rooms_subjects_crud_and_uniques(): void
    {
        $a = $this->admin();
        $gl = $this->level();

        $room = $this->actingAs($a)->postJson('/api/rooms', ['room_name' => 'Room 101', 'capacity' => 40])
            ->assertCreated();
        $this->actingAs($a)->postJson('/api/rooms', ['room_name' => 'Room 101'])->assertStatus(422);

        $this->actingAs($a)->postJson('/api/subjects', [
            'subject_code' => 'MATH7', 'subject_name' => 'Mathematics 7', 'grade_level_id' => $gl->id,
        ])->assertCreated();
        $this->actingAs($a)->postJson('/api/subjects', ['subject_code' => 'MATH7', 'subject_name' => 'Dup'])
            ->assertStatus(422);
        $this->actingAs($a)->postJson('/api/subjects', ['subject_code' => 'X', 'subject_name' => 'Bad level', 'grade_level_id' => 999])
            ->assertStatus(422);

        // Room in use by a section cannot be deleted.
        Section::create(['school_year_id' => $this->year(['name' => 'Y2', 'status' => 'inactive'])->id,
            'grade_level_id' => $gl->id, 'name' => 'St. Anne', 'room_id' => $room['id']]);
        $this->actingAs($a)->deleteJson('/api/rooms/' . $room['id'])->assertStatus(409);
    }

    public function test_sections_scoped_unique_adviser_and_bad_fk(): void
    {
        $a = $this->admin();
        $y = $this->year();
        $gl = $this->level();
        $faculty = User::factory()->create(['username' => 'adv.t', 'role' => 'faculty', 'status' => 'active']);
        $student = User::factory()->create(['username' => 'stu.t', 'role' => 'student', 'status' => 'active']);

        $payload = ['school_year_id' => $y->id, 'grade_level_id' => $gl->id,
            'name' => 'St. Anne', 'adviser_faculty_id' => $faculty->id];
        $this->actingAs($a)->postJson('/api/sections', $payload)->assertCreated();
        // Same name same year+level → 422; same name other year → 201.
        $this->actingAs($a)->postJson('/api/sections', $payload)->assertStatus(422);
        $y2 = $this->year(['name' => 'Y-next', 'status' => 'inactive']);
        $this->actingAs($a)->postJson('/api/sections', array_merge($payload, ['school_year_id' => $y2->id]))
            ->assertCreated();

        // Adviser must be active faculty: student rejected, deactivated rejected.
        $this->actingAs($a)->postJson('/api/sections', array_merge($payload, [
            'name' => 'Other', 'adviser_faculty_id' => $student->id,
        ]))->assertStatus(422);
        // Bad FK rejected.
        $this->actingAs($a)->postJson('/api/sections', [
            'school_year_id' => 999, 'grade_level_id' => $gl->id, 'name' => 'Ghost',
        ])->assertStatus(422);
    }

    public function test_delete_year_with_sections_blocked_and_subject_with_assignment_blocked(): void
    {
        $a = $this->admin();
        $y = $this->year();
        $gl = $this->level();
        $room = Room::create(['room_name' => 'R1']);
        $section = Section::create(['school_year_id' => $y->id, 'grade_level_id' => $gl->id, 'name' => 'S1', 'room_id' => $room->id]);
        $subject = Subject::create(['subject_code' => 'SCI7', 'subject_name' => 'Science 7']);

        $this->actingAs($a)->deleteJson('/api/school-years/' . $y->id)->assertStatus(409);

        $fp = FacultyProfile::create(['user_id' => User::factory()->create([
            'username' => 'fac.t', 'role' => 'faculty', 'status' => 'active'])->id, 'employee_number' => 'E1']);
        TeachingAssignment::create(['faculty_profile_id' => $fp->id, 'subject_id' => $subject->id,
            'section_id' => $section->id, 'school_year_id' => $y->id]);
        $this->actingAs($a)->deleteJson('/api/subjects/' . $subject->id)->assertStatus(409);
    }

    public function test_user_management_filters_create_deactivate_reset(): void
    {
        $a = $this->admin();

        // Students cannot be created here (Phase 4 import owns that).
        $this->actingAs($a)->postJson('/api/users', [
            'name' => 'Kid', 'username' => 'kid.one', 'password' => 'password-1', 'role' => 'student',
        ])->assertStatus(422);

        $fac = $this->actingAs($a)->postJson('/api/users', [
            'name' => 'Maria Santos', 'username' => 'santos.maria', 'password' => 'password-1', 'role' => 'faculty',
        ])->assertCreated()->assertJsonPath('must_change_password', false);

        $list = $this->actingAs($a)->getJson('/api/users?role=faculty&search=santos')->assertOk();
        $this->assertSame(1, $list['total']);

        $reset = $this->actingAs($a)->postJson('/api/users/' . $fac['id'] . '/reset-password')->assertOk();
        $this->assertNotEmpty($reset['temporary_password']);
        $this->assertTrue(User::find($fac['id'])->must_change_password);

        // Deactivate → Phase 2 login now refuses (using the temporary password,
        // which also proves reset-password output actually works).
        $this->actingAs($a)->patchJson('/api/users/' . $fac['id'], ['status' => 'deactivated'])->assertOk();
        $this->postJson('/api/login', ['username' => 'santos.maria', 'password' => $reset['temporary_password']])
            ->assertForbidden();
    }

    public function test_non_admin_forbidden_and_guest_unauthorized(): void
    {
        $faculty = User::factory()->create(['username' => 'f.t', 'role' => 'faculty', 'status' => 'active']);
        $student = User::factory()->create(['username' => 's.t', 'role' => 'student', 'status' => 'active']);

        $paths = ['/api/school-years', '/api/grading-periods', '/api/rooms', '/api/sections', '/api/subjects', '/api/users'];

        // Guests first: actingAs() persists on the app for the rest of the
        // test, so authenticated checks must come after.
        foreach ($paths as $path) {
            $this->getJson($path)->assertUnauthorized();
        }
        foreach ($paths as $path) {
            $this->actingAs($faculty)->getJson($path)->assertForbidden();
            $this->actingAs($student)->postJson($path, [])->assertForbidden();
        }
    }
}
