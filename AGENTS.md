# AGENTS.md

## Section 1: Agent Operational Workflow

### 1.1 Startup Workflow
Before writing any code, the agent must complete these steps:
1. Confirm the working directory using `pwd`.
2. Read `claude-progress.md` for the latest verified state and the next required step.
3. Read `feature_list.json` and select the highest-priority unfinished feature.
4. Review recent repository history by checking the last 5 commits using `git log --oneline -5`.
5. Run the initialization script via `./init.sh`.
6. Run the required smoke or end-to-end verification before starting new work. If baseline verification fails, fix that issue first before stacking new feature work on top of a broken starting state.

### 1.2 Working Rules
1. Focus entirely on one feature at a time.
2. Do not mark a feature complete just because code was added.
3. Keep all changes within the selected feature scope unless a critical blocker forces a narrow supporting fix.
4. Do not silently alter verification rules during implementation.
5. Prefer durable repository artifacts over transient chat summaries.

### 1.3 Required Artifacts
1. `feature_list.json`: The absolute source of truth for tracking feature states.
2. `claude-progress.md`: The continuous session log and current verified status.
3. `init.sh`: The standardized repository startup and verification path.
4. `session-handoff.md`: An optional, compact handoff document reserved for larger development sessions.

### 1.4 Definition Of Done
A feature achieves the status of completed only when all of the following conditions are met:
1. The target behavior is fully implemented.
2. The required verification scripts actually executed successfully.
3. Direct evidence of verification is explicitly recorded in `feature_list.json` or `claude-progress.md`.
4. The repository remains completely restartable from the standard startup path using `./init.sh`.

### 1.5 End Of Session Workflow
Before ending a session, the agent must perform these tasks:
1. Update the contents of `claude-progress.md`.
2. Update the tracking states within `feature_list.json`.
3. Explicitly record any unresolved risk, technical debt, or workflow blocker.
4. Commit the changes with a descriptive message adhering to the Conventional Commits specification once the work is in a safe state.
5. Leave the repository clean enough for the next session to execute `./init.sh` immediately.

## Section 2: General Project Rules

### 2.1 Framework & Stack
1. This project is built using Laravel 13, PostgreSQL, Livewire 4, Tailwind CSS v4 (configured via Vite plugin and CSS `@theme` variables, no longer using `tailwind.config.js`), and Laravel Reverb.

### 2.2 Privacy & Security
1. Do not hardcode personal user data in the source code, comments, or documentation. Always use generic names (e.g., "Generic Print Shop", "admin_owner", "test@example.com").
2. SQL Injection Prevention: Avoid raw SQL queries. Always utilize Eloquent ORM, Query Builder parameter bindings, or safe input sanitization.
3. XSS Prevention: Escape dynamic parameters in templates using double curly braces `{{ $value }}`. For inline JavaScript contexts, always use `\Illuminate\Support\Js::from($value)` to prevent code breaks or injections.
4. CSRF Protection: Secure all post/put form requests with standard CSRF middleware using `@csrf` directives.

### 2.3 Language & Naming Conventions
1. Maintain international coding standards by translating legacy Indonesian domain terms to English. All code structures (variables, methods, database tables, models) must strictly be in English.
2. Financial Metrics Naming at the Invoice Level: Use `grand_total` (Omset), `total_cost` (HPP), and `total_profit` (Laba).
3. Financial Metrics Naming at the Order Level: Use `subtotal` (Subtotal), `cost` (HPP), and `profit` (Laba).
4. Material Waste: Use the `WastedMaterial` model and `wasted_materials` database table (Bahan Gagal).

### 2.4 Role-Based Access Control
1. Strict role segregation applies with native English database values: `owner`, `manager`, `cashier`, `designer`, and `production`. Ensure gates and middleware enforce these boundaries.
2. Profile & Settings Rule: Only `owner` and `manager` roles are authorized to update user profiles (name and email). All other roles can only view their profile information and update their personal passwords.
3. Owner Dashboard Responsibilities: Cross-branch financial analytics (Omset/HPP/Laba, Expenses), period filters (Daily, Weekly, Monthly), branch selection toggle, and sparkline charts mapping net profits.
4. Manager Dashboard Responsibilities: Material inventory management, stock ledgers, display/finishing master data, pricing engine matrices, and user profile management.
5. Cashier Dashboard Responsibilities: Checkouts, down payments (DP), balance settlements, cashier drawer expenses, receipt printing, and customer pickup queue (`pickup_status = 0` / Not Picked Up, and invoice `status = 2` / Paid).
6. Designer Dashboard Responsibilities: Customer select/creation wizard, parameter inputs (sizes, dimensions, options), real-time pricing calculator preview, and printable job tickets (Work Order sheets).
7. Production Dashboard Responsibilities: Print queue lists, printer machine assignments, waste logging, and updating job status to completed (which must decrement stock ledgers).

### 2.5 Real-Time Reactivity
1. When data state changes (e.g., creating an order, updating payment, completing a print job), ensure `OrderUpdated` or equivalent WebSocket broadcast events are dispatched to keep Livewire UI components in sync without manual page reloads.

### 2.6 Aesthetics & UI
1. Prioritize visual excellence. Build dynamic, responsive designs with smooth interactions (micro-animations, modern typography). Maintain styling conventions primarily through Tailwind CSS instead of inline styles. Use a sidebar navigation layout instead of a top navigation bar for the frontend dashboard interface.

### 2.7 Service-Oriented Architecture (API-Ready)
1. Core business logic must reside in isolated PHP Service classes located in the `app/Services/` directory and utilize constructor dependency injection within Livewire components. Livewire components or controllers must call these services rather than implementing business logic directly.
2. `PricingService`: Handles complex pricing matrix matching by product category parameters (Merchandise, Large Format, Print Kertas, etc.).
3. `InventoryService`: Manages stock ledger adjustments and logging material waste.

### 2.8 Multi-Branch Tenancy
1. The application supports multiple physical branches. Tables containing branch-specific data must include a `branch_id` foreign key.
2. Ensure queries and dashboards scope data to the active user's branch (`auth()->user()->branch_id`) to prevent cross-branch data leakage, unless explicitly handled for the owner role (e.g., the owner dashboard can view all branches combined or switch active branches).
3. Restrict access to ticket and receipt routes (`/orders/{invoice}/ticket` and `/orders/{invoice}/receipt`) using middleware/policies to block cross-branch data access for non-owner roles.

### 2.9 Theme Support (Light/Dark Mode) & Tailwind CSS v4 Styling
1. Ensure UI components support both light and dark modes dynamically using Tailwind's `dark:` variant and `localStorage` state.
2. All interactive elements, dropdowns, inputs, text labels, and modal backgrounds must use responsive Tailwind classes (e.g. `bg-white dark:bg-surface-600 border border-gray-200 dark:border-surface-300/50`) to ensure proper readability and contrast in both themes. Avoid hardcoding absolute dark/light background colors.
3. Tailwind CSS v4 Configuration Rule: Do NOT edit or create `tailwind.config.js`. Any custom theme configurations, fonts, brand colors, keyframes, and animations must be defined directly in `resources/css/app.css` using the `@theme` directive.

## Section 3: Technical & Database Specifications

### 3.1 Database Status Codes
1. Invoice status codes (`invoices.status`): `0` = Unpaid / Pending DP (Belum Lunas), `1` = Down Payment Paid (DP), `2` = Fully Paid (Lunas).
2. Invoice pickup codes (`invoices.pickup_status`): `0` = Not Picked Up (Belum), `1` = Picked Up / Done (Done).
3. Order status codes (`orders.status`): `0` = Pending / Not Processed (Belum di Proses), `1` = Processing / Printing (Sedang di Proses), `2` = Completed (Telah Beres).

### 3.2 Database Transaction Safety
1. Any service method performing multiple database mutations must be wrapped entirely within an atomic `DB::transaction()` block.

### 3.3 Structured Error Logging
1. All caught domain exceptions must be logged via `Log::error()` with structured contextual metadata, including the active `user_id` and `branch_id`, before throwing or flashing the error.

### 3.4 Testing & Database Portability (PHPUnit 12)
1. The automated test environment utilizes an SQLite in-memory database (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), while production uses PostgreSQL. All migrations, queries, and Eloquent interactions must remain dialect-agnostic and fully compatible with SQLite. Do not use PostgreSQL-specific or MariaDB-specific raw query statements.
2. UUIDv7 Assertions & Key Generation: Since all keys in the new database schema are UUIDs, all unit and feature tests must mock records and assert database states using valid UUID strings (`Laravel Str::uuid()`) rather than auto-incrementing integers. All model factories must explicitly generate primary and foreign key attributes using stringified UUIDs `(string) Str::uuid()`.
3. Branch Scoping Rule Tests: All new features must include feature tests asserting active branch isolation scoping rules.

### 3.5 WebSocket Broadcast Structure
1. Real-time updates rely on the `App\Events\OrderUpdated` event class broadcasting to a public channel named `orders`. Livewire dashboards should register Echo listeners (e.g., `protected $listeners = ['echo:orders,OrderUpdated' => '$refresh'];`) to automatically update UI states.

## Section 4: Development, Coding, & DevOps Best Practices

### 4.1 Code Modularity & View Architecture (Anti-Bloat Rule)
1. Keep the codebase strictly modular by breaking down views into small, focused files. Main dashboard blade templates must act exclusively as structural layout orchestrators and should not exceed 150 lines of code. Massive role-specific templates must be decomposed using specialized Livewire v4 and Tailwind v4 atomic patterns.
2. Blade Partials (`@include`): Extract heavy, single-use layout blocks that share the parent component's state context (such as sidebars, navigation layout shells, and groups of system modals) into dedicated partials nested by role namespace (e.g., `resources/views/livewire/cashier/partials/_checkout_sidebar.blade.php`).
3. Anonymous Components (`<x-`): Convert highly repetitive UI elements (such as the Cashier's item grid cards, the Owner's financial metric tiles, or Production's status badges) into reusable presentation components within `resources/views/components/`.
4. Livewire v4 Islands (`@island`): Isolate computationally heavy or high-frequency rendering features to prevent system-wide DOM-morphing overhead. Wrap high-overhead components—such as the Owner's cross-branch financial analytics graphs, the Designer's real-time pricing calculator preview, and the Production print queue tables—directly in `@island` directives.
5. Tailwind v4 Responsive Utilities: Utilize native Tailwind v4 container queries (`@container` and container variants like `@lg:`) on shared widgets so they adapt structurally to whichever dashboard column grid they occupy. Always leverage native `data-loading:` modifiers for asynchronous transitions and UI skeleton states instead of custom Alpine.js loading toggles.

### 4.2 Environment Variables & Secrets
1. Always use environment variables. API keys, passwords, and tokens should never appear directly in your code. Store them in a `.env` file and verify it is listed in `.gitignore`. Never hardcode or commit `.env` file credentials. When introducing new configurations or environment dependencies, append placeholder entries to `.env.example` immediately.

### 4.3 Database Migrations
1. Never edit migrations that have already been merged or deployed. Create a new migration file for schema adjustments. Keep all schema definitions dialect-agnostic so they execute cleanly on both SQLite and PostgreSQL.

### 4.4 Vite Asset Management
1. Propose running `npm run build` (or keeping `npm run dev` running) after editing layout files, CSS styles, or configuration parameters to ensure changes reflect properly in the browser.

### 4.5 Coding Standards & Formatting
1. Adhere strictly to PSR-12 standards for PHP classes and code structure.
2. Require `declare(strict_types=1);` at the top of every PHP class file, along with mandatory explicit argument types and return type-hints for all methods.
3. Indent PHP files, Blade views, and JS templates using 4 spaces. For YAML and Markdown files, use 2 spaces.
4. Livewire Separation of Concerns & Input Validation: Maintain a clean separation between backend and frontend files by using class-based Livewire components. Controller logic must reside in `app/Livewire/` and frontend templates in `resources/views/livewire/` (avoid single-file Volt components). All multi-input forms must utilize Livewire Form Objects to isolate validation rules and properties.
5. Blade Template Formatting & Formatter Safety: Avoid using inline Blade conditional tags (like `@if ... @endif`) on the same line as HTML text content. Instead, use inline PHP ternary expressions inside double curly braces: `{{ $condition ? $value : '' }}`.
6. Ensure PHP arrow operators (`->`) in Blade views are never split across multiple lines. Keep the variable, operator, and method call together on a single line.
7. HTML Tag Attributes: To prevent Prettier from splitting the PHP arrow operator (`->`) across lines inside HTML tags, declare merged attributes within a `@php` block first, then echo the variable (e.g. `{{ $mergedAttributes }}`) on the tag.
8. Prettier Ignore: Use `<!-- prettier-ignore -->` comments before HTML tags (like `<p>`) where inline blade directives (e.g. `@if`) or long string concatenations must be kept verbatim to prevent line wrapping from splitting the directive name from its condition.

### 4.6 Container Network Ports
1. Reverb runs internally on container port `8080`, mapped externally to host port `8084` to prevent conflict with other services. Always ensure WebSocket client URLs connect through the host mapped port.

### 4.7 Dependency Management
1. Avoid installing external PHP or JavaScript packages unless explicitly requested or absolutely necessary. Rely on core Laravel 13, Livewire 4, Alpine, and Tailwind utilities.
2. When dependencies are added or updated, commit both the package definition files (`composer.json`, `package.json`) and their lock files (`composer.lock`, `package-lock.json`) together.
3. Verify container and PHP system extension requirements (e.g., the `pcntl` extension needed for Laravel Reverb signal handling) before introducing new packages.

### 4.8 Git Commit Conventions
1. All git commit messages generated by the agent must strictly follow the Conventional Commits specification (e.g., `feat:`, `fix:`, `chore:`).

### 4.9 Workflow Instructions
1. Assume the project is run and tested inside a Docker Compose container stack. All PHP and Artisan commands must be proposed to run inside the container (e.g. `docker exec -it -w /var/www/html/pos_alja local-php83 php artisan <command>`).
2. Avoid proposing `sudo` or interactive command prompts unless requested.
3. Documentation style: Do NOT use emojis. Ensure technical guides provide complete, end-to-end instructions for both installation and post-installation usage.
4. Automated Deployment Guidelines: Enforce production asset compilation via `npm run build`. Follow a strict optimization caching command order during deployments. Ensure safe zero-downtime Reverb worker reloads via signals rather than dropping the container infrastructure.
