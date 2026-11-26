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
| Categories, quantity discounts | all | read | no | no | no |
| Attribute types | all | create/read/delete (global) | no | no | no |
| Attributes (finishing, display, cut) | all | CRUD own branch | no | no | no |
| Pricing matrix | all | CRUD own branch | no | no | no |
| Products | CRUD | read | read | read | no |
| Materials master | CRUD; read all branches including low stock; global delete | create (own-branch stock only); restock/activate/adjust own branch; read own branch including low stock; no master edit/delete | no | no | read own branch including low stock |
| Customers | create/read/update | create/read/update | search | search/create/update | no |
| Invoices/orders | read all | read branch | read branch | create; read branch | read branch |
| Invoice update (pre-production, not fully paid) | no | no | no | own branch | no |
| Design status | all | own branch override | no | own branch | no |
| Invoice print | all | branch | branch | branch | no |
| Down payments/settlements | no | no | own branch | no | no |
| Pickup | no | no | own branch | no | no |
| Production start/complete/fail/reopen | no | no | no | no | own branch; status-gated |
| Production queue read | all | own branch | own branch | own branch | own branch |
| Waste logging | no | no | no | no | own branch; status 0, 1, or 3 |
| Purchases | create/read all | create/read own branch | no | no | no |
| Stock adjustment | all | own branch | no | no | no |
| Receipt | all | branch | branch | branch | no |
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
4. Invoice and receipt print are denied to production because both contain money. Production uses the queue, which has no money.
5. Customers are global and materials master data is global, matching the ERD. Stock rows are branch-scoped.
6. Invoice and receipt payloads enforce the same branch rule as the invoice.
7. Quantity discounts are configured by the Owner per product category but are not applied by the current quote/invoice path. Staff cannot override a tier from the client.
8. Expense categories are a fixed dropdown. Cashier is the only role that creates expenses and writes to their own branch; Owner and Manager can read them.
9. Category, product, and discount writes are Owner-only. Managers may create materials, manage global attribute types, and manage attributes and pricing rows for their own branch. Manager-created materials initialize branch stock only for the manager's own branch. Branch Manager reads products and categories and manages branch stock.
10. The Owner may write `branches.settings` through Branch CRUD. `settings.reseller_discount_percent` is API-only, has no SPA field, and is currently uncapped; the target `branches.reseller_discount_percent` column is not implemented. Manager has no branch write access.
11. Designer updates an invoice only before production and while it is not fully paid. Owner and Branch Manager may override a design-status transition; Designer cannot move backward.
12. A non-owner without a branch is never treated as global. Purchase requests return 403, branch-scoped lists return no rows, and actions that need a branch fail; a null branch is not `all`.
13. Fail and reopen are production-only and branch-scoped. Fail requires status `1`; reopen requires status `3`; any other source status returns 422 with no stock or log change. Waste is allowed on status `0`, `1`, and `3`. The machine master, its pricing, its endpoints, and machine selection no longer exist.

## Revision Note

Sprint Review 1 moved expense creation from Cashier to Owner/Branch Manager, added stock adjustment for Owner/Manager, and replaced the ticket with the invoice print. See `docs/requirements/sprint-review-1.md`. The PB-05/PB-07 follow-up dropped the machine tables and endpoints, added unit-aware production with fail/reopen, and allows waste on failed orders; the pricing matrix remains.
