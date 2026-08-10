# Arsip File Tidak Terpakai — 2026-08-09

Review dead-file di seluruh repo `aplikasi/aplikasi`. Metodologi: grep referensi
lintas repo (`ripgrep`) untuk tiap kandidat, cross-check dengan audit lama
(`docs/audit/audit-*.md`, `docs/audit/LEGACY_ROLE_FOLDER_AUDIT.md`) dan
dokumen perencanaan aktif (`docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md`
— tanggal SAMA dengan sesi ini, jadi rujukan wajib untuk cek "apakah masih
dipertimbangkan aktif").

**Catatan lingkungan penting**: sesi ini berjalan di worktree terisolasi
(`.claude/worktrees/agent-afd64ef5169ece210`) yang tidak mengizinkan perintah
`git` menyasar checkout utama (`/mnt/c/laragon/www/web-bengkel/aplikasi/aplikasi`).
Karena itu semua pemindahan file di bawah dieksekusi dengan `mv` biasa (bukan
`git mv`), langsung di checkout utama (baca/tulis file non-git tetap bisa).
Konsekuensinya: `git status` di checkout utama akan menunjukkan file-file ini
sebagai **delete + untracked baru** (bukan otomatis staged rename seperti hasil
`git mv`). Sebagian besar file yang dipindah memang sudah berstatus
**untracked** sebelum sesi ini (cek `git status` di awal sesi), jadi tidak ada
history git yang hilang untuk file-file itu. Untuk folder/file yang sudah
**tracked** (terutama `_pengadaan/`, `_hrd/`, `_tools/`, dan file-file
`app/*.md` `app/*.sql` `app/*.txt`), jalankan `git add -A` lalu `git status`
untuk verifikasi — git modern biasanya tetap mendeteksi ini sebagai rename
(similarity index) walau tidak dipindah lewat `git mv`. Kalau user ingin
history rename yang eksplisit, langkah ini perlu diulang dengan `git mv` dari
sesi yang tidak ter-isolasi worktree.

---

## 1. Sudah dipindahkan (keyakinan TINGGI)

### 1.1 Folder portal legacy (leverage audit lama `docs/audit/audit-*.md`, 2026-06-15)

| Folder asal | Tujuan | Bukti |
|---|---|---|
| `_pengadaan/` (549 file + vendor copy) | `archive/legacy-portals/_pengadaan/` | `docs/audit/audit-pengadaan.md`: 548/549 file adalah duplikat identik dari `app/` (dulu `_admincab`), sisanya (`servis-input-reg.php`) superseded, zero-reference. Verifikasi ulang hari ini: `rg "_pengadaan/"` di luar folder itu sendiri = 0 hit. Status audit: **AMAN DIHAPUS LANGSUNG**. |
| `_hrd/` (82 file top-level + ~882 file vendor) | `archive/legacy-portals/_hrd/` | `docs/audit/audit-hrd.md`: modul HR/payroll mandiri, dormant ~2,5 tahun (data terakhir 2023-10-19), tidak ada equivalent di `app/`. Rekomendasi eksplisit audit: **ARSIPKAN** (bukan hapus). Verifikasi ulang: zero reference dari luar folder. |
| `_tools/` (1 file: `run_migrations_motor_jenis.php`) | `archive/legacy-tools/_tools/` | One-off migration runner (pola sama dengan SQL migration report lain), zero reference di seluruh repo (`rg -F "run_migrations_motor_jenis"` = 0 hit di luar dirinya). |

**TIDAK dipindahkan meski direkomendasikan audit lama**: `_managemen/` — lihat
bagian 3 (ambigu, ada pertimbangan aktif hari ini).

### 1.2 Bundle prototipe OAuth Accurate (root, keyakinan TINGGI — bukti "broken code")

| File | Tujuan | Bukti |
|---|---|---|
| `login_accurate.php` | `archive/php/` | Bagian dari alur OAuth code-flow lama. |
| `oauth-callback.php` | `archive/php/` | Memakai konstanta `CLIENT_ID`/`CLIENT_SECRET`/`REDIRECT_URI` yang **tidak pernah didefinisikan** di `config/accurate_config.php` (grep konfirmasi 0 hit) → kode ini sudah fatal-error kalau dijalankan, terbukti mati. Redirect ke URL lama yang sudah tidak dipakai. |
| `get_db_list.php` | `archive/php/` | Downstream dari sesi OAuth di atas (`$_SESSION['access_token']`), redirect ke `open_db.php`. Tidak ada entry menu/link dari file aktif manapun. |
| `open_db.php` | `archive/php/` | Redirect ke `master_barang.php` di root — file itu **tidak ada** di struktur app saat ini (bukti tambahan sudah usang). |
| `get_items.php` | `archive/php/` | Downstream sesi OAuth yang sama, tidak direferensikan. |
| `refresh_token.php` | `archive/php/` | Redirect ke `dashboard.php` di root — file itu juga **tidak ada** (dashboard aktif ada di `dashboard/index.php`). |

Integrasi Accurate yang AKTIF sekarang memakai token statis di
`config/accurate_config.php` (`ACCURATE_API_TOKEN`) yang dipakai langsung oleh
`app/save_barang.php`, `app/database_updater.php`, dll — bundle OAuth di atas
adalah prototipe generasi sebelumnya yang sudah ditinggalkan.

### 1.3 File `_asli` / backup di `app/` (keyakinan TINGGI, zero-reference)

Dikonfirmasi lewat `rg` gabungan (pattern semua nama file sekaligus) di luar
`docs/audit/*` (yang hanya *membahas* file ini sebagai temuan audit, bukan
referensi kode aktif):

| File | Tujuan |
|---|---|
| `app/barang_asli.php` | `archive/php/` |
| `app/cari_item_pembelian_asli.php` | `archive/php/` |
| `app/cari_item_pembelian_rst_asli.php` | `archive/php/` |
| `app/kendaraan_asli.php` | `archive/php/` |
| `app/pelanggan_asli.php` | `archive/php/` |
| `app/pesanan_penjualan_add_asli.php` | `archive/php/` |
| `app/pmby_piutang_add asli.php` | `archive/php/` |
| `app/pmby_piutang_add_next asli.php` | `archive/php/` |

`docs/audit/audit-admin.md` sudah menyebut `barang_asli.php` eksplisit sebagai
"Backup file" / superseded oleh `barang.php` + `barang_add_improved.php`.

**PENTING — dikecualikan dari grup ini**: `app/kas_akhir_asli.php`,
`app/kas_awal_asli.php`, `app/"kas_awal_yg dulu.php"`, dan folder
`app/"kas kasir"/` (7 file) **TIDAK dipindahkan** — lihat bagian 3, ada
temuan aktif hari ini yang menyangkut cluster kas ini.

### 1.4 Dokumen non-kode di root (keyakinan TINGGI, tidak dirujuk apapun)

| File | Tujuan |
|---|---|
| `HASIL DISKUSI AHAD, 28 JUNI 2026 (WEB FIT MOTOR).pdf` | `archive/pdf/` |
| `Transformasi_Profit_FIT_POIN.pdf` | `archive/pdf/` |
| `Hasil audit scm (Mba Indry).docx` | `archive/docx/` |
| `PANDUAN MEETING WEB BENGKEL v2.docx` | `archive/docx/` |
| `PANDUAN MEETING WEB BENGKEL v3.docx` | `archive/docx/` |
| `Panduan_Meeting_Web_Bengkel_28Juni2026.docx` | `archive/docx/` |
| `Panduan_Modul_Pengadaan_FitMotor.docx` | `archive/docx/` |
| `Panduan_Modul_Pengadaan_FitMotor_v2.docx` | `archive/docx/` |
| `SCRIPT MEETING WEB BENGKEL.txt` | `archive/txt/` |

Semua di atas: 0 hit referensi lintas repo (di luar dirinya sendiri), bukan
kode, tidak berisiko fungsional dipindah.

**Dikecualikan**: `DAFTAR_KEPUTUSAN_YANG_DIBUTUHKAN (4) (1).docx` — masih aktif
dirujuk sebagai "Sumber" di 3 dokumen planning aktif (`docs/planning/*.md`).
`accurate_debug.log` (root) — log aktif, statusnya `M` (modified) di git
status awal sesi, berarti masih ditulis oleh aplikasi berjalan.

### 1.5 Bundle laporan/dokumentasi one-off di `app/` root (keyakinan TINGGI)

~49 file `.md`/`.txt`/`.sql`/`.bat` yang hanya saling merujuk satu sama lain
(bundle laporan implementasi/patch yang sudah selesai dieksekusi), zero
referensi dari `docs/` aktif atau kode PHP manapun:

`AUTO_PATCH.bat`, `BACKUP_LOG_20251227.txt`, `BACKUP_MANIFEST_20251227.md`,
`CHANGELOG_PELANGGAN_ADD_SERVIS.md`, `CHANGELOG_RUN_DATABASE_OPTIMIZATION.md`,
`DATABASE_UPDATE_COMPLETE.md`, `DATABASE_UPDATE_REQUIREMENTS.md`,
`DOKUMENTASI_EXPORT_TEMUAN_PENAWARAN.md`, `DOKUMENTASI_HISTORY_SERVICE_PELANGGAN.md`,
`DOKUMENTASI_INTEGRASI_BARANG_CUSTOM.md`, `DOKUMENTASI_PERBAIKAN_APPROVE_REJECT_PENAWARAN.md`,
`DOKUMENTASI_PERBAIKAN_TAB_TEMUAN_PENAWARAN.md`, `DOKUMENTASI_WORKORDER_APPROVAL_SYSTEM.md`,
`IMPLEMENTASI_LENGKAP_SUMMARY.md`, `IMPLEMENTASI_PERBAIKAN_TAB_JEMPUT.md`,
`IMPLEMENTASI_SELESAI.md`, `INSTRUKSI_IMPLEMENTASI_USER_MANAGEMENT.md`,
`PANDUAN_AUTO_KALKULASI_JARAK.md`, `PANDUAN_IMPLEMENTASI_MINMAX.md`,
`PATCH_cabang.php.txt`, `PATCH_cabang_edit.txt`, `PATCH_servis-reguler-jemput.txt`,
`PERBAIKAN_DATABASE.md`, `PERBAIKAN_FINAL_SERVIS_INPUT_REGULER.md`,
`PERBAIKAN_KEPALA_MEKANIK.txt`, `PERBAIKAN_SERVIS_INPUT_REGULER.md`,
`PERBAIKAN_SISTEM_FINAL.md`, `PERBAIKAN_STRUKTUR_TABEL_SERVIS_BARANG.md`,
`Panduan_Alur_Input_Pengadaan.txt`, `README_IMPLEMENTASI.md`,
`README_ORI_NONORI.md`, `README_PERBAIKAN_TAB_JEMPUT.txt`,
`READY_TO_IMPLEMENT.txt`, `READY_TO_USE_INSTRUCTIONS.md`,
`RUN_DATABASE_MIGRATION_PICKUP.sql`, `STATUS_FINAL.md`, `SUMMARY_IMPLEMENTASI.txt`,
`SYSTEM_STATUS.md`, `add_tahun_column.sql`, `audit_supply_chain_jawaban.txt`,
`audit_supply_chain_questions.txt`, `backup_checksums.txt`,
`create_missing_tables.sql`, `create_views_if_not_exists.sql`,
`migration_add_google_maps_foto_rumah.sql`, `planning_pengadaan_antarcabang.txt`,
`planning_ux_improvement.md`, `sql_kepala_mekanik.sql`,
`update_cancel_servis_simplified.sql`

→ dipindah ke `archive/md/`, `archive/txt/`, `archive/sql/`, `archive/bat/`
sesuai ekstensi.

**Dikecualikan dari bundle ini** (masih dirujuk aktif / masih dipakai kode):
- `app/BUGFIX_AJAX_ENDPOINTS.md`, `app/DOKUMENTASI_AJAX_ENDPOINTS_TEMUAN.md`,
  `app/DOKUMENTASI_ALUR_PROCUREMENT.md` — dirujuk oleh `docs/summary/*.md` aktif.
- `app/database_update_user_management.sql` — dibaca langsung via
  `file_exists()` oleh `app/database_updater.php` (tool admin live).
- Semua `app/*_log.txt` (`accurate_brand_save_log.txt`, `accurate_category_*`,
  `accurate_item_*`, `accurate_stock_update_log.txt`, `accurate_supplier_add_log.txt`,
  `accurate_unit_*`, `config_load_log.txt`, `database_optimization_log.txt`,
  `pabrik_save_log.txt`) — ditulis langsung oleh kode live (`save_barang.php`,
  `run_database_optimization.php`, dll via `$log_file = '...'`). Log aktif,
  bukan dead file.

---

## 2. Kandidat ragu-ragu — TIDAK dipindahkan (perlu keputusan manual)

| Item | Kenapa ragu-ragu |
|---|---|
| `index_local.php` (root) | Duplikat hampir identik dari `index.php` (593 vs 585 baris), tidak direferensikan file manapun. Tapi entrypoint yang diakses langsung via URL secara wajar tidak akan pernah "direferensikan" oleh grep — bisa jadi sengaja disimpan untuk testing lokal. **Rekomendasi: tanya Rafi apakah `index_local.php` masih dipakai untuk dev lokal sebelum dihapus/diarsip.** |
| `_sync_gabung_direct.vbs` (root) | Awalnya terlihat tanpa referensi, tapi ternyata **aktif didokumentasikan** sebagai command di `app/access-sync.php` (contoh `cscript //NoLogo _sync_gabung_direct.vbs ...`). **Tidak diarsipkan** — bagian dari arsitektur sync helper (sesuai catatan memory sesi lalu). |
| Sisa ~900 file PHP di `app/` (di luar yang sudah diverifikasi di atas) | **Audit dead-code PHP di `app/` TIDAK exhaustive** pada sesi ini — dengan effort/waktu yang tersedia, hanya kandidat dengan sinyal kuat (`_asli`, prototipe OAuth, dan folder legacy yang sudah ada audit sebelumnya) yang diverifikasi penuh. Ratusan file `_rst.php` awalnya dicurigai sebagai backup, tapi setelah dicek pola penamaan (`pembelian_rst.php`, `penjualan_rst.php`, `retur_pembelian_rst.php`, dst — konsisten di banyak modul transaksi inti) ini kemungkinan besar adalah **handler "reset" aktif** (bukan file backup), bukan sinyal dead-file yang valid. **Rekomendasi: audit lanjutan khusus untuk pola `_rst.php` per modul, dengan trace ke form yang memanggilnya, sebelum diputuskan apapun.** |

---

## 3. TIDAK dipindahkan — bertabrakan dengan temuan aktif hari ini (2026-08-09)

Ditemukan `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md`
(**tanggal sama dengan sesi arsip ini**) yang secara eksplisit membahas dua
area yang tadinya masuk kandidat kuat untuk diarsipkan. Kedua area ini
**DIBATALKAN dari pemindahan** karena statusnya masih jadi bahan keputusan
aktif, bukan dead file yang final:

1. **Cluster Kas Awal/Kas Akhir** — `app/kas_akhir_asli.php`,
   `app/kas_awal_asli.php`, `app/"kas_awal_yg dulu.php"`, folder
   `app/"kas kasir"/` (7 file: `kas_akhir.php`, `kas_akhir_asli.php`,
   `kas_akhir_proses.php`, `kas_awal.php`, `kas_awal_asli.php`,
   `kas_awal_proses.php`, `kas_awal_yg dulu.php`).
   Gap-analysis baris 142 menyebut modul Kas Awal/Kas Akhir sebagai
   **"Temuan paling mendesak"**: kodenya sudah ada (`app/kas_awal.php`,
   `app/kas_akhir.php`, + `_proses.php`, + "duplikat di `app/kas kasir/`")
   tapi **belum ada satupun entry di `menu_config.php`** — user belum bisa
   akses. Masih perlu dipastikan "apakah sengaja disembunyikan (belum siap)
   atau kelalaian wiring". Karena file utama (`kas_awal.php`, `kas_akhir.php`)
   dan variannya (`_asli`, folder `"kas kasir"`) berada dalam satu cluster
   investigasi yang sama, semuanya **ditahan dulu**, tidak diarsipkan, sampai
   keputusan wiring diambil.

2. **Folder `_managemen/`** — audit lama (`docs/audit/audit-booking-managemen.md`,
   2026-06-15) menyimpulkan "AMAN DIHAPUS LANGSUNG" karena 100% duplikat dari
   `app/`. **Tapi** gap-analysis hari ini (baris 194, 252) menyebut
   `_managemen/` sebagai portal legacy yang berisi *9 laporan Dashboard
   Manajemen multi-cabang* yang **berpotensi dipakai/dimodernisasi** untuk
   memenuhi requirement "Dashboard Manajemen (gabungan semua cabang)" — masih
   berstatus "Perlu keputusan: modernisasi portal `_managemen` atau bangun
   dashboard baru di menu utama". Karena keputusan ini eksplisit belum
   diambil per hari ini, `_managemen/` **TIDAK diarsipkan** meski audit lama
   sudah merekomendasikan hapus — potensi konflik keputusan bisnis yang masih
   berjalan lebih penting daripada percepatan beres-beres file.

**Rekomendasi**: setelah Rafi memutuskan (a) status modul Kas Awal/Kas Akhir
(wiring ke menu vs dibiarkan tersembunyi) dan (b) nasib `_managemen/`
(modernisasi vs dibuang), baru area ini bisa direview ulang untuk kandidat
arsip.

---

## 4. Ringkasan jumlah

| Kategori | Jumlah dipindah |
|---|---|
| Folder legacy portal (`_pengadaan`, `_hrd`, `_tools`) | 3 folder (~630 file top-level + vendor copy) |
| Bundle prototipe OAuth root | 6 file |
| File `_asli` di `app/` | 8 file |
| Dokumen non-kode root (docx/pdf/txt) | 9 file |
| Bundle laporan/dokumentasi `app/` root | 49 file |
| **Total item top-level dipindah** | **75 file/folder** |
| Kandidat ragu-ragu, TIDAK dipindah | 3 kelompok (`index_local.php`, `_sync_gabung_direct.vbs` — sudah dikonfirmasi aktif, sisa ~900 file PHP `app/` belum diaudit) |
| Ditahan karena keputusan aktif hari ini | 2 kelompok (cluster Kas Awal/Kas Akhir, folder `_managemen/`) |

## 5. Verifikasi pasca-pindah

`php -l` dijalankan spot-check pada file yang paling mungkin terdampak
(`login.php`, `index.php`, `cek_login.php`, `app/database_updater.php`,
`app/save_barang.php`, `app/menu_config.php`) — semua **No syntax errors
detected**. Grep ulang `app/menu_config.php` untuk nama-nama file yang
dipindah (folder `kas kasir`, `*_asli.php`, bundle OAuth) — 0 hit, konsisten
dengan hasil investigasi awal.

## 6. Tindak lanjut yang disarankan

1. Jalankan `git add -A` (atau `git status`) di checkout utama untuk melihat
   hasil pemindahan sebagai staged changes — verifikasi manual sebelum commit.
2. Kalau butuh history rename eksplisit (bukan delete+add), ulangi
   pemindahan file yang sebelumnya **tracked** (`_pengadaan/`, `_hrd/`,
   `_tools/`, bundle `app/*.md`/`*.sql`/`*.txt`) memakai `git mv` dari sesi
   yang tidak ter-isolasi worktree.
3. Audit lanjutan untuk pola `_rst.php` di seluruh `app/` (ratusan file,
   di luar scope sesi ini) — pastikan mana yang benar-benar reset-handler
   aktif vs yang backup mati.
4. Setelah keputusan Kas Awal/Kas Akhir dan `_managemen/` diambil, jalankan
   ulang review arsip untuk dua area itu.
