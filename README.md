# Throughline

A production-scheduling Kanban board: work orders move through **Queued → In Progress →
Blocked → Done** across production lines, built as a **Laravel API-only backend** consumed by
a fully separate **React + TypeScript SPA**.

> All data, code, and business logic here are original and fictional. This is a portfolio
> project, not derived from or containing any employer codebase.

## Why this exists

This is a companion piece to [GreenStock](https://github.com/ftocheri/greenstock), not a
repeat of it. GreenStock is a Laravel + Inertia + Vue monolith; this is the opposite shape on
purpose — a genuinely decoupled REST API with a separately-deployed React client, talking
cross-origin over a bearer token. Two different architectures, two different frontend
frameworks, on the same author.

## Architecture

```
throughline/
  api/   Laravel 12, API-only (no Blade/Inertia views), Sanctum token auth
  web/   React 19 + TypeScript, Vite, deployed as a static SPA
```

- **`api/`** and **`web/`** are deployed to two different platforms (Render and Vercel) and
  know nothing about each other beyond the API's base URL and CORS allow-list — there's no
  shared session, no shared cookie, nothing stateful tying them together.
- **Auth is a bearer token**, not a cookie. Simpler than Sanctum's SPA-cookie mode when the two
  halves live on different domains — no `SANCTUM_STATEFUL_DOMAINS`/`SESSION_DOMAIN`
  configuration needed at all.
- **One seeded demo login**, not public registration. The auth flow itself was already proven
  on GreenStock; this project's job is the API/React work, not re-demoing login forms. The
  login screen shows the demo credentials directly — no invite needed.

## The interesting part: how drag-and-drop reordering works

A dropped card sends `{ stage, before_id, after_id }` — the two cards it landed between — never
a raw position value the client computed itself. The server (`app/Actions/MoveWorkOrder.php`)
re-reads real positions from the database inside a transaction and computes the result:

- Enough room between the neighbors → the new card's position is the midpoint.
- Dropped at either end of a column → offset from that one neighbor.
- The gap between neighbors has been halved too many times to represent distinctly →
  the whole column is rebalanced (renumbered by 1000s) before the drop is placed.

The response includes every position in the affected column, not just the moved card's — a
rebalance can silently shift siblings, so the React client patches its whole cache from one
response instead of guessing or refetching the entire board after every drag
(`web/src/features/board/useMoveWorkOrderMutation.ts`).

## Inventory-as-a-ledger, again — but for workflow this time

Same philosophy as GreenStock's `inventory_movements` table, applied to a different domain: a
work order's current stage is just a column, but every *change* to it is also recorded on an
append-only `work_order_events` table — stage transitions, line reassignments, priority
changes, due-date changes. That's the audit trail shown in each card's detail drawer.

One deliberate exclusion: a same-column drag reorder does **not** write an event. A row saying
"position moved from 1500 to 1499.9" has no audit value — it would just make the ledger noisy
in a way GreenStock's isn't. Only real state changes get logged.

## Local development

**Backend:**
```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

**Frontend** (separate terminal):
```bash
cd web
npm install
cp .env.example .env   # VITE_API_URL defaults to http://localhost:8000/api
npm run dev
```

To log in locally with the same demo credentials the live site shows:
```bash
cd api
DEMO_EMAIL=demo@throughline.app DEMO_PASSWORD=throughline-demo php artisan demo:ensure
```

**Tests:** `cd api && php artisan test` (PHPUnit, runs against in-memory sqlite — no database
setup needed). `cd web && npx tsc --noEmit && npm run lint && npm run build` for the frontend.

## Live demo & deployment

The API runs on Render's free web service (Docker) against a free [Neon](https://neon.tech)
Postgres database. The SPA is a static build on [Vercel](https://vercel.com).

**API (Render):**
1. Create a free Neon project and copy its **direct** (non-pooled — no `-pooler` in the
   hostname) connection string. The pooled endpoint runs PgBouncer in transaction-pooling
   mode, which doesn't handle the multi-statement DDL that migrations run.
2. Create a new Web Service from this repo on Render, root directory `api/` — it picks up
   `api/Dockerfile` automatically, or apply `api/render.yaml` as a Blueprint.
3. Set env vars: `DB_CONNECTION=pgsql`, `DB_URL` = the Neon direct connection string,
   `APP_KEY` (generate locally with `php artisan key:generate --show`), `APP_URL`,
   `APP_ENV=production`, `APP_DEBUG=false`, `CORS_ALLOWED_ORIGINS` = the Vercel URL (once
   known), `DEMO_EMAIL` / `DEMO_PASSWORD`.
4. After the first deploy, open Render's Shell tab once and run:
   ```bash
   php artisan db:seed --force
   php artisan demo:ensure
   ```
   Both are one-time — `docker-entrypoint.sh` runs `php artisan migrate --force` on every boot
   (safe/idempotent) but deliberately doesn't seed automatically, since the seeder isn't
   idempotent and this app has no persistent disk (a free-tier container can restart often).

**SPA (Vercel):**
1. Import this repo into Vercel, set the project root to `web/`. Vercel auto-detects Vite.
2. Set `VITE_API_URL` to the Render API's URL plus `/api`.
3. Once the SPA has a URL, go back and set `CORS_ALLOWED_ORIGINS` on Render to match it, then
   redeploy the API.

## Tech stack

**Backend:** Laravel 12, Sanctum (token auth), PHPUnit, Postgres (Neon) in production /
SQLite locally, Docker.

**Frontend:** React 19, TypeScript, Vite, TanStack Query, `@dnd-kit` (not
`react-beautiful-dnd` — archived, no real React 18+ support), React Router.

**CI:** GitHub Actions (`.github/workflows/ci.yml`) runs the backend PHPUnit suite and a
frontend typecheck/lint/build on every push and PR.
