# 2026-11-26 — Uji tulis Pusat (PST), retur jasa berpotongan, tabel `pelanggan_alias` (lokal)

## Added
- `db/migrations/2026-11-26_run_pelanggan_alias.php [dry|up|down]`: tabel `pelanggan_alias`, hanya menambah, `tblpelanggan` tidak diubah.
  Lokal: dry, up, up, down, up lulus. 1.970 pelanggan beda nama/telepon antar cabang, 1.042 `perlu_review`, 9.185 baris
  (JOIN ke `tblpelanggan` cocok 9.185). Butuh database `fitmotor_center` (env `CENTER_DB_NAME`) di server yang sama.
  Produksi/fm1: belum.

## Hasil uji tulis PST (curl + cek DB, akun `e2e_test_pesalakan`, cabang PST)
- Pembelian tunai `BL26000030018` (2 pcs `08232-2MB-K8JN1`, Rp32.000): lulus, stok masuk 2.
- Penjualan tunai `JL26000019905` (1 pcs, Rp19.000): lulus, stok keluar 1, saldo akhir 38 (37+2-1).
- Servis `SV26000103589`, pelanggan Gold: jasa `SVS001` Rp40.000 - 15% = Rp34.000, bayar tunai lulus (`status='4'`).
- Retur jasa berpotongan `RS26000000007` + approve: harga POST 99.999 diabaikan, refund Rp34.000, `qty_retur=1`, `total_retur=34000`.

## Known issues
- `penjualan_add_rst.php` tunai: `status_lunas='1'` tetapi `tanggal_lunas` kosong.
- `ajax_get_items_retur_servis.php`: `namaitem` jasa berisi kode (`SVS001`), bukan nama.
- `supplier_alias` belum dibuat: menunggu pemetaan manual 7 kode bentrok + 15 nama dobel (`docs/planning/review/`).
- `pelanggan_alias` belum dipakai pencarian kasir; nama/telepon kanonik di `tblpelanggan` belum ditimpa (usulan 3.1 poin 2).
- Laporan checklist-projek belum terkirim: `API_KEY_WEBBENGKEL` kosong.

## Data uji (DIHAPUS 2026-11-26 atas persetujuan Rafi, tanpa backup; statistik 4 pelanggan dihitung ulang)
- 2026-11-26: `BL26000030018`, `JL26000019905`, `SV26000103588` (belum dibayar), `SV26000103589`, `RS26000000007` (semua PST).
- Sebelumnya: `KM26000000112`, `RS26000000004/005/006`, servis `SV26000103585/586/587`.
