# Implementation Gaps

- **Purpose:** Synchronize the target proposal with the current codebase.
- **This document is not part of the capstone proposal.** The proposal and the requirement docs are target-only; this file is the progress tracker.
- **Status labels:** Implemented / Partial / Target.
  - **Implemented** — exists and is demoable.
  - **Partial** — some behavior exists, but not the full requirement.
  - **Target** — proposal requires it, code has nothing.

## 1. Decisions That Override the FR Table

These were already decided and are followed here:

| Area | Decision | Result |
| :--- | :--- | :--- |
| Kasir stock | No stock access | Kasir `Data Stok = -`; purchases and stock visibility are Owner/Branch Manager, with Production read. No conflict with the code. |
| Kasir transaction update | `U` = pickup only | Kasir cannot update order items; order changes are Designer-only. No conflict with the code. |
| Manager stock | CRUD for the manager's branch | Target; global material deletion stays Owner-only. |
| Manager financial reports | `R` limited | Target; full P&L remains Owner-only. |
| Designer order changes | Allowed before production | Target; not implemented. |
| Production stock | `RU` | Target; read plus usage/waste update. |

## 2. Direct Conflicts

These are areas where the target and the as-built cannot both be true.

| # | Area | Target | As-built | Type |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Designer order update and design status (FR9, FR10) | Designer can update items before production and set design status | Create and read only | Under-grant |
| 2 | Production queue visibility (FR19) | All roles read the queue | Production only; Designer has an order list only | Under-grant |
| 3 | Manager transaction view | Read-only transaction list | Leaderboard and recent expenses only | Under-grant |
| 4 | Manager stock scope (FR6, FR18) | Branch CRUD; global delete Owner-only | Manager can delete a global material | Over-permit |
| 5 | Manager account scope (FR2, FR4, FR5) | Branch staff only | `UserManagement::$mode` allows owner mode | Over-permit |
| 6 | Cashier branch scope (FR5, NFR5) | Invoice must match cashier branch | No assertion in `selectInvoice()` / `processCheckout()` | Over-permit |
| 7 | Stock integrity (NFR9, FR18) | Stock cannot go negative | `current_stock` can go negative | Over-permit |
| 8 | DP validation (FR11, FR12) | Cap at remaining receivable | Cap at `grand_total`; overpayment possible | Rule conflict |

## 3. FR Status

| Status | FRs |
| :--- | :--- |
| Implemented | FR1, FR3, FR8, FR13, FR14, FR20 |
| Partial | FR2, FR4, FR5, FR6, FR11, FR12, FR16, FR17, FR19, FR21, FR22, FR23, FR25 |
| Target | FR7, FR9, FR10, FR15, FR18, FR24, FR26 |

Partial details:

| FR | Gap |
| :--- | :--- |
| FR2, FR4 | Manager restriction exists but `mode` is client-writable. |
| FR5 | Branch/role checks are uneven; client-writable scope fields; no mount guard. |
| FR6 | Categories and pricing exist; no product CRUD; Owner has no pricing tab. |
| FR11 | DP cap is `grand_total`, not remaining receivable. |
| FR12 | Multiple DP works, but overpayment is possible and untested. |
| FR16 | Stock deduction works; no order-linked usage record; waste tab fails. |
| FR17 | Stock view works for Owner/Manager; no low-stock alert; Production read is partial. |
| FR19 | Production-only queue; sorted by `created_at`; no priority. |
| FR21 | Invoice pickup updated; related orders are not. |
| FR22 | Owner finance cards exist; Manager summary/order count/production status missing. |
| FR23 | Owner reports full; Manager limited to leaderboard and expenses. |
| FR25 | Audit covers branch/user/machine/customer/invoice create; not payments, order updates, or stock. |

Target details: FR7 product search/pagination, FR9 order update, FR10 design status, FR15 purchase records, FR18 stock adjustment, FR24 export, FR26 backup.

## 4. NFR Status

| NFR | Status | Gap |
| :--- | :--- | :--- |
| NFR1 | Partial | No benchmark, indexes, or product pagination. |
| NFR2 | Target | No export; report queries unindexed. |
| NFR3 | Partial | Core DP tests exist; no multiple-DP/full-DP coverage. |
| NFR4 | Partial | Responsive UI exists; no WCAG or usability test. |
| NFR5 | Partial | RBAC and branch-scope gaps; HTTPS/deployment not verified. |
| NFR6 | Target | No backup, restore drill, or uptime monitoring. |
| NFR7 | Partial | Pint/Larastan run; `strict_types` and test coverage are partial. |
| NFR8 | Partial | PHP/SQLite CI exists; no compose file or browser matrix. |
| NFR9 | Partial | Transactions/locks exist; negative stock and non-atomic material writes. |
| NFR10 | Partial | Branch UI exists; fallback to first branch and uneven scoping remain. |

## 5. Product Backlog Status

| Backlog | Status | Blocking FRs |
| :--- | :--- | :--- |
| PB-01 | Partial | FR6 partial, FR7 target |
| PB-02 | Partial | FR8 implemented; FR9/FR10 target (Designer only) |
| PB-03 | Partial | FR11/FR12 partial |
| PB-04 | Implemented | FR13/FR14 implemented |
| PB-05 | Partial | FR15 target, FR17 partial, FR18 target (Owner/Manager/Production) |
| PB-06 | Partial | FR19 partial, FR20 implemented, FR21 partial |
| PB-07 | Partial | FR16 partial |
| PB-08 | Partial | FR1/FR3 implemented; FR2/FR4/FR5 partial |
| PB-09 | Partial | FR22/FR23 partial, FR24 target |
| PB-10 | Partial | FR25 partial, FR26 target |

Only **PB-04** can be marked done. The other nine are partial.

## 6. Root Cause Status

| Root cause | Status | Blocker |
| :--- | :--- | :--- |
| Owner monitoring across branches | Partial | FR22/FR23 partial |
| Cashier service and DP bugs | Partial | FR11/FR12 partial; NFR1 partial |
| Unclear production priority | Partial | FR19 partial |
| Local-only, isolated environment | Not addressed | NFR5 partial, NFR6 target, NFR8 partial |
| No stock metrics | Partial | FR15/FR18 target, FR16/FR17 partial |
| Slow reports and export | Not addressed | FR7/FR24 target; NFR1/NFR2 |
| Manual reconciliation | Partial | FR22/FR23 partial |
| No production tracking SOP | Partial | FR10 target, FR25 partial |
| No backup, single point of failure | Not addressed | FR26/NFR6 target |
| Unstructured DP values | Partial | FR9 target, FR11/FR12 partial |

Root causes **4, 6, and 9 are not addressed** yet. The other seven are partial.

## 7. Synchronization Rule

1. Proposal and `docs/` describe the target; do not add progress columns there.
2. This file is the only place for Implemented / Partial / Target.
3. A backlog item is done only when all its FRs are implemented and verified.
4. A root cause is addressed only when its FRs and NFRs are implemented and measured.
5. When code changes, update this file and the relevant target requirement, not the other way around.
