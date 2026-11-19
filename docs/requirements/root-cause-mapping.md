# Root Cause to System Requirements Mapping

- **Source:** Capstone proposal root-cause analysis.
- **Language:** Retained in Indonesian to preserve the proposal wording; the rest of `docs/` is in English.
- **Purpose:** Show why each target requirement exists, and connect it to the functional requirements, non-functional requirements, and product backlog.

## Proposal Mapping

| Akar Masalah | Kondisi Aplikasi Lama | Respons pada Sistem Baru | Kebutuhan |
| :---: | :---: | :--- | :---: |
| *People*: keterbatasan pemantauan Pemilik | Database terpisah per cabang | Satu basis data terpusat, dashboard seluruh cabang | FR22, FR23 |
| *People*: hambatan pelayanan Kasir | *Bug* dan web lambat | Validasi DP, kueri teratur, antarmuka ringkas | FR11–FR13, NFR1 |
| *People*: prioritas Produksi tidak jelas | Tidak ada dashboard produksi | Antrean produksi dengan status dan target selesai | FR19, FR20 |
| *Environment*: jaringan lokal terisolasi, lokasi terpisah | Berjalan lokal di satu komputer | Deployment terpusat yang diakses lewat internet (HTTPS) | NFR5, NFR6, NFR8 |
| *Measurement*: tidak ada metrik stok | Modul inventaris tidak berfungsi | Modul inventaris dengan riwayat mutasi dan stok minimum | FR15–FR18 |
| *Measurement*: ekspor laporan lambat, web lambat | Pengambilan data lambat | Optimasi kueri, indeks, dan pagination | FR7, FR24, NFR1, NFR2 |
| *Management*: tidak ada aturan sinkronisasi dan rekonsiliasi manual | Laporan digabung manual | Laporan gabungan otomatis dari satu basis data | FR22, FR23 |
| *Management*: tidak ada SOP pelacakan produksi | Pesanan berisiko terlupa | Status pesanan dan log perubahan status | FR10, FR20, FR25 |
| *Machine*: tanpa pencadangan, *single point of failure* | XAMPP bermasalah, data berisiko hilang | Kontainer, pencadangan otomatis ke penyimpanan luar, *uptime monitoring* | FR26, NFR6 |
| *Material*: data terisolasi, bug pembacaan DP | Nilai DP tidak terstruktur | Tabel pembayaran terstruktur dengan tipe numerik dan banyak baris DP | FR9, FR11, FR12, NFR3 |

## Traceability Matrix

| Root cause | Functional requirements | Non-functional requirements | Product backlog |
| :--- | :--- | :--- | :--- |
| Owner monitoring across branches | FR22, FR23 | NFR1, NFR4 | PB-09 |
| Cashier service speed and DP bugs | FR11, FR12, FR13 | NFR1, NFR3 | PB-03, PB-04 |
| Unclear production priority | FR19, FR20 | NFR1, NFR4 | PB-06 |
| Local-only, isolated environment | FR5, FR26 | NFR5, NFR6, NFR8 | PB-08, PB-10 |
| No stock metrics | FR15, FR16, FR17, FR18 | NFR9, NFR10 | PB-05, PB-07 |
| Slow reports and export | FR7, FR24 | NFR1, NFR2 | PB-01, PB-09 |
| Manual reconciliation across branches | FR22, FR23 | NFR9, NFR10 | PB-09 |
| No production tracking SOP | FR10, FR20, FR25 | NFR9 | PB-02, PB-06, PB-10 |
| No backup, single point of failure | FR26 | NFR6, NFR8 | PB-10 |
| Unstructured DP values | FR9, FR11, FR12 | NFR3, NFR9 | PB-02, PB-03 |

## Notes

1. The DP root cause lists **FR9** because order changes recalculate invoice totals and the remaining receivable. The primary DP requirements are **FR11–FR12**.
2. The production tracking root cause lists **FR10** because design approval is part of the order-status trail, even though FR10 is design status rather than production status.
3. The environment root cause maps to **NFR5, NFR6, and NFR8**; it does not add a new functional requirement.
4. Every root cause in this table must have at least one backlog item and one measurable NFR. If a future root cause has no measurable NFR, add one before implementation.
