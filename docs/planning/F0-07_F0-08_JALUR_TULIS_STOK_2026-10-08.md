# F0-07 / F0-08 — Jalur Tulis Stok dan Audit Potong Ganda Servis

Tanggal: 2026-10-08. Dasar: `git grep` atas `INSERT/UPDATE/DELETE tbstok` di `app/` (di luar `archive/`). Status shadow: kolom terakhir.

## F0-07 Daftar jalur tulis `tbstok`

| Kelompok | Berkas (baris awal) | `tipe` | Pembungkus transaksi DB | Shadow |
|---|---|---|---|---|
| Pembelian | `pembelian_add.php` (546), `pembelian_add_rst.php` (350), `pembelian_cab_add_proses.php` (146) | 2 | tidak | terpasang |
| Penerimaan DO | `do_receive.php` (114), `do_from_po.php` (261) | 2 | ya | terpasang (biaya dari PO) |
| Penjualan | `penjualan_add.php` (344), `penjualan_add_rst.php` (471), `penjualan_cab_add_proses.php` (180), `penjualan_cab_add_proses_mitra.php` (188) | 3 | tidak | terpasang |
| Servis | `servis-reguler-byr.php` (585), `servis-input-reguler.php` (797), `-rst.php` (964), `-jemput.php` (2622), `-jemput-rst.php` (1195), `servis-garansi.php` (1164) | 4 | tidak | terpasang |
| Retur | `save_retur_pembelian.php` (95, tipe 7), `save_retur_penjualan.php` (94, tipe 8), `retur_servis_approve.php` (35, tipe 9) | 7/8/9 | ya | terpasang |
| Batal retur (DELETE) | `retur_pembelian_batal.php` (41), `retur_penjualan_batal.php` (35) | 7/8 | ya | terpasang sebagai pembalikan (VOID) |
| Antar cabang | `pengadaan_antarcab_proses.php` (84), `pengadaan_antarcab_push.php` (110), `pengadaan_antarcab_terima.php` (97) | 3 kirim, 2 terima | ya | terpasang |
| Stok manual | `stok_masuk_add.php` (477), `stok_masuk_add_rst.php` (430), `stok_keluar_add.php` (197), `stok_keluar_add_rst.php` (671) | 5 / 6 | rst keluar ya | terpasang |
| Opname | `so-item-masuk-rst.php` (69), `so-item-keluar-rst.php` (69) | 5 / 6 | tidak | terpasang |
| Sinkron Access | `_include_access_sync.php` (1101 DELETE, 1109 INSERT) | campur | ya | TIDAK dipasang (lihat catatan) |
| Backfill admin | `admin-backfill-tbstok-servis.php` (68, 123) | 4 | tidak | TIDAK dipasang (alat sekali pakai) |
| Master barang | `save_barang.php` (102) | 1, `kd_cabang` kosong | tidak | TIDAK dipasang (lokasi kosong tidak bisa dipetakan) |
| Legacy | `save_pembelian.php` (68, 76), `save_penjualan.php` (87, 95) | 2 | tidak | TIDAK dipasang. `save_pembelian.php` masih dipanggil `pembelian_add_next_rst.php`; `save_penjualan.php` tanpa pemanggil. Verifikasi pemakaian sebelum diarsipkan (F2-11) |

Catatan:
- `_include_access_sync.php` menimpa saldo cabang dengan DELETE+INSERT per item. Untuk cabang yang `live`, penulisan ini harus dihentikan (F2-13). Selama `off/shadow` tidak ada perubahan.
- Total titik tulis aktif yang dicakup shadow: 29 dari 38.
- `tipe` ambigu tetap seperti Lampiran B planning (2 dan 3 dipakai pembelian dan transfer). Ledger baru memakai `trx_type` eksplisit (`PEMBELIAN`, `PENJUALAN`, `TRANSFER_OUT`, `TRANSFER_IN`, `SERVIS`, `RETUR_*`, `STOK_MASUK`, `STOK_KELUAR`, `OPNAME_*`, `DO_TERIMA`, `OPENING`, `VOID_*`).

## F0-08 Audit potong stok ganda servis

Enam titik potong stok servis, semua pada saat BAYAR:

| Berkas | Guard sebelum insert | Filter `asal_barang` |
|---|---|---|
| `servis-input-reguler.php`, `-jemput.php`, `servis-garansi.php` | `COUNT(tbstok) WHERE no_transaksi=$no_service AND tipe='4'` harus 0 | `SERVIS` saja |
| `servis-input-reguler-rst.php`, `-jemput-rst.php` | sama | tidak ada (semua baris barang) |
| `servis-reguler-byr.php` | TIDAK ADA | tidak ada |

Temuan:
1. **`servis-reguler-byr.php` tanpa guard dan tanpa filter `asal_barang`.** Bila servis dibayar dua kali atau lewat dua jalur, stok dipotong dua kali. Barang `PART-CUST` (milik pelanggan) dan `PENJUALAN` (sudah dipotong di nota penjualan) ikut dipotong, berbeda dari lima jalur lain. Perlu keputusan: samakan dengan lima jalur lain (rekomendasi).
2. **Guard di lima jalur lain hanya memakai `no_service`, tanpa cabang.** `no_service` tidak unik antar cabang (30 rb+ duplikat, lihat catatan KRITIS sebelumnya). Servis cabang B dengan nomor yang sama dengan servis cabang A yang sudah dibayar akan MELEWATI pemotongan stok (potong terlewat, bukan ganda).
3. **`-rst.php` dan `-jemput-rst.php` tanpa filter `asal_barang`**, sehingga ikut memotong `PART-CUST` dan `PENJUALAN`.

Ledger baru kebal terhadap temuan 1: idempotency `UNIQUE (ref_type, ref_no, ref_line, trx_type, kd_cabang, no_item)` dengan `ref_type=SERVIS`, `ref_no=no_service`. Bayar kedua dari jalur manapun terdeteksi idempoten. Perbaikan di jalur lama sengaja belum dilakukan (mengubah perilaku stok live) dan masuk F2-04 saat cutover.
