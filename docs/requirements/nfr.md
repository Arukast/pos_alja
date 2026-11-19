# Non-Functional Requirements (Target Specification)

- **Source:** Capstone proposal NFR1–NFR10.
- **Targets:** The proposal left most measurable targets blank. The values below are proposed baselines for the redevelopment; confirm them with the capstone supervisor and adjust before using them as acceptance thresholds.
- **Status legend:** Implemented / Partial / Target.
- **Verification:** Each NFR states how it will be measured, not only what it requires.

## Summary

| ID | Characteristic | Target | Current status |
| :--- | :--- | :--- | :--- |
| NFR1 | Performance efficiency | Operational pages server p95 < 2 s; full page < 3 s; 20 rows per page | Partial |
| NFR2 | Performance efficiency | Reports p95 < 3 s; 10k-row export < 10 s | Target |
| NFR3 | Functional suitability | 100% FR acceptance; DP variance 0 at 2 decimals | Partial |
| NFR4 | Interaction capability | WCAG 2.1 AA; 390/768/1440 px; 90% task completion in a 5-user test | Partial |
| NFR5 | Security | 100% role/branch checks; HTTPS; OWASP Top 10; validated input | Partial |
| NFR6 | Reliability and availability | 99.5% monthly uptime; RPO <= 24 h; RTO <= 4 h | Target |
| NFR7 | Maintainability | Pint and Larastan pass; docs current; services isolated | Partial |
| NFR8 | Compatibility and portability | Latest 2 versions of major browsers; Docker; PHP 8.3; PostgreSQL 14+ | Partial |
| NFR9 | Data integrity | 50 concurrent payment/stock operations with zero lost updates | Partial |
| NFR10 | Functional scalability | New branch added through the UI in < 5 minutes, no code change | Partial |

---

## NFR1 Performance efficiency — operational pages

- **Requirement:** Product list, order entry, payment, and production pages SHALL load quickly enough for counter operations.
- **Proposed target:** p95 server response < 2 seconds and p95 full page load < 3 seconds on office broadband, at 10,000 products and 50,000 orders per branch.
- **Pagination:** At least 20 products per page; reports paginated or chunked.
- **Verification:** Load test with seeded data; Laravel query log; browser performance profile at 390/768/1440 px.
- **Current status:** Partial. No benchmark exists; reporting columns lack explicit indexes; product pagination is not implemented.
- **Related:** FR7, FR19, FR22, PB-01, PB-06, PB-09.

## NFR2 Performance efficiency — reports and export

- **Requirement:** Report queries and exports SHALL not block the cashier or owner workflow.
- **Proposed target:** Periodic report query p95 < 3 seconds; 10,000-row Excel export < 10 seconds; PDF export < 15 seconds.
- **Verification:** Timed export test with seeded data; queue worker for long exports if the target is exceeded.
- **Current status:** Target. Export does not exist; report queries are unindexed and some are unbounded.
- **Related:** FR23, FR24, PB-09.

## NFR3 Functional suitability — completeness and DP accuracy

- **Requirement:** All functional requirements SHALL be implemented, and down-payment values SHALL be read and calculated correctly.
- **Proposed target:** 100% of FR acceptance criteria pass; DP arithmetic variance is 0 at 2 decimal places; invoice reconciliation (`grand_total - payments = accounts_receivable`) holds for every test fixture.
- **Verification:** Acceptance test suite per FR; dedicated multiple-DP regression tests; reconciliation assertions.
- **Current status:** Partial. Core DP tests exist; multiple-DP and full-DP edge cases are not covered; several FRs are target-only.
- **Related:** FR8–FR12, PB-02, PB-03.

## NFR4 Interaction capability — usability and responsiveness

- **Requirement:** The interface SHALL be easy for print-shop staff to learn and usable on desktop and mobile.
- **Proposed target:** WCAG 2.1 AA for labels, focus states, contrast, and keyboard navigation; usable at 390, 768, and 1440 px; 90% of core tasks completed without assistance by 5 representative staff after one training session.
- **Core tasks:** Login, create order, register DP, settle payment, update production status, mark pickup.
- **Verification:** Moderated usability test; axe or Lighthouse accessibility scan; viewport screenshots.
- **Current status:** Partial. Dark mode and responsive layouts exist; no WCAG audit or usability test has been recorded.
- **Related:** FR13, FR17, FR19, FR22, PB-04, PB-06, PB-09.

## NFR5 Security — access control, data protection, validation

- **Requirement:** Access SHALL be controlled by role and branch; data SHALL be protected in transit; all input SHALL be validated.
- **Proposed target:** 100% of routes, services, and Livewire actions enforce role and branch scope; HTTPS in every non-local environment; OWASP Top 10 controls; no cross-branch data in tests; production users cannot see price, cost, or profit.
- **Verification:** Authorization feature tests per role and branch; dependency audit; CSRF and validation tests; security review of auth and input changes.
- **Current status:** Partial. Known gaps: client-writable `UserManagement::$mode` and `MaterialManagement::$branchId`, cashier invoice selection without a branch assertion, unused `RoleMiddleware`, and dashboard components without mount guards.
- **Related:** FR1–FR5, FR25, PB-08, PB-10.

## NFR6 Reliability and availability — uptime and recovery

- **Requirement:** The system SHALL remain available during operating hours and SHALL be recoverable after data loss.
- **Proposed target:** 99.5% monthly uptime; RPO <= 24 hours; RTO <= 4 hours; quarterly restore drill; backup success/failure visible to the owner.
- **Verification:** Uptime monitor; backup job log; timed restore drill in staging.
- **Current status:** Target. No backup, restore, or uptime monitoring exists.
- **Related:** FR26, PB-10.

## NFR7 Maintainability — modular, documented code

- **Requirement:** The codebase SHALL remain modular, formatted, statically analyzable, and documented.
- **Proposed target:** Laravel Pint and Larastan pass in CI; business logic stays in `app/Services`; every changed feature updates `docs/`; critical service paths have feature or unit tests; no unreviewed duplicate pricing/inventory logic.
- **Verification:** CI workflow; `phpstan analyse`; test suite; documentation review per `AGENTS.md` §1.6.
- **Current status:** Partial. Pint and Larastan run in CI; `strict_types` covers 36 of 80 files; `UserManagement` does not use a form object; service coverage is uneven.
- **Related:** All FRs, PB-01–PB-10.

## NFR8 Compatibility and portability — browsers and containers

- **Requirement:** The system SHALL run in common browsers and in the containerized deployment environment.
- **Proposed target:** Latest two versions of Chrome, Edge, Firefox, and Safari; Docker Compose deployment; PHP 8.3; PostgreSQL 14+ in production; SQLite in memory for tests.
- **Verification:** Browser smoke tests; `npm run build`; CI on PHP 8.3 with SQLite; staging on PostgreSQL.
- **Current status:** Partial. CI covers PHP 8.3 and SQLite; the repository has no compose file; PostgreSQL is a deployment prerequisite from the README; browser testing is not automated.
- **Related:** NFR6, PB-08, PB-10.

## NFR9 Data integrity — concurrency and consistency

- **Requirement:** Data SHALL remain consistent when multiple users act at the same time.
- **Proposed target:** 50 concurrent payment and stock operations complete with zero lost updates, zero duplicate payments, and zero negative stock; every multi-table mutation runs in one transaction; stock rows are locked with `lockForUpdate()`.
- **Verification:** Concurrency test against PostgreSQL; transaction rollback tests; reconciliation assertions after each scenario.
- **Current status:** Partial. Core services use transactions and row locks; stock can go negative; material create/restore are not fully atomic; invoice numbering uses `count() + 1` without a lock.
- **Related:** FR8–FR12, FR15–FR18, FR25, PB-02, PB-03, PB-05, PB-07.

## NFR10 Functional scalability — new branches without code

- **Requirement:** A new branch SHALL be addable through the application without code changes.
- **Proposed target:** Owner creates a branch in < 5 minutes through the UI; branch-scoped records use `branch_id`; no hardcoded branch IDs or names; branch isolation tests pass for every new branch.
- **Verification:** Create a branch in staging, assign staff, create an order, verify dashboards and stock are scoped; grep for hardcoded branch IDs.
- **Current status:** Partial. Branch management exists and most tables carry `branch_id`; some queries fall back to `Branch::value('id')` when a user has no branch, and branch authorization is uneven.
- **Related:** FR5, FR22, FR23, PB-08, PB-09.

---

## Current NFR Gaps

| Gap | Affected NFR |
| :--- | :--- |
| No performance benchmark or query budget | NFR1, NFR2 |
| No export implementation | NFR2 |
| No dedicated multiple-DP and full-DP tests | NFR3 |
| No WCAG or usability test | NFR4 |
| Role and branch checks are uneven; two client-writable scope fields | NFR5 |
| No backup, restore drill, or uptime monitor | NFR6 |
| `strict_types` and test coverage are partial | NFR7 |
| No compose file or automated browser matrix | NFR8 |
| Stock can go negative; some multi-table writes are not atomic | NFR9 |
| Branch fallbacks and hardcoded assumptions remain | NFR10 |

## Verification Commands

| Check | Command |
| :--- | :--- |
| Test suite | `docker exec -it -w /var/www/html/pos_alja local-php83 env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test` |
| Style | `./vendor/bin/pint` |
| Static analysis | `./vendor/bin/phpstan analyse --memory-limit=1G` |
| Dependency audit | `composer audit` |
| Asset build | `npm run build` |
| Startup | `./init.sh` |
