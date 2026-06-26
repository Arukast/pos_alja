# AGENTS.md

## General Project Rules
- **Framework & Stack:** This project is built using Laravel 13, PostgreSQL, Livewire 4, Tailwind CSS v4 (configured via Vite plugin and CSS `@theme` variables, no longer using `tailwind.config.js`), and Laravel Reverb.
- **Privacy & Security:** Do not hardcode personal user data in the source code, comments, or documentation. Always use generic names (e.g., "Generic Print Shop", "admin_owner", "test@example.com").
  - **SQL Injection Prevention:** Avoid raw SQL queries. Always utilize Eloquent ORM, Query Builder parameter bindings, or safe input sanitization.
  - **XSS Prevention:** Escape dynamic parameters in templates using double curly braces `{{ $value }}`. For inline JavaScript contexts, always use `\Illuminate\Support\Js::from($value)` to prevent code breaks or injections.
  - **CSRF Protection:** Secure all post/put form requests with standard CSRF middleware (e.g. `@csrf` directives).
- **Language & Naming Conventions:** Maintain international coding standards by translating legacy Indonesian domain terms to English. All code structures (variables, methods, database tables, models) must strictly be in English.
  - **Financial Metrics Naming:**
    - **Invoice level:** `grand_total` (Omset), `total_cost` (HPP), `total_profit` (Laba).
    - **Order level:** `subtotal` (Subtotal), `cost` (HPP), `profit` (Laba).
  - **Material Waste:** Use `WastedMaterial` model and `wasted_materials` database table (Bahan Gagal).
- **Role-Based Access Control:** Strict role segregation applies with English names: `owner`, `manager`, `cashier`, `designer`, and `production`. These role strings are natively stored in the database. Ensure gates and middleware enforce these boundaries.
  - **Profile & Settings Rule:** Only `owner` and `manager` are authorized to update user profiles (name and email). All other roles can only view their profile info and update their passwords.
  - **Role-Specific Dashboard Responsibilities:**
    - **Owner:** Cross-branch financial analytics (Omset/HPP/Laba, Expenses), period filters (Daily, Weekly, Monthly), branch selection toggle, individual designer performance leaderboard, and dynamic SVG sparkline charts mapping net profits.
    - **Manager:** Material inventory management, stock ledgers, display/finishing master data, pricing engine matrices, and user profile management.
    - **Cashier:** Checkouts, down payments (DP), balance settlements, cashier drawer expenses, receipt printing, and customer pickup queue (`pickup_status = 0` / Not Picked Up, and invoice `status = 2` / Paid).
    - **Designer:** Customer select/creation wizard, parameter inputs (sizes, dimensions, options), real-time pricing calculator preview, and printable job tickets (Work Order sheets).
    - **Production:** Print queue lists, printer machine assignments, waste logging, and updating job status to completed (decrementing stock ledgers).
- **Real-Time Reactivity:** When data state changes (e.g., creating an order, updating payment, completing a print job), ensure `OrderUpdated` or equivalent WebSocket broadcast events are dispatched to keep Livewire UI components in sync without manual page reloads.
- **Aesthetics & UI:** Prioritize visual excellence. Build dynamic, responsive designs with smooth interactions (micro-animations, modern typography). Maintain styling conventions primarily through Tailwind CSS instead of inline styles. Use a sidebar navigation layout instead of a top navigation bar for the frontend dashboard interface. 
- **Service-Oriented Architecture (API-Ready):** Core business logic must reside in isolated PHP Service classes. Livewire components/controllers should call these services rather than implementing business logic directly.
  - `PricingService`: Handles complex pricing matrix matching by product category parameters (Merchandise, Large Format, Print Kertas, etc.).
  - `InventoryService`: Manages stock ledger adjustments and logging material waste.
- **Multi-Branch Tenancy:** The application supports multiple physical branches. Tables containing branch-specific data must include a `branch_id` foreign key.
  - Ensure queries and dashboards scope data to the active user's branch (`auth()->user()->branch_id`) to prevent cross-branch data leakage, unless explicitly handled for the owner role (e.g., the owner dashboard can view all branches combined or switch active branches).
  - Restrict access to ticket and receipt routes (`/orders/{invoice}/ticket` and `/orders/{invoice}/receipt`) using middleware/policies to block cross-branch data access for non-owner roles.
- **Legacy Data & Schema Mapping:** For reference during data migrations and troubleshooting imported database records:
  - **Migration Command:** Legacy data migration command `php artisan db:migrate-legacy` (implemented in `MigrateLegacyData.php`) is now disabled/superseded in the redesign as the database has fully transitioned to the new UUIDv7-based schema.
  - **Database Connection:** Reads from `mysql_migration` (legacy MariaDB database) and writes to PostgreSQL.
  - **Password Security Reset:** MD5 passwords from legacy users are not preserved. They are reset to a default Bcrypt/Argon2 password (`password123`) upon migration.
  - **Schema Mappings:**
    - `admin` -> `users` (maps level/role: `owner` -> `owner`, `bm` -> `manager`, `kasir` -> `cashier`, `setting` -> `designer`, `produksi` -> `production`).
    - `kostumer` -> `customers`
    - `produk` -> `products`
    - `bahan` -> `materials`
    - `mesin` -> `machines`
    - `jenis_finishing` -> `attributes` (with `type` column set to `'finishing'`)
    - `jenis_potong` -> `attributes` (with `type` column set to `'cut'`)
    - `jenis_display` -> `attributes` (with `type` column set to `'display'`)
    - `harga_jual` -> `pricing_matrices`
    - `invoice` -> `invoices`
    - `orderan` -> `orders`
    - `pengeluaran` -> `expenses`
    - `bahan_gagal` -> `wasted_materials`
    - `stok` -> `stock_ledgers`
- **Theme Support (Light/Dark Mode) & Tailwind CSS v4 Styling:** Ensure UI components support both light and dark modes dynamically using Tailwind's `dark:` variant and `localStorage` state.
  - All interactive elements, dropdowns, inputs, text labels, and modal backgrounds must use responsive Tailwind classes (e.g. `bg-white dark:bg-surface-600 border border-gray-200 dark:border-surface-300/50`) to ensure proper readability and contrast in both themes. Avoid hardcoding absolute dark/light background colors.
  - **Tailwind CSS v4 Configuration Rule:** Do NOT edit or create `tailwind.config.js`. Any custom theme configurations, fonts, brand colors, keyframes, and animations must be defined directly in `resources/css/app.css` using the `@theme` directive.

## Technical & Database Specifications
- **Database Status Codes:**
  - **Invoice status codes (`invoices.status`):**
    - `0` = Unpaid / Pending DP (Belum Lunas)
    - `1` = Down Payment Paid (DP)
    - `2` = Fully Paid (Lunas)
  - **Invoice pickup codes (`invoices.pickup_status`):**
    - `0` = Not Picked Up (Belum)
    - `1` = Picked Up / Done (Done)
  - **Order status codes (`orders.status`):**
    - `0` = Pending / Not Processed (Belum di Proses)
    - `1` = Processing / Printing (Sedang di Proses)
    - `2` = Completed (Telah Beres)
- **Testing & Database Portability:**
  - The automated test environment utilizes an SQLite in-memory database (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), while production uses PostgreSQL.
  - All migrations, queries, and Eloquent interactions must remain dialect-agnostic and fully compatible with SQLite. Do not use PostgreSQL-specific or MariaDB-specific raw query statements.
  - **UUIDv7 Assertions:** Since all keys in the new database schema are UUIDs, all unit and feature tests must mock records and assert database states using valid UUID strings (Laravel `Str::uuid()`) rather than auto-incrementing integers.
- **WebSocket Broadcast Structure:**
  - Real-time updates rely on the `App\Events\OrderUpdated` event class broadcasting to a public channel named `orders`. Livewire dashboards should register Echo listeners (e.g., `protected $listeners = ['echo:orders,OrderUpdated' => '$refresh'];`) to automatically update UI states.

## Development, Coding, & DevOps Best Practices
- **Environment Configurations:**
  - Never hardcode or commit `.env` file credentials.
  - When introducing new configurations or environment dependencies, append placeholder entries to `.env.example` immediately.
- **Database Migrations:**
  - Never edit migrations that have already been merged/deployed. Create a new migration file for schema adjustments.
  - Keep all schema definitions dialect-agnostic so they execute cleanly on both SQLite (for tests) and PostgreSQL (for production).
- **Vite Asset Management:**
  - The application relies on compiling assets for Tailwind CSS and JavaScript. Propose running `npm run build` (or keeping `npm run dev` running) after editing layout files, CSS styles, or configuration parameters to ensure changes reflect properly in the browser.
- **Coding Standards & Formatting:**
  - Adhere strictly to **PSR-12** standards for PHP classes and code structure.
  - Indent PHP files, Blade views, and JS templates using **4 spaces** (per `.editorconfig`). For YAML and Markdown files, use **2 spaces**.
  - **Livewire Separation of Concerns:** Maintain a clean separation between backend and frontend files by using class-based Livewire components. The controller logic must reside in `app/Livewire/` and frontend templates in `resources/views/livewire/` (avoid single-file Volt components to keep code clean and readable).
  - **Blade Template Formatting & Formatter Safety:** To prevent auto-formatting tools (e.g. Prettier, Pint, or pre-commit hooks) from introducing syntax compilation errors:
    - Avoid using inline Blade conditional tags (like `@if ... @endif`) on the same line as HTML text content. Instead, use inline PHP ternary expressions inside double curly braces: `{{ $condition ? $value : '' }}`. This ensures the formatter treats the expression as a single statement and doesn't split it.
    - Ensure PHP arrow operators (`->`) in Blade views are never split across multiple lines. Keep the variable, operator, and method call together on a single line.
    - **HTML Tag Attributes:** To prevent Prettier from splitting the PHP arrow operator (`->`) across lines inside HTML tags, declare merged attributes within a `@php` block first, then echo the variable (e.g. `{{ $mergedAttributes }}`) on the tag.
    - **Prettier Ignore:** Use `<!-- prettier-ignore -->` comments before HTML tags (like `<p>`) where inline blade directives (e.g. `@if`) or long string concatenations must be kept verbatim to prevent line wrapping from splitting the directive name from its condition.
- **Container Network Ports:**
  - Reverb runs internally on container port `8080`, mapped externally to host port `8084` to prevent conflict with other services. Always ensure WebSocket client URLs connect through the host mapped port.
- **Dependency Management:**
  - Avoid installing external PHP or JavaScript packages unless explicitly requested or absolutely necessary. Rely on core Laravel 13, Livewire 4, Alpine, and Tailwind utilities.
  - When dependencies are added or updated, always commit both the package definition files (`composer.json`, `package.json`) and their lock files (`composer.lock`, `package-lock.json`) together to maintain reproducible builds.
  - Verify container and PHP system extension requirements (e.g., the `pcntl` extension needed for Laravel Reverb signal handling) before introducing new packages.

## Workflow Instructions
- **Docker Environment:** Assume the project is run and tested inside a Docker Compose container stack. All PHP and Artisan commands must be proposed to run inside the container (e.g. `docker exec -it -w /var/www/html/pos_alja local-php83 php artisan <command>`).
- **Interactive & Privileged Commands:** Avoid proposing `sudo` or interactive command prompts unless requested.
- **Documentation style:** When proposing documentation, do NOT use emojis. Ensure technical guides provide complete, end-to-end instructions for both installation and post-installation usage.
- **Walkthrough Logging:** After completing major changes, database modifications, or phase rebuilds, always update or append a detailed summary of the changes, modified files, and manual/automated verification steps to the main walkthrough file at [walkthrough.md](.agents/plan/walkthrough.md).




