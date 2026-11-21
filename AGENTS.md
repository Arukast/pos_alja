# AGENTS.md

## Section 1: Agent Operational Workflow

### 1.1 Startup Workflow

Before writing code:

1. Confirm the working directory with `pwd`.
2. Review the last five commits with `git log --oneline -5`.
3. Run `./init.sh`. It installs backend and frontend dependencies, runs the API test suite, and runs the frontend typecheck, lint, tests, and production build.
4. If verification fails, fix the baseline before adding new work.

The repository runs natively. Docker Compose is the deployment path, not the default development path.

### 1.2 Working Rules

1. Work on one feature at a time.
2. Do not mark work complete because code was added.
3. Keep changes inside the requested scope.
4. Do not silently change verification rules.
5. Put durable decisions in `docs/`, not only in chat.

### 1.3 Required Artifacts

1. `docs/`: the maintained requirements, architecture, and contract set.
2. `init.sh`: the standard native startup and verification path.
3. `session-handoff.md`: optional, for long sessions.

### 1.4 Definition Of Done

A change is complete only when:

1. The behavior is implemented.
2. Backend tests and frontend checks pass.
3. Verification evidence is recorded.
4. The matching `docs/` entries are updated.
5. `./init.sh` succeeds from a clean checkout.

### 1.5 End Of Session

1. Check that `docs/` matches the code.
2. Record unresolved risks or debt in the relevant doc or `session-handoff.md`.
3. Commit with a Conventional Commit message.
4. Leave the repository restartable through `./init.sh`.

## Section 2: Stack And Architecture

### 2.1 Stack

- Laravel 13, PHP 8.5 (composer floor `^8.3`).
- PostgreSQL in production; SQLite `:memory:` in tests.
- Laravel Sanctum SPA cookie authentication.
- Laravel Reverb private channels.
- React 19, TypeScript 7, Vite, Tailwind CSS 4.
- TanStack Query, React Router, React Hook Form/Zod where needed.
- No Livewire and no Blade application UI. The SPA is the only UI.

### 2.2 API-First Layout

```
app/Modules/{Identity,Catalog,Sales,Inventory,Production,Reporting,Audit}
  Http/Controllers
  Http/Requests
  Http/Resources
  Actions
  Queries
app/Models              shared Eloquent persistence
app/Policies            record-level authorization
app/Support             BranchScope, Decimal, SafeBroadcast
app/Services            shared domain services used by module Actions
routes/api.php          /api/v1
routes/channels.php     private branch and owner channels
frontend/               React SPA
```

Rules:

1. Controllers validate through FormRequests, authorize through policies, delegate to one Action or Query, and return a Resource.
2. Business writes live in Actions, not controllers.
3. Multi-table writes run in `DB::transaction()`.
4. Reporting may read across modules; other cross-module writes go through the owning module.
5. Models are shared; do not duplicate persistence classes per module.

### 2.3 Roles And Branch Scope

- Roles: `owner`, `manager`, `cashier`, `designer`, `production`.
- Non-owner users are always scoped to `auth()->user()->branch_id`.
- A non-owner with no branch is denied, not treated as global.
- Owner may use `branch_id=all` in reporting; managers cannot.
- Use `App\Support\BranchScope` for branch columns.
- Managers manage only their own branch and only cashier/designer/production staff.
- Global masters (customers, materials) follow `docs/architecture/erd.md`; stock operations are branch-scoped.
- Ticket is visible to all roles. Receipt is denied to production.

### 2.4 Money Visibility

- Owner and manager: price, cost, profit.
- Designer: sell price, no cost or profit.
- Cashier: invoice totals, receivable, discount, payments; no cost or profit.
- Production: no price, cost, profit, discount, receivable, or payment fields.
- Money is transported as decimal strings with two places. Use `App\Support\Decimal`.
- Never trust client money. Quotes and invoice lines are recomputed server-side.

## Section 3: Database And Domain Codes

### 3.1 Status Codes

- `invoices.status`: 0 Unpaid, 1 Down Payment, 2 Paid.
- `invoices.pickup_status`: 0 Not Picked Up, 1 Picked Up.
- `orders.status`: 0 Pending, 1 Processing, 2 Completed.

### 3.2 Keys And Portability

1. All primary and foreign keys are UUID strings. Tests and factories use UUIDs.
2. Migrations and queries must work on SQLite and PostgreSQL. No platform-specific raw SQL.
3. Never edit a merged migration. Add a new migration.

### 3.3 Transactions And Logging

1. Multi-table mutations run in one transaction.
2. Lock invoices and orders with `lockForUpdate()` for payment and production transitions.
3. Log caught domain exceptions with user and branch context through `Log::error()`.
4. Use `App\Support\SafeBroadcast` for after-commit broadcast failures; a broadcaster must not fail a successful write.

## Section 4: Frontend

### 4.1 Structure

```
frontend/src/
  api/            client, queries, mutations, types
  auth/           AuthProvider, guards, role home
  components/     shell, UI primitives, tables, admin CRUD shell
  features/       owner, manager, cashier, designer, production, print
  hooks/          branch, period, realtime
  lib/            money, dates, errors, Echo
  routes/         router and legacy redirects
  styles/         Tailwind @theme tokens
```

### 4.2 Rules

1. Tailwind 4 tokens live in `frontend/src/styles/app.css` via `@theme`. Do not create `tailwind.config.js`.
2. Use the shared UI primitives and `CrudPage` before adding one-off markup.
3. Server state uses TanStack Query. Invalidate the affected keys after mutations.
4. Use `apiFetch` for every API call so CSRF, cookies, and 401 handling stay consistent.
5. Keep the sidebar layout, dark mode, responsive breakpoints (390, 768, 1440), and print layouts.
6. Keep the current UI copy and Indonesian domain terms; code stays English.
7. The SPA must not compute authoritative prices. Quotes come from `/api/v1/quotes`.
8. `OrderUpdated` invalidates queries; do not trust event payloads.

## Section 5: Testing And Verification

### 5.1 Backend

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=1G
composer audit
```

Every endpoint needs tests for guest 401, wrong role 403, cross-branch denial, validation 422, and the happy path. Production responses must be asserted free of money keys.

### 5.2 Frontend

```bash
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build
```

Use `agent-browser` for role and viewport smoke checks. Verify 390, 768, and 1440 px.

### 5.3 Startup

`./init.sh` is the standard verification path. Do not replace it with an undocumented command sequence.

## Section 6: Environment And Deployment

1. Never read, print, or commit `.env` or secrets.
2. Add every new variable to `.env.example`.
3. Production uses PostgreSQL, database sessions/cache/queue, HTTPS cookies, and Sanctum stateful domains.
4. Reverb runs on container port 8080, host port 8084.
5. Docker Compose services: `app`, `nginx`, `db`, `reverb`. Nginx serves the SPA and proxies `/api`, `/sanctum`, and `/broadcasting`.
6. Build the SPA with `npm --prefix frontend run build`; serve `frontend/dist`.
7. CI runs backend and frontend jobs on `main`, `staging`, and pull requests. It never auto-commits.

## Section 7: Coding Standards

1. PSR-12; `declare(strict_types=1);`; explicit argument and return types.
2. Four spaces in PHP and TypeScript; two spaces in YAML and Markdown.
3. Conventional Commits.
4. No new dependency without asking.
5. Do not stage with `git add -A` or `git add .`.
6. No emojis in documentation.

## Section 8: Documentation

Update in the same change:

- Behavior and acceptance criteria: `docs/requirements/fr.md`.
- Security, performance, reliability, portability, operations: `docs/requirements/nfr.md`.
- Roles, permissions, branch scope: `docs/requirements/access-matrix.md`.
- Tables and relationships: `docs/architecture/erd.md`.
- Multi-actor flows: `docs/architecture/sequence-diagrams.md`.
- HTTP routes, resources, errors, broadcasting, frontend routes: `docs/architecture/api-contracts.md`, `docs/architecture/frontend-architecture.md`.
- Deployment: `docs/architecture/deployment.md`.
- As-built status and remaining FR sprints: `docs/requirements/implementation-gaps.md`.

If code and docs disagree, treat it as a defect.
