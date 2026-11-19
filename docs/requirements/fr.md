# Functional Requirements (Target Specification)

- **System:** Print Shop POS (Alja Digital Printing)
- **Source of truth:** Capstone proposal FR1–FR26, reconciled with the current code.
- **Status legend:**
  - **Implemented** — exists and is demoable.
  - **Partial** — exists for some roles or paths, with a documented gap.
  - **Target** — required for the redevelopment, not implemented yet.
- **Role mapping:** Pemilik = `owner`, Branch Manager = `manager`, Kasir = `cashier`, Designer = `designer`, Produksi = `production`.

## Summary

| ID | Module | Roles | Status |
| :--- | :--- | :--- | :--- |
| FR1 | Autentikasi | All | Implemented |
| FR2 | Autentikasi | Owner, Branch Manager | Partial |
| FR3 | Autentikasi | All | Implemented |
| FR4 | Pengguna | Owner, Branch Manager | Partial |
| FR5 | Hak Akses | All | Partial |
| FR6 | Produk | Owner, Branch Manager | Partial |
| FR7 | Produk | Designer, Kasir | Target |
| FR8 | Pesanan | Designer | Implemented |
| FR9 | Pesanan | Designer, Kasir | Target |
| FR10 | Pesanan | Designer | Target |
| FR11 | Pembayaran | Kasir | Partial |
| FR12 | Pembayaran | Kasir | Partial |
| FR13 | Pembayaran | Kasir | Implemented |
| FR14 | Pengeluaran | Kasir | Implemented |
| FR15 | Inventaris | Kasir | Target |
| FR16 | Inventaris | Produksi | Partial |
| FR17 | Inventaris | Owner, Manager, Kasir, Produksi | Partial |
| FR18 | Inventaris | Branch Manager | Target |
| FR19 | Produksi | All | Partial |
| FR20 | Produksi | Produksi | Implemented |
| FR21 | Produksi | Kasir | Partial |
| FR22 | Laporan | Owner, Branch Manager | Partial |
| FR23 | Laporan | Owner, Branch Manager | Partial |
| FR24 | Laporan | Owner, Branch Manager | Target |
| FR25 | Sistem | All (view: Owner) | Partial |
| FR26 | Sistem | Owner, system scheduler | Target |

---

## 1. Authentication and User Management

### FR1 Login
- **Requirement:** A user SHALL log in with `username` and `password`, with an optional remember-me flag.
- **Acceptance criteria:**
  - Valid credentials create an authenticated session; invalid credentials return a generic error.
  - Login is rate limited to 5 attempts per username and IP.
  - After login, `/dashboard` renders the dashboard for the user's role.
- **Current status:** Implemented, Verified. Evidence: `LoginForm`, `AuthenticationTest`, `UserAuthorizationTest`.
- **Traceability:** NFR5, PB-08.

### FR2 Add user
- **Requirement:** Owner and Branch Manager SHALL create user accounts. A manager SHALL only create cashier, designer, or production users in their own branch.
- **Acceptance criteria:**
  - Fields: name, username, email, phone, address, role, branch, password.
  - Username is unique; password is hashed.
  - A manager cannot create an owner or another manager, and cannot assign another branch.
  - Activity is written to the audit log and `OrderUpdated` is dispatched.
- **Current status:** Partial. The component implements the branch-staff restriction, but `UserManagement::$mode` is client-writable and has no mount guard.
- **Traceability:** NFR5, PB-08.

### FR3 Logout and change own password
- **Requirement:** Every authenticated user SHALL log out and change their own password.
- **Acceptance criteria:**
  - Logout invalidates the session and regenerates the CSRF token.
  - Password change requires the current password and a confirmed new password.
- **Current status:** Implemented, Verified. Evidence: `Logout`, `UpdatePasswordForm`, `PasswordUpdateTest`.
- **Traceability:** NFR5, PB-08.

### FR4 Update user, role, and deactivate account
- **Requirement:** Owner and Branch Manager SHALL update user data, assign roles, and deactivate accounts. A manager SHALL only manage branch staff.
- **Acceptance criteria:**
  - Owner may manage all users; a primary owner is required to manage owner accounts.
  - Manager may manage cashier, designer, and production users in their own branch only.
  - Deactivation is a soft delete; accounts can be restored.
  - Password is optional on update; audit payloads exclude it.
- **Current status:** Partial. Same `mode` bypass as FR2.
- **Traceability:** NFR5, PB-08.

### FR5 Role-based access control
- **Requirement:** The system SHALL restrict menus, data, and actions by role and branch.
- **Acceptance criteria:**
  - Five roles are enforced: owner, manager, cashier, designer, production.
  - Branch-bound users cannot read or mutate another branch's data.
  - Unauthorized actions return HTTP 403.
  - Production users do not see price, cost, or profit amounts.
  - The role and branch scope are derived server-side, never from a client-writable field.
- **Current status:** Partial. Gaps: `UserManagement::$mode` and `MaterialManagement::$branchId` are client-writable; cashier invoice selection lacks a branch assertion; dashboard components do not assert role in `mount()`.
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
- **Current status:** Partial. Categories and pricing matrices exist. There is no product CRUD screen; products are data-setup records. Owner has no pricing tab.
- **Traceability:** NFR1, NFR7, PB-01.

### FR7 Search, filter, and paginate products
- **Requirement:** Designer and Cashier SHALL search, filter, and browse products with pagination.
- **Acceptance criteria:**
  - Search by name and category; filter by category and active status.
  - At least 20 products per page with previous/next navigation.
  - Results load within the NFR1 target.
  - Selecting a product feeds the order calculator or checkout.
- **Current status:** Target. The current calculator uses product category dropdowns and `Product::all()`, with no product list or pagination.
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
- **Current status:** Implemented. Evidence: `OrderService::createInvoice()`, `Designer`, order feature tests.
- **Traceability:** NFR3, NFR9, PB-02.

### FR9 Update order before production
- **Requirement:** Designer and Cashier SHALL update an order that has not entered production, including orders that are already paid or have a down payment, with a change trail.
- **Acceptance criteria:**
  - Allowed only while every order on the invoice has `status = 0`.
  - Users may add, remove, or change items, quantities, specifications, and the deadline.
  - Invoice `grand_total`, `total_cost`, `total_profit`, and `accounts_receivable` are recalculated; existing payments are preserved.
  - Invoice status is recalculated: `2` when the receivable is zero, `1` when a receivable remains after a payment.
  - The remaining receivable cannot become negative.
  - Before/after values are written to the audit log with the actor and branch.
  - A request after production starts is rejected with HTTP 409.
  - `OrderUpdated` is dispatched after commit.
- **Current status:** Target. The current code supports create and read only.
- **Traceability:** NFR3, NFR9, NFR10, PB-02.

### FR10 Update design status
- **Requirement:** Designer SHALL update the design status of an order.
- **Acceptance criteria:**
  - Statuses: `pending` (new), `in_design` (Proses Desain), `approved` (Desain Disetujui).
  - Allowed transitions: `pending -> in_design -> approved`; no backward transition without a manager/owner override.
  - Approval stores `design_approved_at` and `design_approved_by`.
  - Each transition writes an audit/status log and dispatches `OrderUpdated`.
- **Current status:** Target. There is no design-status column or action; only order status `0/1/2`.
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
- **Current status:** Partial. Settlement validates against the receivable, but down-payment validation caps at `grand_total`, not the remaining receivable, so a second DP can overpay.
- **Traceability:** NFR3, NFR9, PB-03.

### FR12 Multiple down payments until paid
- **Requirement:** Cashier SHALL register more than one down payment until the invoice is fully paid, with automatic remaining calculation.
- **Acceptance criteria:**
  - Each down payment is a separate `payments` row of type `down_payment`.
  - `dp_amount` is the sum of all down payments.
  - `accounts_receivable = max(0, grand_total - sum(all payments))`.
  - A down payment equal to the full total moves the invoice to status `2`.
  - Duplicate or over-limit payments are rejected.
- **Current status:** Partial. The service supports repeated down payments, but the UI caps each DP at `grand_total` rather than the remaining receivable, so overpayment is possible. A dedicated multiple-DP test is missing.
- **Traceability:** NFR3, NFR9, PB-03.

### FR13 Display and print payment receipt
- **Requirement:** Cashier SHALL display and print a receipt or proof of payment.
- **Acceptance criteria:**
  - Receipt includes invoice number, customer, items, total, DP history, settlement, total paid, and remaining receivable.
  - Receipt prints on 80 mm paper.
- **Current status:** Implemented, Verified. Evidence: `orders/receipt.blade.php`, `CashieringTest`.
- **Traceability:** NFR4, PB-04.

### FR14 Record operational expenses
- **Requirement:** Cashier SHALL record branch operational expenses.
- **Acceptance criteria:**
  - Fields: amount, type (`modal` or `laba`), incurred date, category, description.
  - Expense inherits the cashier's branch and caches the cashier name.
- **Current status:** Implemented, Verified. Evidence: `Cashier::logExpense()`, `ExpenseForm`, `InventoryManagementTest`.
- **Traceability:** NFR3, PB-04.

---

## 5. Inventory

### FR15 Record raw-material purchases
- **Requirement:** Cashier SHALL record raw-material purchases that add stock to the branch.
- **Acceptance criteria:**
  - Purchase header: branch, cashier, purchase date, optional supplier, notes.
  - Line items: material, quantity, unit cost, subtotal.
  - The purchase, its items, the stock increment, and the stock ledger are written in one transaction.
  - Ledger type is `purchase`, with a reference to the purchase.
  - Activity is written to the audit log.
- **Current status:** Target. The current manager restock adds stock and writes a ledger, but there is no purchase header, supplier, unit-cost record, or cashier purchase flow.
- **Traceability:** NFR9, PB-05.

### FR16 Record material usage on an order
- **Requirement:** Production SHALL record material usage on an order, and stock SHALL decrease automatically.
- **Acceptance criteria:**
  - Usage references the order, material, quantity, and operator.
  - Stock decrement and ledger entry happen in one transaction.
  - A failed print is recorded separately in `wasted_materials`.
  - Ledger type is `usage` or `waste`, with an order reference.
- **Current status:** Partial. Production start deducts stock and waste logging decrements stock, but the ledger has no explicit order reference or usage type, and the waste tab fails to render.
- **Traceability:** NFR9, PB-07.

### FR17 View branch stock and low-stock warnings
- **Requirement:** Owner, Branch Manager, Cashier, and Production SHALL view raw-material stock per branch and receive a warning when stock is below the minimum.
- **Acceptance criteria:**
  - Stock is shown per branch with current stock and minimum stock.
  - Low-stock items are highlighted or listed separately.
  - A warning appears when `current_stock <= min_stock`.
  - Owner may view all branches or one branch.
- **Current status:** Partial. Owner and manager can view stock; low stock is sorted first; there is no explicit warning, and cashier/production access is missing or broken.
- **Traceability:** NFR4, NFR9, PB-05.

### FR18 Adjust stock with a reason
- **Requirement:** Branch Manager SHALL correct branch stock with a mandatory reason.
- **Acceptance criteria:**
  - Adjustment can set a new quantity or apply a signed delta.
  - Reason is required (3–255 characters).
  - New stock cannot be negative.
  - Ledger type is `adjustment` and includes the reason, actor, and branch.
  - Activity is written to the audit log.
- **Current status:** Target. The current restock flow adds stock with optional notes; there is no adjustment type or mandatory reason.
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
- **Current status:** Partial. Only Production has a queue; it is sorted by `created_at`, and there is no priority field or owner/manager/cashier/designer queue view.
- **Traceability:** NFR1, NFR4, PB-06.

### FR20 Update production status
- **Requirement:** Production SHALL update the order status through the production stages.
- **Acceptance criteria:**
  - Transitions: `queued -> processing -> completed`.
  - Each transition writes `order_status_logs` with actor and timestamp.
  - Stock usage is recorded when processing starts.
  - `OrderUpdated` is dispatched.
- **Current status:** Implemented, Verified. Evidence: `OrderService::startProduction()`, `completeProduction()`, `ProductionDashboardTest`.
- **Traceability:** NFR9, PB-06.

### FR21 Mark order as picked up
- **Requirement:** Cashier SHALL mark a paid order as picked up by the customer.
- **Acceptance criteria:**
  - Allowed only when the invoice is fully paid (`status = 2`) and not yet picked up.
  - Invoice and all related orders store `pickup_status = 1` and `pickup_date`.
  - Branch check is enforced.
  - `OrderUpdated` is dispatched.
- **Current status:** Partial. The invoice is updated; `orders.pickup_status` and `orders.pickup_date` are not.
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
- **Current status:** Partial. Owner has financial cards and detail tabs; manager has a dashboard and designer leaderboard but not the full summary.
- **Traceability:** NFR1, NFR4, PB-09.

### FR23 Periodic financial reports
- **Requirement:** Owner and Branch Manager SHALL view periodic financial reports filtered by period and branch.
- **Acceptance criteria:**
  - Reports include gross revenue, base cost, net profit, expenses, and margin.
  - Period: daily, weekly, monthly, and custom range.
  - Owner may view all branches or one branch; manager is branch-scoped.
  - Values reconcile with payments, invoices, waste, and expenses.
- **Current status:** Partial. Owner has full report tabs; manager has a limited revenue leaderboard and recent expenses.
- **Traceability:** NFR1, NFR2, NFR3, PB-09.

### FR24 Export reports to Excel or PDF
- **Requirement:** Owner and Branch Manager SHALL export filtered reports to Excel or PDF.
- **Acceptance criteria:**
  - Export honors the active period, branch, and report type.
  - Excel exports the summary and detail rows; PDF exports a printable summary.
  - The file is generated server-side and downloaded with a clear filename.
  - Export of 10,000 rows completes within the NFR2 target.
  - Export actions are written to the audit log.
- **Current status:** Target. No export package or route exists.
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
- **Current status:** Partial. Activity logs cover branch, user, machine, customer, and invoice creation; payments, order updates, and stock adjustments are not logged.
- **Traceability:** NFR5, NFR9, PB-10.

### FR26 Manual and scheduled database backup
- **Requirement:** Owner SHALL run a manual database backup, and the system SHALL run scheduled automatic backups.
- **Acceptance criteria:**
  - Manual backup is owner-only and runs outside the request timeout.
  - Scheduled backup runs daily without manual action.
  - Backups are stored outside the application database, with a retention policy.
  - Backup success/failure is logged and visible.
  - A restore procedure is documented and tested.
- **Current status:** Target. No backup command, schedule, storage, or restore flow exists.
- **Traceability:** NFR6, PB-10.

---

## 9. Current Implementation Gaps

These are the concrete gaps between the target FRs and the current code. They should drive the redevelopment backlog.

1. `UserManagement::$mode` is client-writable and has no mount-time role guard.
2. `MaterialManagement::$branchId` is client-writable; a manager can delete a global material used by other branches.
3. Cashier invoice selection and checkout lack an explicit branch assertion.
4. Production `render()` queries `materials.branch_id`, which does not exist; the waste/stock tab fails.
5. Product CRUD, product search/filter/pagination, order updates, design status, purchase records, stock adjustments, report export, and backup are not implemented.
6. Stock deduction can drive `current_stock` negative.
7. Category deletion is a hard, cascading delete.
8. Material creation uses two transactions; restore and edit are not transaction-wrapped.
9. Payments, order updates, and stock adjustments are not audited.
10. Pickup updates the invoice but not the related orders.

---

## 10. Traceability to Product Backlog

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
