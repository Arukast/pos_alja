# Design system

As built from the Google Stitch project `15712871753929045706` ("POS Alja — Brand Suite v2"). This file
describes the code in `frontend/src`, not the original design source. Where the built system deliberately
differs from that source, both the rule and the reason are recorded. When code and this file disagree, treat it
as a defect.

## Tokens

Tailwind 4 tokens live in `frontend/src/styles/app.css` under `@theme`. There is no `tailwind.config.js`.

The build added eight tokens to the Stitch palette:

| Token | Value | Use |
| :--- | :--- | :--- |
| `--color-brand-fill` | `#D01A7E` | primary buttons, active navigation, focus ring |
| `--color-brand-fill-hover` | `#B0185F` | primary hover, small magenta text |
| `--color-tint-owner` | `#FFF0F3` | owner banner |
| `--color-tint-ops` | `#E6F7FD` | manager, cashier, production banners |
| `--color-tint-designer` | `#F0FDFA` | designer banner |
| `--color-tint-ops-ink` | `#00658D` | ink on the ops tint |
| `--color-tint-designer-ink` | `#0F766E` | ink on the designer tint |
| `--color-light-muted-strong` | `#4B5563` | banner descriptions, strong muted copy |

Everything else reuses the existing `light-*` and `surface-*` scales plus `--color-brand-primary`,
`--color-brand-secondary`, `--color-brand-accent`, and `--color-brand-dark`. No parallel `canvas`, `paper`,
`line`, or `ink` token family exists; those roles are served by `light-bg`, `light-surface`, `light-border`,
and `light-text`.

## Type

Outfit only, with tabular numerals for money and counts.

- Page title: 28px/800 with tight tracking (`PageBanner`).
- KPI value: 22px/800 (`StatCard`).
- Panel heading: caller-owned. `Card` renders no title, so each caller sets the heading size. The nominal size
  is 16px at `font-semibold` (600), and the `CrudPage` section header uses 16px/700. Callers may deviate: the
  quantity-discount panel on the category page uses 18px (`text-lg`). The 16px/700 rule in the design source
  is a convention for section headers, not something a component enforces.
- Body copy: 14px (`text-sm`) on cards, forms, tables, and descriptions. The base element has no global
  14px rule.
- Table text: 14px (`text-sm`). The design source said 13px; the build uses 14px for readability.
- Labels: 10px/600 uppercase with wide tracking. The source allowed 10-12px; the build standardised on 10px.
- Money format `Rp 486.250.000`; dates `20 Nov 2026`.

## Colour

- Brand: magenta `#E91E8C`, filled magenta `#D01A7E`, filled hover `#B0185F`, cyan `#00AEEF`, yellow
  `#FFF200` for the brand stripe and marks only, dark neutral `#1A1A2E`.
- Light surfaces: canvas `#F8F9FA`, card `#FFFFFF`, secondary surface `#F1F3F5`, border `#E5E7EB`, ink
  `#111827`, muted `#6B7280`, muted-strong `#4B5563`.
- Status: soft pill, 6px dot, 11px/600 text in a strong tonal shade (emerald, amber, red, blue).
- Soft shadows only. No page gradients, glass panels, or neon glows. A page banner may carry a soft tint and
  one blurred magenta circle.

### Control borders

Controls and secondary buttons use `border-light-muted` (`#6B7280`), not `border-light-border`
(`#E5E7EB`). This is a deliberate deviation from the design source.

Reason: WCAG 1.4.11 requires 3:1 non-text contrast for control boundaries. `#E5E7EB` measured 1.24:1 on the
light canvas, and `surface-300` borders measured 1.35:1 on the dark surface. `#6B7280` clears both. Inputs,
selects, checkboxes, and the secondary button variant all use it; error controls switch to `red-500`.

## Shell

240px white sidebar (`w-60`), 64px collapsed (`w-16`), right border, and a 3px top stripe of magenta 33% /
yellow 22% / cyan 45%. The sidebar header carries the `LogoMark`, the `Alja POS` wordmark, and the current
branch name. Navigation groups use 10px uppercase muted captions, 40px rows, 12px radius, and 14px/500 labels.

Active navigation is a solid `#D01A7E` row with white text and a white 3px rail on the left (5.08:1). This
replaced an earlier tinted active row.

Page canvas `#F8F9FA`, no desktop top bar, page padding 16/24/32px by breakpoint, 24px section gaps, max
width 1600px.

## Components

- Page banner: role-tinted, 16px radius, 20px padding, 1px tinted border, four 8px CMYK squares, a 10px
  uppercase eyebrow, title 28px/800, description 14px `light-muted-strong`, actions on the right, and one
  blurred magenta circle. Tints: owner `#FFF0F3`, operations `#E6F7FD` (manager, cashier, production),
  designer `#F0FDFA`.
- KPI card (`StatCard`): white, 16px radius, 1px border, 20px padding, 10px uppercase muted label, 22px/800
  value, optional hint line. It supports a tinted icon chip (`icon` prop, 32px, `brand-primary/10`), but no
  dashboard passes one; record it as available, not as a page rule.
- Panel (`Card`, `CardHeader`, `CardBody`, `CardFooter`): white, 16px radius, 1px border, 20px body padding,
  16px header/footer padding. Headings are caller-owned and follow the Panel heading rule above.
- Table: 10px uppercase muted headers, 1px row dividers, no zebra, 14px cells. Read-only rows are about 36px.
  Rows that contain an action render about 48px because `Button size="sm"` is 32px tall plus 8px vertical
  cell padding. Numeric columns are right-aligned and tabular, with two exceptions: the production dashboard
  queue table renders `Jml` left-aligned, and the manager materials movements panel (`Pergerakan stok
  terbaru`) renders its stock counts left-aligned. The action column is sticky on the right only at `lg:` and
  above.
- Pagination: Indonesian copy. `Menampilkan X–Y dari Z` when a total is known, `Menampilkan X dari Z` when
  the window is a single row, and `Halaman X dari Y` for lists whose total cannot be stated honestly
  (client-side filtered lists that only know the current page). Buttons are `Sebelumnya` and `Berikutnya`.
- Buttons: primary `#D01A7E` with white 14px/600 text, 12px radius, 40px height (`sm` is 32px), soft magenta
  glow; secondary white with a `#6B7280` border; danger red; ghost transparent.
- Forms: 10px/600 uppercase label, 40px control, 12px radius, 1px `#6B7280` border, white surface, 2px
  magenta focus ring at 40%.
- Dialog: centred 560px by default, 16px radius, 1px border, flat `brand-dark/55` dim, one primary and one
  secondary action.
- Chart (`Sparkline`): one magenta area series, 3px line, area gradient from 28% to transparent, three
  quarter gridlines plus a dashed zero baseline, and 3.5px point markers.
- Login: light grey canvas (`login-mesh`) with a 4rem grid mesh, a centred 448px (`max-w-md`) white card with
  the 3px brand stripe, the `LogoMark` beside the wordmark, and one primary button. This screen has no CMYK
  squares.
- `LogoMark`: rounded square on a 32px grid, magenta fill, white A with a yellow crossbar and dot.

### Banner deviations

The design source showed venue badges ("Executive Suite", "Branch Operations", and similar) in the banner
eyebrow. They were not adopted. The eyebrow is a short Indonesian context label instead: the role name on
role dashboards (`Kasir`, `Produksi`, `Desainer`, `Manajer Cabang`, `Pemilik`) and a section label
(`Konfigurasi`, `Administrasi`, `Inventaris`, `Ringkasan`) on admin pages.

## Dark mode

The design source is light-only. Dark mode is a required variant of the same system, mapped onto the
`surface-*` palette:

- Canvas `surface-900` (`#0A0A16`); page body text `gray-100`.
- Sidebar and mobile drawer `surface-800`; panels and cards `surface-700`; table headers and nested surfaces
  at `surface-600` with 60% opacity.
- Borders `surface-300`; muted text `gray-400`.
- Brand tints become the brand colour at 10% over the dark surface (`brand-primary/10`, `brand-secondary/10`,
  `teal-400/10`); the banner's blurred circle uses `brand-primary/15`. Tinted borders sit at 25%.
- Filled brand controls keep `#D01A7E` with white text.
- Dark mode never changes composition, only colour.

## Print

- `PrintHeader` renders four CMYK squares and the wordmark with `print-color-exact`, so the marks survive the
  browser's print colour handling.
- Invoice: A4 sheet, `min-h-[297mm]` on screen with `print:min-h-0`, and a 12mm print margin. The invoice is
  the single customer document and includes prices, discounts, DP history, and the remaining receivable.
- Production has no invoice or receipt print. Its work list is the production queue, which contains no money
  field. No separate ticket or money-free print mode is part of the target.
- Receipt: 80mm wide, `@page size: 80mm auto`.
- Paper is always white, including print output where the dark backdrop is dropped. The on-screen backdrop is
  `brand-dark`; `@media print` forces it white.
- Receipt totals show three rows when there is no discount (`Total`, `Dibayar`, `Sisa Tagihan`) and five with
  one (`Subtotal`, `Diskon`, `Total`, `Dibayar`, `Sisa Tagihan`). Payment lines list each DP or settlement.

## Data honesty

- Compose screens only from data the API returns. Never invent telemetry, and never render a number that
  does not reconcile.
- Loading and error states are distinct from an empty result. A failed request must never render as
  "Belum ada ..." with a zero, especially on money or performance pages. The default is the panel's spinner for
  loading, an `EmptyState` with a `Coba lagi` retry for a failed query, and the empty copy only for a successful
  empty response. Most panels follow it, including the paginated list shell `ResourceTable` and the designer
  order form's `/quotes/options` selectors, which now use the same `EmptyState` + `Coba lagi`. Failures inside
  a control stay inline instead: login and form submit errors render a red alert or field-level text because
  there is no panel to replace.
- Production screens never show price, cost, profit, discount, receivable, or payment fields. Invoice and
  receipt print are denied to production; production uses the queue.
- The production queue table shows `Area` (m²), `Operator`, and a status filter. Failed print (waste) is
  recorded from the queue row and shows a quantity, never money.
- Money is transported as decimal strings with two places and displayed through `formatMoney`; the SPA never
  submits authoritative line prices.

### Dropped from the design source

These elements appeared in the Stitch screens but were not built, because the API does not return the data
needed to state them honestly:

- Receivables aggregate on the owner dashboard.
- Manager dashboard production-queue and low-stock panels: the dashboard payload already carries three panels,
  and adding two more needs extra queries plus a policy check on the dashboard path.
- Per-branch comparison rows.
- File upload.
- Attendance and on-shift state.
- User last-active and active flag.
- Cash-drawer balance.
- Persisted cash tendered.

Order priority, design status, per-order finishing/display, and the invoice print are now target work from Sprint Review 1; they are no longer treated as permanently dropped.

### Known backend follow-ups

These are backend gaps, not UI defects:

- The cashier payment queue filters invoice status on the client, so the list can only show the page fallback
  `Halaman X dari Y`. A server-side open-status filter is needed before an honest total can be shown.
- Seeded invoices and expenses store datetimes in date columns, so `whereBetween(date, date)` excludes
  same-day seeded rows. Seeded rows disappear from daily and monthly reports until the seeder writes dates
  instead of datetimes.

## Rules and bans

- No second action hue; cyan and yellow are supporting marks.
- No dark rail, and no press-console motifs.
- No magenta or cyan at small sizes on white; use the strong variants.
- No gradients over the page, no glass panels everywhere, no fake QR codes or barcodes.
- No emojis in UI copy or documentation.
