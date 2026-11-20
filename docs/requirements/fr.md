# Functional Requirements (Target Specification)

- **System:** Print Shop POS (Alja Digital Printing)
- **Source of truth:** Capstone proposal FR1–FR26.
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
| FR6 | Produk | Owner, Branch Manager |
| FR7 | Produk | Designer, Kasir |
| FR8 | Pesanan | Designer |
| FR9 | Pesanan | Designer |
| FR10 | Pesanan | Designer |
| FR11 | Pembayaran | Kasir |
| FR12 | Pembayaran | Kasir |
| FR13 | Pembayaran | Kasir |
| FR14 | Pengeluaran | Kasir |
| FR15 | Inventaris | Owner, Branch Manager |
| FR16 | Inventaris | Produksi |
| FR17 | Inventaris | Owner, Branch Manager, Produksi |
| FR18 | Inventaris | Branch Manager |
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

### FR6 Manage products, categories, and prices
- **Requirement:** Owner and Branch Manager SHALL manage products, product categories, and prices.
- **Acceptance criteria:**
  - Product CRUD includes name, category, pricing type (`unit` or `area`), and active status.
  - Category CRUD includes name, calculation archetype, and allowed attribute types.
  - Price management includes the pricing matrix, customer type, tiered quantity, and machine/attribute modifiers.
  - All changes are branch-scoped where applicable and written to the audit log.
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
  - Customer is selected or created.
  - Each item stores product, material, machine, attributes, quantity, dimensions, price, cost, profit, and file reference.
  - Submission creates one invoice, its orders, initial status logs, and an activity log in one transaction.
  - Invoice number follows `INV-{branch prefix}-{YYYYMMDD}-{NNNN}`.
  - A printable work-order ticket is available.
- **Traceability:** NFR3, NFR9, PB-02.

### FR9 Update order before production
- **Requirement:** Designer SHALL update an order that has not entered production, including orders that are already paid or have a down payment, with a change trail.
- **Acceptance criteria:**
  - Allowed only while every order on the invoice has `status = 0`.
  - The Designer may add, remove, or change items, quantities, specifications, and the deadline.
  - Invoice `grand_total`, `total_cost`, `total_profit`, and `accounts_receivable` are recalculated; existing payments are preserved.
  - Invoice status is recalculated: `2` when the receivable is zero, `1` when a receivable remains after a payment.
  - The remaining receivable cannot become negative.
  - Before/after values are written to the audit log with the actor and branch.
  - A request after production starts is rejected with HTTP 409.
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

### FR13 Display and print payment receipt
- **Requirement:** Cashier SHALL display and print a receipt or proof of payment.
- **Acceptance criteria:**
  - Receipt includes invoice number, customer, items, total, DP history, settlement, total paid, and remaining receivable.
  - Receipt prints on 80 mm paper.
- **Traceability:** NFR4, PB-04.

### FR14 Record operational expenses
- **Requirement:** Cashier SHALL record branch operational expenses.
- **Acceptance criteria:**
  - Fields: amount, type (`modal` or `laba`), incurred date, category, description.
  - Expense inherits the cashier's branch and caches the cashier name.
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
- **Requirement:** Production SHALL record material usage on an order, and stock SHALL decrease automatically.
- **Acceptance criteria:**
  - Usage references the order, material, quantity, and operator.
  - Stock decrement and ledger entry happen in one transaction.
  - A failed print is recorded separately in `wasted_materials`.
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
- **Requirement:** Branch Manager SHALL correct branch stock with a mandatory reason.
- **Acceptance criteria:**
  - Adjustment can set a new quantity or apply a signed delta.
  - Reason is required (3–255 characters).
  - New stock cannot be negative.
  - Ledger type is `adjustment` and includes the reason, actor, and branch.
  - Activity is written to the audit log.
- **Traceability:** NFR9, PB-05.

---

## 6. Production

### FR19 Production queue sorted by deadline and priority
- **Requirement:** Owner, Branch Manager, Cashier, Designer, and Production SHALL view a production queue ordered by target completion date and priority.
- **Acceptance criteria:**
  - Sort: `deadline ASC (nulls last)`, `priority DESC`, `created_at ASC`.
  - Each row shows order, customer, deadline, priority, status, designer, and operator.
  - Branch-bound users see only their branch; owner may view all or one branch.
  - Statuses: `queued` (Antre Produksi), `processing` (Diproses), `completed` (Selesai).
- **Traceability:** NFR1, NFR4, PB-06.

### FR20 Update production status
- **Requirement:** Production SHALL update the order status through the production stages.
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
  - Covered actions: payment create/settle, order create/update, design status, stock purchase/usage/adjustment, user/branch/product changes.
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
| FR1, FR2, FR3, FR4, FR5 | PB-08 | Sprint 3 |
| FR6, FR7 | PB-01 | Sprint 1 |
| FR8, FR9, FR10 | PB-02 | Sprint 1 |
| FR11, FR12 | PB-03 | Sprint 1 |
| FR13, FR14 | PB-04 | Sprint 1 |
| FR15, FR17, FR18 | PB-05 | Sprint 2 |
| FR16 | PB-07 | Sprint 2 |
| FR19, FR20, FR21 | PB-06 | Sprint 2 |
| FR22, FR23, FR24 | PB-09 | Sprint 3 |
| FR25, FR26 | PB-10 | Sprint 3 |
