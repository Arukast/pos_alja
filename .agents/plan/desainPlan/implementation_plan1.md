# Phase 6 Implementation Plan: UI Bugs, Styling Mismatches & Multi-Branch Scoping

We are resolving all hardcoded UI data, fixing theme-breaking styling mismatches (like white modals/dropdowns with hidden text on a dark theme), integrating dynamic multi-branch scoping across all dashboards, adding a dark/light mode toggle, fixing critical pricing matrix mapping issues, and adding a cashier customer pickup queue.

---

## 1. Target Objectives & Proposed Changes

### A. Theme Styling, Dark/Light Mode & UX Polishing

#### [MODIFY] [app.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/layouts/app.blade.php)
* **HTML & Body Tag:** Replace the hardcoded `class="dark"` on `<html>` with an inline JavaScript block that checks `localStorage` and applies the `dark` class. Update the body class to support light mode backgrounds and text:
  `class="font-sans antialiased bg-gray-50 text-gray-800 dark:bg-surface-800 dark:text-gray-200"`
* **Header and Ambient Blobs:** Adapt header background colors to toggle nicely and hide ambient background glow blobs in light mode.

#### [MODIFY] [guest.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/layouts/guest.blade.php)
* **HTML & Body Tag:** Update `<html class="dark">` to load the theme from `localStorage` dynamically. Restyle the body to support light mode:
  `class="font-sans antialiased bg-gray-50 text-gray-800 dark:bg-surface-900 dark:text-gray-200"`

#### [MODIFY] [navigation.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/layout/navigation.blade.php)
* **Dark Mode Toggle Button:** Insert a theme switch button in both desktop and mobile headers. Toggling the button uses Alpine.js/JavaScript to add/remove the `dark` class from `document.documentElement` and saves `'light'` or `'dark'` to `localStorage`.
* **Light Mode Colors:** Restyle background classes from absolute dark surfaces (`bg-surface-700`) to responsive classes (`bg-white/90 dark:bg-surface-700/90 border-gray-200 dark:border-surface-300/40`). Restyle text and link colors to match.

#### [MODIFY] [text-input.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/text-input.blade.php)
* **Light/Dark Mode support:** Replace the hardcoded dark background and border styling with responsive classes:
  `border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500`

#### [MODIFY] [input-label.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/input-label.blade.php)
* Change class from `text-gray-300` to `text-gray-700 dark:text-gray-300`.

#### [MODIFY] [nav-link.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/nav-link.blade.php)
* Update active/inactive text styles to render nicely in light mode (using `text-gray-900` for active and `text-gray-500 hover:text-gray-900` for inactive).

#### [MODIFY] [responsive-nav-link.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/responsive-nav-link.blade.php)
* Restyle active/inactive backgrounds and border classes for light mode compatibility.

#### [MODIFY] [dropdown.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/dropdown.blade.php)
* Update `contentClasses` and `ring` borders to utilize responsive light/dark classes:
  `contentClasses' => 'py-1 bg-white dark:bg-surface-600 border border-gray-200 dark:border-surface-300/50'`
  `ring-1 ring-gray-200 dark:ring-surface-200/50`

#### [MODIFY] [dropdown-link.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/dropdown-link.blade.php)
* Update text colors to `text-gray-700 dark:text-gray-300 hover:bg-alja-magenta/10 hover:text-gray-900 dark:hover:text-white`.

#### [MODIFY] [modal.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/modal.blade.php)
* Update the modal container and overlay styling to look crisp in both dark and light modes:
  `bg-white dark:bg-surface-600 border border-gray-200 dark:border-surface-300/50`

---

### B. Core Functional Fixes & Features

#### [MODIFY] [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
* **Pricing Matrix ID Mapping Bug:** Add a `public $calcMatrixId = 0;` property. In `calculateQuote()`, save the result's `pricing_matrix_id` to this property. In `addItem()`, ensure `'pricing_matrix_id' => $this->calcMatrixId` is stored in the item queue array so order records carry the pricing reference required for inventory stock adjustments.
* **Customer Dropdown & Panels:** Adapt card layouts and selector lists for light/dark mode compatibility.

#### [MODIFY] [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
* **Customer Pickup Queue:** Add a `Pending Customer Pickup` table. Fetch all invoices with `status = 2` (Paid) and `pickup_status = 0` (Not Picked Up) scoped to the branch.
* **Pickup Action:** Create a `markAsPickedUp($invoiceId)` method. Set `pickup_status = 1` and `pickup_date = today` on database commit.
* **Checkout Modal Styling:** Convert the checkout modal card background to adapt dynamically (`bg-white dark:bg-surface-700 border border-gray-200 dark:border-surface-300/40 text-gray-800 dark:text-gray-200`).

---

### C. Dynamic Financial & Leaderboard Statistics

#### [MODIFY] [owner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/owner.blade.php)
* **Real-time Queries:** Implement actual database queries to replace all hardcoded stats (Omset/Revenue, HPP/Costs, Laba/Profit, and Expenses).
* **Period Selection:** Connect the `period` attribute (Daily, Weekly, Monthly) to dynamic database date range filters.
* **Branch Filtering Selector:** Add a branch dropdown filter allowing the owner to select a specific branch or "All Branches" (default). Filter all sales and expense queries dynamically by the selected branch.
* **Dynamic Designer Leaderboard:** Query invoices grouped by `customer_service` (designer name) during the selected period/branch, returning actual order counts and total invoice values.
* **Dynamic Sparkline Charts:** Replace the static SVG path with a dynamically computed sparkline. Divide the selected period into sub-intervals and fetch net profits for each interval to map coordinate values onto the SVG `<path>`. If no sales data exists, render a flat baseline at the bottom of the grid with a placeholder message.

---

### D. Multi-Branch Scoping across Portals

Ensure all dashboards correctly scope queries to the active user's branch (`auth()->user()->branch_id`), falling back to a default branch if no branch is assigned.

#### [MODIFY] [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php)
* Filter material lists, machine options, and recently placed invoices by `auth()->user()->branch_id`.
* Store new invoices with the designer's active `branch_id`.

#### [MODIFY] [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php)
* Filter active billing invoices, cashier metrics, and cashier drawer expense tables by `auth()->user()->branch_id`.
* Store cashier expenses with the cashier's active `branch_id`.

#### [MODIFY] [production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php)
* Filter active print queues by checking if the order's parent invoice belongs to `auth()->user()->branch_id`.
* Scope stats counters and failed print history lists to `auth()->user()->branch_id`.
* Store logged waste with the operator's active `branch_id`.

#### [MODIFY] [manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php)
* Filter material inventory levels, stock ledger history, and expenses by `auth()->user()->branch_id`.
* Restocking actions store logs with the manager's active `branch_id`.

#### [MODIFY] [InventoryService.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Services/InventoryService.php)
* In `logWaste()`, retrieve the target material's `branch_id` instead of hardcoding `'branch_id' => 1`.

#### [MODIFY] [User.php](file:///home/arukast/Projects/localDev/projects/pos_alja/app/Models/User.php)
* Add `username`, `role`, and `branch_id` to the fillable array to ensure safe seeding and user operations.

#### [MODIFY] [web.php](file:///home/arukast/Projects/localDev/projects/pos_alja/routes/web.php)
* Add a branch restriction check on `/orders/{invoice}/ticket` and `/orders/{invoice}/receipt` routes to prevent cross-branch viewing for roles other than `owner`.

---

## 2. Verification Plan

### Automated Verification
* Ensure existing unit and feature tests pass (using mock database configurations if running locally).
* Add a new authorization and scoping test to verify that users are unable to access/query data belonging to other branches.

### Manual Verification
1. Log in as an **Owner** and toggle between daily, weekly, and monthly periods. Verify that metrics and the sparkline chart update dynamically.
2. Select a specific branch from the dropdown and verify stats isolate to that branch.
3. Log in as a **Designer**, select a product category, search for customers, and verify that the dropdown result styling matches the dark theme.
4. Log in as a **Cashier**, select an invoice, click "Register DP" or "Checkout", and verify that the payment modal renders with readable text, input elements, and buttons on the dark background.
5. Verify that created invoices, expenses, and stock ledger entries are stamped with the correct `branch_id`.
6. Toggle the theme button in the header and verify that the interface transitions smoothly between light (light gray/white theme) and dark (dark blue/glassmorphic theme), persisting your settings on reload.
7. Settle an invoice in the Cashier Dashboard. Verify it appears on the new Customer Pickup Queue, click "Mark Picked Up", and confirm the invoice's `pickup_status` updates and it clears from the list.
