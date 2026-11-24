# Functional Requirements (Target Specification)

- **System:** Print Shop POS (Alja Digital Printing)
- **Source of truth:** Capstone proposal FR1–FR26, revised by Sprint Review 1 on 2026-11-24. The proposal mapping is retained; changes are recorded in `docs/requirements/sprint-review-1.md`.
- **Document type:** Target requirements. Implementation progress is tracked in `docs/requirements/implementation-gaps.md`.
- **Role mapping:** Pemilik = `owner`, Branch Manager = `manager`, Kasir = `cashier`, Designer = `designer`, Produksi = `production`.

## Summary

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
- **Requirement:** Owner SHALL manage products, product categories, materials, finishing/display options, and quantity discounts. Branch Manager SHALL read the catalog and manage branch stock.
- **Acceptance criteria:**
  - Product CRUD includes name, category, pricing type (`unit` or `area`), and active status.
  - Category CRUD includes name, calculation archetype, allowed finishing/display types, and quantity discount tiers (`min_qty`, `discount_percent`).
  - Material master includes name, category, unit (`m2`, `sheet`, `pcs`), type, base cost, and base price. Materials are created by the Owner in the material master and mapped to a product category.
  - Finishing and display options store `price` and `cost`. Each order item may select at most one finishing and one display option.
  - The base price is the material price plus the selected finishing/display prices. For a Reseller customer, the branch reseller discount is applied to the base price before the category quantity discount. The customer type is read from the customer record, never from the client. The resulting customer type and reseller percentage are stored on the order as a snapshot.
  - `branches.reseller_discount_percent` is the only source of the reseller discount: decimal `0–100`, default `0`, editable by the Owner. The invoice branch's value applies.
  - The category quantity discount uses the tier with the highest `min_qty` less than or equal to the item quantity.
  - The pricing matrix and machine masters are not part of the target.
  - Global catalog changes are Owner-only and written to the audit log; branch stock changes are branch-scoped.
- **Traceability:** NFR1, NFR7, PB-01.

### FR7 Search, filter, and paginate products
- **Requirement:** Designer and Cashier SHALL search, filter, and browse products with pagination.
- **Acceptance criteria:**
  - Search by name and category; filter by category and active status.
  - At least 20 products per page with previous/next navigation.
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
  - Each item stores product, material, finishing (one), display (one), quantity, dimensions, price, cost, profit, and file reference.
  - Item entry follows Product -> Material -> Finishing -> Display. There is no machine selection.
  - For area calculations, width and length are separate inputs; area is computed server-side as `width * length`.
  - Submission creates one invoice, its orders, order attribute selections, initial status logs, and an activity log in one transaction.
  - Invoice number follows `INV-{branch prefix}-{YYYYMMDD}-{NNNN}`.
  - A printable A4 invoice is available. The invoice is the single customer document; the production queue is the production work list and never exposes money.
- **Traceability:** NFR3, NFR9, PB-02.

### FR9 Update order before production
- **Requirement:** Designer SHALL update an order that has not entered production and is not fully paid, including orders that have a down payment, with a change trail.
- **Acceptance criteria:**
  - Allowed only while every order on the invoice has `status = 0` and the invoice status is `0` or `1`.
  - A request on a fully paid invoice (`status = 2`) or after production starts is rejected with HTTP 409.
  - The Designer may add, remove, or change items, quantities, specifications, and the deadline.
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
- **Acceptance criteria:**
  - Purchase header: branch, actor, purchase date, optional supplier, notes.
  - Line items: material, quantity, unit cost, subtotal.
  - The purchase, its items, the stock increment, and the stock ledger are written in one transaction.
  - Ledger type is `purchase`, with a reference to the purchase.
  - Activity is written to the audit log.
- **Traceability:** NFR9, PB-05.

### FR16 Record material usage on an order
- **Requirement:** Production SHALL record material usage on an order, and stock SHALL decrease automatically. Failed prints SHALL be recorded from the production queue.
- **Acceptance criteria:**
  - Usage references the order, material, quantity, and operator.
  - A failed print is recorded from the queue row, not from a separate page. It references the order, material, quantity, operator, optional designer, and notes.
  - Machine references are removed; machine cost is part of material cost.
  - Stock decrement and ledger entry happen in one transaction.
  - Ledger type is `usage` or `waste`, with an order reference.
- **Traceability:** NFR9, PB-07.

### FR17 View branch stock and low-stock warnings
- **Requirement:** Owner, Branch Manager, and Production SHALL view raw-material stock per branch and receive a warning when stock is below the minimum.
- **Acceptance criteria:**
  - Stock is shown per branch with current stock and minimum stock.
  - Low-stock items are highlighted or listed separately.
  - A warning appears when `current_stock <= min_stock`.
  - Owner may view all branches or one branch.
- **Traceability:** NFR4, NFR9, PB-05.

### FR18 Adjust stock with a reason
- **Requirement:** Owner and Branch Manager SHALL correct branch stock with a mandatory reason.
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
  - Sort: `deadline ASC (nulls last)`, `priority DESC`, `created_at ASC`.
  - Filter by status (`queued`, `processing`, `completed`); owner may also filter by branch.
  - Each row shows order, customer, deadline, priority, status, designer, and operator.
  - Branch-bound users see only their branch; owner may view all or one branch.
  - Statuses: `queued` (Antre Produksi), `processing` (Diproses), `completed` (Selesai).
  - The queue is the production work list and contains no money fields.
- **Traceability:** NFR1, NFR4, PB-06.

### FR20 Update production status
- **Requirement:** Production SHALL update the order status through the production stages from an explicit status control.
- **Acceptance criteria:**
  - Transitions: `queued -> processing -> completed`.
  - Each transition writes `order_status_logs` with actor and timestamp.
  - Stock usage is recorded when processing starts.
  - `OrderUpdated` is dispatched.
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
