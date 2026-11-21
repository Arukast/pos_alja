# API Contracts

- **Status:** Implemented external API consumed by the React SPA.
- **Base path:** `/api/v1`.
- **Authentication:** Laravel Sanctum stateful cookie plus CSRF. No bearer tokens are issued by the SPA.
- **Format:** JSON only. Keys are `snake_case`.

## 1. Conventions

| Topic | Convention |
| :--- | :--- |
| Authentication | `GET /sanctum/csrf-cookie`, then `POST /api/v1/auth/login`. Session cookie is sent on subsequent requests. |
| CSRF | Send `X-XSRF-TOKEN` on unsafe methods. The SPA obtains it from the CSRF cookie. |
| IDs | UUID strings. |
| Money | Decimal strings with two places, e.g. `"125000.00"`. |
| Dates | `Y-m-d` for filters and date fields; ISO-8601 for timestamps. |
| Pagination | `?page=`; response includes `data`, `links`, and `meta` (`current_page`, `last_page`, `per_page`, `total`). |
| Errors | `{ "message": "...", "errors": { "field": ["..."] } }` with 401, 403, 404, 409, 419, 422, or 429. |
| Language | All user-facing messages are Indonesian. Field names, route paths, enum values, and action codes remain English. |
| Error rendering | Framework-generated 401/403/404/500 on `/api/*` and `/broadcasting/*` render as `{"message": "..."}` in Indonesian. App-authored `abort()` messages with explicit text are preserved. 500 hides internals unless `APP_DEBUG=true`. 422 keeps `message` plus `errors`; 419 and 429 keep default rendering; non-JSON requests keep default rendering. |
| Branch scope | Derived from the authenticated user. Owner may use `branch_id=all` in reporting. |
| Rate limits | Named `api` limiter: 60/min per user or IP. `sensitive` limiter: 5/min on password routes. Login is 5 attempts per username + IP. |
| Broadcast auth | `POST /broadcasting/auth` with Sanctum session; private channels only. |

## 2. Authentication

| Method | Path | Access | Purpose |
| :--- | :--- | :--- | :--- |
| POST | `/auth/login` | guest | Username, password, remember. 422 on failure, 429 when throttled. |
| POST | `/auth/logout` | authenticated | Invalidate session. 204. |
| GET | `/auth/me` | authenticated | User, role, branch. |
| PUT | `/auth/profile` | owner, manager | Name and email. |
| PUT | `/auth/password` | authenticated | Current password plus confirmed new password. 204. |
| POST | `/auth/confirm-password` | authenticated | Validate current password and record confirmation time. 204. |
| DELETE | `/auth/profile` | owner, manager | Current password required; soft delete and logout. 204. |

`UserResource` never returns `password` or `remember_token`.

## 3. Identity

### Branches

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/branches` | owner |
| POST | `/branches` | owner |
| GET | `/branches/{branch}` | owner |
| PUT | `/branches/{branch}` | owner |
| PATCH | `/branches/{branch}/status` | owner |
| DELETE | `/branches/{branch}` | owner |

### Users

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/users` | owner all; manager own branch staff |
| POST | `/users` | owner; manager creates cashier/designer/production in own branch |
| GET | `/users/{user}` | owner; manager own branch staff |
| PUT | `/users/{user}` | same as show |
| DELETE | `/users/{user}` | same as show |
| POST | `/users/{user}/restore` | same as show |

Managers cannot create or manage owner/manager accounts or assign another branch. Passwords are never included in audit payloads.

## 4. Catalog And Inventory

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/categories` | owner, manager |
| POST/PUT/DELETE | `/categories`, `/categories/{category}` | owner, manager |
| GET | `/attribute-types` | owner, manager |
| POST/DELETE | `/attribute-types`, `/attribute-types/{attributeType}` | owner, manager |
| GET | `/attributes` | owner all; manager own branch/global |
| POST/PUT/DELETE | `/attributes`, `/attributes/{attribute}` | owner, manager |
| GET | `/products` | owner, manager, cashier, designer |
| GET | `/materials` | owner all; manager/production own branch |
| POST | `/materials` | owner; manager (own branch stock only) |
| PUT | `/materials/{material}` | owner |
| DELETE | `/materials/{material}` | owner |
| POST | `/materials/{material}/restore` | owner |
| POST | `/materials/{material}/stock` | owner, manager (manager branch forced) |
| POST | `/materials/{material}/activate` | owner, manager |
| GET | `/materials/similar?name=` | owner, manager |
| GET | `/machines` | owner all; manager own branch |
| POST/PUT/DELETE | `/machines`, `/machines/{machine}` | owner; manager own branch |
| POST | `/machines/{machine}/restore` | owner; manager own branch |
| GET | `/pricing` | owner all; manager own branch |
| POST/PUT/DELETE | `/pricing`, `/pricing/{pricing}` | owner; manager own branch |
| POST | `/pricing/clone-tiers` | owner; manager own branch |
| GET | `/stock-ledgers` | owner all; manager own branch |

Material responses include `current_stock`, `min_stock`, and `stocks` per branch for owner/manager. Production material responses omit `cost`, `price`, and per-branch price data.

## 5. Sales

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/customers?search=` | owner, manager, cashier, designer |
| POST | `/customers` | designer |
| GET | `/quotes/options` | designer |
| POST | `/quotes` | designer |
| POST | `/invoices` | designer with a branch |
| GET | `/invoices` | all roles, branch-scoped |
| GET | `/invoices/{invoice}` | all roles, branch-scoped |
| POST | `/invoices/{invoice}/down-payments` | cashier |
| POST | `/invoices/{invoice}/settlements` | cashier |
| POST | `/invoices/{invoice}/pickup` | cashier |
| GET | `/invoices/{invoice}/ticket` | all roles, branch-scoped; no money |
| GET | `/invoices/{invoice}/receipt` | owner, manager, cashier, designer |
| GET | `/expenses` | owner all; manager/cashier own branch |
| POST | `/expenses` | cashier |

### Quote And Invoice Input

`POST /quotes` accepts `product_id`, `material_id`, `machine_id?`, `selected_attributes?`, `quantity`, `width?`, `length?`, and `customer_type?`. It returns `pricing_id`, `unit_price`, `area`, `quantity`, and `subtotal`; cost and profit are not returned to the designer.

`POST /invoices` accepts `customer_id`, `discount?`, `deadline?`, `source?` (`Offline`/`Online`), and `items[]` with `pricing_id`, `file_name?`, `notes?`, `width?`, `length?`, `quantity`, `machine_id?`, `selected_attributes?`, `is_manual_price`, `unit_price?`, and `cost?`.

Server rules:

- Line prices are recomputed from the pricing matrix; client prices are ignored unless `is_manual_price` is true.
- Manual lines require both `unit_price` and `cost`, must be non-negative, and `cost` must not exceed `unit_price`.
- Machine ids must belong to the designer branch.
- Discount cannot exceed the computed subtotal.
- Invoice numbers use the unique branch code and a per-branch daily sequence.
- Creation and manual-price audit entries are atomic.

### Payment Rules

- Down payments cannot exceed the receivable.
- Settlement amount is computed server-side as `receivable - discount`.
- The invoice row is locked during payment and pickup.
- A paid invoice has `status=2` and zero receivable.
- Pickup requires a paid invoice and is branch-scoped.

## 6. Production

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/production/queue` | all roles, branch-scoped |
| GET | `/production/summary` | production |
| POST | `/orders/{order}/start` | production, branch-scoped |
| POST | `/orders/{order}/complete` | production, branch-scoped |
| GET | `/waste` | production, branch-scoped |
| POST | `/waste` | production, branch-scoped |
| GET | `/waste/options` | production, branch-scoped |

Starting an order deducts material stock once, sets status 1, writes a status log, and broadcasts. Completing requires status 1 and sets status 2. Waste requires a material with stock in the branch; optional machine, designer, and order must belong to the branch.

Production responses omit all money fields. Quantities are numeric.

## 7. Reporting And Audit

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/dashboard` | all roles; role-shaped payload |
| GET | `/reports/gross-revenue` | owner |
| GET | `/reports/base-cost` | owner |
| GET | `/reports/net-profit` | owner |
| GET | `/reports/expenses` | owner |
| GET | `/reports/sparkline?period=daily|weekly|monthly` | owner |
| GET | `/reports/designer-leaderboard` | manager |
| GET | `/audit-logs` | owner |
| GET | `/audit-logs/{activityLog}` | owner |

Report filters: `branch_id`, `start_date`, `end_date`. `end_date` must be on or after `start_date`. Owner net profit subtracts invoice HPP, wasted material cost, and operating expenses.

Audit logs support `search`, `sort` (`created_at`, `username`, `role`, `action`), `direction`, and pagination.

## 8. Realtime

| Item | Value |
| :--- | :--- |
| Event | `App\Events\OrderUpdated` with optional `branchId`. |
| Branch channel | `private-branch.{branchId}` |
| Owner channel | `private-owners` |
| Payload | Empty; clients refetch authenticated endpoints. |
| Authorization | Owner may join any branch and the owners channel. Branch users may join only their own branch. |
| Dispatch | After every successful non-GET API request, and after transaction commit in the write Actions. |
| Failure handling | `App\Support\SafeBroadcast` logs broadcaster failures; it does not fail the write. |

The SPA subscribes to `owners` for owners and `branch.{id}` for branch users, then invalidates all relevant queries on `OrderUpdated`.

## 9. Frontend Routes

The SPA routes and role guards are documented in `docs/architecture/frontend-architecture.md`. API clients must send relative `/api`, `/sanctum`, and `/broadcasting` requests so the Vite proxy and Nginx keep cookies same-origin.
