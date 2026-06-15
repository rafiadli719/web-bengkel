# Audit Konsolidasi Portal — `_kasir`

Sub-proyek 4 dari inisiatif konsolidasi portal ke `_admincab`. Lihat spec: `docs/superpowers/specs/2026-06-15-portal-consolidation-audit-booking-managemen-design.md`.

## Ringkasan Temuan

`_kasir` (367 file PHP top-level + 7 folder vendor/subfolder: `assets`, `chartjs`, `dompdf`, `jquery-ui-1.11.4`, `kas kasir`, `PHPExcel`, `proses-add-detail`, `tmp_excel`, `_template`).

**365 dari 367 file top-level (99,5%) punya equivalent dengan nama sama persis di `_admincab`.** Pola sama seperti `_admin` — `_kasir` adalah portal role "Kasir" yang sepenuhnya tergantikan oleh `_admincab` (RBAC role KSR sudah divalidasi di sub-proyek sebelumnya / Task #5).

Termasuk 2 file dengan spasi di nama (`"pmby_piutang_add asli.php"`, `"pmby_piutang_add_next asli.php"`) — keduanya juga punya equivalent persis di `_admincab` (terlihat sebagai *Modified* di git status, bukan file baru).

## 2 File Top-Level Tanpa Nama Sama di `_admincab`

| File | Kategori | Analisis |
|------|----------|----------|
| `menu_penjualan04.php` | Orphan — tidak terpakai | Tidak direferensikan dari mana pun (dalam `_kasir` maupun seluruh repo — hasil `grep -rl` kosong total). `_admincab/menu_penjualan04.php` versi serupa sudah dihapus di sesi sebelumnya (Task #4) karena alasan yang sama: zero reference. Aman dihapus. |
| `servis-input-reg.php` (982 baris) | Duplikat/Tergantikan (superseded) | Tidak direferensikan dari mana pun di seluruh repo (`grep -rl` kosong). Variant lama/lebih kecil dari `servis-input-reguler.php` (`_kasir`=1132 baris, `_admincab`=3077 baris) yang aktif dipakai. Sudah digantikan penuh — aman dihapus. |

## Subfolder Khusus (2 folder)

### `"kas kasir"/` (7 file: `kas_akhir.php`, `kas_akhir_asli.php`, `kas_akhir_proses.php`, `kas_awal.php`, `kas_awal_asli.php`, `kas_awal_proses.php`, `"kas_awal_yg dulu.php"`)

- **Kategori: Duplikat/Tergantikan (folder backup orphan).**
- Folder ini **tidak direferensikan dari mana pun** (`grep -rl "kas kasir"` di seluruh repo kosong) — tidak ada `include`/`require` yang menunjuk ke path ini.
- `_kasir` top-level **sudah punya** versi aktifnya sendiri: `kas_akhir.php` dan `kas_awal.php` (1176 baris, identik ukuran dengan versi di dalam folder `"kas kasir"`, tapi isi berbeda — `diff` menunjukkan DIFFERENT, kemungkinan beda kecil/whitespace dari proses backup).
- `_admincab` juga sudah punya **seluruh 6 nama file** ini (`kas_akhir.php`, `kas_akhir_asli.php`, `kas_akhir_proses.php`, `kas_awal.php`, `kas_awal_asli.php`, `kas_awal_proses.php`) — semua ADA.
- File ke-7, `"kas_awal_yg dulu.php"` ("yang dulu" = versi lama), adalah backup historis tambahan tanpa referensi.
- Kesimpulan: folder `"kas kasir"` adalah **folder backup/snapshot lama** dari file-file `kas_akhir*`/`kas_awal*` yang sudah punya versi aktif baik di `_kasir` top-level maupun `_admincab`. Aman dihapus bersama folder `_kasir`.

### `proses-add-detail/` (3 file: `penjualan-brg-servis.php`, `penjualan.php`, `pesanan-penjualan.php`)

- **Kategori: Duplikat/Tergantikan.**
- Folder ini **aktif dipakai** — direferensikan oleh 8 file di dalam `_kasir` (`penjualan_add.php`, `penjualan_add_rst.php`, `pesanan_penjualan_add.php`, `pesanan_penjualan_add_rst.php`, `servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-input-reguler-jemput-rst.php`, `servis-input-reguler-rst.php`), via pola `include "proses-add-detail/....php"`.
- **`_admincab` punya folder `proses-add-detail/` yang sama**, dengan pola include identik (dikonfirmasi: `_admincab/penjualan_add.php` baris 237 — `include "proses-add-detail/penjualan.php";`).
- Karena setiap portal punya copy lokal sendiri (pola yang sama berlaku di seluruh portal), `_admincab/proses-add-detail/` sudah lengkap dan independen dari `_kasir/proses-add-detail/`. Aman dihapus bersama folder `_kasir`, tidak berdampak ke `_admincab`.

## Folder Vendor/Library Lokal (7 folder)

| Folder | `_kasir` | `_admincab` | Catatan |
|--------|---------|-------------|---------|
| `assets` | 140 | 144 | Copy lokal, selisih wajar (Ace template assets) |
| `chartjs` | 66 | 66 | Identik jumlah file |
| `dompdf` | 316 | 324 | Copy lokal |
| `jquery-ui-1.11.4` | 39 | 39 | Identik jumlah file |
| `PHPExcel` | 254 | 254 | Identik jumlah file |
| `tmp_excel` | 6 | 6 | Identik jumlah file |
| `_template` | 129 | 209 | Copy lokal, `_admincab` punya lebih banyak template (wajar — portal lebih lengkap) |

Semua folder vendor adalah copy lokal milik `_kasir` sendiri, konsisten dengan pola di `_booking`, `_managemen`, `_hrd`, `_admin` — tidak ada referensi cross-folder.

## Ringkasan & Rekomendasi Akhir

- **Tidak ditemukan fitur unik aktif** di `_kasir`. 365/367 file top-level adalah duplikat langsung, 2 sisanya (`menu_penjualan04.php`, `servis-input-reg.php`) adalah orphan/superseded tanpa referensi sama sekali di seluruh repo.
- Folder `"kas kasir"` adalah backup orphan lama, folder `proses-add-detail` punya equivalent identik di `_admincab`.
- Semua folder vendor adalah copy lokal portal, aman dihapus bersama folder.

**Status: AMAN DIHAPUS LANGSUNG, tanpa migrasi apapun.** Tidak ada blocker untuk lanjut ke sub-proyek 5 (`_pengadaan`, 549 file).
