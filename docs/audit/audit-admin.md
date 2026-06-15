# Audit Konsolidasi Portal — `_admin`

Sub-proyek 3 dari inisiatif konsolidasi portal ke `_admincab`. Lihat spec: `docs/superpowers/specs/2026-06-15-portal-consolidation-audit-booking-managemen-design.md`.

## Ringkasan Temuan

`_admin` (274 file PHP top-level + 7 folder vendor lokal: `assets`, `chartjs`, `dompdf`, `jquery-ui-1.11.4`, `PHPExcel`, `tmp_excel`, `_template`).

**264 dari 274 file (96%) punya equivalent dengan nama sama persis di `_admincab`.** Ini menunjukkan `_admin` adalah **versi pendahulu (predecessor) dari `_admincab`** — `_admincab` adalah hasil pengembangan lanjutan dari `_admin` dengan fitur lebih lengkap (905 file vs 274).

## 10 File Tanpa Nama Sama di `_admincab`

| File | Kategori | Analisis |
|------|----------|----------|
| `barang_add_asli.php` (1288 baris) | Duplikat/Tergantikan | Variant lama "asli" dari `barang_add.php`. `_admincab` sudah punya pola variant serupa sendiri (`barang_asli.php`, `barang_add_improved.php`) — superseded. |
| `booking_minggu_1.php` ... `booking_minggu_5b.php` (6 file) | Duplikat/Tergantikan (implementasi beda) | Widget kalender dashboard versi lama — tabel HTML manual per hari, hanya di-include oleh `_admin/index.php`. `_admincab/index.php` memuat library **FullCalendar** (modern) sebagai penggantinya. Tidak direferensikan di luar `_admin/index.php`. |
| `buat pembelian.php` (941 baris) | Duplikat/Tergantikan | Equivalent persis sama (941 baris) ada di `_admincab/"buat pembelian.php"`. |
| `function_pelanggan_kat.php` | Duplikat/Tergantikan (refactor) | Berisi 2 helper kecil (`OtomatisID()`, `FormatNoTrans()`) untuk `tblpelanggangrup`, hanya dipakai `_admin/pelanggan_kategori_add.php`. Versi `_admincab/pelanggan_kategori_add.php` sudah direstruktur — pakai flow `save_pelanggan_kategori.php` terpisah, logika ID generation sudah direfactor ke tempat lain. |
| `validasi.php` | Orphan — tidak terpakai | File demo/tutorial "Belajar Form Validation" (contoh form HTML statis), **tidak direferensikan dari mana pun**, termasuk tidak dari `pelanggan_kategori_add.php` (sempat dikira terkait tapi ternyata tidak). |

## Folder Vendor/Library Lokal (7 folder)

`assets`, `chartjs`, `dompdf`, `jquery-ui-1.11.4`, `PHPExcel`, `tmp_excel`, `_template` — copy lokal milik `_admin` sendiri, pola sama dengan portal lain (masing-masing portal punya copy independen, tidak ada referensi cross-folder).

## Ringkasan & Rekomendasi Akhir

- **Tidak ditemukan fitur unik aktif** di `_admin`. Seluruh 274 file top-level adalah duplikat langsung (264) atau duplikat dengan implementasi yang sudah di-refactor/diganti modern di `_admincab` (10 file sisanya).
- Tidak ada data dormant unik seperti `_hrd` — `_admin` murni kode versi lama dari sistem yang sama (operasional bengkel), sepenuhnya tergantikan `_admincab`.

**Status: AMAN DIHAPUS LANGSUNG, tanpa migrasi apapun.** Tidak ada blocker untuk lanjut ke sub-proyek 4 (`_kasir`).
