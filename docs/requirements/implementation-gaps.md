# Implementation Gaps

- **As of:** 2026-11-21, after the API-first React refactor.
- **Scope:** The refactor replaced Livewire/Blade with Laravel API modules and a React SPA. It did not add the FRs that were still Target in the capstone plan.
- **Read this first:** A feature marked Implemented has API endpoints, policies, tests, and SPA screens. Partial means core behavior exists but part of the acceptance criteria is still missing.

## Functional Requirements

| FR | Status | Notes |
| :--- | :--- | :--- |
| FR1 | Implemented | Username login, remember flag, throttle, generic failure, dummy bcrypt equalization. |
| FR2 | Implemented | Owner and manager user management; manager branch/role limits; profile rules. |
| FR3 | Implemented | Login/logout, password change, confirm-password with session timestamp. |
| FR4 | Implemented | User create/update/soft delete/restore; manager branch staff only. |
| FR5 | Implemented | Policies and branch scope across API and SPA; production money redaction. |
| FR6 | Partial | Product read/search exists; product create/update/delete is still pending. |
| FR7 | Implemented | Product search, category filter, and pagination; consumed by designer options. |
| FR8 | Implemented | Designer customer intake, quote, cart, invoice, status log, audit. |
| FR9 | Target | Order update before production is not implemented. |
| FR10 | Target | Design status codes are not implemented. |
| FR11 | Implemented | Server-computed prices, DP, receivable, overpay guards. |
| FR12 | Implemented | DP, settlement, pickup queue, receipt print. |
| FR13 | Implemented | Receivable fields and owner report breakdowns. |
| FR14 | Implemented | Cashier expense create and branch ledger. |
| FR15 | Target | Raw-material purchases are not implemented. |
| FR16 | Implemented | Stock ledger writes on restock, production usage, and waste. |
| FR17 | Implemented | Branch stock reads and owner all-branch stock view. |
| FR18 | Target | Reason-required manual stock adjustment is not implemented. |
| FR19 | Partial | Queue, start/complete, machine assignment, and waste exist; deadline/priority sorting is not. |
| FR20 | Implemented | Start deducts stock once; complete sets status 2. |
| FR21 | Partial | Invoice pickup works; order-level pickup propagation is not implemented. |
| FR22 | Implemented | Owner finance cards, branch selector, period filters, sparkline. |
| FR23 | Implemented | Revenue, HPP, net profit, expenses, leaderboard reports. |
| FR24 | Target | Excel/PDF export is not implemented. |
| FR25 | Partial | Audit read/detail for owner; coverage of every mutation is not complete. |
| FR26 | Target | Scheduled/manual backup is not implemented. |

## Non-Functional Gaps

- NFR1/NFR2 performance targets need a representative dataset; the suite does not benchmark 10k invoices.
- NFR4 needs a formal WCAG audit and browser matrix beyond the 390/768/1440 smoke checks.
- NFR6 is partial: Compose and CI exist; backup automation is FR26.
- NFR9 locking is implemented but unverified on PostgreSQL; SQLite makes `lockForUpdate()` a no-op.
- Docker build/Compose was not executed in the development VM because Docker is not installed. Validate on the deployment host.

## Refactor Completion

- Livewire and Blade application UI removed.
- API-first modules, Sanctum, React SPA, private Reverb channels, Docker Compose, CI, `init.sh`, and docs are in place.
- Remaining work is the FR sprints above, not the architecture.

## Convention Debt

- Some Catalog controllers still use inline `$request->validate()` and direct model writes (materials update, attributes, attribute types, pricing). Sales and Identity use FormRequests plus Actions. Extract Catalog FormRequests/Actions in the next cleanup.
- Catalog price/cost mutations are not fully written to `activity_logs`; FR25 remains partial.
- `ProductionController::summary` builds its aggregate query in the controller; extract a `ProductionSummaryQuery` when FR19/FR21 work starts.
- Existing audit descriptions and stock-ledger notes stay stored in English; new audit rows are Indonesian. Stock-ledger notes such as `Manual stock addition: +N` are persisted through the API but not displayed by the SPA, so they are left as-is.
- The SPA has no i18n layer by design; copy is hardcoded Indonesian and code, API, and database values stay English.
- `Item`, `Global`, and the printing loanwords `Finishing` and `File` are sanctioned in the design glossary and remain in English.
