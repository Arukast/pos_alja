# Master Rebuild POS Implementation Plan

We are rebuilding the legacy CodeIgniter/Procedural PHP POS system (`pos_dp`) for **ALJA digiprint** into a modern, reactive Laravel web application (`pos_alja`) using PostgreSQL, Livewire, Tailwind CSS, and Laravel Reverb.

---

## Technical Stack & Architecture

1. **Backend:** Laravel 11 with PostgreSQL as the primary database.
2. **Frontend:** Livewire 3 (reactive, stateful components) + Tailwind CSS + Alpine.js (subtle micro-animations, glassmorphism, responsive dashboard).
3. **Database Migration:** Connect Laravel to both PostgreSQL (new) and MariaDB (old), running a custom Artisan command to map and migrate historical data.
4. **Real-time Syncing:** Laravel Reverb (WebSocket server) + Laravel Echo (client) to push instant ticket and status updates across roles (Cashier, Designer/CS, Production) without page reloading.
5. **Docker Integration:** Hook the new project into the existing Docker Compose stack (`localDev/docker-compose.yml`), mapping the app container to PHP 8.3.
6. **Service-Oriented Design (API-Ready):** All core business logic (e.g. order placement, calculations, stock adjustments) will be written inside isolated PHP **Service classes**. The Livewire controllers will call these Services. This ensures we don't build a redundant REST API today, but if a mobile/external client is developed in the future, we can add REST API endpoints in minutes by simply exposing these services.
7. **Multi-Branch Tenant Scoping:** Support multiple physical print shop branches using a single consolidated database. A `branches` table will hold branch metadata (e.g. Menes Main Branch, Pandeglang Branch). Tables containing branch-specific data (`users`, `materials`, `machines`, `invoices`, `expenses`, `stock_ledgers`, `wasted_materials`) will include a `branch_id` foreign key. Laravel Global Scopes will dynamically restrict designers, cashiers, and production staff to their active branch, while allowing the Owner to view combined stats or switch branches seamlessly.

---

## Accounting & Financial Reports (Owner Module)
We will map and consolidate the legacy reports (`laporan_keuangan`, `laporan_penjualan`, `laporan_bulanan`, `saldo`) into a clean, modern financial analytics dashboard for the **Owner**:
* **Real-time Income Statement:** Displays **Omset (Revenue)**, **HPP/Modal (Base Material/Service Costs)**, and **Profit (Net Margins)**.
* **Date-Range Period Filters:** Users can filter logs dynamically using a calendar range (corresponds to the legacy "Dari / Sampai" filter).
* **Expense Classification:** Distinguish between **Pengeluaran Modal (Capital Expenses)** and **Pengeluaran Laba (Revenue-based Expenses)** to compute the final net **Saldo (Balance)**.
* **Staff Performance Reports:** Track individual designer/cashier metrics (previously `performa_individu.php`).
* **Print Options:** PDF and clean browser print optimization for all accounting views.

---

## Material & Inventory Management
We will convert the legacy material stock tracking (`stok_bahan`) into a robust inventory ledger:
* **Unit Categorization:** Full support for printing material types:
  * `lembar` (sheet-based paper)
  * `meter` (roll-based banners, computing printed dimensions in square meters)
  * `pcs` (discrete accessories/units)
  * Display stands, stamps, etc.
* **Automatic Stock Ledger System:** Dynamically compute stock levels using the formula:
  $$\text{Sisa Stok} = \text{Stok Awal} + \text{Penambahan Stok} - \text{Tercetak (Order Qty)} - \text{Gagal Cetak (Wasted)}$$
* **Waste Tracking:** Integrate a form for **Produksi (Production)** to log `wasted_materials` (`bahan_gagal`), tracking the responsible designer, printer machine, material type, and the quantity/length lost.
* **Low Stock Alerts:** Highlight items that fall below a minimum quantity threshold.

---

## Security & Protection Measures
The old application contains severe security vulnerabilities (plain MD5 hashing, raw SQL query concatenation, lack of input filtering, no CSRF checks). The Laravel rebuild will enforce state-of-the-art security patterns:
* **Secure Hashing:** All passwords will use secure **Bcrypt/Argon2** hashing. (Legacy MD5 passwords are fully reset to a temporary default password during migration, forcing users to reset them).
* **SQL Injection Prevention:** Leverage Eloquent ORM and Query Builder parameter binding for all database communication, eliminating raw SQL injections.
* **CSRF (Cross-Site Request Forgery) Protection:** Automate CSRF verification for all form submissions and Livewire requests.
* **XSS (Cross-Site Scripting) Defense:** Auto-escape outputs using Blade template directives (`{{ $data }}`).
* **Role-Based Authorization:** Secure endpoints using Laravel Middleware and Gates to strictly restrict routes by role (`manager`, `owner`, `cashier`, `designer`, `production`).

---

## User Roles & Workflows

We are updating the 5 roles present in the old codebase to standard English POS terminology:
* **Manager** (previously `bm`): Manage users, material stock, display/finishing master data, pricing engine matrices, and see comprehensive logs.
* **Owner** (previously `owner`): Financial statements, profit/loss charts, monthly reports, designer/production performance trackers.
* **Cashier** (previously `kasir`): Payment checkouts, Down Payments (DP), balance settlements, cashier expense logs, and receipt printing.
* **Designer** (previously `setting`): Intake customer files, size parameters, calculate instant quotes, create Work Orders (WO), print designer spec sheets, and update status.
* **Production** (previously `produksi`): Check print queues, assign to machines, log material waste/failed prints, decrement stock ledger, and mark jobs as completed.

---

## Proposed Changes (Rebuilding Skeletons & Roles)

We are renaming roles and updating their respective files and queries:

### 1. Database Migration Command [MODIFY]
#### [MODIFY] [MigrateLegacyData.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Console/Commands/MigrateLegacyData.php)
Translate legacy user levels dynamically in the users import loop:
```php
$role = match ($u->level) {
    'bm' => 'manager',
    'owner' => 'owner',
    'kasir' => 'cashier',
    'setting' => 'designer',
    'produksi' => 'production',
    default => 'cashier',
};
```

### 2. User Factory [MODIFY]
#### [MODIFY] [UserFactory.php](file:///home/arukast/Projects/localDev/projects/pos_alja/database/factories/UserFactory.php)
Update the default role generated for tests from `'kasir'` to `'cashier'`.

### 3. Dashboard Container View [MODIFY]
#### [MODIFY] [dashboard.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/dashboard.blade.php)
Update conditionals to check for new role names and render renamed Volt sub-dashboard files.

### 4. Volt Dashboard Components [RENAME & NEW]
Rename components under [dashboards/](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards):
* Rename `bm.blade.php` to [manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php)
* Rename `kasir.blade.php` to [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
* Rename `setting.blade.php` to [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
* Rename `produksi.blade.php` to [production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php)
* Keep `owner.blade.php` intact.

### 5. Automated Tests [MODIFY]
#### [MODIFY] [UserAuthorizationTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Feature/UserAuthorizationTest.php)
Update role factory attributes and view expectations (e.g. `dashboards.manager` instead of `dashboards.bm`) to align with the new schema.

---

## Verification Plan (Role Verification)

### Automated Verification & Unit Tests
We will run all PHPUnit test suites inside the Docker container:
```bash
sudo docker exec -it -w /var/www/html/pos_alja local-php83 php artisan migrate:fresh
sudo docker exec -it -w /var/www/html/pos_alja local-php83 php artisan db:migrate-legacy
sudo docker exec -it -w /var/www/html/pos_alja local-php83 php artisan test
```

Make sure both the migration test and role authorization tests pass.

---
---

# Phase 3 Implementation Plan: Pricing Engine & Order Entry

This plan describes the implementation details for **Phase 3 (Pricing Engine & Order Entry)** of the `pos_alja` POS rebuild.

## Proposed Database Models

To leverage Laravel's Eloquent ORM and relationship capabilities for order processing and dashboard rendering, we will create the necessary models.

### [NEW] Eloquent Models

* #### [NEW] [Branch.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Branch.php)
  Manages branch scope data.
* #### [NEW] [Customer.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Customer.php)
  Handles customer accounts (name, phone, address, type, etc.).
* #### [NEW] [Product.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Product.php)
  Tracks printing product categories (Merchandise, Large Format, Print Kertas, etc.).
* #### [NEW] [Material.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Material.php)
  Manages print materials (category, stock, cost, price).
* #### [NEW] [Machine.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Machine.php)
  Represents production printing machinery.
* #### [NEW] [Finishing.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Finishing.php)
  Post-processing finishing options.
* #### [NEW] [Cut.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Cut.php)
  Cutting styles (e.g. half cut, full cut).
* #### [NEW] [Display.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Display.php)
  Display accessories/stands.
* #### [NEW] [PricingMatrix.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/PricingMatrix.php)
  The core pricing table mapped to legacy `harga_jual` rows.
* #### [NEW] [Invoice.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Invoice.php)
  Tracks transaction orders, total billing, and payments.
* #### [NEW] [Order.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/Order.php)
  Tracks individual line items under an invoice.

---

## Dynamic Pricing Engine (`PricingService`)

We will build an isolated service to handle complex lookup queries matching legacy pricing matrices.

### [NEW] [PricingService.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Services/PricingService.php)

#### Calculations Workflow

The service will take parameters (product category, materials, dimensions, quantities) and execute category-specific matrix matchers:

1. **Merchandise (1):** Matches product, machine, material, finishing, sides, and finishing sides.
2. **Jasa Desain (2):** Matches product, machine, and material.
3. **Display (3):** Matches product, machine, material, and display.
4. **Print Kertas (4):** Matches product, machine, material, sides, finishing, finishing sides, and cut.
5. **Large Format (5):** Matches product, machine, material, and finishing.
6. **ID Card (6):** Matches product, machine, material, display, and sides.
7. **Stempel (7):** Matches product, machine, material, finishing, and sides.
8. **Kartu Nama (8):** Matches product, machine, finishing, material, sides, finishing sides, and display.
9. **Produk Jadi (9):** Matches product, machine, and material.
10. **NOTA (10):** Matches product, machine, material, and sides.
11. **DTF Large Format (11):** Matches product, machine, and material.

#### Mathematics formulas
* For **Large Format (5)**:
  $$\text{Subtotal} = \text{Matrix Price} \times \text{Quantity} \times \text{Width (m)} \times \text{Length (m)}$$
  $$\text{Total Cost (HPP)} = \text{Matrix Cost} \times \text{Quantity} \times \text{Width (m)} \times \text{Length (m)}$$
* For **all other categories**:
  $$\text{Subtotal} = \text{Matrix Price} \times \text{Quantity}$$
  $$\text{Total Cost (HPP)} = \text{Matrix Cost} \times \text{Quantity}$$
* **Profit:** $\text{Subtotal} - \text{Total Cost}$.

If no pricing configuration is found, the service throws a `PricingMatrixNotFoundException` with context parameters, allowing clean Livewire alert feedback.

---

## CS / Designer Order Entry Form

We will completely rebuild the order intake wizard in a single-page reactive Volt component.

### [MODIFY] [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)

The new interface will feature:
1. **Interactive Customer Selector:**
   * Combobox search autocomplete matching names/phones dynamically.
   * Toggleable "New Customer" panel displaying quick fields (Name, Phone, Address, Type) to create a customer on the fly.
2. **Dynamic Order Parameter Inputs:**
   * Render input elements (Dropdowns for machines/finishing, numeric dimensions) dynamically based on selected product categories.
   * Real-time quote preview updating subtotal, cost (HPP), and profit parameters directly as inputs change.
3. **Draft Item Queue (Pre-Save Cart):**
   * Pre-queued item table with delete actions.
   * Automatic invoice summaries (subtotal, customizable discount, net totals).
4. **Order Submission:**
   * Validates inputs, creates customer if required, writes the `Invoice` and associated `Order` records in a safe database transaction, and alerts designers.

---

## Printable Job Tickets (WO Sheets)

We will build a clean printer-optimized layout for production tracking.

### [NEW] [ticket.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/ticket.blade.php)

* Accessible via `/orders/{order}/ticket`.
* Uses Tailwind print styles to suppress sidebar/navbar components and display a clean high-contrast, black-and-white print sheet.
* Contains QR code/barcodes placeholders, customer meta, dimensions, and checklist grids (Design, Printing, Finishing, QC) for operators.

### [MODIFY] [web.php](file:///home/arukast/Projects/localDev/projects/pos_alja/routes/web.php)

* Register ticket generation route.

---

## Verification Plan (Phase 3)

### Automated Tests
We will build a comprehensive test suite to run in the SQLite environment:
* #### [NEW] [PricingServiceTest.php](file:///home/arukast/Projects/localDev/projects/pos_alja/tests/Unit/PricingServiceTest.php)
  Fills local database seeds, verifies:
  1. Lookups return matrix prices.
  2. Large Format area calculations are mathematical.
  3. Proper exceptions are thrown for non-defined matrix parameters.
  4. Proper rounding is applied.

Execute tests using Docker CLI:
```bash
sudo docker exec -it -w /var/www/html/pos_alja local-php83 php artisan test
```

### Manual Verification
1. Log in as a designer (`designer` role).
2. Create a customer, fill parameters, watch real-time quote preview calculate.
3. Verify adding items updates the summary layout.
4. Submit and verify database records are written correctly.
5. Print the job ticket sheet using browser print controls (`Ctrl + P`), verifying headers are hidden and styling matches physical paper widths.
