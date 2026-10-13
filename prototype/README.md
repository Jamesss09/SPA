# SPA Student Portal — Prototype

Clickable mock. No backend, no database. All data is in `data.js` and resets on reload.

## Run

No build step. Either:

1. Double-click `index.html`, or
2. `cd prototype && npx serve .` (or `python -m http.server`)

Logo loads from `../SPA_logo.jpg`.

## What is mocked

- Shared login with role switcher: **Admin / Faculty / Student**
- Admin: overview, users, school setup, import preview, schedule approvals (approve/reject), grade approvals (finalize/return), announcements, audit
- Faculty: my classes, class list, schedule proposals, Q2 grade draft + submit
- Student: overview, approved schedule only, finalized grades only, announcements, profile (photo/password stubbed)

## What is NOT real

Sanctum auth, MySQL, validations, policies, file uploads. Those belong to Phase 1+ per `../student_portal/04-Phase-Plan.md`.
