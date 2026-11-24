# Sprint Review 1 Change Record

- **Date:** 2026-11-24
- **Source:** `Hasil Sprint Review 1.md` (problem owner review with Pak Alif).
- **Scope:** Requirements changed after Sprint Review 1. Implementation progress stays in `docs/requirements/implementation-gaps.md`.
- **Status:** Accepted for target documentation. Code changes are tracked separately per workstream.

## 1. Approved Items

The problem owner accepted the login and SPA visual direction, the five-role split, the owner dashboard branch/period filter, customer types `Reguler` and `Reseller`, multiple down payments, settlement, and cashier pickup authority.

## 2. Revision Decisions

| No | Problem Owner Feedback | Decision | FR | Target Sprint |
| :---: | :--- | :--- | :--- | :--- |
| 1 | Customer search is the default; create customer on a separate page | Adopted | FR8 | Sprint 2 |
| 2 | Order flow is Product -> Material -> Finishing -> Display; remove machine selection | Adopted | FR6, FR8 | Sprint 2 |
| 3 | Each material allows one finishing and one display option, not multi-select | Adopted | FR6, FR8 | Sprint 2 |
| 4 | Designer can edit an order that has a down payment but is not fully paid | Adopted; fully paid invoices are blocked | FR9 | Sprint 2 |
| 5 | Remove the machine module; machine cost moves into material cost | Adopted | FR6, FR8 | Sprint 2 |
| 6 | Materials are created first and mapped to a product category | Adopted | FR6 | Sprint 2 |
| 7 | Material types use three units: m2, sheet, pcs; meter materials use separate length and width inputs | Adopted | FR6, FR8 | Sprint 2 |
| 8 | Manual stock reduction is available in the material/stock screen for failed prints | Adopted as a reason-required adjustment; production waste stays inline in the queue | FR18 | Sprint 2 |
| 9 | Remove the price matrix; use automatic quantity discounts configured per product category | Adopted. Base price = material price + finishing/display price; category tiers apply the quantity discount | FR6 | Sprint 2 |
| 10 | Add customer address and institution; only name and institution are required | Adopted | FR8 | Sprint 2 |
| 11 | Production status is editable and the queue can be filtered | Adopted | FR19, FR20 | Sprint 2 |
| 12 | Failed print (waste) is recorded from the production queue row, not a separate page | Adopted | FR16 | Sprint 2 |
| 13 | Expenses move from Cashier to Owner and Branch Manager; Cashier records income only | Adopted | FR14 | Sprint 2 |
| 14 | Expense category is a dropdown based on printing accounting | Adopted with a fixed list | FR14 | Sprint 2 |
| 15 | Replace "Cetak tiket" with "Cetak Invoice" and fix the print page | Adopted. One invoice document; production never sees money | FR8, FR13 | Sprint 2 |

## 3. Pricing Decision

The price matrix is removed. The target calculation is:

```text
base price = material price + selected finishing price + selected display price
reseller adjustment = branch reseller discount, applied before quantity discount
quantity discount = category tier with the highest min_qty <= quantity
line subtotal = (base price after reseller adjustment) * quantity * area factor
line subtotal after discount = line subtotal * (1 - discount_percent / 100)
```

- `product_categories` owns the quantity discount tiers.
- `materials` owns the unit, base cost, and base price.
- `attributes` owns the finishing/display price and cost.
- `orders` stores product, material, unit price, area, quantity, discount percent, discount amount, cost, and profit.
- `order_attributes` stores the selected finishing/display option and its price/cost snapshot.
- The invoice carries the financial roll-up; the settlement discount on the invoice remains separate from the quantity discount.

## 4. Machine Removal Decision

The `machines`, `machine_pricing`, and `wasted_materials.machine_id` target structures are removed. Machine usage cost is folded into material cost. Historical orders keep price/cost snapshots, so removing the master data does not change past invoices.

The removal is a staged migration:

1. Hide machine screens and selectors.
2. Stop writing machine references.
3. Backfill or archive historical machine data.
4. Drop the machine tables and columns in a dedicated migration after a backup.

## 5. Invoice Decision

There is one invoice document. The separate work-order ticket is removed from the target. The invoice is printable as A4 for Owner, Branch Manager, Cashier, and Designer. Production uses the production queue and is denied the invoice and receipt because both contain money fields. No separate ticket or money-free print mode is part of the target.

## 6. Customer And Expense Lists

Institutions: `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`.

Expense categories: `Bahan Baku`, `Gaji`, `Sewa`, `Listrik & Air`, `Maintenance`, `Transport`, `Marketing`, `Operasional`, `Lain-lain`.

## 7. Backlog Change

PB-08 (Authentication and Access Management) moves from Sprint 3 to Sprint 1. Sprint 1 now contains PB-08, PB-01, PB-02, PB-03, and PB-04. Sprint 2 contains PB-05, PB-06, and PB-07. Sprint 3 contains PB-09 and PB-10.

This matches the Jira sprint board and keeps the foundation work in the first delivery sprint.

## 8. Impacted Documents

- `docs/requirements/fr.md`
- `docs/requirements/nfr.md`
- `docs/requirements/access-matrix.md`
- `docs/requirements/product-backlog.md`
- `docs/requirements/implementation-gaps.md`
- `docs/architecture/erd.md`
- `docs/architecture/api-contracts.md`
- `docs/architecture/sequence-diagrams.md`
- `docs/architecture/frontend-architecture.md`
- `docs/architecture/use-case-diagram.md`
- `docs/architecture/design-system.md`
- `docs/planning/distribusi-kerja-3-orang.md`
- `docs/planning/jira/`

## 9. Deferred And Open Items

1. Fully paid invoices are not editable. A refund or credit-note flow is out of scope for FR9.
2. The machine table drop needs a data backup and a dedicated migration; do not drop it in the same change as the UI removal.
3. The price matrix removal needs a data migration for existing orders and pricing snapshots.
4. Backup and restore remain FR26 target work; Sprint Review 1 does not change the RPO/RTO targets.
