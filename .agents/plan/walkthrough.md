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

---

## Phase 8: Refactoring Volt to Class-Based Livewire Components (Completed)

We have refactored the entire application from Livewire Volt (single-file inline components) to clean, PSR-12 compliant, Class-Based Livewire Components to improve readability, performance, and structure.

### Changes Made

#### 1. Created PHP Livewire Classes
* **Profile Components:**
  * [UpdateProfileInformationForm.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Profile/UpdateProfileInformationForm.php)
  * [UpdatePasswordForm.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Profile/UpdatePasswordForm.php)
  * [DeleteUserForm.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Profile/DeleteUserForm.php)
* **Dashboard Components:**
  * [Owner.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Owner.php)
  * [Manager.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Manager.php)
  * [Cashier.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Cashier.php)
  * [Designer.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Designer.php)
  * [Production.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Production.php)
* **Extracted Sub-Component:**
  * [UserManagement.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Admin/UserManagement.php)

#### 2. Modified Blade Views (PHP blocks removed)
* Removed Volt inline PHP tags (`<?php ... ?>`) and cleanly matched component layouts:
  * [update-profile-information-form.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/profile/update-profile-information-form.blade.php)
  * [update-password-form.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/profile/update-password-form.blade.php)
  * [delete-user-form.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/profile/delete-user-form.blade.php)
  * [owner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/owner.blade.php)
  * [manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php)
  * [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
  * [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
  * [production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php)
  * [user-management.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/admin/user-management.blade.php)

#### 3. Applied Debounce & Alpine.js Optimizations
* Input discount, input amount, and search elements updated to `.live.debounce.300ms` in Designer, Cashier, and UserManagement blades to limit redundant server requests.
* Replaced `wire:click="$set('showUserModal', false)"` with Alpine `@click="$wire.set('showUserModal', false)"` to avoid unnecessary page re-renders.

#### 4. Migrated Test Suite to Livewire Native Helpers
* Replaced all occurrences of `Livewire\Volt\Volt` testing helpers with standard `Livewire\Livewire::test()` assertions.
* Updated test assertions across `ProfileTest.php`, `UserManagementTest.php`, `BranchAuthorizationAndScopingTest.php`, `CashieringTest.php`, and `InventoryManagementTest.php`.
* Redirected User CRUD tests in owner and manager views to directly test the new shared `UserManagement::class` component.

---

## Phase 9: GitHub Integration (Completed)

We have configured standard GitHub integration files to automate workflows, ensure styling guidelines, and standardize contributions.

### Changes Made

#### 1. GitHub Actions CI Pipeline
* **File:** [.github/workflows/ci.yml](file:///home/arukast/Projects/localDev/projects/pos_alja/.github/workflows/ci.yml)
  * Sets up PHP 8.3 and Node 20 environment on push/PR to `main`.
  * Installs dependencies and runs key generation.
  * **Code Style Auto-Commit:** If code styling issues are detected, Pint fixes them and automatically commits the clean files back to the branch.
  * **Vulnerability Scanning:** Runs built-in package auditor `composer audit` to flag any vulnerable libraries.
  * **Static Analysis:** Executes Larastan static analyzer to catch bugs prior to tests running.
  * Compiles assets using Vite and executes PHPUnit tests.

#### 2. Dependabot Configuration
* **File:** [.github/dependabot.yml](file:///home/arukast/Projects/localDev/projects/pos_alja/.github/dependabot.yml)
  * Automated weekly checks for Composer, NPM, and GitHub Action dependencies.

#### 3. Contribution & Issue Templates
* **Issue Templates:**
  * [bug_report.md](file:///home/arukast/Projects/localDev/projects/pos_alja/.github/ISSUE_TEMPLATE/bug_report.md) — Bug reporting form structure.
  * [feature_request.md](file:///home/arukast/Projects/localDev/projects/pos_alja/.github/ISSUE_TEMPLATE/feature_request.md) — Feature proposal template.
* **PR Template:**
  * [pull_request_template.md](file:///home/arukast/Projects/localDev/projects/pos_alja/.github/pull_request_template.md) — Standardized checklist and verification guide.

#### 4. Static Analysis Setup
* **Files:**
  * [phpstan.neon](file:///home/arukast/Projects/localDev/projects/pos_alja/phpstan.neon) — Basic static analysis ruleset (checking level 5).
  * [phpstan-baseline.neon](file:///home/arukast/Projects/localDev/projects/pos_alja/phpstan-baseline.neon) — Pre-existing code lint exclusions to keep the CI check passing cleanly for future additions.

#### 5. Tailwind CSS Class Sorting (Prettier)
* **File:** [.prettierrc](file:///home/arukast/Projects/localDev/projects/pos_alja/.prettierrc)
  * Integrates `prettier-plugin-tailwindcss` and overrides configuration to automatically format `.blade.php` files as HTML.

#### 6. Git Pre-Commit Hooks (Husky & lint-staged)
* **Files:**
  * [.husky/pre-commit](file:///home/arukast/Projects/localDev/projects/pos_alja/.husky/pre-commit) — Hook running `npx lint-staged` locally.
  * [package.json](file:///home/arukast/Projects/localDev/projects/pos_alja/package.json) — Maps `.php` files to Pint and `.blade.php` files to Prettier class sorting pre-commit.

