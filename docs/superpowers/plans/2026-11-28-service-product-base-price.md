# Service Product Base Price Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add optional base price and base cost to products so service products such as Jasa Desain can be sold and reported with a real margin.

**Architecture:** Two additive decimal columns on `products` feed the existing single authoritative calculation in `QuotePricingService`. The established reseller/quantity/area chain is untouched. Money visibility stays in `ProductResource`; the admin form uses the hand-rolled `CrudPage` field pattern, not React Hook Form or Zod.

**Tech Stack:** Laravel 13, PHP 8.5, SQLite in tests / PostgreSQL in production, Sanctum, React 19, TypeScript 7, Vite, Tailwind 4, TanStack Query, hand-rolled `CrudPage`.

**Spec:** `docs/superpowers/specs/2026-11-28-service-product-base-price-design.md`

## Global Constraints

- New additive migration only; never edit a merged migration.
- Columns: `price` and `cost`, `decimal(12,2)`, `NOT NULL DEFAULT 0.00`, portable to SQLite and PostgreSQL.
- No Eloquent money cast. Format with `Decimal::money()` in the Resource.
- Validation: `sometimes|numeric|min:0` for both fields in both FormRequests. No `required_if`, no `cost <= price` check.
- Both fields are independent and additive: `price` adds to bahan/atribut price; `cost` adds to bahan/atribut cost. Never override.
- Cost is not discounted; it is multiplied by `qty × areaFactor` like material cost.
- Do not change the POST `/quotes` response shape or add order snapshot columns.
- No per-branch pricing table. Existing seeded Jasa Desain stays `0.00` until an owner fills the form; no backfill.
- Money visibility: `price` to owner, manager, cashier, designer; `cost` to owner and manager only; production never sees products.
- Frontend: no React Hook Form, no Zod, no new dependency. Indonesian UI labels, English code, four-space indent.
- Conventional commits. Stage explicit file paths only; never `git add -A` or `git add .`. Signed commits; never `--no-gpg-sign`. No AI attribution.

## Review Focus

- A product with no material and no attributes and no configured price must still quote `0.00` — existing `NoMaterialOrderApiTest` assertions stay valid (Task 2).
- Base price/cost must be added to, not replace, material/attribute price/cost; a filled base cost on a material product must not corrupt `profit` (Task 2).
- Reseller and quantity discounts must apply to the combined base in the existing order, with cost undiscounted (Task 2).
- `cost` must not leak to designer, cashier, or production in any product response, including `/quotes/options` (Task 3).
- Product create/update must persist both fields; the explicit action arrays otherwise silently drop them (Task 1).

---

### Task 1: Persist base price and cost on products

**Files:**
- Create: `database/migrations/2026_11_28_000000_add_price_and_cost_to_products_table.php`
- Modify: `app/Models/Product.php:20-36`, `app/Models/Product.php:93-109`
- Modify: `app/Modules/Catalog/Http/Requests/StoreProductRequest.php:22-41`
- Modify: `app/Modules/Catalog/Http/Requests/UpdateProductRequest.php:26-59`
- Modify: `app/Modules/Catalog/Actions/CreateProduct.php:33-40`
- Modify: `app/Modules/Catalog/Actions/UpdateProduct.php:35-50`
- Test: `tests/Feature/Api/CatalogWriteApiTest.php`

**Interfaces:**
- Consumes: existing product create/update payload (`name`, `product_category_id`, `selling_unit`, flags, ids, `tiers`).
- Produces: `Product::$price` and `Product::$cost` as decimal strings, persisted by `CreateProduct::execute(array $data, User $actor): Product` and `UpdateProduct::execute(Product $product, array $data, User $actor): Product`.

- [ ] **Step 1: Write the failing persistence test**

In `CatalogWriteApiTest.php`, add a test where an owner creates a product with `price: '150000.00'` and `cost: '60000.00'` and assert `assertDatabaseHas('products', [...])` contains both values; then updates the same product to `175000.00` / `70000.00` and assert both changed. Add a second test posting `price: -1` and `cost: 'abc'` separately and assert `422` with those field keys in the errors.

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=CatalogWriteApiTest`
Expected: FAIL — columns do not exist and the fields are dropped.

- [ ] **Step 3: Implement persistence**

Create the migration with `$table->decimal('price', 12, 2)->default(0)` and `$table->decimal('cost', 12, 2)->default(0)`; `down()` drops both. Add both to `Product` fillable and to `auditSnapshot()`. In both FormRequests add `'price' => ['sometimes', 'numeric', 'min:0']` and `'cost' => ['sometimes', 'numeric', 'min:0']`. In both actions add `price` and `cost` to the `Product::create([...])` / `$product->update([...])` arrays using `$data['price'] ?? '0.00'` and `$data['cost'] ?? '0.00'`, and extend each `@param` shape with `price?: string, cost?: string`. Follow the existing `array_key_exists` pattern in `UpdateProduct` for `selling_unit` when deciding whether to write an omitted field.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=CatalogWriteApiTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_11_28_000000_add_price_and_cost_to_products_table.php app/Models/Product.php app/Modules/Catalog/Http/Requests/StoreProductRequest.php app/Modules/Catalog/Http/Requests/UpdateProductRequest.php app/Modules/Catalog/Actions/CreateProduct.php app/Modules/Catalog/Actions/UpdateProduct.php tests/Feature/Api/CatalogWriteApiTest.php
git commit -m "feat(catalog): persist product base price and cost"
```

### Task 2: Add base price and cost to the authoritative calculation

**Files:**
- Modify: `app/Services/QuotePricingService.php:18-36`, `app/Services/QuotePricingService.php:135`, `app/Services/QuotePricingService.php:137`
- Test: `tests/Feature/Api/QuoteApiTest.php`, `tests/Feature/Api/NoMaterialOrderApiTest.php`

**Interfaces:**
- Consumes: `Product::$price`, `Product::$cost` from Task 1.
- Produces: unchanged `QuotePricingService::calculate(array $params): array` return keys (`unit_price`, `subtotal`, `cost`, `profit`, `discount_amount`, …).

- [ ] **Step 1: Write failing pricing tests**

In `QuoteApiTest.php`: (a) product with no material/attributes, `price: '100000.00'`, `cost: '40000.00'`, quantity 2 → assert `unit_price: '100000.00'` and `subtotal: '200000.00'`; (b) product `price: '100000.00'` plus a material priced `50000.00`, with the existing reseller setup at 10% and a quantity tier of 10% at quantity 2 → assert `subtotal: '243000.00'` and `discount_amount: '57000.00'`. In `NoMaterialOrderApiTest.php`, add a case with those base values and quantity 2 that creates an invoice and asserts order snapshots `unit_price: '100000.00'`, `subtotal: '200000.00'`, `cost: '80000.00'`, `profit: '120000.00'`. Keep the existing zero-default `0.00` assertions untouched.

- [ ] **Step 2: Run them to verify they fail**

Run: `php artisan test --filter='(QuoteApiTest|NoMaterialOrderApiTest)'`
Expected: FAIL — base values are ignored.

- [ ] **Step 3: Implement the two formula lines**

Change `$basePrice = $materialPrice + $attributePriceSum;` to `$basePrice = (float) $product->price + $materialPrice + $attributePriceSum;` and `$unitCost = $materialCost + $attributeCostSum;` to `$unitCost = (float) $product->cost + $materialCost + $attributeCostSum;`. Update the docblock lines that currently read `base_price = materials.price + …` and `unit_cost = materials.cost + …` to include the product base values first. Do not touch the reseller, tier, area, subtotal, cost, or profit lines.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter='(QuoteApiTest|NoMaterialOrderApiTest)'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/QuotePricingService.php tests/Feature/Api/QuoteApiTest.php tests/Feature/Api/NoMaterialOrderApiTest.php
git commit -m "feat(pricing): add product base price and cost to quotes"
```

### Task 3: Role-gated money visibility in ProductResource

**Files:**
- Modify: `app/Modules/Catalog/Http/Resources/ProductResource.php`
- Test: `tests/Feature/Api/ProductCompositionApiTest.php`

**Interfaces:**
- Consumes: `Product::$price`, `Product::$cost` from Task 1.
- Produces: `price` in product responses for owner, manager, cashier, designer; `cost` for owner and manager only.

- [ ] **Step 1: Write failing visibility tests**

In `ProductCompositionApiTest.php`, using its existing auth helpers, assert for each role that a product response contains `price: '150000.00'`: owner, manager, cashier, designer. Assert `cost: '60000.00'` for owner and manager, and that `cost` is absent for cashier and designer. Add an assertion that `/api/v1/quotes/options` products carry `price` for a designer and omit `cost`. Confirm production still cannot view products (existing test).

- [ ] **Step 2: Run them to verify they fail**

Run: `php artisan test --filter=ProductCompositionApiTest`
Expected: FAIL — keys are absent.

- [ ] **Step 3: Implement resource visibility**

Move `$user = $request->user();` above the existing tiers block. For `owner|manager|cashier|designer`, set `$data['price'] = Decimal::money($this->price);`. For `owner|manager`, set `$data['cost'] = Decimal::money($this->cost);`. Leave the existing `tiers` role check unchanged.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ProductCompositionApiTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Catalog/Http/Resources/ProductResource.php tests/Feature/Api/ProductCompositionApiTest.php
git commit -m "feat(catalog): expose product base price and cost by role"
```

### Task 4: Product form fields, payload, and non-blocking hint

**Files:**
- Modify: `frontend/src/api/types.ts` (`Product` interface, lines 97-118)
- Modify: `frontend/src/features/catalog/ProductTiersField.tsx:84-103`
- Modify: `frontend/src/features/catalog/ProductsPage.tsx:85-175`
- Test: `frontend/src/features/catalog/productOptions.test.ts`, `frontend/src/features/catalog/ProductsPage.readOnlyOptions.test.tsx`

**Interfaces:**
- Consumes: `Product.price: string`, `Product.cost?: string` from Task 3.
- Produces: `productPayload(values): Record<string, unknown>` includes `price` and `cost` as strings; `CrudField` entries with keys `price`, `cost`, `base_cost_hint`.

- [ ] **Step 1: Write the failing frontend tests**

Add `price: '0.00'` to the `Product` fixtures in both test files so typecheck fails before the interface exists. In `ProductsPage.readOnlyOptions.test.tsx`, using its existing render setup, add a test that fills `price: '100000'` and `cost: '150000'` and asserts `Biaya dasar lebih besar dari harga dasar — cek kembali` is visible, plus a case with equal or zero values where it is absent.

- [ ] **Step 2: Run them to verify they fail**

Run: `npm --prefix frontend run typecheck` and `npm --prefix frontend run test`
Expected: FAIL — unknown properties on `Product`, hint not rendered.

- [ ] **Step 3: Implement fields and payload**

Add `price: string` and `cost?: string` to `Product` in `api/types.ts`. In `productPayload()` add `price: String(values.price ?? '0.00')` and `cost: String(values.cost ?? '0.00')`. In `ProductsPage.tsx` add two `number` fields with keys `price` and `cost`, defaults `'0.00'`, `getValue: (row) => row.price ?? '0.00'` (and `row.cost ?? '0.00'`), labels `Harga dasar` and `Biaya dasar`. Add one `custom` field with key `base_cost_hint`, `label: ''`, and `render(values)` returning the hint `<p>` when `Number(values.cost) > Number(values.price) && Number(values.cost) > 0`, otherwise `null`. Note: `valuesForFields()` initializes a custom field's value to `[]` and `render` does not receive `readOnly`; do not depend on either.

- [ ] **Step 4: Run checks to verify they pass**

Run: `npm --prefix frontend run typecheck`, `npm --prefix frontend run lint`, `npm --prefix frontend run test`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add frontend/src/api/types.ts frontend/src/features/catalog/ProductTiersField.tsx frontend/src/features/catalog/ProductsPage.tsx frontend/src/features/catalog/productOptions.test.ts frontend/src/features/catalog/ProductsPage.readOnlyOptions.test.tsx
git commit -m "feat(catalog): add product base price and cost fields"
```

### Task 5: Documentation and full verification

**Files:**
- Modify: `docs/requirements/fr.md` (FR6, FR8, revision history)
- Modify: `docs/requirements/nfr.md`
- Modify: `docs/requirements/access-matrix.md`
- Modify: `docs/architecture/erd.md` (products table, pricing notes)
- Modify: `docs/architecture/api-contracts.md` (product fields, `/quotes/options`, quote formula)
- Modify: `docs/architecture/frontend-architecture.md`
- Modify: `docs/requirements/implementation-gaps.md`

**Interfaces:**
- Consumes: the implemented behavior from Tasks 1-4.
- Produces: docs that agree with the code.

- [ ] **Step 1: Update the docs**

Describe the two columns, their `0.00` defaults and additive semantics, the unchanged discount chain, the role visibility rule, the per-branch deferral, and the non-blocking hint. Add a revision-history row in `fr.md`.

- [ ] **Step 2: Run full verification**

Run: `./init.sh`, then `php artisan test`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse --memory-limit=1G`, `composer audit`, and `npm --prefix frontend run build`.
Expected: all pass. If `./init.sh` fails, fix the cause before proceeding.

- [ ] **Step 3: Commit**

```bash
git status --short
git add docs/requirements/fr.md docs/requirements/nfr.md docs/requirements/access-matrix.md docs/architecture/erd.md docs/architecture/api-contracts.md docs/architecture/frontend-architecture.md docs/requirements/implementation-gaps.md
git commit -m "docs: document product base price and cost"
```
