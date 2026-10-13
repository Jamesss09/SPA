<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\GradeLevel;
use App\Models\GradingPeriod;
use App\Models\Room;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Grade levels 7–12
        foreach (range(7, 12) as $n) {
            GradeLevel::firstOrCreate(['level_number' => $n], ['name' => "Grade $n"]);
        }

        // Active school year + Q1–Q4
        $year = SchoolYear::firstOrCreate(
            ['name' => 'A.Y. 2026–2027'],
            ['start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => 'active']
        );
        foreach (range(1, 4) as $q) {
            GradingPeriod::firstOrCreate(
                ['school_year_id' => $year->id, 'quarter_number' => $q],
                ['status' => 'active']
            );
        }

        Room::firstOrCreate(['room_name' => 'Room 101'], ['capacity' => 40]);
        Room::firstOrCreate(['room_name' => 'Room 204'], ['capacity' => 40]);

        $g7 = GradeLevel::where('level_number', 7)->first();
        Subject::firstOrCreate(['subject_code' => 'MATH7'], [
            'subject_name' => 'Mathematics 7', 'grade_level_id' => $g7?->id,
        ]);

        // Demo admin (login: admin / password)
        User::firstOrCreate(['username' => 'admin'], [
            'name' => 'Admin Principal',
            'email' => 'admin@spa.local',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'active',
        ]);

        Announcement::firstOrCreate(['title' => 'Welcome to SPA Portal (seed)'], [
            'body' => 'Phase 1 seed data. Real announcements ship in Phase 7.',
            'created_by' => User::where('username', 'admin')->first()->id,
            'published_at' => now(),
            'status' => 'published',
        ]);
    }
}
