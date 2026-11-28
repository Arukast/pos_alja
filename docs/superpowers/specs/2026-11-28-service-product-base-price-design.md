# Service product base price (Jasa Desain) — design

Date: 2026-11-28
Status: approved by partner

## Intent

Jasa Desain and similar service products have no bahan and no atribut, so the derived price resolves to `0.00`. `Product` has no price field today. A service product needs a catalog price and cost so it can be sold and reported with a real margin.

## Goals

- Add an optional base `price` and base `cost` to `products`, both decimal strings with two places, default `0.00`.
- Both fields are always visible in the product form and independent of each other. Neither requires the other to be filled.
- Both are additive: they add to, never replace, the derived bahan/atribut price and cost.
- Keep the authoritative server-side calculation: no client-supplied price.
- Keep diskon reseller and diskon quantity applying to the whole base price, unchanged.
- Keep money visibility: designer gets sell price only; cashier and production never see cost or profit.
- Global price/cost across branches — no per-branch table (partner confirmed "the same in every branch" for now).

## Non-goals

- No per-branch service pricing.
- No designer-time cost tracking beyond the product base cost field.
- No change to the `/quotes` response shape.
- No change to order snapshot columns — the existing six snapshots are sufficient.
- No negative-profit validation on the server.

## Approaches considered

1. **Base price + base cost on `products`** — chosen. Smallest complete change; fits the global-master convention; keeps the existing discount chain.
2. **Per-branch service price table** — rejected; not needed now.
3. **Attribute or synthetic material as the price carrier** — rejected; semantically wrong and risks stock movements on a service sale.
4. **Fixed price overriding derived price** — rejected; would allow pricing a material-based order below its cost and corrupt profit reporting.

## Decided design

### Data

- One new additive migration (never edit a merged migration) adds to `products`:
  - `price` decimal(12,2) NOT NULL DEFAULT `0.00`
  - `cost` decimal(12,2) NOT NULL DEFAULT `0.00`
- Must work on SQLite and PostgreSQL; money via `App\Support\Decimal`.
- `app/Models/Product.php`: add both to fillable, cast to decimal string, and include in `auditSnapshot()`.

### Pricing

`App\Services\QuotePricingService::calculate()` is the single authoritative formula. Two lines change:

```
base   = product.price + material.price (if any) + sum(attribute.price)
unitCost = product.cost + material.cost (if any) + sum(attribute.cost)
```

Then the existing chain is untouched: diskon reseller, `qty × areaFactor` for `m2` only, then highest qualifying quantity tier. `profit = subtotal − cost`. Cost is not discounted, but like material cost it is multiplied by qty and areaFactor.

Consumers already using this service (`/quotes`, `CreateInvoice`, `UpdateInvoiceItems`) need no changes to call sites or response shape.

### Validation

- `StoreProductRequest` and `UpdateProductRequest`: `sometimes|numeric|min:0` for both fields. Neither depends on the other.
- No `required_if`, no `cost <= price` check. Negative profit is allowed.

### Visibility

- `ProductResource`: expose `price` to roles allowed to see sell price; expose `cost` only to owner and manager. Designer gets sell price, never cost or profit. Cashier and production get no cost.
- Frontend `Product` type: `price: string`, `cost?: string`.

### Frontend

- `frontend/src/features/catalog/ProductsPage.tsx`: two plain always-visible fields, default `'0.00'`, no `hideWhen`. Labels: `Harga dasar (ditambahkan ke harga bahan & atribut)` and `Biaya dasar (ditambahkan ke biaya bahan & atribut)`.
- Add a non-blocking UI hint when base cost is greater than base price and cost is greater than zero: `Biaya dasar lebih besar dari harga dasar — cek kembali`. Use the existing `type: 'custom'` field `render` pattern from `ProductTiersField` so it reads live form values. It does not block save and does not imply a final profit warning.
- `frontend/src/features/catalog/ProductTiersField.tsx`: `productPayload()` sends both fields as strings.
- `frontend/src/api/types.ts`: add both fields to `Product`.
- No React Hook Form or Zod is used in this form; do not introduce them.

### Verification

- Keep existing `NoMaterialOrderApiTest` `0.00` assertions valid (both fields default to zero).
- Add tests: base price used when no material/attributes; base price added on top of material/attribute price; base cost added to cost; reseller and quantity discounts still apply to the combined base; missing fields default to `0.00`; FormRequest rejects negative and non-numeric values.
- Run `./init.sh`, backend tests, Pint, PHPStan, composer audit, and frontend typecheck/lint/test/build.

### Docs to update in the same change

`docs/requirements/fr.md` (FR6, FR8), `docs/requirements/nfr.md`, `docs/requirements/access-matrix.md`, `docs/architecture/erd.md` (products table, pricing notes), `docs/architecture/api-contracts.md` (product fields, quote formula), `docs/architecture/frontend-architecture.md`, `docs/requirements/implementation-gaps.md`, plus a revision-history row.

## What is left to the builder

Exact column names, field order in the form, test layout, and the Indonesian hint wording are as above. Per-branch pricing remains deferred debt.

## Builder check resolutions

The builder check found the spec buildable. These points were settled and are binding on the implementation:

- Edit `app/Modules/Catalog/Actions/CreateProduct.php` and `UpdateProduct.php` as well — they build explicit arrays and would otherwise silently drop `price` and `cost` posted by the form. Create uses the request value or `0.00`; update uses the `array_key_exists` pattern already used for `selling_unit`.
- Add `price: '0.00'` to the two existing `Product` test fixtures (`productOptions.test.ts` and `ProductsPage.readOnlyOptions.test.tsx`) so `price: string` does not break typecheck.
- Do not add Eloquent money casts — no model does this today. Follow `Material`/`Attribute`: format with `Decimal::money()` in `ProductResource`.
- Use `decimal(12,2)` as specified. Add no product-table money columns in the admin list view; the fields live in the modal only.
- Update the stale formula description in the `QuotePricingService` docblock (lines ~21 and ~29) alongside the two code lines.
- `/quotes/options` product objects do gain `price` through `ProductResource`; update that line in `docs/architecture/api-contracts.md` even though the POST `/quotes` response shape is unchanged.
- Existing seeded `Jasa Desain` products stay at `0.00` after deploy. The partner chose manual entry: the owner fills `Harga dasar` and `Biaya dasar` in the product form. Do not backfill from a seeder or migration.
