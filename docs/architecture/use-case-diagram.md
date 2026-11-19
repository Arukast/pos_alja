# Use Case Diagram (Proposed Capstone Scope)

- **Status:** Hybrid view. Green = implemented; amber = partial; dashed grey = planned.
- **Purpose:** Show the intended capstone scope, including planned items, without presenting them as working features.
- **Source of truth for current behavior:** `docs/requirements/fr.md`, `docs/requirements/access-matrix.md`, and the code.

Legend:

- **Implemented** — exists and is demoable.
- **Partial** — exists for some actors or has a known limitation.
- **Planned** — agreed scope, not implemented yet.

```mermaid
flowchart LR
    Owner((Pemilik))
    Manager((Branch Manager))
    Cashier((Kasir))
    Designer((Designer))
    Production((Produksi))

    subgraph SYS["Sistem Alja Digital Printing"]
        direction TB

        subgraph ADM["Administrasi"]
            direction TB
            UC01(["Kelola Pengguna"])
            UC06(["Kelola Cabang"])
            UC23(["Audit Trail"])
        end

        subgraph CAT["Katalog & Konfigurasi"]
            direction TB
            UC02(["Kelola Kategori Produk"])
            UC24(["Kelola Produk"])
            UC04(["Kelola Mesin"])
            UC05(["Kelola Harga"])
        end

        subgraph INV["Inventori"]
            direction TB
            UC03(["Kelola Material & Stok"])
            UC11(["Pantau Stok Bahan Baku"])
            UC15(["Restock Bahan Baku"])
        end

        subgraph TRX["Transaksi"]
            direction TB
            UC10(["Lihat Daftar Transaksi"])
            UC16(["Catat Pesanan"])
            UC17(["Perbarui Pesanan"])
            UC22(["Kelola Pelanggan"])
        end

        subgraph PAY["Pembayaran & Pickup"]
            direction TB
            UC13(["Proses Pembayaran (DP/Pelunasan)"])
            UC14(["Catat Pengeluaran Operasional"])
            UC20(["Antrean Pickup"])
            UC21(["Cetak Tiket & Struk"])
        end

        subgraph PROD["Produksi"]
            direction TB
            UC12(["Kelola Antrean & Status Produksi"])
            UC18(["Perbarui Status Desain"])
            UC19(["Catat Waste & Pemakaian Bahan"])
        end

        subgraph REP["Pelaporan"]
            direction TB
            UC07(["Lihat Dashboard & Laporan Keuangan"])
            UC08(["Ekspor Laporan"])
            UC09(["Backup Database Manual"])
        end
    end

    Owner --- UC01
    Owner --- UC02
    Owner --- UC03
    Owner --- UC04
    Owner --- UC06
    Owner --- UC07
    Owner --- UC10
    Owner --- UC11
    Owner --- UC12
    Owner --- UC15
    Owner --- UC23
    Owner -.->|planned| UC08
    Owner -.->|planned| UC09
    Owner -.->|planned| UC24

    Manager --- UC01
    Manager --- UC02
    Manager --- UC03
    Manager --- UC04
    Manager --- UC05
    Manager --- UC07
    Manager --- UC10
    Manager --- UC11
    Manager --- UC12
    Manager --- UC15
    Manager -.->|planned| UC24

    Cashier --- UC10
    Cashier --- UC13
    Cashier --- UC14
    Cashier --- UC20
    Cashier --- UC21

    Designer --- UC10
    Designer --- UC16
    Designer --- UC21
    Designer --- UC22
    Designer -.->|planned| UC17
    Designer -.->|planned| UC18

    Production --- UC10
    Production --- UC11
    Production --- UC12
    Production --- UC19
    Production --- UC21

    UC13 -.->|include| UC21
    UC16 -.->|include| UC22
    UC17 -.->|extend| UC16

    classDef implemented fill:#dcfce7,stroke:#16a34a,color:#14532d
    classDef partial fill:#fef9c3,stroke:#ca8a04,color:#713f12
    classDef planned fill:#f3f4f6,stroke:#6b7280,color:#374151,stroke-dasharray: 5 5

    class UC01,UC02,UC03,UC04,UC05,UC06,UC13,UC14,UC15,UC16,UC20,UC21,UC22,UC23 implemented
    class UC07,UC10,UC11,UC12,UC19 partial
    class UC08,UC09,UC17,UC18,UC24 planned
```

## Use Case Status

| ID | Use case | Status | Current behavior or gap |
| :--- | :--- | :--- | :--- |
| UC01 | Kelola Pengguna | Implemented | Owner manages all users; manager manages cashier/designer/production in their own branch. `UserManagement::$mode` is client-writable and has no mount guard. |
| UC02 | Kelola Kategori Produk | Implemented | Categories, calculation archetypes, and allowed attribute types; no product CRUD screen. |
| UC03 | Kelola Material & Stok | Implemented | Global material catalog, branch stock, restock, stock ledger, soft delete/restore. |
| UC04 | Kelola Mesin | Implemented | Owner global, manager branch-scoped; base cost (HPP) per machine. |
| UC05 | Kelola Harga | Implemented | Manager pricing matrices, tiered quantities, attribute and machine modifiers. Owner has no pricing tab. |
| UC06 | Kelola Cabang | Implemented | Owner-only branch create, edit, toggle, soft delete. |
| UC07 | Lihat Dashboard & Laporan Keuangan | Partial | Owner has full revenue, cost, profit, and expense reports. Manager has a dashboard and designer revenue leaderboard, not full P&L reports. |
| UC08 | Ekspor Laporan | Planned | No export function exists. |
| UC09 | Backup Database Manual | Planned | No backup command or UI exists. |
| UC10 | Lihat Daftar Transaksi | Partial | Owner has financial detail and audit lists; cashier has payment/pickup queues; designer has an order list. A manager general transaction list is planned. |
| UC11 | Pantau Stok Bahan Baku | Partial | Owner and manager can view stock. Production stock read is broken because `Production::render()` queries `materials.branch_id`. |
| UC12 | Kelola Antrean & Status Produksi | Partial | Production can read the queue and update status. Read-only owner/manager access is planned. |
| UC13 | Proses Pembayaran (DP/Pelunasan) | Implemented | Cashier registers down payments and settlements; invoice status recalculates. |
| UC14 | Catat Pengeluaran Operasional | Implemented | Cashier logs drawer expenses with branch and cashier name. |
| UC15 | Restock Bahan Baku | Implemented | Adds stock and writes a stock ledger. There is no purchase order, supplier, or purchase-cost record. |
| UC16 | Catat Pesanan | Implemented | Designer selects or creates a customer, builds a cart, and submits an invoice with orders. |
| UC17 | Perbarui Pesanan | Planned | Designer `CRU` on Data Transaksi: add items to a submitted order when the customer requests changes. Current code supports create and read only. |
| UC18 | Perbarui Status Desain | Planned | There is no design status column or action; only order status `0/1/2`, updated by production. |
| UC19 | Catat Waste & Pemakaian Bahan | Partial | Waste logging and stock deduction work. The production `waste` tab fails to render, so the UI path is not demoable. |
| UC20 | Antrean Pickup | Implemented | Cashier sees paid, unpicked invoices and marks them picked up. |
| UC21 | Cetak Tiket & Struk | Implemented | Authenticated branch users can print the work-order ticket and 80 mm receipt. |
| UC22 | Kelola Pelanggan | Implemented | Designer searches and creates customers. |
| UC23 | Audit Trail | Implemented | Owner views activity logs. Material and pricing actions are not logged. |
| UC24 | Kelola Produk | Planned | Products exist as data records but there is no product CRUD screen. |

## Role Summary Used by This Diagram

| Role | Data Akun | Data Transaksi | Data Stok | Status & Antrean | Laporan Keuangan |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Pemilik | CRUD | R | CRUD | R (planned) | R |
| Branch Manager | CRUD (branch staff) | R (planned general list) | CRU (branch) | R (planned) | R (limited) |
| Kasir | - | CRU (U = pickup only) | - | R | - |
| Designer | - | CRU (U planned) | - | R | - |
| Produksi | - | R | RU | RU | - |

Notes:

- Designer `CRU` includes `Perbarui Pesanan` as planned scope. It is not implemented.
- Kasir `U` on transactions is limited to pickup status; payment and invoice amounts are not editable.
- Manager financial reporting is limited to the designer revenue leaderboard and recent expenses, not the owner's full report tabs.
- Manager account CRUD is limited to cashier/designer/production in the manager's branch; `UserManagement::$mode` is currently client-writable and should be fixed.
- Manager `Data Stok` is proposed as `CRU`; the current code also permits delete, which is a documented gap.
- Owner and manager production-queue read access is planned; production already has read/update.
- Production stock read is partial because the waste tab query is broken.
