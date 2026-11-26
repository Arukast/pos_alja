# API Contracts

- **Status:** Target contract after Sprint Review 1. Implemented endpoints are tracked in `docs/requirements/implementation-gaps.md`.
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
| Error rendering | Framework-generated 401/403/404/500 on `/api/*` and `/broadcasting/*` render as `{"message": "..."}` in Indonesian. App-authored `abort()` messages with explicit **401/403** text are preserved; 404 route/model errors use the fixed message. 500 hides internals unless `APP_DEBUG=true`. 422 keeps `message` plus `errors`; 419 and 429 keep default rendering; non-JSON requests keep default rendering. |
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

Branch create/update accept `reseller_discount_percent` (`0–100`, default `0`) and `settings`; the column and `settings.reseller_discount_percent` are kept in sync and read by pricing.

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
| POST/PUT | `/categories`, `/categories/{category}` | owner |
| GET | `/attributes` | owner, manager |
| POST/PUT | `/attributes`, `/attributes/{attribute}` | owner or manager (manager branch forced when omitted; a different branch is 403; branch immutable after creation) |
| GET | `/products` | owner, manager, cashier, designer |
| POST/PUT/DELETE | `/products`, `/products/{product}` | owner |
| POST | `/products/{product}/restore` | owner |
| GET | `/materials` | owner all; manager/production own branch |
| POST | `/materials` | owner; manager creates for own branch |
| PUT | `/materials/{material}` | owner |
| DELETE | `/materials/{material}` | owner |
| POST | `/materials/{material}/restore` | owner |
| POST | `/materials/{material}/stock` | owner, manager (manager branch forced) |
| POST | `/materials/{material}/adjustments` | owner, manager (manager branch forced) |
| POST | `/materials/{material}/activate` | owner, manager (branch-stock activation) |
| PATCH | `/materials/{material}/min-stock` | owner names the branch; manager own branch (other branch 403) |
| GET | `/materials/similar?name=` | owner, manager |
| GET | `/pricing` | owner all; manager own branch |
| POST | `/pricing` | owner; manager own branch (branch forced) |
| PUT/DELETE | `/pricing/{pricing}` | owner; manager own branch |
| POST | `/pricing/clone-tiers` | owner; manager own branch (branch forced) |
| GET | `/stock-ledgers` | owner all; manager own branch |
| GET | `/purchases` | owner all; manager own branch |
| POST | `/purchases` | owner, manager (manager branch forced) |

Categories accept `name`, `description` (nullable, max 255), and `is_active`; `GET /categories` also returns the derived `products_count` (soft-deleted products excluded), while `POST/PUT` return `{id,name,description,is_active}` only. `calculation_archetype` and `allowed_attribute_types` are omitted from category requests and `CategoryResource`. `DELETE /categories/{category}` is removed and returns 404. `DELETE /attributes/{attribute}` is removed and returns 404. Products accept `name`, `product_category_id`, `pricing_type` (`unit` or `area`), `is_active`, and `material_ids[]?` (UUIDs of compatible non-deleted materials); `ProductResource` returns `materials[]` (`{id,name}`) and `material_ids[]`, and pricing formulas use `product.pricing_type`. Materials accept `name`, `product_category_id`, `type`, `dimensions`, `unit`, `cost`, `price`, `initial_stock`, and `min_stock`. `unit` is required on create and optional on update; allowed values are exactly `m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, and `batang`. The column defaults to `pcs`, and existing rows are backfilled; `m2` displays as `m²`. Attributes accept `name`, `type` (`finishing` or `display`), `branch_id` (create only; immutable after creation), `price`, `cost`, and `is_active`. A manager omitting `branch_id` is forced to the manager's own branch; an explicit different branch returns 403. A manager may create a material; the action initializes `material_branch_stock` only for the manager's branch. A manager without a branch creates the material with no branch stock row.

Material responses include `unit`, `current_stock`, `min_stock`, and `stocks` per branch for owner/manager. Production material responses omit `cost`, `price`, and per-branch price data, but include `unit`, `current_stock`, `min_stock`, and `is_low_stock`. The pricing-matrix endpoints (`/pricing`) remain. `POST /pricing` requires `product_id`, `material_id`, `customer_type`, `min_qty`, `price`, and `cost`; `profit` is optional and defaults to `max(0, price - cost)`. Managers are forced to their own branch for list, write, delete, and clone operations; owners may select a branch. `DELETE /pricing/{pricing}` is a soft delete with no restore endpoint; quote calculation and the `/pricing` list ignore soft-deleted pricing rows.

`GET /materials?low_stock=1` returns only materials whose resolved branch stock has `current_stock <= min_stock`. For an owner with `branch_id=all`, the filter uses OR semantics: a material is returned when at least one branch is low. In that case the top-level `is_low_stock` is always null, even when the response loads one stock row, and each `stocks[]` row carries its own boolean `is_low_stock`. For a single resolved branch the top-level `is_low_stock` is a boolean. Each `stocks[]` row always carries its own boolean. Manager and production are forced to their own branch. The filtered list is paginated like the normal list.

Stock adjustment accepts exactly one of `new_quantity` (0-99999999.99) or `delta` (non-zero signed value, -99999999.99 to 99999999.99), plus `reason` (3-255 characters). `branch_id` is required for an owner and forced from the manager's account. It writes a `stock_ledgers` row of type `adjustment` with the reason and actor, and an activity log entry. The resulting stock must stay between 0 and 99999999.99; a negative result, a zero delta, both or neither mode, or a value over the cap is 422. The response is `{ material: MaterialResource }` with the updated stock, not the ledger row. For an owner adjusting one branch, the updated value appears in `stocks[]`; the top-level `is_low_stock` may be null when several branch rows are loaded. `POST /materials/{material}/stock` remains for a quick positive restock and writes ledger type `restock`.

`PATCH /materials/{material}/min-stock` accepts `min_stock` (0-99999999.99, at most two decimals) and `branch_id` (a UUID). The owner must name the branch; a manager omitting it is forced to the manager's own branch, an explicit different branch returns 403, and a manager without a branch is denied. It updates only `material_branch_stock.min_stock` on an existing row; a missing branch-stock row returns 422 on `branch_id` and is not created. The action writes no `stock_ledgers` row and no activity log, and `current_stock` is unchanged. The response is `{ material: MaterialResource }` with the updated threshold. For an owner whose response loads multiple branch rows, the top-level `min_stock` is `null` and the updated value appears in `stocks[]`; the manager response resolves only the own-branch row and exposes its `min_stock` at the top level.

### Purchase Rules

- `GET /purchases` is paginated (15 rows) and ordered by `purchased_at` descending, then `created_at` descending. An owner omitting `branch_id` (or sending `all`) gets all branches. A manager is forced to their own branch. Cashier, designer, and production get 403; a manager with no branch also gets 403.
- `POST /purchases` requires `idempotency_key` (UUID) and `items` (1-50 lines). `branch_id` is required for an owner and forced for a manager. `purchased_at` is date-only (`Y-m-d`), optional and not after today; any other format, including an ISO timestamp, returns 422. `supplier` is optional max 255; `notes` optional max 1000. Each line has `material_id` (active, non-deleted material), `quantity` 0.01-999999.99, and `unit_cost` 0-9999999.99.
- The server computes each line `subtotal` with integer-cent arithmetic, rounds every line to two decimals, and sums the rounded lines into `total_amount`. Client subtotals and totals are ignored.
- One transaction creates the purchase and items, increments branch stock, writes one `stock_ledgers` row per line (`type=purchase`, `reference_type=purchase`, `reference_id`, actor), and writes an activity log entry. A missing branch stock row is created with `min_stock=0`. Duplicate material lines are allowed as separate lines and each increments stock.
- The unique key is `(branch_id, idempotency_key)`. A replay in the resolved branch returns the stored purchase with 200 and no stock or ledger change, even when the payload differs, provided the new payload still validates; an invalid payload returns 422 before the replay lookup. A new purchase returns 201. The replay lookup is branch-scoped, so a manager cannot read another branch's key.
- The response is `{ purchase: PurchaseResource }`. Fields: `id`, `branch {id,name}`, `user {id,name}`, `purchased_at` (`Y-m-d`), `supplier`, `total_amount`, `notes`, `items[]` (`id`, `material {id,name,unit}`, `quantity`, `unit_cost`, `subtotal`), and `created_at`. Purchase history keeps the name and unit of a soft-deleted material.
- There is no purchase detail, update, or delete route. Purchases are immutable after creation and there is no reversal flow.
- Over-cap values, a soft-deleted material, or an invalid payload return 422.

### Stock Ledger Rules

- `GET /stock-ledgers` is owner all; manager own branch.
- `StockLedgerResource` returns `id`, `branch_id`, `branch`, `material {id,name,unit}`, `type`, `reason`, `user {id,name}`, `reference_type`, `reference_id`, `logged_at`, `initial_stock`, `added_stock`, `used_stock`, `wasted_stock`, `remaining_stock`, and `notes`.
- `type` is one of `initial`, `purchase`, `usage`, `usage_reversal`, `waste`, `adjustment`, or `restock`. Legacy rows may have a null `type`, `user`, and reference. `usage_reversal` is written when a failed processing order returns its net usage; it references the order and records `added_stock`.

`GET /products` uses server-side pagination at 10 products per page (`meta.per_page = 10`) and supports `search` (case-insensitive match on product name and category name), `category_id`, `is_active` (`1`/`0`), and `include_deleted` (`owner` only). `GET /quotes/options` returns active categories only; active products in an active or null category; materials in an active category; and active attributes unless `include_inactive_attributes=1` (still branch-scoped). Its categories omit `products_count`, and product/material `category` objects include `is_active`. `GET /categories` includes `description`, `is_active`, and the derived `products_count` (soft-deleted products excluded).

## 5. Sales

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/customers?search=` | owner, manager, cashier, designer |
| POST | `/customers` | owner, manager, designer |
| PUT | `/customers/{customer}` | owner, manager, designer |
| GET | `/quotes/options` | designer |
| POST | `/quotes` | designer |
| POST | `/invoices` | designer with a branch |
| PUT | `/invoices/{invoice}/items` | designer, branch-scoped; only before production and not fully paid |
| GET | `/invoices` | all roles, branch-scoped |
| GET | `/invoices/{invoice}` | all roles, branch-scoped |
| GET | `/invoices/{invoice}/print` | owner, manager, cashier, designer; branch-scoped |
| POST | `/invoices/{invoice}/down-payments` | cashier |
| POST | `/invoices/{invoice}/settlements` | cashier |
| POST | `/invoices/{invoice}/pickup` | cashier |
| GET | `/invoices/{invoice}/receipt` | owner, manager, cashier, designer; branch-scoped |
| PATCH | `/orders/{order}/design-status` | designer own branch; owner all; manager own branch override |
| GET | `/expenses` | owner all; manager own branch |
| POST | `/expenses` | owner, manager |

### Customers

- `GET /customers?search=` returns customer records including `name`, optional `phone`, `email`, `address`, required `institution`, and the pricing `type`.
- `POST /customers` requires `name` and `institution` (`Masyarakat/Retail`, `Sekolah`, `Universitas`, or `Pemerintahan`). Phone, email, and address are optional.

### Quote And Invoice Input

`POST /quotes` accepts `product_id`, `material_id`, `selected_attributes[]?`, `quantity`, `width?`, `length?`, and `customer_type?`. It returns `pricing_id`, `unit_price`, `area`, `quantity`, and `subtotal`; `unit_price` comes from the pricing matrix plus `attribute_pricing` modifiers. Cost and profit are not returned to the designer. `customer_type` defaults to `Reguler`. `GET /quotes/options` returns categories, products, materials, and finishing/display attributes, filtered to active catalog entries; pass `include_inactive_attributes=1` to keep inactive attributes visible in the designer edit page.

`POST /invoices` accepts `customer_id`, `discount?`, `deadline?`, `source?` (`Offline`/`Online`), and `items[]` with `pricing_id`, `file_name?`, `notes?`, `width?`, `length?`, `quantity`, `selected_attributes[]?`, `is_manual_price`, `unit_price?`, and `cost?`. Order rows persist `width`, `length`, and `selected_attributes` so later edits can reprice from the saved specifications.

`PUT /invoices/{invoice}/items` (designer, own branch) replaces the desired item list. Each update item accepts `id?` for an existing order, `pricing_id`, `file_name?`, `notes?`, `width?`, `length?`, `quantity`, and `selected_attributes[]?`. The action also accepts an optional `deadline`; when the key is absent the stored deadline is preserved, while an explicit `null` clears it.

`GET /invoices/{invoice}` includes each order's `pricing` object with `product` name, `material` name, and `pricing_type` (`unit` or `area`) for non-production roles. Names resolve through soft-deleted `pricing`, `product`, and `material` rows so invoice history stays readable. Production responses load the orders without pricing.

Server rules:

- Base price is recomputed from the selected `pricing` row plus `attribute_pricing` modifiers; client prices are ignored unless `is_manual_price` is true.
- Invoice creation reads the customer type from the customer record. The branch reseller discount is applied only when a Reseller quote falls back to the Reguler pricing row.
- Manual lines require both `unit_price` and `cost`; `unit_price` must be positive, `cost` must be non-negative, and `cost` must not exceed `unit_price`.
- `selected_attributes[]` adds `attribute_pricing` modifiers to the pricing-matrix row. The selection is persisted on `orders.selected_attributes`; there is no `order_attributes` snapshot table.
- Discount cannot exceed the computed subtotal.
- Invoice numbers use the unique branch code and a per-branch daily sequence.
- Invoice creation and item updates lock the invoice and its order rows in a transaction. Item updates recalculate line quotes and invoice totals on the server, preserve payment rows, and audit before/after totals and item IDs.
- An invoice item update is rejected with 409 when any order is already in production, the invoice is fully paid, or any existing order has `is_manual_price` set; manual lines are never repriced at list price.
- An invoice item update is rejected with 422 when the discount exceeds the new subtotal or the new total is less than the amount already paid.
- Production may read the invoice detail only with money fields redacted; invoice and receipt print are denied.

Customer input requires `name` and `institution`; `phone`, `email`, `address`, and `type` are optional. `type` defaults to `Reguler` and accepts `Reguler` or `Reseller`. Institutions: `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`.

### Payment Rules

- Down payments cannot exceed the receivable.
- Settlement amount is computed server-side as `receivable - discount`.
- The invoice row is locked during payment and pickup.
- A paid invoice has `status=2` and zero receivable.
- Pickup requires a paid invoice and is branch-scoped.

### Expense Rules

- Only Owner and Branch Manager may create expenses. Cashier access is removed.
- A manager writes to their own branch. An owner must send `branch_id`.
- `category` must be one of: `Bahan Baku`, `Gaji`, `Sewa`, `Listrik & Air`, `Maintenance`, `Transport`, `Marketing`, `Operasional`, `Lain-lain`.
- The response includes `recorded_by_name`, not a cashier name.

## 6. Production

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/production/queue?branch_id=` | all roles, branch-scoped |
| GET | `/production/summary` | production |
| POST | `/orders/{order}/start` | production, branch-scoped |
| POST | `/orders/{order}/complete` | production, branch-scoped |
| POST | `/orders/{order}/fail` | production, branch-scoped |
| POST | `/orders/{order}/reopen` | production, branch-scoped |
| GET | `/waste` | production, branch-scoped |
| POST | `/waste` | production, branch-scoped |
| GET | `/waste/options` | production, branch-scoped |

The queue returns statuses `0`, `1`, and `3` (Gagal), accepts an optional `status` (`0`, `1`, or `3`; any other value returns 422) and an optional `search` (matched against the invoice number, file name, or exact order UUID), and for owner filters by `branch_id`. A non-UUID `search` value never touches the UUID `id` column. Each item includes `has_waste`, which is `true` when at least one `wasted_materials` row references the order and is eager-loaded so the queue does not run per-row queries. It currently orders by `created_at`, then `id`; deadline/priority sorting is not implemented. Starting an order requires status 0 and deducts material stock once by unit: `m2` deducts `area * quantity` and 422s when `area <= 0`; every other unit deducts `quantity`. It sets status 1, writes a status log, and broadcasts. Completing requires status 1 and sets status 2.

`GET /production/summary` is production-only and branch-scoped. It returns `queued`, `processing`, `completed_today`, `failed` (orders currently in status `3`), and `waste_today_by_unit`, an array of `{ unit, quantity }` for today grouped by material unit, including soft-deleted materials. The old flat waste number no longer exists.

`POST /orders/{order}/fail` requires `note` (3–255 characters) and status `1`; any other status returns 422. It reverses the net usage per `(material_id, branch_id)` (`usage` minus `usage_reversal`), writes one `usage_reversal` row for each material with positive net usage, sets status `3`, keeps the operator, writes the status log with the note verbatim, and broadcasts. It returns 200 `{ order }`. `POST /orders/{order}/reopen` takes no body and requires status `3`; any other status returns 422. It sets status `0`, clears the operator, writes the status log note `Production reopened`, and broadcasts. It returns 200 `{ order }`. Both are production-only and branch-scoped: guest 401, wrong role or other branch 403.

Waste is recorded from the queue row and requires `order_id`; the order must belong to the operator's branch and have status `0`, `1`, or `3`. A completed order (status `2`) returns 422. `material_id` must have a branch stock row, `quantity` must be positive, at most two decimals, and at most 99999999.99, `dimensions` and `notes` are optional, and `designer_id` is optional (a designer in the same branch). Waste writes a `stock_ledgers` row of type `waste` with the order reference and actor, and may push stock negative but not below -99999999.99. `GET /waste` returns paginated history; `WasteResource` has `id`, `branch`, `material {id,name,unit}`, `order_id`, `designer`, `operator`, `quantity`, `dimensions`, `notes`, and `logged_at`. `GET /waste/options` returns branch materials and branch designers only. The SPA records waste from the queue row; `/production/waste` is read-only history.

Production responses omit all money fields. Quantities are numeric. The queue is the production work list; there is no separate ticket endpoint.

### Design Status

`PATCH /orders/{order}/design-status` accepts `status` (`0`, `1`, `2`). Allowed transitions are `pending -> in_design -> approved`; Designer may only move forward. Owner and Branch Manager may override a backward transition. Approval stores `design_approved_at` and `design_approved_by`, writes the status and audit logs, and broadcasts `OrderUpdated`.

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

Report filters: `branch_id`, `start_date`, `end_date`. `end_date` must be on or after `start_date`. Owner net profit subtracts invoice HPP, wasted material cost, and operating expenses. The production dashboard payload returns `queued`, `processing`, `completed_today`, `waste_today_by_unit`, and the queue; `failed` is returned by `/production/summary`.

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
