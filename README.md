# Print Shop POS Rebuild

A modern, high-performance, reactive Point-of-Sale (POS) system built with Laravel 11, PostgreSQL, Livewire 3, Tailwind CSS, and Laravel Reverb (WebSockets).

This project is a complete rebuild of the legacy CodeIgniter/Procedural PHP POS system to optimize speed, security, and real-time operations.

---

## Glossary (Legacy to Modern)

To support transition while maintaining international coding standards, the codebase translates legacy local names to clean English POS domains.

| Legacy Term | Modern English Equivalent | Codebase Reference | Description |
| :--- | :--- | :--- | :--- |
| Omset | Gross Revenue | grand_total | Total revenue before deductions |
| HPP (Harga Pokok) | Cost of Goods Sold (COGS) | cost | Base cost of materials/consumables |
| Laba | Net Profit | profit | Revenue minus HPP |
| Saldo | Net Balance | amount / balance | Final cash/account balance |
| Pengeluaran | Expense | Expense model | Cash drawer operational/capital costs |
| Bahan Gagal | Wasted Material | WastedMaterial model | Logged print failure sheets/meters |
| bm | Manager | manager role | Branch management & inventory restocking |
| owner | Owner | owner role | Performance metrics & financial reports |
| kasir | Cashier | cashier role | Down-payments (DP) & checkouts |
| setting | Designer / CS | designer role | Intakes files & calculates matrix pricing |
| produksi | Production Operator | production role | Operates printers, logs waste & starts print jobs |
| kostumer | Customer | Customer model | Dynamic client profiles |

---

## System Architecture & Event Flow

The system coordinates operations across 5 roles in real-time. When a state changes (e.g., order submitted or printed), the UI instantly reloads across screens using WebSockets (Laravel Reverb).

(Designer -> Production -> Cashier)
1. Designer submits order
2. Order appears live on Production queue
3. Operator starts print, marks done
4. Invoice appears live on Cashier queue for checkout

---

## Installation & Environment Setup

This section covers the standard process for setting up the application in a typical environment.

### Prerequisites
* PHP 8.2 or 8.3
* PostgreSQL 14 or higher
* Node.js (v18+) & npm
* Composer

### 1. Clone and Configure Environment

First, copy the example environment configuration:
```bash
cp .env.example .env
```

Open `.env` and set your application name, database connection details, and Reverb settings:
```env
APP_NAME="Print Shop POS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pos_database
DB_USERNAME=pos_user
DB_PASSWORD=pos_password

BROADCAST_CONNECTION=reverb
```

### 2. Install Backend Dependencies

Install the PHP dependencies using Composer, then generate the application key:
```bash
composer install
php artisan key:generate
```

### 3. Database Migration

Run the database migrations to create the required tables in PostgreSQL:
```bash
php artisan migrate:fresh --seed
```
If you are migrating from a legacy database, ensure `LEGACY_DB_*` connection keys are set in your `.env` and run the custom migration script instead:
```bash
php artisan db:migrate-legacy
```

### 4. Install Frontend Assets

Install NPM packages and build the frontend assets (Vite):
```bash
npm install
npm run build
```

### 5. Start the Application

You need to run three separate processes in your terminal to serve the application, handle frontend assets, and run the WebSocket server.

Terminal 1 (Web Server):
```bash
php artisan serve
```

Terminal 2 (WebSocket Server):
```bash
php artisan reverb:start
```

Terminal 3 (Vite Dev Server - Optional for active frontend development):
```bash
npm run dev
```

You can now access the application at `http://localhost:8000`.

---

## Default User Accounts

The system is seeded with generic default users for privacy and security. Please change their passwords upon first login.

* Default Password: password123

| Role | Username | Email | Name |
| :--- | :--- | :--- | :--- |
| Owner | admin_owner | owner@example.com | Owner Account |
| Manager | admin_manager | manager@example.com | Manager Account |
| Cashier | user_cashier | cashier@example.com | Cashier Account |
| Designer (CS) | user_designer | designer@example.com | Designer Account |
| Production | user_production| production@example.com | Production Account |

*(Note: If migrating from a legacy system, the original usernames might be imported with the default password instead).*

---

## Post-Setup User Guide & Core Workflows

Follow this end-to-end guide to test and verify the printing workflows.

### 1. Intake a Customer Order (Designer / CS Role)
1. Log in at `/login` using the Designer account.
2. Under the Customer Selection panel:
   * Search for an existing customer by typing a name, or
   * Click "New Customer" to register a client on the fly.
3. In the Order Parameters card, select a Product Category (e.g., Large Format). The input form adjusts dynamically.
4. Input details (Machine, Material, Side, Dimensions, and Quantity). The pricing engine updates the price, estimated cost, and profit parameters in real time.
5. Click "Add to Cart". You can append multiple print jobs to the draft.
6. Click "Submit Order". The database writes the Invoice and associated Orders in a transaction, dispatching a WebSocket broadcast to the production operators.

### 2. Process Printing Jobs (Production Operator Role)
1. Log in using the Production account.
2. Locate the active print jobs list. You will notice the order submitted by the Designer appears immediately without reloading the page.
3. Click "Start Printing". The system decreases the current stock level of the specified paper or banner roll dynamically.
4. If a print job fails (e.g., paper jam, bad coloring):
   * Fill out the "Log Failed Print" form.
   * Input the material, machine, and wasted quantity. The system automatically records the waste and deducts the inventory ledger.
5. Once printing succeeds, click "Mark Done". The invoice updates and instantly alerts the cashier.

### 3. Settle Payment (Cashier Role)
1. Log in using the Cashier account.
2. Under the Active Invoices queue, you will find the unpaid invoice.
3. If the customer pays a deposit first:
   * Click "Register DP" (Down Payment).
   * Input the DP amount and payment method (Cash/Transfer).
   * Click "Print Receipt" to open the 80mm receipt browser print window.
4. Once the prints are ready for pickup:
   * Click "Checkout / Settle".
   * Input the payment received. The change is calculated automatically.
   * Settle the invoice, changing its state to Paid.

### 4. Review Analytics (Owner Role)
1. Log in using the Owner account.
2. View real-time graphs showing today's, weekly, or monthly gross revenue, cost of materials, and net profits.
3. Analyze individual Designer/Production operator performance logs and branch balance trends.

---

## Maintenance & Troubleshooting

### Resetting the Database
If you need to reset all transactional data and perform a clean wipe:
```bash
php artisan migrate:fresh --seed
```

### Checking WebSockets Connectivity
If real-time dashboard updates are not syncing:
1. Ensure the Reverb port `8080` is accessible (default Laravel Reverb port).
2. Verify that the `.env` settings align:
   ```env
   REVERB_PORT=8080
   VITE_REVERB_PORT=8080
   ```
3. Check the Reverb console output for connection errors.

### Clearing Application Cache
If dashboard layout updates or translations are not visible:
```bash
php artisan optimize:clear
```

## Running Automated Tests
Run the test suite to verify overall software integrity:
```bash
php artisan test
```
All tests covering pricing matrix calculations, authorization scopes, cashiering payments, and inventory ledgers must pass.
