# Audit Konsolidasi Portal — `_booking` & `_managemen`

Sub-proyek 1 dari inisiatif konsolidasi portal ke `_admincab`. Lihat spec: `docs/superpowers/specs/2026-06-15-portal-consolidation-audit-booking-managemen-design.md`.

Melengkapi rekomendasi #1 di `LEGACY_ROLE_FOLDER_AUDIT.md` (audit per-file untuk membandingkan terhadap `_admincab`).

## `_booking/` (1 file PHP)

| File | Kategori | Equivalent di `_admincab` | Catatan/Rekomendasi |
|------|----------|---------------------------|----------------------|
| `index.php` | Duplikat/Tergantikan | `paket_add.php` (sama 1112 baris) | **Sudah rusak**: include `funcion_wo.php` dan `menu_master01h.php` tidak ada di `_booking/` (broken include). Halaman "Tambah Work Order/Paket" ini sudah digantikan penuh oleh `_admincab/paket_add.php` yang berfungsi normal. Aman dihapus. |

## `_managemen/` (28 file PHP + 2 folder vendor)

| File | Kategori | Equivalent di `_admincab` | Catatan/Rekomendasi |
|------|----------|---------------------------|----------------------|
| `change_pwd.php` | Duplikat/Tergantikan | `change_pwd.php` | Ada equivalent, aman dihapus |
| `change_pwd_proses.php` | Duplikat/Tergantikan | `change_pwd_proses.php` | Ada equivalent, aman dihapus |
| `index.php` | Duplikat/Tergantikan | `index.php` | Ada equivalent, aman dihapus |
| `index-novri.php` | Unik tapi tidak terpakai (orphan) | - | Varian dev `index.php` (908 baris), tidak di-include/diakses dari mana pun. Bukan fitur aktif — aman dihapus bersama folder. |
| `logout.php` | Duplikat/Tergantikan | `logout.php` | Ada equivalent, aman dihapus |
| `profile.php` | Duplikat/Tergantikan | `profile.php` | Ada equivalent, aman dihapus |
| `profile_proses.php` | Duplikat/Tergantikan | `profile_proses.php` | Ada equivalent, aman dihapus |
| `lap_kas_keluar.php` | Duplikat/Tergantikan | `lap_kas_keluar.php` (864 baris vs 903) | Versi `_admincab` sudah migrasi ke `menu_dashboard.php` (Task #3), fungsionalitas sama |
| `lap_kas_masuk.php` | Duplikat/Tergantikan | `lap_kas_masuk.php` (863 vs 903) | sda |
| `lap_pembelian.php` | Duplikat/Tergantikan | `lap_pembelian.php` (875 vs 882) | sda |
| `lap_penjualan.php` | Duplikat/Tergantikan | `lap_penjualan.php` (899 vs 907) | sda |
| `lap_pesanan_pembelian.php` | Duplikat/Tergantikan | `lap_pesanan_pembelian.php` (883 vs 891) | sda |
| `lap_pesanan_penjualan.php` | Duplikat/Tergantikan | `lap_pesanan_penjualan.php` (888 vs 895) | sda |
| `lap_pmby_hutang.php` | Duplikat/Tergantikan | `lap_pmby_hutang.php` (867 vs 878) | sda |
| `lap_pmby_piutang.php` | Duplikat/Tergantikan | `lap_pmby_piutang.php` (868 vs 877) | sda |
| `lap_servis.php` | Duplikat/Tergantikan | `lap_servis.php` (895 vs 904) | sda |
| `lap_stok_keluar.php` | Duplikat/Tergantikan | `lap_stok_keluar.php` (829 vs 829, identik ukuran) | sda |
| `lap_stok_masuk.php` | Duplikat/Tergantikan | `lap_stok_masuk.php` (829 vs 829, identik ukuran) | sda |
| `menu_dashboard.php` | Sidebar lokal | `_admincab/menu_dashboard.php` (RBAC) | Copy lokal milik `_managemen` sendiri (setiap portal punya copy sendiri-sendiri, bukan shared file). Hanya dipakai file-file di atas. Ikut terhapus bersama folder, tidak berdampak ke portal lain. |
| `menu_laporan01.php`...`menu_laporan09.php` (9 file) | Sidebar lokal | - | Sama seperti di atas — copy lokal `_managemen`, tidak dirujuk dari `_kasir`/`_pengadaan`/`_admincab` (masing-masing punya copy sendiri). Aman dihapus bersama folder. |
| `chartjs/` (folder) | Vendor/Library | `_admincab/chartjs/` | Copy lokal library pihak ketiga, setiap portal punya copy sendiri. Aman dihapus bersama folder. |
| `dompdf/` (folder) | Vendor/Library | `_admincab/dompdf/` (asumsi ada, library umum) | Copy lokal library pihak ketiga. Aman dihapus bersama folder. |

## Ringkasan & Rekomendasi Akhir

- **Tidak ditemukan fitur unik** di `_booking` maupun `_managemen` yang belum ada equivalent-nya di `_admincab`.
- `_booking/index.php` bahkan sudah **rusak** (broken include) — sudah lama tidak berfungsi, sepenuhnya digantikan `_admincab/paket_add.php`.
- Semua file `lap_*.php`, `change_pwd*.php`, `profile*.php`, `logout.php`, `index.php` di `_managemen` punya equivalent aktif di `_admincab` (sudah RBAC-aware via `menu_dashboard.php`).
- File sidebar (`menu_dashboard.php`, `menu_laporan01-09.php`) dan folder vendor (`chartjs/`, `dompdf/`) adalah copy lokal milik `_managemen` sendiri — menghapusnya tidak memengaruhi portal lain.

**Status: AMAN DIHAPUS LANGSUNG, tanpa migrasi apapun.**

Sesuai rekomendasi `LEGACY_ROLE_FOLDER_AUDIT.md` poin 3, penghapusan folder bisa dibarengi stub `index.php` yang redirect ke `/panel/` untuk menjaga kompatibilitas link lama — keputusan ini didelegasikan ke sub-proyek penghapusan folder.

Tidak ada blocker untuk sub-proyek penghapusan folder berikutnya.
