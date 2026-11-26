# Implementation Gaps

- **As of:** 2026-11-25, after the PB-05/PB-07 follow-up (material units, machine removal, production failure).
- **Scope:** The refactor replaced Livewire/Blade with Laravel API modules and a React SPA. Sprint Review 1 changed the target for pricing, machines, customers, expenses, production, and printing. The machine drop, material units, and production failure/reopen are now implemented; pricing, finishing/display persistence, and the remaining FR sprints are not.
- **Status source:** Route, controller, policy, model, migration, frontend, and test inspection on `feat/pb05-pb07-followup` at the final commit of the follow-up unit, plus the Sprint Review 1 change record.
- **Read this first:** A feature marked Implemented has API endpoints, policies, tests, and SPA screens. Partial means core behavior exists but part of the acceptance criteria is still missing. Target means no implementation yet.

## Functional Requirements

| FR | Status | Evidence and remaining gap |
| :--- | :--- | :--- |
| FR1 | Implemented | `AuthController` login, `AttemptLogin`, throttle, generic failure, dummy bcrypt equalization; `AuthTest`. |
| FR2 | Implemented | `UserController::store`; owner all roles, manager cashier/designer/production in own branch; `UserApiTest`. |
| FR3 | Implemented | Logout invalidates session; password change and confirm-password; `AuthTest`. |
| FR4 | Implemented | User update, soft delete, restore; manager branch/role limits; `UserApiTest`. |
| FR5 | Implemented | Policies, `BranchScope`, cross-branch tests, production money redaction; `FoundationTest`, `InvoiceApiTest`, `CatalogApiTest`. |
| FR6 | Partial | Owner-only Product CRUD + restore (`ProductController`, FormRequests, Actions, `products.is_active`, 10-row server-side pagination, `/owner/products`, read-only `/manager/products`) and Category quantity discount tiers (`product_category_discounts`, Owner-only `CategoryController` FormRequests/Actions, `/owner/categories`, read-only `/manager/categories`) are implemented (`CatalogApiTest`, `CatalogWriteApiTest`). Material units are implemented: `materials.unit` with seven values, migration plus backfill heuristic, required on create/optional on update, SPA field, dynamic Biaya/Harga labels, and `unitLabel`; the machine master, machine pricing, its tables, and its UI are removed. Managers can create/read/delete global attribute types and CRUD own-branch attributes and pricing rows through `CatalogPolicy`, `/manager/finishing`, and `/manager/pricing`. Remaining gaps: `attributes.price/cost/is_active`, a real `branches.reseller_discount_percent` column, pricing redesign, and applying the category discount tiers. The pricing matrix remains the price source. |
| FR7 | Implemented | `ProductController::index`, `CatalogQueries::products()`, 10-row pagination, case-insensitive search, category and active-status filters; `CatalogApiTest`. |
| FR8 | Partial | `InvoiceController::store`, `CreateInvoice`, quote/customer/cart flow, server-recomputed prices from the pricing matrix; `CreateInvoiceApiTest`. Customer search (`GET /customers?search=`), the separate `/designer/customers/new` page, and customer `institution`/`address` are implemented. Finishing/display masters exist (`attributes`), the designer form selects finishing/display, and the quote/invoice API accepts `selected_attributes[]` with `attribute_pricing` modifiers, but there is no `order_attributes` table and no persistence; machine selection is removed. Missing: finishing/display persistence and one A4 invoice print. |
| FR9 | Target | No invoice update route or `UpdateInvoice` action. Order items cannot be changed after submission. |
| FR10 | Target | No `design_status`, `design_approved_at`, or `design_approved_by` columns or actions. |
| FR11 | Implemented | `RegisterDownPayment`, `SettleInvoice`, receivable and overpay guards; `PaymentApiTest`. |
| FR12 | Implemented | Multiple DP accumulate; full DP marks paid; over-limit and double-settlement tests; `PaymentApiTest`. |
| FR13 | Partial | Receipt route, `InvoiceController::receipt`, and detailed 80 mm `ReceiptPage`; the designer order list labels this as Cetak Invoice. A dedicated A4 invoice layout is not implemented. |
| FR14 | Partial | `ExpenseController`, branch scope, cashier-only write; `ExpenseApiTest`. Needs Owner/Manager write, Cashier removal, fixed category dropdown, and `recorded_by_name`. |
| FR15 | Implemented | `purchases`/`purchase_items` migrations and models, `PurchaseController` index/store, `RecordPurchase`, `PurchaseList`, `PurchasePolicy`, and `/owner/purchases` + `/manager/purchases`; `PurchaseApiTest`. Same-branch `idempotency_key` replay returns 200 without new stock or ledger rows; caps, stock increments, missing stock rows, and soft-deleted material rejection are tested. |
| FR16 | Implemented | `InventoryService::applyMovement` writes typed ledger rows with actor and reference. Production start deducts by unit: `m2` uses `area * quantity` and 422s when `area <= 0`; every other unit uses `quantity`. Fail writes `usage_reversal` for the net `(material_id, branch_id)` usage; reopen clears the operator. Waste writes `waste` with the order reference, allows status 0, 1, or 3, makes the designer optional, and has no machine reference. Queue waste modal plus read-only `/production/waste` history; `InventoryServiceTest`, `ProductionWriteApiTest`, `ProductionFailApiTest`. |
| FR17 | Implemented | `GET /materials?low_stock=1` filters `current_stock <= min_stock`; owner/all uses OR across branches; top-level `is_low_stock` for a single branch plus per-`stocks[]` flags; production read-only Bahan & Stok page; `LowStockMaterialApiTest`. |
| FR18 | Implemented | `POST /materials/{material}/adjustments`, `AdjustStockRequest`, `AdjustStock`; new-quantity or signed-delta mode with a 3-255 character reason, zero floor, 99999999.99 cap, ledger type `adjustment`, activity log; `MaterialAdjustmentApiTest`. |
| FR19 | Partial | Queue API and `/production/queue` include statuses 0, 1, and 3 (Gagal) and are branch-scoped; sorting is `created_at`, not deadline/priority; no priority field; no status filter; no all-role `/queue` screen. The queue shows Gagal/Proses Ulang and waste actions. |
| FR20 | Implemented | `StartProduction` (0 -> 1), `CompleteProduction` (1 -> 2), `FailProduction` (1 -> 3, note 3-255, net usage reversal, operator kept), and `ReopenProduction` (3 -> 0, operator cleared); status logs, broadcasts, and invalid-transition 422; `ProductionWriteApiTest`, `ProductionFailApiTest`. |
| FR21 | Partial | Invoice `pickup_status`/`pickup_date` update; related `orders.pickup_status`/`pickup_date` are not propagated. |
| FR22 | Partial | Owner finance cards and sparkline; manager counts, stock, expenses, leaderboard; the production dashboard returns `queued`, `processing`, `completed_today`, `waste_today_by_unit`, and the queue, while `/production/summary` additionally returns `failed`. Missing owner/manager order count, production-status summary, and receivables. |
| FR23 | Partial | Owner has gross revenue, base cost, net profit, and expense reports. Manager has only the leaderboard; `ReportPolicy::viewOwnerReports` is owner-only. |
| FR24 | Target | No Excel/PDF export route, service, or UI. |
| FR25 | Partial | Audit read/detail for owner; invoice create, user, branch, and some catalog actions are logged. Purchase creation (`RecordPurchase`) and stock adjustment (`AdjustStock`) now write `ActivityLog` entries. Payment, order update, design status, expense, customer, and discount changes are not fully covered. |
| FR26 | Target | No manual backup command/route, scheduled backup, retention, or restore drill. |

## Non-Functional Requirements

| NFR | Status | Evidence and remaining gap |
| :--- | :--- | :--- |
| NFR1 | Partial | Pagination exists; no benchmark on a 10k-product/50k-order dataset. |
| NFR2 | Partial | Owner reports exist; export is missing and report performance is unmeasured. |
| NFR3 | Partial | DP arithmetic and multiple-DP tests pass; quantity-discount and order-edit arithmetic are not implemented. |
| NFR4 | Partial | Responsive layouts exist; no formal WCAG audit or usability test; invoice print and queue filters are missing. |
| NFR5 | Partial | Policies, branch scope, and money redaction are tested; expense-role changes, customer PII, HTTPS, and OWASP controls are not verified in this repository. |
| NFR6 | Partial | Compose and CI exist; backup/restore is FR26 and still missing. |
| NFR7 | Partial | API-first modules and SPA are in place; Catalog convention debt remains. |
| NFR8 | Partial | CI and Compose exist; Docker build was not executed in this VM; browser matrix is not automated. |
| NFR9 | Partial | Locking is implemented for payments, production, stock movements, and fail/reopen reversal; order-update locking, fail/reopen concurrency, and PostgreSQL concurrency are untested. |
| NFR10 | Implemented | Branch CRUD, branch scope, and branch tests exist; no formal five-minute benchmark. |

## Sprint Status

Mapping follows `docs/requirements/product-backlog.md`. Sprint numbers are backlog order, not the actual delivery order; the refactor delivered Sprint 0 and part of Sprint 1 early.

| Sprint | Backlog | FR | Status | Done | Missing |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Sprint 0 | Platform foundation, SPA shell | — | Done | Auth foundation, RBAC, branch scope, SPA shell, API, CI/CD, private channels | Docker/deploy validation |
| Sprint 1 | PB-08 | FR1–FR5 | Done | Auth, RBAC, branch scope, user management | HTTPS/OWASP formal verification |
| Sprint 1 | PB-01 | FR6, FR7 | Partial | Product CRUD/restore, 10-row pagination, Owner-only product/category writes, category quantity discount tiers (configuration), material units and labels, machine removal (tables dropped), legacy pricing matrix (transitional) | Attribute prices, reseller discount config, pricing redesign, category discount application |
| Sprint 1 | PB-02 | FR8, FR9, FR10 | Partial | Order create, customer search and creation, quote, cart, pricing-matrix invoice, finishing/display selection and `selected_attributes` API pipe | Finishing/display persistence, order update, design status, A4 invoice print |
| Sprint 1 | PB-03 | FR11, FR12 | Done | DP, settlement, multiple DP, overpay guard | — |
| Sprint 1 | PB-04 | FR13, FR14 | Partial | Receipt, operational expenses | A4 invoice print, expense role change, category dropdown |
| Sprint 2 | PB-05 | FR15, FR17, FR18 | Done | Stock read, restock, stock ledger, purchase records, reason-required adjustment, low-stock filter | — |
| Sprint 2 | PB-06 | FR19, FR20, FR21 | Partial | Start/complete/fail/reopen production, status log, net usage reversal | All-role queue UI, status filter, deadline/priority sort, order-level pickup |
| Sprint 2 | PB-07 | FR16 | Done | Unit-aware automatic stock deduction, order-linked usage and waste ledgers, `usage_reversal` on fail, queue waste modal, read-only waste history | — |
| Sprint 3 | PB-09 | FR22, FR23, FR24 | Partial | Owner reports/dashboard, manager leaderboard | Manager dashboard/report completeness, Excel/PDF export |
| Sprint 3 | PB-10 | FR25, FR26 | Partial | Audit read/detail, purchase and stock-adjustment audit writes | Payment/order/expense/customer/design/discount audit coverage, manual and scheduled backup |

Summary:

- Sprint 0: done, except deployment verification.
- Sprint 1: PB-08 and PB-03 fully done; PB-01, PB-02, and PB-04 partial. PB-08 moved from Sprint 3 to Sprint 1 after Sprint Review 1.
- Sprint 2: PB-05 and PB-07 done; PB-06 partial.
- Sprint 3: 0 of 2 done; PB-09 and PB-10 partial.

## What Is Left

Priority order:

1. **Pricing redesign**: material price + finishing/display price + branch reseller discount + applied category quantity discount; remove the pricing matrix and migrate data. Machine UI and tables are already gone.
2. **FR6** attribute price/cost/is_active, branch reseller discount config, and applying the configured category discount tiers.
3. **FR8** finishing/display persistence (`order_attributes`) and A4 invoice print.
4. **FR9** order update before production and while not fully paid; **FR10** design status.
5. **FR14** expense Owner/Manager access and fixed category dropdown.
6. **FR19** deadline/priority sorting, status filter, all-role queue UI; **FR21** order-level pickup propagation.
7. **FR22/FR23** manager dashboard/report completeness; **FR24** Excel/PDF export.
8. **FR25** audit coverage for payments, order updates, design status, expenses, customers, and discounts; **FR26** manual and scheduled backup.

## Refactor Completion

- Livewire and Blade application UI removed.
- API-first modules, Sanctum, React SPA, private Reverb channels, Docker Compose, CI, `init.sh`, and docs are in place.
- Remaining work is the FR sprints above plus the Sprint Review 1 target changes, not the architecture.

## Convention Debt

- Some Catalog controllers still use inline `$request->validate()` and direct model writes (materials update, attributes, attribute types, pricing). Sales and Identity use FormRequests plus Actions. Extract Catalog FormRequests/Actions in the next cleanup.
- Catalog price/cost mutations are not fully written to `activity_logs`; FR25 remains partial.
- `ProductionController::summary` builds its aggregate query in the controller; extract a `ProductionSummaryQuery` when FR19/FR21 work starts.
- Existing audit descriptions and stock-ledger notes stay stored in English; new audit rows are Indonesian. Stock-ledger notes such as `Manual stock addition: +N` are persisted through the API but not displayed by the SPA, so they are left as-is.
- The SPA has no i18n layer by design; copy is hardcoded Indonesian and code, API, and database values stay English. `expense.category` must move from free text to the fixed dropdown in FR14.
- `Item`, `Global`, and the printing loanwords `Finishing` and `File` are sanctioned in the design glossary and remain in English.
- The pricing-matrix code is transitional. Keep it behind the current UI until the replacement pricing model and migration are reviewed. The machine code and tables are already gone.

## Parked Debt And Unverified

- The machine tables were dropped by `2026_11_25_000002_drop_machines_tables.php`; their rows are unrecoverable, and `down()` recreates empty tables only. This drop supersedes the machine references in the historical sprint records, specs, planning files, and `root-cause-mapping.md`, which are left as written.
- The pricing-matrix endpoints (`/pricing`) remain transitional, along with `/manager/pricing` (`Matriks Harga`). There is no machine endpoint or machine page.
- PostgreSQL concurrency is unverified for purchase idempotent replay, creating a missing branch stock row, and fail/reopen net-usage reversal. SQLite cannot reproduce those races; the unique `(branch_id, idempotency_key)` index, replay lookup, unique-violation catch, and `insertOrIgnore` + `lockForUpdate` path are implemented but not race-tested.
- Legacy `stock_ledgers` rows may keep a null `type`, actor, and reference. The backfill is best-effort: it maps the known notes signatures and classifies remaining null-type rows with `added_stock > 0` as `restock`; it invents neither an actor nor a reference.
