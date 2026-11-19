# Functional Requirements

- **System:** Print Shop POS (Laravel 13 / Livewire 4 / PostgreSQL / Reverb)
- **Document scope:** Behavior implemented in the application source at the time of writing.
- **Source of truth:** `app/`, `routes/`, `database/migrations/`, and `tests/`.
- **Status legend:**
  - **Implemented** — code path exists.
  - **Verified** — covered by an automated test named in the evidence.
  - **Partial** — works for the main path but has a documented limitation.

Requirements describe what the system does today. Where the code diverges from `AGENTS.md` or `README.md`, the divergence is recorded as an **Implementation note**; it is not silently restated as a requirement.

## Actors

| Role string | Display meaning | Branch binding | Primary responsibilities |
| :--- | :--- | :--- | :--- |
| `owner` | Owner | Optional (`branch_id` may be null) | Cross-branch finance, audit, users, branches, catalog |
| `manager` | Manager | Required in practice | Branch inventory, pricing, machines, staff, leaderboard |
| `cashier` | Cashier | Required in practice | Payments, receipts, pickup queue, drawer expenses |
| `designer` | Designer / CS | Required in practice | Customer intake, quoting, order submission, tickets |
| `production` | Production operator | Required in practice | Print queue, stock deduction, waste logging, completion |

Roles are stored as the literal strings above in `users.role`. There is no self-registration; accounts are created by an owner or manager.

---

## 1. Authentication and Profile

### FR-AUTH-01 Username/password login
- The system SHALL authenticate a user with `username` and `password`, with an optional "remember me" flag.
- Acceptance criteria: valid credentials create an authenticated session; invalid credentials return a generic validation error.
- The login attempt SHALL be rate limited to 5 attempts per `username|IP` key; further attempts return a throttle message.
- Evidence: `app/Livewire/Forms/LoginForm.php`, `tests/Feature/Auth/AuthenticationTest.php`.
- Status: Implemented; login is verified, the throttle path is not covered by a test.

### FR-AUTH-02 Logout
- The system SHALL invalidate the session and regenerate the CSRF token on logout.
- Evidence: `app/Livewire/Actions/Logout.php`, `tests/Feature/Auth/AuthenticationTest.php`.
- Status: Implemented, Verified.

### FR-AUTH-03 Password change
- Any authenticated user SHALL be able to change their own password by supplying the current password and a confirmed new password.
- Evidence: `app/Livewire/Profile/UpdatePasswordForm.php`, `tests/Feature/Auth/PasswordUpdateTest.php`.
- Status: Implemented, Verified.

### FR-AUTH-04 Password confirmation
- The system SHALL provide a password confirmation screen for authenticated users.
- Evidence: `routes/auth.php`, `app/Livewire/Pages/Auth/ConfirmPassword.php`, `tests/Feature/Auth/PasswordConfirmationTest.php`.
- Status: Implemented, Verified.

### FR-AUTH-05 Profile access and editing
- Every authenticated user SHALL be able to view `/profile`.
- Only `owner` and `manager` SHALL be able to update `name` and `email`.
- A changed email SHALL reset `email_verified_at`.
- Other roles SHALL receive a validation error when attempting a name/email update; they may still change their password.
- Evidence: `app/Livewire/Profile/UpdateProfileInformationForm.php`, `tests/Feature/ProfileTest.php`.
- Status: Implemented, Verified.

### FR-AUTH-06 Account deletion
- Only `owner` and `manager` SHALL be able to delete their own account, after confirming their current password.
- The account SHALL be soft deleted.
- Evidence: `app/Livewire/Profile/DeleteUserForm.php`, `tests/Feature/ProfileTest.php`.
- Status: Implemented, Verified.

### FR-AUTH-07 Role-based dashboard dispatch
- After login, `/dashboard` SHALL render the dashboard component matching `auth()->user()->role`.
- An authenticated user with an unknown role SHALL see a generic logged-in card instead of a role dashboard.
- Evidence: `resources/views/dashboard.blade.php`.
- Status: Implemented.

---

## 2. Branch Administration

### FR-BRN-01 Owner-only branch management
- Only `owner` SHALL access branch administration; other roles SHALL receive HTTP 403.
- Evidence: `app/Livewire/Admin/BranchManagement.php`, `tests/Feature/BranchManagementTest.php`.
- Status: Implemented, Verified.

### FR-BRN-02 Create and edit branches
- The owner SHALL create and edit a branch with `name`, unique `code`, `address`, `phone`, `is_active`, and a JSON `settings` object.
- Duplicate `code` SHALL fail validation.
- Evidence: `app/Services/BranchService.php`, `BranchForm`, `tests/Feature/BranchManagementTest.php`.
- Status: Implemented, Verified.
- Implementation note: form validation excludes soft-deleted branches, while the database unique constraint does not. Reusing a deleted branch's code can pass validation and fail at insert.

### FR-BRN-03 Toggle branch status
- The owner SHALL activate or deactivate a branch without deleting it.
- Evidence: `BranchService::toggleBranchStatus()`, `tests/Feature/BranchManagementTest.php`.
- Status: Implemented, Verified.

### FR-BRN-04 Soft-delete and list deactivated branches
- The owner SHALL soft delete a branch and SHALL be able to display deactivated/deleted records.
- Evidence: `BranchService::deleteBranch()`, `app/Livewire/Admin/BranchManagement.php`.
- Status: Implemented; delete is verified, the deactivated-list view is not covered by a test.

### FR-BRN-05 Branch audit and broadcast
- Branch create, update, toggle, and delete SHALL write an `activity_logs` row and dispatch `OrderUpdated`.
- Evidence: `app/Services/BranchService.php`, `app/Livewire/Admin/BranchManagement.php`.
- Status: Implemented.

---

## 3. User Administration

### FR-USR-01 Access control
- `owner` and `manager` SHALL access user management through their dashboards.
- `cashier`, `designer`, and `production` SHALL NOT see a user-management tab; the dashboard dispatcher renders their own role dashboard instead.
- Evidence: `resources/views/dashboard.blade.php`, `_role_links.blade.php`, `tests/Feature/UserManagementTest.php` (asserts the tab is absent, not a 403).
- Status: Partial. `UserManagement` has no `mount()` role guard, and its public `mode` property is client-writable.

### FR-USR-02 Owner user management
- The owner SHALL create, edit, soft delete, and restore users, assigning `name`, `username`, `email`, `phone`, `address`, `role`, and `branch_id`.
- Evidence: `app/Services/UserService.php`, `tests/Feature/UserManagementTest.php`.
- Status: Implemented; create, update, and delete are verified; restore is not covered by a test.

### FR-USR-03 Primary-owner protection
- A primary owner (username `admin_owner`, `primary`, or `owner`, or the legacy fixed ID) SHALL be the only owner allowed to create, edit, delete, or restore other `owner` accounts.
- A secondary owner attempting those operations SHALL receive HTTP 403.
- Evidence: `UserManagement::isPrimaryOwner()`, `tests/Feature/UserManagementTest.php`.
- Status: Implemented, Verified.

### FR-USR-04 Manager user management
- When the component is rendered in manager mode, a manager SHALL manage only `cashier`, `designer`, and `production` users assigned to the manager's own branch.
- In that mode, a manager SHALL NOT create or modify `owner` or `manager` accounts and SHALL NOT move users to another branch.
- Evidence: `UserManagement` (`mode="manager"`), `tests/Feature/UserManagementTest.php`.
- Status: Partial. `mode` is a public, client-writable property and the component has no `mount()` role guard; setting `mode=owner` bypasses the manager restrictions.

### FR-USR-05 Role and branch assignment
- The system SHALL support exactly `owner`, `manager`, `cashier`, `designer`, and `production` as roles.
- Users SHALL optionally carry `branch_id`; owners may be unassigned.
- Evidence: `app/Models/User.php`, migrations, `tests/Feature/UserManagementTest.php`.
- Status: Implemented, Verified.

### FR-USR-06 Audit and broadcast
- User create, update, delete, and restore SHALL write an `activity_logs` row (password excluded from the payload) and dispatch `OrderUpdated`.
- Evidence: `app/Services/UserService.php`, `app/Livewire/Admin/UserManagement.php`.
- Status: Implemented.

---

## 4. Catalog Configuration

### FR-CAT-01 Product categories
- A manager or owner SHALL create, edit, and delete product categories with a `name` and a `calculation_archetype`.
- Supported archetypes: `per_piece`, `surface_area`, `linear_dimension`, `tiered_volume`.
- A category SHALL optionally restrict the attribute types it accepts through `allowed_attribute_types`; an empty list means all types are allowed.
- Evidence: `app/Services/ProductCategoryService.php`, `app/Models/ProductCategory.php`, `tests/Feature/CategoryManagementTest.php`.
- Status: Implemented, Verified.
- Implementation note: deletion is a hard delete. `products.product_category_id` and `materials.product_category_id` cascade, so deleting a category also deletes its products, materials, and their pricing rows. The delete is rejected if `wasted_materials` or `stock_ledgers` rows reference those materials, because those foreign keys are NO ACTION.

### FR-CAT-02 Attribute types
- A manager or owner SHALL create and delete attribute types with a `name` and `calculation_archetype`.
- There is no edit path for an existing attribute type.
- The migration seeds `finishing`, `cut`, and `display`.
- Evidence: `app/Livewire/Admin/CategoryManagement.php`, `database/migrations/2026_07_06_140000_create_attribute_types_table.php`, `tests/Feature/CategoryManagementTest.php`.
- Status: Implemented; create and delete are verified, edit is not implemented.

### FR-CAT-03 Display, finishing, and cut attributes
- A manager SHALL create, edit, and soft delete branch attributes of a type defined in `attribute_types`. The seeded types are `finishing`, `cut`, and `display`; custom types can also be assigned.
- Attribute edits SHALL be restricted to the manager's branch when the attribute carries a `branch_id`.
- Evidence: `app/Livewire/Dashboards/Manager.php`, `tests/Feature/CategoryManagementTest.php`.
- Status: Implemented; category and attribute-type paths are tested, manager attribute CRUD is not.

### FR-CAT-04 Products
- Products SHALL reference a product category and carry a `pricing_type` of `unit` or `area`.
- There is no dedicated product CRUD screen; products are created through data setup or legacy migration and consumed by pricing and order intake.
- Evidence: `app/Models/Product.php`, `database/migrations/2026_06_24_000000_create_pos_tables.php`.
- Status: Partial.

---

## 5. Material and Stock Management

### FR-MAT-01 Material catalog
- A manager or owner SHALL register a material with `name`, `product_category_id`, optional `type`, `dimensions`, `cost` (HPP per unit), and `price`.
- Material names SHALL be unique case-insensitively among non-deleted materials.
- Evidence: `app/Services/InventoryService.php`, `MaterialForm`, `tests/Feature/MaterialManagementTest.php`.
- Status: Implemented, Verified.

### FR-MAT-02 Global versus branch stock
- A manager-created material SHALL receive a `material_branch_stock` row for the target branch in `$branchId`, which defaults to the manager's branch.
- An owner-created material SHALL receive a zero-stock row for every branch.
- Evidence: `MaterialManagement::saveMaterial()`, `tests/Feature/MaterialManagementTest.php`.
- Status: Implemented; the manager branch default is verified, but the target branch is client-selectable and not server-enforced.
- Implementation note: the target branch comes from `MaterialManagement::$branchId`, which is initialized from the manager's branch but can be changed through `handleBranchSelected()` without an owner check.

### FR-MAT-03 Activation wizard
- When a material name already exists globally but is not active in the target branch, a manager SHALL be offered an activation step rather than a duplicate insert.
- Activation SHALL create the branch stock junction with the supplied minimum stock.
- Evidence: `MaterialManagement::saveMaterial()`, `activateExistingMaterial()`, `tests/Feature/MaterialManagementTest.php`.
- Status: Implemented, Verified.

### FR-MAT-04 Similarity warning
- On create, the system SHALL compare the proposed name with existing names using Levenshtein similarity.
- A similarity of 75% or more SHALL show a warning and require explicit confirmation before saving.
- Evidence: `MaterialManagement::saveMaterial()` (`levenshtein`), `tests/Feature/MaterialManagementTest.php`.
- Status: Implemented, Verified.

### FR-MAT-05 Restock and stock ledger
- A manager or owner SHALL add stock for a material in a branch.
- Each mutation SHALL create a `stock_ledgers` row capturing `initial_stock`, `added_stock`, `used_stock`, `wasted_stock`, and `remaining_stock`.
- The stock row SHALL be read with `lockForUpdate()` inside a transaction.
- Evidence: `InventoryService::addStock()`, `tests/Feature/InventoryManagementTest.php`, `tests/Unit/InventoryServiceTest.php`.
- Status: Implemented, Verified.

### FR-MAT-06 Soft delete and restore
- A manager or owner SHALL soft delete a material and view deleted materials.
- Deleting a material SHALL hard delete its branch stock rows inside the same transaction.
- Restoring a material SHALL recreate missing branch stock rows for all branches when the actor is an owner, and for the selected branch only when the actor is a manager.
- Evidence: `InventoryService::deleteGlobalMaterial()`, `MaterialManagement::restoreMaterial()`, `tests/Feature/MaterialManagementTest.php`.
- Status: Implemented; neither delete nor restore is directly covered by a test.
- Implementation note: a manager can delete a global material that other branches use, because the action checks the role but not branch ownership of the material.

### FR-MAT-07 Minimum stock
- Each branch stock row SHALL store a `min_stock` threshold; the inventory list SHALL surface low stock first.
- Evidence: `app/Models/MaterialBranchStock.php`, `Manager::render()`.
- Status: Implemented.

---

## 6. Machine Management

### FR-MCH-01 Manager/owner access
- Only `manager` and `owner` SHALL access machine management; other roles SHALL receive HTTP 403.
- Evidence: `app/Livewire/Admin/MachineManagement.php`, `tests/Feature/MachineManagementTest.php`.
- Status: Implemented, Verified.

### FR-MCH-02 Machine records
- A manager or owner SHALL create, edit, soft delete, and restore a machine with `name`, `branch_id`, and `cost` (HPP).
- A null `branch_id` represents a global machine; a manager SHALL only operate on machines for their own branch.
- Evidence: `app/Services/MachineService.php`, `MachineForm`, `tests/Feature/MachineManagementTest.php`.
- Status: Implemented; create, edit, and delete are verified; restore is not covered by a test.

### FR-MCH-03 Machine cost in pricing
- The pricing engine SHALL add the selected machine's `cost` to the unit cost and any matching `machine_pricing.cost` override when present.
- Evidence: `PricingService::calculateQuote()`.
- Status: Implemented.

---

## 7. Pricing Engine

### FR-PRC-01 Pricing matrix CRUD
- A manager SHALL create, edit, and delete pricing rules scoped to the manager's branch with `product_id`, `material_id`, `customer_type`, `min_qty`, `price`, `cost`, and `profit`.
- `cost` SHALL be taken from the selected material's `cost`; `profit` SHALL be `max(0, price - cost)`.
- Evidence: `Manager::savePricing()`, `tests/Feature/SimplifiedPricingTest.php`.
- Status: Implemented (the engine and clone path are tested; `Manager::savePricing()` is not covered by a test).

### FR-PRC-02 Tiered quantity matching
- Quote calculation SHALL select the pricing row with the highest `min_qty` that is less than or equal to the requested quantity.
- Evidence: `PricingService::calculateQuote()`.
- Status: Implemented.

### FR-PRC-03 Customer type and reseller fallback
- Pricing SHALL match `customer_type` as a stored string. The customer form and the pricing form both offer `Reguler` and `Mitra`; other values, such as `Reseller`, can exist in imported or legacy data.
- If no row exists for a non-`Reguler` type, the engine SHALL fall back to `Reguler`.
- When the requested type is exactly `Reseller` and the fallback is used, the engine SHALL apply the branch's `settings.reseller_discount_percent` to the base price.
- The customer form offers only `Reguler` and `Mitra`, so `Reseller` customers are legacy/import-only. `Reseller` pricing rows can still be created through the clone selector, which offers `Reseller` for source and target customer types.
- Evidence: `PricingService::calculateQuote()`, `app/Models/Branch.php`, `tests/Feature/SimplifiedPricingTest.php`.
- Status: Implemented, Verified.

### FR-PRC-04 Attribute and machine modifiers
- Selected attributes SHALL add their `attribute_pricing.price` and `attribute_pricing.cost`.
- A selected machine SHALL add its `machine_pricing.price`; the cost SHALL prefer `machine_pricing.cost` when greater than zero, otherwise the machine's own `cost`.
- Evidence: `PricingService::calculateQuote()`.
- Status: Implemented.

### FR-PRC-05 Calculation archetypes
- `surface_area` SHALL require positive `width` and `length` and multiply unit price by quantity by area.
- `linear_dimension` SHALL require a positive `length` and multiply unit price by quantity by length.
- `tiered_volume` and `per_piece` SHALL multiply unit price by quantity.
- Evidence: `PricingService::calculateQuote()`, `tests/Unit/PricingServiceTest.php`.
- Status: Implemented, Verified.

### FR-PRC-06 Pricing errors
- Missing pricing SHALL throw `PricingNotFoundException`.
- Missing geometric parameters SHALL throw `InvalidArgumentException`.
- The calculator SHALL catch both and display the message without submitting the item.
- Evidence: `PricingService`, `Designer::calculateQuote()`, `tests/Unit/PricingServiceTest.php`.
- Status: Implemented, Verified.

### FR-PRC-07 Clone pricing tiers
- A manager SHALL clone all pricing rows for a source material and customer type onto a target material and customer type, applying a percentage adjustment in the range -100 to 1000. The action also accepts an owner, but no owner dashboard tab exposes it.
- The target cost SHALL be the target material's `cost`.
- Evidence: `Manager::clonePricingTiers()`, `tests/Feature/SimplifiedPricingTest.php`.
- Status: Implemented, Verified.

### FR-PRC-08 Manual price override
- A designer SHALL override the calculated unit price and cost for a cart item; the item SHALL be flagged `is_manual_price`.
- Evidence: `Designer` (`isManualPrice`, `manualPrice`, `manualCost`), `orders.is_manual_price`.
- Status: Implemented.

---

## 8. Order Intake

### FR-ORD-01 Customer selection and creation
- A designer SHALL search customers by name or phone (up to 8 results) and select one.
- A designer SHALL create a customer with `name`, `phone`, `address`, `email`, and `type` (`Reguler` or `Mitra`, default `Reguler`).
- Evidence: `Designer::render()`, `CustomerService::createCustomer()`, `CustomerForm`.
- Status: Implemented.

### FR-ORD-02 Quote calculator
- The designer calculator SHALL load products, materials, machines, and attributes for the designer's branch.
- The category's allowed attribute types SHALL filter which finishing, cut, and display options are offered.
- Quote calculation SHALL run on the server through `PricingService` and update the preview.
- Evidence: `Designer::calculateQuote()`, `Designer::render()`, `app/Livewire/Forms/CalculatorForm.php`.
- Status: Implemented.

### FR-ORD-03 Cart
- A designer SHALL add multiple calculated items to a draft cart and remove items.
- Each cart line SHALL retain its pricing rule, dimensions, quantity, file name, notes, unit price, subtotal, cost, profit, and manual-price flag.
- Evidence: `Designer::addItem()`, `removeItem()`.
- Status: Implemented.

### FR-ORD-04 Invoice submission
- Submission SHALL require a selected customer and at least one item.
- The system SHALL create one invoice with its orders, an initial `order_status_logs` row per order (`status = 0`), and one `activity_logs` row, inside a single database transaction.
- Invoice totals SHALL be: `grand_total = max(0, sum(subtotals) - discount)`, `total_cost = sum(item costs)`, `total_profit = grand_total - total_cost`, `accounts_receivable = grand_total`.
- Evidence: `OrderService::createInvoice()`, `Designer::submitOrder()`.
- Status: Implemented.

### FR-ORD-05 Invoice numbering
- The invoice number SHALL follow `INV-{BRANCH_PREFIX}-{YYYYMMDD}-{NNNN}`.
- The branch prefix SHALL be derived from the first two words of the branch name, or the first two characters when the name is a single word; the fallback is `BR`.
- The daily sequence SHALL be the count of invoices for that branch and date plus one.
- Evidence: `OrderService::createInvoice()`.
- Status: Implemented.

### FR-ORD-06 Order ticket
- An authenticated user SHALL open `/orders/{invoice}/ticket` to render a printable work-order sheet.
- Evidence: `routes/web.php`, `resources/views/orders/ticket.blade.php`, `tests/Feature/BranchAuthorizationAndScopingTest.php`.
- Status: Implemented, Verified.

### FR-ORD-07 Order list
- A designer SHALL view an order list tab with search and status context.
- Evidence: `Designer` (`invoiceSearch`, `order_list` tab).
- Status: Implemented.

### FR-ORD-08 Update an order (planned)
- A designer SHALL be able to update a submitted order to add items when the customer requests changes.
- Open decision: whether updates are allowed after production starts. Recommended limit: only while every order on the invoice has `status = 0`.
- Status: Planned; not implemented. The current code supports create and read only.
- Evidence: none.

---

## 9. Production

### FR-PRD-01 Branch-scoped queue
- A production user SHALL see queue counts for pending (`status = 0`), printing (`status = 1`), and completed today (`status = 2`) orders, plus today's wasted quantity, scoped to the user's branch.
- Evidence: `Production::render()`.
- Status: Implemented.

### FR-PRD-02 Start production
- Starting an order SHALL set `orders.status = 1`, set `operator_id`, and write a status log.
- If the order has a `pricing_id`, the system SHALL deduct stock at start:
  - `area` products: `order.area * order.quantity`
  - other products: `order.quantity`
- The stock deduction SHALL lock the branch stock row and write a `stock_ledgers` row with `used_stock`.
- Evidence: `OrderService::startProduction()`, `InventoryService::deductStockOnPrint()`, `tests/Feature/ProductionDashboardTest.php`.
- Status: Implemented, Verified.
- Implementation note: stock is deducted at production start, not at completion. This differs from `AGENTS.md` §2.4.7 and the older feature notes.

### FR-PRD-03 Waste logging
- A production user SHALL log a failed print with material, optional machine, quantity, optional dimensions, optional designer, and notes. The service accepts `order_id`, but the form does not expose it.
- The system SHALL create a `wasted_materials` row, decrement branch stock, and write a `stock_ledgers` row with `wasted_stock`, inside a transaction.
- Evidence: `Production::logWaste()`, `InventoryService::logWaste()`, `tests/Feature/ProductionDashboardTest.php`, `tests/Unit/InventoryServiceTest.php`.
- Status: Partial. The action and service work and are tested directly, but rendering the `waste` tab fails because `Production::render()` queries `materials.branch_id`, a column that does not exist; stock lives in `material_branch_stock`.
- Implementation note: waste logging does not dispatch `OrderUpdated`; other dashboards refresh on their next request.

### FR-PRD-04 Complete production
- Completing an order SHALL set `orders.status = 2`, set `operator_id`, and write a status log.
- Evidence: `OrderService::completeProduction()`, `tests/Feature/ProductionDashboardTest.php`.
- Status: Implemented, Verified.

### FR-PRD-05 Branch authorization for production actions
- Starting or completing an order whose invoice belongs to another branch SHALL return HTTP 403 for a branch-bound user.
- Evidence: `Production::startOrderProduction()`, `Production::completeOrderProduction()`, `tests/Feature/ProductionDashboardTest.php` (no cross-branch case).
- Status: Implemented; the branch check exists, but no dedicated cross-branch test covers production actions.

---

## 10. Payments and Pickup

### FR-PAY-01 Payment queue
- A cashier SHALL see unpaid and down-payment invoices (`status` 0 or 1) for the cashier's branch, newest first, limited to 8 on the dashboard.
- Evidence: `Cashier::render()`.
- Status: Implemented.

### FR-PAY-02 Down payment
- A cashier SHALL register a down payment with an amount between 1,000 and the invoice `grand_total`, using `Cash`, `Transfer`, or `QRIS`.
- The system SHALL create a `payments` row of type `down_payment` and recalculate the invoice.
- A down payment equal to the full `grand_total` is allowed and settles the invoice to status `2`.
- Evidence: `PaymentService::registerDownPayment()`, `Cashier::processCheckout()`, `tests/Feature/CashieringTest.php`.
- Status: Implemented; the standard down-payment path is verified, a full-amount down payment is not.

### FR-PAY-03 Settlement
- A cashier SHALL settle an invoice with an optional discount between 0 and the outstanding receivable, and a payment method.
- The amount due SHALL be `accounts_receivable - discount`.
- A cash amount below the amount due SHALL be rejected with a validation error.
- The system SHALL create a `payments` row of type `settlement`, reduce `grand_total` and `total_profit` by the discount, and recalculate the invoice.
- Evidence: `PaymentService::settleInvoice()`, `Cashier::processCheckout()`, `tests/Feature/CashieringTest.php`.
- Status: Implemented, Verified.

### FR-PAY-04 Invoice status recalculation
- Recalculation SHALL derive `dp_amount` from down-payment rows and `accounts_receivable = max(0, grand_total - sum(payments))`.
- Status SHALL be `2` when the receivable is zero, `1` when any payment exists, and `0` otherwise.
- `dp_date` and `payment_date` SHALL be set the first time the corresponding state is reached.
- Evidence: `app/Models/Invoice.php` (`recalculatePayments()`), `tests/Feature/CashieringTest.php`.
- Status: Implemented, Verified.

### FR-PAY-05 Receipt
- An authenticated user SHALL open `/orders/{invoice}/receipt` to render an 80 mm printable receipt.
- Evidence: `routes/web.php`, `resources/views/orders/receipt.blade.php`, `tests/Feature/CashieringTest.php`.
- Status: Implemented, Verified.

### FR-PAY-06 Pickup queue
- A cashier SHALL see invoices with `status = 2` and `pickup_status = 0` for the cashier's branch, ordered by payment date.
- Marking an invoice picked up SHALL set `pickup_status = 1` and `pickup_date`, dispatch `OrderUpdated`, and refresh the component.
- Evidence: `Cashier::render()`, `Cashier::markAsPickedUp()`, `tests/Feature/BranchAuthorizationAndScopingTest.php`.
- Status: Implemented, Verified.

### FR-PAY-07 Drawer expenses
- A cashier SHALL log an expense with amount, type (`modal` or `laba`), incurred date, and description.
- The expense SHALL inherit the cashier's branch and cache the cashier's name.
- Evidence: `Cashier::logExpense()`, `ExpenseForm`, `tests/Feature/InventoryManagementTest.php`.
- Status: Implemented, Verified.

### FR-PAY-08 No refunds or reversals
- The system SHALL NOT provide a payment reversal, refund, or void workflow.
- Status: Not implemented (explicit non-goal of the current scope).

---

## 11. Reporting and Analytics

### FR-RPT-01 Owner period filter
- The owner dashboard SHALL filter by `Daily` (today), `Weekly` (current calendar week), or `Monthly` (current calendar month).
- Evidence: `Owner::render()`.
- Status: Implemented.

### FR-RPT-02 Owner branch filter
- The owner SHALL view all branches or one selected branch.
- Branch selection SHALL be pushed to child detail components through the `branchSelected` event.
- Evidence: `Owner::updatedSelectedBranchId()` for branch selection, `setPeriod()` and `updatedPeriod()` for period selection.
- Status: Implemented.

### FR-RPT-03 Gross revenue
- Gross revenue SHALL be the sum of `payments.amount` in the period, split by `Cash`, `Transfer`, and `QRIS`.
- Evidence: `Owner::render()`, `FinanceService::getGrossRevenueBreakdown()`, `tests/Feature/GrossRevenueDetailTest.php`.
- Status: Implemented, Verified.

### FR-RPT-04 Base cost
- Base cost SHALL be the sum of invoice `total_cost` in the period plus `wasted_materials.quantity * materials.cost` for waste logged in the period.
- Evidence: `FinanceService::getBaseCostBreakdown()`, `tests/Feature/BaseCostDetailTest.php`.
- Status: Implemented, Verified.

### FR-RPT-05 Net profit
- Detailed net profit SHALL be `payments - invoice cost - waste cost - expenses`, with a margin rate of `net_profit / payments * 100`.
- Evidence: `FinanceService::getNetProfitBreakdown()`, `tests/Feature/NetProfitDetailTest.php`.
- Status: Implemented, Verified.
- Implementation note: the owner dashboard headline card computes `payments - invoice cost` only. It does not subtract waste cost or expenses, so the headline can differ from the Net Profit Detail tab.

### FR-RPT-06 Expenses breakdown
- The owner SHALL view total expenses and a per-category breakdown for the period.
- Evidence: `FinanceService::getExpensesBreakdown()`, `tests/Feature/ExpensesDetailTest.php`.
- Status: Implemented, Verified.

### FR-RPT-07 Sparkline
- The owner dashboard SHALL render a headline-style net-profit sparkline derived from six 4-hour buckets (daily), seven days (weekly), or four weeks (monthly). Each point is payments minus invoice cost; it excludes waste cost and expenses.
- Evidence: `Owner::render()`, `resources/views/livewire/dashboards/partials/owner/_sparkline_chart.blade.php`.
- Status: Implemented.

### FR-RPT-08 Audit trail
- The owner SHALL search, sort, paginate, and open detail for `activity_logs`.
- Evidence: `Owner` audit properties and methods, owner audit tab.
- Status: Implemented.

### FR-RPT-09 Designer leaderboard
- A manager SHALL view a current-month leaderboard scoped to the manager's branch with invoice count, total invoice value, average invoice value, and waste count per designer. The source counts invoices, not individual orders.
- Evidence: `Manager::render()`.
- Status: Implemented.

---

## 12. Real-Time Updates

### FR-RT-01 OrderUpdated broadcast
- State changes SHALL dispatch `App\Events\OrderUpdated`, a `ShouldBroadcastNow` event on the public channel `orders`, broadcast as `OrderUpdated`.
- `Owner`, `Manager`, `Cashier`, and `Production` dashboards SHALL subscribe with `echo:orders,OrderUpdated` and refresh; `MachineManagement`, `MaterialManagement`, `CategoryManagement`, and the four owner detail components also subscribe. `Designer` does not register the listener.
- Evidence: `app/Events/OrderUpdated.php`, `resources/js/echo.js`, the four dashboards named above.
- Status: Partial (four of five dashboards subscribe).

### FR-RT-02 Dispatch points
- `OrderUpdated` SHALL be dispatched after order submission, payment, pickup, production start, production completion, pricing-tier cloning, and administrative changes to branches, users, categories, machines, and materials.
- Evidence: `grep "event(new OrderUpdated)" app/Livewire`.
- Status: Implemented.
- Implementation note: these actions do not dispatch the event: waste logging, cashier expense logging, designer customer creation, manager stock restocking, attribute edits, and pricing create/update/delete.

---

## 13. User Interface

### FR-UI-01 Role-specific sidebar navigation
- The sidebar SHALL render links according to the authenticated role, using the tabs documented in `docs/requirements/access-matrix.md`.
- Evidence: `resources/views/livewire/layout/partials/navigation/_role_links.blade.php`.
- Status: Implemented.

### FR-UI-02 Light/dark theme
- The UI SHALL support light and dark themes and persist the choice in `localStorage`.
- Evidence: `resources/views/components/navigation/theme-toggle.blade.php`, `resources/css/app.css`.
- Status: Implemented.

### FR-UI-03 Responsive layout
- Dashboards SHALL use a sidebar layout and responsive Tailwind utilities, including Livewire islands where implemented.
- Evidence: `resources/views/livewire/dashboards/*`, `resources/css/app.css`.
- Status: Implemented.

### FR-UI-04 Feedback states
- Administrative and operational actions SHALL show success alerts and validation errors in the active component.
- Evidence: `showSuccessAlert`, `showUserModal`, form objects across components.
- Status: Implemented.

---

## 14. Audit and Traceability

### FR-AUD-01 Activity log
- The system SHALL record `activity_logs` rows with actor (`user_id`, `username`, `role`), `branch_id`, action, description, model type and ID, and an optional JSON payload.
- Covered actions: branch create/update/toggle/delete; user create/update/delete/restore; machine create/update/delete/restore; customer create; invoice create.
- Not covered: material create/update/delete/restore, product-category changes, attribute-type changes, pricing changes, attribute changes, invoice update/delete, customer update/delete.
- Evidence: `app/Services/*`, `app/Models/ActivityLog.php`.
- Status: Partial.

### FR-AUD-02 Order status log
- Every production status change SHALL append an `order_status_logs` row with status, actor, cached name, and notes.
- Evidence: `OrderService`, `app/Models/OrderStatusLog.php`.
- Status: Implemented.

---

## 15. Known Gaps and Non-Goals

These are observable gaps in the current implementation. They are recorded so the requirements are not read as stronger than the code.

1. **Branch isolation is not uniform.** Ticket/receipt routes, pickup, production start/complete, and admin components check `branch_id`. `Cashier::selectInvoice()` and `processCheckout()` load an invoice by ID without an explicit branch assertion; the queue itself is branch-scoped.
2. **No stock floor.** Stock deductions can drive `current_stock` negative; no guard or alert blocks the operation.
3. **No payment reversal/refund/void.**
4. **No file upload.** Orders store `file_name` only; `file_path` and `preview_path` exist in the schema but are not populated by the current intake flow.
5. **No product CRUD UI.** Products are data-setup records.
6. **Headline profit differs from detailed profit** as noted in FR-RPT-05, and the sparkline follows the headline formula.
7. **Waste logging is not broadcast** as noted in FR-RT-02.
8. **No public JSON API.** The service layer is internal.
9. **Manager user-management restrictions are bypassable.** `UserManagement::$mode` is client-writable and the component has no `mount()` role guard.
10. **Material management is not branch-isolated.** `MaterialManagement::$branchId` is client-writable, and a manager can delete a global material used by other branches.
11. **The production waste tab does not render.** `Production::render()` queries a non-existent `materials.branch_id`.
12. **Category deletion is destructive.** It hard deletes the category and cascades to products, materials, and pricing, unless NO ACTION references from `wasted_materials` or `stock_ledgers` block the delete.
13. **Material writes are not fully atomic.** Creation uses two service transactions; restore and the edit path update the catalog and branch stock without any transaction.
14. **The material rollback test is invalid.** `MaterialManagementTest::test_material_creation_rolls_back_entirely_on_ledger_failure` asserts after the throwing call, so its assertions never run.
15. **Pricing deletion is a hard delete.** `orders.pricing_id` is set to null on delete, breaking the historical pricing link.
16. **Attribute types cannot be edited.** The screen supports create and delete only.
