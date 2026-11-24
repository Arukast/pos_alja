# Sequence Diagrams (Target)

These are target flows for the API-first React SPA after Sprint Review 1. Every request except login uses Sanctum cookies and CSRF; branch and record checks happen server-side. Implementation status is tracked in `docs/requirements/implementation-gaps.md`, not in this file.

## 1. Authentication And Access

### 1.1 Login And Session Bootstrap

```mermaid
sequenceDiagram
    autonumber
    participant SPA
    participant API
    participant DB

    SPA->>API: GET /sanctum/csrf-cookie
    API-->>SPA: CSRF cookie
    SPA->>API: POST /api/v1/auth/login
    API->>DB: Find user by username
    API->>API: Bcrypt check and rate limit
    alt Invalid credentials
        API-->>SPA: 422 generic failure
    else Valid
        API-->>SPA: 200 user + session cookie
        SPA->>API: GET /api/v1/auth/me
        API-->>SPA: User, role, branch
    end
```

### 1.2 User Administration And Branch-Scoped RBAC

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Manager
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    A->>SPA: Open user management
    SPA->>API: GET /api/v1/users?branch_id
    API->>Policy: Authorize viewAny
    Policy-->>API: Owner all, manager own branch staff
    API->>DB: Query users scoped by role and branch
    API-->>SPA: Paginated users

    A->>SPA: Create, update, deactivate, or restore user
    SPA->>API: POST/PUT/DELETE /api/v1/users
    API->>Policy: Authorize manage target user
    alt Manager targets owner/manager or another branch
        Policy-->>API: Deny
        API-->>SPA: 403
    else Allowed
        API->>DB: BEGIN, write user + audit log, COMMIT
        API-->>SPA: User resource
        API->>R: OrderUpdated on branch/owners
    end
```

## 2. Catalog

### 2.1 Product Search, Filter, And Pagination

```mermaid
sequenceDiagram
    autonumber
    actor U as Designer or Cashier
    participant SPA
    participant API
    participant Policy
    participant DB

    U->>SPA: Search product by name/category
    SPA->>API: GET /api/v1/products?search&category_id&page
    API->>Policy: Authorize viewProducts
    Policy-->>API: Allow owner, manager, cashier, designer
    API->>DB: Query products, 20 rows per page
    API-->>SPA: Product metadata and category
```

### 2.2 Product, Category, Material, And Discount Administration

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    A->>SPA: Create or edit product, category, finishing/display, discount tier, or material
    SPA->>API: POST/PUT/DELETE /api/v1/products, /categories, /attributes, /materials
    API->>Policy: Authorize manage catalog
    alt Not owner
        Policy-->>API: Deny
        API-->>SPA: 403
    else Allowed
        API->>DB: BEGIN, validate unit/discount, write catalog row, audit, COMMIT
        API-->>SPA: Resource
        API->>R: OrderUpdated
    end
```

## 3. Sales And Orders

### 3.1 Create Invoice

```mermaid
sequenceDiagram
    autonumber
    actor D as Designer
    participant SPA
    participant API
    participant PriceCalc
    participant DB
    participant R as Reverb

    D->>SPA: Search customer, then choose product, material, finishing, display, specs
    SPA->>API: GET /api/v1/customers?search
    API-->>SPA: Matching customers
    opt New customer
        SPA->>API: POST /api/v1/customers
        API-->>SPA: Created customer
    end
    SPA->>API: GET /api/v1/quotes/options
    API-->>SPA: Branch catalog, no cost/profit for designer
    SPA->>API: POST /api/v1/quotes
    API->>PriceCalc: Material + finishing/display price, customer type from customer, category discount
    PriceCalc-->>API: Unit price, area, subtotal
    API-->>SPA: Quote
    SPA->>API: POST /api/v1/invoices
    API->>DB: BEGIN, lock branch for numbering, recompute every line
    API->>DB: Create invoice, orders, order_attributes, status logs, audit
    API->>DB: COMMIT
    API-->>SPA: 201 invoice
    API->>R: OrderUpdated on branch/owners
    SPA->>API: GET /api/v1/invoices/{id}/print
    API-->>SPA: Invoice print data with money
```

### 3.2 Update Order Before Production

```mermaid
sequenceDiagram
    autonumber
    actor D as Designer
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    D->>SPA: Open invoice
    SPA->>API: GET /api/v1/invoices/{id}
    API-->>SPA: Invoice, orders, payments
    D->>SPA: Add, remove, or change items
    SPA->>API: PUT /api/v1/invoices/{id}
    API->>Policy: Authorize update (designer, same branch)
    API->>DB: BEGIN, lock invoice and orders
    API->>DB: Require every order status = 0 and invoice status 0 or 1
    alt Any order in production or invoice fully paid
        API-->>SPA: 409 cannot change
    else New total below total paid
        API-->>SPA: 422 would create a negative receivable
    else Editable
        API->>DB: Apply item changes, recalculate discount and receivable
        API->>DB: Insert audit log with before/after
        API->>DB: COMMIT
        API-->>SPA: Updated invoice
        API->>R: OrderUpdated
    end
```

### 3.3 Update Design Status

```mermaid
sequenceDiagram
    autonumber
    actor D as Designer
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    D->>SPA: Set design status
    SPA->>API: PATCH /api/v1/orders/{id}/design-status
    API->>Policy: Authorize designer, or owner/manager override
    API->>DB: Validate transition (pending -> in_design -> approved)
    alt Designer attempts backward transition or invalid status
        API-->>SPA: 422
    else Valid
        API->>DB: BEGIN, update design_status, approver, timestamp
        API->>DB: Insert status and audit logs, COMMIT
        API-->>SPA: Order
        API->>R: OrderUpdated
    end
```

## 4. Payments And Pickup

### 4.1 Down Payment And Multiple DP

```mermaid
sequenceDiagram
    autonumber
    actor C as Cashier
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    loop Until paid
        C->>SPA: Enter DP amount and method
        SPA->>API: POST /api/v1/invoices/{id}/down-payments
        API->>Policy: Authorize cashier, same branch
        API->>DB: BEGIN, lock invoice
        alt Amount <= 0 or above receivable
            API-->>SPA: 422
        else Valid
            API->>DB: Create payment, recalculate receivable and status
            API->>DB: Insert audit log, COMMIT
            API-->>SPA: Payment and invoice
            API->>R: OrderUpdated
        end
    end
```

### 4.2 Settlement And Receipt

```mermaid
sequenceDiagram
    autonumber
    actor C as Cashier
    participant SPA
    participant API
    participant DB
    participant R as Reverb

    C->>SPA: Enter discount, cash received, method
    SPA->>API: POST /api/v1/invoices/{id}/settlements
    API->>DB: BEGIN, lock invoice
    API->>DB: Validate discount <= receivable
    API->>DB: amount = receivable - discount, validate cash received
    alt Invalid discount or cash
        API-->>SPA: 422
    else Valid
        API->>DB: Update discount/totals, create settlement, recalc
        API->>DB: Insert audit log, COMMIT
        API-->>SPA: Payment and invoice
        API->>R: OrderUpdated
        SPA->>API: GET /api/v1/invoices/{id}/receipt
        API-->>SPA: Receipt with DP history and remaining receivable
    end
```

### 4.3 Pickup With Order Propagation

```mermaid
sequenceDiagram
    autonumber
    actor C as Cashier
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    C->>SPA: Mark invoice as picked up
    SPA->>API: POST /api/v1/invoices/{id}/pickup
    API->>Policy: Authorize cashier, same branch
    API->>DB: BEGIN, lock invoice
    API->>DB: Require status = 2 and pickup_status = 0
    alt Not paid or already picked up
        API-->>SPA: 422
    else Valid
        API->>DB: Update invoice pickup status/date
        API->>DB: Update all related orders pickup status/date
        API->>DB: Insert audit log, COMMIT
        API-->>SPA: Invoice
        API->>R: OrderUpdated
    end
```

## 5. Inventory

### 5.1 Record Raw-Material Purchase

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    A->>SPA: Submit purchase header and items
    SPA->>API: POST /api/v1/purchases
    API->>Policy: Authorize owner/manager, branch scope
    API->>DB: BEGIN
    API->>DB: Create purchase and purchase items
    loop Each item
        API->>DB: Lock branch stock, increment current_stock
        API->>DB: Insert stock ledger type purchase
    end
    API->>DB: Insert audit log, COMMIT
    API-->>SPA: Purchase
    API->>R: StockUpdated on branch
```

### 5.2 Stock View And Low-Stock Warning

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner, Manager, or Production
    participant SPA
    participant API
    participant Policy
    participant DB

    A->>SPA: Open stock page
    SPA->>API: GET /api/v1/materials?branch_id&low_stock=1
    API->>Policy: Authorize viewAny
    Policy-->>API: Owner all, manager/production own branch
    API->>DB: Query current_stock and min_stock
    API-->>SPA: Materials, current_stock <= min_stock flagged
```

### 5.3 Stock Adjustment With Reason

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant SPA
    participant API
    participant Policy
    participant DB
    participant R as Reverb

    A->>SPA: Submit new quantity or delta and reason
    SPA->>API: POST /api/v1/materials/{id}/adjustments
    API->>Policy: Authorize owner/manager, branch scope
    API->>DB: Require reason 3-255 characters
    API->>DB: BEGIN, lock stock, validate result >= 0
    alt Negative result or missing reason
        API-->>SPA: 422
    else Valid
        API->>DB: Update stock, insert ledger type adjustment
        API->>DB: Insert audit log, COMMIT
        API-->>SPA: Stock ledger
        API->>R: StockUpdated
    end
```

### 5.4 Waste Logging From The Production Queue

```mermaid
sequenceDiagram
    autonumber
    actor P as Production
    participant SPA
    participant API
    participant DB
    participant R as Reverb

    P->>SPA: Open the production queue and choose a job
    SPA->>API: GET /api/v1/waste/options
    API-->>SPA: Branch materials and designers
    P->>SPA: Submit failed print from the queue row
    SPA->>API: POST /api/v1/waste
    API->>DB: Validate material, designer, and order branch
    API->>DB: BEGIN, lock stock, create waste, ledger type waste, audit, COMMIT
    API-->>SPA: Waste record
    API->>R: OrderUpdated/StockUpdated
```

## 6. Production

### 6.1 Production Queue Read

```mermaid
sequenceDiagram
    autonumber
    actor A as Any Role
    participant SPA
    participant API
    participant Policy
    participant DB

    A->>SPA: Open production queue and choose a status filter
    SPA->>API: GET /api/v1/production/queue?status&branch_id
    API->>Policy: Authorize viewQueue
    Policy-->>API: All roles, owner all branches, others own branch
    API->>DB: Query filtered queue, sort deadline ASC, priority DESC, created_at ASC
    API-->>SPA: Queue resource, no money for production
```

### 6.2 Start, Usage, And Complete Production

```mermaid
sequenceDiagram
    autonumber
    actor P as Production
    participant SPA
    participant API
    participant Policy
    participant Inventory
    participant DB
    participant R as Reverb

    P->>SPA: Start job
    SPA->>API: POST /api/v1/orders/{id}/start
    API->>Policy: Authorize production, same branch
    API->>DB: BEGIN, lock order, require status 0
    API->>Inventory: Record order-linked usage
    Inventory->>DB: Lock stock, decrement, ledger type usage with order reference
    API->>DB: Set status 1, operator, status log, audit, COMMIT
    API-->>SPA: Order
    API->>R: OrderUpdated

    P->>SPA: Complete job
    SPA->>API: POST /api/v1/orders/{id}/complete
    API->>DB: BEGIN, lock order, require status 1
    API->>DB: Set status 2, status log, audit, COMMIT
    API-->>SPA: Order
    API->>R: OrderUpdated
```

### 6.3 Realtime Invalidation

```mermaid
sequenceDiagram
    autonumber
    participant Writer as Any API client
    participant API
    participant DB
    participant R as Reverb
    participant SPA

    Writer->>API: Successful non-GET mutation
    API->>DB: Commit transaction
    API->>R: OrderUpdated or StockUpdated
    R-->>SPA: Private branch/owner channel event
    SPA->>API: Refetch affected queries
```

## 7. Reporting And System

### 7.1 Dashboard And Periodic Reports

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant SPA
    participant API
    participant Policy
    participant DB

    A->>SPA: Open dashboard/report with period and branch
    SPA->>API: GET /api/v1/dashboard or /api/v1/reports/{report}
    API->>Policy: Authorize dashboard/report
    Policy-->>API: Owner all branches, manager own branch
    API->>DB: Aggregate payments, invoice costs, waste, expenses
    API-->>SPA: Role-shaped totals, ledgers, margin
```

### 7.2 Export Report

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant SPA
    participant API
    participant Policy
    participant DB
    participant FS as Storage
    participant R as Reverb

    A->>SPA: Choose report, period, branch, format
    SPA->>API: GET /api/v1/reports/export?format=xlsx|pdf&filters
    API->>Policy: Authorize owner/manager, branch scope
    API->>DB: Query summary and detail rows
    API->>FS: Generate and store XLSX/PDF
    API->>DB: Insert audit log
    API-->>SPA: Download path
    API->>R: OrderUpdated optional
```

### 7.3 Audit Log Write And Read

```mermaid
sequenceDiagram
    autonumber
    participant Writer as Any API action
    participant API
    participant DB
    actor O as Owner
    participant SPA

    Writer->>API: Payment, order update, stock adjustment, or admin write
    API->>DB: BEGIN, business write + activity_log, COMMIT
    O->>SPA: Open audit trail
    SPA->>API: GET /api/v1/audit-logs?search&sort
    API->>DB: Query activity logs
    API-->>SPA: Paginated logs and detail
```

### 7.4 Manual And Scheduled Backup

```mermaid
sequenceDiagram
    autonumber
    actor O as Owner
    participant SPA
    participant API
    participant Policy
    participant DB
    participant FS as External Storage
    participant SCH as Scheduler

    alt Manual
        O->>SPA: Run backup
        SPA->>API: POST /api/v1/backups
        API->>Policy: Authorize owner
    else Scheduled
        SCH->>API: Trigger scheduled backup
    end
    API->>DB: Dump schema and data
    API->>FS: Store encrypted backup
    API->>DB: Insert backup audit log
    API-->>SPA: Success or failure
```

## 8. Authorization Rules

Every request shown above:

1. Requires an authenticated session except login.
2. Is scoped to the actor's branch unless the actor is the owner.
3. Returns 403 for another branch and 422 for invalid business input.
4. Omits price, cost, profit, discount, receivable, and payment fields from production responses.
5. Denies invoice and receipt print to production; production uses the queue only.
6. Uses private Reverb channels for branch events; the owner channel is separate.
7. Runs multi-table mutations inside one database transaction with `lockForUpdate()` on invoices, orders, and stock rows.
