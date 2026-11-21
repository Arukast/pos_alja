# Sequence Diagrams

All flows use the React SPA as the only frontend. The SPA calls `/api/v1` with Sanctum cookies and CSRF. Branch and record checks happen server-side.

## 1. Login

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB

    SPA->>API: GET /sanctum/csrf-cookie
    API-->>SPA: CSRF cookie
    SPA->>API: POST /api/v1/auth/login
    API->>DB: find user by username
    API->>API: bcrypt check, rate limit
    API-->>SPA: 200 user + branch, session cookie
    SPA->>API: GET /api/v1/auth/me
    API-->>SPA: authenticated user
```

## 2. Create Invoice

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB
    participant Pricing

    SPA->>API: GET /api/v1/quotes/options
    API-->>SPA: categories, products, materials, machines, attributes
    SPA->>API: POST /api/v1/quotes
    API->>Pricing: match branch pricing tier
    Pricing-->>API: unit price, area, subtotal
    API-->>SPA: quote (no cost/profit)
    SPA->>API: POST /api/v1/invoices
    API->>DB: lock branch, recompute every line
    API->>DB: create invoice, orders, status logs, audit
    API-->>SPA: 201 invoice (designer shape)
    API-->>SPA: OrderUpdated on private-branch
```

## 3. Down Payment And Settlement

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB

    SPA->>API: POST /invoices/{id}/down-payments
    API->>DB: lock invoice
    API->>DB: reject amount above receivable
    API->>DB: create payment, recalculate totals
    API-->>SPA: payment + invoice
    SPA->>API: POST /invoices/{id}/settlements
    API->>DB: lock invoice
    API->>DB: compute amount = receivable - discount
    API->>DB: reject overpay or over-discount
    API->>DB: create settlement, recalculate
    API-->>SPA: payment + invoice
```

## 4. Production

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB
    participant Inventory

    SPA->>API: POST /orders/{id}/start
    API->>DB: lock order
    API->>DB: require status 0
    API->>Inventory: deduct area or quantity once
    API->>DB: status 1, operator, status log
    API-->>SPA: order (no money)
    SPA->>API: POST /orders/{id}/complete
    API->>DB: lock order
    API->>DB: require status 1
    API->>DB: status 2, status log
    API-->>SPA: order (no money)
```

## 5. Waste Logging

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB

    SPA->>API: GET /waste/options
    API-->>SPA: branch materials, machines, designers (no money)
    SPA->>API: POST /waste
    API->>DB: verify material, machine, designer, order branch
    API->>DB: lock stock, create waste, ledger
    API-->>SPA: waste (no money)
```

## 6. Pickup

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB

    SPA->>API: GET /invoices?status=2
    API-->>SPA: paid invoices
    SPA->>API: POST /invoices/{id}/pickup
    API->>DB: lock invoice
    API->>DB: require status 2 and same branch
    API->>DB: pickup_status 1, pickup_date
    API-->>SPA: invoice
```

## 7. Realtime Invalidation

```mermaid
sequenceDiagram
    participant Writer as Any API client
    participant API
    participant Reverb
    participant SPA

    Writer->>API: successful non-GET request
    API->>API: commit transaction
    API->>Reverb: OrderUpdated on branch and/or owners
    Reverb-->>SPA: private channel event
    SPA->>API: refetch authenticated queries
```

## 8. Owner Reporting

```mermaid
sequenceDiagram
    participant SPA
    participant API
    participant DB

    SPA->>API: GET /reports/net-profit?branch_id&start_date&end_date
    API->>DB: payments, invoice costs, waste costs, expenses
    API-->>SPA: totals, margin, ledgers
```

## 9. Authorization Rules

Every request shown above:

1. Requires an authenticated session except login.
2. Is scoped to the actor's branch unless the actor is the owner.
3. Returns 403 for another branch and 422 for invalid business input.
4. Omits cost, profit, discount, receivable, and payment fields from production responses.
