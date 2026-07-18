# Planning Eksekusi — 3 Keputusan Resiko Rendah

**Tanggal:** 2026-07-10
**Sumber:** `DAFTAR_KEPUTUSAN_YANG_DIBUTUHKAN (4) (1).docx` — Keputusan #13, #18, #24 (Prioritas Rendah, Rekomendasi Teknis ✅)
**Status:** Draft planning, belum eksekusi kode

---

## #13 — Sistem Lapor Masalah: Generik Lintas Modul

**FSD rujukan:** `FSD_CRM.md` FR-02/FR-03, Open Item O3.
**Rekomendasi disepakati:** Rancang generik dari awal (`kategori` sebagai kolom, bukan tabel terpisah per divisi).

### Status Saat Ini (dicek ke skema riil)
- Tabel `tbl_issue` / `tbl_issue_progress_log` **belum ada** di DB (dicek `tools/sql/fitmotor_dbbengkel_FIXED_V7.sql` — 0 match `CREATE TABLE tbl_issue`).
- Tidak ada file `app/*komplain*`, `*issue*`, atau `*lapor*` existing — ini murni fitur baru, bukan migrasi.
- Modul lain yang disebut FSD sebagai calon konsumen tiket generik: merge Customer (`FSD_CUSTOMER.md` FR-05), pindah kepemilikan kendaraan (`FSD_KENDARAAN.md` FR-05) — keduanya **juga belum dibangun** (masih level FSD draft, belum ada tabel `customer_merge_log`/`kepemilikan_kendaraan` di skema).

### Rencana Kerja
1. **Skema baru** (sesuai `FSD_CRM.md` section 5.1, tidak perlu ubah tabel existing):
   - `tbl_issue` (`id_issue` PK format `ISS-YYYYMMDD-####`, `kategori` ENUM termasuk `data_pelanggan`/`data_kendaraan`/`komisi`/`stok`/`sistem`/`lainnya` — kategori sudah generik dari awal sesuai rekomendasi, walau modul stok/komisi belum dipakai sekarang).
   - `tbl_issue_progress_log` (audit trail tiap perubahan status).
2. **CRUD dasar:** form ajukan tiket (CS/Kasir), list + approve/reject (Supervisor/Owner), keduanya idealnya nempel di menu existing (`menu_config.php` sudah punya struktur RBAC per role, tinggal tambah entry menu baru).
3. **Belum perlu** hook ke merge Customer/pindah kepemilikan kendaraan — dua fitur itu sendiri belum dibangun, jadi FR-02 `tbl_issue` untuk sekarang berdiri sendiri (generik) tanpa trigger eksekusi otomatis ke modul lain.
4. **RBAC:** cek pola existing di `lib/rbac.php` / `config/rbac.php` untuk role Supervisor — dipakai juga nanti oleh Keputusan #4 (approval merge Customer) sehingga 1x kerja RBAC dipakai 2 keputusan.

### File Terdampak
- Baru: `app/issue_add.php`, `app/issue_list.php`, `app/issue_approve.php` (nama indikatif, sesuaikan konvensi existing `_add.php`/`_edit_proses.php`).
- Update: `app/menu_config.php` (entry menu baru + role mapping).

### Resiko & Rollback
- Resiko rendah: tabel baru, tidak menyentuh tabel existing, tidak ada data lama yang bisa rusak. Rollback = `DROP TABLE` kalau batal.
- Perlu keputusan kecil tambahan sebelum coding: siapa role "Supervisor" di RBAC existing (O3 `FSD_CUSTOMER.md` juga nanya ini — jawab sekali dipakai 2 tempat).

### Estimasi
Kecil–Sedang (2 tabel baru + 3 file CRUD + 1 entry menu). Tidak ada dependency ke modul lain yang belum jadi.

---

## #18 — Kolom Resmi Garansi Servis

**FSD rujukan:** `FSD_SERVIS.md` Q5 / gap I-02.
**Rekomendasi disepakati:** Ya, perlu kolom resmi (bukan keyword bebas di catatan).

### Status Saat Ini — TEMUAN PENTING: SUDAH DIBANGUN SEBAGIAN
FSD ditulis 2026-07-04 dengan asumsi garansi "cuma dideteksi dari pola teks (`*GARAN*`) di kolom keterangan". Setelah dicek kode riil, **ini sudah tidak akurat** — kolom resmi garansi **sudah ada dan dipakai**:

- `tblservice.is_garansi`, `ref_no_service_original`, `tanggal_garansi_expire`, `mekanik_original`, `komisi_garansi_mode` — sudah di-INSERT di `app/servis-garansi.php` (label kode `F1-A`/`F1-B`, tanggal kerja 2026-07-04).
- Masa garansi **dinamis per tier member** (bukan flat 7 hari) — fungsi `getMasaGaransiHari()` baca `tbmaster_kategori_member.masa_garansi_hari`.
- Dipakai juga di `app/servis-carinopol-garansi.php`, `app/servis-garansi-rst.php`, `app/menu_config.php`.

**Kesimpulan:** Keputusan #18 kemungkinan besar **sudah efektif diputuskan & dieksekusi** duluan (kode sudah jalan), FSD dokumennya yang belum diupdate mengikuti. Bukan "belum ada kolom resmi" seperti tertulis di FSD/docx.

### Rencana Kerja (berubah jadi AUDIT, bukan BUILD)
1. **Audit cakupan:** cek apakah SEMUA titik pembuatan servis garansi (reguler garansi, jemput garansi kalau ada) konsisten set `is_garansi`, atau ada jalur lama yang masih andalkan pattern teks `keterangan LIKE '%GARAN%'`.
   - Cek: `app/servis-input-garansi.php`, `app/servis-reguler-jemput.php` (kalau ada varian garansi+jemput).
2. **Audit laporan:** cek `app/lap_servis.php` dan laporan lain — apakah kolom/filter garansi (`is_garansi`) sudah tampil di laporan, atau laporan masih pakai pattern teks lama (kalau iya, ini bug tersisa, bukan keputusan owner lagi).
3. **Update FSD_SERVIS.md:** tandai Q5/I-02 sebagai **"Sudah diimplementasikan 2026-07-04 (F1-A/F1-B)"**, pindahkan dari Open Question ke Decision Log — supaya dokumen gak nyesatin pembaca berikutnya.
4. **Update `DAFTAR_KEPUTUSAN_YANG_DIBUTUHKAN.docx`:** poin #18 tandai sudah selesai, gak perlu jawaban Owner lagi.

### File Terdampak
- Audit only: `app/servis-input-garansi.php`, `app/lap_servis.php`, laporan terkait.
- Dokumentasi: `docs/fsd/FSD_SERVIS.md`, docx keputusan (perlu versi baru/addendum).

### Resiko & Rollback
- Resiko sangat rendah — ini audit + dokumentasi, tidak ada perubahan skema/kode baru kecuali audit menemukan gap nyata (baru jadi task terpisah).

### Estimasi
Kecil — audit 2-3 file + update 2 dokumen. Jauh lebih ringan dari perkiraan awal (yang mengira perlu ALTER TABLE + fitur baru).

---

## #24 — Auto-Draft PR Saat Stok Hampir Habis

**FSD rujukan:** `FSD_PENGADAAN_INVENTORY.md` section 10, Q6.
**Rekomendasi disepakati:** Bangun sekarang, logic sederhana (bandingkan stok vs `stokmin`, generate draft PR).

### Status Saat Ini (dicek ke skema & file riil)
- `tblitem_stok` (PK `noitem`+`kode_cabang`): kolom `stokmin`, `stok_maks`, `stok_awal`, `rakbarang` — **per cabang**, sudah ada, tidak perlu diubah.
- Stok aktual **tidak** disimpan sebagai kolom running total di `tblitem_stok` — harus dihitung dari `tbstok` (ledger keluar-masuk semua tipe transaksi termasuk servis `tipe='4'`), sesuai catatan `FSD_SERVIS.md` section 11 dan `FSD_PENGADAAN_INVENTORY.md` section 10.
- Alur PR manual sudah lengkap & jalan: `tblpurchase_request_header`/`tblpurchase_request_detail` (kolom `status_pr`, `status_item` enum `pending/approved/rejected/po_created`, `qty_po` sudah anti-dobel-PO) — file `app/pr_add.php` sudah ada sebagai pola insert PR yang bisa dicontoh/reuse untuk draft otomatis.
- Belum ada scheduler/cron di aplikasi yang terlihat (pola yang ada baru auto-refresh dashboard tiap 30 detik via AJAX polling, bukan server-side cron) — perlu tentukan mekanisme trigger.

### Rencana Kerja
1. **Query dasar reorder check** (per cabang, per item):
   ```sql
   -- stok aktual = SUM(masuk) - SUM(keluar) dari tbstok per noitem+kode_cabang
   -- bandingkan ke tblitem_stok.stokmin
   ```
   Perlu konfirmasi exact kolom `tbstok` (tipe masuk/keluar, noitem, kode_cabang) sebelum tulis query final — belum dicek di sesi ini karena fokus ke 3 tabel lain dulu.
2. **Trigger mekanisme — pilih salah satu:**
   - **Opsi A (lebih murah, sesuai pola app existing):** cek saat halaman `procurement_dashboard.php` atau `pr_add.php` dibuka — tampilkan badge "N item di bawah stok minimal, [Buat Draft PR]" — tidak perlu cron/scheduler baru.
   - **Opsi B (lebih otomatis tapi butuh infrastruktur baru):** cron job harian (Laragon Task Scheduler / Windows Task Scheduler karena environment ini di Laragon, bukan Linux server) yang jalankan script PHP CLI, insert draft PR otomatis ke `tblpurchase_request_header` (`status_pr='draft'`) tanpa perlu staf buka halaman dulu.
   - **Rekomendasi:** mulai dari Opsi A (effort kecil, tidak nambah moving part infrastruktur), upgrade ke Opsi B kalau setelah berjalan ternyata staf jarang buka halaman procurement.
3. **Insert draft PR:** ikuti pola `no_pr` generation & struktur di `app/pr_add.php`, set `status_pr='draft'`, `alasan='Auto-generated: stok di bawah minimal'`, per baris `tblpurchase_request_detail` isi `quantity` = `stok_maks - stok_aktual` (isi ke batas maksimal, bukan cuma ke minimal — supaya tidak generate PR lagi besok untuk item sama).
4. **Cegah draft dobel:** cek dulu apakah sudah ada PR `status_pr IN ('draft','submitted','approved')` yang mengandung item sama & cabang sama sebelum generate baru (mirip pola `qty_po` anti-dobel yang sudah ada di detail existing).

### File Terdampak
- Baru: `app/pr_auto_draft.php` (logic generate) dipanggil dari `procurement_dashboard.php` (Opsi A) atau dari scheduled script terpisah (Opsi B).
- Baca saja (referensi pola): `app/pr_add.php`, `tblitem_stok`, `tbstok`.

### Resiko & Rollback
- Resiko rendah: hasil akhir cuma `status_pr='draft'` — staf tetap review manual sebelum submit/approve, tidak ada auto-approve, tidak ada barang benar-benar dipesan tanpa manusia menyetujui.
- Rollback: hapus baris `tblpurchase_request_header/detail` berstatus `draft` yang `alasan` mengandung tag "Auto-generated" — mudah diidentifikasi & dibersihkan kalau logic keliru.

### Yang Masih Perlu Dicek Sebelum Coding
- Struktur kolom `tbstok` (nama kolom tipe transaksi masuk/keluar, apakah ada index yang cukup cepat untuk hitung running total per noitem+cabang di real-time) — **belum diverifikasi di sesi planning ini**, perlu 1 query `DESCRIBE tbstok` sebelum implementasi mulai.

### Estimasi
Sedang — bukan cuma CRUD baru, ada logic agregasi stok dari ledger + keputusan kecil trigger mechanism (Opsi A vs B).

---

## Ringkasan Urutan Kerja Disarankan

1. **#18 duluan** — paling ringan (audit + update dokumen), bisa selesai dalam 1 sesi kerja.
2. **#13 kedua** — bangun skema + CRUD dasar, tidak ada dependency luar.
3. **#24 terakhir** — butuh 1 langkah verifikasi tambahan (struktur `tbstok`) sebelum mulai, dan ada keputusan kecil (Opsi A/B trigger) yang baiknya dikonfirmasi dulu ke Owner/Gudang sebelum coding supaya tidak salah arah.
