# AGENTS.md

## General Project Rules
- **Framework & Stack:** This project is built using Laravel 11, PostgreSQL, Livewire 3, Tailwind CSS, and Laravel Reverb.
- **Privacy & Security:** Do not hardcode personal user data in the source code, comments, or documentation. Always use generic names (e.g., "Generic Print Shop", "admin_owner", "test@example.com").
- **Language & Naming Conventions:** Maintain international coding standards by translating legacy Indonesian domain terms to English. All code structures (variables, methods, database tables, models) must strictly be in English.
  - **Financial Metrics Naming:**
    - **Invoice level:** `grand_total` (Omset), `total_cost` (HPP), `total_profit` (Laba).
    - **Order level:** `subtotal` (Subtotal), `cost` (HPP), `profit` (Laba).
  - **Material Waste:** Use `WastedMaterial` model and `wasted_materials` database table (Bahan Gagal).
- **Role-Based Access Control:** Strict role segregation applies: `owner`, `manager` (bm), `cashier` (kasir), `designer` (setting), and `production` (produksi). Ensure gates and middleware enforce these boundaries.
  - **Profile & Settings Rule:** Only `owner` and `manager` are authorized to update user profiles (name and email). All other roles can only view their profile info and update their passwords.
  - **Role-Specific Dashboard Responsibilities:**
    - **Owner:** Cross-branch financial analytics (Omset/HPP/Laba, Expenses), period filters (Daily, Weekly, Monthly), branch selection toggle, individual designer performance leaderboard, and dynamic SVG sparkline charts mapping net profits.
    - **Manager (bm):** Material inventory management, stock ledgers, display/finishing master data, pricing engine matrices, and user profile management.
    - **Cashier (kasir):** Checkouts, down payments (DP), balance settlements, cashier drawer expenses, receipt printing, and customer pickup queue (`pickup_status = 0` / Not Picked Up, and invoice `status = 2` / Paid).
    - **Designer (setting):** Customer select/creation wizard, parameter inputs (sizes, dimensions, options), real-time pricing calculator preview, and printable job tickets (Work Order sheets).
    - **Production (produksi):** Print queue lists, printer machine assignments, waste logging, and updating job status to completed (decrementing stock ledgers).
- **Real-Time Reactivity:** When data state changes (e.g., creating an order, updating payment, completing a print job), ensure `OrderUpdated` or equivalent WebSocket broadcast events are dispatched to keep Livewire UI components in sync without manual page reloads.
- **Aesthetics & UI:** Prioritize visual excellence. Build dynamic, responsive designs with smooth interactions (micro-animations, modern typography). Maintain styling conventions primarily through Tailwind CSS instead of inline styles. 
- **Service-Oriented Architecture (API-Ready):** Core business logic must reside in isolated PHP Service classes. Livewire components/controllers should call these services rather than implementing business logic directly.
  - `PricingService`: Handles complex pricing matrix matching by product category parameters (Merchandise, Large Format, Print Kertas, etc.).
  - `InventoryService`: Manages stock ledger adjustments and logging material waste.
- **Multi-Branch Tenancy:** The application supports multiple physical branches. Tables containing branch-specific data must include a `branch_id` foreign key.
  - Ensure queries and dashboards scope data to the active user's branch (`auth()->user()->branch_id`) to prevent cross-branch data leakage, unless explicitly handled for the owner role (e.g., the owner dashboard can view all branches combined or switch active branches).
  - Restrict access to ticket and receipt routes (`/orders/{invoice}/ticket` and `/orders/{invoice}/receipt`) using middleware/policies to block cross-branch data access for non-owner roles.
- **Legacy Data & Schema Mapping:** For reference during data migrations and troubleshooting imported database records:
  - **Migration Command:** Run mapping tasks using `php artisan db:migrate-legacy` (implemented in `MigrateLegacyData.php`).
  - **Database Connection:** Reads from `mysql_migration` (legacy MariaDB database) and writes to PostgreSQL.
  - **Password Security Reset:** MD5 passwords from legacy users are not preserved. They are reset to a default Bcrypt/Argon2 password (`password123`) upon migration.
  - **Schema Mappings:**
    - `admin` -> `users` (maps levels: `bm` to `manager`, `owner` to `owner`, `kasir` to `cashier`, `setting` to `designer`, `produksi` to `production`).
    - `kostumer` -> `customers`
    - `produk` -> `products`
    - `bahan` -> `materials`
    - `mesin` -> `machines`
    - `jenis_finishing` -> `finishings`
    - `jenis_potong` -> `cuts`
    - `jenis_display` -> `displays`
    - `harga_jual` -> `pricing_matrices`
    - `invoice` -> `invoices`
    - `orderan` -> `orders`
    - `pengeluaran` -> `expenses`
    - `bahan_gagal` -> `wasted_materials`
    - `stok` -> `stock_ledgers`
- **Theme Support (Light/Dark Mode):** Ensure UI components support both light and dark modes dynamically using Tailwind's `dark:` variant and `localStorage` state. 
  - All interactive elements, dropdowns, inputs, text labels, and modal backgrounds must use responsive Tailwind classes (e.g. `bg-white dark:bg-surface-600 border border-gray-200 dark:border-surface-300/50`) to ensure proper readability and contrast in both themes. Avoid hardcoding absolute dark/light background colors.

## Workflow Instructions
- **Docker Environment:** Assume the project is run and tested inside a Docker Compose container stack. All PHP and Artisan commands must be proposed to run inside the container (e.g. `docker exec -it -w /var/www/html/pos_alja local-php83 php artisan <command>`).
- **Interactive & Privileged Commands:** Avoid proposing `sudo` or interactive command prompts unless requested.
- **Documentation style:** When proposing documentation, do NOT use emojis. Ensure technical guides provide complete, end-to-end instructions for both installation and post-installation usage.

