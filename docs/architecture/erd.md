# Entity Relationship Diagram

- **Database:** PostgreSQL in production, SQLite in the automated test suite.
- **Keys:** All primary keys are UUID strings. Foreign keys are UUID strings.
- **Table naming:** Eloquent default pluralization, except `pricing`, `material_branch_stock`, `attribute_pricing`, and `machine_pricing`, which use singular table names.
- **Soft deletes:** Present on `branches`, `users`, `customers`, `products`, `materials`, `machines`, and `attributes`. `pricing` has a `deleted_at` column in the migration, but the model does not use `SoftDeletes`; the column is unused.

```mermaid
erDiagram
    BRANCHES |o--o{ USERS : "assigns staff"
    BRANCHES ||--o{ MATERIAL_BRANCH_STOCK : "holds stock"
    BRANCHES |o--o{ MACHINES : "owns"
    BRANCHES |o--o{ ATTRIBUTES : "owns"
    BRANCHES |o--o{ PRICING : "prices"
    BRANCHES |o--o{ ATTRIBUTE_PRICING : "scopes"
    BRANCHES |o--o{ MACHINE_PRICING : "scopes"
    BRANCHES |o--o{ INVOICES : "issues"
    BRANCHES ||--o{ PAYMENTS : "receives"
    BRANCHES |o--o{ EXPENSES : "incurs"
    BRANCHES |o--o{ WASTED_MATERIALS : "logs"
    BRANCHES |o--o{ STOCK_LEDGERS : "records"
    BRANCHES |o--o{ ACTIVITY_LOGS : "audits"

    CUSTOMERS ||--o{ INVOICES : "places"

    PRODUCT_CATEGORIES |o--o{ PRODUCTS : "groups"
    PRODUCT_CATEGORIES ||--o{ MATERIALS : "groups"

    PRODUCTS ||--o{ PRICING : "priced by"
    MATERIALS ||--o{ PRICING : "priced by"
    MATERIALS ||--o{ MATERIAL_BRANCH_STOCK : "stocked at"
    MATERIALS ||--o{ STOCK_LEDGERS : "moves"
    MATERIALS ||--o{ WASTED_MATERIALS : "wasted"

    PRICING ||--o{ ATTRIBUTE_PRICING : "modifiers"
    PRICING ||--o{ MACHINE_PRICING : "modifiers"
    PRICING |o--o{ ORDERS : "quoted by"

    ATTRIBUTES ||--o{ ATTRIBUTE_PRICING : "priced as"
    MACHINES ||--o{ MACHINE_PRICING : "priced as"
    MACHINES |o--o{ WASTED_MATERIALS : "used by"

    INVOICES ||--o{ ORDERS : "contains"
    INVOICES ||--o{ PAYMENTS : "settled by"
    ORDERS ||--o{ ORDER_STATUS_LOGS : "status history"
    ORDERS |o--o{ WASTED_MATERIALS : "optional link"

    USERS |o--o{ INVOICES : "designer"
    USERS |o--o{ ORDERS : "operator"
    USERS |o--o{ PAYMENTS : "cashier"
    USERS |o--o{ ORDER_STATUS_LOGS : "actor"
    USERS |o--o{ WASTED_MATERIALS : "designer"
    USERS |o--o{ WASTED_MATERIALS : "operator"
    USERS |o--o{ ACTIVITY_LOGS : "actor"

    BRANCHES {
        uuid id PK
        string name
        string code UK
        string address
        string phone
        boolean is_active
        json settings
        timestamp deleted_at
    }

    USERS {
        uuid id PK
        string name
        string username UK
        string email
        timestamp email_verified_at
        string password
        string role
        uuid branch_id FK
        string phone
        text address
        timestamp deleted_at
    }

    CUSTOMERS {
        uuid id PK
        string name
        string phone
        text address
        string email
        string type
        timestamp deleted_at
    }

    PRODUCT_CATEGORIES {
        uuid id PK
        string name
        string calculation_archetype
        json allowed_attribute_types
    }

    PRODUCTS {
        uuid id PK
        uuid product_category_id FK
        string name
        string pricing_type
        timestamp deleted_at
    }

    MATERIALS {
        uuid id PK
        uuid product_category_id FK
        string name
        string type
        string dimensions
        decimal cost
        decimal price
        timestamp deleted_at
    }

    MATERIAL_BRANCH_STOCK {
        uuid id PK
        uuid material_id FK
        uuid branch_id FK
        decimal current_stock
        decimal min_stock
    }

    MACHINES {
        uuid id PK
        uuid branch_id FK
        string name
        decimal cost
        timestamp deleted_at
    }

    ATTRIBUTES {
        uuid id PK
        uuid branch_id FK
        string name
        string type
        timestamp deleted_at
    }

    ATTRIBUTE_TYPES {
        uuid id PK
        string name UK
        string calculation_archetype
    }

    PRICING {
        uuid id PK
        uuid branch_id FK
        uuid product_id FK
        uuid material_id FK
        string customer_type
        integer min_qty
        decimal price
        decimal cost
        decimal profit
    }

    ATTRIBUTE_PRICING {
        uuid id PK
        uuid branch_id FK
        uuid pricing_id FK
        uuid attribute_id FK
        decimal price
        decimal cost
        decimal profit
    }

    MACHINE_PRICING {
        uuid id PK
        uuid branch_id FK
        uuid pricing_id FK
        uuid machine_id FK
        decimal price
        decimal cost
        decimal profit
    }

    INVOICES {
        uuid id PK
        string invoice_number UK
        uuid branch_id FK
        timestamp created_date
        timestamp payment_date
        timestamp dp_date
        uuid customer_id FK
        uuid designer_id FK
        integer status
        decimal discount
        decimal dp_amount
        decimal accounts_receivable
        decimal grand_total
        decimal total_cost
        decimal total_profit
        timestamp deadline
        integer pickup_status
        timestamp pickup_date
        integer source
    }

    ORDERS {
        uuid id PK
        uuid invoice_id FK
        uuid pricing_id FK
        uuid operator_id FK
        string file_name
        string file_path
        string preview_path
        text notes
        decimal area
        decimal quantity
        decimal unit_price
        decimal subtotal
        decimal cost
        decimal profit
        timestamp ordered_at
        integer status
        integer pickup_status
        timestamp pickup_date
        boolean is_manual_price
    }

    PAYMENTS {
        uuid id PK
        uuid branch_id FK
        uuid invoice_id FK
        uuid user_id FK
        decimal amount
        string payment_method
        string type
        string cashier_name
    }

    EXPENSES {
        uuid id PK
        uuid branch_id FK
        timestamp incurred_at
        decimal amount
        text description
        string cashier_name
        string type
        string category
    }

    WASTED_MATERIALS {
        uuid id PK
        uuid branch_id FK
        timestamp logged_at
        uuid material_id FK
        uuid machine_id FK
        uuid order_id FK
        uuid designer_id FK
        uuid operator_id FK
        decimal quantity
        string dimensions
        text notes
    }

    STOCK_LEDGERS {
        uuid id PK
        uuid branch_id FK
        uuid material_id FK
        timestamp logged_at
        decimal initial_stock
        decimal added_stock
        decimal used_stock
        decimal wasted_stock
        decimal remaining_stock
        text notes
    }

    ORDER_STATUS_LOGS {
        uuid id PK
        uuid order_id FK
        integer status
        uuid user_id FK
        string user_name
        string notes
    }

    ACTIVITY_LOGS {
        uuid id PK
        uuid branch_id FK
        uuid user_id FK
        string username
        string role
        string action
        string description
        string model_type
        uuid model_id
        json payload
    }
```

---

## Relationship Notes

1. **`attribute_types` is a reference table without foreign keys.** `attributes.type` stores the type name (`finishing`, `cut`, `display`, or a custom type created through the category screen), and `product_categories.allowed_attribute_types` stores an array of type names. Referential integrity is by convention, not by key.
2. **`material_branch_stock` is the only junction with a unique `(material_id, branch_id)` constraint.** `attribute_pricing` and `machine_pricing` are also junction tables, each linking a `pricing` row to one modifier and carrying price, cost, and profit.
3. **`orders` is a line item of `invoices`.** The invoice carries the financial roll-up (`grand_total`, `total_cost`, `total_profit`), while each order carries its own `unit_price`, `subtotal`, `cost`, and `profit`.
4. **`pricing` is the matrix header.** `attribute_pricing` and `machine_pricing` are additive modifiers keyed by `pricing_id`. `orders.pricing_id` records which matrix row produced the quote. Pricing deletion is a hard delete, and `orders.pricing_id` is `nullOnDelete`, so deleting a pricing row breaks that historical link.
5. **`orders.operator_id`** is the production operator who started or completed the job; it is null until production starts.
6. **`invoices.designer_id`** is the user who submitted the order.
7. **`payments.user_id`** is the cashier; `payments.cashier_name` is a denormalized audit copy.
8. **`expenses` has no user foreign key**, only `cashier_name` and `branch_id`.
9. **`activity_logs.model_id` is a UUID without a foreign key**, allowing the log to reference any model type.
10. **`branch_id` is nullable on** `users`, `machines`, `attributes`, `pricing`, `attribute_pricing`, `machine_pricing`, `invoices`, `expenses`, `wasted_materials`, `stock_ledgers`, and `activity_logs`. A null value means "global" or "unassigned", depending on the table; owner accounts are the main case for a null user branch.
11. **Soft-deleted rows are not shown separately.** The `deleted_at` column is included only on tables that use `SoftDeletes`; deleted rows remain in the table. The migration defines `pricing.deleted_at`, but the model does not use `SoftDeletes`, so pricing rows are hard deleted and the column stays null.
12. **`product_categories` deletion cascades, subject to NO ACTION references.** It is a hard delete, and `products.product_category_id` plus `materials.product_category_id` use `cascadeOnDelete`. The delete removes dependent products, materials, and pricing rows when no NO ACTION reference blocks it; `wasted_materials.material_id` and `stock_ledgers.material_id` can reject the delete when history exists.

---

## Status Code Reference

| Column | Value | Meaning |
| :--- | :--- | :--- |
| `invoices.status` | 0 | Unpaid / pending down payment |
| `invoices.status` | 1 | Down payment paid |
| `invoices.status` | 2 | Fully paid |
| `invoices.pickup_status` | 0 | Not picked up |
| `invoices.pickup_status` | 1 | Picked up |
| `orders.status` | 0 | Pending / not processed |
| `orders.status` | 1 | Processing / printing |
| `orders.status` | 2 | Completed |
| `payments.type` | `down_payment` | Down payment |
| `payments.type` | `settlement` | Balance settlement |
| `payments.type` | `partial` | Reserved by schema; not written by `PaymentService` |
| `expenses.type` | `modal` | Capital / drawer float label |
| `expenses.type` | `laba` | Operating expense label |

Both expense types are summed into `expenses_total` and subtracted from `net_profit`; `type` is a label, not a filter.

---

## Key Constraints

| Table | Constraint |
| :--- | :--- |
| `branches` | `code` unique |
| `users` | `username` unique |
| `material_branch_stock` | `(material_id, branch_id)` unique |
| `invoices` | `invoice_number` unique |
| `attribute_types` | `name` unique |
| `pricing` | No unique constraint on `(branch_id, product_id, material_id, customer_type, min_qty)`; duplicates are possible |
| Foreign keys | `cascadeOnDelete` on most owned children; `nullOnDelete` on actor references, `orders.pricing_id`, `users.branch_id`, `wasted_materials.machine_id`, and `wasted_materials.order_id`; NO ACTION/RESTRICT on `invoices.customer_id`, `wasted_materials.material_id`, and `stock_ledgers.material_id` |

---

## Diagram Maintenance

Update this diagram whenever a migration adds, removes, or renames a table or a foreign key. Do not edit merged migrations; add a new migration and reflect the result here.
