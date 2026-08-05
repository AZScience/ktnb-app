# AGENTS.md

## Cursor Cloud specific instructions

This is a single **Laravel 13 / PHP 8.3** application (KTNB internal-audit system for NTTU). It is not a monorepo. Standard commands live in `composer.json` scripts and the deploy notes under `deploy/hosting/`.

### Services & how to run them
- Full dev stack: `composer dev` — runs `php artisan serve` (web on `:8000`), `queue:listen`, `pail` (log tail), and `npm run dev` (Vite HMR on `:5173`) concurrently.
- App only: `php artisan serve --host=0.0.0.0 --port=8000`. Health check: `GET /up` returns `200`.
- Assets: dev uses Vite (`:5173`); for a serve-only setup run `npm run build` once so `@vite` uses built assets in `public/build/` (no `public/hot` file).
- Lint: `./vendor/bin/pint` (use `./vendor/bin/pint --test` to check without writing). The repo has many pre-existing style deviations (including root-level `_*.php` and `scripts/*.php` scratch files); a failing `pint --test` is the pre-existing baseline, not something your change caused.
- Tests: `composer test` or `php artisan test` (uses in-memory SQLite, sync queue — see `phpunit.xml`). Baseline: 3 pre-existing failures in `tests/Feature/ExampleTest.php` and `tests/Feature/ProfileTest.php` — they are stock Laravel/Breeze tests that assume default routes, but this app redirects `/` → `/dashboard` and profile updates redirect to `/`. Unrelated to environment setup.

### Database (important caveat)
- Default dev DB is **SQLite** (`.env` `DB_CONNECTION=sqlite`, file `database/database.sqlite`). This matches `composer setup`.
- Some queries use **MySQL-only SQL functions** (`STR_TO_DATE`, `DATE_FORMAT`) — notably the birthday widget in `DashboardController`, plus parts of `ScheduleController`, `ReportQueryService`, and `BackupController`. On SQLite these throw `QueryException: no such function: STR_TO_DATE`, so **the `/dashboard` landing page (and some report/schedule pages) crash on the default SQLite DB**. Catalog/CRUD pages (e.g. `/personnel/departments`) work fine on SQLite.
- To exercise the dashboard/reports end to end, point the app at **MySQL** (production uses MySQL 8 — see `deploy/hosting/env.production`): set `DB_CONNECTION=mysql` + `DB_*` vars, create the database, then `php artisan migrate`.
- After pulling code that adds migrations, run `php artisan migrate` (migrations are intentionally NOT in the startup update script).

### Seeded login (for manual testing)
- `php artisan db:seed` (NttuSeeder) creates an admin user: **`ngviphuc@gmail.com` / `Nttu@2026`**. Log in at `/login` (Vietnamese UI).

### npm gotcha
- `@zxing/browser` and `@zxing/library` have a peer-dependency conflict, so plain `npm install` / `npm ci` fails. Always use `--legacy-peer-deps` (the startup update script already does this).
