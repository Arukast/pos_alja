# Walkthrough Archive - Phases 6 to 10

This archive documents the completed Phases 6 to 10 of the print shop POS rebuild project.

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

---

## Phase 10: Tailwind CSS v4 & Livewire v4 Upgrade (Completed Code Changes)

We have upgraded the application build system to Tailwind CSS v4 (using the `@tailwindcss/vite` plugin) and bumped the backend dependencies to support Livewire v4. As part of this upgrade, we fully cleaned up the `livewire/volt` package by converting all remaining 7 Volt components into standard PSR-12 compliant Class-Based Livewire components.

### Changes Made

#### 1. Configured Dependencies and Asset Build Tools
* **[package.json](file:///home/arukast/Projects/localDev/projects/pos_alja/package.json):** Removed `postcss`, `autoprefixer`, and `tailwindcss` (v3). Added `tailwindcss` (v4.0.0) and `@tailwindcss/vite` (v4.0.0).
* **[composer.json](file:///home/arukast/Projects/localDev/projects/pos_alja/composer.json):** Bumped `livewire/livewire` to `^4.0` and removed `livewire/volt`.
* **[vite.config.js](file:///home/arukast/Projects/localDev/projects/pos_alja/vite.config.js):** Configured and registered the new `@tailwindcss/vite` plugin.
* **[postcss.config.js](file:///home/arukast/Projects/localDev/projects/pos_alja/postcss.config.js) & [tailwind.config.js](file:///home/arukast/Projects/localDev/projects/pos_alja/tailwind.config.js):** Overwritten with deprecation/migration notices.
* **[bootstrap/providers.php](file:///home/arukast/Projects/localDev/projects/pos_alja/bootstrap/providers.php) & [VoltServiceProvider.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Providers/VoltServiceProvider.php):** Removed and disabled the Volt service provider.

#### 2. Migrated Design System & Custom Classes
* **[app.css](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/css/app.css):** Upgraded styling directives to the new Tailwind CSS v4 syntax (`@import "tailwindcss";` and `@plugin "@tailwindcss/forms";`). Migrated the custom ALJA design system theme (fonts, colors, keyframes, shadows, and animations) into the `@theme` directive, utilizing standard CSS custom properties.

#### 3. Refactored remaining Volt Components to Class-Based Livewire
Created class files and stripped inline Volt PHP code from Blade templates:
* Layout Navbar: [Navigation.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Layout/Navigation.php) & [navigation.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/layout/navigation.blade.php)
* Login Screen: [Login.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Pages/Auth/Login.php) & [login.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/login.blade.php)
* Confirm Password Screen: [ConfirmPassword.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Pages/Auth/ConfirmPassword.php) & [confirm-password.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/confirm-password.blade.php)
* Forgot Password Screen: [ForgotPassword.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Pages/Auth/ForgotPassword.php) & [forgot-password.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/forgot-password.blade.php)
* Register Screen: [Register.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Pages/Auth/Register.php) & [register.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/register.blade.php)
* Reset Password Screen: [ResetPassword.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Pages/Auth/ResetPassword.php) & [reset-password.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/reset-password.blade.php)
* Verify Email Screen: [VerifyEmail.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Pages/Auth/VerifyEmail.php) & [verify-email.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/verify-email.blade.php)

* Welcome page navigation: [Navigation.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Welcome/Navigation.php)

#### 4. Updated Auth Routing & Feature Tests
* **[auth.php](file:///home/arukast/Projects/localDev/projects/pos_alja/routes/auth.php):** Replaced Volt routing with standard Laravel route rules pointing to the class-based components.
* **Refactored Test Files:** Replaced all Volt test assertions and testing helpers with native Livewire assertions:
  * [AuthenticationTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/Auth/AuthenticationTest.php)
  * [PasswordConfirmationTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/Auth/PasswordConfirmationTest.php)
  * [PasswordUpdateTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/Auth/PasswordUpdateTest.php)
  * [UserAuthorizationTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/UserAuthorizationTest.php)

---

## Verification & Execution Guide (Phase 10)

Because direct workspace execution via terminal is restricted, please verify the upgrades by running the following commands inside the container environment:

### 1. Update Backend Dependencies
Update Livewire and remove the old Volt dependencies from the container shell:
```bash
sudo docker exec -it -w /var/www/html/pos_alja local-php83 composer update livewire/livewire --with-all-dependencies
sudo docker exec -it -w /var/www/html/pos_alja local-php83 composer remove livewire/volt
```

### 2. Update Frontend Assets and Build
Install the new Tailwind CSS v4 dependencies and compile the assets on the host machine directly (not inside the PHP container):
```bash
npm install
npm run build
```

### 3. Clear Application Cache and Run Tests
Clear any cached application configurations and run the PHPUnit test suite:
```bash
sudo docker exec -it -w /var/www/html/pos_alja local-php83 php artisan optimize:clear
sudo docker exec -it -w /var/www/html/pos_alja local-php83 ./vendor/bin/phpunit
```
