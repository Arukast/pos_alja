# Functional Requirements (Target Specification)

- **System:** Print Shop POS (Alja Digital Printing)
- **Source of truth:** Capstone proposal FR1–FR26, revised by Sprint Review 1 on 2026-11-24. The proposal mapping is retained; changes are recorded in `docs/requirements/sprint-review-1.md`.
- **Document type:** Target requirements. Implementation progress is tracked in `docs/requirements/implementation-gaps.md`.
- **Role mapping:** Pemilik = `owner`, Branch Manager = `manager`, Kasir = `cashier`, Designer = `designer`, Produksi = `production`.

## Summary

Sprint Review 1 Sales acceptance, superseded in part by the customer-management change (2026-11-27): Pesanan Baru is search-first and customers are managed on one shared `/customers` page (owner/manager/designer write, cashier read-only). A customer carries one or more institution labels and the chosen label is snapshotted on the invoice; institution is required while phone, email, and address remain optional. Designer item configuration follows Product -> Material -> Finishing -> Display. Cashiers have no expense-entry screen; invoice print is the customer-facing print flow.

| ID | Module | Roles |
| :--- | :--- | :--- |
| FR1 | Autentikasi | All |
| FR2 | Autentikasi | Owner, Branch Manager |
| FR3 | Autentikasi | All |
| FR4 | Pengguna | Owner, Branch Manager |
| FR5 | Hak Akses | All |
| FR6 | Produk | Owner, Branch Manager (read) |
| FR7 | Produk | Designer, Kasir |
| FR8 | Pesanan | Designer; Owner, Manager, Cashier (customer management) |
| FR9 | Pesanan | Designer |
| FR10 | Pesanan | Designer |
| FR11 | Pembayaran | Kasir |
| FR12 | Pembayaran | Kasir |
| FR13 | Pembayaran | Kasir, Owner, Branch Manager, Designer |
| FR14 | Pengeluaran | Owner, Branch Manager |
| FR15 | Inventaris | Owner, Branch Manager |
| FR16 | Inventaris | Produksi |
| FR17 | Inventaris | Owner, Branch Manager, Produksi |
| FR18 | Inventaris | Owner, Branch Manager |
| FR19 | Produksi | All |
| FR20 | Produksi | Produksi |
| FR21 | Produksi | Kasir |
| FR22 | Laporan | Owner, Branch Manager |
| FR23 | Laporan | Owner, Branch Manager |
| FR24 | Laporan | Owner, Branch Manager |
| FR25 | Sistem | All (view: Owner) |
| FR26 | Sistem | Owner, system scheduler |

---

## 1. Authentication and User Management

### FR1 Login
- **Requirement:** A user SHALL log in with `username` and `password`, with an optional remember-me flag.
- **Acceptance criteria:**
  - Valid credentials create an authenticated session; invalid credentials return a generic error.
  - Login rejects an inactive user and a non-owner user whose assigned branch is inactive, trashed, or missing, all through the generic `auth.failed` response.
  - Owners skip the branch check. A non-owner with no branch keeps the current behavior: login is allowed and branch-scoped actions keep their documented denial or empty results.
  - Login is rate limited to 5 attempts per username and IP.
  - After login, `/dashboard` renders the dashboard for the user's role.
- **Traceability:** NFR5, PB-08.

### FR2 Add user
- **Requirement:** Owner and Branch Manager SHALL create user accounts. A manager SHALL only create cashier, designer, or production users in their own branch.
- **Acceptance criteria:**
  - Fields: name, username, email, phone, address, role, branch, password.
  - Username is unique; password is hashed.
  - A manager cannot create an owner or another manager, and cannot assign another branch.
  - Activity is written to the audit log and `OrderUpdated` is dispatched.
- **Traceability:** NFR5, PB-08.

### FR3 Logout and change own password
- **Requirement:** Every authenticated user SHALL log out and change their own password.
- **Acceptance criteria:**
  - Logout invalidates the session and regenerates the CSRF token.
  - Password change requires the current password and a confirmed new password.
- **Traceability:** NFR5, PB-08.

### FR4 Update user, role, and deactivate account
- **Requirement:** Owner and Branch Manager SHALL update user data, assign roles, and activate or deactivate accounts. A manager SHALL only manage branch staff.
- **Acceptance criteria:**
  - Only an owner can manage owner accounts; Owner may manage all users.
  - Manager may manage cashier, designer, and production users in their own branch only.
  - Deactivation sets `users.is_active = false` and keeps the row; an owner can reactivate the account. Deactivation blocks new logins and takes effect on the user's next authenticated `/api/v1` request.
  - A manager may toggle own-branch staff status but cannot delete or restore users. Soft delete and restore remain owner-only administrative APIs with no normal UI.
  - The last active owner (`role = owner`, `is_active = true`, not trashed) cannot be deactivated, deleted, or demoted: profile deactivation, `DELETE /users/{user}`, and demotion through `PUT /users/{user}` return 422 with `Pemilik aktif terakhir tidak dapat dinonaktifkan, dihapus, atau diturunkan perannya.` The status route cannot emit that guard message: a sole owner toggling themselves hits the self-toggle rule, and any other target implies a second active owner.
  - A user cannot deactivate their own account through the user management status route. Profile deactivation remains available with the current password.
  - Status changes are audited with before/after `is_active`. Password is optional on update; audit payloads exclude it.
- **Traceability:** NFR5, PB-08.

### FR5 Role-based access control
- **Requirement:** The system SHALL restrict menus, data, and actions by role and branch.
- **Acceptance criteria:**
  - Five roles are enforced: owner, manager, cashier, designer, production.
  - Branch-bound users cannot read or mutate another branch's data.
  - Unauthorized actions return HTTP 403.
  - Production users do not see price, cost, or profit amounts.
  - The role and branch scope are derived server-side, never from a client-writable field.
- **Traceability:** NFR5, NFR9, PB-08.

---

## 2. Products

### FR6 Manage products, categories, materials, and prices
- **Requirement:** Owner SHALL manage products, product categories, materials, attribute types, attributes, and global product quantity tiers (now configured inline on the product form). Branch Manager SHALL read products and their inline quantity tiers through a read-only `Lihat` modal, read categories, attribute types, and quantity tiers, manage own-branch attributes, create materials, and manage branch stock.
- **Acceptance criteria:**
  - Product CRUD includes name, category, `selling_unit` (one of the seven SATUAN values `m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, `batang`; default `pcs`), compatible material mapping (`material_ids[]`), and active status. Only `m2` selects area (`width * length`) pricing; every other value uses per-unit pricing. It also includes `requires_material` and `uses_attributes` (both boolean, default true) and the `attribute_type_ids[]`/`attribute_ids[]` mappings synced to `attribute_type_product`/`attribute_product`. An empty mapping means all active types/attributes; `uses_attributes = false` disables attributes while preserving the mappings. These writes are Owner-only; the Manager product page is read-only and shows `Semua bahan`, `Semua tipe`, `Semua atribut` or comma-separated names from the resource's display-only name arrays. The product table shows `Tanpa bahan` when `requires_material = false` and `Tanpa tipe` / `Tanpa atribut` when `uses_attributes = false`; otherwise it shows the current names or `Semua bahan` / `Semua tipe` / `Semua atribut` for an empty mapping. Product create and update also accept an optional `tiers[]` array of `{min_qty, discount_percent}` rows (with `id` allowed on update) written atomically with the product; zero tiers is valid. On `PUT /products/{id}` an absent `tiers` key or `tiers: []` clears every tier row, so a client that wants to preserve tiers must send the full array. In the product modal, switching `Perlu bahan` or `Gunakan atribut` off hides the `Bahan yang Kompatibel`, `Tipe Atribut yang Kompatibel`, and `Atribut yang Kompatibel` boxes without erasing their stored IDs from form values or the submitted payload. The standalone `Tier Jumlah` pages are removed; `/owner/quantity-tiers`, `/manager/quantity-tiers`, and `/manager/pricing` redirect to the respective products pages, while the tier REST endpoints remain available to Owner (write) and Manager (read).
  - Product create and update also accept optional `price` and `cost`, each validated `sometimes|numeric|min:0` and stored as a `decimal(12,2)` money column defaulting to `0.00`. Both are additive to the derived material/attribute price and cost, never a replacement, and neither requires the other; a material product with a filled base cost still reprices from the full chain. The columns are global across branches (per-branch service pricing is deferred). `ProductResource` exposes `price` to Owner, Manager, Cashier, and Designer, and `cost` only to Owner and Manager; Production receives neither. The product form always shows both fields with a default of `0.00` plus a non-blocking hint `Biaya dasar lebih besar dari harga dasar — cek kembali` when base cost exceeds base price and cost is greater than zero; the hint never blocks saving and never implies a final profit check. Product create/restore and update audit snapshots include both fields.
  - Category CRUD includes name, description, active status, and the derived product count; categories are never deleted, so `is_active` is the retire mechanism. Legacy `calculation_archetype` and `allowed_attribute_types` remain dead columns but are removed from the category form and API. Quantity discount tiers are removed.
  - Products use the same retire mechanism (`is_active`). An active product may still be created or edited under an inactive category (no 422 guard); it stays hidden from the designer option list until the category is active again.
  - The designer `/quotes/options` list follows catalog visibility: active categories only, active products in an active or null category, globally active materials in an active category with an active branch-stock row, and active attributes unless `include_inactive_attributes=1`. The response omits `products_count`; product categories report `is_active`, while material categories remain `{id,name}`. Attribute objects omit `cost` for every non-owner/manager actor (cashier and production too), though only designers can reach quote options; Owner and Manager attribute lists keep it.
  - Material master includes name, category, unit, type, dimensions, base cost, and base price. The allowed units are exactly `m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, and `batang`; `m2` displays as `m²`. Create requires `unit`; update accepts it optionally. The column defaults to `pcs`.
  - Materials have a global `is_active` flag (default true), and `material_branch_stock.is_active` (default true) marks availability in a branch. The Owner toggles the global flag; Owner any branch and Manager own branch toggle branch availability. Neither toggle writes a `stock_ledgers` row or changes `current_stock`. The materials screen has no Hapus; the Owner-only delete/restore API remains recovery-only. `available_total` and the manager `Bahan Aktif` count include only globally active materials; a resolved named branch additionally requires an active branch-stock row there, while an owner request with `branch_id=all` or omitted counts globally active materials only.
  - Existing materials are backfilled by `MaterialUnit::backfill()`. The first match wins: material name keywords (`banner`, `vinyl`, `flexi`, `backdrop`, `spandex`, `spanduk` -> `m2`; `kertas`, `paper`, `hvs`, `art paper`, `sticker`, `stiker` -> `lembar`; `tinta`, `ink`, `solvent`, `pelarut` -> `liter`), then the same keyword sets against the category name, then a `surface_area` category archetype -> `m2`, otherwise `pcs`. A null or legacy value reads as `pcs`.
  - Attribute types and attributes are master data. `attribute_types` stores `name` (unique, max 100), an immutable `slug` (unique, max 100, generated from the name), `sort_order`, `is_active`, and timestamps; Owner has type CRUD and Manager reads the list, and there is no delete (deactivate instead). The migration seeds `finishing`, `cut` (`Potong`), and `display` and backfills every other non-empty legacy `attributes.type` with its exact slug and a headline name. Attributes store `type` as the immutable `attribute_types.slug` with no foreign key; requests validate `exists:attribute_types,slug` and max 100. Creating an attribute requires an active type; updating keeps the current type even when it is inactive, and a changed type must be active. `AttributeResource` adds `type_name`. Cut is first-class, so legacy cut rows are editable. Types are not limited to finishing and display.
  - The price source is computed, never selected from a matrix: `base = products.price + materials.price + sum(selected active attributes.price)`, a Reseller line applies the branch reseller percent to that base, `line_subtotal = base_after_reseller * quantity * area_factor` where `area_factor = width * length` only for `selling_unit = m2` and `1` otherwise, then the highest eligible global product quantity tier (`min_qty <= quantity`) applies as `subtotal * (1 - tier_percent/100)`. Cost mirrors the chain from `products.cost` plus `materials.cost` plus attribute costs without commercial discounts (still multiplied by quantity and area factor), and `profit = subtotal - cost`. `products.price` and `products.cost` default to `0.00` and are additive, leaving the reseller/quantity/area discount order unchanged; cost is never discounted. `discount_amount` is the combined undiscounted subtotal minus the final subtotal. All money is recomputed server-side and transported as two-decimal strings.
  - `selected_attributes` is the only attribute selection; legacy `finishing_id`/`cut_id`/`display_id` keys are gone. A selected inactive attribute is accepted only for an unchanged invoice line already using it.
  - Invoice creation reads the customer type from the customer record; quotes default to `Reguler` and accept a `customer_type` request value. `branches.reseller_discount_percent` (falling back to `branches.settings.reseller_discount_percent`) applies to Reseller lines and is snapshotted on each order together with the tier percent that was used.
  - `branches.reseller_discount_percent` (`0–100`, default `0`) is the reseller-discount source, mirrored into `branches.settings.reseller_discount_percent` by `BranchService` and editable on `/owner/branches`. `Branch::getResellerDiscountPercent()` falls back to `settings` for legacy rows. The invoice branch's value applies.
  - Machine masters and machine selection are removed; the `machines`, `machine_pricing`, `pricing`, and `attribute_pricing` tables are dropped, along with the `/pricing` endpoints and the `orders.pricing_id` column. Dropped rows are unrecoverable; deployment requires a verified backup.
  - Global product quantity tiers carry `min_qty` (`1..100000`, distinct within a payload) and `discount_percent` (`0..100`, a two-decimal string), are unique per `(product_id, min_qty)`, and are Owner-write / Manager-read. They have no branch dimension. `tiers[]` is validated in the product FormRequests, written inside the product transaction, and read back through `ProductResource` for Owner, Manager, and Designer only; Cashier and Production receive no `tiers` key. Product create and update audit snapshots include the ordered tier rows.
  - Category and product changes are Owner-only; product create/update/delete/restore, category create/update, material create/update/delete/restore/stock, the material global active toggle, and stock adjustment writes are written to the audit log, while attribute, attribute-type, minimum-stock, and material branch-availability writes remain unaudited. Tier changes made through the product form are captured in the product audit snapshot (`before`/`after` include `tiers`); standalone tier REST writes remain unaudited. Managers manage own-branch attributes and read quantity tiers; global attributes are read-only, attributes cannot be moved to another branch after creation, and the attribute delete endpoint is removed. Branch stock changes are branch-scoped.
- **Traceability:** NFR1, NFR7, PB-01.

### FR7 Search, filter, and paginate products
- **Requirement:** Designer and Cashier SHALL search, filter, and browse products with pagination.
- **Acceptance criteria:**
  - Search by name and category; filter by category and active status.
  - Server-side pagination at 10 products per page with previous/next navigation.
  - Results load within the NFR1 target.
  - Selecting a product feeds the order calculator or checkout.
- **Traceability:** NFR1, PB-01.

---

## 3. Orders

### FR8 Create order
- **Requirement:** Designer SHALL record a new order with customer, items, specifications, quantity, and target completion date; Owner, Branch Manager, and Designer SHALL manage customer masters on one shared screen; Cashier SHALL view customer masters read-only.
- **Acceptance criteria:**
  - Pesanan Baru is search-first: the customer query stays disabled until a non-empty name/phone fragment is typed, and an empty search shows `Cari pelanggan untuk memilih.` with no customer rows. Customer creation and editing happen on the shared `/customers` page, opened by both `Tambah Pelanggan` and `Buat pelanggan baru`; there is no separate designer create page.
  - The shared `/customers` page is paginated and guarded for owner, manager, designer, and cashier. Owner, Manager, and Designer can create and edit customers; Cashier sees a read-only list with no create or edit controls; Production is denied the page and the endpoints.
  - Customer master requires `name` and at least one institution label; phone, email, and address are optional. `PUT /customers/{customer}` is full replacement: every field is overwritten from the request, omitted optional fields are cleared to null, and an omitted `type` resets to `Reguler`.
  - Institution labels are exactly `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`, `Bisnis/Perusahaan`, and `Organisasi/Yayasan` (in that order). A customer may hold several labels; the first after normalization is mirrored into the legacy singular `institution` field.
  - The order picker shows the chosen customer's labels under `Institusi pesanan`. Invoice creation validates the requested label against that customer's labels (422 on `institution` when it is not a member), defaults to the customer's first label when omitted, and stores a snapshot that later customer edits do not change.
  - Each item stores its own `product_id`, nullable `material_id`, `customer_type`, `reseller_discount_percent`, `quantity_discount_percent`, and `discount_amount` snapshot alongside quantity, area, `unit_price`, `subtotal`, `cost`, `profit`, and file reference. Attribute selections are dynamic across all active types, persisted in `orders.selected_attributes`, and limited to one attribute per type; there is no fixed cap and no `order_attributes` price/cost snapshot. Lines created before the redesign keep their money snapshots but render null product/material names.
  - Item entry follows Product -> Material -> Atribut; machine selection is gone. When a product defines compatible materials (`material_product`), the Designer material selector filters to those mapped materials (falling back to all branch materials when no mapping exists). The option lists hide inactive categories and the products/materials they contain; material options also require the global active flag and an active branch-stock row. The new-order page offers one select per active type in `sort_order`, not a fixed finishing/display pair. The designer edit page requests `include_inactive_attributes=1` with its own query key, so a saved inactive attribute and its inactive type stay selected and removable without being offered for new rows.
  - The designer new-order and edit forms filter attribute types and attributes by the product's `attribute_type_ids`/`attribute_ids`, reset incompatible material and attribute selections when the product changes, and send a null `material_id` when the product has `requires_material = false`. A null-material quote skips the material availability check but still requires an active product and a positive quantity; a material-required product without a material returns 422 on `material_id`. A product with `uses_attributes = false` hides the attribute selects, and new lines carrying `selected_attributes[]` are rejected with 422 under `selected_attributes`.
  - New quote and invoice lines require a globally active material with an active branch-stock row. An inactive, branch-inactive, soft-deleted, or missing-stock material returns 422 on `material_id` (quote) or `items.{index}.material_id` (invoice), including manual-price lines.
  - Quote and invoice lines use the authoritative formula from FR6: the product's base `price`/`cost` (default `0.00`) add to the selected material and attribute price/cost before the reseller discount, quantity multiplier, area factor, and highest eligible quantity tier; cost is never discounted. The six per-line snapshots and the `/quotes` and `/invoices` response shapes are unchanged, and no client-supplied price is trusted.
  - Attribute types and attributes are masters with their own `price`/`cost`; the quote/invoice API accepts `selected_attributes[]`, rejects more than one attribute of a type, and stores the selection on the order.
  - For area calculations, width and length are separate inputs; area is computed server-side as `width * length`.
  - Submission creates one invoice, its orders (including `width`, `length`, and `selected_attributes`), initial status logs, and an activity log in one transaction. No `order_attributes` rows are written.
  - Invoice number follows `INV-{branch prefix}-{YYYYMMDD}-{NNNN}`.
  - A printable A4 invoice is available. The invoice is the single customer document; the production queue is the production work list and never exposes money.
- **Traceability:** NFR3, NFR9, PB-02.

### FR9 Update order before production
- **Requirement:** Designer SHALL update an order that has not entered production and is not fully paid, including orders that have a down payment, with a change trail.
- **Acceptance criteria:**
  - Allowed only while every order on the invoice has `status = 0` and the invoice status is `0` or `1`.
  - A request on a fully paid invoice (`status = 2`), after production starts, or for an invoice containing an `is_manual_price` order is rejected with HTTP 409.
  - The designer list and edit page share one eligibility check with exactly four Indonesian lock reasons: `lunas`, `sudah diproduksi`, `tarif tidak tersedia`, and `harga manual`. The list shows `Terkunci — <reason>` instead of the `Edit` link when locked, and the edit page shows the `Invoice tidak dapat diubah` empty state; the backend 409 rule is unchanged.
  - The Designer may add, remove, or change items, quantities, specifications, and the deadline. An omitted `deadline` preserves the stored value; an explicit `null` clears it.
  - Invoice `grand_total`, `total_cost`, `total_profit`, and `accounts_receivable` are recalculated; existing payments are preserved.
  - Invoice status is recalculated: `2` when the receivable is zero, `1` when a receivable remains after a payment.
  - The remaining receivable cannot become negative. If the new total is less than the total already paid, the request is rejected with HTTP 422.
  - An invoice edit may reuse an inactive material already present on the locked existing invoice, including a new line that reuses that material; a different unavailable material returns 422. A soft-deleted material is always rejected, even when an existing order used it, so the allowance applies only to inactive-but-present materials. The allowance is derived only from the server-locked existing orders; no client flag widens it.
  - An invoice edit may also keep any inactive attribute or inactive attribute type already selected on the locked existing orders. This allowance is applied only to whole unchanged invoice lines (narrowed per plan), not invoice-wide, and is server-derived; no client field widens it. Any other inactive or unavailable attribute returns 422 on `items.{index}.selected_attributes`, and duplicate attributes of one type are rejected. There is no fixed selected-attribute cap; the old `max:3` is gone.
  - Product capability checks are grandfathered only for an entire unchanged line: the server compares the locked order's `product_id`, `material_id`, and normalized (lowercased, de-duplicated, sorted) `selected_attributes` set. Unchanged lines skip the material requirement, compatible-material, allowed-type, compatible-attribute, and `uses_attributes` checks. Any new line, changed product, changed material, or changed/added attribute selection must satisfy the product and returns 422 on `items.{index}.product_id`, `items.{index}.material_id`, or `items.{index}.selected_attributes` when incompatible. The decision is derived from locked orders, never from a client flag.
  - Before/after values are written to the audit log with the actor and branch.
  - `OrderUpdated` is dispatched after commit.
- **Traceability:** NFR3, NFR9, NFR10, PB-02.

### FR10 Update design status
- **Requirement:** Designer SHALL update the design status of an order.
- **Acceptance criteria:**
  - Statuses: `pending` (new), `in_design` (Proses Desain), `approved` (Desain Disetujui).
  - Allowed transitions: `pending -> in_design -> approved`; no backward transition without a manager/owner override.
  - Approval stores `design_approved_at` and `design_approved_by`.
  - Each transition writes an audit/status log and dispatches `OrderUpdated`.
- **Traceability:** NFR9, NFR10, PB-02.

---

## 4. Payments and Expenses

### FR11 Process down payment and settlement
- **Requirement:** Cashier SHALL process down payments and settlements with amount validation.
- **Acceptance criteria:**
  - Amount must be numeric, greater than zero, and not exceed the remaining receivable.
  - Methods: Cash, Transfer, QRIS.
  - A settlement may include a discount that does not exceed the receivable.
  - Invoice status updates to `1` when a receivable remains and `2` when fully paid.
- **Traceability:** NFR3, NFR9, PB-03.

### FR12 Multiple down payments until paid
- **Requirement:** Cashier SHALL register more than one down payment until the invoice is fully paid, with automatic remaining calculation.
- **Acceptance criteria:**
  - Each down payment is a separate `payments` row of type `down_payment`.
  - `dp_amount` is the sum of all down payments.
  - `accounts_receivable = max(0, grand_total - sum(all payments))`.
  - A down payment equal to the full total moves the invoice to status `2`.
  - Duplicate or over-limit payments are rejected.
- **Traceability:** NFR3, NFR9, PB-03.

### FR13 Display and print invoice and payment receipt
- **Requirement:** Owner, Branch Manager, Cashier, and Designer SHALL display and print the invoice and the payment receipt.
- **Acceptance criteria:**
  - The invoice includes invoice number, customer, items, atribut, quantity, area, price, discount, total, DP history, and remaining receivable.
  - The invoice prints on A4. The invoice is the single customer document; no separate work-order ticket is produced.
  - The payment receipt prints on 80 mm paper and includes invoice number, customer, items, total, DP history, settlement, total paid, and remaining receivable.
  - Production is denied both print views because they contain money fields. Production uses the queue without money.
- **Traceability:** NFR4, PB-04.

### FR14 Record operational expenses
- **Requirement:** Owner and Branch Manager SHALL record branch operational expenses. Cashier SHALL NOT create expenses.
- **Acceptance criteria:**
  - Fields: amount, type (`modal` or `laba`), incurred date, category, description.
  - Category is a dropdown: `Bahan Baku`, `Gaji`, `Sewa`, `Listrik & Air`, `Maintenance`, `Transport`, `Marketing`, `Operasional`, `Lain-lain`.
  - A manager writes to their own branch. An owner selects the branch.
  - The expense stores the actor user id and a name snapshot.
  - The write is audited.
- **Traceability:** NFR3, PB-04.

---

## 5. Inventory

### FR15 Record raw-material purchases
- **Requirement:** Owner and Branch Manager SHALL record raw-material purchases that add stock to the branch.
- **Status:** Built (PB-05/PB-07, 2026-11-24).
- **Acceptance criteria:**
  - Purchase header: branch, actor, purchase date, optional supplier, notes.
  - Line items: material, quantity, unit cost, subtotal.
  - The purchase, its items, the stock increment, and the stock ledger are written in one transaction.
  - Ledger type is `purchase`, with a reference to the purchase.
  - Activity is written to the audit log.
- **Traceability:** NFR9, PB-05.

### FR16 Record material usage on an order
- **Requirement:** Production SHALL record material usage on an order, and stock SHALL decrease automatically. Failed prints SHALL be recorded from the production queue.
- **Status:** Built (PB-05/PB-07 follow-up, 2026-11-25).
- **Acceptance criteria:**
  - Usage references the order, the material stored on that order (`orders.material_id`, eager-loaded as `order.material`), quantity, and operator. A soft-deleted material still keeps the id but renders null, so production start rejects it; a null material is valid only when the order's product has `requires_material = false`, skips stock deduction, and writes `material_usage = []`.
  - Production start deducts by the material unit: `m2` deducts `area * quantity` and returns 422 when the order area is not greater than zero; every other unit deducts `quantity`.
  - A failed print is recorded from the queue row, not from a separate page. It references the order, material, quantity, operator, optional designer, and notes.
  - Waste is allowed on order status `0`, `1`, or `3`; a completed order (status `2`) is rejected with 422.
  - Failing a processing order (`1 -> 3`) reverses the net usage per `(material_id, branch_id)` across `usage` and `usage_reversal` ledger rows and writes one `usage_reversal` row per material with positive net usage. Reopening (`3 -> 0`) clears the operator and returns the order to the queue.
  - Machine references are removed; there is no machine selection or machine cost.
  - Stock decrement, reversal, and ledger entry happen in one transaction.
  - Ledger type is `usage`, `usage_reversal`, or `waste`, with an order reference.
- **Traceability:** NFR9, PB-07.

### FR17 View branch stock and low-stock warnings
- **Requirement:** Owner, Branch Manager, and Production SHALL view raw-material stock per branch and receive a warning when stock is below the minimum.
- **Status:** Built (PB-05/PB-07, 2026-11-24).
- **Acceptance criteria:**
  - Stock is shown per branch with current stock and minimum stock.
  - Low-stock items are highlighted or listed separately.
  - A warning appears when `current_stock <= min_stock`.
  - Owner and Branch Manager can edit the branch minimum-stock threshold without a stock-ledger movement or activity log; `current_stock` is unchanged by the edit, and the branch-stock row must already exist.
  - The manager materials `Bahan Aktif` stat reads `available_total`, and the manager dashboard `Bahan Tersedia` uses the same available count: globally active materials with an active own-branch stock row. The production list is unchanged and exposes no availability flags or count.
  - Owner may view all branches or one branch.
- **Traceability:** NFR4, NFR9, PB-05.

### FR18 Adjust stock with a reason
- **Requirement:** Owner and Branch Manager SHALL correct branch stock with a mandatory reason.
- **Status:** Built (PB-05/PB-07, 2026-11-24).
- **Acceptance criteria:**
  - The adjustment is available in the materials/stock screen.
  - Adjustment can set a new quantity or apply a signed delta.
  - Reason is required (3–255 characters).
  - New stock cannot be negative.
  - Ledger type is `adjustment` and includes the reason, actor, and branch.
  - Activity is written to the audit log.
- **Traceability:** NFR9, PB-05.

---

## 6. Production

### FR19 Production queue sorted by deadline and priority
- **Requirement:** Owner, Branch Manager, Cashier, Designer, and Production SHALL view a production queue ordered by target completion date and priority, with a status filter.
- **Acceptance criteria:**
  - Sort: `created_at ASC`, then `id`. The target rule is `deadline ASC (nulls last)`, `priority DESC`, `created_at ASC`; it is not implemented.
  - The implemented queue returns status `0`, `1`, and `3`; status `2` is excluded. `status` accepts 0/1/3 (422 otherwise) and `search` matches invoice number, file name, or exact order UUID. The owner may also filter by branch.
  - Orders whose invoice status is `0` (Belum Dibayar) are excluded from the queue and from all four `/production/summary` counts; invoices with DP (`1`) or Lunas (`2`) remain visible.
  - Each row shows order, customer, deadline, status, designer, and operator.
  - Branch-bound users see only their branch; owner may view all or one branch.
  - Statuses: `0` (Antre Produksi), `1` (Diproses), `3` (Gagal). Status `2` (Selesai) is not queued.
  - The queue is the production work list and contains no money fields. Failed rows offer `Proses Ulang`; waste can be recorded after a failure.
  - A failed row with no `wasted_materials` row exposes `has_waste: false` and shows a `Belum ada bahan terbuang` warning badge. Closing its auto-opened waste modal asks `Belum ada bahan terbuang. Tetap tutup?`; saving waste clears the badge after the queue refetches.
- **Traceability:** NFR1, NFR4, PB-06.

### FR20 Update production status
- **Requirement:** Production SHALL update the order status through the production stages from an explicit status control.
- **Acceptance criteria:**
  - Transitions: `0 -> 1` (start), `1 -> 2` (complete), `1 -> 3` (fail), and `3 -> 0` (reopen).
  - Start requires status `0`; complete and fail require status `1`; reopen requires status `3`. Any other source status returns 422 with no status change and no stock movement.
  - Fail requires a `note` of 3–255 characters, keeps the operator, and reverses net usage. Reopen takes no body, clears the operator, and writes the status-log note `Production reopened`.
  - Each transition writes `order_status_logs` with actor and timestamp.
  - Stock usage is recorded when processing starts, using the material unit.
  - `OrderUpdated` is dispatched after commit for every transition.
- **Traceability:** NFR9, PB-06.

### FR21 Mark order as picked up
- **Requirement:** Cashier SHALL mark a paid order as picked up by the customer.
- **Acceptance criteria:**
  - Allowed only when the invoice is fully paid (`status = 2`) and not yet picked up.
  - Invoice and all related orders store `pickup_status = 1` and `pickup_date`.
  - Branch check is enforced.
  - `OrderUpdated` is dispatched.
- **Traceability:** NFR9, PB-06.

---

## 7. Reporting

### FR22 Dashboard summary
- **Requirement:** Owner and Branch Manager SHALL see a dashboard summary with revenue, expenses, order count, and production status. Owner may view all branches or one branch.
- **Acceptance criteria:**
  - Cards: gross revenue, expenses, receivables, order count, queued/processing/completed counts.
  - The production summary exposes `failed` (orders currently in status `3`). The summary and production dashboard expose `waste_today_by_unit`, an array of `{ unit, quantity }` for today grouped by material unit. Mixed units are never summed; an empty result renders as `-`.
  - Period filter: daily, weekly, monthly.
  - Branch filter for owner; manager is fixed to their branch.
  - Data refreshes on `OrderUpdated`.
- **Traceability:** NFR1, NFR4, PB-09.
- **Implementation note (PB-09, 2026-11-27):**
  - `DashboardRequest` validates `period` (`daily|weekly|monthly`) and enforces `403` when a non-owner has no branch or passes `branch_id=all` / another branch.
  - `DashboardQuery` returns `period`, `finance.receivables`, `order_count`, and `production` (`queued`, `processing`, `completed`, `failed`) for both `owner` and `manager` (alongside manager `finance` `gross_revenue` and `expenses`); `OwnerDashboardPage` and `ManagerDashboardPage` render these metrics and refresh via `OrderUpdated`.

### FR23 Periodic financial reports
- **Requirement:** Owner and Branch Manager SHALL view periodic financial reports filtered by period and branch.
- **Acceptance criteria:**
  - Reports include gross revenue, base cost, net profit, expenses, and margin.
  - Period: daily, weekly, monthly, and custom range.
  - Owner may view all branches or one branch; manager is branch-scoped.
  - Values reconcile with payments, invoices, waste, and expenses.
- **Traceability:** NFR1, NFR2, NFR3, PB-09.
- **Implementation note (PB-09, 2026-11-27):**
  - `ReportPolicy::viewFinancialReports` and `ReportFilterRequest` open `/reports/gross-revenue`, `/reports/base-cost`, `/reports/net-profit`, and `/reports/expenses` to both Owner (`branch_id=all` or single branch) and Branch Manager (own branch only; `403` when branchless or targeting `all`/another branch). `/reports/sparkline` remains Owner-only.
  - `ReportFilterBar` provides quick `Harian`, `Mingguan`, `Bulanan`, and `Kustom` presets and hides the branch selector for managers; manager sidebar and routes expose `/manager/{revenue,base-cost,net-profit,expenses}`.

### FR24 Export reports to Excel or PDF
- **Requirement:** Owner and Branch Manager SHALL export filtered reports to Excel or PDF.
- **Acceptance criteria:**
  - Export honors the active period, branch, and report type.
  - Excel exports the summary and detail rows; PDF exports a printable summary.
  - The file is generated server-side and downloaded with a clear filename.
  - Export of 10,000 rows completes within the NFR2 target.
  - Export actions are written to the audit log.
- **Traceability:** NFR2, PB-09.
- **Implementation note (PB-09, 2026-11-27):**
  - `GET /api/v1/reports/{type}/export` (`ExportReportRequest`, `ExportReport` action) exports `gross-revenue`, `base-cost`, `net-profit`, and `expenses` to `.xlsx` via streaming `openspout/openspout` (`ExcelReportExporter`) or A4 `.pdf` via `dompdf/dompdf` (`PdfReportExporter`, capping PDF detail tables at 200 rows per section with an Excel guidance note).
  - Filenames follow `laporan-{type}-{scope}-{start_date}_{end_date}.{xlsx|pdf}`. `AuditLogger` records an `EXPORT` row before streaming starts (`branch_id = null` for owner `all`, or the branch UUID).
  - The 10,000-row staging benchmark remains unmeasured in this environment (see `docs/requirements/nfr.md`).

---

## 8. System

### FR25 Audit log for important changes
- **Requirement:** The system SHALL record an audit trail for important changes, including payments, order changes, and stock adjustments.
- **Acceptance criteria:**
  - Log fields: actor, role, branch, action, model type, model ID, before/after payload, timestamp.
  - Covered actions in code: invoice create/update, payments, pickup, production transitions, expense, purchase create, customer create and update, category and product writes, material create/update/delete/restore/stock and the global active toggle, stock adjustment, waste, user and branch writes, and backup operations. Product create/restore records a single flat audit snapshot with `requires_material`, `uses_attributes`, `material_ids`, `attribute_type_ids`, `attribute_ids`, and the ordered `tiers` rows; only product update records `before`/`after`.
  - Owner can view and filter the audit trail.
  - Audit rows are append-only from the application.
  - **Audit debt:** design status, quantity-tier, attribute, and attribute-type master writes, minimum-stock edits, and the material branch-availability toggle are not logged. In particular, `PATCH /materials/{material}/min-stock` and `PATCH /materials/{material}/branch-availability` write no `activity_logs` row (accepted FR25 debt, see `docs/requirements/implementation-gaps.md`). The retired pricing-matrix write path needs no audit coverage.
- **Traceability:** NFR5, NFR9, PB-10.
- **Implementation note (PB-10, 2026-11-26):**
  - `App\Services\AuditLogger` is the single write path. It records actor, role, branch, action, `model_type`, `model_id`, payload, and timestamp; a null actor writes `username = system`, `role = system`, `user_id = null`, and `branch_id = null`. Descriptions are capped at 255 characters; payload keys such as `password`, `token`, and `secret` are dropped.
  - Eloquent updates and deletes throw `LogicException`. Query-builder mass updates and raw SQL still bypass the guard, by convention. `activity_logs.branch_id` is `nullOnDelete`, and `activity_logs.created_at` is indexed.
  - Covered actions: invoice create/update/manual price, down payment/settlement, pickup, production start/complete/fail/reopen, expense, purchase create, customer create/update, category create/update, product create/update/delete/restore, material create/update/delete/restore/stock and the global active toggle, stock adjustment, waste, user and branch writes, report exports (`EXPORT`), and backup operations. `ACTIVATE` records branch-stock creation and initial stock, and sets an existing inactive stock row active; the global material active toggle writes one `UPDATE` row with the `is_active` before/after. The global toggle audit inherits the actor's `branch_id` (null when the owner has no branch). Product create/restore audit records a single flat `Product::auditSnapshot()` payload; only product update records `before`/`after`. The snapshot carries both flags (`requires_material`, `uses_attributes`) and all three pivot ID arrays (`material_ids`, `attribute_type_ids`, `attribute_ids`).
  - Not covered: design status (its endpoint and columns do not exist), quantity-tier, attribute, and attribute-type master writes, minimum-stock edits, and the material branch-availability toggle (accepted FR25 debt). Pricing-matrix writes no longer exist.
  - The owner list/search/sort stays global and owner-only. The SPA adds a "Lihat" detail modal over the list payload; there is no extra detail fetch. Audit rows are kept in full: retention and archive-then-delete are deferred until the first real backup size is known.

### FR26 Manual and scheduled database backup
- **Requirement:** Owner SHALL run a manual database backup, and the system SHALL run scheduled automatic backups.
- **Acceptance criteria:**
  - Manual backup is owner-only and runs outside the request timeout.
  - Scheduled backup runs daily without manual action.
  - Backups are stored outside the application database, with a retention policy.
  - Backup success/failure is logged and visible.
  - A restore procedure is documented and tested.
- **Traceability:** NFR6, PB-10.
- **Implementation note (PB-10, 2026-11-26):**
  - Three backup types, each an independent `backups` row with one `BACKUP` audit row:
    1. Manual by the owner: `POST /api/v1/backups` returns `202` before the dump starts and queues a run with `destination=both` when remote backup is enabled and configured, `destination=local` when remote backup is disabled, and `422` (no row) when remote backup is enabled but unconfigured.
    2. Scheduled local: `backup:tick` queues a local run when the owner-set local time is due and enabled.
    3. Scheduled remote (R2/S3): `backup:tick` queues a remote-only run when the owner-set remote time is due and enabled.
  - Schedules and retention are stored in `backup_settings` and read on every `backup:tick` run, so owner changes apply without a restart; a missed run catches up on the next tick. The tick runs every minute with a five-minute `withoutOverlapping` mutex. Defaults: local 02:00 and remote 02:30 in the application timezone.
  - Retention is GFS per destination: defaults local 30/12/12/3 and remote 7/4/12/3 (daily/weekly/monthly/yearly), owner-editable. Pinned files are exempt, and each destination always keeps its newest file. A window below 1 fails closed with an error instead of deleting extra files.
  - Download is owner-only, local first with R2 fallback, streamed as the stored filename, and returns `410` when neither file exists. Pin/unpin is owner-only. Attempted downloads write a `DOWNLOAD` audit row before streaming.
  - R2 usage against the configured quota is shown with `remote_usage_percent = usage / quota * 100`; the page warns at 80% and 100%. The app never deletes archives to meet the quota.
  - Backups are PostgreSQL-only and never stored in the application database. Storage: the `backupdata` volume at `/var/www/html/storage/app/backups` and an S3-compatible bucket. A queue worker is required; without it a run stays `queued`.
  - Remaining gaps: no in-app restore, no client-side encryption, uploaded files are not backed up, no stale-run cleanup, live R2 upload unverified, dump size unknown, and the restore drill has not been run. See `docs/requirements/nfr.md` and `docs/requirements/implementation-gaps.md`.

---

## 9. Traceability to Product Backlog

| FR | Backlog | Sprint |
| :--- | :--- | :--- |
| FR1, FR2, FR3, FR4, FR5 | PB-08 | Sprint 1 |
| FR6, FR7 | PB-01 | Sprint 1 |
| FR8, FR9, FR10 | PB-02 | Sprint 1 |
| FR11, FR12 | PB-03 | Sprint 1 |
| FR13, FR14 | PB-04 | Sprint 1 |
| FR15, FR17, FR18 | PB-05 | Sprint 2 |
| FR16 | PB-07 | Sprint 2 |
| FR19, FR20, FR21 | PB-06 | Sprint 2 |
| FR22, FR23, FR24 | PB-09 | Sprint 3 |
| FR25, FR26 | PB-10 | Sprint 3 |

## Revision History

| Date | Source | Changes |
| :--- | :--- | :--- |
| 2026-11-24 | Sprint Review 1 | FR6, FR8, FR9, FR13, FR14, FR16, FR18, FR19, FR20, and FR25 revised. PB-08 moved to Sprint 1. Details: `docs/requirements/sprint-review-1.md`. |
| 2026-11-25 | PB-05/PB-07 follow-up | FR6 material units and pricing-source truth; FR8 finishing/display truth; FR16 unit-aware usage and reversal; FR19 queue statuses; FR20 fail/reopen transitions; FR22 per-unit waste KPI. Machine masters and tables removed. |
| 2026-11-26 | Unit A catalog cleanup | FR6 category description/active status with no hard delete, attribute types removed, Manager own-branch attributes; FR8/FR9 order-history and inactive-attribute edits; FR17 minimum stock editable per branch; FR25 minimum-stock audit debt. Category quantity-discount tiers removed; product-level tiers move to Unit B. |
| 2026-11-26 | PB-10 | FR25 audit coverage and append-only guard; FR26 three backup types, owner schedules and retention, download, pinning, quota warning. Acceptance text unchanged; implementation notes added. |
| 2026-11-26 | B1a material retirement | FR6 material `is_active` and branch-stock availability toggles with no stock or ledger effect, Hapus removal, `available_total` counts, and designer attribute `cost` omission; FR8 new-line availability enforcement; FR9 invoice-edit allowance for an already-used inactive material; FR17 manager available count; FR25 global toggle audit and branch-availability audit debt. |
| 2026-11-26 | Admin active lifecycle | FR1 login rejection for inactive users and inactive/trashed branches with owner exemption and unchanged null-branch behavior; FR4 active/inactive toggle, owner-only delete/restore, last-active-owner guard on every path, profile deactivation, and status audit. |
| 2026-11-27 | PB-09 | FR22 owner/manager dashboard receivables, order count, and production summary; FR23 branch-scoped financial reports and period presets for managers; FR24 server-side Excel/PDF exports with `AuditLogger` (`EXPORT`). |
| 2026-11-27 | B1b attribute types | FR6 attribute types (Owner CRUD, Manager read), generated immutable slugs, no delete, seed/backfill, `exists` validation, `type_name`, and cut as a first-class type; FR8 dynamic one-per-type selection with no fixed cap and inactive selections preserved on edit; FR9 invoice-wide inactive attribute/type allowance from locked orders; FR25 attribute-type audit debt. |
| 2026-11-27 | B2 product composition | FR6 product requirement/attribute flags, allowed-type and compatible-attribute pivots with empty=all, Owner-only writes and Manager read-only name columns; FR8 product-filtered Designer selections, null-material quote, and `uses_attributes=false` rejection; FR9 whole-unchanged-line invoice-edit grandfathering by `pricing_id` + `material_id` + normalized attributes with new/changed lines enforced (superseded in B4 by order-owned `product_id` + `material_id` + attributes; the column and matrix were dropped); FR16/FR20 no-material production start without stock or usage; FR25 product audit payloads include both flags and both new pivot ID arrays. |
| 2026-11-27 | Customer management | FR8 search-first Pesanan Baru picker, shared paginated `/customers` page (owner/manager/designer write, cashier read-only), six institution labels with multi-label create and full-replacement update, and a server-validated per-order institution snapshot on invoices that survives later customer edits; FR25 customer create and update audited. |
| 2026-11-27 | B4 pricing redesign | FR6 material/attribute price-cost sources, global Owner-managed quantity tiers with Manager read, and removal of the pricing matrix tables, routes, UI, and `orders.pricing_id`; FR8/FR9 server-computed quote formula, per-line product/material/customer-type/discount/money snapshots and order-based whole-line edit grandfathering; FR16 production material sourced from the order; FR25 pricing/attribute audit debt moot with the removed path. |
| 2026-11-28 | Inline product quantity tiers | FR6 optional `tiers[]` on product POST/PUT with one transaction, destructive absent/`[]` PUT semantics, role-gated `tiers` in `ProductResource` (Owner/Manager/Designer), read-only Manager `Lihat` product modal, compatibility boxes hidden via `hideWhen` without dropping pivot IDs, standalone tier pages removed with legacy URLs redirected to products; FR25 product audit snapshots include `tiers`. |
| 2026-11-28 | Service product base price | FR6/FR8 optional product `price` and `cost` (`decimal(12,2)`, default `0.00`), additive to material/attribute price and cost with the reseller/quantity/area chain and undiscounted cost unchanged; role-gated visibility (`price` to Owner/Manager/Cashier/Designer, `cost` to Owner/Manager only, none to Production), global pricing with per-branch deferred, and a non-blocking base-cost hint in the product form. |
