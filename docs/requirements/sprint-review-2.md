# Sprint Review 2 Change Record

- **Date:** 2026-11-26
- **Source:** Sprint Review 2 feedback.
- **Scope:** Catalog cleanup and product-to-material mapping (Stream A, PB-01/FR6), plus custom minimum-stock follow-up assigned to Stream C (PB-05/FR17, Sprint 3).
- **Status:** Stream A items and the custom minimum-stock item are implemented (Unit A catalog cleanup). Remaining work is tracked in `docs/requirements/implementation-gaps.md`.

## 1. Approved Items

- Raw-material purchase recording and automatic branch stock increments.
- Separation of purchase cost (recorded as operating expense) from HPP/cost pricing.
- Quantity discount tiers (`min_qty`, `discount_percent`) on Product Categories. Removed in Unit A: the tiers, table, API, and UI are gone; product-level tiers are deferred to Unit B.
- Basic Product CRUD (category selection, unit/area pricing type) with search and filtering.

## 2. Revision Decisions

| No | Feedback | Decision | Owner | Follow-up |
| :---: | :--- | :--- | :--- | :--- |
| 1 | Remove *Arch Type* (`calculation_archetype`) from Product Categories | Removed from Category UI/API; pricing and dimension formulas moved to `products.pricing_type` (`unit` / `area`) with backfill for legacy `surface_area` categories | Stream A | Implemented |
| 2 | Remove "Atribut yang Diizinkan" (`allowed_attribute_types`) from Product Categories | Removed from Category UI/API and Designer attribute filtering | Stream A | Implemented |
| 3 | Map compatible materials per Product and filter Designer material selection | Added global `material_product` pivot (backfilled from `pricing`), multi-select in `ProductsPage`, `material_ids` in `/products` and `/quotes/options`, and filtering in `DesignerNewOrderPage` | Stream A (catalog/API) + Stream B (Designer UI) | Implemented |
| 4 | Confirm removal of Print Machine menu and endpoints | Already removed in `staging` (`machines` and `machine_pricing` dropped) | Already done | Done |
| 5 | Custom minimum stock threshold per branch material | Implemented in Unit A: `PATCH /materials/{material}/min-stock` edits the existing branch row (Owner names the branch, Manager own branch), writes no stock-ledger row and no activity log | Stream C | Implemented (Unit A) |
| 6 | Category discount tiers and attribute types no longer match the target pricing model | Removed `product_category_discounts` and `attribute_types`; categories gain `description`/`is_active` with no hard delete; attributes get Manager own-branch CRUD with no delete; minimum stock is editable per branch; pricing delete is soft. Product-level tiers and the pricing redesign move to Unit B | Unit A | Implemented |
