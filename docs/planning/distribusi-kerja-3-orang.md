# Distribusi Kerja 3 Orang — Capstone POS Alja Digital Printing

Dokumen ini menjelaskan pembagian kerja untuk tim berisi 3 orang pada redevelopment POS Alja Digital Printing. Isinya disusun agar bisa dibagikan ke teman tim dan dipakai sebagai dasar pembuatan backlog Jira.

## 1. Asumsi

1. Target arsitektur mengikuti `AGENTS.md`: Laravel 13 sebagai API, React 19 + TypeScript sebagai SPA, PostgreSQL, Sanctum, Reverb, dan Tailwind CSS 4.
2. UI Livewire/Blade yang lama hanya menjadi referensi perilaku. Jangan menambah fitur ke UI lama jika tim sedang melakukan redevelopment ke API-first + SPA.
3. Dokumen target yang menjadi acuan:
   - `docs/requirements/fr.md` — FR1–FR26.
   - `docs/requirements/nfr.md` — NFR1–NFR10.
   - `docs/requirements/access-matrix.md` — peran dan batas cabang.
   - `docs/requirements/product-backlog.md` — PB-01–PB-10.
   - `docs/requirements/implementation-gaps.md` — status implementasi, bukan bagian proposal.
   - `docs/architecture/erd.md`, `sequence-diagrams.md`, `api-contracts.md`, `use-case-diagram.md`.
4. Ketiga orang dianggap bisa bekerja full-stack. Jika kemampuan tidak merata, lihat bagian 10.

## 2. Prinsip Pembagian Kerja

1. **Bagi berdasarkan vertical slice, bukan layer.** Satu orang memiliki satu area fitur secara end-to-end: migration, API, UI, test, dan dokumentasi.
2. **Jangan membagi menjadi “satu orang backend, satu frontend, satu database”.** Pola itu membuat frontend menunggu API, API menunggu schema, dan integrasi baru dilakukan di akhir.
3. **Setiap orang memiliki satu area cross-cutting.** Area ini bukan pekerjaan penuh waktu, tetapi menjadi tanggung jawab utama untuk review dan standar.
4. **Contract-first.** ERD dan bentuk API disepakati sebelum coding fitur. Perubahan kontrak harus memperbarui dokumen dan disetujui pemilik area.
5. **Setiap orang menulis test untuk irisannya.** Tidak ada orang yang hanya menjadi “tukang test” atau “tukang dokumentasi”.
6. **Satu feature branch per irisan.** Jangan bekerja langsung di `main`.

## 3. Ringkasan Tiga Workstream

| Pengembang | Fokus end-to-end | FR utama | Backlog | Cross-cutting lead |
| :--- | :--- | :--- | :--- | :--- |
| **A** | Identity, Catalog, Administration | FR1–FR7, FR25 | PB-01, PB-08, PB-10 | Security/RBAC, API contracts, audit, dokumen FR/NFR/access |
| **B** | Sales dan Payments | FR8–FR14, FR21 | PB-02, PB-03, PB-04, PB-06 | SPA shell, design system, real-time, print, UX/accessibility |
| **C** | Inventory, Production, Reporting | FR15–FR20, FR22–FR24, FR26 | PB-05, PB-07, PB-09, PB-10 | Database/migration, CI/CD, Reverb, backup, integration test, deployment |

Pembagian ini memberi setiap orang fitur yang bisa didemokan di akhir sprint, bukan hanya potongan layer.

## 4. Detail Tanggung Jawab

### A — Identity, Catalog, Administration

**Fokus fitur**

- Autentikasi: login, logout, ubah kata sandi, session Sanctum.
- RBAC: `owner`, `manager`, `cashier`, `designer`, `production`.
- Branch scope: non-owner selalu terikat `branch_id`; non-owner tanpa cabang ditolak.
- User management: owner semua user; manager hanya cashier/designer/production di cabangnya.
- Branch management.
- Product, category, finishing/display, dan diskon kuantitas per kategori. Material master dan satuannya dimiliki C.
- Product search, filter, dan pagination.
- Audit log untuk aksi kritis.
- Backup manual dan terjadwal.

**FR yang dimiliki:** FR1–FR7, FR25, FR26.
**Backlog:** PB-01, PB-08, PB-10.
**Cross-cutting:** security/RBAC, policy, API contract, audit, dokumen FR/NFR/access-matrix.

**Output per sprint**

- Sprint 0: auth, RBAC, branch scope, user CRUD, policy, API skeleton.
- Sprint 1: PB-08 acceptance, product/category/finishing/display/discount CRUD, product search/pagination.
- Sprint 2: branch/user administration, audit log coverage.
- Sprint 3: RBAC hardening, audit review, backup manual/terjadwal, security review.

### B — Sales dan Payments

**Fokus fitur**

- Customer: pencarian, pembuatan di halaman terpisah, edit data, institusi dan alamat.
- Order: pembuatan invoice, item produk/bahan/finishing/display, kalkulasi harga, target selesai.
- Order update sebelum produksi dan selama belum lunas.
- Design status: pending, in design, approved.
- Down payment, settlement, diskon, dan validasi sisa tagihan.
- Multiple DP sampai lunas.
- Invoice A4 dan nota 80 mm.
- Pengeluaran operasional Owner/Manajer dengan kategori tetap.
- Pickup pesanan.

**FR yang dimiliki:** FR8–FR14, FR21.
**Backlog:** PB-02, PB-03, PB-04, PB-06.
**Cross-cutting:** SPA shell, design system, TanStack Query, real-time invalidation, print layout, UX/accessibility.

**Output per sprint**

- Sprint 0: SPA shell, design token, API client, auth screens, route guard.
- Sprint 1: customer search/create, order create, invoice print, DP, settlement, nota.
- Sprint 2: order update, design status, multiple DP, pickup, expense Owner/Manajer, responsive/print, real-time.
- Sprint 3: UI laporan/ekspor, UX polish, aksesibilitas.

### C — Inventory, Production, Reporting

**Fokus fitur**

- Material master dengan satuan `m2`, `sheet`, dan `pcs`, serta stok per cabang.
- Pembelian bahan baku: purchase header, purchase item, unit cost.
- Stock ledger.
- Penyesuaian stok dengan alasan.
- Peringatan stok minimum.
- Production queue: semua peran bisa membaca, filter status, produksi mengubah status.
- Status produksi: queued, processing, completed.
- Pemakaian bahan per pesanan.
- Waste logging dari baris antrean.
- Laporan keuangan periodik.
- Export Excel/PDF.
- Backup terjadwal dan restore drill, bersama A.
- CI/CD, Reverb private channel, deployment.

**FR yang dimiliki:** FR15–FR20, FR22–FR24, FR26.
**Backlog:** PB-05, PB-07, PB-09, PB-10.
**Cross-cutting:** database/migration, CI/CD, Reverb, test harness, integration test, deployment, implementation-gaps.

**Output per sprint**

- Sprint 0: ERD, migration, CI/CD, Reverb, test harness, deployment.
- Sprint 1: inventory foundation, material unit, branch stock, stock ledger.
- Sprint 2: purchase, adjustment, low-stock, production queue/filter/status, usage/waste inline.
- Sprint 3: report query, export, backup terjadwal, integration test, deployment final.

## 5. Rencana Sprint

### Sprint 0 — Fondasi

| A | B | C |
| :--- | :--- | :--- |
| Auth, RBAC, branch scope, policy, user CRUD, API skeleton | SPA shell, design token, UI primitive, API client, auth screens | ERD, migration, CI/CD, Reverb, test harness, deployment |

Semua orang menyepakati ERD dan API contract di sprint ini. Jangan mulai fitur sebelum auth dan branch scope stabil.

### Sprint 1 — Fondasi, Katalog, Pesanan, Pembayaran

| A | B | C |
| :--- | :--- | :--- |
| PB-08 auth/RBAC, product/category/finishing/display/discount, product search/pagination | Customer search/create, order create Produk -> Bahan -> Finishing -> Display, invoice print, DP, settlement, nota | Inventory foundation: material unit, branch stock, stock ledger |

B boleh memakai data produk seed/mock selama API katalog A belum selesai. PB-08 sekarang berada di Sprint 1 agar fondasi auth dan katalog selesai sebelum fitur transaksi.

### Sprint 2 — Inventori, Produksi, dan Revisi Sprint Review 1

| A | B | C |
| :--- | :--- | :--- |
| Branch/user administration, audit log, category discount review | Order update DP, design status, expense Owner/Manajer, pickup, responsive/print, real-time | Purchase, stock adjustment, low-stock, production queue/filter/status, usage/waste inline |

C dan B harus menyepakati bentuk data antrean produksi sebelum UI queue dibuat. B dan A menyepakati model harga bahan + finishing/display + diskon kategori sebelum menghapus matriks harga.

### Sprint 3 — Laporan, Audit, Backup, Hardening

| A | B | C |
| :--- | :--- | :--- |
| RBAC hardening, audit review, backup manual/terjadwal, security review | UI laporan/ekspor, UX polish, aksesibilitas | Report query, export Excel/PDF, backup terjadwal, integration test, deployment |

Sprint ini juga dipakai untuk memperbaiki temuan `implementation-gaps.md` dan menyiapkan demo sidang.

## 6. Dependency dan Critical Path

```text
Auth/RBAC
  -> Catalog/Price model (material + finishing/display + category discount)
    -> Orders
      -> Payments
      -> Inventory
        -> Production
          -> Reporting/Export/Backup
```

Aturan dependency:

1. A menyelesaikan auth/RBAC dan PB-08 sebelum B dan C membuat endpoint terlindungi.
2. A menyelesaikan katalog dan model harga bahan/finishing/display/diskon kategori sebelum B menyelesaikan order create. B boleh mulai dengan mock produk.
3. B menyelesaikan order sebelum C membuat production queue dan laporan.
4. C menyelesaikan inventory dan satuan bahan sebelum B menambahkan validasi stok pada order.
5. C mulai reporting setelah data order, payment, dan stock tersedia. Gunakan seed/fixture selama menunggu.
6. Audit tidak dikerjakan di akhir. A menambahkan instrumentasi audit saat setiap aksi selesai.
7. Penghapusan tabel mesin dan matriks harga dilakukan setelah migrasi data dan backup, bukan bersamaan dengan perubahan UI.

## 7. Cross-Cutting Ownership

| Area | Pemilik | Aturan |
| :--- | :--- | :--- |
| Security, RBAC, policy, branch scope | A | Setiap endpoint diuji guest 401, wrong role 403, cross-branch denial, validation 422, dan happy path. |
| SPA shell, design system, shared component | B | A dan C membangun screen dari primitive B; tidak boleh membuat komponen satu-off. |
| Migration, ERD, CI/CD, Reverb, backup | C | Migration direview A; migration yang sudah merge tidak boleh diedit. |
| API contract | A | Bentuk endpoint, resource, dan error disepakati sebelum coding; update `api-contracts.md`. |
| Test | Semua | Setiap orang menulis test untuk irisannya; C memiliki integration/E2E; A memiliki security test; B memiliki frontend test. |
| Dokumentasi | A/B/C | A: FR/NFR/access/API. B: use case/frontend/print. C: ERD/sequence/deployment/gaps. Semua mereview. |
| Sidang | Semua | A: requirement/security/arsitektur. B: demo/UI/UX. C: deployment/testing/hasil. |

## 8. Aturan Kolaborasi

1. Branch: `feat/<stream>/<slice>`, `fix/<stream>/<issue>`, `docs/<topic>`.
2. Satu PR untuk satu irisan fitur. PR besar dipecah.
3. Setiap PR direview minimal satu orang lain:
   - A mereview security, auth, policy, dan API contract.
   - B mereview frontend, UX, dan aksesibilitas.
   - C mereview migration, query, dan transaksi.
4. Sebelum coding, update dokumen kontrak (ERD/API) dan setujui bersama.
5. Daily sync 15 menit: selesai, berikutnya, blocker.
6. Weekly integration: gabungkan branch, jalankan test, demo internal.
7. Jangan mengubah file milik stream lain tanpa koordinasi.
8. Jangan merge ke `main` tanpa review dan test.
9. Jangan menunda migration atau auth ke akhir sprint.

## 9. Definition of Done per Task

Sebuah task dianggap selesai jika:

1. Perilaku sesuai FR dan acceptance criteria.
2. Backend test lulus:
   - guest 401,
   - wrong role 403,
   - cross-branch denial,
   - validation 422,
   - happy path.
3. Response produksi tidak mengandung price/cost/profit/payment fields.
4. Frontend typecheck, lint, test, dan build lulus.
5. UI dicek di 390, 768, dan 1440 px.
6. Dokumen `docs/` terkait diperbarui.
7. PR direview dan disetujui.
8. Tidak ada secret atau data pribadi di commit.

## 10. Jika Kemampuan Tim Tidak Merata

- **Jika B satu-satunya yang kuat di frontend:** B memiliki shell dan design system; A dan C tetap membuat feature screen dari primitive B. B menjadi reviewer UI, bukan satu-satunya yang menulis UI.
- **Jika A satu-satunya yang kuat di backend:** A mengambil auth, katalog, dan security; C mengambil payment/inventory backend; A tetap mereview security.
- **Jika ada anggota junior:** beri satu irisan vertikal yang jelas, misalnya catalog CRUD + test, dengan pairing A atau B. Jangan beri hanya dokumentasi atau hanya testing.
- **Jika semua full-stack:** gunakan pembagian A/B/C di atas tanpa perubahan.

## 11. Pembagian Materi Sidang

| Bagian | Penanggung jawab | Isi |
| :--- | :--- | :--- |
| Requirement dan ruang lingkup | A | FR1–FR26, NFR1–NFR10, access matrix, root cause |
| Arsitektur dan keamanan | A | API-first, RBAC, branch scope, audit |
| Demo alur utama | B | Catalog, order, payment, invoice, pickup |
| UI/UX dan responsive | B | Design system, dark mode, 390/768/1440 px |
| Inventori dan produksi | C | Stock, purchase, adjustment, production queue, waste |
| Laporan dan ekspor | C | Dashboard, report, Excel/PDF |
| Deployment dan pengujian | C | CI/CD, Reverb, backup, test result |
| Tanya jawab | Semua | Masing-masing menjawab area yang dikuasai |

## 12. Anti-Pattern yang Harus Dihindari

1. Membagi tim menjadi backend/frontend/database saja.
2. Memberi satu orang seluruh dokumentasi dan materi sidang.
3. Menunda auth, branch scope, dan migration ke akhir.
4. Big-bang integration di akhir sprint.
5. Frontend menunggu API selesai tanpa mock/contract.
6. Dua orang mengedit file yang sama tanpa koordinasi.
7. Merge ke `main` tanpa review.
8. Menambah fitur ke UI Livewire lama saat targetnya SPA.
9. Menandai task selesai hanya karena code sudah ada.

## 13. Referensi Dokumen

- `docs/requirements/fr.md`
- `docs/requirements/nfr.md`
- `docs/requirements/access-matrix.md`
- `docs/requirements/product-backlog.md`
- `docs/requirements/implementation-gaps.md`
- `docs/requirements/sprint-review-1.md`
- `docs/architecture/erd.md`
- `docs/architecture/sequence-diagrams.md`
- `docs/architecture/api-contracts.md`
- `docs/architecture/use-case-diagram.md`
- `docs/planning/jira/README.md`
