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
| Errors | `{ "message": "...", "errors": { "field": ["..."] } }` with 401, 403, 404, 409, 410, 419, 422, or 429. |
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
| DELETE | `/auth/profile` | owner, manager | Current password required; sets `is_active = false`, then logs out and invalidates the session. 204. 422 when the owner is the last active owner. |

`UserResource` never returns `password` or `remember_token`.

Profile deactivation writes an `UPDATE` audit row with description `Menonaktifkan akun sendiri: {username}` and before/after `is_active`.

Login returns 422 with the generic `username` error (`auth.failed`) for invalid credentials, an inactive user, and a non-owner whose assigned branch is inactive, trashed, or missing. Owners skip the branch check; a non-owner with no branch keeps the previous behavior (login succeeds, and branch-scoped actions keep their documented 403/422/empty results).

`EnsureAccountIsActive` runs after `auth:sanctum` on every authenticated `/api/v1` route. An inactive user, or a non-owner whose assigned branch is inactive, trashed, or missing, gets `401` `{ "message": "Akun atau cabang Anda tidak aktif. Silakan hubungi pemilik." }` after the web guard logs out and the session and CSRF token are invalidated. Open Reverb sockets are not revoked; the next authenticated `/api/v1` request rejects the session, and broadcasts carry no money data. `/broadcasting/auth` is outside the `active` middleware, so a deactivated session can still re-authorize a private channel until its next authenticated `/api/v1` request.

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

`PATCH /branches/{branch}/status` toggles `is_active` and writes an `UPDATE` audit row with before/after values; it is the normal SPA toggle. Branch store/update still accept `is_active` (`sometimes`, boolean), but the SPA omits it, so create defaults active and update preserves the current state. Soft-deleted branches are converted to visible inactive rows by `2026_11_26_140000_add_is_active_to_users_and_convert_trashed_branches.php`. `DELETE /branches/{branch}` remains an administrative escape hatch with no restore route.

### Users

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/users` | owner all; manager own branch staff |
| POST | `/users` | owner; manager creates cashier/designer/production in own branch |
| GET | `/users/{user}` | owner; manager own branch staff |
| PUT | `/users/{user}` | same as show |
| PATCH | `/users/{user}/status` | owner any user except self; manager own-branch cashier/designer/production |
| DELETE | `/users/{user}` | owner (administrative; no SPA action) |
| POST | `/users/{user}/restore` | owner (administrative; no SPA action) |

`UserResource` includes `is_active` (boolean, default `true`). Store and update requests do not accept `is_active`; the status route, profile deactivation, and user restore change it. `branch_id` must reference a non-trashed branch.

`PATCH /users/{user}/status` toggles `is_active` and writes an `UPDATE` audit row with `before`/`after` `is_active`. An owner cannot toggle their own status (`422` on `is_active`); a manager targeting themselves is denied `403` by the policy before the service runs. The status route cannot emit the last-active-owner guard: a sole owner toggling themselves hits the self-toggle rule, and any other target implies a second active owner. The shared guard blocks profile deactivation (`is_active` key), `DELETE /users/{user}` (`user` key), and demotion through `PUT /users/{user}` (`role` key) with `Pemilik aktif terakhir tidak dapat dinonaktifkan, dihapus, atau diturunkan perannya.`, inside the caller's transaction with `lockForUpdate()`, and is tested at service level. `DELETE` and restore are owner-only administrative APIs with no normal UI; restore also sets `is_active = true`. Managers cannot create or manage owner/manager accounts or assign another branch. Passwords are never included in audit payloads.

## 4. Catalog And Inventory

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/categories` | owner, manager |
| POST/PUT | `/categories`, `/categories/{category}` | owner |
| GET | `/attribute-types` | owner, manager |
| POST | `/attribute-types` | owner |
| PUT | `/attribute-types/{type}` (slug-bound) | owner |
| DELETE | `/attribute-types/{type}` (slug-bound) | none (404 tombstone; the row is kept) |
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
| PATCH | `/materials/{material}/active` | owner (global active toggle) |
| PATCH | `/materials/{material}/branch-availability` | owner any branch; manager own branch (other branch 403; branchless manager denied) |
| GET | `/materials/similar?name=` | owner, manager |
| GET | `/pricing` | owner all; manager own branch |
| POST | `/pricing` | owner; manager own branch (branch forced) |
| PUT/DELETE | `/pricing/{pricing}` | owner; manager own branch |
| POST | `/pricing/clone-tiers` | owner; manager own branch (branch forced) |
| GET | `/stock-ledgers` | owner all; manager own branch |
| GET | `/purchases` | owner all; manager own branch |
| POST | `/purchases` | owner, manager (manager branch forced) |

Categories accept `name`, `description` (nullable, max 255), and `is_active`; `GET /categories` also returns the derived `products_count` (soft-deleted products excluded), while `POST/PUT` return `{id,name,description,is_active}` only. `calculation_archetype` and `allowed_attribute_types` are omitted from category requests and `CategoryResource`. `DELETE /categories/{category}` is removed and returns 404. `DELETE /attributes/{attribute}` is removed and returns 404; `DELETE /attribute-types/{type}` is a 404 tombstone and the row is kept. Attribute types accept `name` (required, max 100, unique), `sort_order` (optional nullable integer, default `max(sort_order) + 1`), and `is_active` (optional boolean, default true). `slug` is generated from the name on create (lowercase, non-empty, unique with a numeric suffix on collision, capped at 100 characters) and is immutable; it is the route key. `GET /attribute-types` returns `{data:[...]}` ordered by `sort_order`, then `name`; `POST` returns `{attribute_type:{...}}` with 201 and `PUT` returns the updated type. `AttributeTypeResource` returns `id`, `name`, `slug`, `sort_order`, and `is_active`. Products accept `name`, `product_category_id`, `selling_unit` (`m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, `batang`), `is_active`, and `material_ids[]?` (UUIDs of compatible non-deleted materials), plus `requires_material` and `uses_attributes` (`sometimes`, boolean, both defaulting to true) and `attribute_type_ids[]?`/`attribute_ids[]?` (UUIDs, `distinct`; types must exist, attributes must exist and not be soft-deleted). `selling_unit` is validated with `Rule::in(MaterialUnit::VALUES)`; the stored column defaults to `pcs`, but a create request must send `selling_unit` and a missing key returns 422. This is a hard API break: a client sending only the legacy `pricing_type` key is ignored, so a create without `selling_unit` is 422. New type IDs must reference active `attribute_types`; new attributes must be active, belong to an active type, and be inside the final allowed types. IDs already mapped to the product stay valid on update even when now inactive. The two B2 pivot ID arrays (`attribute_type_ids`, `attribute_ids`) are synced to `attribute_type_product`/`attribute_product`, alongside the existing `material_ids`, in the same transaction as the product row, and writes are Owner-only (Manager gets 403). `ProductResource` returns `materials[]` (`{id,name}`), `material_ids[]`, `attribute_types[]` (`{id,name}`), `attribute_type_ids[]`, `compatible_attributes[]` (`{id,name,type,type_name}`), `attribute_ids[]`, `requires_material`, and `uses_attributes`; the name arrays are display-only and the ID arrays stay authoritative. Empty mappings mean all active types/attributes; `uses_attributes = false` disables attributes but keeps the mappings. Product create/restore records a single flat audit snapshot and only product update records `before`/`after`; both carry both flags and all three pivot ID arrays. Product create/update (not restore) invalidates `['quote-options']` in the SPA. Pricing formulas use `product.selling_unit`; only `m2` selects area (`width * length`) pricing. B2 adds no route: all capability writes use the existing `/products` and `/pricing` endpoints. Materials accept `name`, `product_category_id`, `type`, `dimensions`, `unit`, `cost`, `price`, `initial_stock`, and `min_stock`. `unit` is required on create and optional on update; allowed values are exactly `m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, and `batang`. The column defaults to `pcs`, and existing rows are backfilled; `m2` displays as `m²`. Attributes accept `name`, `type` (the `attribute_types.slug`; required, max 100, `exists:attribute_types,slug`), `branch_id` (create only; immutable after creation), `price`, `cost`, and `is_active`. Creating an attribute requires an active type; updating keeps the current type even when it is inactive, and a changed type must be active. `AttributeResource` adds `type_name` (the type's display name, or the raw slug when unresolved) and keeps the `cost` rule below. A manager omitting `branch_id` is forced to the manager's own branch; an explicit different branch returns 403. A manager may create a material; the action initializes `material_branch_stock` only for the manager's branch. A manager without a branch creates the material with no branch stock row. Material create and update requests are unchanged and do not accept `is_active`; availability changes only through the two availability PATCH routes described below. `MaterialResource` adds `is_active` at the top level and in each `stocks[]` row. `GET /materials` adds a top-level integer `available_total` for Owner and Manager: globally active, non-deleted materials, and when a branch is resolved it also requires an active branch-stock row there. An owner omitting `branch_id` (or sending `all`) gets the global-only count; a manager is forced to the own branch and a branchless manager gets 0. `available_total` ignores `search`, `low_stock`, `include_deleted`, and pagination. Production responses are unchanged: they omit `is_active`, `available_total`, and money. `AttributeResource` includes `cost` only for Owner and Manager; every other role, including cashier and production, receives attributes without `cost` (only designers can read quote options), while `price` stays.

Material responses include `unit`, `current_stock`, `min_stock`, and `stocks` per branch for owner/manager. Production material responses omit `cost`, `price`, and per-branch price data, but include `unit`, `current_stock`, `min_stock`, and `is_low_stock`. The pricing-matrix endpoints (`/pricing`) remain. `POST /pricing` requires `product_id`, `customer_type`, `min_qty`, `price`, and `cost`, and accepts a nullable `material_id`; `profit` is optional and defaults to `max(0, price - cost)`. A `null` material is accepted only when the product has `requires_material = false`, and a non-null material is rejected with 422 on `material_id` for a no-material product; `'Produk ini tidak menggunakan bahan.'` and `'Bahan wajib dipilih untuk produk ini.'` are the two messages. Managers are forced to their own branch for list, write, delete, and clone operations; owners may select a branch. `PUT /pricing/{pricing}` accepts only `min_qty`, `price`, `cost`, and optional `profit`; `product_id`, `material_id`, `customer_type`, and `branch_id` are immutable, so changing a tier's material requires delete and recreate. `DELETE /pricing/{pricing}` is a soft delete with no restore endpoint; quote calculation and the `/pricing` list ignore soft-deleted pricing rows.

`GET /materials?low_stock=1` returns only materials whose resolved branch stock has `current_stock <= min_stock`. For an owner with `branch_id=all`, the filter uses OR semantics: a material is returned when at least one branch is low. In that case the top-level `is_low_stock` is always null, even when the response loads one stock row, and each `stocks[]` row carries its own boolean `is_low_stock`. For a single resolved branch the top-level `is_low_stock` is a boolean. Each `stocks[]` row always carries its own boolean. Manager and production are forced to their own branch. The filtered list is paginated like the normal list.

Stock adjustment accepts exactly one of `new_quantity` (0-99999999.99) or `delta` (non-zero signed value, -99999999.99 to 99999999.99), plus `reason` (3-255 characters). `branch_id` is required for an owner and forced from the manager's account. It writes a `stock_ledgers` row of type `adjustment` with the reason and actor, and an activity log entry. The resulting stock must stay between 0 and 99999999.99; a negative result, a zero delta, both or neither mode, or a value over the cap is 422. The response is `{ material: MaterialResource }` with the updated stock, not the ledger row. For an owner adjusting one branch, the updated value appears in `stocks[]`; the top-level `is_low_stock` may be null when several branch rows are loaded. `POST /materials/{material}/stock` remains for a quick positive restock and writes ledger type `restock`.

`PATCH /materials/{material}/min-stock` accepts `min_stock` (0-99999999.99, at most two decimals) and `branch_id` (a UUID). The owner must name the branch; a manager omitting it is forced to the manager's own branch, an explicit different branch returns 403, and a manager without a branch is denied. It updates only `material_branch_stock.min_stock` on an existing row; a missing branch-stock row returns 422 on `branch_id` and is not created. The action writes no `stock_ledgers` row and no activity log, and `current_stock` is unchanged. The response is `{ material: MaterialResource }` with the updated threshold. For an owner whose response loads multiple branch rows, the top-level `min_stock` is `null` and the updated value appears in `stocks[]`; the manager response resolves only the own-branch row and exposes its `min_stock` at the top level.

`PATCH /materials/{material}/active` accepts `is_active` (required boolean) and is Owner-only; other roles get 403 and a missing or non-boolean value gets 422. It locks the material, writes one `UPDATE` audit row with the `is_active` before/after, and leaves stock, ledger, and other master fields untouched. The response is `{ material: MaterialResource }`. The audit row inherits the actor's `branch_id` (null when the owner has no branch).

`PATCH /materials/{material}/branch-availability` accepts `is_active` (required boolean) and `branch_id` (required for an owner, UUID, existing). A manager is forced to the own branch; an explicit different branch is 403 and a branchless manager is denied. An owner omitting `branch_id`, an unknown branch, or a missing branch-stock row returns 422 on `branch_id`, and no row is created. It locks the stock row, updates only `is_active`, and writes no audit row and no stock-ledger row; `current_stock` and `min_stock` are unchanged (accepted FR25 debt). The response is `{ material: MaterialResource }`.

`POST /materials/{material}/activate` creates the branch-stock row when it is missing and sets `is_active = true` on an existing inactive row. Its `ACTIVATE` audit payload keys are unchanged; when the row exists, stock is unchanged and no ledger row is written.

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

`GET /products` uses server-side pagination at 10 products per page (`meta.per_page = 10`) and supports `search` (case-insensitive match on product name and category name), `category_id`, `is_active` (`1`/`0`), and `include_deleted` (`owner` only). `GET /quotes/options` returns active categories only; active products in an active or null category; globally active materials in an active category with an active branch-stock row in the designer's branch; and active attributes unless `include_inactive_attributes=1` (still branch-scoped). It also returns `attribute_types` (all types, ordered by `sort_order`, then `name`) so the SPA can render one select per active type. Attribute objects include `type_name`. Its categories omit `products_count`; product `category` objects include `is_active`, while quote-option material categories return only `{id,name}`. The top-level `materials` list is authoritative for availability. `products[].materials` and `products[].material_ids` are the compatible-material mapping and may still include globally or branch-inactive materials. Each product also carries `requires_material`, `uses_attributes`, `attribute_type_ids[]`, `attribute_ids[]`, and the display-only `attribute_types[]`/`compatible_attributes[]` name arrays. Options are not filtered by those mappings; the Designer SPA filters the selects, and the quote/invoice API enforces compatibility server-side. Attribute objects omit `cost` for designers and keep `price`. `GET /categories` includes `description`, `is_active`, and the derived `products_count` (soft-deleted products excluded).

## 5. Sales

| Method | Path | Access |
| :--- | :--- | :--- |
| GET | `/customers?search=&page=` | owner, manager, cashier, designer |
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

- `GET /customers?search=&page=` is server-side paginated at 20 rows per page and returns the paginated envelope `{data, links, meta}` with `meta.per_page = 20`. `search` is trimmed and matches `name` or `phone` (case-insensitive `like`); rows are ordered by `name`. Each `data[]` record carries `id`, `name`, optional `phone`/`email`/`address`, the legacy primary `institution`, the full `institutions: string[]` list, and the pricing `type`. Guests get 401 and production gets 403.
- Institution labels are exactly, in order: `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`, `Bisnis/Perusahaan`, `Organisasi/Yayasan`. `Customer::INSTITUTIONS` on the server and `CUSTOMER_INSTITUTIONS` in the SPA must both use this list.
- `POST /customers` requires `name` plus either `institutions` (non-empty array of allowed labels) or the legacy singular `institution` (one allowed label); when both are sent, `institutions` wins. Labels are deduplicated and sorted into constant order; the first normalized label is mirrored into `institution`. Phone, email, and address are optional; `type` is optional and accepts `Reguler` (default) or `Reseller`. Owner, manager, and designer may create; cashier and production get 403, guests 401. An unknown label returns 422 on `institutions` (or `institutions.0`) or `institution`; an empty/missing label pair returns 422. A successful create writes an `ActivityLog` `CREATE` row with actor, role, and branch and returns `{customer: CustomerResource}` with 201.
- `PUT /customers/{customer}` is **full replacement**, not a patch: the server rewrites `name`, `phone`, `email`, `address`, `institution`, `institutions`, and `type` from the request. Omitted optional fields become null and an omitted `type` resets to `Reguler`; send the complete desired state. It uses the same validation pair and label precedence as `POST`, resolves the route-model binding to 404 for an unknown UUID, and writes one `UPDATE` `ActivityLog` row with the actor, role, branch, and the resulting `institutions`. Access mirrors create (owner, manager, designer; cashier/production 403, guest 401). Invalid labels return 422 and write no audit row. The response is `{customer: CustomerResource}` with 200.
- Customers are global: neither index, create, nor update applies a branch filter. The no-branch non-owner write gap is recorded as remaining debt in `docs/requirements/implementation-gaps.md`.

### Quote And Invoice Input

`POST /quotes` accepts `product_id`, `material_id` (nullable), `selected_attributes[]?`, `quantity`, `width?`, `length?`, and `customer_type?`. `material_id` is required when the product has `requires_material = true` and must be absent or null when it is false; the controller uses `$data['material_id'] ?? null` and skips `MaterialAvailability` when null. Product capability material errors (missing/incompatible material, material on a no-material product) return 422 on `material_id`; product capability attribute errors (attribute outside the allowed types/compatible attributes, or any attributes on a `uses_attributes = false` product) return 422 on `selected_attributes`. Legacy errors are unchanged: request-validation failures on unknown IDs stay under `selected_attributes.*`; inactive/branch-inactive/duplicate attribute errors and pricing/product errors stay on `quote`; availability errors stay on `material_id`. No client flag widens these checks. It returns `pricing_id`, `unit_price`, `area`, `quantity`, and `subtotal`; `unit_price` comes from the pricing matrix plus, per selected attribute, its `attribute_pricing` modifier row (a `0` row still wins) or otherwise the attribute's `price`/`cost`. Attribute selection is dynamic across all active types and limited to one attribute per type; there is no fixed cap (no `max:3`). Cost and profit are not returned to the designer. `customer_type` defaults to `Reguler`. It returns 422 on `material_id` when the material is globally inactive, soft-deleted, missing, or has no active branch-stock row in the designer's branch. An unknown attribute ID returns 422 under `selected_attributes.*`; an attribute or type that is inactive, belongs to another branch, or repeats a type returns 422 under `quote`. `GET /quotes/options` returns categories, products, materials, and attributes, filtered to active catalog entries; pass `include_inactive_attributes=1` to keep inactive attributes visible in the designer edit page.

`POST /invoices` accepts `customer_id`, `institution?`, `discount?`, `deadline?`, `source?` (`Offline`/`Online`), and `items[]` with `pricing_id`, `file_name?`, `notes?`, `width?`, `length?`, `quantity`, `selected_attributes[]?`, `is_manual_price`, `unit_price?`, and `cost?`. Attribute selection follows the same dynamic one-per-type rule as `/quotes`. Order rows persist `width`, `length`, and `selected_attributes` so later edits can reprice from the saved specifications. The optional `institution` is the per-order institution choice: it must be one of that customer's `institutions` (or the legacy singular `institution` for an unbackfilled row) and is stored as a snapshot on `invoices.institution`.

`PUT /invoices/{invoice}/items` (designer, own branch) replaces the desired item list. Each update item accepts `id?` for an existing order, `pricing_id`, `file_name?`, `notes?`, `width?`, `length?`, `quantity`, and `selected_attributes[]?`. An update item may reuse an inactive material already on the locked existing invoice, including a new line with that material or a different pricing row for the same material; another unavailable material returns 422 on `items.{index}.pricing_id`. A soft-deleted material is always rejected, even when an existing order used it. The material allowance applies only to inactive-but-present materials and is material-level and server-derived from the locked existing orders; no client field widens it. The update also keeps any inactive attribute or inactive attribute type already selected anywhere on the locked existing invoice (invoice-wide allowance, server-derived, no client flag). Another inactive or unavailable attribute returns 422 on `items.{index}.selected_attributes`, duplicate attributes of one type are rejected, and there is no fixed selected-attribute cap. The action also accepts an optional `deadline`; when the key is absent the stored deadline is preserved, while an explicit `null` clears it.

`GET /invoices/{invoice}` includes each order's `pricing` object with `product` name, `material` name, and `selling_unit` (one of the seven SATUAN values) for non-production roles; only `m2` selects area (`width * length`) pricing. Names resolve through soft-deleted `pricing`, `product`, and `material` rows so invoice history stays readable. Production responses load the orders without pricing.

Server rules:

- New quote and invoice lines require a globally active material with an active branch-stock row. `POST /quotes` returns 422 on `material_id` and `POST /invoices` returns 422 on `items.{index}.pricing_id` for an inactive, branch-inactive, soft-deleted, or missing-stock material, including manual-price lines. The invoice-edit allowance above is the only exception.
- Invoice create and item updates enforce the pricing row's product composition: a `null` `material_id` is accepted only for a product with `requires_material = false`; a non-null material requires a material product and must be compatible; `uses_attributes = false` rejects new attributes under `items.{index}.selected_attributes`; allowed types/compatible attributes are enforced. Material requirement and compatibility errors return 422 on `items.{index}.pricing_id`; capability attribute errors return 422 on `items.{index}.selected_attributes`. A null-material row skips the availability check; branch, customer type, and `min_qty` still apply.
- An invoice edit grandfathers only a whole unchanged line: the locked order's `pricing_id`, the pricing row's `material_id`, and the normalized (lowercased, de-duplicated, sorted) `selected_attributes` set must all match. Unchanged lines skip the product capability checks and keep the invoice-wide inactive material/attribute allowances. Any new line, changed pricing row, changed material, or changed/added selection is enforced; no client field or flag widens the grandfather decision. `selected_attributes` in the request is used for the comparison, while the signature is derived from locked orders.
- Base price is recomputed from the selected `pricing` row plus `attribute_pricing` modifiers; client prices are ignored unless `is_manual_price` is true.
- Invoice creation reads the customer type from the customer record. The branch reseller discount is applied only when a Reseller quote falls back to the Reguler pricing row.
- Invoice creation resolves the allowed labels from `customer.institutions`, falling back to `[customer.institution]` for legacy rows. A requested `institution` outside that set returns 422 on `institution` (`Institusi tidak termasuk pilihan pelanggan ini.`) and creates no invoice; when omitted, the first allowed label is used, including for legacy labels outside the six-label allow-list. The SPA therefore omits the field when the selected label is not in `CUSTOMER_INSTITUTIONS`, so an unbackfilled legacy label still produces a valid invoice. The chosen label is stored on `invoices.institution`. `InvoiceResource` returns this snapshot as the nested `customer.institution`, falling back to the live customer label for legacy invoices, so later customer edits never rewrite existing invoice history. No top-level invoice field, receipt field, ticket field, or print change is introduced.
- Manual lines require both `unit_price` and `cost`; `unit_price` must be positive, `cost` must be non-negative, and `cost` must not exceed `unit_price`.
- `selected_attributes[]` is the canonical attribute selection: per selected attribute, its `attribute_pricing` modifier row is added when present (including a `0` row), otherwise the attribute's `price`/`cost`. Legacy `finishing_id`/`cut_id`/`display_id` keys are not request fields; they remain only as an internal fallback when `selected_attributes` is empty. The selection is persisted on `orders.selected_attributes`; there is no `order_attributes` snapshot table.
- Discount cannot exceed the computed subtotal.
- Invoice numbers use the unique branch code and a per-branch daily sequence.
- Invoice creation and item updates lock the invoice and its order rows in a transaction. Item updates recalculate line quotes and invoice totals on the server, preserve payment rows, and audit before/after totals and item IDs.
- An invoice item update is rejected with 409 when any order is already in production, the invoice is fully paid, or any existing order has `is_manual_price` set; manual lines are never repriced at list price.
- An invoice item update is rejected with 422 when the discount exceeds the new subtotal or the new total is less than the amount already paid.
- Production may read the invoice detail only with money fields redacted; invoice and receipt print are denied.

Customer input requires `name` and at least one institution label via `institutions[]` or legacy `institution`; `phone`, `email`, `address`, and `type` are optional. `type` defaults to `Reguler` and accepts `Reguler` or `Reseller`. Institutions: `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`, `Bisnis/Perusahaan`, `Organisasi/Yayasan`. Both POST and PUT follow the rules in the Customers section above, including `institutions` precedence and PUT full-replacement semantics.

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

The queue returns statuses `0`, `1`, and `3` (Gagal), accepts an optional `status` (`0`, `1`, or `3`; any other value returns 422) and an optional `search` (matched against the invoice number, file name, or exact order UUID), and for owner filters by `branch_id`. A non-UUID `search` value never touches the UUID `id` column. Each item includes `has_waste`, which is `true` when at least one `wasted_materials` row references the order and is eager-loaded so the queue does not run per-row queries. It currently orders by `created_at`, then `id`; deadline/priority sorting is not implemented. Starting an order requires status 0 and deducts material stock once by unit: `m2` deducts `area * quantity` and 422s when `area <= 0`; every other unit deducts `quantity`. A pricing row with `material_id = null` may start only when its product has `requires_material = false`: no stock deduction, no usage ledger, `material_usage = []` in the audit payload, and the existing status/operator/log/broadcast path unchanged. A null-material row for a material-required product, or a soft-deleted material (`material_id` set but `material` null), returns 422 on `material` with `Bahan untuk pesanan ini tidak ditemukan.` Waste remains material-only. No new route or permission is added. It sets status 1, writes a status log, and broadcasts. Completing requires status 1 and sets status 2.

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
| GET | `/reports/gross-revenue` | owner, manager (own branch) |
| GET | `/reports/base-cost` | owner, manager (own branch) |
| GET | `/reports/net-profit` | owner, manager (own branch) |
| GET | `/reports/expenses` | owner, manager (own branch) |
| GET | `/reports/{type}/export` | owner, manager (own branch) |
| GET | `/reports/sparkline?period=daily|weekly|monthly` | owner |
| GET | `/reports/designer-leaderboard` | manager |
| GET | `/audit-logs` | owner |
| GET | `/audit-logs/{activityLog}` | owner |
| GET | `/backups` | owner |
| POST | `/backups` | owner |
| GET | `/backups/{backup}/download` | owner |
| PATCH | `/backups/{backup}/pin` | owner |
| GET | `/backup-settings` | owner |
| PUT | `/backup-settings` | owner |

Dashboard and report scoping:

- `GET /dashboard` (`DashboardRequest`): accepts `period` (`daily|weekly|monthly`, default `daily`) and `branch_id` (owner only; defaults to `all`). Non-owner users without a branch or non-owners sending `branch_id=all` or another branch's UUID receive `403`. Both `owner` and `manager` receive `period`, `finance.receivables`, `order_count`, and `production` (`queued`, `processing`, `completed`, `failed`); `manager` also receives branch `finance` (`gross_revenue`, `expenses`, `receivables`), `counts`, `stock_ledgers`, `expenses`, and `leaderboard`. For a manager, `counts.materials` is the same available material count as `GET /materials.available_total` for the own branch: globally active materials with an active own-branch stock row; a branchless manager gets 0.
- The production dashboard payload returns `queued`, `processing`, `completed_today`, `waste_today_by_unit`, and the queue; `failed` is returned by `/production/summary`.
- Financial reports (`gross-revenue`, `base-cost`, `net-profit`, `expenses`) use `ReportFilterRequest`: `period` (`daily|weekly|monthly|custom`), `branch_id` (owner only; managers are locked to their own branch and receive `403` on `branch_id=all` or another branch), `start_date`, and `end_date` (`after_or_equal:start_date`). Preset periods (`daily`, `weekly`, `monthly`) derive `start_date`/`end_date` automatically when omitted; `custom` requires both dates. Net profit subtracts invoice HPP, wasted material cost, and operating expenses.
- `GET /reports/{type}/export` (`ExportReportRequest`, `ExportReport` action): `type` in `gross-revenue`, `base-cost`, `net-profit`, `expenses`; required `format` in `xlsx|pdf` (`422` otherwise); same period, date, and branch authorization rules as financial reports. Generates `.xlsx` via streaming `openspout/openspout` (`ExcelReportExporter`) or printable A4 `.pdf` via `dompdf/dompdf` (`PdfReportExporter`, detail tables capped at 200 rows per section) with `Content-Disposition: attachment; filename="laporan-{type}-{scope}-{start_date}_{end_date}.{xlsx|pdf}"`. Records an `EXPORT` `ActivityLog` entry via `AuditLogger` before streaming starts.

Audit logs support `search`, `sort` (`created_at`, `username`, `role`, `action`), `direction`, and pagination.

`ActivityLogResource` returns `id`, `branch {id,name}` or null, `user {id,name}` or null, `username`, `role`, `action`, `description`, `model_type`, `model_id`, `payload`, and `created_at`. Eloquent audit rows are append-only: updates and deletes throw. Query-builder mass updates and raw SQL bypass the guard by convention. The list is global and owner-only; no branch filter exists. The SPA renders a detail modal from the same list payload; there is no extra detail fetch.

### Backup Runs

`GET /api/v1/backups`

- Owner only. Paginated, `created_at` descending. `per_page` is 1-50 and defaults to 10.
- `BackupResource` fields: `id`, `trigger` (`manual`/`scheduled`), `destination` (`local`/`remote`/`both`), `status` (`queued`/`running`/`success`/`partial`/`failed`), `filename` (null while `queued`), `size_bytes`, `local_available`, `remote_available`, `is_pinned`, `error`, `user {id,name}` or null, `started_at`, `finished_at`, `created_at`, and `meta`.
- `local_available` is a live existence check on the local disk. `remote_available` only means `remote_path !== null`; no per-row R2 HEAD is issued, so the download endpoint's `410` remains authoritative.
- `meta` carries `pruned` counts per destination, `duration_ms`, `remote_skipped`, and `retention_error`. Transient keys starting with `pending_` (job cleanup targets) are stripped from the response.

`POST /api/v1/backups`

- Owner only. No request body.
- If a manual run is `queued` or `running` and was created within the last 30 minutes, returns `409` with `{ "message": "Backup manual sedang berjalan." }`. Scheduled runs never block a manual run.
- Remote backup disabled: creates `trigger=manual`, `destination=local`, `status=queued` with the owner as `user_id`.
- Remote backup enabled and configured: creates `destination=both`.
- Remote backup enabled but unconfigured (bucket, endpoint, key, or secret missing): returns `422` with `{ "message": "Backup remote belum dikonfigurasi." }` and creates no row.
- Dispatches the queued job and returns `202` with `{ "message": "Backup dimulai.", "backup": BackupResource }`. The dump never runs inside the request.
- If dispatch throws, the row is marked `failed`, one `BACKUP` audit row is written, and the response is `500` with `{ "message": "Backup gagal dimulai." }`.

`GET /api/v1/backups/{backup}/download`

- Owner only. Streams the stored dump as `filename`. Prefers `local_path` when the file exists, then `remote_path`. The request never supplies a path.
- Writes a `DOWNLOAD` audit row before streaming starts; the row means an attempted download, so a client abort after the headers still leaves it.
- Returns `410` with `{ "message": "Berkas backup tidak ditemukan." }` when neither file exists.

`PATCH /api/v1/backups/{backup}/pin`

- Owner only. Body `{ "pinned": true|false }` (`pinned` is required and boolean); unknown fields are ignored.
- Locks the row, updates `is_pinned`, writes `PIN` or `UNPIN` with before/after, and returns `200` `{ "backup": BackupResource }`.
- Pinned files are exempt from retention. They still count toward usage and can still be downloaded.

### Backup Settings

`GET /api/v1/backup-settings` returns `{ "settings": BackupSettingResource }` for the owner. `PUT /api/v1/backup-settings` is a full replace: all twelve fields are required, and partial updates return `422`.

| Field | Rule |
| :--- | :--- |
| `local_enabled`, `remote_enabled` | boolean |
| `local_time`, `remote_time` | `H:i` |
| `local_daily_days`, `remote_daily_days` | integer 7-90 |
| `local_weekly_weeks`, `remote_weekly_weeks` | integer 4-52 |
| `local_monthly_months`, `remote_monthly_months` | integer 6-24 |
| `local_yearly_years`, `remote_yearly_years` | integer 1-10 |

`BackupSettingResource` returns the twelve fields plus `local_usage_bytes`, `remote_usage_bytes`, `remote_quota_bytes`, `remote_usage_percent`, `remote_configured`, and `pinned_count`.

- Usage sums `size_bytes` of rows whose matching path is not null; pinned files count. `remote_usage_percent = remote_usage_bytes / remote_quota_bytes * 100`, rounded to one decimal, and is `0` when the quota is `0`.
- `remote_configured` only says whether bucket, endpoint, key, and secret are present; values are never exposed.
- Saving with remote enabled but unconfigured is allowed; the first remote run fails visibly and the page shows a warning.
- The update writes one `UPDATE` audit row with `before` and `after` for the submitted fields and returns `{ "settings": ... }`.
- Changes apply on the next `backup:tick`; no scheduler restart is needed. A time moved earlier than now catches up on the next tick if today's run has not happened.

### Reporting And Backup Audit Actions

| Action | Subject | Payload | Notes |
| :--- | :--- | :--- | :--- |
| `EXPORT` | None (`model_type`/`model_id` null) | `report_type`, `format`, `period`, `branch_id`, `start_date`, `end_date` | Written via `AuditLogger` before streaming; `activity_logs.branch_id` is null when owner exports `all` branches. |
| `BACKUP` | `Backup` | `backup_id`, `trigger`, `destination`, `status`, `filename`, `local_path`, `remote_path`, `size`, `error`, `meta` | One row per run. Scheduled runs use the system actor. `RunBackup::failed()` is idempotent by checking for an existing row. |
| `DOWNLOAD` | `Backup` | `backup_id`, `filename`, `source` (`local` or `remote`) | Written before streaming; it records the attempt, not completion. |
| `PIN` / `UNPIN` | `Backup` | `backup_id`, `is_pinned_before`, `is_pinned_after` | Inside the pin transaction. |
| `UPDATE` | `BackupSetting` | `before`, `after` | Keys limited to the submitted settings fields, e.g. `local_time` or `remote_daily_days`. |

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
