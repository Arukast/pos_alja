# Walkthrough - POS Rebuild Phases

This walkthrough documents the verified implementations of the print shop POS rebuild project.

> [!NOTE]
> Historical logs from the initial stages of the rebuild have been archived:
> * [Archive of Phases 1 to 5](.agents/plan/rebuildPlan/walkthrough_phases_1_5.md)
> * [Archive of Phases 6 to 10](.agents/plan/rebuildPlan/walkthrough_phases_6_10.md)

---

## Phase 11: Un-Hardcoding Visual Styles & Semantic Brand Refactoring (Completed)

We have refactored the entire application to extract hardcoded brand strings, visual decorations, and literal CSS color classes (e.g. `bg-alja-magenta`, `bg-alja-cyan`) into a centralized, semantic, and configurable template design system.

### Changes Made

#### 1. Created Brand Configuration
* **Brand configuration:** [brand.php](file:///home/arukast/Projects/localDev/projects/pos_alja/config/brand.php) - Centralizes name strings (`ALJA`, `POS`), slogans, and footer copies.
* **Web Route redirect:** [web.php](file:///home/arukast/Projects/localDev/projects/pos_alja/routes/web.php) - Redirects root `/` to `/dashboard` to avoid rendering the default Laravel welcome page.

#### 2. Created Reusable Brand Blade Components
* **Brand Dots:** [brand-dots.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/brand-dots.blade.php) - Renders the signature colored dot decorations.
* **Brand Logo:** [brand-logo.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/brand-logo.blade.php) - Centralizes the short and full logo text rendering.
* **Brand Stripe:** [brand-stripe.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/brand-stripe.blade.php) - Unifies the header accent bar.
* **Glow Blobs:** [brand-glow-blobs.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/brand-glow-blobs.blade.php) - Unifies the ambient glowing background blobs.
* **Header Banner:** [brand-header-banner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/brand-header-banner.blade.php) - Unifies role-based dashboard banners, supporting dynamic light/dark mode gradients (fresh gradients in light mode and deep, rich gradients in dark mode).

#### 3. Refactored Global Layouts & Print Views
* **Layouts:** [app.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/layouts/app.blade.php) and [guest.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/layouts/guest.blade.php) updated to use `<x-brand-glow-blobs>`, `<x-brand-logo>`, and `<x-brand-stripe>`.
* **Navigation:** [navigation.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/layout/navigation.blade.php) updated to replace hardcoded CMYK top bar and logo text.
* **Print Templates:** [receipt.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/receipt.blade.php) and [ticket.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/orders/ticket.blade.php) updated to load company fullname/slogans from config and fetch dynamic, multi-branch addresses and phone numbers.

#### 4. Migrated to Semantic Design Tokens
* **Tailwind / CSS:** [app.css](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/css/app.css) updated to map color properties to semantic tokens (e.g. `brand-primary`, `brand-secondary`, `brand-accent`, `brand-dark`) instead of literal CMYK names.
* **Core Components:** Updated input elements, buttons, nav links, and dropdowns:
  * [primary-button.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/primary-button.blade.php)
  * [text-input.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/text-input.blade.php)
  * [nav-link.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/nav-link.blade.php)
  * [responsive-nav-link.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/responsive-nav-link.blade.php)
  * [dropdown-link.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/components/dropdown-link.blade.php)
* **Auth & Profiles:** Updated [login.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/pages/auth/login.blade.php) and [update-profile-information-form.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/profile/update-profile-information-form.blade.php).
* **Dashboards:** Updated [owner.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/owner.blade.php), [manager.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/manager.blade.php), [cashier.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/cashier.blade.php), [designer.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/designer.blade.php), [production.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/dashboards/production.blade.php), and [user-management.blade.php](file:///home/arukast/Projects/localDev/projects/pos_alja/resources/views/livewire/admin/user-management.blade.php) to use the new `<x-brand-header-banner>` and refactored stats, leaderboards, and charts to style dynamically with the primary brand variables.

### Verification (Phase 11)

* Visual styles compile correctly under the Tailwind CSS v4 compiler setup.
* All component files render correctly without server-side Blade execution errors.
* Multi-branch printed invoices and job tickets successfully output dynamic data, securing correct store and branch representations.
