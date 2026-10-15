<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Phase 4: student-list import. Two stateless steps sharing this logic:
// preview (no writes) and commit (one transaction, re-validates everything).
// Columns (case-insensitive header): last_name*, first_name*, middle_name,
// birth_date (Y-m-d), contact_number, student_number (optional; generated if blank).
class StudentImportService
{
    public static function readRows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, ['xlsx', 'xls'], true)) {
            if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                throw new \RuntimeException('Excel files need phpoffice/phpspreadsheet installed.');
            }
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath())->getActiveSheet();
            $grid = $sheet->toArray(null, true, true, false);
            $grid = array_values(array_filter($grid, fn ($r) => count(array_filter($r, fn ($c) => $c !== null && $c !== '')) > 0));
            if (! $grid) {
                return [];
            }
            $headers = array_map([self::class, 'normHeader'], array_shift($grid));
            return array_map(fn ($r) => array_combine($headers, array_pad(array_values($r), count($headers), null)), $grid);
        }
        $text = file_get_contents($file->getRealPath());
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text); // strip BOM
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn ($l) => $l !== ''));
        if (! $lines) {
            return [];
        }
        $headers = array_map([self::class, 'normHeader'], str_getcsv(array_shift($lines)));
        return array_map(fn ($l) => array_combine($headers, array_pad(str_getcsv($l), count($headers), null)), $lines);
    }

    private static function normHeader(mixed $h): string
    {
        $h = strtolower(trim((string) $h));
        return match ($h) {
            'surname', 'lastname', 'family_name' => 'last_name',
            'firstname', 'given_name' => 'first_name',
            'middlename', 'middle_initial' => 'middle_name',
            'student_no', 'studentno', 'student_id', 'id_number' => 'student_number',
            'birthdate', 'dob', 'date_of_birth' => 'birth_date',
            'contact', 'phone', 'mobile' => 'contact_number',
            default => $h,
        };
    }

    /** Classify every row without writing. Returns rows + summary. */
    public static function preview(array $rows, Section $section): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $i => $row) {
            $out[] = self::classify($row, $i + 1, $section, $seen);
        }
        return self::summarize($out);
    }

    /** Write valid rows transactionally. Returns rows + summary + batch id. */
    public static function commit(array $rows, Section $section, int $uploaderId): array
    {
        return DB::transaction(function () use ($rows, $section, $uploaderId) {
            $seen = [];
            $out = [];
            foreach ($rows as $i => $row) {
                $out[] = self::apply(self::classify($row, $i + 1, $section, $seen), $section);
            }
            $summary = self::summarize($out);
            $batch = \App\Models\ImportBatch::create([
                'uploaded_by' => $uploaderId,
                'original_filename' => 'api-commit',
                'status' => 'committed',
                'total_rows' => $summary['total'],
                'success_count' => $summary['created'] + $summary['matched'],
                'error_count' => $summary['errors'] + $summary['duplicates'],
            ]);
            $summary['import_batch_id'] = $batch->id;
            return $summary;
        });
    }

    private static function classify(array $row, int $n, Section $section, array &$seen): array
    {
        // Rows carrying a preview-generated number must regenerate at commit
        // (DB state changed since preview); only truly provided IDs match.
        if (! empty($row['generated'])) {
            $row['student_number'] = '';
        }
        $base = ['row' => $n, 'last_name' => trim((string) ($row['last_name'] ?? '')),
            'first_name' => trim((string) ($row['first_name'] ?? ''))];
        if ($base['last_name'] === '' || $base['first_name'] === '') {
            return $base + ['status' => 'error', 'detail' => 'Missing required last_name/first_name.'];
        }
        $bd = trim((string) ($row['birth_date'] ?? ''));
        if ($bd !== '' && ! self::validDate($bd)) {
            return $base + ['status' => 'error', 'detail' => 'birth_date must be Y-m-d.'];
        }

        $given = trim((string) ($row['student_number'] ?? ''));
        $dedupKey = $given !== '' ? 'id:' . $given
            : 'name:' . strtolower($base['last_name'] . '|' . $base['first_name'] . '|' . $bd);
        if (isset($seen[$dedupKey])) {
            return $base + ['status' => 'duplicate', 'detail' => 'Duplicate of row ' . $seen[$dedupKey] . ' in this file.'];
        }
        $seen[$dedupKey] = $n;

        if ($given !== '') {
            $existing = StudentProfile::where('student_number', $given)->first();
            if (! $existing) {
                // Provided ID unknown → new student keeping the official number
                // (preview lets the uploader catch typos before commit).
                return $base + ['status' => 'ok', 'detail' => 'New with provided ID — will create account + enrollment.',
                    'student_number' => $given, 'username' => self::nextUsername($base['last_name'], $base['first_name'])];
            }
            $enr = Enrollment::where('student_profile_id', $existing->id)
                ->where('school_year_id', $section->school_year_id)->first();
            if ($enr && (int) $enr->section_id !== (int) $section->id) {
                return $base + ['status' => 'error', 'detail' => 'Already enrolled in another section this year; needs admin decision.'];
            }
            return $base + ['status' => 'matched', 'detail' => $enr ? 'Exists — already enrolled here.' : 'Exists — will enroll.',
                'student_number' => $given, 'username' => $existing->user->username];
        }

        $number = self::nextStudentNumber($section);
        return $base + ['status' => 'ok', 'detail' => 'New — will create account + enrollment.',
            'student_number' => $number, 'username' => self::nextUsername($base['last_name'], $base['first_name']),
            'generated' => true];
    }

    private static function apply(array $r, Section $section): array
    {
        if ($r['status'] === 'ok') {
            $user = User::create([
                'name' => $r['first_name'] . ' ' . $r['last_name'],
                'username' => $r['username'],
                'password' => Hash::make($r['student_number']),
                'role' => 'student', 'status' => 'active', 'must_change_password' => true,
            ]);
            $profile = StudentProfile::create(['user_id' => $user->id, 'student_number' => $r['student_number']]);
            Enrollment::create(['student_profile_id' => $profile->id,
                'school_year_id' => $section->school_year_id, 'section_id' => $section->id,
                'status' => 'enrolled', 'enrolled_at' => now()]);
            return $r;
        }
        if ($r['status'] === 'matched') {
            $profile = StudentProfile::where('student_number', $r['student_number'])->first();
            Enrollment::firstOrCreate(
                ['student_profile_id' => $profile->id, 'school_year_id' => $section->school_year_id],
                ['section_id' => $section->id, 'status' => 'enrolled', 'enrolled_at' => now()]);
            return $r;
        }
        return $r;
    }

    private static function summarize(array $rows): array
    {
        $c = ['total' => count($rows), 'created' => 0, 'matched' => 0, 'duplicates' => 0, 'errors' => 0];
        foreach ($rows as $r) {
            match ($r['status']) {
                'ok' => $c['created']++, 'matched' => $c['matched']++,
                'duplicate' => $c['duplicates']++, default => $c['errors']++,
            };
        }
        return $c + ['rows' => $rows];
    }

    private static function validDate(string $d): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    }

    /** surname.firstname per blueprint example (Enquig.john): surname as-is, first name lowercased; numeric suffix on collision. */
    public static function nextUsername(string $last, string $first): string
    {
        $clean = fn ($s) => (string) preg_replace('/[^A-Za-z0-9]+/', '', $s);
        $base = $clean($last) . '.' . strtolower($clean($first));
        $name = $base;
        for ($i = 2; User::where('username', $name)->exists(); $i++) {
            $name = $base . $i;
        }
        return $name;
    }

    /** {startYear}-{0001…}, bumping past existing numbers (DB + file order safe). */
    public static function nextStudentNumber(Section $section): string
    {
        $year = $section->schoolYear?->start_date
            ? (int) substr($section->schoolYear->start_date, 0, 4)
            : (int) date('Y');
        $prefix = $year . '-';
        $seq = StudentProfile::where('student_number', 'like', $prefix . '%')->count() + 1;
        do {
            $candidate = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        } while (StudentProfile::where('student_number', $candidate)->exists());
        return $candidate;
    }
}
