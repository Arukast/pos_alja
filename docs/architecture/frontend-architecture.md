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
| `/owner/expenses` | owner | Expense detail |
| `/owner/audit` | owner | Audit trail |
| `/owner/users`, `/owner/branches`, `/owner/categories`, `/owner/products`, `/owner/machines`, `/owner/materials` | owner | Administration CRUD |
| `/manager` | manager | Branch dashboard |
| `/manager/materials`, `/manager/ledgers`, `/manager/finishing`, `/manager/pricing` | manager | Inventory and configuration |
| `/manager/leaderboard`, `/manager/users`, `/manager/categories`, `/manager/products`, `/manager/machines` | manager | Performance and administration |
| `/cashier` | cashier | Cashier dashboard |
| `/cashier/payments` | cashier | Payment queue and checkout |
| `/cashier/pickup` | cashier | Pickup queue |
| `/cashier/expenses` | cashier | Expense entry and ledger |
| `/designer` | designer | Designer dashboard |
| `/designer/new-order` | designer | Customer intake, quote, cart, invoice |
| `/designer/orders` | designer | Invoice list and ticket links |
| `/production` | production | Production dashboard |
| `/production/queue` | production | Start/complete jobs |
| `/production/waste` | production | Waste entry and ledger |
| `/orders/:id/ticket` | authenticated | Work-order print view and three-stage production checklist |
| `/orders/:id/receipt` | owner, manager, cashier, designer | 80 mm receipt |
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
- `/production/queue` shows `Area` (m²) and `Operator` at `lg:` and above; `/production/waste` shows a
  `Sisa Bahan Hari Ini` KPI from `/production/summary`, a quantity and never money.
- Print views have dedicated `@media print` CSS: A4 ticket at `min-h-[297mm] print:min-h-0`, 80 mm receipt,
  `PrintHeader` with CMYK marks, white paper.
- `Card` follows the Panel spec (white, 16px radius, 1px border, 20px padding), and `StatCard` follows the KPI
  spec (10px uppercase label, 22px/800 value). Controls are 40px tall, labels 10px uppercase.
- `CrudPage` provides create/edit/delete for admin resources; `ResourceTable` handles paginated read-only
  lists. `CrudPage` supports `summary` and `footer` slots, `pageResetKey` to return to page 1 when a filter
  changes, and `hideBanner` so a second section on the same page can render a labelled panel header instead of
  a second page banner.
- Copy is hardcoded Indonesian; there is no i18n layer. Code identifiers, API fields, and database values stay English, with one exception: `expense.category` is free text, new rows default to `Operasional`, and existing rows may be `Office`/`Operational`, so reports group by exact string.
- Shared copy lives in `roleLinks`, `admin/CrudPage`, `components/ui`, and `lib/labels.ts`.
- Dates format with `id-ID`; money already formats with `id-ID`.

## 6. Realtime

`useOrderUpdates` subscribes owners to `private-owners` and branch users to `private-branch.{id}`. It requires `VITE_REVERB_APP_KEY`; without it, realtime stays disabled and the app remains usable.

## 7. Verification

```bash
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build
```

Use `agent-browser` for role and viewport smoke checks. The UI is served by Vite in development and by Nginx from `frontend/dist` in production.
