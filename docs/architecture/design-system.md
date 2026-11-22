# Design system

Adopted from Google Stitch project `15712871753929045706` ("POS Alja — Brand Suite v2"). This file is the
contract for UI work; when code and this file disagree, treat it as a defect.

## Type

Outfit only. Tabular numerals for money and counts. Page title 28px/800 with tight tracking. Panel title
16px/700. Body 14px/400. Table 13px/400-600. Labels 10-12px/700 uppercase with wide tracking.
Money format `Rp 486.250.000`. Dates `20 Nov 2026`.

## Colour

- Mark/accent magenta `#E91E8C`; filled controls `#D01A7E` with white text; filled hover `#B0185F`; small
  magenta text `#B0185F`.
- Cyan mark `#00AEEF`; small cyan text `#00658D`. Yellow `#FFF200` for the brand stripe only, never text.
- Dark neutral `#1A1A2E`.
- Canvas `#F8F9FA`, surface `#FFFFFF`, surface-2 `#F1F3F5`, border `#E5E7EB`, ink `#111827`,
  muted `#6B7280`, muted-strong `#4B5563`.
- Status dots: positive `#059669`, warning `#D97706`, danger `#DC2626`, info `#2563EB`. Status text uses
  `#047857`, `#B45309`, `#B91C1C`, `#1D4ED8`.
- Soft shadows only. No page gradients, glass panels, or neon glows. The page banner may carry a soft tint and
  one blurred brand circle.

## Shell

240px white sidebar (64px collapsed), 1px right border, 3px top stripe of magenta 33% / yellow 22% / cyan 45%.
Sidebar header carries four CMYK squares and the `ALJA POS` wordmark. Navigation is grouped with 10px uppercase
tracking-wide muted captions, 40px rows, 12px radius, 14px/600 labels. Active row is solid `#D01A7E` with white
label and a soft magenta glow. Page canvas `#F8F9FA`, no desktop top bar, 24px page padding, 16px section gaps,
max width 1600px.

## Components

- Page banner: role-tinted, 16px radius, 20px padding, 1px tinted border, uppercase badge, three 8px brand
  squares, title 28px/800, description 14px `#4B5563`, actions on the right, one blurred brand circle.
- KPI cards: four-up, white, 16px radius, 1px border, 16px padding. 10px uppercase muted label, 22px/800
  value, tinted 12px icon chip, comparison line.
- Panels: white, 16px radius, 1px border, 20px padding. Title 16px/700 with a muted subline.
- Tables: 10px uppercase muted headers, 36px rows, 1px row dividers, no zebra. Numbers right-aligned and
  tabular. Footers read `Showing 1-N of M`.
- Status: soft pill, 6px dot, 10-11px/700 text in the strong shade.
- Buttons: primary `#D01A7E`, white 14px/700, 12px radius, 40px height, soft magenta glow; secondary white with
  1px border; text actions 12px/600 `#B0185F`.
- Forms: 10px/700 uppercase label, 40px control, 12px radius, 1px border, white, focus ring 2px magenta at 40%.
- Dialog: centred 560px, white, 16px radius, 1px border, flat dim `rgba(26,26,46,0.55)`, one primary and one
  secondary action.
- Chart: one magenta area series, 2px line, soft gradient fill, 4-5 horizontal gridlines.
- Login: light grey canvas with a subtle 4rem grid mesh, centred 448px, CMYK squares, white card with a brand
  stripe on top.
- Print artifacts: A4 or 80mm white paper on a `#1A1A2E` backdrop, soft shadow, Outfit, CMYK squares.

## Dark mode

The design source is light-only. Dark mode is a required variant of this same system, mapped onto the existing
`surface-*` palette: canvas becomes `surface-900`, surfaces become `surface-700/800`, borders `surface-400`,
text `gray-100/400`, and tints become the brand colour at 10% over the dark surface. Dark mode never changes
composition, only colour.

## Rules and bans

- Compose screens only from data the API returns. Never invent telemetry, and never render numbers that do not
  reconcile.
- Production screens never show price, cost, profit, discount, receivable, or payment fields.
- No second action hue; cyan and yellow are supporting marks.
- No dark rail, and no press-console motifs.
- No magenta or cyan at small sizes on white; use the strong variants.
- No gradients over the page, no glass panels everywhere, no fake QR codes or barcodes.
