# 2026-11-02 E2E klik penuh sisa halaman stok dan servis

## Fixed
- `app/stok_keluar_add_rst.php`: tombol `btnsimpan` di-disable sinkron di handler submit sehingga tidak terkirim di POST dan handler PHP skip diam-diam (header dan stok tidak tersimpan). Disable ditunda `setTimeout(0)`.
- `app/servis-input-reguler-jemput.php`: `SUM()` tanpa baris jasa bernilai NULL sehingga `subtotal_jasa=''` ditolak MySQL strict ("Data truncated"); servis jemput berisi barang saja tidak bisa dibayar. Total diberi `?? 0`.

## Tested (CIKDITIRO, mode live sementara, ledger/tbstok/saldo/selfCheck cocok)
- Stok: `stok_masuk_add`, `stok_masuk_add_rst`, `stok_keluar_add`, `stok_keluar_add_rst`, `so-item-masuk-rst`, `so-item-keluar-rst`.
- Servis bayar: `servis-input-reguler`, `servis-input-reguler-jemput`, `servis-garansi`.
- `servis-input-reguler-rst.php` read-only (tanpa tombol bayar, handler tidak terjangkau UI); `servis-input-reguler-jemput-rst.php` kode mati. Keduanya hanya lint.

## Catatan
- Dropdown Admin form bayar servis ambil dari `tbuser_karyawan` (CS/KSR/ADM) per cabang; CIKDITIRO kosong, perlu data karyawan admin dari Rafi.
- Commit `a6a2eb5`: belum push, belum deploy fitmotor1.
- Data uji dihapus, mode CIKDITIRO kembali `off`, statistik pelanggan template dipulihkan.
