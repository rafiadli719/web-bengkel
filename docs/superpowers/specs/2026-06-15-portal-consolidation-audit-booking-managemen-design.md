# Konsolidasi Portal — Audit `_booking` + `_managemen` (Sub-proyek 1)

## Latar Belakang & Konteks Proyek Besar

Tujuan akhir: `_admincab` (di-akses via symlink `panel/`) menjadi satu-satunya portal untuk semua role, diatur penuh oleh sistem RBAC (`menu_config.php` + `_include_menu_rbac.php`, sudah dibangun & divalidasi). Folder portal lain (`_admin`, `_hrd`, `_kasir`, `_pengadaan`, `_managemen`, `_booking`) akan dihapus, lalu `_admincab` di-rename ke nama yang lebih general, dan 58 file `menu_*.php` yang saat ini masih dipakai sebagai sidebar statis di folder-folder tersebut akhirnya dihapus juga.

Temuan eksplorasi:
- `cek_login.php` sudah meredirect SEMUA user ke `panel/` (symlink ke `_admincab`) sejak login.
- Tidak ada file di luar `_admin/_kasir/_pengadaan/_hrd/_managemen/_booking` yang memiliki link/href ke folder-folder tersebut — folder-folder ini kemungkinan besar sudah jadi kode mati dari sisi flow utama.
- Namun untuk memastikan tidak ada fitur unik yang hilang, setiap folder diaudit dulu sebelum dihapus.

Karena skala total ~1.300 file di 6 folder, pekerjaan dipecah jadi sub-proyek berurutan, dimulai dari folder terkecil:

| # | Sub-proyek | Folder | Jumlah file |
|---|-----------|--------|-------------|
| 1 | Audit (spec ini) | `_booking` + `_managemen` | 1 + 28 |
| 2 | Audit | `_hrd` | 82 |
| 3 | Audit | `_admin` | 273 |
| 4 | Audit | `_kasir` | 367 |
| 5 | Audit | `_pengadaan` | 549 |
| 6+ | Migrasi fitur unik (jika ada) ke `_admincab`, hapus folder lama, rename `_admincab`, hapus 58 file `menu_*.php` sisa | — | — |

Setiap sub-proyek audit menghasilkan laporan yang menjadi dasar keputusan: folder aman dihapus langsung, atau perlu migrasi fitur tertentu dulu.

## Scope Sub-proyek 1

Audit seluruh file di `_booking/` (1 file PHP) dan `_managemen/` (28 file PHP, di luar folder vendor `chartjs/` dan `dompdf/`), untuk menentukan kategori masing-masing file dan rekomendasi akhir.

## Metodologi

Untuk setiap file PHP (skip `error_log`, dan skip isi folder vendor `chartjs/`, `dompdf/` — folder ini diberi 1 baris kategori "Vendor/Library" saja tanpa audit per file):

1. Baca isi file: identifikasi fungsi utama (CRUD/laporan/halaman statis), tabel DB yang di-SELECT/INSERT/UPDATE, dan file lain yang di-include.
2. Cross-check ke `_admincab`:
   - Cari file dengan nama sama/serupa di `_admincab`.
   - Grep nama tabel DB utama di `_admincab` untuk menemukan halaman yang menangani data yang sama.
3. Tetapkan kategori:
   - **Duplikat/Tergantikan** — equivalent sudah ada & berfungsi di `_admincab`.
   - **Vendor/Library** — kode pihak ketiga (chartjs, dompdf), aman diabaikan.
   - **Unik — perlu migrasi** — tidak ditemukan equivalent, fitur perlu dipindah ke `_admincab` (ditambah entry `menu_config.php` + permission RBAC) sebelum folder dihapus.

## Output

File `docs/audit/audit-booking-managemen.md` berisi tabel:

| File | Kategori | Equivalent di `_admincab` (jika ada) | Catatan/Rekomendasi |
|------|----------|---------------------------------------|---------------------|

Diakhiri dengan ringkasan keputusan: apakah `_booking` dan `_managemen` aman dihapus langsung, atau daftar fitur yang perlu dimigrasi dulu (dengan estimasi lingkup migrasinya).

## Keputusan Migrasi vs Tunda (arahan user)

Jika ditemukan fitur unik di `_booking`/`_managemen`, migrasi BOLEH dilakukan langsung sebagai bagian sub-proyek ini, ATAU ditunda (dicatat di laporan untuk dikerjakan nanti) — pilih yang paling efisien berdasarkan ukuran fitur tersebut. Yang penting laporan akhir mencatat status setiap fitur unik dengan jelas, agar sub-proyek selanjutnya (penghapusan folder) tidak kehilangan informasi ini.

## Definition of Done

- Semua file di `_booking` dan `_managemen` (selain vendor lib) sudah dikategorikan.
- Laporan `docs/audit/audit-booking-managemen.md` tersedia dan lengkap.
- Untuk setiap item "Unik — perlu migrasi": sudah dimigrasi (dengan entry menu_config.php + permission), atau dicatat eksplisit sebagai "ditunda" dengan alasan.
- Rekomendasi akhir jelas: `_booking` dan `_managemen` siap dihapus pada sub-proyek penghapusan folder, ATAU ada blocker yang harus diselesaikan dulu.
