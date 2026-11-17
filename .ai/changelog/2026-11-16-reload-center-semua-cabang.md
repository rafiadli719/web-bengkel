# 2026-11-16 — Muat ulang PACUL, CIKDITIRO, TRAYEMAN, PST dari fitmotor_center + Fase K (lokal)

Sumber angka: bagian "6. Hasil sesi" di `docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md`.
Pemetaan kolom lengkap: `docs/MAPPING_CENTER_KE_WEBBASE.md`. Semua perubahan ini baru di DB lokal, produksi belum.

## Added
- Fase K: kunci gabungan nomor transaksi. PK `tblorder_header` jadi (no_order, kd_cabang), FK DO dan log approval ikut gabungan,
  UNIQUE (nomor, kd_cabang) di 9 header (+ `tblretur_penjualan_header` `uk_fk_rj_h`).
  Migrasi `db/migrations/2026-11-16_run_fase_k_po_composite.php`, `2026-11-16_run_fase_k_unique_header.php` (keduanya ada `down`).
- `db/migrations/2026-11-16_run_index_performa_servis_stok.php`: `idx_nosvc_cab (no_service, kd_cabang)` di `tblservis_barang` dan
  `tblservis_jasa`, `idx_tanggal` di `tbstok`.
- Penjaga pre-commit `scripts/audit_nomor_transaksi.php` (query nomor transaksi non-servis wajib `kd_cabang`).
- `scripts/center_reload/generate_mapping_doc.php` dan `docs/MAPPING_CENTER_KE_WEBBASE.md` (digenerate dari `$DS` di `reload_cabang.php`).
- Laporan review manual di `docs/planning/review/`: `satuan_beda_item.csv` (45 item), `supplier_kode_bentrok.csv` (7 kode), `supplier_nama_dobel.csv` (15 nama).

## Changed
- PACUL dimuat (sesi 2): servis 41.228, pembelian 9.824, penjualan 6.225, PO 2.788, stok 655 item. Statistik 16.387 pelanggan.
  Backup `backups/pre-center-reload-20261116-pacul.sql`, tabel `bak_cr1116_PACUL_*`.
- CIKDITIRO: servis 16.410, pembelian 4.389, PO 2.158, hutang 639. TRAYEMAN: servis 2.896, pembelian 1.134, PO 746.
  PST (tanpa servis): pembelian 1.212, penjualan 1.518, PO 41, hutang 512, pesanan jual 1.716. Statistik CDT 6.828, TRM 1.762.
  Dump `backups/pre-center-reload-20261116-cikditiro.sql`, tabel `bak_cr1116_<CAB>_*`, tag `1116`.
  Verify COCOK di semua cek (jumlah baris + rupiah + saldo stok). Sumber: salinan lokal `fitmotor_center` (data s/d 2026-11-12).
- 5 PO web lama diganti nomor seri `PW26...`; seri PO web resmi `PW` (keputusan Rafi) supaya tidak bentrok dengan seri Access `PSyy...`.
- `tbcabang.tipe_cabang`: semua `'2'` kecuali PST (gudang pusat) `'1'`.
- Servis `TotalAkhir=0` dibiarkan (dicek di center: semua kasus diskon 100% atau kosong, bukan cacat).
- 184 query di 82 file diberi filter `kd_cabang`.

## Fixed
- Nomor sama di PACUL dan PESALAKAN (mis. `PS21000000001`) kini tampil terpisah, tidak bercampur.
- Data uji antar cabang TRAYEMAN ikut terhapus oleh muat ulang (disengaja).

## Hasil uji tulis (PACUL, CIKDITIRO, TRAYEMAN, curl + cek DB)
- Lulus: kas, PO -> approve -> DO, pembelian tunai, retur pembelian, penjualan, retur penjualan, servis bayar, antar cabang PST -> cabang.
- Smoke 250 halaman PACUL: 247 OK (3 fragmen tanpa koneksi). CDT dan TRM 13/15 halaman OK.

## Known issues
- Antar cabang non-PST (mis. PACUL <-> CIKDITIRO) tidak bisa dikirim: guard `is_pusat` butuh `tipe_cabang='1'`. Perlu keputusan Rafi.
- Admin cabang hanya ada di PESALAKAN dan PST; staf asli per cabang dibutuhkan (admin CIKDITIRO kosong).
- Servis yang `no_polisi` tidak ada di `tblkendaraan` (CDT 26, PCL 1, PSL 43, TRM 6 nopol) ditolak "Data kendaraan tidak ditemukan".
- 3 pelanggan gagal statistik per cabang (CDT, TRM) dan 9 servis di 3 cabang ber-`no_pelanggan` = nopol, belum bisa dipetakan.
- Saldo negatif: 5 baris `kd_cabang='001'` (legacy) dan 2 kode item rusak dari servis uji. 74 kode item di stok tanpa master (saldo ~0).
- Detail servis yatim (jasa PST 7, PESALAKAN 5, TRAYEMAN 2; barang PST 5, PESALAKAN 10, TRAYEMAN 10), dibiarkan sebagai riwayat.
- Belum diuji: retur servis dan approve retur di PACUL, CIKDITIRO, TRAYEMAN.
- Produksi: belum. Jalankan migrasi (runner di shared hosting) sebelum deploy.
