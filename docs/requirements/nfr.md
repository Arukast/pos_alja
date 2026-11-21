# Non-Functional Requirements

- **Source:** Capstone proposal NFR1–NFR10.
- **Status:** As-built after the API-first refactor. Targets marked TBD still need capstone supervisor sign-off.
- **Verification:** Each item states how it is checked.

| ID | Characteristic | Target | Verification |
| :--- | :--- | :--- | :--- |
| NFR1 | Performance efficiency | API p95 < 2 s for operational lists; SPA first meaningful paint < 3 s on office broadband; 15–20 rows per page | Browser measurements and query logs |
| NFR2 | Performance efficiency | Reports p95 < 3 s at 10k invoices; export < 10 s (export is FR24, target) | Staging dataset benchmark |
| NFR3 | Functional suitability | DP/settlement arithmetic reconciles to 2 decimals; no client-trusted prices | PHPUnit payment and invoice tests |
| NFR4 | Interaction capability | WCAG 2.1 AA intent; 390/768/1440 px; keyboard-operable dialogs; dark mode | `agent-browser` checks and UI review |
| NFR5 | Security | 100% role and branch checks; Sanctum cookie + CSRF; production money redaction; validated input | Feature tests + `security-code-reviewer` |
| NFR6 | Reliability and availability | 99.5% monthly uptime; RPO <= 24 h; RTO <= 4 h. Backup automation is FR26 and remains target | Provider/Compose checks; restore drill |
| NFR7 | Maintainability | Pint and PHPStan clean; modular API; docs current; TypeScript strict | `./init.sh` |
| NFR8 | Compatibility and portability | Latest two versions of major browsers; Docker; PHP 8.5 tested with `^8.3` floor; PostgreSQL 16 | CI and Compose |
| NFR9 | Data integrity | 50 concurrent payment/stock operations with zero lost updates. SQLite cannot prove row locking; final check on PostgreSQL | Staging concurrency test |
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
- NFR9 locking is implemented with `lockForUpdate()` in payments and production; SQLite makes it a no-op, so run the check against PostgreSQL before delivery.
- NFR8 records PHP 8.5 as the tested runtime while `composer.json` keeps the `^8.3` compatibility floor.
