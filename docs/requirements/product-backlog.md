# Product Backlog (Target Specification)

- **Source:** Capstone proposal product backlog, revised by Sprint Review 1 on 2026-11-24. PB-08 moved to Sprint 1; PB-01, PB-02, PB-04, PB-05, PB-06, and PB-07 acceptance criteria were updated.
- **Language:** Retained in Indonesian to preserve the proposal wording; the rest of `docs/` is in English.
- **Scope:** All items are target scope.
- **Privacy:** Branch names are generalized to "Cabang A" and "Cabang B" for the repository.

| ID Backlog | Sprint | Kode FR | Peran (Role) | User Story | Acceptance Criteria |
| :---: | :---: | :---: | :---: | :--- | :--- |
| PB-01 | Sprint 1 | FR6, FR7 | Owner, Branch Manager, Designer, Kasir | Sebagai pengguna operasional, saya ingin mengelola produk, kategori, bahan, finishing/display, dan diskon kuantitas serta mencari katalog dengan pagination, agar pemuatan katalog cepat. | Katalog produk tampil dengan pencarian, filter, dan pagination di bawah 2 detik (NFR1); harga dihitung dari bahan + finishing/display + diskon kategori. |
| PB-02 | Sprint 1 | FR8, FR9, FR10 | Designer | Sebagai Designer, saya ingin mencari pelanggan, mencatat pesanan baru, memperbarui status desain, dan mengubah pesanan yang belum diproduksi serta belum lunas. | Pelanggan dicari terlebih dahulu; pesanan memakai urutan Produk -> Bahan -> Finishing -> Display; pesanan berstatus DP dapat diubah sebelum produksi; satu invoice dapat dicetak. |
| PB-03 | Sprint 1 | FR11, FR12 | Kasir | Sebagai Kasir, saya ingin memproses pembayaran DP berkali-kali hingga lunas dengan validasi angka otomatis, agar tidak terjadi error atau selisih hitung. | Input nominal divalidasi. Sistem menghitung sisa piutang secara akurat (NFR3). |
| PB-04 | Sprint 1 | FR13, FR14 | Kasir, Owner, Branch Manager, Designer | Sebagai Kasir, saya ingin mencetak invoice dan nota pembayaran; sebagai Owner/Manajer, saya ingin mencatat pengeluaran operasional dengan kategori tetap. | Invoice A4 dan nota 80 mm menampilkan rincian biaya, riwayat DP, dan sisa tagihan; pengeluaran cabang memakai dropdown kategori dan tersimpan di basis data. |
| PB-05 | Sprint 2 | FR15, FR17, FR18 | Owner, Branch Manager, Produksi | Sebagai pengelola cabang, saya ingin mencatat pembelian bahan baku, melakukan koreksi stok dengan alasan, dan melihat peringatan stok minimum. | Pembelian menambah stok dan ledger purchase; koreksi stok wajib alasan dan tercatat di `stock_ledgers`; peringatan muncul saat stok di bawah batas minimum. |
| PB-06 | Sprint 2 | FR19, FR20, FR21 | Produksi, Kasir, Designer, Owner, Branch Manager | Sebagai staf Produksi dan Kasir, saya ingin memfilter antrean produksi, mengubah status pengerjaan, dan menandai pickup. | Semua role dapat melihat/memfilter antrean; Produksi mengubah status produksi; Kasir menandai pickup; status tercatat di `order_status_logs`. |
| PB-07 | Sprint 2 | FR16 | Produksi | Sebagai staf Produksi, saya ingin mencatat pemakaian bahan baku dan bahan gagal cetak (waste) langsung dari baris antrean. | Stok cabang berkurang otomatis saat pesanan diproses; bahan rusak tercatat pada `wasted_materials` tanpa membuka halaman terpisah. |
| PB-08 | Sprint 1 | FR1, FR2, FR3, FR4, FR5 | All, Owner, Branch Manager | Sebagai pengguna, saya ingin sistem membatasi menu serta data sesuai peran dan cabang (RBAC), serta mendukung pengelolaan akun staf. | 5 peran terpisah sesuai matriks hak akses; staf Produksi tidak melihat nominal harga; Manajer hanya mengakses cabangnya. |
| PB-09 | Sprint 3 | FR22, FR23, FR24 | Owner, Branch Manager | Sebagai Owner dan Branch Manager, saya ingin melihat dasbor ringkasan, memfilter laporan keuangan periodik, serta mengekspornya ke Excel/PDF. | Dasbor menampilkan omzet, pengeluaran, dan piutang lintas cabang (Cabang A dan Cabang B); ekspor Excel/PDF berjalan cepat (NFR2). |
| PB-10 | Sprint 3 | FR25, FR26 | All, Owner | Sebagai Owner, saya ingin sistem mencatat audit log atas aktivitas penting dan menyediakan fitur pencadangan basis data (backup). | Perubahan pembayaran, pesanan, dan stok terekam di `activity_logs`; fitur backup manual dan terjadwal berfungsi. |

## Sprint Summary

| Sprint | Backlog | Focus |
| :--- | :--- | :--- |
| Sprint 1 | PB-08, PB-01, PB-02, PB-03, PB-04 | RBAC, catalog, orders, payments, invoice/receipt, expenses |
| Sprint 2 | PB-05, PB-06, PB-07 | Inventory, production queue, usage/waste, Sprint Review 1 revisions |
| Sprint 3 | PB-09, PB-10 | Reports/export, audit/backup, final hardening |

## Delivery Notes

1. PB-08 moved from Sprint 3 to Sprint 1 after Sprint Review 1. It is the foundation for all protected endpoints.
2. PB-01 must precede PB-02 because product, material, finishing/display, and discount data feed order entry.
3. PB-02 depends on the order-update and design-status schema changes in `docs/architecture/erd.md`.
4. PB-05 depends on the purchase and stock-adjustment schema in `docs/architecture/erd.md`.
5. PB-09 depends on the report queries, indexes, and export service in `docs/architecture/api-contracts.md`.
6. PB-10 depends on the backup service and scheduler in `docs/architecture/api-contracts.md`.
7. Every backlog item is complete only when its FRs, access rules, migrations, tests, and `docs/` are updated together.

## Reconciliation Notes

1. **"Diambil" is not a fourth production status.** The target schema uses `orders.pickup_status = 1` and `orders.pickup_date` for pickup, while production status remains `0/1/2`. Pickup changes are logged through the audit trail; if the team wants a fourth `order_status_logs` status, add it explicitly to FR20, FR21, and the ERD.
2. **Original branch names were generalized** to "Cabang A dan Cabang B" for the repository, per the privacy rule.
3. **PB-02 says "sebelum diproduksi"**, which matches FR9: an order may be changed only while every order on the invoice has `status = 0` and the invoice is not fully paid.
4. **PB-10 says "activity_logs"** for payment, order, and stock changes; this is the target FR25 coverage.
5. **PB-08 is a Sprint 1 item** after Sprint Review 1. The Sprint 0 foundation remains setup work; the PB-08 acceptance is delivered in Sprint 1.
6. **The Sprint column records the original backlog sprint.** PB-01, PB-02, and PB-04 remain Sprint 1 items; their Sprint Review 1 follow-up work is scheduled in Sprint 2. See `docs/requirements/sprint-review-1.md`.
