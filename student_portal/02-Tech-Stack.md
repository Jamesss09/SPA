---
title: SPA Student Portal - Tech Stack
type: reference
tags: [spa, portal, tech-stack, architecture]
---

# 02 - Tech Stack

Related: [[01-Project-Overview]], [[03-Architecture]], [[04-Phase-Plan]]

## Stack (per blueprint p.5 — do not combine Laravel + Express)

| Layer | Technology | Purpose |
|-------|------------|---------|
| Frontend | React + TypeScript + Vite | Dashboards, reusable UI |
| Routing | React Router | Navigation, route UX |
| Styling | Tailwind CSS or standard CSS | Responsive layout |
| API client | Axios or fetch | Talk to Laravel API |
| Forms | React Hook Form + Zod (optional) | Client validation (server re-validates) |
| Backend | Laravel REST API (PHP) | Logic, validation, permissions, imports, workflows |
| Auth | Laravel Sanctum | Cookie/session auth for first-party SPA |
| AuthZ | Policies + middleware | Role + record-level checks |
| ORM | Eloquent + migrations | Schema versioning, relations |
| DB | MySQL | Academic + account records |
| Import | Laravel Excel / maintained CSV/XLSX lib | Student list ingest |
| Testing | PHPUnit/Pest, frontend tests, Postman | API rules, permissions, imports, workflows |
| VCS | Git + GitHub | History, review |
| Hosting | Vercel/static + Laravel PHP host + managed MySQL | Separate frontend/backend/DB deploys |

## Auth Notes
- Sanctum cookie/session + CSRF for first-party SPA.
- Secure HTTP-only cookies, SameSite, HTTPS in prod.
- Configure Sanctum stateful domains, CORS, CSRF for real domains.
- Password hashing, login rate limits, secure reset, `must_change_password` for generated student credentials.
- `POST /api/login`, `POST /api/logout`, `GET /api/me` → frontend routes by `role`.

## Hosting Requirements
Confirm PHP/Laravel version, persistent storage, background jobs, scheduler (cron), MySQL connectivity, env secrets, least-privilege DB user, encrypted backups + tested restore.
