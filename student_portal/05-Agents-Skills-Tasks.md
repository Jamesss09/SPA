---
title: SPA Student Portal - Agents, Skills, Tasks
type: execution
tags: [spa, portal, agents, skills, tasks]
---

# 05 - Agents, Skills & Tasks

Related: [[01-Project-Overview]], [[02-Tech-Stack]], [[03-Architecture]], [[04-Phase-Plan]]

## Suggested Agents

| Agent | Owns | Key skills |
|-------|------|------------|
| `db-architect` | Migrations, constraints, Eloquent models, seeders | MySQL, Laravel migrations, normalization, unique/FK/index design |
| `backend-dev` | Auth, policies, validation, imports, workflows, audit, API docs | PHP/Laravel, Sanctum, policies/middleware, Pest/PHPUnit, Laravel Excel |
| `frontend-dev` | Layouts, dashboards (Admin/Faculty/Student), forms, API client | React+TS+Vite, Router, Tailwind, Hook Form+Zod, Axios |
| `qa-engineer` | Permission tests, import tests, workflow tests, Postman | Negative testing, IDOR checks, fixtures |
| `devops-docs` | Env, deploy, backups, README | Vercel/static, PHP host, MySQL, GitHub |

> One backend only — no Express/Laravel split. Backend is permission authority.

## Skill Matrix
- **Auth:** Sanctum sessions, CSRF, rate limits, `must_change_password`, `status` gate.
- **AuthZ:** per-route policies + record-level (assigned class, own record).
- **Imports:** file validation, row validation, duplicate detection, preview, transactional writes, `import_batches` summary.
- **Workflows:** state machines for `schedule_proposals` + `grade_submissions`, overlap detection, versioning, audit trail.
- **Frontend:** role routing, reusable tables/forms, error handling, file upload UI.
- **Ops:** env secrets, CORS/domains, storage, scheduler, backups.

## Task Breakdown (prioritized)

### P0 — Foundation + Auth (Phase 1–2)
- [ ] Scaffold `frontend/` + `backend/`, Git init, env templates
- [ ] Migrations for all [[03-Architecture#Database Plan]] tables + uniques
- [ ] Models + relations + seeders (admin, levels 7–12, demo year/period)
- [ ] Sanctum login/logout/me, password change/reset, rate limit, status block
- [ ] Policies: admin-only setup, faculty assigned-only, student own-only
- [ ] Role dashboards shell + route guards (UX only)

### P1 — Admin setup (Phase 3)
- [ ] CRUD: school-years, grading-periods, grade-levels, sections, rooms, subjects
- [ ] User mgmt: faculty/admin create, student read, reset-password, deactivate
- [ ] Adviser + teaching-assignment UI + API

### P2 — Enrollment + import (Phase 4)
- [ ] `POST /api/enrollments/import`: upload → validate → preview → commit
- [ ] Auto-create `users.username` + `student_number`, collision suffix, `must_change_password`
- [ ] Duplicate/malformed reporting, `import_batches` counts
- [ ] Faculty-scoped upload (assigned sections only)

### P3 — Schedules (Phase 5)
- [ ] Proposal CRUD + `submit` + `review(approve/reject)` with comments
- [ ] Overlap validation (teacher/room/section) on submit + approve
- [ ] Versioning: edit approved → new version, old stays active
- [ ] Student timetable = approved only

### P4 — Grades (Phase 6)
- [ ] Batch draft + `submit` + `review(finalize/return)` with comments
- [ ] One active submission per assignment+period, one entry per enrollment
- [ ] Correction + resubmit flow, reopen = admin + audit log
- [ ] Student grades = finalized only

### P5 — Student portal + announcements (Phase 7)
- [ ] `GET /api/student/*`, profile/photo/password endpoints
- [ ] Announcements publish/edit/archive + audiences
- [ ] Student UI: schedule, Q1–Q4 grades, announcements, profile

### P6 — Hardening + deploy (Phase 8)
- [ ] Run all [[04-Phase-Plan#Acceptance Tests]]
- [ ] Prod: HTTPS, Sanctum domains, CORS/CSRF, storage, scheduler, backups
- [ ] Docs: setup, roles, workflows, restore procedure

## Working Agreements
1. Backend validates + authorizes everything; frontend validation is early-feedback only.
2. Review endpoints take explicit actions (`approve`, `reject`, `finalize`, `return`) — never raw status writes.
3. No passwords/tokens in logs, exports, or responses.
4. Every grade/schedule transition writes audit where sensitive.
