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
