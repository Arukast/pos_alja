# Alja POS

API-first point-of-sale for a digital printing shop. The Laravel backend owns the domain and exposes a versioned REST API; the React SPA is the only web UI.

## Stack

- PHP 8.5 (composer floor `^8.3`), Laravel 13, Sanctum, Reverb.
- PostgreSQL in production; SQLite `:memory:` in tests.
- React 19, TypeScript, Vite, Tailwind CSS 4, TanStack Query, React Router.

## Features

- Username/password session login, profile, password, and CSRF protection.
- Owner, manager, cashier, designer, and production dashboards.
- Branches, users, customers, products, materials, attributes, and pricing matrices.
- Designer quotes, invoices, work-order tickets, and receipt printing.
- Cashier down payments, settlements, pickup queue, and expenses.
- Production queue, start/complete/fail/reopen transitions, waste logging, and stock ledgers.
- Owner finance reports, manager designer leaderboard, and audit trail.
- Role and branch isolation on every endpoint; money redaction for production.
- Private realtime channels for branch and owner updates.

FR9, FR10, FR24, and FR26 remain future sprints. See `docs/requirements/implementation-gaps.md`.

## Requirements

- PHP 8.3+ (8.5 tested), Composer 2.
- Node 20+ (26 LTS tested), npm.
- SQLite for local tests; PostgreSQL for production.
- Docker optional, for the Compose deployment.

## Setup

```bash
composer install
npm --prefix frontend ci
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Set the Reverb app key/secret in `.env` if you want realtime updates. The SPA can run without Reverb; broadcasts are then disabled.

## Development

Run the API, Reverb, and Vite in separate terminals:

```bash
php artisan serve
php artisan reverb:start --host=0.0.0.0 --port=8080
npm --prefix frontend run dev
```

The SPA runs at `http://localhost:5173` and proxies `/api`, `/sanctum`, and `/broadcasting` to `http://127.0.0.1:8000`.

## Verification

```bash
./init.sh
```

Or individually:

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=1G
composer audit
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build
```

## API

- Base path: `/api/v1`.
- Authentication: Sanctum stateful cookie + CSRF.
- Money: decimal strings.
- Full contract: `docs/architecture/api-contracts.md`.

## Deployment

Docker Compose builds the PHP-FPM app, Nginx SPA host, PostgreSQL, and Reverb:

```bash
docker compose up -d --build
```

Prepare `.env` first. The Compose stack expects production-safe `APP_KEY`, `DB_*`, `SESSION_*`, `SANCTUM_STATEFUL_DOMAINS`, and `REVERB_*` values. See `docs/architecture/deployment.md`.

## Documentation

- `docs/requirements/` — functional, non-functional, access, and gap tracking.
- `docs/architecture/` — API contract, frontend architecture, deployment, ERD, use cases, sequence diagrams.
- `AGENTS.md` — working rules for contributors and agents.
