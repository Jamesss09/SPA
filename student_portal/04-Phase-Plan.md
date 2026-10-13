---
title: SPA Student Portal - Phase Plan
type: plan
tags: [spa, portal, phases, acceptance-criteria]
---

# 04 - Phase Plan

Related: [[01-Project-Overview]], [[03-Architecture]], [[05-Agents-Skills-Tasks]]

## Phases 1–8

| # | Phase | Deliverables | Done when |
|---|-------|--------------|-----------|
| 1 | Foundation | React/Vite, Laravel API, MySQL, migrations, local env, Git | Migrations run reliably; relations + setup documented |
| 2 | Authentication | Shared login/logout, role routing, profile endpoints, status, policies | Each role only authorized endpoints; deactivated blocked |
| 3 | Admin setup | Years, periods, levels, sections, rooms, subjects, user mgmt | Admin prepares year via UI, no manual DB edits |
| 4 | Enrollment + assignments | Import, validation, auto accounts, section enroll, advisers, teaching assignments | Preview works, duplicates caught, correct account+enrollment |
| 5 | Schedule workflow | Drafts, overlap checks, approve/reject, versioning, timetable | Unapproved never replaces active timetable |
| 6 | Grade workflow | Draft batches, submit/return/correct/finalize, audit | Students see finalized only; unauthorized rejected + audited |
| 7 | Student portal | Schedule, grades, announcements, profile/photo, password | Student sees only own records |
| 8 | Testing + deployment | Permission/import/schedule/grade tests, prod config, backups, docs | Acceptance passes + restore demonstrated |

## Acceptance Tests
- [ ] Change URL/API ID to another student's grades → denied.
- [ ] Upload dup IDs, dup names, malformed rows, username collisions → reported, no corruption.
- [ ] Conflicting schedule proposals → conflicting approval blocked.
- [ ] Edit submitted/finalized grade w/o auth → rejected.
- [ ] Move student section next year → prior grades stay on historical enrollment.
- [ ] Deactivate account → protected requests denied.

## Open Decisions & Risks
| Decision | Why | Status |
|----------|-----|--------|
| Grading policy | Range, transmutation, rounding, final formula | Unresolved — confirm SPA |
| Student ID source | Initial password; system vs official | Provisional: system-generated |
| Username collision | Same surname+firstname | Deterministic suffix or admin resolve — finalize |
| Identity matching | Name unreliable | Prefer official ID / approved key |
| Schedule format | Free weekly vs fixed periods | Unresolved |
| Adviser rules | 1 vs many per section | Provisional: 1 adviser/section |
| Import formats | Columns + types | Provisional: .xlsx + CSV |
| Announcement audiences | Role/grade/section targeting | Proposed: school-wide + targeted |
| Grade corrections | Who reopens, extra approval | Recommended: admin-controlled + audited |
| Retention/privacy | How long keep records/logs/uploads | Align w/ policy + PH law |

Risks → mitigations: duplicates (stable IDs + unique constraints + preview), credential exposure (hash + force-change + no bulk export), wrong grades/schedules (states + gates + transactions + audit), cross-student exposure (record-level AuthZ + negative tests), data loss (backups + restore tests), policy mismatch (confirm before build).

**Next step per blueprint:** confirm policies → implementation-ready schema (exact MySQL types, PK/FK, indexes, uniques, delete/update) + prioritized tasks. Start with DB + auth. See [[05-Agents-Skills-Tasks]].
