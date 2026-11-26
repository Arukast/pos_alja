# Frontend Architecture

- **App:** `frontend/`, React 19 + TypeScript + Vite.
- **Styling:** Tailwind CSS 4 with `@theme` tokens in `frontend/src/styles/app.css`.
- **Server state:** TanStack Query.
- **Routing:** React Router with role guards.
- **API:** `apiFetch` in `frontend/src/lib/api.ts`, which handles cookies, CSRF, `X-XSRF-TOKEN`, 401 handling, and typed errors.

## 1. Routes

| Route | Role | Screen |
| :--- | :--- | :--- |
| `/login` | guest | Login |
| `/` | authenticated | Redirect to role home |
| `/profile` | authenticated | Profile, password, account deletion |
| `/owner` | owner | Finance dashboard |
| `/owner/revenue` | owner | Gross revenue detail |
| `/owner/base-cost` | owner | Base cost detail |
| `/owner/net-profit` | owner | Net profit detail |
| `/owner/expenses` | owner | Expense entry and detail |
| `/owner/audit` | owner | Audit trail |
| `/owner/purchases` | owner | Purchase history and purchase entry |
| `/owner/users`, `/owner/branches`, `/owner/categories`, `/owner/products`, `/owner/attributes`, `/owner/materials` | owner | Administration CRUD |
| `/manager` | manager | Branch dashboard |
| `/manager/materials`, `/manager/ledgers`, `/manager/expenses`, `/manager/customers` | manager | Inventory, expense entry, customers |
| `/manager/finishing` | manager | Read-only finishing/display attributes and attribute types |
| `/manager/pricing` | manager | CRUD own-branch pricing matrix (`Matriks Harga`) |
| `/manager/purchases` | manager | Purchase history and purchase entry, own branch only |
| `/manager/leaderboard`, `/manager/users` | manager | Performance and user administration |
| `/cashier` | cashier | Cashier dashboard |
| `/cashier/payments` | cashier | Payment queue and checkout |
| `/cashier/pickup` | cashier | Pickup queue |
| `/designer` | designer | Designer dashboard |
| `/designer/new-order` | designer | Customer entry, quote, cart, invoice |
| `/designer/orders` | designer | Invoice list and print links |
| `/designer/orders/:id/edit` | designer | Edit invoice items while unpaid and before production |
| `/production` | production | Production dashboard |
| `/production/materials` | production | Read-only Bahan & Stok with low-stock filter |
| `/production/queue` | production | Start/complete/fail/reopen controls, inline waste modal, server-side status and search filters |
| `/production/waste` | production | Read-only Riwayat Bahan Terbuang history |
| `/invoices/:id/print` | owner, manager, cashier, designer | A4 invoice print |
| `/invoices/:id/receipt` | owner, manager, cashier, designer | 80 mm receipt |
| `/dashboard?tab=...` | authenticated | Legacy redirect to role routes |

## 2. Structure

```
src/
  api/            client, queries, mutations, shared types
  auth/           AuthProvider, useAuth, RequireAuth, roleHome
  components/     AppShell, Sidebar, MobileDrawer, LogoMark, PageBanner, PrintHeader, ThemeToggle, UserPopover
  components/ui/  Button, Input, Select, Checkbox, Modal, Table, Card, ...
  components/admin/CrudPage
  features/       owner, manager, cashier, designer, production, print
  hooks/          useBranches, useOrderUpdates
  lib/            api, money, dates, errors, echo, pagination, units, cn
  routes/         router, legacy redirects
  styles/         app.css tokens
```

## 3. Authentication And Guards

- `AuthProvider` loads `GET /auth/me` on mount and exposes `user`, `login`, `logout`, and `refresh`.
- `RequireAuth` redirects guests to `/login`.
- `RequireRole` redirects a role to its home route.
- A 401 clears auth state and sends the user to login.
- The SPA uses relative `/api` and `/sanctum` requests; Vite proxies them in development and Nginx proxies them in production.

## 4. Data And Mutations

- Query keys follow `[module, resource, params]`.
- Mutations invalidate the relevant list, dashboard, and queue keys.
- `OrderUpdated` invalidates all queries; the event carries no data.
- Forms use `mapApiErrors` to render field-level 422 errors.
- Server-owned money is always displayed through `formatMoney`; the SPA never submits authoritative line prices.

## 5. UI Conventions

- Design tokens and component specifications live in `docs/architecture/design-system.md`. Read it before
  adding a token, a primitive, or a new page layout.
- Sidebar navigation, collapsible desktop rail, mobile drawer.
- Light/dark mode with `dark` class and `theme` in `localStorage`.
- Responsive layouts checked at 390, 768, and 1440 px.
- Native `<dialog>` modals with focus handling.
- Tables scroll horizontally on small screens. Action columns stick to the right only at `lg:` and above.
- Pagination copy is Indonesian: `Menampilkan X–Y dari Z` when a total is known, with `Halaman X dari Y` for
  lists whose total cannot be stated honestly.
- A failed query renders an `EmptyState` with a `Coba lagi` retry that refetches; `ResourceTable` and the
  designer order form's `/quotes/options` selectors follow it. A successful empty result uses the empty copy,
  and errors inside a control stay inline. The states are never merged.
- `/production/queue` shows `Area` (m²) and `Operator` at `lg:` and above with a `Gagal` badge for status `3`. A failed order with no waste shows a `Belum ada bahan terbuang` warning badge in its invoice cell at every width, and closing its auto-opened waste modal asks `Belum ada bahan terbuang. Tetap tutup?`. Status `0` offers `Mulai`, status `1` offers `Selesaikan` and `Gagal`, and status `3` offers `Proses Ulang`; status `0`, `1`, and `3` offer `Bahan Terbuang`. Failing opens the waste modal with the failed order. It never shows money. `/production/materials` is a read-only Bahan & Stok list: no create/edit/delete or money fields, with the same low-stock filter. `/production/waste` is read-only history titled "Riwayat Bahan Terbuang" with no create form.
- Print views have dedicated `@media print` CSS: A4 invoice at `min-h-[297mm] print:min-h-0`, 80 mm receipt,
  `PrintHeader` with CMYK marks, white paper. Production is denied both print views.
- The designer order form follows Product -> Material -> Finishing -> Display. Finishing/display masters exist at `/owner/attributes` (read-only at `/manager/finishing`), and the quote/invoice API accepts and persists `selected_attributes[]` on `orders.selected_attributes`; there is no separate `order_attributes` snapshot table. Customer creation uses `/designer/customers/new` and returns to the order flow after save.
- The material screen includes the `unit` dropdown with the seven values, dynamic `Biaya per {unit}` and `Harga per {unit}` labels, a reason-required stock adjustment action, and a low-stock filter. Owner, manager, and production lists render quantities through `unitLabel`/`quantityWithUnit` from `lib/units.ts`, so `m2` shows as `m²` and a null unit falls back to `pcs`. The owner marks low rows with a `menipis` text cue; manager and production also apply a row tint.
- `/owner/products` and `/owner/categories` provide Owner CRUD (including 10-per-page product pagination and category quantity discount tiers `min_qty`, `discount_percent`). `/manager/products` and `/manager/categories` render read-only views for Branch Manager. `/owner/attributes` provides Owner CRUD for finishing/display options (`price`, `cost`, `is_active`) and attribute types, while `/manager/finishing` renders a read-only view for Branch Manager. `/owner/branches` includes the branch `reseller_discount_percent` field. `/manager/pricing` (`Matriks Harga`) performs CRUD on own-branch pricing-matrix rows. The machine pages and endpoints are removed. Category discount tiers are configured but not applied to quotes or invoices.
- `Card` follows the Panel spec (white, 16px radius, 1px border, 20px padding), and `StatCard` follows the KPI
  spec (10px uppercase label, 22px/800 value). Controls are 40px tall, labels 10px uppercase.
- `CrudPage` provides create/edit/delete for admin resources; `ResourceTable` handles paginated read-only
  lists. `CrudPage` supports `summary`, `footer`, and `toolbar` slots, `rowClassName` for row tinting, `pageResetKey` to return to page 1 when a filter
  changes, and `hideBanner` so a second section on the same page can render a labelled panel header instead of
  a second page banner.
- `/owner/purchases` and `/manager/purchases` share `PurchasesPage`: a paginated history table plus a "Catat Pembelian" modal with date-only `purchased_at`, supplier, notes, dynamic line items, a running total, and an `idempotency_key` generated once when the modal opens. Each history row expands to its line items: material name, quantity with unit, unit cost, subtotal, notes, and the recorder. `ResourceTable` takes an optional `expandedRow` renderer; existing tables are unchanged. The owner selects a branch; the manager has no branch selector. The purchase modal labels quantity with the selected material's unit.
- `/manager/ledgers` adds type, reason, and user columns to the stock quantities.
- Copy is hardcoded Indonesian; there is no i18n layer. Code identifiers, API fields, and database values stay English, with one exception: `expense.category` is a fixed dropdown, not free text, so reports group by the selected category.
- Shared copy lives in `roleLinks`, `admin/CrudPage`, `components/ui`, `lib/labels.ts`, and `lib/units.ts`.
- Dates format with `id-ID`; money already formats with `id-ID`.

## 6. Realtime

`useOrderUpdates` subscribes owners to `private-owners` and branch users to `private-branch.{id}`. It requires `VITE_REVERB_APP_KEY`; without it, realtime stays disabled and the app remains usable. Stock mutations (purchase, adjustment, usage, waste) and production fail/reopen use the existing `OrderUpdated` broadcast; there is no separate `StockUpdated` event or listener.

## 7. Verification

```bash
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build
```

Use `agent-browser` for role and viewport smoke checks. The UI is served by Vite in development and by Nginx from `frontend/dist` in production.
# Sales UI revision

The designer starts a new order by searching customers. New customer registration uses `/designer/customers/new` and returns to the order flow after save. Print navigation is labeled “Nota”/“Cetak Nota”; production ticket data remains restricted to non-financial fields.
