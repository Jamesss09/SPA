# Phase 1 — Foundation (complete)

## What was built
- Git repo init (`main`), `.gitignore`
- `frontend/` — React 19 + TS + Vite 8, Router, Axios, Tailwind v3, role shells:
  `src/App.tsx`, `src/auth/AuthContext.tsx`, `src/lib/api.ts`,
  `src/components/Layout.tsx`, `src/components/ProtectedRoute.tsx`,
  `src/pages/{Login,AdminDashboard,FacultyDashboard,StudentDashboard}.tsx`
  Build verified: `npm run build` passes.
- `backend/` — Laravel 12, Sanctum 4 (`personal_access_tokens` migrated),
  6 SPA migrations + 10 tables + uniques, 19 Eloquent models, seeder.
  `php artisan migrate` (10/10 Ran), `php artisan db:seed` (6 levels, 4 periods, 1 admin), `php artisan test` (2 passed).
- `prototype/` — untouched clickable mock.

## Run
- Backend (MySQL `spa_portal` on 127.0.0.1:3307): `cd backend && php artisan serve` → http://localhost:8000
- Frontend: `cd frontend && npm run dev` → http://localhost:5173 (local `.env` already created from example)
- Seed admin: username `admin` / password `password`
- Fresh reset: `cd backend && php artisan migrate:fresh --seed --force` (11/11 Ran on MySQL, verified)

## MySQL cutover (done)
Wired to service `MySQL80` on port 3307, database `spa_portal`.
Credentials live only in gitignored `backend/.env` — never committed.

Phase 2 (Auth) is next: Sanctum stateful/CORS config + login/logout/me endpoints.

## Phase 2 — Authentication (complete)

Endpoints (`backend/routes/api.php`, all under `web` middleware for session+CSRF):
- `POST /api/login` (throttle `login`: 5/min per username+IP) → `{user:{id,name,username,role,status,must_change_password}}`; deactivated → 403 + session cleared.
- `POST /api/logout` (`auth:sanctum`), `GET /api/me` (`auth:sanctum` + `active`), `POST /api/password/change` (clears `must_change_password`).
- Guards: `EnsureActive` (alias `active`), `EnsureRole` (alias `role:...`, for later phases).

Env keys (values stay in gitignored `backend/.env`, never committed):
- `SANCTUM_STATEFUL_DOMAINS=localhost:5173`, `FRONTEND_URL=http://localhost:5173`
- `SESSION_DOMAIN=null` (correct for localhost: cookies ignore ports),
  `SESSION_SECURE_COOKIE=false` local / `true` prod, `SESSION_SAME_SITE=lax`.
- CORS (`backend/config/cors.php`): paths `api/*` + `sanctum/csrf-cookie`, explicit origin, `supports_credentials=true`.

Frontend: `AuthContext` does csrf-cookie → login → me, restores session on boot;
`Login` is a real form (401/403/422/429 errors); `ProtectedRoute` honors loading
and `must_change_password` (`/change-password` page); `Layout` logout awaits API.
Password-reset email deferred to Phase 7.

## Phase 3 — Admin setup (complete)

Admin-only (`auth:sanctum` + `active` + `admin`; faculty/student → 403, guest → 401):
- CRUD: `/api/school-years`, `/api/grading-periods` (`?school_year_id=`),
  `/api/grade-levels`, `/api/rooms`, `/api/sections` (`?school_year_id=`, `?grade_level_id=`),
  `/api/subjects`, `/api/users` (`?role=`, `?status=`, `?search=`, paginated).
- Users: create admin|faculty only (students via Phase 4 import), PATCH role/status,
  `POST /users/{id}/reset-password` (temporary PW + force-change), deactivate via status.
- Rules in FormRequests: uniques, quarter 1–4 + unique per year, section name unique
  per year+level, adviser must be active faculty. Deletes blocked with 409 when in use.
- Decisions: single active year enforced in code (others auto-deactivated);
  per-record policies deferred to Phase 4 where faculty ownership checks appear.
- Frontend: `/admin/*` pages (years, periods, levels, sections, rooms, subjects, users)
  with real counts on the overview; 403/422 surfaced inline.

## Phase 4 — Enrollment & assignments (complete)

- Import: `POST /enrollments/import/preview` (multipart file + section_id, no writes)
  → rows classified ok/matched/duplicate/error + summary; `POST .../commit`
  (section_id + rows) re-validates and writes transactionally + `import_batches` row.
  Columns: last_name*, first_name*, middle_name, birth_date Y-m-d, contact_number,
  student_number (optional). CSV native; XLSX via phpoffice/phpspreadsheet
  (needs php `ext-gd` — enabled in `C:\xampp\php\php.ini`).
- Accounts: username `Surname.lowercased-firstname` (`Enquig.john`), numeric suffix
  on collision; blank IDs generated `{startYear}-0001…`; initial password = student
  number (hashed), `must_change_password=true`. Existing IDs match (no dup accounts);
  same-year other-section enrollment → error row for admin decision. No password lists.
- Scope: admin school-wide; faculty only taught/advised sections
  (`SectionAccess::canManage` — the record-level pattern for later phases).
- Teaching assignments: admin CRUD by faculty user (profile auto-created `EMP-{id}`,
  year derived from section); faculty index auto-scoped to own; class lists via
  assignment/section students endpoints. Advisers via section PATCH (1/section).
- Frontend: `/admin/imports` (upload→preview→commit), `/admin/assignments`,
  `/faculty/classes` (assignments + class lists + scoped import).
- Env note: `composer dump-autoload` with scripts hangs here — use `--no-scripts`.
