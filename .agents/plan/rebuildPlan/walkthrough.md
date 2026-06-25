# Walkthrough - POS Rebuild Phases

This walkthrough documents the verified implementations of the print shop POS rebuild project.

---

## Phase 1 & 2: Database Migration & Role skeletons (Completed)

* See complete migration verification details below in the historical logs section.
* Replaced legacy user level designations with standard English names: `manager` (formerly `bm`), `owner` (formerly `owner`), `cashier` (formerly `kasir`), `designer` (formerly `setting`), and `production` (formerly `produksi`).

---

## Phase 3: Pricing Engine & Order Entry (Completed)

* **Service:** [PricingService.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Services/PricingService.php) calculates dynamic quotes.
* **Component:** [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php) handles order drafts.
* **Route:** `/orders/{invoice}/ticket` renders work orders [ticket.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/ticket.blade.php).
* **Test:** [PricingServiceTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Unit/PricingServiceTest.php) passes successfully (3 tests, 12 assertions).

---

## Phase 4: Expenses, Inventory & Waste (Completed)

We have built database models for expenses and print failure logs, created an inventory service to manage restocking arithmetic, integrated expense logging on the cashier board, added stock management controls on the manager board, and embedded failed print logs on the operator board.

### Changes Made

### 1. Database Eloquent Models
* **Location:** [app/Models/](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/)
  * [Expense.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Expense.php) (Tracks amount, classifications: modal/laba, cashier name, description)
  * [WastedMaterial.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/WastedMaterial.php) (Logs print failures, material, machine, operator, and reasons)

### 2. Inventory Service Layer
* **Location:** [InventoryService.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Services/InventoryService.php)
  * `addStock()`: Resets material parent fields (initial_stock, current_stock) and writes restock ledger logs.
  * `logWaste()`: Creates print failure logs, increments wasted stock counts, and updates the material ledger.
  * `deductStockOnPrint()`: Deducts stock based on order details when production starts.

### 3. Cashier Dashboard (Expense Log & Active Invoices)
* **Location:** [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
  * Added quick stats showing today's DP payments collected and cashier operational expenses.
  * Added an operational expense submission form with date, amount, description, and type selectors.
  * Rendered the recent cash drawer expense logs table.
  * Bounded the billing queue to actual active database invoices.

### 4. Manager Dashboard (Restock Form & Stock Levels)
* **Location:** [manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php)
  * Added material restocking form where managers can input restock counts and notes.
  * Rendered a live material inventory table with warning badges for low stock (remaining stock <= 10).
  * Rendered dynamic history logs of recent restocks queried from the stock ledgers database.

### 5. Production Operator Dashboard (Failed Prints Logger)
* **Location:** [production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php)
  * Added failed print logging form (Material, Machine, Responsible designer/operator, failure reasons, quantity).
  * Added active print queue orders list where operators can click **Start Printing** (which deducts material stock) and **Mark Done** (marking the job completed).
  * Rendered history of recent print failures.

---

## Verification & Execution Guide (Phase 4)

### 1. Run Automated Unit & Feature Tests
Verify the inventory calculations and dashboard Livewire actions inside the PHP container:
```bash
# Run unit tests
docker exec -it -w /var/www/html/pos_alja local-php83 ./vendor/bin/phpunit tests/Unit/InventoryServiceTest.php

# Run feature tests (Dashboard integrations & Expense logs)
docker exec -it -w /var/www/html/pos_alja local-php83 ./vendor/bin/phpunit tests/Feature/InventoryManagementTest.php
```

### 2. Verify Cashier Expenses & Queues (Manual)
1. Log in as a cashier.
2. Log an expense of Rp. 15,000 for "Sapu Baru" (broom), verify the total today and the table logs increment.
3. Check the active billing queue to see actual unpaid orders from the designers.

### 3. Verify Manager Restocks (Manual)
1. Log in as a manager.
2. Check the material inventory table, find a material with low stock.
3. Select the material in the Restock form, enter 100, and submit. Verify remaining stock recalculates immediately.

### 4. Verify Operator Waste & Production (Manual)
1. Log in as an operator.
2. In the active tickets queue, click **Start Printing** on a pending layout. Check database to verify material stock has decremented.
3. If a print layout fails, submit a **Failed Print** log. Verify the material's remaining stock updates instantly.

---

## Phase 5: Cashiering & Live WebSockets (Completed)

We have built cashier payment checkout flows (including down payments and final invoice settlements), designed receipt layouts optimized for 80mm thermal receipt printers, and configured Laravel Reverb WebSockets to synchronize dashboard orders in real time.

### Changes Made

### 1. Cashier Checkout & Payment
* **Location:** [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
  * Integrated a modal dialog that opens when cashiers click **Register DP** or **Checkout**.
  * Handled DP deposits (modifying `dp_amount`, `dp_paid`, and updating status to 1).
  * Handled final Settlements (pelunasan) including discount application, cash received, change calculation, and setting invoice status to 2 (Paid).
  * Audited cashier names on the database record (`cashier_name`, `cashier_dp_name`).

### 2. 80mm Thermal Receipt Layout
* **Location:** [receipt.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/receipt.blade.php)
  * Designed a compact layout styled inside an `80mm` container, matching the paper width of commercial thermal receipt printers.
  * Added print-specific media queries (`@media print`) to hide browser margins, scrollbars, headers/footers, and action buttons.
  * Shows headers, order items, subtotals, discount/DP, settlement payments, cash change, and terms policy.
  * Configured inline route `/orders/{invoice}/receipt` in [web.php](file:///home/arukast/Projects/localDev/projects/pos_alja/routes/web.php).

### 3. Broadcasting & WebSockets Synchronization
* **Location:** [OrderUpdated.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Events/OrderUpdated.php)
  * Created the `OrderUpdated` event class implementing `ShouldBroadcastNow` for synchronous real-time triggering.
  * Dispatched the event upon:
    * Order Creation: [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
    * Payment Checkout: [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
    * Production status change: [production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php)
  * Added Echo listener `$listeners` bindings on Cashier, Production, Manager, and Owner dashboards to automatically reload (`$refresh`) lists when other roles submit changes.

---

## Verification & Execution Guide (Phase 5)

### 1. Run Automated Cashiering Tests
Run the feature test suite inside the PHP container to verify payment calculations and validation logic:
```bash
docker exec -it -w /var/www/html/pos_alja local-php83 ./vendor/bin/phpunit tests/Feature/CashieringTest.php
```

### 2. Verify Receipt Printing Layout (Manual)
1. Log in as a cashier.
2. In the Active Billing queue, click the **Receipt** button on any invoice.
3. Verify that the layout is scaled for a narrow receipt (80mm width).
4. Click **Print** (Ctrl + P) and check that the browser print preview hides all page margins and dashboard navigation buttons.

### 3. Verify Real-time WebSockets Sync (Manual)
1. Start the Laravel Reverb WebSocket server inside the PHP Docker container:
   > [!NOTE]
   > Laravel Reverb requires the `pcntl` extension to catch system signals (SIGINT, etc.). We have added `pcntl` to the [Dockerfile](file:///home/arukast/Projects/localDev/php83/Dockerfile). 
   > Before running Reverb, rebuild the container using:
   > `sudo docker compose -f /home/arukast/Projects/localDev/docker-compose.yml up -d --build php83`

   ```bash
   sudo docker exec -it -w /var/www/html/pos_alja local-php83 php artisan reverb:start
   ```
2. Open two different browsers:
   - Browser A: Log in as a **Designer** at `/dashboard`
   - Browser B: Log in as a **Production Operator** at `/dashboard`
3. In Browser A, enter a new design order and save.
4. Verify that the order instantly appears on Browser B's active print queue table without any manual page reload.
5. In Browser B, click **Start Printing**. Verify that the status instantly updates on both screens.

---

## Environment & Routing Fixes (Completed)

* **Nginx Routing Resolution:** Replaced the rewrite-rule loop with a conditional root strategy (`if (-d ...)` in `default.conf`). Nginx now dynamically sets the root directory to the `/public` folder if it exists (for Laravel apps) or falls back to the project root (for flat legacy PHP apps), resolving the 403 Forbidden directory index error.
* **Reverb Port Conflict Fix:** Mapped Laravel Reverb internally from port `8080` (used by phpMyAdmin on the host) to port `8084` on the host within `docker-compose.yml` and `.env`, allowing successful client WebSocket connections.
* **Asset Compilation:** Rebuilt assets on the host to bake in the updated WebSocket ports.
* **Documentation:** Created a comprehensive `README.md` at [README.md](file:///home/arukast/Projects/localDev/projects/pos_alja/README.md) detailing the system architecture, event sequences, glossary of Indonesian terms, and setup commands.

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

