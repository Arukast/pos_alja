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
| `/owner/users`, `/owner/branches`, `/owner/categories`, `/owner/attributes`, `/owner/materials`, `/owner/customers` | owner | Administration CRUD |
| `/manager` | manager | Branch dashboard |
| `/manager/materials`, `/manager/ledgers`, `/manager/expenses`, `/manager/customers` | manager | Inventory, expense entry, customers |
| `/manager/purchases` | manager | Purchase history and purchase entry, own branch only |
| `/manager/leaderboard`, `/manager/users` | manager | Performance and user administration |
| `/cashier` | cashier | Cashier dashboard |
| `/cashier/payments` | cashier | Payment queue and checkout |
| `/cashier/pickup` | cashier | Pickup queue |
| `/designer` | designer | Designer dashboard |
| `/designer/new-order` | designer | Customer search, quote, cart, invoice |
| `/designer/customers/new` | designer | Create customer on a separate page |
| `/designer/orders` | designer | Invoice list and print links |
| `/designer/orders/:id/edit` | designer | Edit invoice items while unpaid and before production |
| `/queue` | owner, manager, cashier, designer | Read-only production queue with owner branch filter |
| `/production` | production | Production dashboard |
| `/production/materials` | production | Read-only Bahan & Stok with low-stock filter |
| `/production/queue` | production | Status control, inline waste modal |
| `/production/waste` | production | Read-only Riwayat Sisa Bahan history |
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
  lib/            api, money, dates, errors, echo, pagination, cn
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
- `/production/queue` shows `Area` (m²) and `Operator` at `lg:` and above and an inline waste modal prefilled with the order. It never shows money and has no machine field. `/production/materials` is a read-only Bahan & Stok list: no create/edit/delete or money fields, with the same low-stock filter. `/production/waste` is read-only history titled "Riwayat Sisa Bahan" with no create form and no machine column.
- Print views have dedicated `@media print` CSS: A4 invoice at `min-h-[297mm] print:min-h-0`, 80 mm receipt,
  `PrintHeader` with CMYK marks, white paper. Production is denied both print views.
- The designer order form uses Product -> Material -> Finishing -> Display with single-select finishing/display controls. The customer field defaults to search; new customer creation lives on `/designer/customers/new`.
- The material screen includes the reason-required stock adjustment action and a low-stock filter. The owner marks low rows with a `menipis` text cue; manager and production also apply a row tint. The `unit` dropdown remains a target field and is not implemented (see `docs/requirements/implementation-gaps.md`).
- The category and finishing/display screens are Owner-only. The category screen includes quantity discount tiers (`min_qty`, `discount_percent`); `/owner/attributes` manages finishing/display options. The pricing-matrix and machine pages remain transitional and are scheduled for removal with the pricing redesign.
- `Card` follows the Panel spec (white, 16px radius, 1px border, 20px padding), and `StatCard` follows the KPI
  spec (10px uppercase label, 22px/800 value). Controls are 40px tall, labels 10px uppercase.
- `CrudPage` provides create/edit/delete for admin resources; `ResourceTable` handles paginated read-only
  lists. `CrudPage` supports `summary`, `footer`, and `toolbar` slots, `rowClassName` for row tinting, `pageResetKey` to return to page 1 when a filter
  changes, and `hideBanner` so a second section on the same page can render a labelled panel header instead of
  a second page banner.
- `/owner/purchases` and `/manager/purchases` share `PurchasesPage`: a paginated history table plus a "Catat Pembelian" modal with date-only `purchased_at`, supplier, notes, dynamic line items, a running total, and an `idempotency_key` generated once when the modal opens. The owner selects a branch; the manager has no branch selector.
- `/manager/ledgers` adds type, reason, and user columns to the stock quantities.
- Copy is hardcoded Indonesian; there is no i18n layer. Code identifiers, API fields, and database values stay English, with one exception: `expense.category` is a fixed dropdown, not free text, so reports group by the selected category.
- Shared copy lives in `roleLinks`, `admin/CrudPage`, `components/ui`, and `lib/labels.ts`.
- Dates format with `id-ID`; money already formats with `id-ID`.

## 6. Realtime

`useOrderUpdates` subscribes owners to `private-owners` and branch users to `private-branch.{id}`. It requires `VITE_REVERB_APP_KEY`; without it, realtime stays disabled and the app remains usable. Stock mutations (purchase, adjustment, usage, waste) use the existing `OrderUpdated` broadcast; there is no separate `StockUpdated` event or listener.

## 7. Verification

```bash
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build
```

Use `agent-browser` for role and viewport smoke checks. The UI is served by Vite in development and by Nginx from `frontend/dist` in production.
