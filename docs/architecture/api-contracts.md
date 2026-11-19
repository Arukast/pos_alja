# API Contracts

- **Status:** Internal contracts. The application has no public JSON API, no API authentication, and no versioned external surface.
- **What this document covers:**
  1. PHP service interfaces that contain the business logic.
  2. The small set of HTTP routes that return HTML.
  3. The WebSocket broadcast contract.
  4. The Livewire action surface that the browser calls.
- **What it does not cover:** Proposed or future REST endpoints. None exist in the repository.

The service layer is described as "API-ready" in `AGENTS.md` §2.7 because business logic is isolated there. That is an architectural property, not an exposed API.

---

## 1. Common Conventions

| Topic | Convention |
| :--- | :--- |
| Actor | Service methods receive a `User` model or read `auth()` implicitly. Check the individual contract. |
| Identifiers | UUID strings. Never integers. |
| Money | Stored as `DECIMAL(15,2)`. Treat Eloquent decimal attributes as numeric strings unless explicitly cast; services cast to `float` before arithmetic. |
| Dates | `Y-m-d` strings for filters and date columns; ISO-8601 strings for `logged_at`; `timestampTz` columns for transactional times. |
| Branch scope | `branch_id` may be `'all'` only in reporting filters. A null branch on a data row means global or unassigned. |
| Transactions | Multi-table mutations are wrapped in `DB::transaction()`. Nested service calls join the outer transaction. |
| Errors | Services throw standard exceptions; callers translate them into Livewire validation errors, HTTP 403, or HTTP 500. |
| Side effects | Services may write `activity_logs`, `order_status_logs`, or `stock_ledgers`. They do not dispatch `OrderUpdated`; the calling Livewire component does. |

---

## 2. Service Catalog

| Service | Responsibility | Authentication context |
| :--- | :--- | :--- |
| `OrderService` | Invoice creation, production start, production completion | Explicit `User` or `user_id` |
| `PaymentService` | Down payment and settlement | Explicit `User $cashier` |
| `PricingService` | Quote calculation from the pricing matrix | Optional `auth()` for reseller branch fallback |
| `InventoryService` | Material catalog, branch stock, stock ledger, waste | Implicit `auth()` for actor and branch defaults |
| `FinanceService` | Read-only financial aggregations | None; caller passes `branchId` |
| `BranchService` | Branch CRUD | Explicit `User $actor` |
| `UserService` | User CRUD and restore | Explicit `User $actor` |
| `MachineService` | Machine CRUD and restore | Explicit `User $actor` |
| `CustomerService` | Customer creation | Explicit `User $creator` |
| `ProductCategoryService` | Category CRUD | Implicit `auth()` for logging context |

---

## 3. OrderService

Namespace: `App\Services\OrderService`

### 3.1 `createInvoice(array $data): Invoice`

Creates an invoice, its order lines, initial status logs, and an activity log in one transaction.

Input:

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `customer_id` | UUID string | Yes | Must exist |
| `items` | array of item maps | Yes | At least one item is enforced by the caller, not the service |
| `discount` | integer | No | Defaults to 0; subtracted from the subtotal sum |
| `deadline` | date-time string | No | Stored as `invoices.deadline` |
| `source` | string | No | Defaults to `Offline`; any other value is stored as `1` |
| `user_id` | UUID string | Yes | Designer; must exist |
| `branch_id` | UUID string | Yes | Used for the invoice and the invoice number prefix |

Item map:

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `pricing_id` | UUID string | Yes | Snapshot of the pricing row |
| `file_name` | string | Yes | Display name only |
| `notes` | string | Yes | May be empty; the key must exist. Falls back to `details_string` when empty |
| `details_string` | string | Yes | May be empty; the key must exist |
| `width` | number | Yes | Multiplied by `length` to store `orders.area` |
| `length` | number | Yes | |
| `quantity` | number | Yes | |
| `unit_price` | number | Yes | |
| `subtotal` | number | Yes | Summed into `grand_total` |
| `cost` | number | Yes | Summed into `total_cost` |
| `profit` | number | Yes | Replaced by `grand_total - total_cost` at invoice level |
| `is_manual_price` | boolean | Yes | |

Output: `Invoice` with `status = 0`, `accounts_receivable = grand_total`, `pickup_status = 0`, `source` normalized to `0` or `1`.

Throws: `ModelNotFoundException` when the user is missing. The customer is not loaded; a missing customer surfaces as a database integrity error (`QueryException`) and rolls the transaction back.

Side effects: inserts `invoices`, `orders`, `order_status_logs`, `activity_logs`.

### 3.2 `startProduction(Order $order, User $operator): void`

Sets `orders.status = 1`, assigns `operator_id`, and appends a status log. If `pricing_id` is present, deducts stock:

- `area` product: `order.area * order.quantity`
- otherwise: `order.quantity`

The branch used for the deduction is `operator.branch_id`, falling back to the invoice branch.

Throws: database exceptions; no domain exception for insufficient stock.

### 3.3 `completeProduction(Order $order, User $operator): void`

Sets `orders.status = 2`, assigns `operator_id`, and appends a status log. Does not touch stock.

---

## 4. PaymentService

Namespace: `App\Services\PaymentService`

### 4.1 `registerDownPayment(Invoice $invoice, array $data, User $cashier): Payment`

Input:

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `amount` | number | Yes | Caller validates 1,000 to `grand_total` |
| `payment_method` | string | Yes | Caller validates `Cash`, `Transfer`, or `QRIS` |

Output: `Payment` with `type = down_payment`, `cashier_name` cached from the user.

A down payment equal to the full `grand_total` drives the invoice to status `2`, not `1`.

Side effects: creates the payment and calls `$invoice->recalculatePayments()` inside a transaction.

### 4.2 `settleInvoice(Invoice $invoice, array $data, User $cashier): Payment`

Input:

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `discount` | number | No | Defaults to 0; the Cashier validates it against the receivable, but the service does not enforce the bound |
| `amount_to_pay` | number | Yes | Caller computes `accounts_receivable - discount` |
| `payment_method` | string | Yes | `Cash`, `Transfer`, or `QRIS` |

Output: `Payment` with `type = settlement`.

Side effects: increments `invoices.discount`, reduces `invoices.grand_total` and `invoices.total_profit` by the discount with both clamped at zero, inserts the payment, and recalculates the invoice, all in one transaction.

### 4.3 Invoice recalculation (`Invoice::recalculatePayments(): void`)

- `dp_amount = sum(payments where type = down_payment)`
- `accounts_receivable = max(0, grand_total - sum(all payments))`
- `status = 2` when receivable is 0, `1` when any payment exists, `0` otherwise
- `dp_date` and `payment_date` are set on first entry into the corresponding state

---

## 5. PricingService

Namespace: `App\Services\PricingService`

### 5.1 `calculateQuote(array $params): array`

Input:

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `product_id` | UUID string | Yes | Loaded with `findOrFail` |
| `material_id` | UUID string | Yes | Pricing match key |
| `machine_id` | UUID string | No | Adds the machine's `cost`; adds `machine_pricing.price` only when a matching modifier exists |
| `finishing_id` | UUID string | No | Legacy single-attribute input |
| `cut_id` | UUID string | No | Legacy single-attribute input |
| `display_id` | UUID string | No | Legacy single-attribute input |
| `selected_attributes` | UUID array | No | Preferred over the three legacy fields |
| `sides` | integer | No | Accepted but not used in the calculation |
| `finishing_sides` | integer | No | Accepted but not used in the calculation |
| `quantity` | number | No | Defaults to 1 |
| `width` | number | Conditional | Required for `surface_area` |
| `length` | number | Conditional | Required for `surface_area` and `linear_dimension` |
| `customer_type` | string | No | Defaults to `Reguler` |
| `branch_id` | UUID string | No | Restricts the pricing lookup to a branch |

Lookup order:

1. Exact `(product_id, material_id, customer_type)` with `min_qty <= quantity`, highest `min_qty` wins.
2. If no match and the type is not `Reguler`, retry with `Reguler`.
3. If the fallback is used and the type is `Reseller`, apply `branches.settings.reseller_discount_percent` to the base price.
4. Add matching `machine_pricing` and `attribute_pricing` modifiers.

Output:

| Key | Type | Notes |
| :--- | :--- | :--- |
| `pricing_id` | UUID string | Matched matrix row |
| `unit_price` | float | Base price plus modifiers, rounded to 2 decimals |
| `unit_cost` | float | Base cost plus modifiers, rounded to 2 decimals |
| `area` | float | `width * length` for surface area, otherwise 0 |
| `quantity` | float | Echoes the requested quantity |
| `subtotal` | float | Archetype-dependent, rounded to 2 decimals |
| `cost` | float | Archetype-dependent, rounded to 2 decimals |
| `profit` | float | `subtotal - cost`, rounded to 2 decimals |

Base cost rule: use `pricing.cost` when greater than zero; otherwise use `materials.cost`.

Throws:

- `App\Exceptions\PricingNotFoundException` with `getParams()` returning the input map.
- `InvalidArgumentException` when geometry is missing for `surface_area` or `linear_dimension`.

Archetype formulas:

| Archetype | Subtotal | Cost |
| :--- | :--- | :--- |
| `surface_area` | `unit_price * quantity * width * length` | `unit_cost * quantity * width * length` |
| `linear_dimension` | `unit_price * quantity * length` | `unit_cost * quantity * length` |
| `tiered_volume` | `unit_price * quantity` | `unit_cost * quantity` |
| `per_piece` (default) | `unit_price * quantity` | `unit_cost * quantity` |

---

## 6. InventoryService

Namespace: `App\Services\InventoryService`

All methods require an authenticated user. Each reads `auth()->id()`. Branch defaults vary: `createGlobalMaterial()` and `deleteGlobalMaterial()` use the actor branch then the first branch; `initializeBranchStock()` takes an explicit branch; `addStock()`, `logWaste()`, and `deductStockOnPrint()` prefer the argument, then the actor branch, then the first branch.

### 6.1 `createGlobalMaterial(array $data): Material`

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `name` | string | Yes | |
| `product_category_id` | UUID string | Yes | |
| `type` | string | No | |
| `dimensions` | string | No | |
| `cost` | number | No | Defaults to 0 |
| `price` | number | No | Defaults to 0 |

Inserts one material with an explicit UUID. Does not create branch stock.

### 6.2 `initializeBranchStock(string $materialId, string $branchId, float $initialStock, float $minStock): void`

Creates the `material_branch_stock` row. When `initialStock > 0`, also writes an initial `stock_ledgers` row with `added_stock = initialStock` and `remaining_stock = initialStock`.

Throws if the junction already exists (unique constraint).

### 6.3 `deleteGlobalMaterial(string $materialId): void`

Soft deletes the material and hard deletes its `material_branch_stock` rows in one transaction.

### 6.4 `addStock(Material $material, float $quantity, ?string $notes = null, ?string $branchId = null): Material`

- Branch defaults to `auth()->user()->branch_id`, then the first branch.
- Locks the stock row with `lockForUpdate()`.
- Increments `current_stock`.
- Writes a `stock_ledgers` row with `added_stock` and the new `remaining_stock`.
- Returns the material, not the stock row.

### 6.5 `logWaste(array $params): WastedMaterial`

| Key | Type | Required | Notes |
| :--- | :--- | :--- | :--- |
| `material_id` | UUID string | Yes | |
| `quantity` | number | Yes | Subtracted from stock |
| `machine_id` | UUID string or null | No | |
| `dimensions` | string | No | |
| `designer_id` | UUID string or null | No | |
| `operator_id` | UUID string or null | No | |
| `order_id` | UUID string or null | No | |
| `notes` | string | No | Defaults to `Print failure` |
| `branch_id` | UUID string | No | Defaults to the actor branch |

Writes `wasted_materials`, decrements stock, and writes a ledger row with `wasted_stock`, all in one transaction.

### 6.6 `deductStockOnPrint(string $materialId, float $quantity, ?string $branchId = null): Material`

Locks the stock row, decrements `current_stock`, and writes a ledger row with `used_stock`. No floor at zero.

---

## 7. FinanceService

Namespace: `App\Services\FinanceService`

All methods are read-only and take `(?string $branchId, string $startDate, string $endDate)`, where `branchId` is `'all'`, null, or a UUID, and dates are `Y-m-d`.

### 7.1 `getGrossRevenueBreakdown(...): array`

| Key | Type |
| :--- | :--- |
| `grand_total` | integer |
| `cash_total` | integer |
| `transfer_total` | integer |
| `qris_total` | integer |
| `payments` | `Collection<Payment>` with `invoice.customer` and `branch` |

Source: `payments.created_at` within the period.

`grand_total` is the sum of the Cash, Transfer, and QRIS buckets only. Payments with another method remain in the `payments` collection but are excluded from `grand_total`. By contrast, `getNetProfitBreakdown()` sums all payment rows.

### 7.2 `getBaseCostBreakdown(...): array`

| Key | Type |
| :--- | :--- |
| `total_hpp` | integer |
| `invoice_cost_total` | integer |
| `wasted_cost_total` | integer |
| `invoices` | `Collection<Invoice>` |
| `wasted_materials` | `Collection<WastedMaterial>` |

Source: `invoices.created_date` for `total_cost`, plus `wasted_materials.logged_at` multiplied by `materials.cost`.

### 7.3 `getNetProfitBreakdown(...): array`

| Key | Type |
| :--- | :--- |
| `net_profit` | integer |
| `gross_revenue` | integer |
| `total_hpp` | integer |
| `expenses_total` | integer |
| `margin_rate` | float |
| `invoices` | `Collection<Invoice>` |
| `wasted_materials` | `Collection<WastedMaterial>` |
| `expenses` | `Collection<Expense>` |

Formula: `net_profit = gross_revenue - total_hpp - expenses_total`; `margin_rate = net_profit / gross_revenue * 100`, or 0 when revenue is zero.

### 7.4 `getExpensesBreakdown(...): array`

| Key | Type |
| :--- | :--- |
| `expenses_total` | integer |
| `by_category` | map of category to integer |
| `expenses` | `Collection<Expense>` |

Source: `expenses.incurred_at` within the period.

All aggregates are cast to `int`, so fractional currency is truncated in reports.

---

## 8. Administration Services

### 8.1 BranchService

| Method | Input keys | Output | Side effects |
| :--- | :--- | :--- | :--- |
| `createBranch(array $data, User $actor): Branch` | `name`, `code`, `address`, `phone`, `is_active`, `settings` | `Branch` | `activity_logs` |
| `updateBranch(Branch $branch, array $data, User $actor): Branch` | same as create | `Branch` | `activity_logs` with before/after |
| `toggleBranchStatus(Branch $branch, User $actor): Branch` | none | `Branch` | `activity_logs` |
| `deleteBranch(Branch $branch, User $actor): void` | none | void | `activity_logs`, soft delete |

### 8.2 UserService

| Method | Input keys | Output | Side effects |
| :--- | :--- | :--- | :--- |
| `createUser(array $data, User $actor): User` | `name`, `username`, `email`, `phone`, `address`, `role`, `branch_id`, `password` | `User` | Hash password, `activity_logs` |
| `updateUser(User $user, array $data, User $actor): User` | same; `password` optional | `User` | Hash password when supplied, `activity_logs` with before/after |
| `deleteUser(User $user, User $actor): void` | none | void | Soft delete, `activity_logs` |
| `restoreUser(User $user, User $actor): void` | none | void | Restore, `activity_logs` |

Password is always removed from audit payloads.

### 8.3 MachineService

| Method | Input keys | Output |
| :--- | :--- | :--- |
| `createMachine(array $data, User $actor): Machine` | `branch_id`, `name`, `cost` | `Machine` |
| `updateMachine(Machine $machine, array $data, User $actor): Machine` | `branch_id`, `name`, `cost` | `Machine` |
| `deleteMachine(Machine $machine, User $actor): void` | none | void, soft delete |
| `restoreMachine(Machine $machine, User $actor): void` | none | void |

Each method writes `activity_logs` inside a transaction and logs failures with the actor's `user_id` and `branch_id`.

### 8.4 CustomerService

| Method | Input keys | Output |
| :--- | :--- | :--- |
| `createCustomer(array $data, User $creator): Customer` | `name`, `phone`, `address`, `email`, `type` | `Customer` |

`type` defaults to `Reguler`. Writes `activity_logs`.

### 8.5 ProductCategoryService

| Method | Input keys | Output |
| :--- | :--- | :--- |
| `createCategory(array $data): ProductCategory` | `name`, `calculation_archetype`, `allowed_attribute_types` | `ProductCategory` |
| `updateCategory(string $id, array $data): ProductCategory` | same | `ProductCategory` |
| `deleteCategory(string $id): void` | none | void |

Each method wraps its write in a transaction and logs failures with the actor context. No `activity_logs` row is written by this service.

---

## 9. HTTP Routes

The application exposes HTML routes only. There is no `routes/api.php`.

| Method | Path | Name | Middleware | Response | Errors |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `/` | none | none | 302 redirect to `/dashboard` | none |
| GET | `/dashboard` | `dashboard` | `auth`, `verified` | HTML role dashboard | 302 to login; 302 to verification notice if verification is enabled |
| GET | `/profile` | `profile` | `auth` | HTML profile page | 302 to login |
| GET | `/orders/{invoice}/ticket` | `orders.ticket` | `auth` | HTML work-order sheet | 403 for cross-branch non-owner; 404 for unknown invoice |
| GET | `/orders/{invoice}/receipt` | `orders.receipt` | `auth` | HTML 80 mm receipt | 403 for cross-branch non-owner; 404 for unknown invoice |
| GET | `/login` | `login` | `guest` | HTML login page | 302 if already authenticated |
| GET | `/confirm-password` | `password.confirm` | `auth` | HTML confirmation page | 302 to login |

Branch rule for ticket and receipt:

```
if role !== 'owner'
   and user.branch_id is not null
   and invoice.branch_id !== user.branch_id:
       abort(403)
```

The routes return Blade views; they are not JSON endpoints.

---

## 10. WebSocket Contract

| Item | Value |
| :--- | :--- |
| Driver | Laravel Reverb |
| Event class | `App\Events\OrderUpdated` |
| Interface | `ShouldBroadcastNow` (broadcast immediately, not queued) |
| Channel | `orders` |
| Channel type | Public (`Channel`), no authorization callback |
| Broadcast name | `OrderUpdated` |
| Payload | Empty object |
| Client listener | `echo:orders,OrderUpdated` mapped to `$refresh` on Owner, Manager, Cashier, Production, `MachineManagement`, `MaterialManagement`, `CategoryManagement`, and the four owner detail components; not registered on Designer, `BranchManagement`, or `UserManagement` |

Client subscription shape:

```js
Echo.channel('orders').listen('.OrderUpdated', () => {
    // Livewire refresh is registered by the component listener
});
```

Operational ports:

- Reverb listens on container port `8080`.
- The host maps it to `8084` per `AGENTS.md` §4.6.
- The client must use the host-mapped port when running in Docker.

---

## 11. Livewire Action Surface

These are the browser-facing actions. They are not versioned, are not JSON contracts, and are coupled to the component templates. They are listed so integrators know where the UI writes.

### Designer

`selectCustomer`, `createCustomer`, `calculateQuote`, `addItem`, `removeItem`, `submitOrder`, `updatingInvoiceSearch`, `updatingActiveTab`.

### Cashier

`logExpense`, `selectInvoice`, `closeCheckout`, `processCheckout`, `markAsPickedUp`.

### Production

`startOrderProduction`, `completeOrderProduction`, `logWaste`.

### Manager

`setTab`, `addMaterialStock`, `openMaterialModal`, `closeMaterialModal`, `saveMaterial`, `saveAttribute`, `editAttribute`, `deleteAttribute`, `resetAttributeForm`, `savePricing`, `editPricing`, `deletePricing`, `resetPricingForm`, `clonePricingTiers`.

### Owner

`setTab`, `setPeriod`, `updatedPeriod`, `updatedSelectedBranchId`, `openAuditDetail`, `closeAuditDetail`, `updatingAuditSearch`, `sortByAudit`.

The detail components `GrossRevenueDetail`, `BaseCostDetail`, `NetProfitDetail`, and `ExpensesDetail` each expose `applyCustomRange` for their custom date forms.

### BranchManagement

`openCreateModal`, `openEditModal`, `saveBranch`, `toggleBranchStatus`, `deleteBranch`.

### UserManagement

`openCreateModal`, `openEditModal`, `saveUser`, `deleteUser`, `openDetailModal`, `closeDetailModal`, `restoreUser`.

### MaterialManagement

`handleBranchSelected`, `addMaterialStock`, `openMaterialModal`, `editMaterial`, `deleteMaterial`, `closeMaterialModal`, `activateExistingMaterial`, `acceptSimilarMaterial`, `bypassSimilarityAndSave`, `saveMaterial`, `restoreMaterial`.

### MachineManagement

`handleBranchSelected`, `openCreateModal`, `openEditModal`, `saveMachine`, `deleteMachine`, `restoreMachine`.

### CategoryManagement

`openCategoryModal`, `editCategory`, `saveCategory`, `deleteCategory`, `closeCategoryModal`, `openTypeModal`, `closeTypeModal`, `saveType`, `deleteType`.

---

## 12. Error Model

| Error | Produced by | Caller behavior |
| :--- | :--- | :--- |
| `Illuminate\Validation\ValidationException` | Livewire form validation | 422-style error bag rendered in the component |
| `Symfony\Component\HttpKernel\Exception\HttpException` (403) | `abort(403)` in routes and components | Error page; no JSON envelope |
| `ModelNotFoundException` | `findOrFail` | 404 |
| `PricingNotFoundException` | `PricingService` | Designer catches and displays `calcError` |
| `InvalidArgumentException` | `PricingService` | Designer catches and displays the message |
| `Throwable` from the database | Constraint or connection failure | Transaction rollback; 500; typically reported by Laravel |

Broadcast calls are synchronous. A Reverb outage can surface as an exception from `event(new OrderUpdated)`. Some callers wrap it in `try/catch` (`UserManagement`, `Manager::clonePricingTiers`); others do not.

---

## 13. Compatibility Rules

1. These contracts are internal. Changing a service signature requires updating every caller and its tests in the same change.
2. Do not add a public JSON API by exposing services directly. Add routes, request validation, API authentication, and a versioning strategy first.
3. Any new service method that mutates more than one table must use `DB::transaction()` and must document whether it relies on `auth()` or an explicit actor.
4. Any new broadcast contract must state the channel, event name, payload shape, and whether the channel is public or private.
5. Keep UUIDs and decimal money in any future API; do not switch to integer IDs or floats in transport without an explicit decision.
