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
| Categories, attribute types, attributes | all | yes | no | no | no |
| Products | read | read | read | read | no |
| Materials master | CRUD; global delete | create; restock/activate own branch; no master edit/delete | no | no | read own branch |
| Machines | all | own branch | no | no | no |
| Pricing | all | own branch | no | no | no |
| Customers | read | read | search | search/create | no |
| Invoices/orders | read all | read branch | read branch | create; read branch | read branch |
| Down payments/settlements | no | no | own branch | no | no |
| Pickup | no | no | own branch | no | no |
| Production start/complete | no | no | no | no | own branch |
| Waste logging | no | no | no | no | own branch |
| Ticket | all | all | all | all | all, no money |
| Receipt | all | all | all | all | no |
| Dashboard | full | branch | branch | branch | branch |
| Owner financial reports | all | no | no | no | no |
| Designer leaderboard | no | own branch | no | no | no |
| Audit trail | all | no | no | no | no |
| Expenses | read all | read own branch | create/read own branch | no | no |
| Real-time channels | owners + any branch | own branch | own branch | own branch | own branch |

## Rules

1. Managers cannot create or manage owner/manager accounts.
2. Managers cannot assign a user to another branch; branch ids from the client are ignored or rejected.
3. Production responses omit price, cost, profit, discount, receivable, and payment fields.
4. Receipt is denied to production because a receipt is entirely money; ticket remains available.
5. Customers are global and materials master data is global, matching the ERD. Stock rows are branch-scoped.
6. Ticket and receipt payloads enforce the same branch rule as the invoice.
