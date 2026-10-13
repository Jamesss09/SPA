---
title: SPA Student Portal - Architecture
type: architecture
tags: [spa, portal, architecture, database, api]
---

# 03 - Architecture

Related: [[01-Project-Overview]], [[02-Tech-Stack]], [[04-Phase-Plan]]

## System Flow

```
React + TS (Admin · Faculty · Student dashboards)
  ↕ HTTPS / Sanctum session + CSRF
Laravel REST API (Auth · Policies · Validation · Workflows · Imports · Audit)
  ↕ Eloquent · transactions · migrations
MySQL (Users · Enrollment · Assignments · Schedules · Grades · Announcements)
```

One backend for v1. Frontend/backend separately deployable. API enforces every permission.

## Core Workflows

### A. School setup + enrollment
1. Admin prepares year, levels, sections, rooms, subjects, faculty, assignments.
2. Faculty uploads list for authorized class/section.
3. Laravel validates file + rows, checks duplicates, previews outcome.
4. Per valid new student: profile + login account + unique Student ID + enrollment for year/section.
5. Report: created, matched, enrolled, rows needing correction.

### B. Auto account creation
- Example: John Enquig → `Enquig.john` / initial password = generated Student ID.
- `users.username` unique, `student_profiles.student_number` unique.
- Normalize names, deterministic suffix on collision, never duplicate.
- Match existing by stable ID (not name alone), update enrollment per rules.
- Hash only, `must_change_password` flag, no bulk password export/listing.

### C. Schedule workflow
- Faculty edits draft → submits. Overlap check: teacher + room + section.
- Admin approval rechecks conflicts before activation.
- Edit approved → new proposal/version; old approved stays active until replacement approved.
- Students see approved/active only.
- States: `draft, pending, approved, rejected, superseded`

### D. Quarterly grades
- Faculty drafts per class → submits batch per assignment + grading period.
- Admin returns (with comment) or finalizes. Returned → correct + resubmit.
- Students see finalized only. Post-final corrections = authorized reopen + audit (old/new, actor, time, reason).
- States: `draft, submitted, returned, finalized`. One active submission per assignment+period. One entry per enrollment per submission.

## Database Plan (via migrations, normalized)

**A. Accounts:** `users(id, name, username!unique, email?unique, password_hash, role, status, must_change_password)`, `student_profiles(user_id!unique, student_number!unique, ...)`, `faculty_profiles(user_id!unique, employee_number!unique, ...)`

**B. Structure:** `school_years`, `grading_periods(school_year_id, quarter 1-4!unique together)`, `grade_levels(7-12)`, `rooms`, `sections(school_year_id, grade_level_id, adviser_faculty_id?, room_id?)`, `subjects(code, name, grade_level_id?)`

**C. Enrollment/teaching:** `enrollments(student_profile_id, school_year_id, section_id!unique student+year)`, `teaching_assignments(faculty, subject, section, school_year!unique combo)`

**D. Schedules:** `schedule_proposals(teaching_assignment_id, version_number, status, submitted_at, reviewed_by/at, comment)`, `schedule_entries(proposal_id, room_id, day_of_week, start_time < end_time)`

**E. Grades:** `grade_submissions(assignment_id, grading_period_id, status, submitted_by/at, reviewed_by/at, comment)`, `grade_entries(submission_id, enrollment_id!unique per submission, grade_value, remarks)`

**F. Misc:** `announcements(title, body, created_by, published/expires_at, status)`, `announcement_audiences(announcement_id, audience_type, grade/section?)`, `import_batches(uploaded_by, filename, status, total/success/error)`, `audit_logs(user_id, action, entity, old/new_values, reason)` — never passwords/tokens.

> Exact column types, FK actions, indexes → finalize in implementation.

## API Groups (proposals, not final contract)
- Auth: `POST /api/login · POST /api/logout · GET /api/me`
- Users: `GET/POST /api/users · PATCH /api/users/{id} · POST /api/users/{id}/reset-password`
- Setup: `/api/school-years · /api/grading-periods · /api/grade-levels · /api/sections · /api/rooms · /api/subjects`
- Enrollment: `POST /api/enrollments/import · GET /api/enrollments · PATCH /api/enrollments/{id}`
- Assignments: `/api/teaching-assignments`
- Schedules: `/api/schedule-proposals · .../{id}/submit · .../{id}/review` (explicit `approve/reject`)
- Grades: `/api/grade-submissions · .../{id}/submit · .../{id}/review` (explicit `finalize/return`)
- Student: `GET /api/student/schedule · /grades · /profile`
- Announcements: `GET/POST /api/announcements · PATCH /api/announcements/{id}`
- Profile: `PATCH /api/profile · POST /api/profile/photo · POST /api/password/change`

## Frontend Groups
- Shared: login, reset/change, profile, announcements
- Admin: overview, users, years, levels, sections/rooms, subjects, imports, assignments, schedule + grade approvals, audit
- Faculty: classes, class lists, my schedule, proposals, quarterly grades, submission status
- Student: overview, my schedule, my grades, announcements, my profile

## Security / Deployment Checklist
- [ ] HTTPS, HTTP-only SameSite cookies
- [ ] Sanctum domains + CORS + CSRF
- [ ] Policies/middleware on every route incl. record-level
- [ ] Upload type/size/content validation, transactional imports
- [ ] Private files not on public paths, image validation
- [ ] Env secrets, least-privilege DB, encrypted backups + restore test
- [ ] Audit sensitive changes, PH data-protection + minors privacy
