# Planning Formal — Realisasi FSD & Keputusan Owner

**Tanggal:** 2026-07-14  
**Scope:** `docs/fsd/*.md`, `DAFTAR_KEPUTUSAN_YANG_DIBUTUHKAN (4) (1).docx`, kode PHP aktif, migrasi SQL aktif  
**Status:** Draft eksekusi teknis berbasis kondisi riil codebase  
**Tujuan:** Memetakan mana yang sudah live, mana yang belum, mana yang bisa dikerjakan segera dengan risiko rendah, dan urutan eksekusi prioritas.

---

## 1. Ringkasan Eksekutif

Hasil pembacaan FSD, dokumen keputusan, dan pengecekan kode aktif menunjukkan:

1. **Modul Servis** adalah area paling maju implementasinya.
   - Garansi dinamis, DP servis, approval diskon, retur servis sudah live.
2. **Modul CRM / Laporan Masalah** sudah punya pondasi kuat.
   - Tiket terstruktur, seed jenis masalah, approval dasar, dan integrasi ke merge customer sudah ada.
3. **Modul Customer & Kendaraan** sudah mulai masuk tahap struktural benar.
   - Merge customer dan pindah kepemilikan kendaraan sudah punya tabel + UI + approval.
4. **Masalah paling berbahaya saat ini bukan fitur yang belum ada, tapi bug data aktif.**
   - Duplikat pelanggan masih bisa lahir dari flow create customer existing, dan berpotensi diperparah oleh proses sinkronisasi kalau mapping identitas pelanggan belum konsisten.
5. **Permintaan Owner tanggal 14 Juli 2026 cocok dengan 4 area prioritas paling bernilai:**
   - tutup akar duplikat pelanggan,
   - sempurnakan laporan masalah operasional,
   - sempurnakan alur merge customer,
   - lanjutkan alur “buat servis dari penjualan”.

---

## 2. Sumber Fakta yang Dipakai

### Dokumen FSD
- `docs/fsd/FSD_CRM.md`
- `docs/fsd/FSD_CUSTOMER.md`
- `docs/fsd/FSD_KENDARAAN.md`
- `docs/fsd/FSD_MEMBERSHIP.md`
- `docs/fsd/FSD_PENGADAAN_INVENTORY.md`
- `docs/fsd/FSD_SERVIS.md`

### Dokumen keputusan bisnis
- `DAFTAR_KEPUTUSAN_YANG_DIBUTUHKAN (4) (1).docx`

### Dokumen audit / planning existing
- `docs/IMPLEMENTATION_PLAN_WEB_BENGKEL.md`
- `docs/PLANNING_KEPUTUSAN_BISNIS_2026-07-05.md`
- `docs/planning/PLANNING_REVISI_LAPORAN_MASALAH_2026-07-10.md`
- `docs/planning/PLANNING_PINDAH_KEPEMILIKAN_KENDARAAN_2026-07-13.md`
- `docs/audit/AUDIT_HALAMAN_PELANGGAN_KENDARAAN_WEBBASE.md`
- `docs/audit/AUDIT_FORMAL_MODUL_LAPORAN_MASALAH_2026-07-11.md`
- `docs/guides/UAT_PINDAH_KEPEMILIKAN_KENDARAAN.md`

### Kode & migrasi yang diverifikasi
- `app/issue_add.php`
- `app/admin_deteksi_pelanggan_dobel.php`
- `app/customer_merge_approve.php`
- `app/kendaraan_pindah_tangan.php`
- `app/kendaraan_pindah_tangan_approve.php`
- `app/save_pelanggan_only.php`
- `app/save_pelanggan.php`
- `app/approval-diskon.php`
- `app/laporan-dp.php`
- `app/retur_servis.php`
- `app/retur_servis_approve.php`
- `db/migrations/2026-07-11_crm_tiket_terstruktur.sql`
- `db/migrations/2026-07-13_customer_merge_schema.sql`
- `db/migrations/2026-07-13_kendaraan_pindah_kepemilikan.sql`
- `db/migrations/2026-07-05_task3_ref_penjualan.sql`

---

## 3. Status Besar per FSD

| Fitur / Modul | Status Sekarang | File Terkait | Butuh Keputusan? | Risiko | Urutan Eksekusi |
|---|---|---|---|---|---|
| FSD_SERVIS — Garansi dinamis | **Sudah live** | `app/servis-carinopol-garansi.php`, `app/_include_kategori_member.php`, `app/class_whatsapp_automation.php` | Tidak untuk maintenance dasar | Rendah | Pertahankan, audit kecil saja |
| FSD_SERVIS — DP servis | **Sudah live** | `app/laporan-dp.php`, `app/_ajax/ajax-catat-dp.php`, `app/helper-functions.php` | Tidak | Rendah | Hanya bugfix jika ada |
| FSD_SERVIS — Approval diskon | **Sudah live** | `app/approval-diskon.php`, `app/helper-functions.php` | Ya jika mau threshold nominal | Rendah | Setelah prioritas 4 besar |
| FSD_SERVIS — Retur servis | **Sudah live, tapi perlu audit hybrid stok** | `app/retur_servis.php`, `app/retur_servis_approve.php` | Ya untuk kebijakan barang terpasang | Sedang | Setelah 4 prioritas awal |
| FSD_SERVIS — Snapshot komisi permanen | **Tabel sudah ada, integrasi operasional belum penuh** | `db/migrations/2026-07-11_crm_tiket_terstruktur.sql`, `app/issue_add.php` | Ya untuk formula komisi final | Sedang | Setelah keputusan komisi |
| FSD_SERVIS — Voucher cuci gratis | **Belum dibangun** | belum ada file app aktif | Ya | Rendah-Sedang | Backlog setelah keputusan owner |
| FSD_SERVIS — Reminder ganti oli | **Belum dibangun** | belum ada file app aktif | Ya | Rendah-Sedang | Backlog setelah keputusan owner |
| FSD_CRM — Tiket terstruktur | **Sudah live sebagian** | `app/issue_add.php`, `app/ajax-get-jenis-masalah.php`, `db/migrations/2026-07-11_crm_tiket_terstruktur.sql` | Tidak untuk use-case inti | Rendah | **Prioritas 2** |
| FSD_CRM — Customer 360 | **Belum utuh** | sebagian di `app/detail_pelanggan.php` | Tidak wajib untuk sprint awal | Sedang | Backlog menengah |
| FSD_CRM — Broadcast / reminder dasar | **Belum utuh** | belum ada alur formal | Ya | Rendah | Setelah workflow inti stabil |
| FSD_CUSTOMER — Merge customer | **Sudah live dasar, perlu penyempurnaan** | `app/admin_deteksi_pelanggan_dobel.php`, `app/customer_merge_approve.php`, `db/migrations/2026-07-13_customer_merge_schema.sql` | Ya untuk role final approver | Sedang | **Prioritas 3** |
| FSD_CUSTOMER — Histori profil pelanggan | **Belum dibangun** | belum ada tabel history formal | Tidak mendesak untuk bug inti | Sedang | Setelah akar duplikat ditutup |
| FSD_CUSTOMER — Search wajib sebelum create | **Belum konsisten di semua entry point** | `app/save_pelanggan_only.php`, `app/save_pelanggan.php`, form pelanggan/service | Tidak | **Tinggi** | **Prioritas 1** |
| FSD_KENDARAAN — Pindah kepemilikan | **Sudah live dasar** | `app/kendaraan_pindah_tangan.php`, `app/kendaraan_pindah_tangan_approve.php`, `app/detail_pelanggan.php` | Ya untuk approver final | Sedang | Setelah 4 prioritas awal |
| FSD_KENDARAAN — Histori plat & statistik kendaraan formal | **Belum lengkap** | belum ada modul penuh | Tidak untuk sprint awal | Sedang | Backlog menengah |
| FSD_MEMBERSHIP — Tier & threshold final | **Belum final** | `app/_include_kategori_member.php`, `tbmaster_kategori_member` | **Ya** | Sedang | Tunggu owner |
| FSD_PENGADAAN_INVENTORY — PR auto draft | **Sudah ada pondasi** | `app/pr_auto_draft.php`, `app/procurement_dashboard.php` | Ya bila mau wajib/otomatis penuh | Rendah | Setelah prioritas awal |
| FSD_PENGADAAN_INVENTORY — approval owner berdasar nominal | **Belum utuh** | modul pengadaan aktif, rule belum final | **Ya** | Sedang | Tunggu owner |
| FSD_PENGADAAN_INVENTORY — warning harga beli signifikan | **Belum final** | modul pengadaan aktif, kategori belum lengkap | **Ya** | Sedang | Tunggu owner |

---

## 4. Status Keputusan Owner — Fokus yang Bisa Jalan Dulu

| Keputusan | Inti Keputusan | Status Sekarang | Bisa Jalan Dulu Tanpa Jawaban Final? | Catatan Risiko |
|---|---|---|---|---|
| #1 | Tier membership final | Belum final | Tidak penuh | Hanya maintenance kecil boleh jalan |
| #3 | Threshold deteksi duplikat customer | Belum final | **Ya** | Mulai dari exact match WA + nama mirip sebagai kalibrasi read-only |
| #4 | Siapa approve merge customer | Belum final | **Ya, sementara pakai role existing** | Gunakan Supervisor/Owner existing dulu |
| #6 | Siapa approve pindah kepemilikan | Belum final | **Ya, sementara pakai role existing** | Role final bisa dirapikan belakangan |
| #12 | SLA laporan masalah | Belum final | **Ya** | Bisa mulai dari reminder pasif / label overdue |
| #13 | Lapor masalah lintas modul atau fokus dulu | Sebagian sudah dijawab praktis | **Ya** | Fokus dulu ke masalah transaksional prioritas |
| #17 | Retur stok servis otomatis/manual | Belum final | **Ya, audit hybrid dulu** | Jangan ubah rule stok besar sebelum owner jawab |
| #18 | Kolom resmi garansi servis | Sebagian praktis sudah jalan | **Ya** | Pondasi sudah ada di modul servis |
| #19 | Nominal approval owner pembelian | Belum final | Tidak | Butuh angka final |
| #20 | Warning % perubahan harga beli | Belum final | Tidak penuh | Bisa siapkan hook tapi jangan aktif penuh |
| #24 | Draft PR otomatis saat stok minimum | Sebagian pondasi sudah ada | **Ya** | Low risk, fitur kenyamanan |
| #25 | Laporan rekap kunjungan per cabang vs semua cabang | Belum final | **Ya, audit & siapkan 2 mode** | Jangan ubah perilaku produksi tanpa konfirmasi owner |

---

## 5. Empat Prioritas Awal yang Harus Dikerjakan Dulu

### Prioritas 1 — Tutup akar duplikat pelanggan

**Masalah riil:** duplikat pelanggan bisa lahir dari flow create customer existing, dan berpotensi diperparah oleh sinkronisasi Access bila mapping identitas tidak konsisten.

**Bukti lapangan:**
- `docs/audit/AUDIT_HALAMAN_PELANGGAN_KENDARAAN_WEBBASE.md` menemukan flow `nopelanggan = nopol`.
- `app/save_pelanggan_only.php` menggabungkan logika pelanggan+kendaraan secara berbahaya.
- search pelanggan lama belum cukup memaksa user memilih data existing sebelum create baru.

| Fitur | Status Sekarang | File Terkait | Butuh Keputusan? | Risiko | Urutan Eksekusi |
|---|---|---|---|---|---|
| Stop pola `nopelanggan = nopol` | Belum aman | `app/save_pelanggan_only.php` | Tidak | **Tinggi** | 1 |
| Wajib cari pelanggan dulu sebelum create | Belum konsisten | `app/pelanggan.php`, form servis/kasir, `app/save_pelanggan.php` | Tidak | Tinggi | 2 |
| Search pelanggan tampil multi hasil, bukan tebakan 1 hasil | Sebagian sudah diperbaiki, perlu audit menyeluruh | `app/_ajax/ajax-cari-pelanggan.php`, modal/customer picker lain | Tidak | Sedang | 3 |
| Audit pengaruh sync Access ke duplikat | Belum dipetakan penuh | script sync, tabel hasil sync, audit mapping customer/vehicle | Tidak | Sedang | 4 |
| Tambah guard duplicate-safe saat sync | Belum jelas | script sync harian Access -> MySQL | Tidak | Sedang | 5 |

**Aksi teknis yang disarankan:**
1. Pisahkan identitas pelanggan dari identitas kendaraan di seluruh form “pelanggan + kendaraan”.
2. Jadikan `nopelanggan` generated code independen, bukan `nopol`.
3. Tambahkan mode “pakai pelanggan existing” sebagai jalur default.
4. Audit script sync apakah insert customer baru masih berbasis nopol / nama teks / WA yang tidak stabil.
5. Tambah log audit insert customer dari sync agar sumber duplikat bisa dibedakan: web form vs sync.

---

### Prioritas 2 — Sempurnakan laporan masalah operasional

**Target owner yang sudah cocok dengan implementasi existing:**
- salah input nopol saat input servis,
- revisi salah input persentase pengerjaan mekanik,
- revisi salah input persentase pengerjaan kepala mekanik,
- kaitan ke penggabungan data pelanggan bila ditemukan dobel.

| Fitur | Status Sekarang | File Terkait | Butuh Keputusan? | Risiko | Urutan Eksekusi |
|---|---|---|---|---|---|
| Ticketing terstruktur dasar | Sudah live | `app/issue_add.php`, `db/migrations/2026-07-11_crm_tiket_terstruktur.sql` | Tidak | Rendah | Sudah ada |
| UX laporan masalah | Sudah membaik, perlu final polish | `app/issue_add.php` | Tidak | Rendah | 1 |
| Approval operasional | Sudah ada, perlu konsistensi bahasa & guardrail | `app/issue_add.php` | Tidak | Rendah | 2 |
| Auto-eksekusi beberapa jenis tiket | Sebagian ada | `app/issue_add.php` | Tidak | Sedang | 3 |
| SLA / overdue / reminder | Belum ada | `app/issue_add.php`, log issue | Ya untuk angka SLA final, tapi bisa label dulu | Rendah | 4 |
| Tiket referensi ke merge customer | Sudah tersambung sebagian | `app/customer_merge_approve.php`, `app/issue_add.php` | Tidak | Rendah | 5 |

**Aksi teknis yang disarankan:**
1. Finalkan 3 jenis masalah prioritas owner sebagai jalur utama paling mudah dipakai user.
2. Bedakan jelas tiket transaksional vs tool admin cleanup data.
3. Tambahkan status “perlu aksi saya” / overdue ringan tanpa mengubah business rule inti.
4. Pastikan setiap approve/reject menulis log yang mudah dibaca operasional.

---

### Prioritas 3 — Sempurnakan alur merge customer

**Status sekarang:** pondasi merge sudah ada, tetapi masih perlu dibuat lebih aman, lebih mudah dipahami, dan lebih berbasis kandidat duplikat yang benar.

| Fitur | Status Sekarang | File Terkait | Butuh Keputusan? | Risiko | Urutan Eksekusi |
|---|---|---|---|---|---|
| Pengajuan merge manual | Sudah live | `app/admin_deteksi_pelanggan_dobel.php` | Tidak | Rendah | Sudah ada |
| Approval merge | Sudah live | `app/customer_merge_approve.php` | Ya untuk approver final | Sedang | 1 |
| Redirect alias permanen | Sudah ada dasar | `customer_alias`, `app/customer_merge_approve.php` | Tidak | Rendah | 2 |
| Skor kandidat / cluster duplikat | Belum ada formal | `app/admin_deteksi_pelanggan_dobel.php` | Ya untuk threshold final, tapi bisa exact-first | Sedang | 3 |
| Re-point seluruh relasi customer secara aman | Sebagian ada | `app/customer_merge_approve.php` | Tidak | Sedang-Tinggi | 4 |
| Rollback manual / snapshot lebih kaya | Snapshot ada, rollback formal belum | `customer_merge_log` | Tidak mendesak awal | Sedang | 5 |

**Aksi teknis yang disarankan:**
1. Ubah halaman deteksi jadi cluster kandidat, bukan hanya form manual.
2. Tambah skor berbasis exact WA, exact phone, nama sangat mirip, alamat mirip.
3. Tambah preview dampak merge: jumlah servis, kendaraan, transaksi.
4. Audit apakah relasi lain selain `tblservice` perlu ikut dipindah saat merge.
5. Tetapkan merge sebagai tool admin/supervisor, bukan alat kasir umum.

---

### Prioritas 4 — Lanjutkan “buat servis dari penjualan”

**Makna bisnis yang paling masuk akal dari arahan owner:**
- transaksi penjualan sparepart ke customer bisa dikonversi jadi service order,
- item barang dari nota penjualan otomatis ikut masuk ke servis,
- admin tinggal tambah jasa dan teknisi, tanpa input ulang barang satu-satu.

**Status sekarang:** baru ada pondasi referensi data, belum terlihat alur end-to-end yang matang.

| Fitur | Status Sekarang | File Terkait | Butuh Keputusan? | Risiko | Urutan Eksekusi |
|---|---|---|---|---|---|
| Kolom ref penjualan asal di servis | Sudah ada migrasi | `db/migrations/2026-07-05_task3_ref_penjualan.sql` | Tidak | Rendah | Sudah ada |
| Penanda asal barang servis | Sudah ada migrasi | `db/migrations/2026-07-05_task3_ref_penjualan.sql` | Tidak | Rendah | Sudah ada |
| Tombol/flow “Buat Servis dari Penjualan” | Belum terpetakan penuh | modul penjualan + servis | Tidak | Sedang | 1 |
| Auto-copy item penjualan ke `tblservis_barang` | Belum jelas utuh | handler penjualan/servis | Tidak | Sedang | 2 |
| Validasi 1 nota jangan double-convert liar | Belum jelas | service create handler | Tidak | Sedang | 3 |
| Audit stok & HPP lintas penjualan-servis | Belum jelas | penjualan/servis/tbstok/HPP | Tidak | Sedang-Tinggi | 4 |

**Aksi teknis yang disarankan:**
1. Petakan dulu titik entry penjualan umum yang paling relevan.
2. Tambahkan tombol “Buat Servis” hanya pada nota yang customer-facing dan belum pernah dikonversi.
3. Saat convert, sistem buat service draft dengan referensi nota asal.
4. Item barang dari nota masuk ke `tblservis_barang` dengan `asal_barang='PENJUALAN'`.
5. Di layar servis, user hanya tambah jasa / mekanik / kepala mekanik / admin.
6. Tambah guard agar nota yang sama tidak bikin dua servis aktif tanpa sengaja.

---

## 6. Penyesuaian DB yang Disarankan Sebelum atau Saat Eksekusi

| Kebutuhan DB | Alasan | Wajib Sekarang? | Catatan |
|---|---|---|---|
| `nopelanggan` independen dari `nopol` | Memutus akar duplikat customer | **Ya** | Minimal di flow baru, data lama bisa bertahap |
| Log sumber pembuatan customer (`WEB_FORM`, `SYNC_ACCESS`, `MERGE`, dll) | Bedakan asal duplikat | Ya, sangat membantu audit | Bisa kolom baru atau tabel log |
| Index pencarian customer (`namapelanggan`, `telephone`, `no_wa`) | Search cepat & akurat | Ya | Penting untuk FR-01 customer |
| Histori atribut customer | Audit perubahan data | Tidak untuk sprint awal | Sprint berikutnya |
| `kendaraan_plat_history` | Bedakan ganti plat vs ganti owner | Tidak untuk sprint awal | Sprint kendaraan lanjut |
| Guard convert penjualan -> servis | Cegah double convert | Ya untuk fitur prioritas 4 | Bisa flag / ref relation |
| Audit stok retur servis hybrid | Cocokkan keputusan #17 | Belum wajib | Tunggu jawaban owner bila ubah rule besar |

---

## 7. Jawaban Ringkas untuk Pertanyaan Owner yang Sudah Bisa Dijawab Sekarang

### Q1 — “Input penjualan ke pelanggan jika ingin sekalian dipasangkan” maksudnya apa?

**Interpretasi bisnis paling tepat untuk web ini:**
1. Kasir buat transaksi penjualan sparepart ke pelanggan.
2. Jika barang itu ternyata langsung dipasang di bengkel, user klik **Buat Servis dari Penjualan**.
3. Sistem membuat data servis baru dengan pelanggan yang sama.
4. Barang dari nota penjualan otomatis masuk ke item servis.
5. User tinggal pilih / tambah jasa servis dan personel pengerjaan.

**Contoh riil:**
- Customer beli oli, busi, filter udara.
- Ternyata mau langsung dipasang.
- Tidak perlu input ulang 3 barang itu di modul servis.
- Servis draft langsung terisi barang, user tinggal tambah jasa “servis ringan” / “ganti oli”.

### Q3 — “Kenapa bisa dobel pada versi web base?”

**Jawaban faktual berdasar kode aktif:**
- Ya, versi web base memang bisa membuat data dobel baru.
- Penyebab utamanya bukan hanya user salah pilih, tetapi juga desain flow create customer lama.
- Ada flow yang menjadikan **nomor polisi motor sebagai kode pelanggan**, sehingga 1 orang dengan 2 motor bisa tercatat sebagai 2 pelanggan berbeda.
- Search pelanggan juga dulu tidak cukup kuat memaksa user memilih data existing yang benar.
- Selain itu, perlu audit tambahan apakah proses sync Access ikut menambah duplikat karena mapping identitas pelanggan tidak konsisten.

Jadi ini **bukan salah persepsi**, tetapi memang ada risiko struktural nyata di web aktif.

---

## 8. Urutan Eksekusi yang Direkomendasikan

### Sprint 1 — Minggu Terdekat
1. Tutup akar duplikat pelanggan.
2. Sempurnakan laporan masalah operasional.
3. Audit penuh relasi merge customer.
4. Petakan alur buat servis dari penjualan.

### Sprint 2 — Setelah Sprint 1 Stabil
1. Finalkan merge customer berbasis cluster kandidat.
2. Eksekusi fitur buat servis dari penjualan end-to-end.
3. Tambahkan overdue / SLA ringan di laporan masalah.
4. Audit pengaruh sync Access terhadap customer duplicate.

### Sprint 3 — Menunggu Keputusan Owner
1. Membership final.
2. Approval nominal pembelian.
3. Warning harga beli signifikan.
4. Voucher cuci / reminder oli bila disetujui.

---

## 9. Checklist Implementasi Langsung Setelah Dokumen Ini

### Empat item yang dikerjakan dulu
- [ ] Tutup akar duplikat pelanggan
- [ ] Sempurnakan laporan masalah operasional
- [ ] Sempurnakan alur merge customer
- [ ] Lanjutkan buat servis dari penjualan

### Sub-task teknis paling awal
- [ ] Audit semua entry point create customer
- [ ] Audit apakah sync Access membuat customer baru berbasis nopol/nama/WA
- [ ] Audit semua query search pelanggan yang masih single-result / ambigu
- [ ] Audit relasi customer apa saja yang harus ikut dipindah saat merge
- [ ] Petakan file penjualan yang akan jadi titik tombol “Buat Servis”

---

## 10. Kesimpulan

Planning ini sengaja memisahkan tiga hal:
1. **yang sudah live** dan cukup dipertahankan,
2. **yang belum live tapi bisa dikerjakan segera dengan risiko rendah**,
3. **yang memang harus tunggu keputusan owner**.

Dengan pemisahan ini, tim tidak perlu berhenti total menunggu jawaban owner.  
Empat item prioritas yang diminta Owner **bisa langsung dikerjakan**, dan semuanya punya dampak langsung ke operasional harian web aktif.

