# Phase 1 — Foundation (done, with one follow-up)

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
- Backend (sqlite dev): `cd backend && php artisan serve` → http://localhost:8000
- Frontend: `cd frontend && cp .env.example .env && npm run dev` → http://localhost:5173
- Seed admin: username `admin` / password `password`
- Fresh reset: `cd backend && php artisan migrate:fresh --seed --force`

## Follow-up before Phase 2
MySQL is installed but not yet wired: service `MySQL80` listens on **port 3307**
(not 3306) and `root` requires a password (`ERROR 1045` with empty password).
To cut over from sqlite:
1. Get/create MySQL credentials with `CREATE DATABASE spa_portal`.
2. Set in `backend/.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3307`,
   `DB_DATABASE=spa_portal`, `DB_USERNAME=...`, `DB_PASSWORD=...`.
3. Re-run `php artisan migrate:fresh --seed --force` and confirm 11/11 Ran.

Phase 2 (Auth) starts after this cutover + Sanctum stateful/CORS config.
