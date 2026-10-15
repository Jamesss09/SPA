# SPA Student Portal — Phase Tracker

Current: Phase 1 complete, Phase 2 next.

- [x] Phase 1 — Foundation
  - React/Vite, Laravel API, MySQL, migrations, local env, Git repo.
  - Done: `migrate` 10/10, `db:seed`, `artisan test` 2 passed, `npm run build` passes. See `PHASE1-SETUP.md`.
- [x] Phase 2 — Authentication
  - Shared login, logout, role routing, `GET /me`, account status, policies.
  - Done: 7 AuthTest pass (login/me/logout, 422s, 403 deactivated, 401 guest,
    throttle 5/min, password-change flag), E2E over HTTP+MySQL
    (csrf 204 → login 200 → me 200 → logout 200 → me 401, ACAO localhost:5173),
    `npm run build` passes. Reset-email deferred to Phase 7.
- [x] Phase 3 — Admin setup
  - School years, grading periods, grade levels, sections, rooms, subjects, user management.
  - Done: IsAdmin gate, FormRequests, 7 AdminSetupTest pass (CRUD, 422 dup/FK,
    409 delete-blocks, 403/401 gates, deactivate→login-403), HTTP E2E built a
    full year (year→Q1-4→room→subject→faculty→section+adviser, dup→422,
    faculty→403), `npm run build` passes. Single-active-year enforced in code;
    section names unique per year+level (app-level); adviser = active faculty.
- [x] Phase 4 — Enrollment & assignments
  - Student import, validation, auto accounts, section enrollment, advisers, teaching assignments.
  - Done: preview/commit import (CSV+XLSX), 5 ImportTest pass (classification,
    account creation, `Enquig.john`+suffix rule, faculty scope, assignment CRUD),
    HTTP E2E built a full year+import (preview 3/0/1/1, commit batch, faculty 403
    elsewhere, student login via ID), `npm run build` passes. Username =
    surname.lowercased-firstname; blank IDs generated `{year}-0001…`; faculty
    scoped to taught/advised sections; advisers via section PATCH.
- [ ] Phase 5 — Schedule workflow
  - Draft proposals, overlap checks, approval/rejection, versioning, student timetable.
  - Criteria: unapproved never replaces active approved timetable.
- [ ] Phase 6 — Grade workflow
  - Draft batches, submission, return/correction, finalization, audit trail.
  - Criteria: students see only finalized grades; unauthorized changes rejected + audited.
- [ ] Phase 7 — Student portal
  - Schedule, quarterly grades, announcements, profile/photo, password change.
  - Criteria: student sees only own authorized records.
- [ ] Phase 8 — Testing & deployment
  - Permission/import/schedule/grade tests, prod config, backups, docs.
  - Criteria: acceptance tests pass, backup restore demonstrated.
