# Access Matrix

- **Roles:** `owner`, `manager`, `cashier`, `designer`, `production`.
- **Enforcement:** Laravel policies, FormRequest authorization, and explicit branch scoping in queries. Non-owner users are always limited to `auth()->user()->branch_id`; a null branch is denied.
- **Endpoint-level contract:** `docs/architecture/api-contracts.md`.

| Capability | Owner | Manager | Cashier | Designer | Production |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Login, own password | yes | yes | yes | yes | yes |
| Edit own name/email | yes | yes | no | no | no |
| Delete own account | yes | yes | no | no | no |
| Branch CRUD | all | no | no | no | no |
| User CRUD | all | own branch staff only | no | no | no |
| Categories, attribute types, attributes, quantity discounts | all | yes | no | no | no |
| Products | CRUD | CRUD | read | read | no |
| Materials master | CRUD; global delete | restock/activate/adjust own branch; no master create/edit/delete | no | no | read own branch |
| Prices and finishing/display prices | all | own branch | no | no | no |
| Customers | CRUD | CRUD | search | search/create/update | no |
| Invoices/orders | read all | read branch | read branch | create; read branch | read branch |
| Invoice print | all | branch | branch | branch | no |
| Down payments/settlements | no | no | own branch | no | no |
| Pickup | no | no | own branch | no | no |
| Production start/complete | no | no | no | no | own branch |
| Waste logging | no | no | no | no | own branch |
| Stock adjustment | all | own branch | no | no | no |
| Receipt | all | all | all | all | no |
| Dashboard | full | branch | branch | branch | branch |
| Owner financial reports | all | no | no | no | no |
| Designer leaderboard | no | own branch | no | no | no |
| Audit trail | all | no | no | no | no |
| Expenses | create/read all | create/read own branch | no | no | no |
| Real-time channels | owners + any branch | own branch | own branch | own branch | own branch |

## Rules

1. Managers cannot create or manage owner/manager accounts.
2. Managers cannot assign a user to another branch; branch ids from the client are ignored or rejected.
3. Production responses omit price, cost, profit, discount, receivable, and payment fields.
4. Invoice and receipt print are denied to production because both contain money. Production uses the queue, which has no money.
5. Customers are global and materials master data is global, matching the ERD. Stock rows are branch-scoped.
6. Invoice and receipt payloads enforce the same branch rule as the invoice.
7. Quantity discounts are configured by Owner/Manager per product category. Staff cannot override the tier from the client.
8. Expense categories are a fixed dropdown. Cashier has no expense access.

## Revision Note

Sprint Review 1 moved expense creation from Cashier to Owner/Branch Manager, removed the machine and pricing-matrix rows, added stock adjustment for Owner/Manager, and replaced the ticket with the invoice print. See `docs/requirements/sprint-review-1.md`.
