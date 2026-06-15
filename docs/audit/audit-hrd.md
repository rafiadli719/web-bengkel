# Audit Konsolidasi Portal — `_hrd`

Sub-proyek 2 dari inisiatif konsolidasi portal ke `_admincab`. Lihat spec: `docs/superpowers/specs/2026-06-15-portal-consolidation-audit-booking-managemen-design.md` (metodologi sama, diterapkan ke `_hrd`).

## Ringkasan Temuan

`_hrd` (82 file PHP top-level + 8 folder vendor/library ~882 file) adalah **modul HR/payroll mandiri** (absensi, data pegawai, gaji/salary, training, pendidikan, keluarga pegawai, jabatan, divisi) dengan skema database terpisah (~25 tabel khusus: `tbpegawai`, `tbabsensi`, `tbemp_education`, `tbemp_family`, `tbemp_training`, `tbemp_tunjangan`, `tbpegawai_salary`, `tbsalary_*`, `tbjabatan`, `tbdivisi`, `tbwork_schedule`, dll).

**Status aktivitas**: data `tbabsensi` terakhir tanggal **2023-10-19** (≈2,5 tahun dormant), `tbpegawai.tgl_input` tidak pernah terisi (`0000-00-00`), 11 akun user role HRD `last_login=null`. Modul ini sudah ditinggalkan jauh sebelum proyek konsolidasi RBAC dimulai.

## Kategori File

### 1. Shell/Common (7 file) — Duplikat/Tergantikan

| File | Equivalent di `_admincab` |
|------|----------------------------|
| `change_pwd.php` | ✅ ada |
| `change_pwd_proses.php` | ✅ ada |
| `logout.php` | ✅ ada |
| `profile.php` | ✅ ada |
| `profile_proses.php` | ✅ ada |
| `index.php` | ✅ ada |
| `menu_dashboard.php` | Sidebar lokal `_hrd` (copy sendiri, sama seperti pola `_managemen`) |

Aman dihapus — fungsionalitasnya identik dengan yang sudah ada di `_admincab`.

### 2. Modul HR inti (75 file) — Unik, tidak ada equivalent, TAPI dormant

| Grup | Jumlah file | Contoh |
|------|-------------|--------|
| Absensi (CRUD, upload, import) | 20 | `absensi.php`, `absensi_input.php`, `absensi_upload*.php`, `absensi_edit*.php` |
| Laporan absensi (termasuk export PDF/Excel) | 6 | `laporan_absensi.php`, `laporan_absensi_excel.php`, `laporan_absensi_pdf.php` |
| Data pegawai (CRUD + education/family/training/jabatan/gaji) | 39 | `pegawai.php`, `pegawai_add.php`, `pegawai_education*.php`, `pegawai_gaji*.php`, `pegawai_jabatan*.php`, `pegawai_keluarga*.php`, `pegawai_training*.php` |
| Sidebar menu HR | 6 | `menu_absensi01-05.php`, `menu_pegawai.php` |
| Save handler | 4 | `save_absensi.php`, `save_absensi_dashboard.php`, `save_absensi_upload.php`, `save_pegawai.php` |

**Tidak ada satupun equivalent di `_admincab`** — `_admincab` 100% berfokus pada operasional bengkel (penjualan/pembelian/servis/stok), tidak punya konsep pegawai/absensi/gaji sama sekali.

### 3. Folder Vendor/Library lokal (8 folder, ~882 file)

| Folder | Jumlah file | Keterangan |
|--------|-------------|------------|
| `PHPExcel` | 254 | Library import/export Excel untuk absensi — copy lokal |
| `dompdf` | 316 | Library PDF untuk laporan absensi — copy lokal |
| `chartjs` | 66 | Copy lokal, pola sama dengan portal lain |
| `jquery-ui-1.11.4` | 39 | Asset frontend lokal |
| `ambil_tgl` | 42 | Komponen date-picker lokal |
| `tmp_excel` | 7 | Folder temp untuk proses import Excel |
| `_template` | 18 | Template halaman lokal `_hrd` |
| `assets` | 140 | Asset frontend (Ace template) lokal |

Tidak ada referensi dari luar `_hrd` ke folder-folder ini — sepenuhnya lokal/mandiri.

## Rekomendasi

Berbeda dengan `_booking`/`_managemen` (yang murni duplikat dan aman dihapus), `_hrd` berisi **fitur unik (modul HR)** yang:
- Tidak punya equivalent di `_admincab` — kalau dihapus permanen, kemampuan kelola absensi/pegawai/gaji **hilang dari sistem** (meski sudah dormant 2,5 tahun).
- Migrasi penuh ke `_admincab` adalah sub-proyek besar tersendiri (75 file fungsional + 25 tabel DB baru + entry `menu_config.php` + permission RBAC role HRD yang sesuai).

**Rekomendasi: ARSIPKAN, bukan hapus permanen.**
- Pindahkan folder `_hrd` (kecuali 7 file shell yang sudah ada equivalent) ke lokasi arsip (misal `_archive_legacy/_hrd/`) di luar struktur portal aktif — sesuai rekomendasi `LEGACY_ROLE_FOLDER_AUDIT.md` poin 2-3 (arsip + stub redirect).
- 7 file shell (`change_pwd*`, `logout`, `profile*`, `index`, `menu_dashboard`) dihapus langsung (duplikat).
- Tabel DB HR (`tbpegawai`, `tbabsensi`, dll) **tidak disentuh** — data historis tetap ada di database untuk referensi, hanya UI/akses aktifnya yang diarsipkan.
- Jika di masa depan modul HR ingin diaktifkan kembali, ini jadi sub-proyek migrasi terpisah (di luar lingkup konsolidasi portal ini).

**Status: tidak ada blocker untuk lanjut ke sub-proyek 3 (`_admin`)** — keputusan arsip vs hapus permanen untuk `_hrd` didelegasikan ke sub-proyek eksekusi penghapusan/pengarsipan folder nanti.
