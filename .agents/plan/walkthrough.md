# Walkthrough - POS Rebuild Phases

This walkthrough documents the verified implementations of the print shop POS rebuild project.

> [!NOTE]
> Historical logs from the initial stages of the rebuild (Phases 1 to 5) have been archived. You can find detail records, model structures, pricing calculations, inventory flow math, and WebSocket configurations in the [Archive of Phases 1 to 5](.agents/plan/rebuildPlan/walkthrough_phases_1_5.md).

---

## Phase 6: Bug Fixes Across Dashboard Pages (Completed)

Fixed 7 bugs found across all 5 dashboard pages and the receipt page:

### 1. Designer — Ghost Customer Selection (Stale `customerId`)
* **File:** [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
* Added `updatedCustomerSearch()` lifecycle hook to reset `customerId` when user manually edits the search field after already picking a customer. Prevents a stale customer ID being silently attached to a new search.

### 2. Designer — XSS / JS Crash on Customer Names with Apostrophes
* **File:** [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
* Replaced `'{{ $c->name }}'` in `wire:click="selectCustomer()"` with `\Illuminate\Support\Js::from($c->name)` which produces a properly JSON-encoded, XSS-safe JS string. This prevents names like `O'Brien` from breaking the Livewire wire:click call.

### 3. Cashier — `dpCollectedToday` Queried Wrong Date Column
* **File:** [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
* Changed `whereDate('created_date', ...)` to `whereDate('dp_date', ...)` — the DP counter now correctly reflects invoices where the Down Payment was paid *today*, not just created today.

### 4. Cashier — `settlementsToday` Queried Wrong Date Column
* **File:** [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
* Changed `whereDate('created_date', ...)` to `whereDate('payment_date', ...)` — settlements counter now reflects invoices *settled* today, not created today.

### 5. Cashier — Pickup Queue Not Refreshing After `markAsPickedUp()`
* **File:** [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
* Added `$this->dispatch('$refresh')` after marking an invoice as picked up. The WebSocket `OrderUpdated` event only refreshes other roles via Reverb; this direct dispatch ensures the cashier's own component re-renders immediately even when Reverb is not running.

### 6. Owner — SVG Sparkline Divide-by-Zero Crash
* **File:** [owner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/owner.blade.php)
* Fixed `($idx / ($count - 1)) * 300` crashing with division-by-zero when there is only 1 sparkline data point. Guard: when `$count === 1`, the single point is placed at horizontal center (x=150) instead of dividing by zero.

### 7. Receipt — Grand Total Double-Counting Discount
* **File:** [receipt.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/receipt.blade.php)
* The old code showed `$invoice->grand_total + $invoice->discount` as the original amount, but `grand_total` is stored *post-discount* in the DB already. This caused the displayed "pre-discount total" to be inflated. Fixed by computing the true pre-discount total from `$invoice->orders->sum('subtotal')` which is always stored as the raw line-item sum.


---

## Post-Migration Verification & Profile Auth Fixes (Completed)

After the database was updated with the new `deleted_at` column on the `users` table:

1. **Applied Schema Updates:** Ran `php artisan migrate:fresh` to rebuild all tables correctly.
2. **Reloaded Legacy Records:** Successfully executed the legacy data command `php artisan db:migrate-legacy` to reload historical MariaDB records into PostgreSQL.
3. **Fixed Failing Profile Tests:** 
   * Updated [ProfileTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/ProfileTest.php) to align with role authorization rules (only `owner` and `manager` can edit/delete accounts).
   * Verified user deletion with `assertSoftDeleted()` rather than expecting the database record to be fully deleted.
   * Secured the backend delete handler `deleteUser` in [delete-user-form.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/profile/delete-user-form.blade.php) with an authorization check.

---

## Phase 7: User Management (Completed)

We have implemented a secure User Management system inside the Manager and Owner dashboards, enforcing strict access scopes, role separation, and primary owner privilege controls.

### Changes Made

### 1. Manager Dashboard User Management
* **Location:** [manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php)
  * Integrated a tabbed layout switching between "Overview & Inventory" and "User Management".
  * Rendered a search-filterable table containing all staff members belonging to the manager's branch.
  * Restricted staff role creation to cashier, designer, and production roles (scoped to the active branch).
  * Added a security guard and action block preventing managers from editing or deleting other branch managers or owner accounts.

### 2. Owner Dashboard User Management
* **Location:** [owner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/owner.blade.php)
  * Integrated a tabbed layout switching between "Financial Overview" and "User Management".
  * Rendered a global table containing all users across all branches.
  * Added staff creation and edit forms, supporting custom role selection and branch assignment dropdowns.
  * Enforced Primary Owner rules: Only the Primary Owner (ID = 1) can register, update, or delete other owner accounts. Secondary owners (ID > 1) are prevented from executing actions on owner accounts.

### 3. Automated Feature Verification
* **Location:** [UserManagementTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/UserManagementTest.php)
  * Created a comprehensive feature test suite checking all user management scenarios.
  * Covers branch scoping, manager CRUD permissions, manager access blocks on owner accounts, cashier/operator access blocks, and Primary vs. Secondary Owner permissions.

---

## Verification & Execution Guide (Phase 7)

### 1. Run Automated User Management Tests
Run the new feature test suite inside the PHP container to verify user management rules:
```bash
sudo docker exec -it -w /var/www/html/pos_alja local-php83 ./vendor/bin/phpunit tests/Feature/UserManagementTest.php
```

### 2. Verify all test files
Verify that all unit and feature tests pass successfully:
```bash
sudo docker exec -it -w /var/www/html/pos_alja local-php83 ./vendor/bin/phpunit
```
