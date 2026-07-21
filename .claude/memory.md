# Claude Code Memory — web-bengkel/aplikasi/aplikasi

Catatan teknis lintas-sesi. Baca ini di AWAL tiap sesi kerja sebelum
mulai apa-apa. SELALU APPEND entry baru, jangan timpa/hapus yang lama.

---

## 2026-07-21 - Servis / Guard kd_cabang no_service High-Risk group

- Status: Selesai & Live, ditest E2E, commit sudah masuk.
- Yang dikerjakan: tambah `AND kd_cabang='$kd_cabang'` di query
  `tblservice` yang sebelumnya cuma filter `no_service` (bisa nembus
  data cabang lain kalau no_service kebetulan collide). 3 file:
  `app/servis-garansi.php` (6 titik UPDATE), `app/servis-reguler-byr.php`
  (3 titik: SELECT header, UPDATE status_servis, UPDATE payment),
  `_pengadaan/servis-garansi.php` (2 titik). Commit `8cf1389`.
- Keputusan/catatan penting: HANYA tabel `tblservice` yang punya kolom
  `kd_cabang`. Tabel anak (`tblservis_jasa`, `tblservis_barang`,
  `tbservis_workorder`, `tbservis_keluhan_status`, `tbservis_pengerjaan`)
  TIDAK punya kolom ini sama sekali — jangan pernah nambah guard
  `kd_cabang` ke tabel-tabel itu tanpa cek skema dulu
  (`tools/sql/fitmotor_dbbengkel_FIXED_V7.sql`). Sempat salah nambah
  guard ke 12 query `tblservis_jasa`/`tblservis_barang` di
  `_pengadaan/servis-garansi.php`, ke-revert sebelum commit.
- Test E2E: claude-in-chrome ke `http://localhost/web-bengkel/aplikasi/aplikasi/app/...`
  (session admin, cabang PST). No_service cabang sendiri
  (`SV26000103542`) → data lengkap. No_service cabang lain
  (`SV26000103548`, PESALAKAN) → semua field kosong, no leak, no error
  PHP.
- Follow-up yang masih nyantol: audit no_service High-Risk group
  sekarang TUNTAS (lanjutan `d936b15`+`a52ca6a`). Grup risiko-rendah
  (~58 file, koincidensi ganda) sengaja belum digarap — lihat Progress
  Sheet row #31.

## 2026-07-21 - Servis / Wiring Komisi Mekanik (spec, BELUM diimplementasi)

- Status: Blocked — spec salah premis, harus ditulis ulang sebelum
  lanjut implementasi. JANGAN lanjut ke writing-plans pakai spec lama.
- Yang dikerjakan: brainstorm + tulis
  `docs/superpowers/specs/2026-07-21-komisi-mekanik-wiring-design.md`
  (2 tabel baru: `bagi_hasil_komisi` config per cabang + `servis_komisi`
  snapshot custom). SETELAH ditulis, ketauan tabel `servis_komisi`
  **SUDAH ADA di produksi** (8 baris, dipakai `app/issue_add.php`
  buat fitur "Revisi Komisi Servis Pasca-Bayar").
- Keputusan/catatan penting: skema NYATA `servis_komisi`:
  ```sql
  CREATE TABLE servis_komisi (
    id, no_service,
    peran ENUM('mekanik1'..'mekanik4','kepala_mekanik1','kepala_mekanik2','admin1','admin2'),
    nominal_jasa, nominal_barang,
    persen_terpakai,
    dihitung_saat ENUM('selesai','bayar','revisi_tiket'),
    id_issue_ref, created_at
  )
  ```
  Formula yang sudah LIVE di `app/issue_add.php:211-224` (hardcode
  global, bukan config per cabang):
  ```
  kepala_mekanik/mekanik: nominal_jasa = (jasa_bersih * 0.20) / jml_mekanik
                           nominal_barang = (laba_barang * 0.05) / jml_mekanik
  admin:                   nominal_jasa = jasa_bersih * 0.05
                            nominal_barang = laba_barang * 0.05
  ```
  Progress Sheet row #16 konfirmasi: `servis_komisi` 0 baris dari alur
  BAYAR NORMAL — cuma keisi dari jalur revisi tiket CRM. Gap sebenarnya
  cuma: alur bayar normal (`servis-reguler-byr.php`/jemput/garansi)
  belum insert ke `servis_komisi` pakai formula yang sama.
- Follow-up yang masih nyantol:
  1. Baca penuh `app/issue_add.php` baris ~180-230 buat ambil utuh
     logic `jasa_bersih`/`laba_barang`/`jml_mekanik`.
  2. Baca `app/lap_komisi_mekanik.php` (sudah ada dari backlog
     2026-06-25) — pastikan gak dobel logic.
  3. Tulis ulang spec: insert ke `servis_komisi` yang SUDAH ADA (jangan
     re-desain tabel), `dihitung_saat='bayar'`, `id_issue_ref=NULL`.
     Kemungkinan besar TIDAK perlu tabel config `bagi_hasil_komisi`
     kalau tetap hardcode global.
  4. Sinkron ulang sama user sebelum lanjut writing-plans — jangan
     asumsikan spec lama (`2026-07-21-komisi-mekanik-wiring-design.md`)
     masih berlaku, walau statusnya tertulis "Disetujui" di dokumen.
