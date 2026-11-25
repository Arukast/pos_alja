# Jira Backlog — POS Alja Digital Printing

Folder ini berisi backlog Jira untuk tim 3 orang. Isinya diturunkan dari:

- `docs/requirements/fr.md` — FR1–FR26.
- `docs/requirements/nfr.md` — NFR1–NFR10.
- `docs/requirements/product-backlog.md` — PB-01–PB-10.
- `docs/requirements/sprint-review-1.md` — Sprint Review 1 changes.
- `docs/planning/distribusi-kerja-3-orang.md` — pembagian kerja A/B/C.

## Isi Folder

| File | Isi |
| :--- | :--- |
| `epics.csv` | 9 epic yang membagi area kerja. |
| `stories.csv` | Story/task per epic, sudah diberi sprint dan story point. |

## Ringkasan Epic

| Placeholder | Epic | Fokus | Pemilik | FR |
| :--- | :--- | :--- | :--- | :--- |
| `EPIC-FOUNDATION` | Fondasi: Autentikasi, RBAC, dan Branch Scope | Auth, user, policy, branch | A | FR1–FR5 |
| `EPIC-CATALOG` | Katalog Produk, Kategori, dan Harga | Produk, kategori, bahan, finishing/display, diskon reseller, diskon kategori | A (catalog) + C (material) | FR6–FR7 |
| `EPIC-SALES` | Pesanan, Pelanggan, dan Status Desain | Customer, order, design status, invoice | B | FR8–FR10 |
| `EPIC-PAYMENTS` | Pembayaran, Invoice, dan Pengeluaran | DP, settlement, invoice, expense | B | FR11–FR14, FR21 |
| `EPIC-INVENTORY` | Inventori, Pembelian, dan Penyesuaian Stok | Material, stock, purchase, adjustment | C | FR15–FR18 |
| `EPIC-PRODUCTION` | Antrean dan Status Produksi | Queue, status, usage, waste | C | FR19–FR20 |
| `EPIC-REPORTING` | Laporan dan Ekspor | Dashboard, report, Excel/PDF | C | FR22–FR24 |
| `EPIC-AUDIT-BACKUP` | Audit dan Backup | Audit log, manual/scheduled backup | A | FR25–FR26 |
| `EPIC-FRONTEND` | SPA Shell, Design System, dan Real-time | Shell, primitive, query, print | B | Lintas |

## Cara Import ke Jira

1. Buat project Jira dengan tipe issue `Epic` dan `Story`.
2. Buat sprint berikut di project:
   - `Sprint 0`
   - `Sprint 1`
   - `Sprint 2`
   - `Sprint 3`
3. Import `epics.csv`:
   - `Issue Type` → Issue Type.
   - `Summary` → Summary.
   - `Description` → Description.
   - `Priority` → Priority.
   - `Labels` → Labels.
4. Setelah import epic selesai, catat key setiap epic, misalnya `POS-10` untuk `EPIC-FOUNDATION`.
5. Buka `stories.csv`, lalu ganti placeholder di kolom `Epic Link` dengan key epic yang sebenarnya. Contoh:
   - `EPIC-FOUNDATION` → `POS-10`
   - `EPIC-CATALOG` → `POS-11`
6. Import `stories.csv`:
   - `Issue Type` → Issue Type.
   - `Summary` → Summary.
   - `Description` → Description.
   - `Priority` → Priority.
   - `Labels` → Labels.
   - `Sprint` → Sprint.
   - `Story Points` → Story Points.
   - `Epic Link` → Epic Link.
7. Jika project memakai team-managed Jira dan tidak punya `Epic Link`, gunakan field `Parent` dan isi dengan key epic. Jika fitur parent belum tersedia saat import, import story terlebih dahulu lalu hubungkan ke epic secara manual.

Catatan:

- Story point adalah estimasi awal, bukan angka mati. Sesuaikan saat sprint planning.
- `Sprint` hanya bisa diimport jika sprint dengan nama tersebut sudah dibuat.
- Label `owner-a`, `owner-b`, `owner-c` dipakai untuk filter pemilik workstream.
- Status implementasi tidak ada di CSV ini. Gunakan `docs/requirements/implementation-gaps.md`.
- Kolom `Sprint` mencatat sprint backlog asli. Pekerjaan tindak lanjut Sprint Review 1 untuk PB-01, PB-02, dan PB-04 masuk Sprint 2; PB-08 pindah ke Sprint 1.

## Ringkasan Sprint

| Sprint | Fokus | Epic utama |
| :--- | :--- | :--- |
| Sprint 0 | Fondasi teknis, SPA shell, CI/CD | Foundation, Frontend |
| Sprint 1 | PB-08 auth/RBAC, katalog core, pesanan core, pembayaran, nota, inventory foundation | Foundation, Catalog, Sales, Payments, Inventory |
| Sprint 2 | Inventory, produksi, revisi Sprint Review 1, audit awal | Inventory, Production, Sales, Payments, Foundation |
| Sprint 3 | Laporan, ekspor, audit, backup, hardening | Reporting, Audit-Backup, Frontend |

Sprint 2 memiliki beban paling berat. Jika velocity tim di bawah target, pindahkan beberapa item berikut ke Sprint 3 saat sprint planning:

- `CRUD cabang`
- `Audit log untuk aksi kritis`
- `Responsive, accessibility, dan print layout`
- `Lihat stok per peran dan cabang`
- `Catat waste dan kurangi stok`

## Mapping FR ke Epic

| FR | Epic |
| :--- | :--- |
| FR1–FR5 | `EPIC-FOUNDATION` |
| FR6–FR7 | `EPIC-CATALOG` |
| FR8–FR10 | `EPIC-SALES` |
| FR11–FR14, FR21 | `EPIC-PAYMENTS` |
| FR15–FR18 | `EPIC-INVENTORY` |
| FR19–FR20 | `EPIC-PRODUCTION` |
| FR22–FR24 | `EPIC-REPORTING` |
| FR25–FR26 | `EPIC-AUDIT-BACKUP` |
| Lintas fitur | `EPIC-FRONTEND` |

## Catatan Keputusan Akses

- Kasir tidak memiliki akses stok dan tidak dapat mencatat pengeluaran.
- Kasir hanya boleh mengubah status pickup, bukan item pesanan.
- Designer memiliki order update, design status, customer search/create, dan invoice print.
- Manager hanya mengelola staf dan stok cabangnya.
- Global catalog, product, category, finishing/display, dan quantity discount adalah Owner-only; Manager read-only.
- Owner dan Branch Manager mencatat pengeluaran; kategori pengeluaran adalah dropdown tetap.
- Global material deletion hanya Owner.
- Production tidak melihat price, cost, profit, atau payment fields; invoice dan receipt ditolak untuk Production.
- Mesin dihapus dari target; biaya mesin menjadi bagian dari biaya bahan.
