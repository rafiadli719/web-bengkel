# 2026-11-15 — Audit DB + muat ulang data Adiwerna dari fitmotor_center (lokal)

## Added
- `docs/planning/AUDIT_DATABASE_CENTER_VS_DBBENGKEL_2026-11-15.md` + lampiran CSV klasifikasi 396 objek.
- `docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md` (keputusan Rafi, analisa konflik master + usulan, rencana 5 sesi, Fase K).
- `db/migrations/2026-11-15_run_center_reload_schema.php [up|down]`: `kd_cabang` di `tblhutang_detail`/`tblpiutang_detail`,
  22 index reload, tabel `center_reload_log`. Lokal: SUDAH up. Produksi: BELUM.
- `scripts/center_reload/reload_cabang.php`: preflight/run/verify/rollback per cabang, sumber VPS read-only via env.

## Changed
- Data lokal PESALAKAN (Adiwerna) diganti isi center: servis 50.269 (dulu 48.312 PSL, salah atribusi detail),
  barang servis 195.992, jasa 61.921, pembelian 13.448, penjualan 11.241, PO 796, pesanan jual 1.390, hutang 3.006,
  retur beli 12, stok masuk/keluar, kas, harga per cabang; kartu stok dibangun ulang (saldo = Access).
  Verify cocok persis (jumlah + rupiah). Status servis lunas sekarang `status='4'`/`bayar` (dulu `1`).

## Fixed
- 18.699 alarm harga beli palsu dari trigger saat muat histori dihapus.
- `app/barang_kartu_stok_rst.php`: saldo awal bulan dihitung dari mutasi sebelum tanggal 1 (dulu `inv_jmlawal`, saldo selalu mulai 0),
  baris diurutkan per tanggal. Diuji browser: item `08232-2MB-K8JN1` Adiwerna saldo akhir 65 = Access.
- `statistik_pelanggan` 16.712 pelanggan Adiwerna dihitung ulang (`scripts/center_reload/refresh_statistik_cabang.php`), tier naik 70, turun 0.

## Known issues
- PO: 7 nomor Access bentrok dengan PO uji web (PST & PESALAKAN), dilewati. Perlu Fase K + keputusan data uji.
- `view_suggested_parts` rusak (tidak bisa di-dump), dipakai `app/ajax-get-suggested-parts.php`.
- Sebelum sesi PACUL wajib Fase K (kunci gabungan nomor transaksi non-servis).
- `lap_servis.php` tidak memfilter cabang (semua cabang tampil), tinjau di Fase K.

## Hasil uji pasca-reload (2026-11-15)
- Smoke 434 halaman OK; laporan cocok DB. Temuan: laporan tanpa filter cabang, 1.799 servis `total_akhir=0` (sama dengan sumber), 15 detail yatim PCL, 15 item tanpa master, halaman lambat. Lihat `docs/planning/HASIL_UJI_E2E_SETELAH_RELOAD_ADIWERNA_2026-11-15.md`.

## 2026-11-16 Fase K + PACUL
Lihat bagian "Sesi 2: PACUL" di `docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md`. Commit 181391e, 270fd9f.
