# Master Design Plan — POS Alja Frontend Redesign

## Overview

This plan governs a full frontend redesign of the POS Alja application.
The redesign converts the current top-navbar layout into a professional
collapsible sidebar layout, applies the brand color palette derived from the
ALJA Digital Printing logo, and rebuilds all dashboard components from scratch
with a cohesive, maintainable design system.

All changes are purely frontend (Blade/CSS/Alpine). No backend logic,
migrations, or routes are altered unless a new route is explicitly required to
support a new sidebar section.

> NOTE FOR NEW THREADS: This is the master reference document for the frontend
> redesign. Do NOT execute from this file directly. Read the Thread Instructions
> section below, then load the relevant phase sub-plan from `.agents/plan/phases/`.

---

## Thread Instructions

This master plan is designed to be consumed by multiple separate agent threads,
one per phase. Each thread must follow this workflow:

### Step 1 — Files to Read First (every thread, every phase)

1. `.agents/AGENTS.md` — project-wide rules and constraints (mandatory).
2. `.agents/plan/master_desain_plan.md` — this file (big-picture context).
3. `.agents/plan/walkthrough.md` — understand what has already been completed.
4. The relevant phase sub-plan: `.agents/plan/phases/phase-N-<name>.md`.

### Step 2 — Hard Constraints (every thread must enforce)

- Do NOT edit or create `tailwind.config.js`. All Tailwind config goes in
  `resources/css/app.css` under the `@theme` directive.
- Do NOT modify any PHP backend files (`app/Livewire/*.php`, `app/Services/*.php`,
  migrations, models, routes) unless the phase sub-plan explicitly instructs it.
- Do NOT install new Composer or NPM packages unless explicitly required.
- All Blade indentation must use 4 spaces. YAML/Markdown use 2 spaces.
- Maintain Blade formatter safety rules (no inline `@if` on the same line as
  text content; use ternary `{{ $condition ? $value : '' }}` instead).
- All commands must be run inside the Docker container:
  `docker exec -it -w /var/www/html/pos_alja local-php83 <command>`.

### Step 3 — After Completing a Phase

1. Run `npm run build` inside the container to compile assets.
2. Run `php artisan test` inside the container — all 54 tests must remain green.
3. Append a summary of changes to `.agents/plan/walkthrough.md` under a new
   dated section titled with the phase name.

### Phase Sub-Plan File Map

| Phase | File |
| :--- | :--- |
| Phase 1 — CSS Design System | `.agents/plan/phases/phase-1-css.md` |
| Phase 2 — Layout Shell | `.agents/plan/phases/phase-2-layout.md` |
| Phase 3 — Shared Components | `.agents/plan/phases/phase-3-components.md` |
| Phase 4 — Dashboard Rebuilds | `.agents/plan/phases/phase-4-dashboards.md` |
| Phase 5 — Supporting Views | `.agents/plan/phases/phase-5-supporting.md` |

---

## Phase Sub-Plan Template

Each file in `.agents/plan/phases/` must follow this structure:

```markdown
# Phase N — <Name>

## Goal
One-paragraph description of what this phase achieves.

## Files to Read First
List of specific files to read before starting work.

## Constraints
Any phase-specific constraints beyond the global ones in master_desain_plan.md.

## Step-by-Step Tasks
Ordered list of files to create or modify, with a brief description per file.

### [MODIFY] path/to/file.blade.php
What to change and why.

## Verification
Commands and manual checks to confirm the phase is complete.

## Walkthrough Update
Template for the walkthrough.md entry to append after completion.
```

---

## Design Decisions (Interview Summary)

| Decision | Choice |
| :--- | :--- |
| Sidebar structure | Collapsible — full labels expanded (240 px), icon-only collapsed (64 px) |
| Role navigation | Each role has its own dedicated sidebar menu |
| Sidebar routing | In-page tab switching (same Livewire component, sidebar changes `$activeTab`) |
| Light mode sidebar | White/light-gray background, brand pink (#E91E8C) as accent |
| Dark mode sidebar | Deep navy surface-800 background |
| Active item style | Brand pink pill/highlight with white text |
| User info placement | Logo at top of sidebar; user avatar + name + role badge + logout at bottom |
| Theme toggle placement | Inside the sidebar footer (next to user info), no separate topbar |
| Redesign depth | Full rebuild — layout shell AND all dashboard card/table/form/component visuals |
| CSS approach | Tailwind @theme tokens only; remove raw .brand-* CSS utility classes |
| Mobile behavior | Sidebar becomes a slide-in drawer from the left; hamburger toggle in topbar strip |

---

## Brand Identity & Maintainability

All brand identity — text, colors, and logo image — is centralized in
`config/brand.php` and driven from `.env`. A complete rebrand requires **zero
Blade or CSS file edits**.

### Rebrand Checklist

| What changes | Action required | Files touched |
| :--- | :--- | :--- |
| Company name / slogan / footer | Update `.env` values | None |
| Logo colors | Update `BRAND_COLOR_*` in `.env` | None |
| Logo image | Set `BRAND_LOGO_PATH` in `.env` + drop file in `public/` | None |
| Logo SVG shape (complex rebrand) | Replace file in `public/images/` | None |

### config/brand.php Structure

```php
'name'     => env('BRAND_NAME',     'ALJA'),
'subname'  => env('BRAND_SUBNAME',  'POS'),
'fullname' => env('BRAND_FULLNAME', 'ALJA Digital Printing'),
'slogan'   => env('BRAND_SLOGAN',   '...'),
'footer'   => env('BRAND_FOOTER',   '...'),

// Brand colors — injected as CSS custom properties at runtime
'colors' => [
    'primary'   => env('BRAND_COLOR_PRIMARY',   '#E91E8C'),
    'secondary' => env('BRAND_COLOR_SECONDARY', '#00AEEF'),
    'accent'    => env('BRAND_COLOR_ACCENT',    '#FFF200'),
    'dark'      => env('BRAND_COLOR_DARK',      '#1A1A2E'),
],

// Logo image — null falls back to inline text+dots rendering
'logo' => env('BRAND_LOGO_PATH', null),
```

### How Colors Are Applied

The `colors` array is injected as inline CSS custom properties on the `<html>`
element inside `layouts/app.blade.php`. This overrides the `@theme` defaults
in `app.css` **at runtime without a rebuild**:

```html
<html style="
    --color-brand-primary:   {{ config('brand.colors.primary') }};
    --color-brand-secondary: {{ config('brand.colors.secondary') }};
    --color-brand-accent:    {{ config('brand.colors.accent') }};
    --color-brand-dark:      {{ config('brand.colors.dark') }};
">
```

Tailwind's generated utility classes (`bg-brand-primary`, `text-brand-primary`,
etc.) reference these CSS variables, so they automatically reflect any `.env`
override.

### How the Logo Image Is Applied

`brand-logo.blade.php` checks `config('brand.logo')` at render time:

```blade
@if (config('brand.logo'))
    <img src="{{ asset(config('brand.logo')) }}"
         alt="{{ config('brand.fullname') }}"
         class="h-8 w-auto object-contain">
@else
    {{-- Fallback: inline text + brand dots decoration --}}
    <x-brand-dots />
    <span ...>{{ $brandName }}<span class="text-brand-primary">{{ $brandSubname }}</span></span>
@endif
```

## Brand Color Palette (Defaults)

Derived from the ALJA Digital Printing logo (CMYK printing colors + brand black).
Default values live in `@theme` in `resources/css/app.css` and are overridden
at runtime via `config/brand.php` + `.env`.

```
Primary (Magenta/Pink)  — #E91E8C   ->  --color-brand-primary
Cyan (Secondary)        — #00AEEF   ->  --color-brand-secondary
Yellow (Accent)         — #FFF200   ->  --color-brand-accent
Black (Brand Dark)      — #1A1A2E   ->  --color-brand-dark
```

### Light Mode Surface Tokens

```
--color-light-bg          : #F8F9FA   (page background)
--color-light-surface     : #FFFFFF   (card/panel background)
--color-light-surface-2   : #F1F3F5   (secondary panel / table row alt)
--color-light-border      : #E5E7EB   (dividers, borders)
--color-light-text        : #111827   (primary text)
--color-light-muted       : #6B7280   (muted/secondary text)
```

### Dark Mode Surface Tokens (existing, retained)

```
--color-surface-900  : #0A0A16
--color-surface-800  : #0D0D1A  (page background)
--color-surface-700  : #111122  (sidebar, nav)
--color-surface-600  : #141428  (cards, panels)
--color-surface-500  : #1A1A32
--color-surface-400  : #22223D  (hover states)
--color-surface-300  : #2A2A48  (borders)
--color-surface-200  : #333358
--color-surface-100  : #3D3D65
```

---

## Typography

- **Font family:** `Outfit` (already loaded via Google Fonts — keep as-is)
- **Scale:** Use Tailwind's default type scale (`text-xs` through `text-2xl`)
- **Weights in use:** 300 (light), 400 (body), 500 (medium), 600 (semibold), 700 (bold), 800 (extra-bold)

---

## Layout Architecture

### Desktop (>= 1024 px)

```
+-------------------+--------------------------------------------------+
|  SIDEBAR (240px)  |   MAIN CONTENT AREA (flex-1, scrollable)         |
|                   |                                                  |
|  [Logo + Name]    |   [Page Heading / breadcrumb strip]              |
|                   |                                                  |
|  [Nav Group]      |   [Dashboard Cards / Tables / Forms]             |
|  > Item           |                                                  |
|  > Item (active)  |                                                  |
|  > Item           |                                                  |
|                   |                                                  |
|  [User Footer]    |                                                  |
|  Avatar Name Role |                                                  |
|  [Theme Toggle]   |                                                  |
|  [Logout]         |                                                  |
+-------------------+--------------------------------------------------+
```

Collapsed state: sidebar shrinks to 64 px, only icons are visible, a tooltip
on hover shows the label.

### Mobile (< 1024 px)

- A thin topbar (48 px tall) holds the hamburger icon and the app logo.
- Sidebar slides in as a drawer (full height, 240 px wide) from the left,
  overlaying the content with a semi-transparent backdrop.
- Main content is always full-width when the drawer is closed.

---

## Sidebar Content per Role

### owner

| Group | Items |
| :--- | :--- |
| Overview | Dashboard (Financial Overview) |
| Insights | Designer Leaderboard, Audit Trail |
| Administration | User Management |
| Account | Profile & Settings |

### manager

| Group | Items |
| :--- | :--- |
| Overview | Dashboard |
| Inventory | Materials & Stock, Stock Ledgers |
| Configuration | Display & Finishing, Pricing Matrices |
| Administration | User Management (branch-scoped: view/edit all users in own branch, password reset — excludes owner-level accounts) |
| Account | Profile & Settings |

### cashier

| Group | Items |
| :--- | :--- |
| Overview | Dashboard |
| Operations | Payment Queue, Pickup Queue, Expenses |
| Account | Profile & Settings |

### designer

| Group | Items |
| :--- | :--- |
| Overview | Dashboard |
| Orders | New Order, Order List |
| Account | Profile & Settings |

### production

| Group | Items |
| :--- | :--- |
| Overview | Dashboard |
| Operations | Print Queue, Waste Logging |
| Account | Profile & Settings |

---

## Component Design System

### Sidebar Component (`livewire/layout/navigation.blade.php` rebuilt)

- Alpine.js `x-data` controls `sidebarOpen` (collapsed/expanded) and
  `mobileOpen` (drawer open/close).
- Logo area: brand logo SVG + "ALJA" text label (hidden when collapsed).
- Nav group headers: uppercase, `text-xs`, muted color, hidden when collapsed.
- Nav items: `flex items-center gap-3 px-3 py-2.5 rounded-xl transition`
  - Active: `bg-brand-primary text-white`
  - Hover: `hover:bg-brand-primary/10 hover:text-brand-primary`
  - Collapsed: only icon visible, label hidden; tooltip on hover.
- Sidebar footer: avatar circle (initials-based) + name + role badge + theme
  toggle icon button + logout icon button (all collapse to icons-only).
- Theme toggle: sun/moon icon button, switches `dark` class on `<html>` and
  persists to `localStorage`. Light mode is the default.

### App Layout (`layouts/app.blade.php` rebuilt)

- Full-screen flex layout: `flex h-screen overflow-hidden`.
- Sidebar is a fixed-height left column.
- Main area is `flex-1 overflow-y-auto` with a page-level padding.
- Mobile topbar rendered only below `lg` breakpoint.
- Theme init script retains the existing localStorage-based approach but
  defaults to `light` if no preference is stored.

### Cards

```
Light:  bg-white border border-light-border shadow-sm rounded-2xl p-5
Dark:   bg-surface-600 border border-surface-300/40 rounded-2xl p-5
Hover:  shadow-md translate-y-[-2px] transition-all duration-200
```

### Stat / KPI Cards

- Icon container: rounded-xl, brand primary or secondary bg at 10% opacity,
  icon in brand primary or secondary color.
- Value: `text-2xl font-bold`.
- Label: `text-xs font-medium` muted.
- Trend badge: green (up) / red (down) with small arrow icon.

### Tables

```
Light:  bg-white border border-light-border rounded-2xl overflow-hidden
Dark:   bg-surface-600 border border-surface-300/40 rounded-2xl

Thead:  bg-light-surface-2 / bg-surface-500  — text-xs uppercase muted
Tbody rows: hover:bg-brand-primary/5
Alternating: even rows get subtle tint
```

### Buttons

| Variant | Style |
| :--- | :--- |
| Primary | `bg-brand-primary hover:bg-brand-primary-hover text-white rounded-xl px-4 py-2` |
| Secondary | `bg-brand-secondary hover:bg-brand-secondary-hover text-white rounded-xl px-4 py-2` |
| Outline | `border border-brand-primary text-brand-primary hover:bg-brand-primary/10 rounded-xl` |
| Danger | `bg-red-500 hover:bg-red-600 text-white rounded-xl` |
| Ghost | `text-gray-500 hover:bg-gray-100 dark:hover:bg-surface-400/50 rounded-xl` |

### Modals

- Backdrop: `bg-black/50 backdrop-blur-sm`
- Panel light: `bg-white rounded-2xl shadow-2xl`
- Panel dark: `bg-surface-600 rounded-2xl shadow-card`
- Header: brand stripe bar (3-color) at top edge of modal

### Badges / Status Pills

| Status | Color |
| :--- | :--- |
| Paid (status=2) | `bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400` |
| DP (status=1) | `bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400` |
| Unpaid (status=0) | `bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400` |
| Pending order | `bg-gray-100 text-gray-600 dark:bg-surface-400 dark:text-gray-300` |
| Processing | `bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400` |
| Completed | `bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400` |

### Form Inputs

- `rounded-xl border border-light-border dark:border-surface-300/50`
- Focus: `ring-2 ring-brand-primary/30 border-brand-primary`
- Background: `bg-white dark:bg-surface-500`
- Use `@tailwindcss/forms` plugin (already installed)

---

## CSS Design Token Strategy (`resources/css/app.css`)

All brand configuration lives in the `@theme` block. The only raw CSS kept is
for custom scrollbar styling and the `brand-stripe-bar` gradient (which cannot
be expressed as a pure utility). All other `.brand-*` classes are removed.

### Token Naming Convention

```
--color-brand-*         -> brand identity colors
--color-light-*         -> light mode surface tokens (NEW)
--color-surface-*       -> dark mode surface tokens (existing)
--shadow-*              -> shadow/glow presets
--animate-*             -> animation shorthand
```

> NOTE: Tailwind CSS v4 is in use. Do NOT create or modify `tailwind.config.js`.
> All tokens go into `@theme` inside `resources/css/app.css`.

---

## File Change Map

### Phase 1 — Design System Foundation

| Action | File |
| :--- | :--- |
| MODIFY | `resources/css/app.css` |

Rebuilds the `@theme` block with new light mode tokens, cleans up raw CSS
utility classes, keeps only what cannot be expressed as Tailwind utilities.

### Phase 2 — Layout Shell Rebuild

| Action | File | Purpose |
| :--- | :--- | :--- |
| MODIFY | `resources/views/layouts/app.blade.php` | Sidebar + main flex layout |
| MODIFY | `resources/views/livewire/layout/navigation.blade.php` | Full sidebar component rebuild |

Introduces Alpine.js-controlled collapsible sidebar with:
- Per-role menu rendering using `@auth` + role checks
- Mobile drawer behavior
- Theme toggle in sidebar footer
- User info (avatar initials, name, role badge, logout)

### Phase 3 — Shared Component Rebuild

| Action | File | Purpose |
| :--- | :--- | :--- |
| MODIFY | `resources/views/components/modal.blade.php` | Redesigned modal panels |
| MODIFY | `resources/views/components/primary-button.blade.php` | New button system |
| MODIFY | `resources/views/components/secondary-button.blade.php` | New button system |
| MODIFY | `resources/views/components/danger-button.blade.php` | New button system |
| MODIFY | `resources/views/components/text-input.blade.php` | Rounded inputs |
| MODIFY | `resources/views/components/input-label.blade.php` | Consistent label style |
| MODIFY | `resources/views/components/input-error.blade.php` | Error message style |
| MODIFY | `resources/views/components/dropdown.blade.php` | Styled dropdown panels |
| MODIFY | `resources/views/components/dropdown-link.blade.php` | Dropdown item style |
| MODIFY | `resources/views/components/brand-logo.blade.php` | Ensure logo renders in sidebar context |
| MODIFY | `resources/views/components/brand-header-banner.blade.php` | Adapt or remove — sidebar replaces the banner header |
| MODIFY | `resources/views/components/brand-stripe.blade.php` | Keep as accent element |

### Phase 4 — Dashboard Rebuilds

Each dashboard blade is fully rebuilt visually. Backend Livewire PHP class
logic is untouched.

| Action | File |
| :--- | :--- |
| MODIFY | `resources/views/livewire/dashboards/owner.blade.php` |
| MODIFY | `resources/views/livewire/dashboards/manager.blade.php` |
| MODIFY | `resources/views/livewire/dashboards/cashier.blade.php` |
| MODIFY | `resources/views/livewire/dashboards/designer.blade.php` |
| MODIFY | `resources/views/livewire/dashboards/production.blade.php` |

### Phase 5 — Supporting Views

| Action | File | Purpose |
| :--- | :--- | :--- |
| MODIFY | `resources/views/livewire/admin/user-management.blade.php` | Consistent card/table style |
| MODIFY | `resources/views/profile.blade.php` | Consistent profile page style |
| MODIFY | `resources/views/dashboard.blade.php` | Role-routing hub (unchanged logic) |
| REVIEW | `resources/views/orders/receipt.blade.php` | Print-optimized — review for print styles only |
| REVIEW | `resources/views/livewire/pages/auth/*` | Auth pages — keep guest layout |

---

## Accessibility & Quality Requirements

- All interactive elements must have descriptive `id` attributes (for browser testing).
- Color contrast must meet WCAG AA minimum (4.5:1 for normal text).
- Sidebar collapse state must be persisted in `localStorage` so it survives page
  navigation (Livewire wire:navigate).
- Focus ring styles must be visible in both light and dark modes.
- All icon-only states (collapsed sidebar, mobile drawer closed) must include
  `title` or `aria-label` attributes on buttons.

---

## Execution Order

1. Phase 1: CSS design token foundation (`app.css`)
2. Phase 2: Layout shell (`layouts/app.blade.php` + `navigation.blade.php`)
3. Phase 3: Shared Blade components
4. Phase 4: Dashboard blade rebuilds (owner -> manager -> cashier -> designer -> production)
5. Phase 5: Supporting views
6. Final: Run `npm run build` inside container to compile assets, then run
   the full test suite to verify no regressions.

---

## Verification Plan

### Build Check

```bash
docker exec -it -w /var/www/html/pos_alja local-php83 npm run build
```

### Automated Tests (no regressions)

```bash
docker exec -it -w /var/www/html/pos_alja local-php83 php artisan test
```

### Manual Verification Checklist

- [ ] Light mode default loads correctly (no flash of dark mode).
- [ ] Dark mode toggle persists after page reload.
- [ ] Sidebar collapses to icon-only; tooltip shows on hover.
- [ ] Sidebar state persists across Livewire `wire:navigate` transitions.
- [ ] Mobile: hamburger opens drawer, clicking outside closes it.
- [ ] Owner: sidebar shows owner menu items only.
- [ ] Manager: sidebar shows manager menu items only.
- [ ] Cashier/Designer/Production: same role-isolation check.
- [ ] All modals open/close correctly in both light and dark modes.
- [ ] Receipt and ticket print views unaffected.
- [ ] WCAG contrast check on primary text in both themes.
