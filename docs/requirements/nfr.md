# Non-Functional Requirements

- **Source:** Capstone proposal NFR1–NFR10. Sprint Review 1 did not change the measurable targets; verification scope is noted below.
- **Status:** Target specification. Implementation progress is tracked in `docs/requirements/implementation-gaps.md`.
- **Verification:** Each item states how it is checked.

| ID | Characteristic | Target | Verification |
| :--- | :--- | :--- | :--- |
| NFR1 | Performance efficiency | API p95 < 2 s for operational lists; SPA first meaningful paint < 3 s on office broadband; 10–20 rows per page (10 products per page for product catalog) | Browser measurements and query logs |
| NFR2 | Performance efficiency | Reports p95 < 3 s at 10k invoices; export < 10 s (export is FR24, target) | Staging dataset benchmark |
| NFR3 | Functional suitability | DP/settlement arithmetic reconciles to 2 decimals; unit-aware stock deduction and reversal reconcile to 2 decimals; no client-trusted prices | PHPUnit payment, reseller-discount, order-edit, order-history resolution, minimum-stock, and production fail/reopen tests |
| NFR4 | Interaction capability | WCAG 2.1 AA intent; 390/768/1440 px; keyboard-operable dialogs; dark mode | `agent-browser` checks and UI review, including invoice print and queue filters |
| NFR5 | Security | 100% role and branch checks; Sanctum cookie + CSRF; production money redaction; validated input | Feature tests + `security-code-reviewer`; expense-role and customer-PII checks |
| NFR6 | Reliability and availability | 99.5% monthly uptime; RPO <= 24 h; RTO <= 4 h. Backup automation is FR26 and remains target | Provider/Compose checks; restore drill |
| NFR7 | Maintainability | Pint and PHPStan clean; modular API; docs current; TypeScript strict | `./init.sh` |
| NFR8 | Compatibility and portability | Latest two versions of major browsers; Docker; PHP 8.5 tested with `^8.3` floor; PostgreSQL 16 | CI and Compose |
| NFR9 | Data integrity | 50 concurrent payment/stock operations with zero lost updates. SQLite cannot prove row locking; final check on PostgreSQL | Staging concurrency test, including invoice update with down payment, unit-aware usage, and fail/reopen reversal |
| NFR10 | Functional scalability | New branch created through the SPA in < 5 minutes, no code change | Browser walkthrough |

## Verification Commands

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=1G
composer audit
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build
```

## Notes

- NFR1/NFR2 require a representative dataset; the current test suite does not benchmark production-scale rows.
- NFR6 backup is not implemented by this refactor. Do not claim RPO/RTO coverage until FR26 lands.
- NFR9 locking is implemented with `lockForUpdate()` for payments, production, stock movements, and fail/reopen reversal inside the same transaction; SQLite makes it a no-op, so run the check against PostgreSQL before delivery. PostgreSQL concurrency remains unverified, including the fail/reopen path.
- Stock integrity is unit-aware: production start deducts `area * quantity` for `m2` and `quantity` for every other unit; fail reverses the net `(material_id, branch_id)` usage and writes `usage_reversal`; reopen clears the operator, and the next start deducts again. The waste KPI groups today's waste by material unit, so mixed units are never summed.
- NFR8 records PHP 8.5 as the tested runtime while `composer.json` keeps the `^8.3` compatibility floor.
- User-facing copy and API messages are Indonesian; dates format with `id-ID`; production requires `APP_LOCALE=id`.
- Sprint Review 1 changed FR6, FR8, FR9, FR13, FR14, FR16, FR18, FR19, FR20, and FR25. NFR targets are unchanged. NFR3, NFR4, NFR5, and NFR9 verification scope expanded to cover invoice print, expense access, customer PII, and order-update locking. Unit A removed the category quantity-discount tiers and attribute types, and added minimum-stock editing; NFR3 verification now covers order-history resolution and minimum-stock changes instead of quantity discounts.
