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
| `/profile` | authenticated | Profile, password, account deactivation |
| `/owner` | owner | Finance dashboard |
| `/owner/revenue` | owner | Gross revenue detail |
| `/owner/base-cost` | owner | Base cost detail |
| `/owner/net-profit` | owner | Net profit detail |
| `/owner/expenses` | owner | Expense entry and detail |
| `/owner/audit` | owner | Audit trail with search/sort and a per-row payload detail modal |
| `/owner/backups` | owner | Backup history, manual run, download, pin/unpin, schedule and retention settings |
| `/owner/purchases` | owner | Purchase history and purchase entry |
| `/owner/users`, `/owner/branches`, `/owner/categories`, `/owner/products`, `/owner/attributes`, `/owner/materials` | owner | Administration CRUD |
| `/manager` | manager | Branch dashboard |
| `/manager/materials`, `/manager/ledgers`, `/manager/expenses`, `/manager/customers` | manager | Inventory, expense entry, customers |
| `/manager/finishing` | manager | Create/edit own-branch finishing/display attributes; global rows read-only |
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
| `/orders/:id/ticket` | authenticated | A4 work ticket (`Tiket Perintah Kerja`), no money fields |
| `/orders/:id/receipt` | owner, manager, cashier, designer | 80 mm receipt (`Nota`) |
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
- Tables scroll horizontally on small screens. In CRUD tables the action column sticks to the right at every width; in the production queue the action column and in purchases the total column stick only below `lg:` (`max-lg:sticky`).
- Pagination copy is Indonesian: `Menampilkan X–Y dari Z` when a total is known, with `Halaman X dari Y` for
  lists whose total cannot be stated honestly.
- A failed query renders an `EmptyState` with a `Coba lagi` retry that refetches; `ResourceTable` and the
  designer order form's `/quotes/options` selectors follow it. A successful empty result uses the empty copy,
  and errors inside a control stay inline. The states are never merged.
- `/production/queue` shows `Area` (m²) and `Operator` at `lg:` and above with a `Gagal` badge for status `3`. A failed order with no waste shows a `Belum ada bahan terbuang` warning badge in its invoice cell at every width, and closing its auto-opened waste modal asks `Belum ada bahan terbuang. Tetap tutup?`. Status `0` offers `Mulai`, status `1` offers `Selesaikan` and `Gagal`, and status `3` offers `Proses Ulang`; status `0`, `1`, and `3` offer `Bahan Terbuang`. Failing opens the waste modal with the failed order. It never shows money. `/production/materials` is a read-only Bahan & Stok list: no create/edit/delete or money fields, with the same low-stock filter. `/production/waste` is read-only history titled "Riwayat Bahan Terbuang" with no create form.
- Print views have dedicated `@media print` CSS: the production work ticket (`/orders/:id/ticket`, available to every authenticated role) and the 80 mm payment receipt (`/orders/:id/receipt`, denied to production), with `PrintHeader` CMYK marks on white paper. There is no dedicated A4 invoice layout.
- The designer order form follows Product -> Material -> Finishing -> Display, deriving dimension inputs from `selectedProduct.pricing_type === 'area'` and filtering the Material dropdown to `selectedProduct.material_ids` when mapped (falling back to all branch materials when empty). The `/quotes/options` lists follow the active catalog filters: active categories only, active products in an active or null category, and materials in an active category. The designer edit page requests `include_inactive_attributes=1` through its own `['quote-options','edit']` key: inactive finishing/display options appear only on rows that already selected them and stay removable, and the new-order page keeps `['quote-options']` without the flag. Finishing/display masters exist at `/owner/attributes` (own-branch rows are also editable at `/manager/finishing`), and the quote/invoice API accepts and persists `selected_attributes[]` on `orders.selected_attributes`; there is no separate `order_attributes` snapshot table. Customer creation uses `/designer/customers/new` and returns to the order flow after save.
- The material screen includes the `unit` dropdown with the seven values, dynamic `Biaya per {unit}` and `Harga per {unit}` labels, a reason-required stock adjustment action, and a low-stock filter. Owner, manager, and production lists render quantities through `unitLabel`/`quantityWithUnit` from `lib/units.ts`, so `m2` shows as `m²` and a null unit falls back to `pcs`. The owner marks low rows with a `menipis` text cue; manager and production also apply a row tint. Owner rows edit each branch's minimum threshold inline through `MinimumStockInline` (`Ubah` -> number input -> `Simpan`/`Batal`, sending that row's `branch_id`); the manager edits the single own-branch Minimum column and its mobile copy without a branch id. A successful save invalidates the owner/manager material lists, the shared `materials` key, and the dashboard without changing `current_stock` or writing a stock-ledger row. A material in an inactive category shows a `Kategori nonaktif` badge under its name on both pages.
- `/owner/products` and `/owner/categories` provide Owner create/edit without delete (products include 10-per-page pagination, compatible-material multi-select, and a `Kategori nonaktif` badge with `(nonaktif)` option labels when the category is inactive; categories include `name`, `description`, the derived `products_count`, and `is_active`, with no tiers). `/manager/products` and `/manager/categories` render read-only views for Branch Manager. `/owner/attributes` provides Owner create/edit for finishing/display options (`name`, `type`, `price`, `cost`, `is_active`, and an optional branch, where no branch means global) without delete, while `/manager/finishing` shares the same form but creates only for the manager's own branch and edits only own-branch rows, so global rows are read-only. `/owner/branches` includes the branch `reseller_discount_percent` field. `/manager/pricing` (`Matriks Harga`) performs CRUD on own-branch pricing-matrix rows. The machine pages and endpoints are removed.
- `/owner/users` and `/manager/users` (`Manajemen Pengguna`) have no Hapus. Rows render `RoleBadge`, an Aktif/Nonaktif `Badge`, and an `ActiveToggleButton`: deactivation confirms that access ends on the next authenticated `/api/v1` request, activation does not confirm, and the owner's own row has no toggle. `/owner/branches` has no Hapus and no `is_active` checkbox; rows toggle status with branch-specific confirmation. In the user form, inactive branches are labelled `(nonaktif)`, and trashed branches are excluded from the selector and from store/update validation.
- `/owner/audit` keeps the owner-only global audit table with search, sort, and pagination. Each row has a `Lihat` button that opens `AuditDetailModal` from the already-loaded row: metadata plus a payload table (`before`/`after` as a three-column comparison, other payloads as key/value rows). There is no extra detail request.
- `/owner/backups` (`BackupPage`) has a `PageBanner` with `Jalankan Backup`, and a "Pengaturan Backup" card: local/R2 enable toggles, `time` inputs, and four retention numbers per destination with the server's 7-90 / 4-52 / 6-24 / 1-10 bounds. The card shows "Penggunaan R2: X dari Y (Z%)", amber at 80% and red at 100%, the pinned count, "R2 nonaktif, backup lokal saja." when remote is off, and a warning when R2 is enabled but unconfigured (credentials stay operator-only in `.env`).
  - "Riwayat Backup" is a paginated table: Waktu, Jenis (Manual/Terjadwal), Tujuan (Lokal/R2/Keduanya), Status (Menunggu/Berjalan/Berhasil/Sebagian/Gagal), Ukuran, Aksi. Status carries `R2 dilewati`, `Retensi gagal: ...`, and the run error; a `running` row older than 30 minutes shows `Mungkin macet`.
  - `Unduh` is a normal same-origin link to `/api/v1/backups/{id}/download` and only renders when `local_available` or `remote_available`; otherwise the row shows `Tidak tersedia`. `Sematkan`/`Lepas` is a TanStack mutation that invalidates the list and settings.
  - The list refetches every 5 seconds while any queued/running row is at most 2 minutes old; after that it stops and shows "Belum ada hasil. Periksa worker." when every active row is older. The run button is disabled while any queued/running row is under 30 minutes old. A finished run refreshes the settings query so usage updates.
  - The page uses the shared `Card`, `Table`, `Pagination`, `Input`, `Checkbox`, `Badge`, `EmptyState`, `Spinner`, `Toast`, and `Button`; the settings grid stacks below `sm`. Copy is Indonesian.
- `Card` follows the Panel spec (white, 16px radius, 1px border, 20px padding), and `StatCard` follows the KPI
  spec (10px uppercase label, 22px/800 value). Controls are 40px tall, labels 10px uppercase.
- `CrudPage` provides create/edit/delete for admin resources; `ResourceTable` handles paginated read-only
  lists. `CrudPage` supports `summary`, `footer`, and `toolbar` slots, `rowClassName` for row tinting, `canEditRow` to hide the Ubah action per row while keeping the action column, `pageResetKey` to return to page 1 when a filter
  changes, and `hideBanner` so a second section on the same page can render a labelled panel header instead of
  a second page banner.
- `/owner/purchases` and `/manager/purchases` share `PurchasesPage`: a paginated history table plus a "Catat Pembelian" modal with date-only `purchased_at`, supplier, notes, dynamic line items, a running total, and an `idempotency_key` generated once when the modal opens. Each history row expands to its line items: material name, quantity with unit, unit cost, subtotal, notes, and the recorder. `ResourceTable` takes an optional `expandedRow` renderer; existing tables are unchanged. The owner selects a branch; the manager has no branch selector. The purchase modal labels quantity with the selected material's unit.
- `/manager/ledgers` adds type, reason, and user columns to the stock quantities.
- Copy is hardcoded Indonesian; there is no i18n layer. Code identifiers, API fields, and database values stay English, with one exception: `expense.category` is a fixed dropdown, not free text, so reports group by the selected category.
- Shared copy lives in `roleLinks`, `admin/CrudPage`, `admin/ActiveToggleButton`, `components/RoleBadge`, `components/ui`, `lib/labels.ts`, and `lib/units.ts`.
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
