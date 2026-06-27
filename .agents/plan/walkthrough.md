# POS Schema Redesign & Replication-Ready Rebuild Walkthrough

This document outlines the print-shop POS rebuild, transitioning from the legacy auto-increment schema to a UUIDv7 relational structure. All 54 tests are fully green.

---

## 1. Database Schema Map (UUIDv7 & Relational)

Primary and foreign keys are UUIDv7 (via Laravel's `HasUuids` trait) to prevent conflicts during multi-branch synchronization.

### Relationship Diagram

```mermaid
erDiagram
    branches ||--o{ users : "has many"
    branches ||--o{ materials : "has many"
    branches ||--o{ machines : "has many"
    branches ||--o{ attributes : "has many"
    branches ||--o{ invoices : "has many"
    branches ||--o{ payments : "has many"
    branches ||--o{ expenses : "has many"
    branches ||--o{ wasted_materials : "has many"
    branches ||--o{ stock_ledgers : "has many"
    branches ||--o{ activity_logs : "has many"

    users ||--o{ invoices : "designs (designer_id)"
    users ||--o{ orders : "prints (operator_id)"
    users ||--o{ payments : "receipts (user_id)"
    users ||--o{ wasted_materials : "logs"
    users ||--o{ order_status_logs : "creates"
    users ||--o{ activity_logs : "performs"

    customers ||--o{ invoices : "has many"

    products ||--o{ pricing_matrices : "has many"
    materials ||--o{ pricing_matrices : "has many"
    machines ||--o{ pricing_matrices : "has many"
    attributes ||--o{ pricing_matrices : "finishing/cut/display"

    invoices ||--o{ orders : "has many"
    invoices ||--o{ payments : "has many"
    orders ||--o{ order_status_logs : "has many"
    orders ||--o{ wasted_materials : "optional link"
```

### Core Table Catalog

For detailed field-level specifications, constraints, and validation parameters, refer to the [schema_details.md](file:///home/arukast/Projects/localDev/projects/pos_alja/.agents/plan/schema_details.md) reference sheet.

| Table | Description | Primary Key | Key Foreign Keys |
| :--- | :--- | :--- | :--- |
| **`branches`** | Physical shop branches. | UUID | - |
| **`users`** | Shop employees. Roles: `owner`, `manager`, `cashier`, `designer`, `production`. | UUID | `branch_id` |
| **`customers`** | Customers (`Reguler` / `Mitra`). | UUID | - |
| **`products`** | Catalog items (pricing type: `unit` or `area`). | UUID | - |
| **`materials`** | Raw inventory tracks (Paper, banners, etc.) with stock levels. | UUID | `branch_id` |
| **`pricing_matrices`** | Price engine tier matching based on options and quantity thresholds. | UUID | `product_id`, `material_id`, `machine_id`, `finishing_id`, `cut_id`, `display_id` |
| **`invoices`** | Main sale metadata (discount, AR, deadline, status). | UUID | `branch_id`, `customer_id`, `designer_id` |
| **`orders`** | Print jobs/lines under an invoice. | UUID | `invoice_id`, `pricing_matrix_id`, `operator_id` |
| **`payments`** | Tracks transaction logs (down payments, settlements). | UUID | `branch_id`, `invoice_id`, `user_id` |
| **`expenses`** | Cashier drawer payouts. | UUID | `branch_id` |
| **`wasted_materials`** | Logs failed prints and waste reasons. | UUID | `branch_id`, `material_id`, `machine_id`, `order_id`, `designer_id`, `operator_id` |
| **`stock_ledgers`** | Audit trail cards for inventory. | UUID | `branch_id`, `material_id` |
| **`order_status_logs`** | Order state history (Pending $\rightarrow$ Printing $\rightarrow$ Completed). | UUID | `order_id`, `user_id` |
| **`activity_logs`** | System-wide audit log trail. | UUID | `branch_id`, `user_id` |

---

## 2. Core Business Logic & Operation Rules

### Cashier Payments & Balance Recalculation
- **Recalculation**: Invoices compute balance from the `payments` log instead of raw fields.
  $$\text{accounts\_receivable} = \max(0, \text{grand\_total} - \sum(\text{payments.amount}))$$
- **Invoice Status**: `0` = Unpaid, `1` = DP Paid (balance > 0), `2` = Fully Paid (balance <= 0).
- **Payment Log**: Writes to `payments` with cash/transfer/QRIS details.

### Designer Order Intake
- **Numbering**: Dynamic prefix `INV-[BranchPrefix]-[YYYYMMDD]-[4-digit Seq]` (e.g. `INV-MN-20260627-0001`).
- **Tiered Pricing**: Selects pricing matrix with max `min_qty` $\le$ quantity.
- **Manual Override**: Sets `is_manual_price = true` to allow custom input price/cost.

### Production Job Progression
- **Handshake & Status**: Moves order to printing/completed, logging state changes, and sets operator ID.
- **Deduction**: Deducts stock via `InventoryService` (area-based uses `quantity * width * length`, unit-based uses `quantity`).

> [!IMPORTANT]
> **Financial Metric Columns**:
> - **Invoice level**: `grand_total` (Revenue/Omset), `total_cost` (HPP), `total_profit` (Profit/Laba).
> - **Order level**: `subtotal`, `cost`, `profit`.
> - **Material Waste**: `WastedMaterial` model and `wasted_materials` table.

---

## 3. Refactoring & Implementation Summary

| Refactoring Step | Key Files Modified | Core Implementation Changes |
| :--- | :--- | :--- |
| **Step 0: Inventory Service** | [InventoryService.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Services/InventoryService.php) | Changed parameter signatures to `string` (UUID). Removed `(int)` casts. Dynamic branch lookup fallback. |
| **Step 1: Cashier Dashboard** | [Cashier.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Cashier.php)<br>[cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)<br>[receipt.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/receipt.blade.php) | Converted state IDs to UUID strings. Implemented payment logs on checkout. Recalculated invoice balances dynamically. |
| **Step 2: Production Dashboard** | [Production.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Production.php)<br>[production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php) | Updated order transitions to write history logs. Deducted inventory on print job start. Audited waste log by designer and operator UUIDs. |
| **Step 3: Owner Dashboard** | [Owner.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Owner.php)<br>[owner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/owner.blade.php) | Switched metrics to aggregate actual payments. Refactored the designer leaderboard query to group by users UUID. Added System Audit Trail. |
| **Step 4: Manager Dashboard** | [Manager.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Manager.php)<br>[manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php) | Updated restocking validation rules to accept UUID strings. Corrected logs queries to order by `created_at` timestamp. |
| **Step 5: Test & Layout Cleanups** | [UserManagement.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Admin/UserManagement.php)<br>[0001_01_01_000000_create_users_table.php](file:///home/arukast/Projects/localDev/projects/pos_alja/database/migrations/0001_01_01_000000_create_users_table.php)<br>Blade components & Test classes | Changed Laravel `sessions` `user_id` to UUID type in users migration. Cleaned up view components to declare merged attributes within `@php` blocks to avoid Prettier formatting compiler errors. |

---

## 4. Verification & Verification Results

### Automated Test Runs
Execute the suite inside the container:
```bash
docker exec -it -w /var/www/html/pos_alja local-php83 php artisan test
```

**Results**: All **54 tests passed** successfully (198 assertions). Legacy migration tests are safely skipped.

### Manual Verification
1. **Re-seed**: `php artisan migrate:fresh --seed` (verifies PostgreSQL schema compatibility).
2. **Order Flow**: Intake order $\rightarrow$ cashier DP payment log $\rightarrow$ production print start (stock deduction) & completion $\rightarrow$ final cash settlement.

---

## Phase 1 — CSS Design System (Completed)

We have rebuilt the CSS design system in `resources/css/app.css` to support both light and dark themes using Tailwind CSS v4 variables, and cleaned up legacy styles.

### Changes Made

- **Theme Surface Tokens**: Registered light mode surface tokens inside `@theme` in [app.css](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/css/app.css):
  - `--color-light-bg: #F8F9FA`
  - `--color-light-surface: #FFFFFF`
  - `--color-light-surface-2: #F1F3F5`
  - `--color-light-border: #E5E7EB`
  - `--color-light-text: #111827`
  - `--color-light-muted: #6B7280`
- **Removed Helper Classes**: Cleaned up the file by removing obsolete custom CSS classes that can be expressed as Tailwind CSS v4 utility classes:
  - `:root` wrapper with duplicate `--brand-*` definitions.
  - `.brand-stripe`, `.brand-dot`, and `.brand-dot-*` colors.
  - `.brand-glass`, `.brand-focus-ring`, gradients, and `.hover-lift`.
- **Dynamic Scrollbars & Selection**: Refactored `::selection` and scrollbars to dynamically adapt to light/dark themes and reference `@theme` properties.
- **Dynamic Accent Stripes**: Refactored `.brand-stripe-bar` to reference dynamic `@theme` variables so it responds correctly to runtime branding color overrides.

---

## Phase 2 — Layout Shell (Completed)

We have rebuilt the layout shell and navigation header, transitioning the application from a top navigation bar to a modern collapsible sidebar layout for desktop viewports and a slide-in drawer layout for mobile viewports.

### Changes Made

- **Query-Based Tab State**: Bound the `$activeTab` property in [Owner.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Owner.php) and [Manager.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Livewire/Dashboards/Manager.php) to the URL query string using Livewire's `#[Url(as: 'tab')]` attribute. This allows seamless tab-switching navigation across pages and persistent tab state during Livewire `wire:navigate` transitions.
- **Collapsible Layout Shell**: Updated [app.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/layouts/app.blade.php) to establish a full-height flex container on desktop and initialize Alpine.js states for coordinating sidebar expanded/collapsed visibility (`sidebarOpen` persisted in `localStorage`) and mobile drawer visibility (`mobileOpen`).
- **Sidebar & Mobile Drawer Component**: Rebuilt [navigation.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/layout/navigation.blade.php) from scratch to support two responsive layouts:
  - **Desktop Collapsible Sidebar**: Left column layout with smooth width transitions (`w-60` to `w-16`) containing the brand stripe, logo, collapsible menu groups, initials-based user avatar, dynamic theme toggler, and logout button.
  - **Mobile Drawer Overlay**: Slide-in panel (width `w-60`) shown below `lg` breakpoint, controlled by a hamburger button in the thin mobile header, with a semi-transparent backdrop overlay.
- **Role-Based Navigation Menus**: Implemented menu structures for all 5 roles (`owner`, `manager`, `cashier`, `designer`, `production`) exactly as mapped in the master design plan.
- **Aesthetic and UX Enhancements**: Added dynamic theme switching, smooth transitions, initials-based user avatar, and hover tooltips for all navigation items.

---

## Phase 3 — Shared Components (Completed)

We have rebuilt all 12 shared Blade UI components to be fully compatible with the new light/dark mode design system and the ALJA brand identity.

### Changes Made

- **Modal Component (`modal.blade.php`)**: Added the `<x-brand-stripe />` component to the top edge of the modal panel for consistent branding. Standardized the backdrop styling with `bg-black/50 backdrop-blur-sm` and borders with `border-light-border dark:border-surface-300/50`.
- **Primary Button (`primary-button.blade.php`)**: Made focus ring-offset theme-aware (`focus:ring-offset-light-bg dark:focus:ring-offset-surface-800`).
- **Secondary Button (`secondary-button.blade.php`)**: Redesigned to use the brand's secondary Cyan color (`bg-brand-secondary hover:bg-brand-secondary-hover`) and secondary glow (`shadow-glow-secondary`). Added theme-aware focus ring offsets.
- **Danger Button (`danger-button.blade.php`)**: Retained standard red background styling but made the focus ring-offset theme-aware.
- **Text Input (`text-input.blade.php`)**: Standardized inputs to use light-border and surface tokens: `border-light-border dark:border-surface-300/50 bg-white dark:bg-surface-500 text-light-text dark:text-gray-100 placeholder-light-muted dark:placeholder-gray-400`.
- **Input Label (`input-label.blade.php`)**: Switched label colors to use `text-light-text dark:text-gray-300`.
- **Input Error (`input-error.blade.php`)**: Re-stylized error messages to use higher contrast red: `text-red-600 dark:text-red-400 text-xs font-medium`.
- **Dropdown (`dropdown.blade.php`)**: Updated default content classes to use the light mode border token (`border-light-border dark:border-surface-300/50`), removed the redundant ring style, and wrapped content in a `rounded-xl overflow-hidden` container.
- **Dropdown Link (`dropdown-link.blade.php`)**: Made link hover and focus text/background colors theme-aware (`text-light-text dark:text-gray-300 hover:bg-brand-primary/10 hover:text-brand-primary`).
- **Brand Logo (`brand-logo.blade.php`)**: Added support for configured image logos (`config('brand.logo')`) with size-appropriate heights (`sm`/`md`/`lg`), falling back to the standard brand dots and text style.
- **Brand Header Banner (`brand-header-banner.blade.php`)**: Cleaned up contrasting gradients in light mode to use a subtle border-matched gradient. Standardized typography colors using `text-light-text` and `text-light-muted`.
- **Brand Stripe (`brand-stripe.blade.php`)**: Verified as accent element using the `.brand-stripe-bar` CSS class.

---

## Phase 4 — Dashboard Rebuilds (Completed)

We have rebuilt the visual layouts of all 5 role-based dashboards to support the new light/dark mode design system, accessibility contrast guidelines, and the ALJA brand identity.

### Changes Made

- **Owner Dashboard (`owner.blade.php`)**:
  - Replaced the root layout spacing with high-contrast text sizing (`text-light-text dark:text-gray-100`).
  - Redesigned branch selector and period controls to adapt cleanly to light and dark modes within the header banner slot.
  - Standardized financial KPI cards (Omset, HPP, Laba, Expenses), the SVG sparkline margins trend chart, and the Designer Leaderboard.
  - Standardized the Audit Trail table structure, headers, alternating row colors, and payload codes.
- **Manager Dashboard (`manager.blade.php`)**:
  - Rebuilt inventory stock trackers, machinery status cards, and the restocking form.
  - Updated the restocking forms inputs, selectors, textareas, and submit buttons to be theme-aware.
  - Standardized the Inventory Ledger and Material Inventory Levels tables, with dynamic red-alert highlight rows for low stock levels.
  - Rebuilt the drawer expenses card lists.
- **Cashier Dashboard (`cashier.blade.php`)**:
  - Redesigned the awaiting checkout queue table, customer pickup queue, and expenses tables.
  - Updated status badges/pills to match standard color mappings for Paid, Down Payment, and Unpaid states.
  - Rebuilt the Checkout Modal with proper backdrop-blur overlays, brand gradient stripes, input payment types, bank transfer/QRIS select dropdowns, and calculations previews.
- **Designer Dashboard (`designer.blade.php`)**:
  - Redesigned the customer search intake and customer creation form layout.
  - Styled all Quote Calculator option dropdowns, inputs, and prepress textareas.
  - Standardized the estimated subtotal block and the pre-queued draft order summary table.
  - Re-stylized the recently placed invoices list table and job ticket Work Order links.
- **Production Dashboard (`production.blade.php`)**:
  - Rebuilt active print queue tickets as readable rows with clear statuses (Pending/Printing), operator badges, and action buttons.
  - Restyled print waste forms and the failed runs audit tables.
