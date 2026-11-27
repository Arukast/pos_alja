# Non-Functional Requirements

- **Source:** Capstone proposal NFR1–NFR10. Sprint Review 1 did not change the measurable targets; verification scope is noted below.
- **Status:** Target specification. Implementation progress is tracked in `docs/requirements/implementation-gaps.md`.
- **Verification:** Each item states how it is checked.

| ID | Characteristic | Target | Verification |
| :--- | :--- | :--- | :--- |
| NFR1 | Performance efficiency | API p95 < 2 s for operational lists; SPA first meaningful paint < 3 s on office broadband; 10–20 rows per page (10 products per page for product catalog) | Browser measurements and query logs |
| NFR2 | Performance efficiency | Reports p95 < 3 s at 10k invoices; export < 10 s (FR24 export implemented with OpenSpout and Dompdf; benchmark pending) | Staging dataset benchmark |
| NFR3 | Functional suitability | DP/settlement arithmetic reconciles to 2 decimals; unit-aware stock deduction and reversal reconcile to 2 decimals; no client-trusted prices | PHPUnit payment, reseller-discount, order-edit, order-history resolution, minimum-stock, and production fail/reopen tests |
| NFR4 | Interaction capability | WCAG 2.1 AA intent; 390/768/1440 px; keyboard-operable dialogs; dark mode | `agent-browser` checks and UI review, including invoice print and queue filters |
| NFR5 | Security | 100% role and branch checks; Sanctum cookie + CSRF; production money redaction; validated input; per-request account/branch enforcement; last active owner protection | Feature tests + `security-code-reviewer`; account-status, last-owner, expense-role, customer-PII, customer-institution validation, customer-role enforcement (guest/cashier/production denials), and invoice-snapshot stability checks |
| NFR6 | Reliability and availability | 99.5% monthly uptime; RPO <= 24 h; RTO <= 4 h. Backup automation is PB-10; RPO/RTO and uptime are not yet claimed | Provider/Compose checks; restore drill (not run) |
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
- NFR6 backup automation is implemented by PB-10: a queued `pg_dump` run with local and R2 destinations, a once-per-minute `backup:tick`, per-destination GFS retention, owner-editable schedules, download, pinning, and quota visibility. The restore procedure is documented in `docs/architecture/deployment.md` section 7. **Verification status: not verified.** No restore drill has been run, so RPO <= 24 h, RTO <= 4 h, and 99.5% uptime are not claimed. Blockers in this environment: no Docker CLI, no PostgreSQL 16 server, and no `pg_dump`/`psql` client; the automated suite runs on SQLite with a fake dumper and a faked R2 disk. A live R2 upload, the `change()` migration on PostgreSQL, the `postgresql-client-16` image, and nginx syntax are also unverified for the same reasons (no Docker runtime). UI smoke and 390/768/1440 screenshots are recorded in `.ai-docs/screens/pb10/`; dark mode remains unverified. Record drill owner, date, scratch database, dump file, and result here and in the session handoff after the first real drill.
- NFR9 locking is implemented with `lockForUpdate()` for payments, production, stock movements, and fail/reopen reversal inside the same transaction; SQLite makes it a no-op, so run the check against PostgreSQL before delivery. PostgreSQL concurrency remains unverified, including the fail/reopen path.
- Account enforcement is per request: `EnsureAccountIsActive` runs after `auth:sanctum` on every authenticated `/api/v1` route. An inactive user, or a non-owner whose assigned branch is inactive, trashed, or missing, gets `401` `Akun atau cabang Anda tidak aktif. Silakan hubungi pemilik.` after the web guard logs out, the session is invalidated, and the CSRF token is regenerated. Owners skip the branch check, and a non-owner with a null branch keeps the previous behavior. Open Reverb sockets are not revoked; the next authenticated `/api/v1` request logs the user out, and broadcasts carry no money data. `/broadcasting/auth` is outside the `active` middleware, so a deactivated session can still re-authorize a private channel until its next authenticated `/api/v1` request. The last active owner guard runs inside the caller transaction with `lockForUpdate()`; PostgreSQL concurrency for that lock is unverified for the same SQLite limitation as NFR9.
- Deployment precondition: before deploying, confirm at least one active, non-trashed owner exists; the migration does not restore soft-deleted users, so a pre-existing trashed sole owner would leave no active owner to operate the guard.
- Stock integrity is unit-aware: production start deducts `area * quantity` for `m2` and `quantity` for every other unit; fail reverses the net `(material_id, branch_id)` usage and writes `usage_reversal`; reopen clears the operator, and the next start deducts again. The waste KPI groups today's waste by material unit, so mixed units are never summed.
- NFR8 records PHP 8.5 as the tested runtime while `composer.json` keeps the `^8.3` compatibility floor.
- User-facing copy and API messages are Indonesian; dates format with `id-ID`; production requires `APP_LOCALE=id`.
- Sprint Review 1 changed FR6, FR8, FR9, FR13, FR14, FR16, FR18, FR19, FR20, and FR25. NFR targets are unchanged. NFR3, NFR4, NFR5, and NFR9 verification scope expanded to cover invoice print, expense access, customer PII, and order-update locking. Unit A removed the category quantity-discount tiers and attribute types, and added minimum-stock editing; NFR3 verification now covers order-history resolution and minimum-stock changes instead of quantity discounts.
- The 2026-11-27 customer-management change adds no NFR target. NFR5 verification now covers the six-label allow-list on both create and update, `institutions` precedence over legacy `institution`, owner/manager/designer write with cashier read-only and production/guest denial, and an invoice institution snapshot that does not change when the customer's labels are later edited. NFR4 smoke checks cover the shared `/customers` page and the search-first order picker at 390, 768, and 1440 px. The no-branch-designer/customer access gap stays recorded as debt in `docs/requirements/implementation-gaps.md`.
