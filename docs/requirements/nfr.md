# Non-Functional Requirements

- **System:** Print Shop POS
- **Document scope:** Quality attributes of the current application and the targets the implementation is measured against.
- **Status legend:**
  - **Enforced** — guaranteed by code, configuration, or an automated check in the repository.
  - **Partial** — implemented on the main path, with a known gap.
  - **Target** — an agreed goal that is not yet measured or enforced by tooling.

---

## 1. Security

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-SEC-01 | Role-based access control SHALL restrict administrative features to `owner` and `manager`, and SHALL reject unauthorized roles. | Feature tests under `tests/Feature/*Authorization*` and `*ManagementTest.php`; inline `abort(403)` checks. | Partial (see gaps: `UserManagement::$mode`, material branch switching, unused `RoleMiddleware`) |
| NFR-SEC-02 | Branch-bound users SHALL NOT read or mutate another branch's invoices, orders, tickets, receipts, stock, machines, or materials. | `BranchAuthorizationAndScopingTest.php`; branch assertions in routes and components. | Partial (material and user-management branch scope are client-controlled) |
| NFR-SEC-03 | All state-changing web requests SHALL be protected against CSRF. | Laravel `web` middleware and Livewire request signing. | Enforced |
| NFR-SEC-04 | Dynamic output SHALL be HTML-escaped; inline JavaScript data SHALL use `Js::from()` rather than string interpolation. | Blade `{{ }}` review; `AGENTS.md` §2.2.3. | Enforced (convention) |
| NFR-SEC-05 | Database access SHALL use Eloquent or parameter-bound query builder statements; no user input SHALL be concatenated into SQL. | Code review; `AGENTS.md` §2.2.2. | Enforced (convention) |
| NFR-SEC-06 | Passwords SHALL be hashed with bcrypt; plaintext passwords SHALL never be persisted or logged. | `Hash::make()` in user service and profile forms; `BCRYPT_ROUNDS=12`; audit payloads unset `password`. | Enforced |
| NFR-SEC-07 | Logout SHALL invalidate the session and regenerate the CSRF token. | `app/Livewire/Actions/Logout.php`. | Enforced |
| NFR-SEC-08 | Login SHALL be rate limited to 5 attempts per username and IP. | `LoginForm::ensureIsNotRateLimited()`. | Enforced |
| NFR-SEC-09 | Credentials, API keys, and database passwords SHALL live only in `.env`; `.env` SHALL remain untracked. | `.gitignore`; `.env.example` contains placeholders only. | Enforced |
| NFR-SEC-10 | Public broadcast traffic SHALL carry no business data. | `OrderUpdated` has an empty payload and broadcasts on public channel `orders`. | Enforced |
| NFR-SEC-11 | Sensitive identifiers SHALL be non-sequential. | UUID primary keys on all application tables. | Enforced |
| NFR-SEC-12 | A security review SHALL run on any change touching auth, input handling, secrets, or infrastructure. | Process requirement (`AGENTS.md`, `WORKFLOW.md`). | Target |

### Security gaps observed in code

1. `Cashier::selectInvoice()` and `processCheckout()` resolve an invoice by ID without an explicit branch assertion. The queue listing is branch-scoped, but the mutation methods rely on the caller using that list.
2. Ticket and receipt routes check branch membership but not role; any authenticated user bound to the invoice's branch can open them.
3. Dashboard components (`Owner`, `Manager`, `Cashier`, `Designer`, `Production`) do not assert the role in `mount()`; role dispatch happens in `resources/views/dashboard.blade.php`. `BranchManagement`, `CategoryManagement`, and `MachineManagement` assert on mount; `UserManagement` and `MaterialManagement` do not.
4. `RoleMiddleware` is registered as the `role` alias but no route currently uses it.
5. `UserManagement::$mode` is a public, client-writable property with no `mount()` role guard; a manager can set `mode=owner` and bypass the manager restrictions.
6. `MaterialManagement::$branchId` is client-writable and has no server-side manager branch assertion; a manager can switch branches and can delete a global material used by other branches.
7. `Production::render()` queries `materials.branch_id`, a column that does not exist, so the waste tab fails to render.

---

## 2. Data Integrity

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-DATA-01 | Any operation that mutates more than one table SHALL run inside a single `DB::transaction()`. | Services: `OrderService`, `PaymentService`, `InventoryService`, `BranchService`, `UserService`, `MachineService`, `CustomerService`, `ProductCategoryService`. | Partial: material creation uses two service transactions; restore and the edit path are not transaction-wrapped |
| NFR-DATA-02 | A failed multi-table write SHALL leave no partial rows. | `MaterialManagementTest::test_material_creation_rolls_back_entirely_on_ledger_failure` exists but its assertions run after the throwing call, so it does not verify rollback. Material restore and edit are not transactional. | Partial (gap) |
| NFR-DATA-03 | Monetary values SHALL use `DECIMAL(15,2)` in storage and 2-decimal rounding at calculation boundaries. | Migrations; `PricingService` rounds subtotal, cost, and profit to 2 decimals. Reporting aggregates are cast to `int` and truncate fractions. | Partial |
| NFR-DATA-04 | Foreign keys SHALL enforce referential integrity, with explicit `nullOnDelete` or `cascadeOnDelete` behavior. | `database/migrations/2026_06_24_000000_create_pos_tables.php`. Exceptions: `invoices.customer_id`, `wasted_materials.material_id`, and `stock_ledgers.material_id` use the default NO ACTION/RESTRICT. | Partial |
| NFR-DATA-05 | Master data SHALL use soft deletes so historical transactions remain readable. | `customers`, `products`, `materials`, `machines`, `attributes`, `branches`, `users` use `SoftDeletes`. `product_categories`, `attribute_types`, and `pricing` do not; category deletion cascades to products, materials, and pricing, unless NO ACTION references block it. | Partial |
| NFR-DATA-06 | Transactional timestamps SHALL be timezone-aware. | `timestampTz` columns for invoice, order, expense, waste, and ledger dates. `payments` uses plain `timestamps()`, so `payments.created_at` is not timezone-aware. | Partial |
| NFR-DATA-07 | Stock SHALL never go below zero; a blocked or negative deduction SHALL be rejected or flagged. | No guard exists; `current_stock` may become negative. | Partial (gap) |
| NFR-DATA-08 | Invoice numbers SHALL be unique per branch and day under concurrent submission. | `invoice_number` has a unique constraint, but the sequence uses `count() + 1` without a lock; a concurrent insert can collide and fail the transaction. | Partial |
| NFR-DATA-09 | Payment operations SHALL be idempotent or reject duplicates. | No idempotency key or duplicate guard exists. | Target |
| NFR-DATA-10 | Status values SHALL match the documented codes. | Invoice `0/1/2`, pickup `0/1`, order `0/1/2`; see `docs/requirements/fr.md`. | Enforced |

---

## 3. Performance and Scalability

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-PERF-01 | A branch dashboard SHALL render within 2 seconds at p95 with 10,000 invoices and 50,000 payments per branch. | Load test not present; target baseline for staging. | Target |
| NFR-PERF-02 | A quote calculation SHALL complete within 200 ms at p95 for one pricing lookup plus modifiers. | `PricingService` performs several queries (product, category, pricing, machine, machine pricing, attribute pricing, optional branch); the filter columns are not explicitly indexed. No benchmark. | Target |
| NFR-PERF-03 | `OrderUpdated` SHALL reach connected clients within 1 second of the originating action. | Reverb broadcast; no latency measurement. | Target |
| NFR-PERF-04 | List screens SHALL bound result size through pagination or explicit limits. | Payment queue `limit(8)`, pickup queue unbounded but branch-scoped, audit paginated, materials `limit(20)`, machines paginated. | Partial |
| NFR-PERF-05 | Heavy dashboard regions SHALL be isolated with Livewire islands to limit DOM morphing. | `@island` in owner, cashier, production, manager views. No container queries are used. | Partial |
| NFR-PERF-06 | Branch and date filter columns used by reporting SHALL be indexed. | No explicit indexes beyond primary keys and unique constraints; PostgreSQL does not auto-index foreign keys. | Partial (gap) |

Scalability assumptions:
- The application is a single Laravel deployment per environment.
- Multi-branch tenancy is row-level (`branch_id`), not database-per-tenant.
- Reporting is computed on request; there is no pre-aggregation or warehouse.

---

## 4. Reliability and Availability

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-REL-01 | Domain exceptions SHALL be logged with `user_id`, `branch_id`, and relevant entity IDs before being rethrown or flashed. | `InventoryService`, `MachineService`, `ProductCategoryService`. | Partial (not every service logs) |
| NFR-REL-02 | Broadcasting failures SHALL NOT roll back a committed business transaction. | `ShouldBroadcastNow` runs after the service transaction; only some callers wrap dispatch in `try/catch`. | Partial |
| NFR-REL-03 | The application SHALL start from a clean checkout through `./init.sh`. | `init.sh` installs dependencies and runs the test suite before optional service start. The script requires an existing `local-php83` container; no compose file is tracked in this repository. | Partial |
| NFR-REL-04 | The database SHALL be recoverable from a logical backup. | No backup automation in the repository; operational responsibility. | Target |
| NFR-REL-05 | Real-time degradation SHALL be graceful: dashboards remain usable when Reverb is unavailable. | Livewire actions work without Echo, but `ShouldBroadcastNow` is called synchronously and several callers (`Cashier`, `Production`, `Designer`, `Manager::addMaterialStock`) do not wrap it in `try/catch`; a broadcast failure can surface after the database commit. | Partial |

---

## 5. Portability and Compatibility

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-PORT-01 | Production SHALL run on PHP 8.3 with PostgreSQL 14 or newer. | `composer.json` (`php: ^8.3`) and CI use PHP 8.3. PostgreSQL 14+ is a deployment prerequisite from the README; the repository default and CI use SQLite. | Partial |
| NFR-PORT-02 | The automated test suite SHALL run on SQLite in memory. | `phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`. | Enforced |
| NFR-PORT-03 | Migrations and queries SHALL be dialect-agnostic across PostgreSQL and SQLite. | CI runs the suite on SQLite; review rule in `AGENTS.md` §3.4.1. | Enforced (convention) |
| NFR-PORT-04 | Frontend assets SHALL build with Node.js 20 and Vite 8. | `.github/workflows/ci.yml`; `package.json`. | Enforced |
| NFR-PORT-05 | Primary keys SHALL be UUID strings, not auto-increment integers. | `HasUuids` on all application models; factories use UUIDs. | Enforced |
| NFR-PORT-06 | Theme configuration SHALL live in `resources/css/app.css` through Tailwind v4 `@theme`; `tailwind.config.js` SHALL remain an empty compatibility stub. | `tailwind.config.js` contains only a comment and `export default {}`. | Enforced |

---

## 6. Maintainability and Code Quality

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-MAINT-01 | PHP code SHALL follow PSR-12 and pass Laravel Pint. | CI runs `./vendor/bin/pint` and auto-commits formatting changes, so it fixes rather than rejects; Husky + lint-staged run Pint on commit. | Enforced (auto-fix, not a blocking gate) |
| NFR-MAINT-02 | Static analysis SHALL pass at the configured Larastan level. | CI: `phpstan analyse --memory-limit=1G`; `phpstan.neon`. | Enforced |
| NFR-MAINT-03 | New PHP class files SHALL declare `strict_types=1` and use explicit parameter and return types. | Current coverage: 36 of 80 files under `app/`. | Partial |
| NFR-MAINT-04 | Business logic SHALL live in `app/Services` and be injected into Livewire components or controllers. | Services directory; constructor/boot injection in admin components; some dashboards instantiate services directly. | Partial |
| NFR-MAINT-05 | Livewire components SHALL be class-based, with form objects for multi-input forms. | `app/Livewire/*`, `app/Livewire/Forms/*`. `UserManagement` uses individual public properties instead of a form object. | Partial |
| NFR-MAINT-06 | Blade views SHALL be decomposed into partials and anonymous components; main dashboard templates SHALL stay under 150 lines. | Role dashboards delegate to `partials/`; `dashboard.blade.php` is small. | Partial |
| NFR-MAINT-07 | Automated tests SHALL cover pricing, inventory, payments, branch scoping, authorization, and admin CRUD. | 22 feature test files (19 top-level plus 3 under `tests/Feature/Auth`) and 3 unit files. | Enforced |
| NFR-MAINT-08 | Dependencies SHALL NOT be added without explicit approval; Composer and npm lock files SHALL be committed together with manifests. | Process rule; `composer.lock` and `package-lock.json` are tracked. | Enforced (process) |
| NFR-MAINT-09 | Commits SHALL follow Conventional Commits. | Repository history and `AGENTS.md` §4.8. At least one historical commit is not conventional. | Target |

---

## 7. Usability and Accessibility

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-UX-01 | Every screen SHALL work in light and dark themes with readable contrast. | `dark:` variants across views; theme toggle persists in `localStorage`. | Enforced |
| NFR-UX-02 | Layouts SHALL remain usable at 390 px, 768 px, and 1440 px widths. | Responsive Tailwind utilities; no automated viewport test in the repository. | Target |
| NFR-UX-03 | Interactive controls SHALL have accessible names and keyboard focus states. | Labels and focus styles are present in forms; no WCAG audit has been recorded. | Target |
| NFR-UX-04 | Receipts SHALL print on 80 mm paper and tickets on standard A4/letter. | `resources/views/orders/receipt.blade.php` (`width: 80mm`), `ticket.blade.php`. | Enforced |
| NFR-UX-05 | Destructive and financial actions SHALL require explicit confirmation or validation feedback. | Checkout validation, delete confirmations, success alerts. | Partial |
| NFR-UX-06 | Copy SHALL be understandable to print-shop staff; internal code terms SHALL remain English. | `AGENTS.md` §2.3; UI mixes English labels with legacy Indonesian financial terms in places. | Partial |

---

## 8. Observability

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-OBS-01 | Business mutations SHALL be traceable through `activity_logs` and `order_status_logs`. | Models and services; owner audit tab. Material, product-category, attribute-type, pricing, attribute, invoice-update, and customer-update actions are not logged. | Partial |
| NFR-OBS-02 | Caught domain failures SHALL be logged at error level with context. | `Log::error()` in inventory, machine, and category services. | Partial |
| NFR-OBS-03 | Application logs, queue failures, and WebSocket connections SHALL be available during operations. | Laravel log stack; `php artisan pail`; Reverb console. No metrics or tracing. | Partial |
| NFR-OBS-04 | Uptime, latency, and error-rate monitoring SHALL exist in production. | Not configured in the repository. | Target |

---

## 9. Operability and Deployment

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-OPS-01 | The application SHALL run inside the project Docker Compose stack, with PHP commands executed in `local-php83`. | `AGENTS.md` §4.9.1; `init.sh`. No compose file is tracked in this repository. | Target (process requirement) |
| NFR-OPS-02 | Reverb SHALL listen on container port 8080 and be reachable on host port 8084. | `AGENTS.md` §4.6 and `init.sh` start Reverb on 8080. The 8084 mapping is an environment requirement; the README references 8080. No mapping is tracked in the repository. | Partial |
| NFR-OPS-03 | Production assets SHALL be compiled with `npm run build` before release. | CI build step; `AGENTS.md` §4.4. | Enforced |
| NFR-OPS-04 | CI SHALL run Pint, Composer audit, Larastan, asset build, and the test suite on every push and pull request to `main`. | `.github/workflows/ci.yml`. | Enforced |
| NFR-OPS-05 | Deployments SHALL use ordered optimization caching and signal-based Reverb reloads. | `AGENTS.md` §4.9.4; no deployment script in the repository. | Target |
| NFR-OPS-06 | Queue workers SHALL process background jobs with retry and timeout limits. | `composer dev` runs `queue:listen --tries=1 --timeout=0`, which means one attempt and no timeout. No production supervisor config is tracked. | Partial |

---

## 10. Privacy and Compliance

| ID | Requirement | Measure / verification | Status |
| :--- | :--- | :--- | :--- |
| NFR-PRIV-01 | Source code, seeds, and documentation SHALL use generic names and example addresses, never real customer data. | `MinimalUserSeeder` uses generic accounts; `AGENTS.md` §2.2.1. | Enforced |
| NFR-PRIV-02 | Secrets SHALL NOT be committed; `.env` and key material SHALL stay untracked. | `.gitignore`. | Enforced |
| NFR-PRIV-03 | Broadcast channels SHALL NOT expose customer, invoice, or payment payloads. | Empty `OrderUpdated` payload. | Enforced |
| NFR-PRIV-04 | Soft-deleted personal data SHALL have a retention or purge policy. | No retention policy in the repository. | Target |
| NFR-PRIV-05 | Audit payloads SHALL exclude secrets and password fields. | `UserService` unsets `password` before writing payloads. | Enforced |

### Additional data-integrity gaps

1. Material creation uses two service transactions; restore and the edit path are not transaction-wrapped. A failure after the first write can leave the catalog and stock out of sync.
2. The cited rollback test does not execute its assertions, so the rollback behavior is unverified.
3. `product_categories` deletion is a hard delete with cascading effects on products, materials, and pricing, unless NO ACTION references from `wasted_materials` or `stock_ledgers` block it.
4. `pricing` deletion is a hard delete; `orders.pricing_id` is nulled, so the historical quote link is lost.
5. `Production::render()` queries a non-existent `materials.branch_id`, so the waste tab cannot load.

---

## 11. Verification Commands

| Check | Command |
| :--- | :--- |
| Full test suite | `docker exec -it -w /var/www/html/pos_alja local-php83 env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test` |
| Style | `./vendor/bin/pint` |
| Static analysis | `./vendor/bin/phpstan analyse --memory-limit=1G` |
| Dependency audit | `composer audit` |
| Asset build | `npm run build` |
| Startup path | `./init.sh` |
