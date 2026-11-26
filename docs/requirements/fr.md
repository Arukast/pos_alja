# Functional Requirements (Target Specification)

- **System:** Print Shop POS (Alja Digital Printing)
- **Source of truth:** Capstone proposal FR1–FR26, revised by Sprint Review 1 on 2026-11-24. The proposal mapping is retained; changes are recorded in `docs/requirements/sprint-review-1.md`.
- **Document type:** Target requirements. Implementation progress is tracked in `docs/requirements/implementation-gaps.md`.
- **Role mapping:** Pemilik = `owner`, Branch Manager = `manager`, Kasir = `cashier`, Designer = `designer`, Produksi = `production`.

## Summary

Sprint Review 1 Sales acceptance: customer registration is separate from new-order search; institution is required while phone, email, and address remain optional. Designer item configuration follows Product -> Material -> Finishing -> Display. Cashiers have no expense-entry screen; invoice print is the customer-facing print flow.

| ID | Module | Roles |
| :--- | :--- | :--- |
| FR1 | Autentikasi | All |
| FR2 | Autentikasi | Owner, Branch Manager |
| FR3 | Autentikasi | All |
| FR4 | Pengguna | Owner, Branch Manager |
| FR5 | Hak Akses | All |
| FR6 | Produk | Owner, Branch Manager (read) |
| FR7 | Produk | Designer, Kasir |
| FR8 | Pesanan | Designer |
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
- **Requirement:** Owner and Branch Manager SHALL update user data, assign roles, and deactivate accounts. A manager SHALL only manage branch staff.
- **Acceptance criteria:**
  - Owner may manage all users; a primary owner is required to manage owner accounts.
  - Manager may manage cashier, designer, and production users in their own branch only.
  - Deactivation is a soft delete; accounts can be restored.
  - Password is optional on update; audit payloads exclude it.
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
- **Requirement:** Owner SHALL manage products, product categories, materials, and finishing/display options. Branch Manager SHALL read products and categories, manage own-branch attributes and pricing rows, create materials, and manage branch stock.
- **Acceptance criteria:**
  - Product CRUD includes name, category, pricing type (`unit` or `area`), compatible material mapping (`material_ids[]`), and active status. `products.pricing_type` drives area (`width * length`) vs per-unit price calculations.
  - Category CRUD includes name, description, active status, and the derived product count; categories are never deleted, so `is_active` is the retire mechanism. Legacy `calculation_archetype` and `allowed_attribute_types` remain dead columns but are removed from the category form and API. Quantity discount tiers are removed.
  - Products use the same retire mechanism (`is_active`). An active product may still be created or edited under an inactive category (no 422 guard); it stays hidden from the designer option list until the category is active again.
  - The designer `/quotes/options` list follows catalog visibility: active categories only, active products in an active or null category, materials in an active category, and active attributes unless `include_inactive_attributes=1`. The response omits `products_count` and product/material categories report `is_active`.
  - Material master includes name, category, unit, type, dimensions, base cost, and base price. The allowed units are exactly `m2`, `pcs`, `lembar`, `roll`, `kg`, `liter`, and `batang`; `m2` displays as `m²`. Create requires `unit`; update accepts it optionally. The column defaults to `pcs`.
  - Existing materials are backfilled by `MaterialUnit::backfill()`. The first match wins: material name keywords (`banner`, `vinyl`, `flexi`, `backdrop`, `spandex`, `spanduk` -> `m2`; `kertas`, `paper`, `hvs`, `art paper`, `sticker`, `stiker` -> `lembar`; `tinta`, `ink`, `solvent`, `pelarut` -> `liter`), then the same keyword sets against the category name, then a `surface_area` category archetype -> `m2`, otherwise `pcs`. A null or legacy value reads as `pcs`.
  - Finishing and display masters exist as `attributes` with type `finishing` or `display`, and `attribute_pricing` rows supply their price/cost modifiers. The designer form offers finishing/display selection, and the selection is persisted in `orders.selected_attributes`.
  - The current price source is the pricing matrix: a quote selects a `pricing` row by product, material, customer type, and `min_qty`, then adds the selected `attribute_pricing` modifiers. The target model (material price + finishing/display price + reseller discount + category quantity discount) is not implemented.
  - Invoice creation reads the customer type from the customer record; quotes default to `Reguler` and accept a `customer_type` request value. The branch reseller discount is read from `branches.settings.reseller_discount_percent` and applied when a Reseller quote falls back to the Reguler pricing row.
  - `branches.settings.reseller_discount_percent` is the current source of the reseller discount. It is API-only, has no SPA field, and is currently uncapped. The target `branches.reseller_discount_percent` column is not implemented. The invoice branch's value applies.
  - Machine masters and machine selection are removed; the `machines` and `machine_pricing` tables are dropped. The pricing matrix is a target removal and remains the current price source.
  - Category and product changes are Owner-only and written to the audit log. Managers manage own-branch attributes and pricing rows; global attributes are read-only, attributes cannot be moved to another branch after creation, and the attribute delete endpoint is removed. Branch stock changes are branch-scoped.
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
- **Requirement:** Designer SHALL record a new order with customer, items, specifications, quantity, and target completion date.
- **Acceptance criteria:**
  - Customer search is the default. A new customer is created on a separate screen.
  - Customer master requires name and institution; phone, email, and address are optional.
  - Institutions: `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`.
  - Each item stores the selected pricing row (`pricing_id`), quantity, area, price, cost, profit, and file reference. Finishing and display selections are persisted in `orders.selected_attributes`; there is no `order_attributes` price/cost snapshot.
  - Item entry follows Product -> Material -> Finishing -> Display; machine selection is gone. When a product defines compatible materials (`material_product`), the Designer material selector filters to those mapped materials (falling back to all branch materials when no mapping exists). The option lists hide inactive categories and the products/materials they contain. The designer edit page does not yet request `include_inactive_attributes=1`, so a saved inactive finishing/display is not yet preserved for editing.
  - Finishing and display masters exist (attributes plus `attribute_pricing` modifiers), and the quote/invoice API accepts `selected_attributes[]` and stores it on the order.
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
  - The Designer may add, remove, or change items, quantities, specifications, and the deadline. An omitted `deadline` preserves the stored value; an explicit `null` clears it.
  - Invoice `grand_total`, `total_cost`, `total_profit`, quantity discounts, and `accounts_receivable` are recalculated; existing payments are preserved.
  - Invoice status is recalculated: `2` when the receivable is zero, `1` when a receivable remains after a payment.
  - The remaining receivable cannot become negative. If the new total is less than the total already paid, the request is rejected with HTTP 422.
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
  - The invoice includes invoice number, customer, items, finishing/display, quantity, area, price, discount, total, DP history, and remaining receivable.
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
  - Usage references the order, material, quantity, and operator.
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

### FR23 Periodic financial reports
- **Requirement:** Owner and Branch Manager SHALL view periodic financial reports filtered by period and branch.
- **Acceptance criteria:**
  - Reports include gross revenue, base cost, net profit, expenses, and margin.
  - Period: daily, weekly, monthly, and custom range.
  - Owner may view all branches or one branch; manager is branch-scoped.
  - Values reconcile with payments, invoices, waste, and expenses.
- **Traceability:** NFR1, NFR2, NFR3, PB-09.

### FR24 Export reports to Excel or PDF
- **Requirement:** Owner and Branch Manager SHALL export filtered reports to Excel or PDF.
- **Acceptance criteria:**
  - Export honors the active period, branch, and report type.
  - Excel exports the summary and detail rows; PDF exports a printable summary.
  - The file is generated server-side and downloaded with a clear filename.
  - Export of 10,000 rows completes within the NFR2 target.
  - Export actions are written to the audit log.
- **Traceability:** NFR2, PB-09.

---

## 8. System

### FR25 Audit log for important changes
- **Requirement:** The system SHALL record an audit trail for important changes, including payments, order changes, and stock adjustments.
- **Acceptance criteria:**
  - Log fields: actor, role, branch, action, model type, model ID, before/after payload, timestamp.
  - Covered actions: payment create/settle, order create/update, design status, stock purchase/usage/adjustment, expense create, customer create/update, user/branch/product/category/material/discount changes.
  - Owner can view and filter the audit trail.
  - Audit rows are append-only from the application.
- **Traceability:** NFR5, NFR9, PB-10.

### FR26 Manual and scheduled database backup
- **Requirement:** Owner SHALL run a manual database backup, and the system SHALL run scheduled automatic backups.
- **Acceptance criteria:**
  - Manual backup is owner-only and runs outside the request timeout.
  - Scheduled backup runs daily without manual action.
  - Backups are stored outside the application database, with a retention policy.
  - Backup success/failure is logged and visible.
  - A restore procedure is documented and tested.
- **Traceability:** NFR6, PB-10.

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
