# Implementation Gaps

- **As of:** 2026-11-22, after the API-first React refactor and PR #37.
- **Scope:** The refactor replaced Livewire/Blade with Laravel API modules and a React SPA. It did not add the FRs that were still Target in the capstone plan.
- **Status source:** Route, controller, policy, model, migration, frontend, and test inspection on `main` (`2df7f41`). The test suite was not executed during this audit.
- **Read this first:** A feature marked Implemented has API endpoints, policies, tests, and SPA screens. Partial means core behavior exists but part of the acceptance criteria is still missing. Target means no implementation yet.

## Functional Requirements

| FR | Status | Evidence and remaining gap |
| :--- | :--- | :--- |
| FR1 | Implemented | `AuthController` login, `AttemptLogin`, throttle, generic failure, dummy bcrypt equalization; `AuthTest`. |
| FR2 | Implemented | `UserController::store`; owner all roles, manager cashier/designer/production in own branch; `UserApiTest`. |
| FR3 | Implemented | Logout invalidates session; password change and confirm-password; `AuthTest`. |
| FR4 | Implemented | User update, soft delete, restore; manager branch/role limits; `UserApiTest`. |
| FR5 | Implemented | Policies, `BranchScope`, cross-branch tests, production money redaction; `FoundationTest`, `InvoiceApiTest`, `CatalogApiTest`. |
| FR6 | Partial | Product read, search, filter, and pagination exist. Product create/update/delete routes and UI are missing; `ProductController` only has `index`. |
| FR7 | Implemented | `ProductController::index`, `CatalogQueries::products()`, 20-row pagination; `CatalogApiTest`. |
| FR8 | Implemented | `InvoiceController::store`, `CreateInvoice`, quote/customer/cart flow, server-recomputed prices; `CreateInvoiceApiTest`. |
| FR9 | Target | No invoice update route or `UpdateInvoice` action. Order items cannot be changed after submission. |
| FR10 | Target | No `design_status`, `design_approved_at`, or `design_approved_by` columns or actions. |
| FR11 | Implemented | `RegisterDownPayment`, `SettleInvoice`, receivable and overpay guards; `PaymentApiTest`. |
| FR12 | Implemented | Multiple DP accumulate; full DP marks paid; over-limit and double-settlement tests; `PaymentApiTest`. |
| FR13 | Implemented | Receipt route, `InvoiceController::receipt`, and `ReceiptPage`; production is denied. |
| FR14 | Implemented | `ExpenseController`, branch scope, cashier-only write; `ExpenseApiTest`. |
| FR15 | Target | No `purchases`/`purchase_items` tables, routes, or UI. Restock only adds stock. |
| FR16 | Partial | Production start deducts stock and waste reduces stock, but `stock_ledgers` has no `type`, `order_id`, `user_id`, or `reason`; no order-linked usage record. |
| FR17 | Partial | Owner/manager/production can read stock, but there is no low-stock endpoint, notification, or alert. |
| FR18 | Target | No reason-required stock adjustment route or service. Restock accepts optional notes only. |
| FR19 | Partial | Queue API is readable by all roles, but the SPA queue is production-only; sorting is `created_at`, not deadline/priority; no priority field. |
| FR20 | Implemented | `StartProduction`, `CompleteProduction`, status logs, stock deduction once; `ProductionWriteApiTest`. |
| FR21 | Partial | Invoice `pickup_status`/`pickup_date` update; related `orders.pickup_status`/`pickup_date` are not propagated. |
| FR22 | Partial | Owner finance cards and sparkline; manager counts, stock, expenses, leaderboard. Missing order count, production-status summary, and receivables. |
| FR23 | Partial | Owner has gross revenue, base cost, net profit, and expense reports. Manager has only the leaderboard; `ReportPolicy::viewOwnerReports` is owner-only. |
| FR24 | Target | No Excel/PDF export route, service, or UI. |
| FR25 | Partial | Audit read/detail for owner; invoice create, user, machine, and branch actions are logged. Payment, order update, stock adjustment, purchase, and design status are not. |
| FR26 | Target | No manual backup command/route, scheduled backup, retention, or restore drill. |

## Non-Functional Requirements

| NFR | Status | Evidence and remaining gap |
| :--- | :--- | :--- |
| NFR1 | Partial | Pagination exists; no benchmark on a 10k-product/50k-order dataset. |
| NFR2 | Partial | Owner reports exist; export is missing and report performance is unmeasured. |
| NFR3 | Partial | DP arithmetic and multiple-DP tests pass; six FRs remain Target. |
| NFR4 | Partial | Responsive layouts exist; no formal WCAG audit or usability test. |
| NFR5 | Partial | Policies, branch scope, and money redaction are tested; HTTPS and OWASP controls are not verified in this repository. |
| NFR6 | Partial | Compose and CI exist; backup/restore is FR26 and still missing. |
| NFR7 | Partial | API-first modules and SPA are in place; Catalog convention debt remains. |
| NFR8 | Partial | CI and Compose exist; Docker build was not executed in this VM; browser matrix is not automated. |
| NFR9 | Partial | Locking is implemented; SQLite makes `lockForUpdate()` a no-op and PostgreSQL concurrency is untested. |
| NFR10 | Implemented | Branch CRUD, branch scope, and branch tests exist; no formal five-minute benchmark. |

## Sprint Status

Mapping follows `docs/requirements/product-backlog.md`. Sprint numbers are backlog order, not the actual delivery order; the refactor delivered Sprint 0 and part of Sprint 3 early.

| Sprint | Backlog | FR | Status | Done | Missing |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Sprint 0 | Foundation, SPA shell, platform | FR1–FR5 plus shell/CI | Done | Auth, RBAC, branch scope, SPA shell, API, CI/CD, private channels | Docker/deploy validation |
| Sprint 1 | PB-01 | FR6, FR7 | Partial | Product read/search/filter/pagination, category, pricing | Product create/update/delete and admin UI |
| Sprint 1 | PB-02 | FR8, FR9, FR10 | Partial | Order create, customer, quote, cart, ticket | Order update before production, design status |
| Sprint 1 | PB-03 | FR11, FR12 | Done | DP, settlement, multiple DP, overpay guard | — |
| Sprint 1 | PB-04 | FR13, FR14 | Done | Receipt, operational expenses | — |
| Sprint 2 | PB-05 | FR15, FR17, FR18 | Partial | Stock read, restock, stock ledger | Purchase records, reason-required adjustment, low-stock alert |
| Sprint 2 | PB-06 | FR19, FR20, FR21 | Partial | Start/complete production, status log | All-role queue UI, deadline/priority sort, order-level pickup |
| Sprint 2 | PB-07 | FR16 | Partial | Automatic stock deduction, waste logging | Order-linked usage record, ledger type/order reference |
| Sprint 3 | PB-08 | FR1–FR5 | Done | Auth, user management, RBAC, branch scope, money redaction | HTTPS/OWASP formal verification |
| Sprint 3 | PB-09 | FR22, FR23, FR24 | Partial | Owner reports/dashboard, manager leaderboard | Manager dashboard/report completeness, Excel/PDF export |
| Sprint 3 | PB-10 | FR25, FR26 | Partial | Audit read/detail, partial audit writes | Payment/order/stock audit coverage, manual and scheduled backup |

Summary:

- Sprint 0: done, except deployment verification.
- Sprint 1: 2 of 4 backlog items done; PB-01 and PB-02 partial.
- Sprint 2: 0 of 3 done; all partial.
- Sprint 3: 1 of 3 done; PB-09 and PB-10 partial.

## What Is Left

Priority order:

1. **FR6** product create/update/delete and product admin UI.
2. **FR9** order update before production; **FR10** design status.
3. **FR15** purchase records; **FR18** stock adjustment with reason; **FR17** low-stock alert.
4. **FR19** deadline/priority sorting and all-role queue UI; **FR21** order-level pickup propagation.
5. **FR22/FR23** manager dashboard/report completeness; **FR24** Excel/PDF export.
6. **FR25** audit coverage for payments, order updates, and stock; **FR26** manual and scheduled backup.

## Refactor Completion

- Livewire and Blade application UI removed.
- API-first modules, Sanctum, React SPA, private Reverb channels, Docker Compose, CI, `init.sh`, and docs are in place.
- Remaining work is the FR sprints above, not the architecture.

## Convention Debt

- Some Catalog controllers still use inline `$request->validate()` and direct model writes (materials update, attributes, attribute types, pricing). Sales and Identity use FormRequests plus Actions. Extract Catalog FormRequests/Actions in the next cleanup.
- Catalog price/cost mutations are not fully written to `activity_logs`; FR25 remains partial.
- `ProductionController::summary` builds its aggregate query in the controller; extract a `ProductionSummaryQuery` when FR19/FR21 work starts.
- Existing audit descriptions and stock-ledger notes stay stored in English; new audit rows are Indonesian. Stock-ledger notes such as `Manual stock addition: +N` are persisted through the API but not displayed by the SPA, so they are left as-is.
- The SPA has no i18n layer by design; copy is hardcoded Indonesian and code, API, and database values stay English, with one exception: `expense.category` is free text, new rows default to `Operasional`, and existing rows may be `Office`/`Operational`, so reports group by exact string.
- `Item`, `Global`, and the printing loanwords `Finishing` and `File` are sanctioned in the design glossary and remain in English.
