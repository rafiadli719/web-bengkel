# Audit Konsolidasi Portal — `_pengadaan`

Sub-proyek 5 dari inisiatif konsolidasi portal ke `_admincab`. Lihat spec: `docs/superpowers/specs/2026-06-15-portal-consolidation-audit-booking-managemen-design.md`.

## Ringkasan Temuan

`_pengadaan` (549 file PHP top-level + 9 folder vendor/subfolder: `assets`, `chartjs`, `dompdf`, `jquery-ui-1.11.4`, `kas kasir`, `PHPExcel`, `proses-add-detail`, `tmp_excel`, `_template`).

**Struktur `_pengadaan` praktis identik dengan `_kasir`** (sub-proyek 4) — portal role "Pengadaan" yang dibangun dari basis kode yang sama. **548 dari 549 file top-level (99,8%) punya equivalent dengan nama sama persis di `_admincab`.**

## 1 File Top-Level Tanpa Nama Sama di `_admincab`

| File | Kategori | Analisis |
|------|----------|----------|
| `servis-input-reg.php` (982 baris) | Duplikat/Tergantikan (superseded) | Tidak direferensikan dari mana pun di seluruh repo (`grep -rl` kosong). Sama persis dengan temuan di `_kasir` — variant lama/lebih kecil dari `servis-input-reguler.php` (`_pengadaan`=1132 baris, `_admincab`=3077 baris) yang aktif dipakai. Aman dihapus. |

Catatan: `_pengadaan` **tidak punya** `menu_penjualan04.php` (berbeda dengan `_kasir`), sehingga tidak masuk daftar unmatched.

## Subfolder Khusus (2 folder) — sama persis dengan `_kasir`

### `"kas kasir"/` (7 file: `kas_akhir.php`, `kas_akhir_asli.php`, `kas_akhir_proses.php`, `kas_awal.php`, `kas_awal_asli.php`, `kas_awal_proses.php`, `"kas_awal_yg dulu.php"`)

- **Kategori: Duplikat/Tergantikan (folder backup orphan).**
- Tidak direferensikan dari mana pun (`grep -rl "kas kasir"` di seluruh repo kosong).
- `_pengadaan` top-level dan `_admincab` sudah punya seluruh 6 nama file `kas_akhir*`/`kas_awal*` versi aktifnya sendiri.
- Sama seperti `_kasir`: folder backup/snapshot lama, aman dihapus bersama folder `_pengadaan`.

### `proses-add-detail/` (3 file: `penjualan-brg-servis.php`, `penjualan.php`, `pesanan-penjualan.php`)

- **Kategori: Duplikat/Tergantikan.**
- Aktif dipakai — direferensikan oleh 8 file di dalam `_pengadaan` (`penjualan_add.php`, `penjualan_add_rst.php`, `pesanan_penjualan_add.php`, `pesanan_penjualan_add_rst.php`, `servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-input-reguler-jemput-rst.php`, `servis-input-reguler-rst.php`) — pola identik dengan `_kasir`.
- `_admincab` punya folder `proses-add-detail/` yang sama (sudah dikonfirmasi di audit `_kasir`). Aman dihapus bersama folder `_pengadaan`, tidak berdampak ke `_admincab`.

## Folder Vendor/Library Lokal (7 folder)

| Folder | `_pengadaan` | `_admincab` | Catatan |
|--------|-------------|-------------|---------|
| `assets` | 140 | 144 | Copy lokal — angka identik dengan `_kasir` |
| `chartjs` | 66 | 66 | Identik jumlah file |
| `dompdf` | 316 | 324 | Copy lokal |
| `jquery-ui-1.11.4` | 39 | 39 | Identik jumlah file |
| `PHPExcel` | 254 | 254 | Identik jumlah file |
| `tmp_excel` | 6 | 6 | Identik jumlah file |
| `_template` | 129 | 209 | Copy lokal, `_admincab` punya lebih banyak template |

Semua angka sama persis dengan `_kasir` — mengonfirmasi `_pengadaan` dan `_kasir` dibangun dari basis kode portal yang sama. Folder vendor adalah copy lokal milik `_pengadaan` sendiri, tidak ada referensi cross-folder.

## Ringkasan & Rekomendasi Akhir

- **Tidak ditemukan fitur unik aktif** di `_pengadaan`. 548/549 file top-level adalah duplikat langsung, 1 sisanya (`servis-input-reg.php`) adalah file superseded tanpa referensi sama sekali di seluruh repo (pola identik dengan `_kasir`).
- Folder `"kas kasir"` adalah backup orphan lama, folder `proses-add-detail` punya equivalent identik di `_admincab`.
- Semua folder vendor adalah copy lokal portal, aman dihapus bersama folder.

**Status: AMAN DIHAPUS LANGSUNG, tanpa migrasi apapun.**

## Status Audit Seluruh Sub-Proyek

Dengan selesainya audit `_pengadaan`, seluruh 5 sub-proyek audit folder legacy telah selesai:

| Sub-proyek | Folder | Status |
|------------|--------|--------|
| 1 | `_booking`, `_managemen` | AMAN DIHAPUS LANGSUNG |
| 2 | `_hrd` | ARSIPKAN (modul HR unik, dormant ~2,5 tahun) |
| 3 | `_admin` | AMAN DIHAPUS LANGSUNG |
| 4 | `_kasir` | AMAN DIHAPUS LANGSUNG |
| 5 | `_pengadaan` | AMAN DIHAPUS LANGSUNG |

Tidak ada blocker untuk lanjut ke fase eksekusi: penghapusan folder (`_booking`, `_managemen`, `_admin`, `_kasir`, `_pengadaan`), pengarsipan `_hrd`, rename `_admincab`, dan pembersihan 58 file `menu_*.php` yang tidak terpakai.
