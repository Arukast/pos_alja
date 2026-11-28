# Entity Relationship Diagram

- **Database:** PostgreSQL in production, SQLite in the automated test suite.
- **Keys:** All primary keys are UUID strings. Foreign keys are UUID strings.
- **Table naming:** Eloquent default pluralization, except `material_branch_stock`, which uses a singular table name.
- **Soft deletes:** Present on `branches`, `users`, `customers`, `products`, `materials`, and `attributes`.
- **Target schema:** This diagram shows the as-built schema after B4. PB-05/PB-07 inventory purchasing, stock-ledger traceability, material units, production failure, the Unit A catalog cleanup, the B1a material retirement flags, the B1b attribute types, the B2 product flags/pivots, B3 selling units, and the B4 pricing redesign are implemented; remaining target changes are tracked in `docs/requirements/implementation-gaps.md`.
- **Pricing matrix removed:** `pricing`, `attribute_pricing`, and `orders.pricing_id` were dropped by `2026_11_27_000013` and `2026_11_27_000014`; their rows are unrecoverable and require a verified backup before deploy. Prices now compute from material and attribute rows plus global product quantity tiers, with six snapshots on each order (note 4).
- **Machine tables:** `machines` and `machine_pricing` are dropped by `2026_11_25_000002_drop_machines_tables.php`; their rows are unrecoverable.
- **API-first status:** The React refactor did not add domain tables. Sanctum adds `personal_access_tokens`; the purchase and stock-ledger tables and columns are implemented as described here, while the remaining business tables follow this target.
- **PB-10 tables:** `backups` (run history) and `backup_settings` (owner-editable singleton) are implemented by `2026_11_26_130000_create_backups_table.php`, `2026_11_26_130001_create_backup_settings_table.php`, and `2026_11_26_130002_make_backups_filename_nullable.php`. `activity_logs` gains a `created_at` index and a `nullOnDelete` branch FK.

```mermaid
erDiagram
    BRANCHES |o--o{ USERS : "assigns staff"
    BRANCHES ||--o{ MATERIAL_BRANCH_STOCK : "holds stock"
    BRANCHES |o--o{ ATTRIBUTES : "owns"
    BRANCHES |o--o{ INVOICES : "issues"
    BRANCHES ||--o{ PAYMENTS : "receives"
    BRANCHES ||--o{ EXPENSES : "incurs"
    BRANCHES |o--o{ WASTED_MATERIALS : "logs"
    BRANCHES |o--o{ STOCK_LEDGERS : "records"
    BRANCHES |o--o{ ACTIVITY_LOGS : "audits"
    BRANCHES ||--o{ PURCHASES : "records"

    CUSTOMERS ||--o{ INVOICES : "places"

    PRODUCT_CATEGORIES |o--o{ PRODUCTS : "groups"
    PRODUCT_CATEGORIES ||--o{ MATERIALS : "groups"

    PRODUCTS ||--o{ ORDERS : "ordered as"
    PRODUCTS ||--o{ PRODUCT_QUANTITY_DISCOUNTS : "tiered by"
    PRODUCTS ||--o{ MATERIAL_PRODUCT : "maps"
    PRODUCTS ||--o{ ATTRIBUTE_TYPE_PRODUCT : "allows types"
    PRODUCTS ||--o{ ATTRIBUTE_PRODUCT : "allows attributes"
    MATERIALS ||--o{ ORDERS : "uses"
    MATERIALS ||--o{ MATERIAL_PRODUCT : "compatible with"
    MATERIALS ||--o{ MATERIAL_BRANCH_STOCK : "stocked at"
    MATERIALS ||--o{ STOCK_LEDGERS : "moves"
    MATERIALS ||--o{ WASTED_MATERIALS : "wasted"
    MATERIALS ||--o{ PURCHASE_ITEMS : "purchased as"

    ATTRIBUTE_TYPES ||--o{ ATTRIBUTES : "classifies"
    ATTRIBUTE_TYPES ||--o{ ATTRIBUTE_TYPE_PRODUCT : "allowed on"
    ATTRIBUTES ||--o{ ATTRIBUTE_PRODUCT : "compatible with"
    ATTRIBUTES ||--o{ ORDER_ATTRIBUTES : "selected as"


    INVOICES ||--o{ ORDERS : "contains"
    INVOICES ||--o{ PAYMENTS : "settled by"
    ORDERS ||--o{ ORDER_ATTRIBUTES : "has"
    ORDERS ||--o{ ORDER_STATUS_LOGS : "status history"
    ORDERS |o--o{ WASTED_MATERIALS : "optional link"
    PURCHASES ||--o{ PURCHASE_ITEMS : "contains"

    USERS |o--o{ INVOICES : "designer"
    USERS |o--o{ ORDERS : "operator"
    USERS |o--o{ ORDERS : "approves design"
    USERS |o--o{ PAYMENTS : "cashier"
    USERS |o--o{ ORDER_STATUS_LOGS : "actor"
    USERS |o--o{ STOCK_LEDGERS : "records"
    USERS ||--o{ PURCHASES : "actor"
    USERS |o--o{ WASTED_MATERIALS : "designer"
    USERS |o--o{ WASTED_MATERIALS : "operator"
    USERS |o--o{ EXPENSES : "records"
    USERS |o--o{ ACTIVITY_LOGS : "actor"
    USERS |o--o{ BACKUPS : "starts"

    BRANCHES {
        uuid id PK
        string name
        string code UK
        string address
        string phone
        boolean is_active
        decimal reseller_discount_percent
        json settings
        timestamp deleted_at
    }

    USERS {
        uuid id PK
        string name
        string username UK
        string email
        timestamp email_verified_at
        string password
        string role
        uuid branch_id FK
        boolean is_active
        string phone
        text address
        timestamp deleted_at
    }

    CUSTOMERS {
        uuid id PK
        string name
        string phone
        text address
        string email
        string institution
        json institutions
        string type
        timestamp deleted_at
    }

    PRODUCT_CATEGORIES {
        uuid id PK
        string name
        string description
        boolean is_active
        string calculation_archetype
        json allowed_attribute_types
    }

    PRODUCTS {
        uuid id PK
        uuid product_category_id FK
        string name
        string selling_unit
        boolean requires_material
        boolean uses_attributes
        boolean is_active
        timestamp deleted_at
    }

    MATERIAL_PRODUCT {
        uuid product_id FK
        uuid material_id FK
        timestamp created_at
        timestamp updated_at
    }

    ATTRIBUTE_TYPE_PRODUCT {
        uuid product_id FK
        uuid attribute_type_id FK
        timestamp created_at
        timestamp updated_at
    }

    ATTRIBUTE_PRODUCT {
        uuid product_id FK
        uuid attribute_id FK
        timestamp created_at
        timestamp updated_at
    }

    PRODUCT_QUANTITY_DISCOUNTS {
        uuid id PK
        uuid product_id FK
        integer min_qty
        decimal discount_percent
        timestamp created_at
        timestamp updated_at
    }

    MATERIALS {
        uuid id PK
        uuid product_category_id FK
        string name
        string type
        string unit
        string dimensions
        decimal cost
        decimal price
        boolean is_active
        timestamp deleted_at
    }

    MATERIAL_BRANCH_STOCK {
        uuid id PK
        uuid material_id FK
        uuid branch_id FK
        decimal current_stock
        decimal min_stock
        boolean is_active
    }

    ATTRIBUTE_TYPES {
        uuid id PK
        string name UK
        string slug UK
        integer sort_order
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    ATTRIBUTES {
        uuid id PK
        uuid branch_id FK
        string name
        string type
        decimal price
        decimal cost
        boolean is_active
        timestamp deleted_at
    }

    INVOICES {
        uuid id PK
        string invoice_number UK
        uuid branch_id FK
        timestamp created_date
        timestamp payment_date
        timestamp dp_date
        uuid customer_id FK
        uuid designer_id FK
        integer status
        decimal discount
        decimal dp_amount
        decimal accounts_receivable
        decimal grand_total
        decimal total_cost
        decimal total_profit
        timestamp deadline
        integer pickup_status
        timestamp pickup_date
        integer source
        string institution nullable
    }

    ORDERS {
        uuid id PK
        uuid invoice_id FK
        uuid product_id FK
        uuid material_id FK
        uuid operator_id FK
        string file_name
        string file_path
        string preview_path
        text notes
        decimal area
        decimal quantity
        decimal unit_price
        string customer_type
        decimal reseller_discount_percent
        decimal quantity_discount_percent
        decimal discount_amount
        decimal subtotal
        decimal cost
        decimal profit
        timestamp ordered_at
        integer status
        integer design_status
        timestamp design_approved_at
        uuid design_approved_by FK
        integer priority
        integer pickup_status
        timestamp pickup_date
        boolean is_manual_price
    }

    ORDER_ATTRIBUTES {
        uuid id PK
        uuid order_id FK
        uuid attribute_id FK
        string type
        decimal price
        decimal cost
    }

    PAYMENTS {
        uuid id PK
        uuid branch_id FK
        uuid invoice_id FK
        uuid user_id FK
        decimal amount
        string payment_method
        string type
        string cashier_name
    }

    EXPENSES {
        uuid id PK
        uuid branch_id FK
        uuid user_id FK
        timestamp incurred_at
        decimal amount
        text description
        string recorded_by_name
        string type
        string category
    }

    WASTED_MATERIALS {
        uuid id PK
        uuid branch_id FK
        timestamp logged_at
        uuid material_id FK
        uuid order_id FK
        uuid designer_id FK
        uuid operator_id FK
        decimal quantity
        string dimensions
        text notes
    }

    STOCK_LEDGERS {
        uuid id PK
        uuid branch_id FK
        uuid material_id FK
        uuid user_id FK
        string type
        string reason
        string reference_type
        uuid reference_id
        timestamp logged_at
        decimal initial_stock
        decimal added_stock
        decimal used_stock
        decimal wasted_stock
        decimal remaining_stock
        text notes
    }

    PURCHASES {
        uuid id PK
        uuid branch_id FK
        uuid user_id FK
        timestamp purchased_at
        string supplier
        decimal total_amount
        text notes
        string idempotency_key
    }

    PURCHASE_ITEMS {
        uuid id PK
        uuid purchase_id FK
        uuid material_id FK
        decimal quantity
        decimal unit_cost
        decimal subtotal
    }

    ORDER_STATUS_LOGS {
        uuid id PK
        uuid order_id FK
        integer status
        uuid user_id FK
        string user_name
        string notes
    }

    ACTIVITY_LOGS {
        uuid id PK
        uuid branch_id FK
        uuid user_id FK
        string username
        string role
        string action
        string description
        string model_type
        uuid model_id
        json payload
        timestamp created_at
    }

    BACKUPS {
        uuid id PK
        string trigger
        string destination
        string status
        string filename
        string local_path
        string remote_path
        bigint size_bytes
        text error
        boolean is_pinned
        json meta
        uuid user_id FK
        timestamp started_at
        timestamp finished_at
        timestamp created_at
        timestamp updated_at
    }

    BACKUP_SETTINGS {
        uuid id PK
        boolean local_enabled
        string local_time
        boolean remote_enabled
        string remote_time
        integer local_daily_days
        integer local_weekly_weeks
        integer local_monthly_months
        integer local_yearly_years
        integer remote_daily_days
        integer remote_weekly_weeks
        integer remote_monthly_months
        integer remote_yearly_years
        date last_local_date
        date last_remote_date
        timestamp created_at
        timestamp updated_at
    }
```

---

## Relationship Notes

1. **`attributes.type` stores an `attribute_types.slug`, not a foreign key.** The slug is immutable and unique, and the column is widened to 100 characters so it matches. Referential integrity is by request validation (`exists:attribute_types,slug`, max 100), not by a key. Legacy non-empty values were backfilled as types; an empty value is not a type.
2. **`material_branch_stock` is the only stock junction with a unique `(material_id, branch_id)` constraint.** `material_product` has a composite primary key `(product_id, material_id)`.
3. **`orders` is a line item of `invoices`.** The invoice carries the financial roll-up (`grand_total`, `total_cost`, `total_profit`), while each order carries its own `unit_price`, `subtotal`, `cost`, and `profit` plus six identity/discount snapshots: `product_id`, `material_id`, `customer_type`, `reseller_discount_percent`, `quantity_discount_percent`, and `discount_amount`. Pre-B4 orders keep their money snapshots with null product/material identity.
4. **Price is computed, not looked up.** `base = materials.price + sum(selected active attributes.price)`; Reseller lines apply `branches.reseller_discount_percent` (`0–100`, default `0`, mirrored in `branches.settings.reseller_discount_percent` and editable on `/owner/branches`) to the base; `area_factor = width * length` only when `products.selling_unit = m2`; then the highest `product_quantity_discounts` row with `min_qty <= quantity` applies its `discount_percent`. Cost mirrors the chain from `materials.cost` plus attribute costs without commercial discounts, `profit = subtotal - cost`, and `discount_amount` is the undiscounted subtotal minus the final subtotal. Tiers are global, unique per `(product_id, min_qty)`, Owner-written and Manager-read. `pricing`, `attribute_pricing`, the `/pricing` routes, the `/manager/pricing` page (the URL now redirects to `/manager/quantity-tiers`), and `orders.pricing_id` were removed by B4.
5. **`order_attributes` is target-only.** No table exists; attribute selections are persisted on `orders.selected_attributes` as attribute IDs whose `price`/`cost` are read from the attribute rows at quote time, without a per-attribute snapshot. The selection is dynamic across all active `attribute_types`, at most one attribute per type, with no fixed cap. The target keeps at most one row per `(order_id, type)` with a price/cost snapshot.
6. **`orders.operator_id`** is the production operator who started or completed the job; it is null until production starts.
7. **`invoices.designer_id`** is the user who submitted the order.
8. **`payments.user_id`** is the cashier; `payments.cashier_name` is a denormalized audit copy.
9. **`expenses.user_id`** is the Owner or Branch Manager who recorded the expense; `recorded_by_name` is the audit copy. `expenses.category` is a fixed dropdown, not free text.
10. **`activity_logs.model_id` is a UUID without a foreign key**, allowing the log to reference any model type.
11. **`branch_id` is nullable on** `users`, `attributes`, `invoices`, `wasted_materials`, `stock_ledgers`, and `activity_logs`. A null value means "global" or "unassigned", depending on the table; owner accounts are the main case for a null user branch. `product_quantity_discounts` has no branch column.
12. **Soft-deleted rows are not shown separately.** The `deleted_at` column is included only on tables that use `SoftDeletes`; deleted rows remain in the table.
13. **`product_categories` rows are never deleted.** `is_active` is the retire mechanism: deactivating a category leaves its products and materials untouched. `calculation_archetype` and `allowed_attribute_types` remain dead columns but are still present in the table.
14. **Target and implemented additions.** The following columns and tables are part of the target schema:
    - `branches.reseller_discount_percent` for Reseller pricing; implemented by `2026_11_25_000002_add_reseller_discount_percent_to_branches_table.php`.
    - `products.is_active` for catalog filtering; implemented by `2026_11_23_000000_add_is_active_and_indexes_to_products_table.php`.
    - `product_categories.calculation_archetype` made nullable (default `per_piece`) and `allowed_attribute_types` deprecated from Category API/UI, with `products.pricing_type` backfilled to `area` for legacy `surface_area` categories by `2026_11_27_000000_drop_category_archetype_and_allowed_types.php`.
    - `products.selling_unit` replaces `products.pricing_type` with the seven SATUAN values (`m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, `batang`), default `pcs`; implemented by `2026_11_27_000008_z_replace_product_pricing_type_with_selling_unit.php`. The backfill first sets every row, including soft-deleted ones, to `pcs` (legacy `unit`), then maps legacy `pricing_type = 'area'` rows to `m2`, and drops `pricing_type` in a separate closure for SQLite. `down()` restores `pricing_type` only as `unit`/`area` with default `unit`, so `lembar`, `roll`, `kg`, `liter`, and `batang` values are lossy on rollback; back up the database and obtain owner approval before rolling back.
    - `material_product` pivot for compatible materials per product (historically backfilled from the now-dropped matrix) by `2026_11_27_000001_create_material_product_table.php`.
    - `product_categories.description` and `is_active`, with `product_category_discounts` dropped, by `2026_11_27_000002_add_description_and_is_active_to_product_categories_table.php` and `2026_11_27_000003_drop_product_category_discounts_table.php`.
    - `attribute_types` was dropped by `2026_11_27_000004_drop_attribute_types_table.php` during the Unit A cleanup and reintroduced by `2026_11_27_000006_create_attribute_types_and_widen_attributes_type.php` for B1b. The B1b migration widens `attributes.type` from 20 to 100, seeds Finishing, Potong (`cut`), and Display, and backfills other non-empty legacy values; the previously dropped rows remain unrecoverable.
    - `materials.unit` for `m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, and `batang`; implemented by `2026_11_25_000001_add_unit_to_materials_table.php` with default `pcs` and a name/category/archetype backfill.
    - `materials.is_active` for global retirement and `material_branch_stock.is_active` for branch availability, both boolean default `true`; implemented by `2026_11_27_000005_add_is_active_to_materials_and_material_branch_stock.php` with no backfill beyond the default and no index.
    - `attributes.price`, `attributes.cost`, and `attributes.is_active`; implemented by `2026_11_25_000001_add_price_cost_is_active_to_attributes_table.php`.
    - `orders.product_id`, `material_id`, `customer_type`, `reseller_discount_percent`, `quantity_discount_percent`, and `discount_amount`, added with no backfill by `2026_11_27_000012_add_pricing_snapshots_to_orders_table.php`; `orders.pricing_id` was dropped by `2026_11_27_000013`. `product_quantity_discounts` was created by `2026_11_27_000011`, and the `pricing`/`attribute_pricing` tables were dropped by `2026_11_27_000014`. All four migrations use portable Schema operations, no platform-specific raw SQL, and are exercised on SQLite; PostgreSQL DDL and rollback are unverified here. `design_status`, `design_approved_at`, `design_approved_by`, and `priority` remain target-only.
    - `customers.institution` for customer classification; implemented by `2026_11_25_000000_add_institution_to_customers_table.php`. `customers.institutions` (JSON, default `["Masyarakat/Retail"]`) holds the full ordered label list; added by `2026_11_27_000009_add_institutions_to_customers_table.php`, which backfills each legacy primary label. `invoices.institution` (nullable string) holds the per-order snapshot; added by `2026_11_27_000010_add_institution_to_invoices_table.php`, which backfills historical invoices from their customer's current label. Both are portable additive migrations; no merged migration was edited.
    - The transitional order quote-input columns `width`, `length`, and `selected_attributes` are added by `2026_11_26_100000_add_order_edit_specifications.php` and persisted by invoice create/update so pre-production edits can reprice from the saved specifications. `selected_attributes` is a dynamic list with at most one attribute per type.
    - `order_attributes` for attribute snapshots.
    - `expenses.user_id` and `recorded_by_name`.
    - `stock_ledgers.user_id`, `type`, `reason`, `reference_type`, and `reference_id`; implemented by `2026_11_24_000001_add_traceability_to_stock_ledgers_table.php`.
    - `purchases` and `purchase_items` for raw-material purchases; implemented by `2026_11_24_000002_create_purchases_table.php` and `2026_11_24_000003_create_purchase_items_table.php`. `purchases.user_id` is the actor (Owner or Branch Manager), and `purchases.idempotency_key` is unique per branch.
    - `users.is_active` (boolean, default `true`, non-null) by `2026_11_26_140000_add_is_active_to_users_and_convert_trashed_branches.php`. The same migration converts soft-deleted branches to visible inactive rows (`deleted_at = null`, `is_active = false`); `down()` drops only `users.is_active`, so the branch conversion is one-way.
    - `backups` and `backup_settings` for PB-10; implemented by the 2026-11-26 migrations, with `backups.filename` made nullable by a follow-up migration. `activity_logs` gains a `nullOnDelete` branch FK and a `created_at` index.
15. **The machine master is gone.** `wasted_materials.machine_id` was dropped by `2026_11_24_000004_drop_machine_id_from_wasted_materials_table.php`; the `machines` and `machine_pricing` tables and their endpoints were dropped by `2026_11_25_000002_drop_machines_tables.php`. The rows are unrecoverable, and `down()` recreates empty tables only. The pricing matrix tables were removed separately by B4 (note 4). `wasted_materials.order_id` stays nullable for legacy rows, but the API requires it for all new waste.
16. **Order priority is target-only.** There is no `priority` column; the implemented queue orders by `created_at`, then `id`. The target semantics are higher `priority` first, then `deadline ASC (nulls last)`, `priority DESC`, `created_at ASC`, with default priority 0.
17. **Design status semantics.** `0 = pending`, `1 = in_design`, `2 = approved`. Approval stores the actor and timestamp. The Designer may move forward; Owner and Branch Manager may override a backward transition.
18. **Stock ledger type semantics.** Types: `initial`, `purchase`, `usage`, `usage_reversal`, `waste`, `adjustment`, `restock`. `reason` is mandatory for `adjustment`; `reference_type` and `reference_id` point to the purchase (`purchase`) or order (`usage`, `usage_reversal`, `waste`) that caused the movement; `user_id` is the actor. `usage_reversal` is written when a processing order fails and adds back the net usage per `(material_id, branch_id)`. Legacy rows may have a null type, actor, and reference after the best-effort backfill.
19. **Invoice print is a view, not a table.** There is no ticket table and no separate work-order document. The target invoice print reads `invoices`, `orders`, and `order_attributes`; the current build persists `orders.selected_attributes` and has no `order_attributes` table.
20. **`materials.unit` is implemented** with exactly seven values (`m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, `batang`), default `pcs`, and `m2` displayed as `m²`. The unit drives production deduction: `m2` uses `area * quantity`; every other unit uses `quantity`. `materials.type` remains an optional descriptive label.
21. **`backups` is run history, never the backup payload.** `trigger` is `manual` or `scheduled`; `destination` is `local`, `remote`, or `both`; `status` is `queued`, `running`, `success`, `partial`, or `failed`. `filename` is nullable while a row is still `queued`; the job fills it when it starts. `local_path` and `remote_path` are relative to the `backups-local` and `backups-s3` disks. `user_id` is the manual owner and `nullOnDelete`; scheduled rows have no user. `meta` holds pruned counts, `duration_ms`, `remote_skipped`, `retention_error`, and transient `pending_local`/`pending_temp` cleanup targets that `BackupResource` strips. Indexes: `created_at`, `status`, `is_pinned`. No branch column: backups are owner-only and global.
22. **`backup_settings` is a singleton with a fixed UUID** (`00000000-0000-4000-8000-000000000001`) so concurrent first access cannot insert two rows. The row seeds from `config('backup.*')` on first access; afterwards the database is the source of truth and env values are initial defaults only. `last_local_date` and `last_remote_date` prevent duplicate daily scheduled runs. A retention window below 1 fails closed at prune time.
23. **`activity_logs` is append-only from the application.** Eloquent `updating` and `deleting` throw `LogicException`; query-builder mass updates, `truncate()`, and raw SQL bypass the guard by convention. `branch_id` is nullable and `nullOnDelete`, so a force-deleted branch leaves the row with `branch_id = null`. `created_at` is indexed for the audit list and any future archive query. System writes use `user_id = null`, `username = system`, `role = system`.
24. **Retention deletes files, not history.** When a dump file is pruned, the matching `local_path` or `remote_path` is set to `null` and the row remains. Pinned filenames are exempt from selection; the newest file on each destination is always kept. `ACTIVATE` audit payloads record branch-stock creation and initial stock; the global material active toggle writes one `UPDATE` row with the flag before/after and inherits the actor's `branch_id`.
25. **Material retirement is two-level.** `materials.is_active` (default true) is the global retire flag; `material_branch_stock.is_active` (default true) is branch availability. Neither changes `current_stock` nor writes `stock_ledgers`. A material is available for new quote/invoice lines only when both flags are true and a branch-stock row exists; a missing row means unavailable, not inactive. An invoice edit may reuse an inactive material already on the locked invoice, while a soft-deleted material is never available, even when an order used it. `POST /materials/{material}/activate` sets an existing inactive stock row active; it creates a missing row as before.
26. **`attribute_types` is master data, not a lookup foreign key.** `name` and `slug` are each unique and capped at 100 characters; `slug` is generated from the name on create, is immutable on update, and is the route key. There is no delete endpoint (a 404 tombstone); `is_active = false` is retirement. The migration seeds Finishing, Potong (`cut`), and Display at sort order 1/2/3, then backfills every other non-empty legacy `attributes.type` with its exact slug and a headline name. Attribute creation requires an active type; updates keep the current type even when inactive and require an active type when the type changes.
27. **`users.is_active` is the account lifecycle flag; `deleted_at` stays administrative.** Default `true`. Deactivation keeps the row, blocks login, and rejects the account on its next authenticated `/api/v1` request; an owner can reactivate it. Soft-deleted users stay hidden by default, and restore sets `is_active = true`. `users.is_active` is not accepted by user store/update requests.
28. **Product composition is stored on `products` plus two pivots.** `products.requires_material` and `products.uses_attributes` are boolean, default `true`, and cast to boolean. `attribute_type_product` and `attribute_product` use composite primary keys `(product_id, attribute_type_id)` and `(product_id, attribute_id)`, UUID foreign keys with `cascadeOnDelete()`, and `created_at`/`updated_at` timestamps from `withTimestamps()`. The `Product` relations are explicitly named `attributeTypes()` (pivot `attribute_type_product`) and `compatibleAttributes()` (pivot `attribute_product`); there is no `attributes()` relation. An empty type pivot means all active `attribute_types`; an empty attribute pivot means all active attributes of the allowed types. `uses_attributes = false` rejects new client attribute selections while preserving both pivots so re-enabling restores them. Every `ProductResource` producer eager-loads `category`, `materials`, `attributeTypes`, and `compatibleAttributes.typeInfo` (catalog list, quote options, and product create/update/restore): an unloaded pivot yields empty arrays, which silently means all. The resource exposes `attribute_type_ids`/`attribute_ids` for writes and filtering plus display-only `attribute_types`/`compatible_attributes` name arrays. Owner writes the flags and pivots; Manager reads them.
29. **No-material lines are order-owned.** `2026_11_27_000008_make_pricing_material_id_nullable.php` was a B2 step on the retired matrix; B4 dropped that table. A null `orders.material_id` is valid only when the order's product has `requires_material = false`, skips availability and stock deduction, and is accepted by production start with an empty `material_usage`. Its PostgreSQL DDL and null-row rollback remain unverified.
30. **Customer institutions are a label list; invoice institution is a snapshot.** `customers.institutions` is a JSON array of at most six allowed labels in constant order (`Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`, `Bisnis/Perusahaan`, `Organisasi/Yayasan`). The legacy singular `customers.institution` is kept as the mirrored first label so existing readers and payloads keep working; unknown legacy labels are preserved during backfill rather than dropped. `invoices.institution` stores the label chosen for that order and is immutable after creation, so a later customer edit changes neither past invoices nor their API responses. `InvoiceResource` falls back to the live `customer.institution` only for invoices with no snapshot. Customers are global and carry no branch id.

---

## Status Code Reference

| Column | Value | Meaning |
| :--- | :--- | :--- |
| `invoices.status` | 0 | Unpaid / pending down payment |
| `invoices.status` | 1 | Down payment paid |
| `invoices.status` | 2 | Fully paid |
| `invoices.pickup_status` | 0 | Not picked up |
| `invoices.pickup_status` | 1 | Picked up |
| `orders.status` | 0 | Queued / not processed (Antre Produksi) |
| `orders.status` | 1 | Processing / printing (Diproses) |
| `orders.status` | 2 | Completed (Selesai) |
| `orders.status` | 3 | Failed (Gagal); net usage reversed, reopen returns to 0 |
| `orders.design_status` | 0 | Pending |
| `orders.design_status` | 1 | In design (Proses Desain) |
| `orders.design_status` | 2 | Design approved (Disetujui) |
| `customers.type` | `Reguler` | Regular customer; no reseller discount |
| `customers.type` | `Reseller` | Reseller customer; branch reseller discount applies |
| `orders.customer_type` | `Reguler` / `Reseller` | Snapshot of the customer type used for the quote |
| `branches.reseller_discount_percent` | `0–100` | Reseller discount percentage (default `0.00`; mirrored in `branches.settings.reseller_discount_percent`) |
| `payments.type` | `down_payment` | Down payment |
| `payments.type` | `settlement` | Balance settlement |
| `payments.type` | `partial` | Reserved by schema; not written by `PaymentService` |
| `expenses.type` | `modal` | Capital / drawer float label |
| `expenses.type` | `laba` | Operating expense label |
| `expenses.category` | `Bahan Baku` | Raw-material expense |
| `expenses.category` | `Gaji` | Payroll |
| `expenses.category` | `Sewa` | Rent |
| `expenses.category` | `Listrik & Air` | Utilities |
| `expenses.category` | `Maintenance` | Equipment and building maintenance |
| `expenses.category` | `Transport` | Delivery and transport |
| `expenses.category` | `Marketing` | Promotion |
| `expenses.category` | `Operasional` | General operations |
| `expenses.category` | `Lain-lain` | Other |
| `stock_ledgers.type` | `initial` | Initial stock registration |
| `stock_ledgers.type` | `purchase` | Raw-material purchase |
| `stock_ledgers.type` | `usage` | Material used by an order |
| `stock_ledgers.type` | `usage_reversal` | Net usage returned when a processing order fails |
| `stock_ledgers.type` | `waste` | Failed print / waste |
| `stock_ledgers.type` | `adjustment` | Owner/Manager stock correction with reason |
| `stock_ledgers.type` | `restock` | Manual stock addition |
| `backups.trigger` | `manual` | Owner-triggered run |
| `backups.trigger` | `scheduled` | `backup:tick` run |
| `backups.destination` | `local` | Local `backupdata` volume only |
| `backups.destination` | `remote` | R2/S3 only |
| `backups.destination` | `both` | Local volume plus R2/S3 |
| `backups.status` | `queued` | Row created, job not started |
| `backups.status` | `running` | Job started; a killed worker can leave this state |
| `backups.status` | `success` | Every requested destination written |
| `backups.status` | `partial` | Local written, remote failed for `both` |
| `backups.status` | `failed` | No usable destination produced |

Both expense types are summed into `expenses_total` and subtracted from `net_profit`; `type` is a label, not a filter.

---

## Key Constraints

| Table | Constraint |
| :--- | :--- |
| `branches` | `code` unique |
| `users` | `username` unique |
| `material_branch_stock` | `(material_id, branch_id)` unique |
| `material_product`, `attribute_type_product`, `attribute_product` | Composite primary keys on the two ID columns; both new B2 pivots cascade on product/type/attribute delete |
| `invoices` | `invoice_number` unique |
| `attribute_types` | `name` unique; `slug` unique; no delete; activation is retirement |
| `order_attributes` (target-only, no table) | `(order_id, type)` unique; one attribute per type per order |
| `purchases` / `purchase_items` | `purchases` has a unique `(branch_id, idempotency_key)`; `purchase_items.purchase_id` cascades and `material_id` restricts |
| `backups` | `user_id` is `nullOnDelete`; indexes on `created_at`, `status`, and `is_pinned` |
| `backup_settings` | One row with the fixed singleton UUID `00000000-0000-4000-8000-000000000001` |
| Foreign keys | `cascadeOnDelete` on most owned children; `nullOnDelete` on actor references, `orders.design_approved_by`, `orders.operator_id`, `wasted_materials.order_id`, `backups.user_id`, and `activity_logs.branch_id`; NO ACTION/RESTRICT on `invoices.customer_id`, `wasted_materials.material_id`, `stock_ledgers.material_id`, `orders.product_id`/`material_id`, `purchases.branch_id`/`user_id`, and `purchase_items.material_id` |

---

## Diagram Maintenance

Update this diagram whenever a migration adds, removes, or renames a table or a foreign key. Do not edit merged migrations; add a new migration and reflect the result here.
