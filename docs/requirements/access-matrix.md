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
| Categories | all | read | no | no | no |
| Attributes (finishing, display; legacy `cut` rows cannot be edited without changing their type) | all | global read-only; create/update own branch | no | no | no |
| Pricing matrix | all | CRUD own branch | no | no | no |
| Products | CRUD | read | read | read | no |
| Materials master | CRUD; global active toggle; read all branches including low stock; global delete | create (own-branch stock only); restock/activate/adjust own branch; branch active toggle; read own branch including low stock; no master edit/delete and no global toggle | no | no | read own branch including low stock |
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
| Minimum stock threshold | all branches | own branch | no | no | no |
| Receipt | all | branch | branch | branch | no |
| Dashboard | full | branch | branch | branch | branch |
| Owner financial reports | all | no | no | no | no |
| Designer leaderboard | no | own branch | no | no | no |
| Audit trail | all | no | no | no | no |
| Backup page (history, run) | all | no | no | no | no |
| Backup download | all | no | no | no | no |
| Backup pin/unpin | all | no | no | no | no |
| Backup settings (schedule, retention) | all | no | no | no | no |
| Expenses | read all | read own branch | create/read own branch | no | no |
| Real-time channels | owners + any branch | own branch | own branch | own branch | own branch |

## Rules

1. Managers cannot create or manage owner/manager accounts.
2. Managers cannot assign a user to another branch; branch ids from the client are ignored or rejected.
3. Production responses omit price, cost, profit, discount, receivable, and payment fields.
4. Invoice and receipt print are denied to production because both contain money. Production uses the queue, which has no money.
5. Customers are global and materials master data is global, matching the ERD. Stock rows are branch-scoped. `materials.is_active` is the global retire flag; `material_branch_stock.is_active` is branch availability. Both default true.
6. Invoice and receipt payloads enforce the same branch rule as the invoice.
7. Category quantity-discount tiers are removed. The only discount is the branch reseller discount; staff cannot override it from the client.
8. Expense categories are a fixed dropdown. Cashier is the only role that creates expenses and writes to their own branch; Owner and Manager can read them.
9. Category, product, and branch reseller-discount writes are Owner-only. Managers may create materials and manage attributes and pricing rows for their own branch; global attributes are read-only. Manager-created materials initialize branch stock only for the manager's own branch. Branch Manager reads products and categories and manages branch stock.
10. The Owner may write branch master data, including `reseller_discount_percent` (`0–100`, default `0`), through Branch CRUD; `BranchService` mirrors it into `branches.settings.reseller_discount_percent`. Manager has no branch write access.
11. Designer updates an invoice only before production and while it is not fully paid. Owner and Branch Manager may override a design-status transition; Designer cannot move backward.
12. A non-owner without a branch is never treated as global. Purchase requests return 403, branch-scoped lists return no rows, and actions that need a branch fail; a null branch is not `all`.
13. Fail and reopen are production-only and branch-scoped. Fail requires status `1`; reopen requires status `3`; any other source status returns 422 with no stock or log change. Waste is allowed on status `0`, `1`, and `3`. The machine master, its pricing, its endpoints, and machine selection no longer exist.
14. Catalog rows are retired with `is_active`; category, attribute, and attribute-type delete endpoints are removed; product and material delete/restore remain Owner-only recovery APIs with no SPA action. The materials SPA action is removed, so Hapus no longer appears. Attributes soft-deleted before Unit A have no restore path.
15. Editing a branch `min_stock` writes no `stock_ledgers` row and no activity log; `current_stock` changes only through restock, adjustment, purchase, or usage. The branch-stock row must already exist; otherwise the request returns 422 on `branch_id` and creates nothing. An explicit branch other than the manager's own is 403, and a manager without a branch is denied.
16. Audit and backup are owner-only and global, not branch-filtered. `backup:tick` is a system actor (`user_id = null`, `username = system`, `role = system`, `branch_id = null`); manual runs carry the owner's user id.
17. Customers are global and `CustomerPolicy::create` checks only the designer role. A designer with no branch can therefore create a customer, and the audit row records `branch_id = null`. This is a pre-existing access gap, not changed by PB-10; it is recorded in `docs/requirements/implementation-gaps.md`.
18. Material availability toggles: `PATCH /materials/{material}/active` is Owner-only and writes one `UPDATE` audit row with the flag before/after. It writes no `stock_ledgers` row and leaves `current_stock` unchanged. The audit row inherits the actor's `branch_id` (null when the owner has no branch). `PATCH /materials/{material}/branch-availability` is Owner any branch and Manager own branch only; a manager's explicit other branch is 403, a branchless manager is denied, the owner must name the branch, and a missing branch-stock row returns 422 with no row created. It writes no audit row and no ledger row; this is accepted FR25 debt. `POST /materials/{material}/activate` sets an existing inactive branch-stock row active and keeps its existing audit payload.
19. New quote and invoice lines require the material to be globally active and to have an active branch-stock row in the branch; otherwise the request returns 422 on `material_id` (quote) or `items.{index}.pricing_id` (invoice). An invoice edit allows an inactive material already on the locked existing invoice, including a new line that reuses that material; a different unavailable material returns 422. A soft-deleted material is always unavailable, even when already used, so the allowance applies only to inactive-but-present materials. `available_total`, the manager `Bahan Aktif` stat, and the manager dashboard `Bahan Tersedia` count globally active materials; a named branch adds the active branch-stock predicate, while an owner request with `branch_id=all` or omitted counts globally active materials only.
20. Attribute resources omit `cost` for every non-owner/manager role (cashier and production included, though only designers can read quote options); Owner and Manager keep `cost`. `price` is unchanged.

## Revision Note

Sprint Review 1 moved expense creation from Cashier to Owner/Branch Manager, added stock adjustment for Owner/Manager, and replaced the ticket with the invoice print. See `docs/requirements/sprint-review-1.md`. The PB-05/PB-07 follow-up dropped the machine tables and endpoints, added unit-aware production with fail/reopen, and allows waste on failed orders; the pricing matrix remains. The Unit A catalog cleanup removed category discount tiers and attribute types, made categories retire-only with `description`/`is_active`, allowed Manager own-branch attributes, and added branch minimum-stock editing without a stock ledger or audit row. PB-10 adds owner-only backup page, download, pin/unpin, and settings; the audit trail stays owner-only and global. B1a (2026-11-26) adds material retirement with a global active flag and per-branch stock availability, quote/invoice availability enforcement, the material Hapus removal, `available_total` counts, and designer attribute `cost` omission.
