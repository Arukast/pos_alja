# Customer management and per-order institution selection

Date: 2026-11-27
Branch: feature/customer-management

## Intent and why

A customer is a person, not a single institution. The same person can order once for the school where they teach and later as a private individual. Today the customer has one `institution` column, so representing both contexts means creating duplicate customer records and fragmenting the customer list.

Separately, the designer's Pesanan Baru page fires the customer query with an empty search on mount, so up to eight customers are shown before the user searches. We want a search-first picker.

This change delivers: (1) customers hidden until search, (2) a dedicated customer management screen that lists and edits customers, (3) multiple institution labels per customer, and (4) an institution choice at order creation that is recorded on the invoice.

## Goals

- On Pesanan Baru, show no customers until the user types a name or phone fragment; then show matching customers.
- After choosing a customer, let the designer pick which of that customer's institutions applies to this order, with a sensible default.
- Record that choice on the invoice so historical orders do not change when a customer is edited.
- Provide a dedicated management page that lists customers and supports create and edit of every customer field including institution labels.
- Allow owner, manager and designer to add and edit customers; cashier may view the page read-only; production has no access.
- Preserve existing customer and invoice data through migrations.
- Update the docs that currently disagree with the code.

## Non-goals

- No named institution entities (school name, address, separate master table). Labels only.
- No customer delete or retirement flow.
- No institution grouping or filtering in reports/export/print.
- No change to pricing: `type` (Reguler/Reseller) remains per customer and is the only pricing input.
- No change to customer branch scope: customers remain global masters.
- No new dependencies.

## Constraints

- Laravel 13, PHP 8.5, PSR-12, `declare(strict_types=1);`, explicit types.
- UUID keys; migrations must run on SQLite and PostgreSQL; never edit a merged migration.
- React 19 / TypeScript 7 / Vite / Tailwind 4 tokens in `frontend/src/styles/app.css`; no new Tailwind config.
- Reuse `CrudPage`, shared primitives, `apiFetch`, and TanStack Query invalidation.
- Keep Indonesian UI copy and existing domain terms; code in English.
- Conventional Commit messages; explicit-path staging only.
- Repository AGENTS.md requires `docs/` to match the code.

## Approaches considered

1. **Institution labels on the customer, recorded per order (chosen).** The customer holds a set of labels; the designer picks one for each order and the invoice stores a snapshot. Smallest change that fully solves the duplicate-customer problem and keeps history truthful.
2. **Institutions as real entities with names and a pivot table.** Rejected: the request is about context (school vs self), not storing which school. It would add a master table, pivot, extra endpoints, and a nested management UI for no stated benefit. This remains a future upgrade if institution names are ever needed.
3. **Keep one institution on the customer and override it per order without saving.** Rejected: Bob still has one stored identity, so the customer list stays fragmented — the core problem remains.

## Decisions

### D1 Data model

- Add `customers.institutions` as a JSON array of label strings, default `["Masyarakat/Retail"]`.
- Keep `customers.institution` as the primary label (the first element of `institutions`), kept in sync on every write. This preserves existing readers and existing tests without a broad rewrite.
- Migration backfills `institutions` from the existing `institution` value using a portable chunked update, never platform-specific SQL.
- Normalize `institutions` to the order of the shared label constant on every write, so the first element (the primary label) is deterministic regardless of checkbox click order.
- Allowed labels live in one PHP constant on `App\Models\Customer` and one exported constant in `frontend/src/api/types.ts`: `Masyarakat/Retail`, `Sekolah`, `Universitas`, `Pemerintahan`, `Bisnis/Perusahaan`, `Organisasi/Yayasan`. The last two are additions requested by the user.

### D2 Write contract for customers

- `StoreCustomerRequest` and a new `UpdateCustomerRequest` accept `institutions` as a required array with `min:1`, each value in the allowed set.
- Both fields are individually optional but one is required: `institutions` uses `required_without:institution`, `institution` uses `required_without:institutions`; both are validated against the allowed set. When both are sent, `institutions` wins. An invalid label yields 422 on the field that carried it. This keeps existing API clients and tests working.
- Create and update both write through Actions inside `DB::transaction()` and write an audit row with user and branch context.

### D3 Per-order snapshot

- Add nullable `invoices.institution` (string).
- `StoreInvoiceRequest` accepts an optional `institution` string validated against the allowed set.
- `CreateInvoice` resolves the customer, checks the requested label is one of that customer's `institutions` (422 on the `institution` field otherwise), defaults to the customer's first label when omitted, and stores it on the invoice.
- The label is data only: no invoice detail, receipt, ticket, or print layout changes are in scope.
- `InvoiceResource` emits the stored snapshot, falling back to `$this->customer?->institution` for older invoices with no snapshot.
- The migration also backfills `invoices.institution` from the customer's current label with a portable chunked update, so historical invoices get a snapshot too.

### D4 Authorization

- `CustomerPolicy::viewAny` unchanged: owner, manager, cashier, designer.
- `CustomerPolicy::create` and a new `update` allow owner, manager, designer.
- Cashier sees the management page but the UI disables create/edit (`canEditRow`), backed by the policy returning false.
- Production is denied both the page and the endpoints. Customers stay global masters; no branch filter is added.
- Owner/manager create becomes true in this change. The pre-existing no-branch-designer gap stays out of scope and is recorded as remaining debt.

### D5 Management screen

- One shared React page `CustomerManagementPage` with a shared route `/customers` guarded by `RequireRole roles={['owner','manager','designer','cashier']}`.
- Add a "Pelanggan" nav item to the owner, manager, designer and cashier groups in `roleLinks.ts`.
- The page uses `CrudPage<Customer>`: columns Nama, Institusi, Tipe, Telepon; fields name (text), `institutions` (multiselect, required, options from the shared frontend constant, `getValue` mapping `customer.institutions`), type (select Reguler/Reseller), phone, email, address.
- The page is the only create/edit form. Remove the separate `DesignerNewCustomerPage` component and point the Pesanan Baru "Tambah Pelanggan" link at `/customers`.
- Both the "Tambah Pelanggan" and "Buat pelanggan baru" buttons point to `/customers`; remove `DesignerNewCustomerPage.tsx`, its router entry, and its two documentation references. Navigating away from a draft order still discards cart lines, matching current behavior; no draft persistence is added.

### D6 API and frontend client

- `GET /api/v1/customers` becomes paginated (20 per page) with the existing `search` filter on name and phone; response is `{ data, links, meta }`. Search callers read `data` only.
- Add `PUT /api/v1/customers/{customer}` authorized by `CustomerPolicy::update`, returning `CustomerResource`.
- `frontend/src/api/types.ts`: `Customer.institution` stays, add `institutions: string[]`; change `api.customers` to `Paginated<Customer>`; add `updateCustomer(id, values)`.
- Existing `GET` consumers need no change beyond the type annotation.

### D7 Pesanan Baru behavior

- Query `enabled` becomes `!customer && customerSearch.trim() !== ''`.
- Idle state renders a short message instead of a list: "Cari pelanggan untuk memilih."
- Selecting a customer reveals an "Institusi pesanan" select populated from that customer's `institutions`, defaulting to the first. `Pilih` stores the customer and selected label; the invoice create payload includes `institution`.

### D8 Tests and verification

Backend additions in `CustomerApiTest` and `CreateInvoiceApiTest`:
- guest 401 and wrong-role 403 for index/create/update; owner and manager create allowed; cashier create/update 403.
- validation 422 for missing name, empty institutions, invalid label, and legacy invalid singular institution.
- happy create and update with multiple institutions; audit row written on update; search still returns matching customers with `institutions`.
- invoice stores the chosen label; a label not belonging to the customer is rejected; omitted label falls back to the first.

Frontend: a small Vitest test for the shared institution-label helper/constant. Browser smoke at 390, 768 and 1440 px for designer, owner and cashier.

Verification: `./init.sh`, `php artisan test`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse --memory-limit=1G`, `composer audit`, and the four frontend scripts.

### D9 Documentation

Update in the same change: `docs/requirements/fr.md`, `docs/requirements/nfr.md`, `docs/requirements/access-matrix.md`, `docs/requirements/implementation-gaps.md`, `docs/requirements/sprint-review-1.md`, `docs/architecture/erd.md`, `docs/architecture/api-contracts.md`, `docs/architecture/frontend-architecture.md`, `docs/architecture/sequence-diagrams.md`. Align the previously stale claims: owner/manager create and `PUT /customers/{customer}` become true in this change, and the manager path becomes the shared `/customers` route. Leave the no-branch-designer gap listed as remaining debt.

## Acceptance criteria

1. Opening Pesanan Baru with an empty search shows no customer rows; typing a fragment shows matches by name or phone.
2. Choosing a customer shows their institution labels; the designer can pick one and the selected label is saved on the invoice and returned by the API.
3. Editing that customer's institutions later does not change the label returned by the invoice API for existing invoices.
4. Bob can be one customer with both `Sekolah` and `Masyarakat/Retail` and can be searched once.
5. Owner, manager and designer can reach `/customers`, add a customer and edit any customer; cashier sees a read-only list; production gets 403 on the page and endpoints.
6. The management list paginates and the create/edit form validates client and server side.
7. `./init.sh` and all additional checks pass; docs match the code.

## What is left to the implementer

Follow repository conventions for Actions, Requests, Resources, Policies and migrations. Add tests first for each behavioral change, then implement. Keep the diff to the files named above; do not add features outside the non-goals. Record verification evidence in `docs/requirements/implementation-gaps.md` and append phase entries to `.ai-docs/timeline.md` (never staged or committed).
