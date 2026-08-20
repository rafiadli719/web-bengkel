# Rencana Kerja — Item Non-Blocked (19 Juli 2026)

**Tujuan dokumen:** rencana eksekusi buat semua item ceklis progres yang TIDAK menunggu jawaban Owner. Ini bukan ceklis status (lihat `docs/CEKLIS_PROGRES_WEB_BENGKEL_2026-07-19.md`), ini detail per-item hasil investigasi ulang — bukan tebakan. Tidak ada urutan prioritas diusulkan di sini; urutan pengerjaan diputuskan terpisah.

Tidak ada perubahan kode/DB dilakukan di sesi ini kecuali query read-only (`SHOW TRIGGERS`, `SHOW COLUMNS`, `SELECT COUNT`).

---

## 1. Kendaraan — Pindah Kepemilikan (URGENT)

**Effort: kecil** (migrasi sudah ditulis lengkap, tinggal dijalankan + verifikasi)

**File/tabel kesentuh:**
- `db/migrations/2026-07-13_kendaraan_pindah_kepemilikan.sql` (dijalankan)
- Tabel baru: `kepemilikan_kendaraan`, `permintaan_pindah_kepemilikan_kendaraan`, `kendaraan_plat_history`, `statistik_kendaraan`
- Kolom baru: `tblkendaraan.id_kendaraan`
- View baru: `view_kendaraan_owner_current`, `view_kendaraan_blocker_pindah_kepemilikan`
- Pemakai: `app/kendaraan_pindah_tangan.php`, `app/kendaraan_pindah_tangan_approve.php`

**Hasil verifikasi produksi (2026-07-19, read-only):**
- `tblkendaraan.id_kendaraan`: **BELUM ADA**
- 4 tabel baru: **TIDAK ADA semua** — migrasi memang belum pernah dijalankan, sesuai temuan ceklis.
- `tblkendaraan` total 37.354 baris, **tidak ada** `nopolisi` NULL/kosong, **tidak ada** duplikat `nopolisi` — artinya langkah 1 migrasi (`UPDATE ... SET id_kendaraan = (@rownum:=@rownum+1) ... ORDER BY nopolisi`) aman jalan tanpa konflik penomoran.
- Kolom `tblkendaraan.pemilik` **ADA** — backfill owner (langkah 7 migrasi, fallback ke `pemilik`) bisa jalan.

**Review kesiapan migrasi (baca isi file baris demi baris):**
- Struktur SQL sudah bagus: pakai `INFORMATION_SCHEMA` check sebelum `ALTER TABLE`/`CREATE UNIQUE KEY` → aman di-re-run (idempotent).
- Tidak ada `FOREIGN KEY` eksplisit dari `kendaraan_plat_history.id_kendaraan`, `kepemilikan_kendaraan.id_kendaraan`, `permintaan_pindah_kepemilikan_kendaraan.id_kendaraan`, `statistik_kendaraan.id_kendaraan` balik ke `tblkendaraan.id_kendaraan` — konsisten dengan gaya project ini (tabel lama juga tanpa FK eksplisit), tapi berarti integritas referensial 100% bergantung ke aplikasi, bukan DB. Bukan blocker, tapi perlu disadari sebelum jalan.
- Backfill owner (langkah 7) pakai `GROUP_CONCAT ... ORDER BY tanggal DESC, jam DESC, no_service DESC` buat ambil owner servis terakhir per `no_polisi`. **Catatan silang dengan item #2 di bawah**: `no_service` di sini dipakai cuma untuk tie-break `ORDER BY`, bukan buat JOIN/filter data — jadi TIDAK kena bug `no_service` tidak unik (aman, beda kelas masalah).
- Belum ada `DOWN`/rollback script terpisah — kalau migrasi ternyata perlu dibatalkan, harus manual `DROP TABLE`/`DROP COLUMN`.

**Kesimpulan:** migrasi siap dijalankan langsung ke produksi tanpa perlu revisi struktur. Satu-satunya rekomendasi tambahan (opsional, bukan blocker): tambah dokumentasi rollback singkat sebelum eksekusi, karena ini nambah kolom+tabel baru di tabel besar (37rb baris).

**Langkah eksekusi:**
1. Backup `tblkendaraan` (37rb baris, operasi ALTER+UPDATE massal).
2. Jalankan migrasi lewat browser runner pola project ini (bukan `mysql` CLI langsung — lihat catatan memori "cara benar migrasi DB live").
3. Verifikasi count 4 tabel baru + `id_kendaraan` terisi penuh (tidak ada NULL).
4. Test browser: buka `kendaraan_pindah_tangan.php` dengan user login, pastikan tidak error lagi.
5. (Alternatif kalau mau lebih hati-hati) sembunyikan dulu menu ini di `menu_config.php` sampai migrasi terverifikasi — opsi cepat kalau eksekusi migrasi mau ditunda.

---

## 2. `no_service` Tidak Unik — CRITICAL + High-Risk Group

**Effort: sedang** (CRITICAL sudah 90% selesai tinggal commit; High-Risk masih butuh audit ulang + fix per file)

### 2a. Grup CRITICAL (3 file tanpa session check sama sekali)

**File:** `app/servis-carinopol-batal.php`, `app/servis-carinopol-kosongkan.php`, `app/chartjs/servis-carinopol-kosongkan.php`

**Temuan penting:** ketiga file ini **SUDAH ADA PATCH di working tree, belum di-commit** (`git status` nunjukin `M`). Isi patch (dicek via `git diff`):
- Tambah `session_start()` + guard `empty($_SESSION['_iduser'])` (sebelumnya publik, tanpa login check).
- Tambah `$kd_cabang = $_SESSION['_cabang']`.
- Tambah guard kepemilikan: `SELECT no_service FROM tblservice WHERE no_service='...' AND kd_cabang='...'` sebelum DELETE apa pun — kalau tidak match, `die("Service tidak ditemukan di cabang Anda.")`.
- Query DELETE ke `tblservice`/`tblservis_barang`/`tblservis_jasa` ditambah `AND kd_cabang='$kd_cabang'`.
- `tbservis_keluhan`/`tbservis_pengerjaan` sengaja TIDAK ditambah `kd_cabang` di WHERE-nya (dengan komentar penjelasan di kode) karena tabel itu tidak punya kolom `kd_cabang` — mitigasi lewat guard kepemilikan di awal, bukan filter langsung.
- `mysqli_real_escape_string()` ditambah ke `$_GET['snoserv']` (sebelumnya raw, celah SQL injection tertutup sekalian).
- `php -l` lolos ketiga file.

**Effort sisa:** kecil — tinggal review patch, test browser (klik tombol batal/kosongkan di UI sebagai user cabang lain vs cabang sendiri), lalu commit. Kerja inti sudah selesai, cuma belum diverifikasi & di-commit.

### 2b. Grup High-Risk (22 file, menurut ceklis)

**Status investigasi:** daftar 22 file persis TIDAK ditemukan tersimpan di memory atau dokumen manapun di repo — audit itu dilakukan sesi sebelumnya tapi hasil listing filenya tidak sempat dipersist selain ringkasan angka di ceklis. Perlu direkonstruksi ulang sebelum eksekusi.

**Sudah dikonfirmasi TUNTAS** (jangan dikerjakan ulang) — 17 file pola `JOIN tblservice ... ON no_service` (grep ulang 19 Juli, cocok 100% dengan daftar di memory `project_critical_no_service_not_unique.md`):
`approval-diskon.php`, `retur_servis.php`, `retur_servis_detail.php`, `lap_servis.php`+`_pdf`+`_xls`, `helper-functions.php`, `index.php`, `kelola-antrian.php`, `dashboard-antrian-servis.php`, `laporan-dp.php`, `check_antrian.php`, `_ajax/ajax-refresh-antrian-dashboard.php`, `_ajax/ajax-get-discount-preview.php`, `_include_statistik_pelanggan.php`, `_include_kategori_member.php`, `admin-backfill-tbstok-servis.php` — semua sudah fixed atau sengaja-diskip dengan alasan tercatat.

**Kandidat file yang BELUM diaudit ulang** (grep baru 19 Juli, pola `DELETE`/`UPDATE ... WHERE no_service=...` tanpa `kd_cabang`, di luar 17 file JOIN di atas dan di luar `_archive/`):
`_ajax/ajax-save-mechanic-percentages.php`, `_ajax/ajax-save-service.php`, `_ajax/ajax-toggle-boleh-dp.php`, `_ajax/ajax-update-status-antrian.php`, `_ajax/hapus_spk_keluhan.php`, `_ajax/hapus_spk_workorder.php`, `_ajax/save_mechanic_data.php`, `_handler_status_keluhan_wo.php`, `_handler_temuan_penawaran.php`, `_include_access_sync.php`, `issue_add.php`, `kelola-antrian.php`, `retur_servis_batal.php`, `save_antar_jemput.php`, `save_retur_servis.php`, `service-validation.php`, `servis-delete.php`, `servis-garansi.php`, `servis-input-reguler-jemput-rst.php`, `servis-input-reguler-jemput.php`, `servis-input-reguler-rst.php`, `servis-input-reguler.php`, `servis-reguler.php`, `workorder-hapus.php`, plus **`servis-reguler-byr.php`** (lihat item #3 — overlap eksplisit).

Ini ~25 kandidat, bukan persis 22 — kemungkinan besar overlap dengan daftar asli tapi belum tentu identik (audit lama mungkin exclude beberapa karena analisis risk lebih dalam, atau grup risiko-rendah dari ceklis termasuk sebagian). **Rekomendasi:** treat sebagai starting point, bukan daftar final — audit ulang tiap file (pola sama seperti fix Grup A-D sebelumnya: admin lihat semua cabang, non-admin difilter `kd_cabang` sesi via `EXISTS` guard) sebelum commit.

**Effort per file:** kecil-sedang tergantung kompleksitas query; polanya sudah baku dari fix-fix sebelumnya (Grup A-D), jadi bisa dikerjakan cepat begitu daftar final ditetapkan.

---

## 3. Formula Komisi Mekanik — `servis-reguler-byr.php`

**Effort: sedang**

**File kesentuh:** `app/servis-reguler-byr.php` (baris ~538-597, blok final proses bayar)

**Temuan:**
- Grep `"komisi"` di file ini: **0 hasil**. Tidak ada satu pun referensi ke `servis_komisi` di alur bayar normal — konfirmasi ceklis benar.
- Blok final bayar (baris 538-597): terima input (`txtbayar`, `txtpotfaktur_*`, `txtpajak_persen`), hitung `$tot`/`$net`/`$kembalian`, lalu:
  - `UPDATE tblservice SET status='4', ..., status_servis='bayar' WHERE no_service='$no_service'` (baris 570-581) — **tanpa `kd_cabang` di WHERE**.
  - Loop INSERT ke `tbstok` per item barang.
  - Redirect ke halaman cetak struk.
- **Tidak ada logic komisi mekanik di mana pun di file ini** — data mekanik & persentase cuma tersimpan di `tbservis_pengerjaan.kd_mekanik` (baris ~281-297, saat assign item pengerjaan ke mekanik), tidak pernah di-snapshot ke `servis_komisi` saat pembayaran final.
- `servis_komisi` saat ini **0 baris** di produksi (dicek langsung) — jalur CRM (`issue_add.php`) yang tadinya mengisi tabel ini tampaknya sudah kosong lagi (kemungkinan data test yang sudah dibersihkan sesi sebelumnya, bukan berarti kodenya rusak — `issue_add.php` tidak disentuh sesi ini).

**⚠️ OVERLAP EKSPLISIT DENGAN ITEM #2 — HARUS 1 SESI, JANGAN DIPISAH:**
Fix bug `no_service` (tambah `AND kd_cabang='$kd_cabang'` ke `UPDATE tblservice` baris 570-581) dan wiring komisi mekanik (insert ke `servis_komisi` pakai `$no_service`, `$net`, dll — variabel yang sama persis) **sama-sama menyentuh blok kode yang identik** (baris 538-597, fungsi/handler yang sama, variabel `$no_service`/`$tot`/`$net` yang sama). Mengerjakan salah satu tanpa yang lain di sesi terpisah berisiko:
- Merge conflict kalau dikerjakan paralel oleh sesi/orang berbeda.
- Testing ganda sia-sia (tiap perubahan di blok ini butuh test end-to-end bayar servis lewat browser — kalau dipisah, testing manual harus diulang 2x untuk area yang sama).
- Insert komisi idealnya pakai `$no_service` yang SUDAH divalidasi lewat guard `kd_cabang` dari fix bug #2 — kalau komisi ditulis duluan tanpa guard itu, insert komisi ikut rawan salah-cabang juga.

**Rekomendasi konkret untuk sesi eksekusi nanti:**
1. Tambah `AND kd_cabang='$kd_cabang'` ke query `UPDATE tblservice` baris 570-581 (dan pastikan `$kd_cabang` sudah diambil dari session di awal file, sudah ada di baris 7).
2. Di blok yang sama, sebelum/sesudah UPDATE tblservice, tambah `INSERT INTO servis_komisi (no_service, kd_cabang, kd_mekanik, ..., created_at) SELECT ... FROM tbservis_pengerjaan WHERE no_service='$no_service'` — snapshot komisi per mekanik yang mengerjakan servis ini, dihitung dari persentase & nominal saat itu (perlu cek struktur tabel `servis_komisi` & `tbservis_pengerjaan.kd_mekanik` dulu buat pastikan kolom apa saja yang perlu diisi).
3. Test browser end-to-end: input servis baru → assign mekanik → bayar → cek `servis_komisi` terisi 1 baris per mekanik, DAN cek `tblservice` cuma ke-update di cabang yang benar (test dengan 2 servis beda cabang yang share `no_service` sama kalau ada datanya, atau simulasi).

---

## 4. Alarm Harga Beli — Verifikasi Trigger

**Effort: kecil** (script pembuat trigger sudah ada, tinggal dijalankan)

**Hasil `SHOW TRIGGERS LIKE '%alarm_harga%'` (dijalankan langsung ke DB produksi, read-only, 19 Juli 2026):**

```
total triggers found: 0
```

**Trigger `trg_alarm_harga_beli` TIDAK aktif di database produksi.** Tabel `alarm_harga_beli` juga 0 baris (konsisten — kalau trigger belum ada, memang tidak akan pernah ada baris masuk).

**File terkait:** `db/migrations/2026-07-17_run_alarm_harga_beli.php` — runner PHP siap-pakai yang:
1. Load & jalankan `2026-07-17_f4_alarm_harga_beli.sql` (bikin tabel `tb_master_threshold_harga` & `alarm_harga_beli`, seed data threshold).
2. `DROP TRIGGER IF EXISTS trg_alarm_harga_beli` (aman di-re-run).
3. `CREATE TRIGGER trg_alarm_harga_beli AFTER INSERT ON tblpembelian_detail` — bandingkan `harga_pokok` baris baru vs baris sebelumnya per `no_item`, kalau selisih persen ≥ threshold (dari `tb_master_threshold_harga`), insert ke `alarm_harga_beli`.

Karena ceklis bilang tabel `tb_master_threshold_harga` **sudah ada isi 2 baris** (naik 5%, turun 10%) dan dipakai `setting-threshold-harga.php` yang **live**, kemungkinan besar bagian tabel dari script ini sudah pernah dijalankan sebelumnya — tapi bagian `CREATE TRIGGER` di baris 61-65 script yang sama entah kenapa gagal/belum sempat dijalankan, atau sempat dijalankan lalu ke-drop tanpa dibuat ulang.

**Langkah eksekusi:** jalankan ulang `db/migrations/2026-07-17_run_alarm_harga_beli.php` lewat browser runner (script sudah idempotent, aman di-re-run — drop-if-exists sebelum create). Setelah itu verifikasi: insert 1 baris test ke `tblpembelian_detail` dengan `harga_pokok` beda >5% dari baris sebelumnya untuk `no_item` yang sama, cek `alarm_harga_beli` bertambah 1 baris.

---

## 5. Sisa Teknis Promo Engine

**Effort: kecil** (4 dari 5 sub-item TERNYATA SUDAH SELESAI di sesi hari ini sebelum sesi ini dimulai — cuma 1 sisa)

Investigasi ulang menemukan progress lebih jauh dari yang tertulis di ceklis (ceklis kemungkinan ditulis di tengah sesi, sebelum item-item ini kelar):

| Sub-item | Status aktual (dicek ulang) |
|---|---|
| Mode OR syarat kelayakan | **SUDAH ditest** — divalidasi via PHP CLI test suite sesi sebelumnya (temuan tercatat: "OR mode, jumlah_kunjungan rolling window, dan paket_workorder semua validated correct") |
| `jumlah_kunjungan`/`paket_workorder` | **SUDAH ditest**, sama seperti di atas |
| E2E browser test lewat form kasir | **SUDAH dilakukan** — test lengkap lewat browser (search barang, tambah item promo, retarget produk, cek diskon ke-apply, cleanup data test), terverifikasi lolos |
| Registrasi permission `promo_diskon_read` di RBAC | **SUDAH di-commit** — commit `705385b` (permission didaftarkan resmi ke role ADM + page-level guard `canAccessPage()` di `master-diskon-periode.php`) |
| Kolom `kd_cabang` di `promo_usage_log` | **BELUM ADA** — dicek langsung (`SHOW COLUMNS FROM promo_usage_log LIKE 'kd_cabang'` → TIDAK ADA). Satu-satunya sub-item yang genuinely belum selesai. |

**File/tabel kesentuh (sisa 1 item):** `promo_usage_log` (ALTER TABLE tambah kolom), titik-titik INSERT ke `promo_usage_log` (25 titik transaksi servis, per commit `c4f4fca`) perlu diupdate isi `kd_cabang` dari session saat insert.

**Rekomendasi:** cek dulu urgensinya — ceklis sendiri bilang "belum jadi masalah aktif" (belum ada laporan yang JOIN balik promo_usage_log ke servis per cabang). Bisa ditunda tanpa risiko langsung, beda dengan item #1-#4 di atas.

---

## 6. Housekeeping Dokumentasi FSD

**Effort: kecil**

**Hasil cek header tiap dokumen (19 Juli 2026):**

| Dokumen | Status header saat ini | Perlu update? |
|---|---|---|
| `docs/fsd/FSD_MEMBERSHIP.md` | `Status: Menunggu approval` | **Ya** — kode sudah live (37.110 baris `statistik_pelanggan`, tier floor rule jalan) |
| `docs/fsd/FSD_CUSTOMER.md` | `Status: Menunggu approval` | **Ya** — kode sudah live (deteksi & merge duplikat pelanggan jalan, ada data merge nyata) |
| `docs/fsd/FSD_CRM.md` | `Status: Menunggu approval` | **Ya** — kode sudah live (tbl_issue ada 4 tiket nyata, revisi komisi servis pasca-bayar jalan) |
| `docs/fsd/FSD_PENGADAAN_INVENTORY.md` | `Status: Aktif — keputusan Owner #1, #2 sudah masuk (2026-07-16)` | **Tidak** — sudah update, tidak perlu disentuh |
| `docs/fsd/FSD_PROMO.md` | `Status: Disetujui untuk implementasi` | **Tidak** — sudah update, tidak perlu disentuh |

Cuma **3 dari 5 file** yang perlu diupdate (2 sudah benar duluan — kemungkinan diupdate di sesi sebelumnya, tidak sesuai asumsi awal di instruksi user bahwa kelima-limanya masih "Menunggu approval").

**Rekomendasi isi update untuk 3 file:** ubah baris `**Status:** Menunggu approval` jadi status yang mencerminkan realita per modul, contoh pola yang sudah dipakai `FSD_PENGADAAN_INVENTORY.md`: `**Status:** Aktif — [ringkasan bagian mana yang live] (diverifikasi ke kode & DB [tanggal])`. Untuk bagian yang masih ada sub-fitur belum dibangun (mis. `member_tier_history` di Membership, `pelanggan_kontak_history` di Customer), tetap catat sebagai gap terbuka di badan dokumen, jangan disembunyikan hanya karena status header berubah jadi "Aktif".

---

## Ringkasan Effort Relatif

| # | Item | Effort | Catatan kunci |
|---|---|---|---|
| 1 | Kendaraan — Pindah Kepemilikan | Kecil | Migrasi siap jalan, tidak perlu revisi struktur |
| 2a | no_service CRITICAL (3 file) | Kecil | Patch sudah ada di working tree, tinggal test+commit |
| 2b | no_service High-Risk (~22-25 file) | Sedang | Daftar file perlu direkonstruksi ulang dulu |
| 3 | Komisi Mekanik | Sedang | **1 sesi bareng dengan 2b khusus `servis-reguler-byr.php`** |
| 4 | Alarm Harga Beli | Kecil | Trigger 0/0 — runner script tinggal dijalankan ulang |
| 5 | Promo Engine sisa | Kecil | 4/5 sudah selesai, sisa cuma kolom `kd_cabang` |
| 6 | Housekeeping FSD | Kecil | 3/5 file perlu update (2 sudah benar) |
