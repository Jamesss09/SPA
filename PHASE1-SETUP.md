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
