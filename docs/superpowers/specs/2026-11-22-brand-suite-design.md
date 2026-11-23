# ALJA POS — Brand Suite UI Revision

Status: approved 2026-11-22
Source: Google Stitch project `15712871753929045706` ("POS Alja — Brand Suite v2"), 13 design screens + DESIGN.md

## Intent

Bring the SPA onto the ALJA Brand Suite design system: one shell, one component set, the 13 prototyped
screens recomposed over real data, then the remaining pages swept for consistency. Behaviour, routes,
permissions, API contracts and money rules are untouched. This is a presentation-layer revision.

## Goals

1. A single visual system across all 27 route pages.
2. The 13 prototype screens reproduced in composition, not pixel-for-pixel.
3. Light and dark both first-class. Light is the reference; dark is the same system on the existing dark surfaces.
4. Responsive at 390 / 768 / 1440.
5. Print artifacts (A4 ticket, 80mm receipt) restyled; content unchanged in meaning.

## Non-goals

No backend or schema changes. No new dependencies (the existing SVG `Sparkline` stays; no chart library).
No route, permission, or Indonesian-copy changes. No fixed 1280x800 non-scrolling canvas.

## Anti-goals

Inventing numbers to match a prototype. A half-migrated app where some pages still use old card styling.
Altering business behaviour while restyling.

## Constraints

Tailwind 4 `@theme` only, no `tailwind.config.js`. Keep `apiFetch`, TanStack Query keys, `CrudPage`,
`ResourceTable`. Money stays decimal strings, tabular and right-aligned. Production screens never render
money. Every changed page keeps its existing tests passing.

## Approaches considered

1. Restyle in place, page by page. Cheap per page, 27x duplication, guaranteed drift. Rejected.
2. Foundations first, then pages. Tokens, shell and primitives change once and every page inherits; the 13
   screens then get composition work; last, a sweep. Chosen.
3. Rebuild from the prototypes. Discards a working data layer for no visual gain. Rejected.

## Foundations

### Tokens

`frontend/src/styles/app.css` already carries the same palette as the design system. Changes:

- Filled controls move to `#D01A7E`, hover `#B0185F`. `#E91E8C` remains the mark/accent colour. This is the
  only visible colour change.
- Add tints: owner `#FFF0F3`, info `#E0F7FF`, warn `#FFFBEB`.
- Add strong-shade status text: `#047857`, `#B45309`, `#B91C1C`, `#1D4ED8`.
- Add a panel radius token (`1rem`). Soften card shadow to a small shadow and remove brand glows from cards;
  the glow survives only on primary buttons.
- Top stripe becomes magenta 33% / yellow 22% / cyan 45%.
- Dark mode maps the same tokens onto `surface-900/800/700`, `surface-400` borders, `gray-100/400` text and
  tint-at-10% backgrounds.

### Shell

White 240px sidebar (64px collapsed), 3px stripe, CMYK-square mark beside the `ALJA POS` wordmark, grouped
navigation with 10px uppercase group captions on the existing `NavGroup` model, solid-magenta active row,
footer cluster (avatar, name, role, theme toggle, sign-out). Mobile drawer behaviour unchanged.

### Page banner

`PageHeader` becomes `PageBanner`: role-tinted, 16px radius, 8px CMYK squares beside the title, uppercase role
badge, description line, right-hand actions slot, one blurred brand circle. Replaces the generic "Ruang Kerja"
eyebrow. Badge text per role: Executive Suite, Branch Operations, Front Desk, Design Studio, Production Floor,
Administration.

### Primitives

Button 40px with 12px radius. Card becomes Panel, 16px radius, 20px padding. Table headers 10px uppercase,
rows 36px, numerals tabular and right-aligned. Badge becomes a status pill with a 6px dot in the strong shade.
Modal defaults to 560px with a flat `rgba(26,26,46,0.55)` dim. Inputs 40px with 10px uppercase labels and a
2px magenta focus ring at 40% opacity. `StatCard` becomes a KPI card (10px label, 22px/800 value, tinted icon
chip, comparison line). Pagination footer reads `Showing 1-N of M`. Login: 4rem grid mesh, 448px card, brand
stripe on top.

### Print

A4 ticket and 80mm receipt render as white paper on a `#1A1A2E` backdrop with CMYK squares.

## Screens and data honesty

Each screen follows its prototype composition. Where a prototype element has no data behind it (confirmed by
an API audit; most gaps are schema-level), the element is dropped rather than faked.

| Screen | Kept | Dropped (no data) |
|---|---|---|
| Login | full | - |
| Owner dashboard | 4 KPIs (gross revenue, base cost, net profit, expenses) with derived % sublines; net-profit area chart from `/reports/sparkline`; right panel becomes Financial detail links with real totals | receivables, order-count strip, per-branch table |
| Manager dashboard | 4 KPIs from dashboard counts (materials, machines, staff, pricing rules); production queue panel; low-stock panel derived from materials | revenue/expenses-today totals, on-shift roster |
| Manager materials | 3 KPIs (active materials, categories, machines); table with derived Low/In-stock status; recent movements panel | unit column, stock value, purchases-this-month, low-stock filter and count |
| Designer new order | full form plus server quote panel | design status, file upload |
| Cashier front desk | KPIs from available stats; billing queue; pickup panel; expenses panel | cash drawer, pickup count |
| Checkout dialog | full; tendered and change kept as transient client state only | - |
| Receipt 80mm | header, lines, totals, payment history | per-item finishing, tendered/change (not persisted) |
| Print queue | 3 KPIs; job cards with reference, file, due, customer, quantity, area, operator, status | machine, priority, machine-load panel |
| Waste | waste-today KPI, low-stock, log form, recent log | usage-today, entry counts, invoice select, unit |
| Users | search, role filter, table | role-count KPIs, last-active, active-status column |
| Job ticket A4 | invoice metadata, item table, checklist from order status | priority, operator field, per-line material/machine/size |

Two payload details must be verified before use rather than assumed: the owner dashboard's non-`finance` keys,
and which stats the cashier dashboard returns. If a panel's data is absent, it moves to the dropped column and
the deviation is reported.

## Left to the builder

Exact spacing, icon choices per navigation item, per-page column sets within the rules above, test updates, and
the sweep of the remaining 14 pages.
