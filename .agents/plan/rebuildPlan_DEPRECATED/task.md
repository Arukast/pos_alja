# Tasks: Rebuild POS using Laravel & PostgreSQL

## Phase 1: Project Setup & Database Migration
- `[x]` Initialize fresh Laravel 11 project `pos_alja` via PHP container
- `[x]` Configure database connections (`pgsql` and legacy `mysql_migration`) in `.env` and `config/database.php`
- `[x]` Create database migration files for PostgreSQL schema
- `[x]` Add `branches` table and `branch_id` foreign keys to PostgreSQL migrations (Multi-Branch support)
- `[x]` Develop the Artisan command `db:migrate-legacy` to transfer MariaDB data to PostgreSQL
- `[x]` Update `db:migrate-legacy` command to seed a default "Main Branch" and link all imported data to it
- `[x]` Fix orphan records & foreign key violations in `db:migrate-legacy` command and run legacy data import
- `[x]` Install Laravel Breeze (Livewire Starter Kit)
- `[x]` Create and run `LegacyMigrationTest` to verify imported database records

## Phase 2: Authentication & Role Skeleton
- `[x]` Set up user auth routes and session login
- `[x]` Create custom middleware/gates for `bm`, `owner`, `kasir`, `setting`, and `produksi` roles
- `[x]` Create responsive UI dashboard/layout skeletons for all 5 roles
- `[x]` Create and run `UserAuthorizationTest` to verify page access controls

## Phase 3: Pricing Engine & Order Entry
- `[x]` Create Eloquent models (`Branch`, `Customer`, `Product`, `Material`, `Machine`, `Finishing`, `Cut`, `Display`, `PricingMatrix`, `Invoice`, `Order`)
- `[x]` Implement `PricingService` for dynamic calculations (product specifications -> quotes)
- `[x]` Create and run `PricingServiceTest` to verify dynamic quote mathematics
- `[x]` Build the `Designer` (CS/Designer) order creation form in Livewire
- `[x]` Build the specs sheet and job tickets view

## Phase 4: Expenses, Inventory & Waste
- `[x]` Create Eloquent models (`Expense`, `WastedMaterial`)
- `[x]` Implement `InventoryService` for stock adjustments and logs
- `[x]` Integrate Expense forms and summaries in Cashier & Manager dashboards
- `[x]` Integrate Inventory list and update controls in Manager & Production dashboards
- `[x]` Integrate Wasted Materials logger in Production dashboard
- `[x]` Create and run tests for inventory ledger and expenses

## Phase 5: Cashiering & Live WebSockets
- `[x]` Integrate checkout payment forms on Cashier dashboard (`cashier.blade.php`)
  - `[x]` Add DP deposit inputs (DP amount, payment method)
  - `[x]` Add full settlement inputs (total paid, payment method, dynamic change calculation)
  - `[x]` Update invoice status, accounts receivable, and auditor fields
- `[x]` Optimize 80mm cashier receipt printing stylesheet (`@media print`)
  - `[x]` Add `/orders/{invoice}/receipt` route and controller
  - `[x]` Create receipt blade template styled for 80mm thermal printers
- `[x]` Build the Production Queue board and Live WebSockets syncing
  - `[x]` Install and configure Laravel Reverb broadcasting
  - `[x]` Create `OrderUpdated` broadcast event
  - `[x]` Broadcast changes on ticket creation, production state shifts, and payment updates
  - `[x]` Set up Livewire dashboard listeners to trigger reactive table reloads
- `[x]` Create and run `CashieringTest` to verify all payment scenarios and events
