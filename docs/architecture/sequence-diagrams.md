# Sequence Diagrams

- **Scope:** The principal end-to-end workflows in the current application (§1–17) and the workflows required by the target specification (§18).
- **Notation:** Mermaid `sequenceDiagram`. Participants are application roles, Livewire components, service classes, the database, and Reverb.
- **Source of truth:** Current flows come from `app/Livewire`, `app/Services`, `app/Models`, `app/Events`, and `routes/web.php`. Target flows come from `docs/requirements/fr.md` and `docs/architecture/api-contracts.md` §13.
- **Status:** Sections §1–17 describe shipped behavior, including known gaps. Section §18 describes required target behavior, not shipped code.

## Requirement Coverage

Every FR in `docs/requirements/fr.md` maps to at least one diagram.

| FR | Diagram(s) | Notes |
| :--- | :--- | :--- |
| FR1 | 8 | Login and role dispatch |
| FR2 | 12 | User creation and branch/role restrictions |
| FR3 | 13 | Profile update, password change, and logout |
| FR4 | 12 | Update, deactivate, and restore |
| FR5 | 8, 18.11 | Target access contract; branch checks also appear in 2, 5, 9, 10, 12, and 14 |
| FR6 | 17, 18.9 | Pricing is current; product and category CRUD are target |
| FR7 | 18.10 | Target paginated search |
| FR8 | 1, 10 | Intake and work-order ticket |
| FR9 | 18.1 | Target |
| FR10 | 18.2 | Target |
| FR11 | 4 | Down payment and settlement |
| FR12 | 4 | Repeated down payments use the same path |
| FR13 | 10 | 80 mm receipt |
| FR14 | 11 | Branch expense logging |
| FR15 | 18.3 | Target |
| FR16 | 2, 3, 18.5 | Start deduction, waste logging, and target usage ledger |
| FR17 | 14 | Current stock view; the separate low-stock alert is target |
| FR18 | 18.4 | Target |
| FR19 | 18.8 | Target queue contract |
| FR20 | 2, 9 | Start and completion |
| FR21 | 5, 10 | Pickup and receipt |
| FR22 | 6, 15 | Owner and manager summaries |
| FR23 | 6 | Periodic finance breakdowns |
| FR24 | 18.6 | Target |
| FR25 | 16, 18.1–18.4, 18.9 | View is current; target writes appear in those flows. Payment, usage, and waste audit writes remain gaps. |
| FR26 | 18.7 | Target |

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

Implementation note: `selectInvoice()` and `processCheckout()` load the invoice by primary key without an explicit branch assertion. The queue is branch-scoped. Down-payment validation caps the amount at `grand_total`, not at the remaining receivable; FR11 and FR12 require the receivable cap.

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

Notes:

- The current flow updates the invoice only. FR21 also requires every related order to store `pickup_status` and `pickup_date`.

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

## 9. Production Completion

```mermaid
sequenceDiagram
    autonumber
    actor P as Production Operator
    participant UI as Production Dashboard
    participant OS as OrderService
    participant DB as Database
    participant R as Reverb
    participant Peers as Other Dashboards

    P->>UI: Mark order as completed (orderId)
    UI->>DB: Load order with invoice
    alt Invoice belongs to another branch
        UI-->>P: HTTP 403 Unauthorized branch access
    else Same branch or unbound user
        UI->>OS: completeProduction(order, operator)
        OS->>DB: BEGIN
        OS->>DB: UPDATE orders SET status = 2, operator_id = operator
        OS->>DB: INSERT order_status_logs (status 2)
        OS->>DB: COMMIT
        UI->>R: event(OrderUpdated)
        R-->>Peers: orders / OrderUpdated
        Peers->>Peers: $refresh
        UI-->>P: Success alert
    end
```

Notes:

- Completion does not change stock. The deduction already happened in §2 when production started.
- Completion does not change the invoice payment or pickup status.

---

## 10. Receipt and Work-Order Ticket

```mermaid
sequenceDiagram
    autonumber
    actor A as Authorized User
    participant B as Browser
    participant RT as Route
    participant DB as Database
    participant IV as Invoice Model
    participant V as Blade View
    participant PR as Print Dialog

    A->>B: Open /orders/{invoice}/receipt or /ticket
    B->>RT: GET request
    RT->>RT: Apply auth middleware
    RT->>DB: Resolve invoice by UUID
    alt Unknown invoice
        DB-->>RT: No row
        RT-->>B: HTTP 404
    else Invoice found
        alt Cross-branch non-owner
            RT-->>B: HTTP 403 Unauthorized branch access
        else Owner, same branch, or unbound user
            RT->>IV: Load customer and orders
            IV->>DB: Lazy-load branch, designer, and payments while rendering
            RT->>V: Render 80 mm receipt or work-order ticket
            V-->>B: HTML with print styles
            B->>PR: window.print()
        end
    end
```

Notes:

- Receipt and ticket routes are read-only: no audit row and no `OrderUpdated`.
- The 80 mm receipt includes DP history, settlement, total paid, and the remaining receivable. The ticket is the printable work order for production.
- Branch blocking is skipped for owners and for users with no branch assignment.

---

## 11. Cashier Expense Logging

```mermaid
sequenceDiagram
    autonumber
    actor C as Cashier
    participant UI as Cashier Dashboard
    participant F as ExpenseForm
    participant DB as Database

    C->>UI: Submit operational expense
    UI->>F: validate()
    alt Invalid input
        F-->>UI: ValidationException
        UI-->>C: Field errors
    else Valid input
        UI->>DB: INSERT expenses with actor branch and cached cashier name
        DB-->>UI: Expense
        UI->>F: reset() and restore default type
        UI-->>C: Success alert
    end
```

Notes:

- `ExpenseForm` validates an integer amount of at least 100, type `modal` or `laba`, an incurred date, and a description of at most 500 characters.
- The branch falls back to the first branch when the cashier has no branch assignment.
- This flow does not call a service, write `activity_logs`, or dispatch `OrderUpdated`. The expense is visible to `FinanceService::getExpensesBreakdown()` on the next report request.

---

## 12. User Account Management

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant UI as UserManagement
    participant US as UserService
    participant DB as Database
    participant R as Reverb

    A->>UI: Open create or edit modal
    UI->>UI: Apply mode from the component property
    alt Create
        A->>UI: Submit name, username, email, phone, address, role, branch, password
        UI->>UI: Validate unique username and email, role, branch, and password
        alt Role or branch outside the actor scope
            UI-->>A: HTTP 403 or validation error
        else Allowed
            UI->>US: createUser(data, actor)
            US->>DB: BEGIN
            US->>DB: INSERT users with hashed password
            US->>DB: INSERT activity_logs with password removed
            US->>DB: COMMIT
            US-->>UI: User
        end
    else Update
        A->>UI: Submit changed user data
        UI->>UI: Validate fields, password is optional
        alt Target user is outside the actor scope
            UI-->>A: HTTP 403 or validation error
        else Allowed
            UI->>US: updateUser(user, data, actor)
            US->>DB: BEGIN
            US->>DB: UPDATE users and hash password when supplied
            US->>DB: INSERT activity_logs with before and after
            US->>DB: COMMIT
            US-->>UI: User
        end
    else Deactivate or restore
        A->>UI: Delete or restore a user
        UI->>UI: Apply the same role and branch guards
        UI->>US: deleteUser or restoreUser
        US->>DB: BEGIN
        US->>DB: Soft delete or restore users
        US->>DB: INSERT activity_logs
        US->>DB: COMMIT
    end
    UI->>R: event(OrderUpdated) in try/catch
    UI-->>A: Success alert
```

Notes:

- `mode` is a public Livewire property set by the owner or manager view. The target requires the role and branch to be derived server-side, so this property must not be client-writable.
- A secondary owner cannot edit or create owner accounts unless `isPrimaryOwner()` recognizes the actor.
- Managers only see their own branch and cannot manage owners or other managers.
- Password is removed from every audit payload.

---

## 13. Own Profile, Password, and Logout

```mermaid
sequenceDiagram
    autonumber
    actor U as Authenticated User
    participant PP as Profile Page
    participant PF as UpdateProfileInformationForm
    participant PW as UpdatePasswordForm
    participant NAV as Navigation
    participant LO as Logout Action
    participant DB as Database
    participant S as Session Store

    U->>PP: Open /profile
    alt Update name and email
        U->>PF: Submit profile form
        alt Role is not owner or manager
            PF-->>U: Validation error
        else Owner or manager
            PF->>PF: Validate name and unique lowercase email
            PF->>DB: UPDATE users
            opt Email changed
                PF->>DB: Clear email_verified_at
            end
            PF-->>PP: profile-updated event
        end
    else Change password
        U->>PW: Submit current password and confirmed new password
        PW->>PW: Validate current_password and Password::defaults()
        alt Invalid
            PW-->>U: Validation error and reset fields
        else Valid
            PW->>DB: UPDATE users with hashed password
            PW-->>PP: password-updated event
        end
    else Logout
        U->>NAV: Click logout
        NAV->>LO: __invoke()
        LO->>S: Log out web guard, invalidate session, regenerate CSRF token
        LO-->>U: Redirect to login
    end
```

Notes:

- Cashier, designer, and production can change their own password but cannot update their name or email.
- The profile update is not written to the audit log.
- Account deletion is owner/manager-only and is handled by a separate profile form that logs the user out after deletion.

---

## 14. Branch Stock Visibility and Low-Stock Warnings

```mermaid
sequenceDiagram
    autonumber
    actor M as Branch Manager
    actor O as Owner
    participant MD as Manager Dashboard
    participant MM as MaterialManagement
    participant DB as Database

    alt Manager branch
        M->>MD: Open materials or stock ledger tab
        MD->>MM: Render MaterialManagement for the manager branch
        MM->>DB: SELECT materials JOIN material_branch_stock WHERE branch = manager branch
        DB-->>MM: current_stock, min_stock, and material rows
        MM->>MM: Order by current_stock ascending
        MM->>MM: Flag each row where current_stock is at or below min_stock
        MM-->>M: Stock table with low-stock highlight and ledger history
    else Owner all or selected branch
        O->>MM: Select all branches or one branch
        MM->>DB: SELECT materials JOIN material_branch_stock with optional branch filter
        DB-->>MM: Rows including branch name when all branches are selected
        MM->>MM: Flag each row where current_stock is at or below min_stock
        MM-->>O: Stock table with branch and low-stock highlight
    end
```

Notes:

- The low-stock state is a row highlight in the existing table. There is no separate alert list or dashboard warning yet.
- Production stock read and the FR17 warning surface are target behavior.
- Managers are fixed to their own branch. Owners may switch between `all` and a single branch.

---

## 15. Manager Branch Summary

```mermaid
sequenceDiagram
    autonumber
    actor M as Branch Manager
    participant UI as Manager Dashboard
    participant DB as Database

    M->>UI: Open manager dashboard
    UI->>DB: Count materials, machines, staff, and pricing rules for the branch
    UI->>DB: Load stock ledger, materials, expenses, attributes, and pricing
    UI->>DB: Aggregate designer invoices and waste for the current month
    DB-->>UI: Leaderboard rows and totals
    UI->>UI: Compute top revenue, order champion, top AOV, and accuracy leader
    UI-->>M: Branch cards, leaderboard, stock, and expense panels
```

Notes:

- Every branch-owned query is fixed to the manager's branch. Products and attribute types are global reference data.
- The current summary does not show gross revenue, receivables, order counts, or queued/processing/completed counts. Those are target FR22 additions.
- The owner summary is covered in §6.

---

## 16. Audit Trail View

```mermaid
sequenceDiagram
    autonumber
    actor O as Owner
    participant UI as Owner Dashboard
    participant DB as Database

    O->>UI: Open audit tab
    UI->>DB: SELECT activity_logs with optional branch filter, search, and sort
    DB-->>UI: Paginated rows
    UI-->>O: Audit table with actor, role, action, and timestamp

    O->>UI: Open audit detail (logId)
    UI->>DB: SELECT activity_logs WHERE id = logId
    DB-->>UI: Model type, model id, and payload
    UI-->>O: Detail modal with before/after values when present
```

Notes:

- Search covers username, role, action, and description. Sorting is available on the displayed columns.
- Audit rows are append-only from the application code, but coverage is partial. Invoice creation, customer creation, user, branch, and machine changes write rows. Payments, order status changes, stock movements, pricing, categories, and expenses do not yet write `activity_logs`.
- Owner is the only role with an audit screen.

---

## 17. Pricing Matrix Management

```mermaid
sequenceDiagram
    autonumber
    actor M as Branch Manager
    participant UI as Manager Dashboard
    participant DB as Database
    participant R as Reverb

    M->>UI: Open pricing tab
    UI->>DB: Load pricing rows for the manager branch with product and material
    DB-->>UI: Pricing matrix

    M->>UI: Create or edit a pricing row
    UI->>UI: Validate product, material, customer type, minimum quantity, and price
    UI->>DB: Load material cost
    alt Edit or delete a row from another branch
        UI-->>M: HTTP 403 Unauthorized branch access
    else Allowed
        UI->>DB: INSERT, UPDATE, or DELETE pricing row
        UI->>DB: Derive cost from the material and profit from price minus cost
        UI-->>M: Success alert
    end

    opt Clone pricing tiers
        M->>UI: Choose source material, target material, customer types, and adjustment percent
        UI->>DB: BEGIN
        UI->>DB: SELECT source pricing rows for the manager branch
        loop Each source tier
            UI->>DB: Upsert target pricing row with adjusted price
        end
        UI->>DB: COMMIT
        UI->>R: event(OrderUpdated)
        UI-->>M: Success alert
    end
```

Notes:

- Pricing writes are branch-scoped to `auth()->user()->branch_id`, falling back to the first branch.
- `savePricing()` and `deletePricing()` do not write `activity_logs` and do not dispatch `OrderUpdated`. `clonePricingTiers()` dispatches the event but does not write an audit row.
- The owner has no pricing tab yet; target FR6 requires owner and manager price management.
- Machine and attribute master data use separate branch-scoped CRUD screens and are not part of this diagram.

---

## 18. Additional Target Workflows

These flows define the target contract for FR5–FR7, FR9–FR10, FR15–FR16, FR18–FR19, FR24, and FR26.

### 18.1 Update an Order Before Production (FR9)

```mermaid
sequenceDiagram
    autonumber
    actor D as Designer
    participant UI as Order Screen
    participant OS as OrderService
    participant DB as Database
    participant R as Reverb

    D->>UI: Open invoice
    UI->>DB: Load invoice and orders
    UI->>UI: Check every order status = 0
    alt Any order already in production
        UI-->>D: HTTP 409, order cannot be changed
    else Editable
        D->>UI: Add, remove, or change items, quantities, specs, deadline
        UI->>OS: updateOrder(invoice, data, actor)
        OS->>DB: BEGIN
        OS->>DB: Lock invoice and orders
        OS->>DB: Apply item changes
        OS->>DB: Recalculate grand_total, total_cost, total_profit, accounts_receivable
        OS->>DB: Insert activity_log with before and after
        OS->>DB: COMMIT
        UI->>R: OrderUpdated
        UI-->>D: Success
    end
```

### 18.2 Update Design Status (FR10)

```mermaid
sequenceDiagram
    autonumber
    actor D as Designer
    participant UI as Order Screen
    participant OS as OrderService
    participant DB as Database
    participant R as Reverb

    D->>UI: Set design status
    UI->>OS: updateDesignStatus(order, status, actor)
    OS->>DB: Validate transition (pending -> in_design -> approved)
    alt Invalid transition
        OS-->>UI: Validation error
    else Valid
        OS->>DB: BEGIN
        OS->>DB: Update design_status
        OS->>DB: Set design_approved_at and design_approved_by when approved
        OS->>DB: Insert audit and status log
        OS->>DB: COMMIT
        UI->>R: OrderUpdated
        UI-->>D: Success
    end
```

### 18.3 Record a Raw-Material Purchase (FR15)

```mermaid
sequenceDiagram
    autonumber
    actor M as Branch Manager or Owner
    participant UI as Purchase Screen
    participant PS as PurchaseService
    participant DB as Database
    participant R as Reverb

    M->>UI: Submit purchase header and line items
    UI->>UI: Validate material, quantity, unit cost
    UI->>PS: recordPurchase(data, actor)
    PS->>DB: BEGIN
    PS->>DB: Insert purchases
    PS->>DB: Insert purchase_items
    loop Each line item
        PS->>DB: Lock branch stock row
        PS->>DB: Increment current_stock
        PS->>DB: Insert stock_ledger type purchase
    end
    PS->>DB: Insert activity_log
    PS->>DB: COMMIT
    UI->>R: StockUpdated
    UI-->>M: Success
```

### 18.4 Adjust Stock with a Reason (FR18)

```mermaid
sequenceDiagram
    autonumber
    actor M as Branch Manager
    participant UI as Stock Screen
    participant SS as StockService
    participant DB as Database
    participant R as Reverb

    M->>UI: Submit new quantity or delta and reason
    UI->>UI: Require reason 3-255 characters
    UI->>SS: adjustStock(material, mode, quantity, reason, manager)
    SS->>DB: Validate result is not negative
    alt Negative result
        SS-->>UI: Validation error
    else Valid
        SS->>DB: BEGIN
        SS->>DB: Lock branch stock row
        SS->>DB: Update current_stock
        SS->>DB: Insert stock_ledger type adjustment with reason
        SS->>DB: Insert activity_log
        SS->>DB: COMMIT
        UI->>R: StockUpdated
        UI-->>M: Success
    end
```

### 18.5 Record Production Usage (FR16)

```mermaid
sequenceDiagram
    autonumber
    actor P as Production
    participant UI as Production Screen
    participant OS as OrderService
    participant IS as InventoryService
    participant DB as Database
    participant R as Reverb

    P->>UI: Start production or record usage
    UI->>OS: startProduction(order, operator)
    OS->>DB: BEGIN
    OS->>DB: Set order status = 1 and operator
    OS->>IS: recordUsage(order, material, quantity, operator)
    IS->>DB: Lock branch stock row
    IS->>DB: Decrement current_stock
    IS->>DB: Insert stock_ledger type usage with order reference
    OS->>DB: Insert order_status_log
    OS->>DB: COMMIT
    UI->>R: OrderUpdated
    UI-->>P: Success
```

### 18.6 Export a Report (FR24)

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant UI as Report Screen
    participant ES as ReportExportService
    participant DB as Database
    participant FS as Storage

    A->>UI: Choose report, period, branch, format
    UI->>ES: export(filters, format)
    ES->>DB: Query summary and detail rows
    ES->>ES: Build XLSX or PDF
    ES->>FS: Store generated file
    ES-->>UI: Download path
    UI-->>A: Download file
```

### 18.7 Manual and Scheduled Backup (FR26)

```mermaid
sequenceDiagram
    autonumber
    actor O as Owner
    participant SCH as Scheduler
    participant UI as Backup Screen
    participant BS as BackupService
    participant DB as Database
    participant FS as External Storage

    alt Manual
        O->>UI: Run backup
        UI->>BS: run(manual, actor)
        BS->>DB: Dump schema and data
        BS->>FS: Store encrypted backup
        BS->>DB: Insert backup audit log
        BS-->>UI: Success or failure
    else Scheduled
        SCH->>BS: run(scheduled)
        BS->>DB: Dump schema and data
        BS->>FS: Store encrypted backup
        BS->>DB: Insert backup audit log
        BS-->>SCH: Success or failure
    end
```

### 18.8 Read the Production Queue (FR19)

```mermaid
sequenceDiagram
    autonumber
    actor A as Any Role
    participant UI as Production Queue
    participant DB as Database

    A->>UI: Open production queue
    UI->>DB: Query orders by branch and status
    DB-->>UI: Rows sorted by deadline, priority, created_at
    UI-->>A: Queue with customer, deadline, priority, status, designer, operator
```

### 18.9 Manage the Product Catalog (FR6)

```mermaid
sequenceDiagram
    autonumber
    actor A as Owner or Branch Manager
    participant UI as Catalog Screen
    participant PS as ProductService
    participant CS as ProductCategoryService
    participant DB as Database
    participant R as Reverb

    A->>UI: Submit a product or category form
    UI->>UI: Derive role and branch from the authenticated user
    alt Product
        A->>UI: Create, update, or delete a product
        UI->>UI: Validate name, category, pricing type, and active status
        alt Create or update
            UI->>PS: create or update(data, actor)
            PS->>DB: BEGIN
            PS->>DB: INSERT or UPDATE products
            PS->>DB: INSERT activity_logs
            PS->>DB: COMMIT
        else Delete
            UI->>PS: delete(product, actor)
            PS->>DB: BEGIN
            PS->>DB: Soft delete products
            PS->>DB: INSERT activity_logs
            PS->>DB: COMMIT
        end
    else Category
        A->>UI: Create, update, or delete a category
        UI->>UI: Validate name, calculation archetype, and allowed attribute types
        UI->>CS: createCategory, updateCategory, or deleteCategory
        CS->>DB: BEGIN
        CS->>DB: INSERT, UPDATE, or delete product_categories
        CS->>DB: INSERT activity_logs
        CS->>DB: COMMIT
    end
    UI->>R: event(OrderUpdated)
    UI-->>A: Success alert
```

Notes:

- `ProductService` is target behavior. Products and categories are global master data; the pricing matrix that uses them is branch-scoped (§17).
- `ProductCategoryService` exists, wraps its writes in a transaction, and logs failures, but it does not write `activity_logs` yet. FR6 requires the audit row.
- A category delete cascades to products, materials, and pricing rows unless a restrictive reference blocks it.

### 18.10 Search and Paginate Products (FR7)

```mermaid
sequenceDiagram
    autonumber
    actor A as Designer or Cashier
    participant UI as Product Browser
    participant PS as ProductService
    participant DB as Database

    A->>UI: Enter search text and choose category and active filters
    UI->>PS: paginate(search, category_id, is_active, per_page)
    PS->>DB: SELECT products JOIN product_categories with filters ORDER BY name
    DB-->>PS: Current page and total count
    PS-->>UI: LengthAwarePaginator with 20 rows by default
    UI-->>A: Product grid with previous and next navigation

    A->>UI: Select a product
    UI-->>A: Feed the product into the order calculator or checkout
```

Notes:

- The target is 20 products per page and a p95 response under the NFR1 target.
- Products are global; branch-specific availability comes from the pricing matrix, not from the product row.
- The current designer screen loads categories and products without pagination, so FR7 remains target.

### 18.11 Enforce Role and Branch Scope (FR5)

```mermaid
sequenceDiagram
    autonumber
    actor A as Authenticated User
    participant E as Route or Livewire Action
    participant G as Access Guard
    participant S as Service
    participant DB as Database
    participant B as Broadcast Auth

    A->>E: Submit a request or action
    E->>G: auth, verified, role, and policy checks
    alt Unauthenticated
        G-->>A: Redirect to login
    else Wrong role
        G-->>A: HTTP 403
    else Allowed role
        G->>G: Derive actor, role, and branch from the session
        E->>S: Call the service with the explicit actor
        S->>DB: Query or mutate with a branch predicate
        alt Record belongs to another branch
            S-->>E: Branch access denied
            E-->>A: HTTP 403
        else In scope
            S->>DB: COMMIT when the flow mutates
            E-->>A: Response or success alert
            opt Mutation broadcasts state
                E->>B: Broadcast on private-branch.{branchId}
                B->>G: Authorize owner or matching branch
            end
        end
    end
```

Notes:

- The target derives role and branch server-side. Client-writable fields such as `UserManagement::$mode` and request branch parameters are ignored or rejected.
- The same check applies to ticket and receipt routes, report exports, stock actions, and private broadcast channels.
- Private branch channels replace the current public `orders` channel described in §7.
- Production responses and exports omit price, cost, and profit fields.

---

## Diagram Maintenance

Add a sequence diagram when a new cross-component workflow is introduced or when a service contract changes enough that the existing diagram would mislead. Keep each diagram at the level of service calls and database effects, not private helper methods. Keep the requirement coverage table current when an FR is added, removed, or re-scoped. Do not move target behavior into the current sections until it ships.
