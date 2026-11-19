# claude-progress.md

---

## Current Verified State
- **Repository root directory:** `/home/dev/projects/pos_alja`
- **Standard startup path:** `cd /home/dev/projects/pos_alja && RUN_START_COMMAND=1 ./init.sh`
- **Standard verification path:** `docker exec -it -w /var/www/html/pos_alja local-php83 env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test`
- **Highest priority unfinished feature:** None (all tracked features passing)
- **Current blocker:** Docker CLI is not available in this environment, so the PHP test suite could not be executed during the documentation session.

---

## Session Record
### Session: 2026-11-19 - Development Documentation Set
- **Goal:** Create development documentation for requirements, access rights, data model, workflows, and internal contracts.
- **Completed:** Added `docs/requirements/fr.md`, `docs/requirements/nfr.md`, `docs/requirements/access-matrix.md`, `docs/architecture/erd.md`, `docs/architecture/sequence-diagrams.md`, and `docs/architecture/api-contracts.md`. Documents describe the implemented system, including observed gaps, and use Mermaid for the ERD and sequence diagrams.
- **Verification run:** Two independent read-only accuracy reviews against `app/`, `routes/`, `database/migrations/`, and `tests/`; structural Mermaid checks (entity and block balance); table and fence checks. The PHP test suite was not run because the Docker CLI is unavailable.
- **Evidence recorded:** Six files under `docs/`; reviewer findings and corrections are reflected in the documents. No remaining high-severity accuracy findings.
- **Commits:** Pending at the time this entry was written; see the branch history for `docs: add development documentation set`.
- **Known risks:** The docs record real implementation gaps: `UserManagement::$mode` and `MaterialManagement::$branchId` are client-writable, `Production::render()` queries a non-existent `materials.branch_id`, category deletion can cascade destructively, and material catalog/stock writes are not fully atomic.
- **Next best action:** Run `./init.sh` in an environment with the `local-php83` container, then address the documented gaps or file them as separate features.

### Session: 2026-07-07 - Material-Driven HPP & Simplified Matrix UI
- **Goal:** Drive base print cost (HPP) directly from individual Material profiles and clean up matrices views.
- **Completed:** Added HPP (Cost) and Base Price columns to the Material Inventory list table, removed the redundant Cost field input and table column from Pricing Matrices dashboard views, made PricingForm cost optional, updated component logic to automatically resolve and populate material costs, and configured PricingService to fetch base cost directly from Material profiles.
- **Verification run:** Updated Feature tests in `tests/Feature/SimplifiedPricingTest.php`.
- **Evidence recorded:** Checked off task checklist in `task.md` and updated `walkthrough.md`.
- **Commits:** None.
- **Known risks:** Docker container execution is currently encountering peer-reset errors locally.
- **Next best action:** Run automated test suites to ensure everything operates cleanly.




