# Sequence Diagrams

- **Scope:** The principal end-to-end workflows implemented in the application.
- **Notation:** Mermaid `sequenceDiagram`. Participants are application roles, Livewire components, service classes, the database, and Reverb.
- **Source of truth:** `app/Livewire`, `app/Services`, `app/Models`, `app/Events`, `routes/web.php`.

---

## 1. Designer Order Intake

Covers customer selection or creation, quote calculation, cart assembly, invoice submission, and the broadcast that refreshes the production queue.

```mermaid
sequenceDiagram
    autonumber
    actor D as Designer
    participant UI as Designer Dashboard
    participant CS as CustomerService
    participant PS as PricingService
    participant OS as OrderService
    participant DB as Database
    participant R as Reverb
    participant P as Production Dashboard

    D->>UI: Search customer by name or phone
    UI->>DB: SELECT customers LIKE name/phone LIMIT 8
    DB-->>UI: Matching customers

    opt Customer does not exist
        D->>UI: Submit new customer form
        UI->>CS: createCustomer(data, creator)
        CS->>DB: BEGIN
        CS->>DB: INSERT customers
        CS->>DB: INSERT activity_logs
        CS->>DB: COMMIT
        CS-->>UI: Customer
    end

    D->>UI: Select category, product, material, machine, attributes, quantity, dimensions
    UI->>PS: calculateQuote(params)
    PS->>DB: Load product and category
    PS->>DB: Find pricing row (branch, product, material, customer type, min_qty <= quantity)
    PS->>DB: Load machine_pricing and attribute_pricing modifiers
    alt No pricing row
        PS-->>UI: throw PricingNotFoundException
        UI-->>D: Show calculator error
    else Missing geometry for archetype
        PS-->>UI: throw InvalidArgumentException
        UI-->>D: Show calculator error
    else Quote found
        PS-->>UI: pricing_id, unit_price, unit_cost, area, subtotal, cost, profit
        UI-->>D: Render price preview
    end

    D->>UI: Add item to cart (repeatable)
    UI->>UI: Store item snapshot in component state

    D->>UI: Submit order
    UI->>UI: Require customer and at least one item
    UI->>OS: createInvoice(data)
    OS->>DB: BEGIN
    OS->>DB: Compute grand_total, total_cost, total_profit
    OS->>DB: Generate invoice number INV-{prefix}-{YYYYMMDD}-{NNNN}
    OS->>DB: INSERT invoices
    loop Each cart item
        OS->>DB: INSERT orders (status 0)
        OS->>DB: INSERT order_status_logs (status 0)
    end
    OS->>DB: INSERT activity_logs
    OS->>DB: COMMIT
    OS-->>UI: Invoice
    UI->>R: event(OrderUpdated)
    R-->>P: orders / OrderUpdated
    P->>P: $refresh
    UI-->>D: Success alert, reset cart
```

Notes:

- The invoice number sequence is `count(invoices for branch and date) + 1`; it is not protected by a lock.
- `file_name` is stored; no binary file is uploaded by this flow.
- Manual price override replaces the calculated unit price and cost for a cart line before submission.

---

## 2. Production Start and Stock Deduction

```mermaid
sequenceDiagram
    autonumber
    actor P as Production Operator
    participant UI as Production Dashboard
    participant OS as OrderService
    participant IS as InventoryService
    participant DB as Database
    participant R as Reverb
    participant Peers as Other Dashboards

    P->>UI: Start printing (orderId)
    UI->>DB: Load order and invoice
    alt Invoice belongs to another branch
        UI-->>P: HTTP 403 Unauthorized branch access
    else Same branch or unbound user
        UI->>OS: startProduction(order, operator)
        OS->>DB: BEGIN
        OS->>DB: UPDATE orders SET status = 1, operator_id = operator
        OS->>DB: Load pricing and product
        opt Order has pricing and material
            OS->>IS: deductStockOnPrint(material_id, quantity, branch_id)
            IS->>DB: BEGIN
            IS->>DB: SELECT material_branch_stock FOR UPDATE
            IS->>DB: UPDATE current_stock = current_stock - quantity
            IS->>DB: INSERT stock_ledgers (used_stock)
            IS->>DB: COMMIT
        end
        OS->>DB: INSERT order_status_logs (status 1)
        OS->>DB: COMMIT
        UI->>R: event(OrderUpdated)
        R-->>Peers: orders / OrderUpdated
        Peers->>Peers: $refresh
        UI-->>P: Success alert
    end
```

Deduction quantity:

- Product `pricing_type = area`: `order.area * order.quantity`
- Otherwise: `order.quantity`

Implementation note: the deduction happens at production start, not at completion.

---

## 3. Waste Logging

```mermaid
sequenceDiagram
    autonumber
    actor P as Production Operator
    participant UI as Production Dashboard
    participant F as WasteLogForm
    participant IS as InventoryService
    participant DB as Database

    P->>UI: Submit failed print form
    UI->>F: validate()
    alt Invalid input
        F-->>UI: ValidationException
        UI-->>P: Field errors
    else Valid input
        UI->>IS: logWaste(params)
        IS->>DB: BEGIN
        IS->>DB: SELECT material_branch_stock FOR UPDATE
        IS->>DB: INSERT wasted_materials
        IS->>DB: UPDATE current_stock = current_stock - quantity
        IS->>DB: INSERT stock_ledgers (wasted_stock)
        IS->>DB: COMMIT
        IS-->>UI: WastedMaterial
        UI-->>P: Success alert, reset form
    end
```

Implementation note: this flow does not dispatch `OrderUpdated`. Other dashboards do not refresh until their next request.

---

## 4. Down Payment and Settlement

```mermaid
sequenceDiagram
    autonumber
    actor C as Cashier
    participant UI as Cashier Dashboard
    participant PS as PaymentService
    participant INV as Invoice Model
    participant DB as Database
    participant R as Reverb
    participant Peers as Other Dashboards

    C->>UI: Select invoice from payment queue
    UI->>DB: Load invoice by ID
    alt Down payment
        UI->>UI: Suggest 50 percent of grand_total
        C->>UI: Enter amount and method
        UI->>UI: Validate amount between 1000 and grand_total, method in Cash/Transfer/QRIS
        UI->>PS: registerDownPayment(invoice, data, cashier)
        PS->>DB: BEGIN
        PS->>DB: INSERT payments (type down_payment)
        PS->>INV: recalculatePayments()
        INV->>DB: SUM payments, derive dp_amount and accounts_receivable
        alt Receivable remains
            INV->>DB: UPDATE invoices SET status = 1, dp_date = today
        else Down payment covers grand_total
            INV->>DB: UPDATE invoices SET status = 2, payment_date = today
        end
        PS->>DB: COMMIT
    else Settlement
        UI->>UI: Suggest accounts_receivable
        C->>UI: Enter discount, amount received, method
        UI->>UI: Validate discount <= receivable and amount >= receivable - discount
        UI->>PS: settleInvoice(invoice, data, cashier)
        PS->>DB: BEGIN
        PS->>DB: Reduce invoice grand_total and total_profit by discount
        PS->>DB: INSERT payments (type settlement)
        PS->>INV: recalculatePayments()
        INV->>DB: SUM payments, derive accounts_receivable
        INV->>DB: UPDATE invoices SET status = 2, payment_date = today
        PS->>DB: COMMIT
    end
    UI->>R: event(OrderUpdated)
    R-->>Peers: orders / OrderUpdated
    Peers->>Peers: $refresh
    UI-->>C: Success alert, close checkout panel
```

Status transitions:

| Before | Event | After |
| :--- | :--- | :--- |
| 0 Unpaid | Down payment smaller than the receivable | 1 Down payment |
| 0 Unpaid | Down payment equal to `grand_total` | 2 Paid |
| 1 Down payment | Remaining balance settled | 2 Paid |
| 0 Unpaid | Full settlement in one payment | 2 Paid |

Implementation note: `selectInvoice()` and `processCheckout()` load the invoice by primary key without an explicit branch assertion. The queue is branch-scoped.

---

## 5. Customer Pickup

```mermaid
sequenceDiagram
    autonumber
    actor C as Cashier
    participant UI as Cashier Dashboard
    participant DB as Database
    participant R as Reverb
    participant Peers as Other Dashboards

    C->>UI: Open pickup queue
    UI->>DB: SELECT invoices WHERE status = 2 AND pickup_status = 0 AND branch_id = cashier branch
    DB-->>UI: Paid, not picked up invoices

    C->>UI: Mark as picked up (invoiceId)
    UI->>DB: Load invoice
    alt Invoice belongs to another branch
        UI-->>C: HTTP 403 Unauthorized branch access
    else Same branch
        UI->>DB: UPDATE invoices SET pickup_status = 1, pickup_date = today
        UI->>R: event(OrderUpdated)
        R-->>Peers: orders / OrderUpdated
        Peers->>Peers: $refresh
        UI->>UI: dispatch($refresh) as local fallback
        UI-->>C: Success alert
    end
```

---

## 6. Owner Financial Analytics

```mermaid
sequenceDiagram
    autonumber
    actor O as Owner
    participant UI as Owner Dashboard
    participant D1 as Revenue Detail
    participant D2 as Cost Detail
    participant D3 as Profit Detail
    participant D4 as Expenses Detail
    participant FS as FinanceService
    participant DB as Database

    O->>UI: Select period (Daily/Weekly/Monthly) and branch (all or one)
    UI->>D1: branchSelected / periodSelected
    UI->>D2: branchSelected / periodSelected
    UI->>D3: branchSelected / periodSelected
    UI->>D4: branchSelected / periodSelected

    alt Gross revenue tab
        D1->>FS: getGrossRevenueBreakdown(branchId, startDate, endDate)
        FS->>DB: SUM payments by method, list payments
        FS-->>D1: grand_total, cash, transfer, qris, payments
    else Base cost tab
        D2->>FS: getBaseCostBreakdown(branchId, startDate, endDate)
        FS->>DB: SUM invoices.total_cost, SUM wasted quantity * material cost
        FS-->>D2: total_hpp, invoice total, waste total, rows
    else Net profit tab
        D3->>FS: getNetProfitBreakdown(branchId, startDate, endDate)
        FS->>DB: Payments, invoice cost, waste cost, expenses
        FS-->>D3: net_profit, gross_revenue, total_hpp, expenses_total, margin_rate
    else Expenses tab
        D4->>FS: getExpensesBreakdown(branchId, startDate, endDate)
        FS->>DB: SUM expenses, group by category, list expenses
        FS-->>D4: expenses_total, by_category, expenses
    end

    D1-->>O: Tables and totals
    D2-->>O: Tables and totals
    D3-->>O: Tables and totals
    D4-->>O: Tables and totals
    UI->>DB: Compute sparkline buckets (payments minus invoice cost)
    UI-->>O: Metric cards and sparkline
```

Implementation note: the owner headline cards compute `payments - invoice cost`. They do not subtract waste cost or expenses. The Net Profit Detail tab uses the full `FinanceService` calculation.

---

## 7. Real-Time Refresh

```mermaid
sequenceDiagram
    autonumber
    participant A as Acting Dashboard
    participant LW as Livewire Action
    participant EV as OrderUpdated Event
    participant R as Reverb
    participant L as Echo Listener
    participant T as Target Dashboard

    A->>LW: Commit a mutation that dispatches the event
    LW->>EV: event(new OrderUpdated)
    EV->>R: Broadcast "OrderUpdated" on public channel "orders"
    R-->>L: orders / OrderUpdated
    L->>T: $refresh
    T->>T: Re-query scoped data and re-render
```

Contract:

- Channel: `orders` (public, no authorization callback)
- Event name: `OrderUpdated`
- Payload: empty object
- Broadcast driver: Laravel Reverb (`ShouldBroadcastNow`)
- Client listener: `protected $listeners = ['echo:orders,OrderUpdated' => '$refresh'];` on `Owner`, `Manager`, `Cashier`, and `Production`, plus `MachineManagement`, `MaterialManagement`, `CategoryManagement`, and the four owner detail components
- `Designer`, `BranchManagement`, and `UserManagement` do not register the listener.

Not every mutation dispatches the event. The following do not: waste logging, cashier expense logging, designer customer creation, manager stock restocking, attribute edits, and pricing create/update/delete via `savePricing`/`deletePricing`. `clonePricingTiers` does dispatch.

---

## 8. Authentication and Role Dispatch

```mermaid
sequenceDiagram
    autonumber
    actor U as User
    participant UI as Login Page
    participant LF as LoginForm
    participant DB as Database
    participant R as Router
    participant V as dashboard.blade.php
    participant C as Role Dashboard

    U->>UI: Submit username and password
    UI->>LF: authenticate()
    LF->>LF: Check rate limit (5 attempts per username and IP)
    LF->>DB: Auth::attempt(username, password)
    alt Invalid credentials
        LF-->>UI: Generic validation error
    else Valid credentials
        LF->>DB: Create session
        UI->>R: Redirect to /dashboard
        R->>V: auth and verified middleware pass
        V->>V: Match auth()->user()->role
        V->>C: Render owner, manager, cashier, designer, or production component
        C-->>U: Role dashboard
    end
```

---

## Diagram Maintenance

Add a sequence diagram when a new cross-component workflow is introduced or when a service contract changes enough that the existing diagram would mislead. Keep each diagram at the level of service calls and database effects, not private helper methods.
