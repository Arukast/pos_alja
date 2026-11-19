# Non-Functional Requirements (Target Specification)

- **Source:** Capstone proposal NFR1–NFR10.
- **Targets:** The proposal left most measurable targets blank. The values below are baselines for the redevelopment; confirm them with the capstone supervisor and adjust before using them as acceptance thresholds.
- **Verification:** Each NFR states how it will be measured, not only what it requires.

## Summary

| ID | Characteristic | Target |
| :--- | :--- | :--- |
| NFR1 | Performance efficiency | Operational pages server p95 < 2 s; full page < 3 s; 20 rows per page |
| NFR2 | Performance efficiency | Reports p95 < 3 s; 10k-row export < 10 s |
| NFR3 | Functional suitability | 100% FR acceptance; DP variance 0 at 2 decimals |
| NFR4 | Interaction capability | WCAG 2.1 AA; 390/768/1440 px; 90% task completion in a 5-user test |
| NFR5 | Security | 100% role/branch checks; HTTPS; OWASP Top 10; validated input |
| NFR6 | Reliability and availability | 99.5% monthly uptime; RPO <= 24 h; RTO <= 4 h |
| NFR7 | Maintainability | Pint and Larastan pass; docs current; services isolated |
| NFR8 | Compatibility and portability | Latest 2 versions of major browsers; Docker; PHP 8.3; PostgreSQL 14+ |
| NFR9 | Data integrity | 50 concurrent payment/stock operations with zero lost updates |
| NFR10 | Functional scalability | New branch added through the UI in < 5 minutes, no code change |

---

## NFR1 Performance efficiency — operational pages

- **Requirement:** Product list, order entry, payment, and production pages SHALL load quickly enough for counter operations.
- **Target baseline:** p95 server response < 2 seconds and p95 full page load < 3 seconds on office broadband, at 10,000 products and 50,000 orders per branch.
- **Pagination:** At least 20 products per page; reports paginated or chunked.
- **Verification:** Load test with seeded data; Laravel query log; browser performance profile at 390/768/1440 px.
- **Related:** FR7, FR19, FR22, PB-01, PB-06, PB-09.

## NFR2 Performance efficiency — reports and export

- **Requirement:** Report queries and exports SHALL not block the cashier or owner workflow.
- **Target baseline:** Periodic report query p95 < 3 seconds; 10,000-row Excel export < 10 seconds; PDF export < 15 seconds.
- **Verification:** Timed export test with seeded data; queue worker for long exports if the target is exceeded.
- **Related:** FR23, FR24, PB-09.

## NFR3 Functional suitability — completeness and DP accuracy

- **Requirement:** All functional requirements SHALL be implemented, and down-payment values SHALL be read and calculated correctly.
- **Target baseline:** 100% of FR acceptance criteria pass; DP arithmetic variance is 0 at 2 decimal places; invoice reconciliation (`grand_total - payments = accounts_receivable`) holds for every test fixture.
- **Verification:** Acceptance test suite per FR; dedicated multiple-DP regression tests; reconciliation assertions.
- **Related:** FR8–FR12, PB-02, PB-03.

## NFR4 Interaction capability — usability and responsiveness

- **Requirement:** The interface SHALL be easy for print-shop staff to learn and usable on desktop and mobile.
- **Target baseline:** WCAG 2.1 AA for labels, focus states, contrast, and keyboard navigation; usable at 390, 768, and 1440 px; 90% of core tasks completed without assistance by 5 representative staff after one training session.
- **Core tasks:** Login, create order, register DP, settle payment, update production status, mark pickup.
- **Verification:** Moderated usability test; axe or Lighthouse accessibility scan; viewport screenshots.
- **Related:** FR13, FR17, FR19, FR22, PB-04, PB-06, PB-09.

## NFR5 Security — access control, data protection, validation

- **Requirement:** Access SHALL be controlled by role and branch; data SHALL be protected in transit; all input SHALL be validated.
- **Target baseline:** 100% of routes, services, and Livewire actions enforce role and branch scope; HTTPS in every non-local environment; OWASP Top 10 controls; no cross-branch data in tests; production users cannot see price, cost, or profit.
- **Verification:** Authorization feature tests per role and branch; dependency audit; CSRF and validation tests; security review of auth and input changes.
- **Related:** FR1–FR5, FR25, PB-08, PB-10.

## NFR6 Reliability and availability — uptime and recovery

- **Requirement:** The system SHALL remain available during operating hours and SHALL be recoverable after data loss.
- **Target baseline:** 99.5% monthly uptime; RPO <= 24 hours; RTO <= 4 hours; quarterly restore drill; backup success/failure visible to the owner.
- **Verification:** Uptime monitor; backup job log; timed restore drill in staging.
- **Related:** FR26, PB-10.

## NFR7 Maintainability — modular, documented code

- **Requirement:** The codebase SHALL remain modular, formatted, statically analyzable, and documented.
- **Target baseline:** Laravel Pint and Larastan pass in CI; business logic stays in `app/Services`; every changed feature updates `docs/`; critical service paths have feature or unit tests; no unreviewed duplicate pricing/inventory logic.
- **Verification:** CI workflow; `phpstan analyse`; test suite; documentation review per `AGENTS.md` §1.6.
- **Related:** All FRs, PB-01–PB-10.

## NFR8 Compatibility and portability — browsers and containers

- **Requirement:** The system SHALL run in common browsers and in the containerized deployment environment.
- **Target baseline:** Latest two versions of Chrome, Edge, Firefox, and Safari; Docker Compose deployment; PHP 8.3; PostgreSQL 14+ in production; SQLite in memory for tests.
- **Verification:** Browser smoke tests; `npm run build`; CI on PHP 8.3 with SQLite; staging on PostgreSQL.
- **Related:** NFR6, PB-08, PB-10.

## NFR9 Data integrity — concurrency and consistency

- **Requirement:** Data SHALL remain consistent when multiple users act at the same time.
- **Target baseline:** 50 concurrent payment and stock operations complete with zero lost updates, zero duplicate payments, and zero negative stock; every multi-table mutation runs in one transaction; stock rows are locked with `lockForUpdate()`.
- **Verification:** Concurrency test against PostgreSQL; transaction rollback tests; reconciliation assertions after each scenario.
- **Related:** FR8–FR12, FR15–FR18, FR25, PB-02, PB-03, PB-05, PB-07.

## NFR10 Functional scalability — new branches without code

- **Requirement:** A new branch SHALL be addable through the application without code changes.
- **Target baseline:** Owner creates a branch in < 5 minutes through the UI; branch-scoped records use `branch_id`; no hardcoded branch IDs or names; branch isolation tests pass for every new branch.
- **Verification:** Create a branch in staging, assign staff, create an order, verify dashboards and stock are scoped; grep for hardcoded branch IDs.
- **Related:** FR5, FR22, FR23, PB-08, PB-09.

---

## Verification Commands

| Check | Command |
| :--- | :--- |
| Test suite | `docker exec -it -w /var/www/html/pos_alja local-php83 env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test` |
| Style | `./vendor/bin/pint` |
| Static analysis | `./vendor/bin/phpstan analyse --memory-limit=1G` |
| Dependency audit | `composer audit` |
| Asset build | `npm run build` |
| Startup | `./init.sh` |
