# Audit halaman app/*.php yang belum jadi item sidebar

Sumber: app/menu_config.php (155 URL menu) dibanding 763 file PHP langsung di app/.

Kandidat halaman ber-layout belum di menu: 73. Hasil ini memisahkan halaman utama vs sub-page/CRUD.

## Prioritas tambah ke sidebar / review menu

- jasa-list.php (Lainnya) — refs: jasa-input.php. Saran: Data Master > Daftar Item (Master Jasa Service)
- lap_antarcab.php (Laporan) — refs: -. Saran: Laporan > Antar Cabang (summary/combined report)
- lap_profit_insentif.php (Laporan) — refs: -. Saran: Laporan (Profit & Insentif)
- master_perusahaan.php (Master/Data Referensi) — refs: -. Saran: Data Master (Master Perusahaan)
- supplier_pabrik_spart.php (Master/Data Referensi) — refs: -. Saran: Data Master > Supplier (mapping pabrik sparepart) — butuh parameter kd, lebih aman sebagai sub-action dari supplier.php
- workorder-list.php (Servis) — refs: ajax-get-keluhan-workorder-list.php, master-diskon-periode.php, paket.php, workorder-detail-view.php, workorder-input.php. Saran: Data Master > Daftar Item (replace/clarify existing Work Order/Paket if needed; current menu points to paket.php)
- kas_keluar.php (Transaksi/Operasional) — refs: kas_keluar_add.php, kas_keluar_del.php, kas_keluar_edit.php, kas_keluar_edit_proses.php, kas_keluar_proses.php. Saran: Keuangan Kasir atau legacy Kas (Pengeluaran Kas) — cek duplikasi dengan _keuangan/kasir/pengeluaran.php
- kas_masuk.php (Transaksi/Operasional) — refs: kas_masuk_add.php, kas_masuk_del.php, kas_masuk_edit.php, kas_masuk_edit_proses.php, kas_masuk_proses.php, lap_kas_keluar.php, lap_kas_masuk.php, lap_stok_keluar.php, lap_stok_masuk.php. Saran: Keuangan Kasir atau legacy Kas (Kas Masuk) — cek duplikasi dengan _keuangan/kasir/pemasukan.php
- pengadaan_antarcab_push.php (Transaksi/Operasional) — refs: pengadaan_antarcab.php. Saran: Antar Cabang > Pengadaan Barang (Kirim tanpa request / push)

## Dead code terkonfirmasi (jangan tambah ke sidebar)

- do_receive.php — tidak ada ref internal; sudah masuk backlog arsip dead code.
- do_tracking_update.php — tidak ada ref internal; sudah masuk backlog arsip dead code.

## False positive / sub-page fungsional (tidak perlu sidebar langsung)

- change_pwd.php — sub-page dari: access-sync-report.php, access-sync.php, akun_biaya.php, akun_biaya_edit.php, akun_kas.php
- dokter.php — sub-page dari: barang_rak_add.php, cabang_add.php, cabang_tipe_add.php, dokter_add.php, hjual_jasa_add.php
- jasa-input.php — sub-page dari: jasa-list.php
- profile.php — sub-page dari: access-sync-report.php, access-sync.php, akun_biaya.php, akun_biaya_edit.php, akun_kas.php
- setting-highlight-member.php — sub-page dari: servis-carinopol.php
- setting-threshold-harga.php — sub-page dari: alarm-harga-beli.php
- so-item-keluar.php — sub-page dari: penyesuaian-stok-otomatis.php, so-item-keluar-batal.php
- so-item-masuk.php — sub-page dari: penyesuaian-stok-otomatis.php, so-item-masuk-batal.php, so-keluar-add-cetak.php, so-masuk-add-cetak.php
- stok-akhir-result.php — sub-page dari: stok-akhir-rst.php
- tipe-motor-kategori.php — sub-page dari: item-motor-mapping.php
- user_management.php — sub-page dari: user.php
- laporan-cancel-servis.php — sub-page dari: lap_cancel_servis.php
- barang_history_hp.php — sub-page dari: barang.php, barang_rst.php
- barang_kartu_stok.php — sub-page dari: barang.php, barang_rst.php
- barang_list_improved.php — sub-page dari: barang.php
- barang_stok_akhir.php — sub-page dari: barang.php, barang_rst.php
- kendaraan-history.php — sub-page dari: kendaraan.php, kendaraan_rst.php
- master-keluhan.php — sub-page dari: keluhan-proses.php
- master_jenis_item.php — sub-page dari: master_jenis_item_add.php, master_jenis_item_del.php, master_jenis_item_edit.php
- master_jenis_item_add.php — sub-page dari: master_jenis_item.php
- master_jenis_item_del.php — sub-page dari: master_jenis_item.php
- master_jenis_item_edit.php — sub-page dari: master_jenis_item.php
- master_jenis_motor.php — sub-page dari: master_jenis_motor_add.php, master_jenis_motor_del.php, master_jenis_motor_edit.php
- master_jenis_motor_add.php — sub-page dari: master_jenis_motor.php
- master_jenis_motor_del.php — sub-page dari: master_jenis_motor.php
- master_jenis_motor_edit.php — sub-page dari: master_jenis_motor.php
- master_karyawan_add.php — sub-page dari: master_karyawan.php
- master_karyawan_edit.php — sub-page dari: master_karyawan.php
- master_kategori_item.php — sub-page dari: master_kategori_item_add.php, master_kategori_item_add_simple.php, master_kategori_item_del.php, master_kategori_item_del_simple.php, master_kategori_item_edit.php
- master_kategori_motor.php — sub-page dari: master_kategori_motor_add.php, master_kategori_motor_del.php, master_kategori_motor_edit.php
- master_kategori_motor_add.php — sub-page dari: master_kategori_motor.php
- master_kategori_motor_del.php — sub-page dari: master_kategori_motor.php
- master_kategori_motor_edit.php — sub-page dari: master_kategori_motor.php
- master_merk_motor.php — sub-page dari: master_merk_motor_add.php, master_merk_motor_del.php, master_merk_motor_edit.php
- master_merk_motor_add.php — sub-page dari: master_merk_motor.php
- master_merk_motor_del.php — sub-page dari: master_merk_motor.php
- master_merk_motor_edit.php — sub-page dari: master_merk_motor.php
- master_nama_barang.php — sub-page dari: master_nama_barang_add.php, master_nama_barang_del.php, master_nama_barang_edit.php
- master_nama_barang_add.php — sub-page dari: master_nama_barang.php
- master_nama_barang_del.php — sub-page dari: master_nama_barang.php
- master_nama_barang_edit.php — sub-page dari: master_nama_barang.php
- master_satuan_barang.php — sub-page dari: master_satuan_barang_add.php, master_satuan_barang_del.php, master_satuan_barang_edit.php
- master_satuan_barang_add.php — sub-page dari: master_satuan_barang.php
- master_satuan_barang_del.php — sub-page dari: master_satuan_barang.php
- master_satuan_barang_edit.php — sub-page dari: master_satuan_barang.php
- master_tipe_detail.php — sub-page dari: master_tipe_detail_add_simple.php, master_tipe_detail_del_simple.php, master_tipe_detail_edit.php, master_tipe_detail_edit_simple.php
- master_tipe_detail_add_simple.php — sub-page dari: master_tipe_detail.php
- master_tipe_detail_del_simple.php — sub-page dari: master_tipe_detail.php
- master_tipe_detail_edit_simple.php — sub-page dari: master_tipe_detail.php
- master_tipe_header.php — sub-page dari: master_tipe_header_add.php, master_tipe_header_add_simple.php, master_tipe_header_del.php, master_tipe_header_del_simple.php, master_tipe_header_edit.php
- master_tipe_header_add_simple.php — sub-page dari: master_tipe_header.php
- master_tipe_header_del_simple.php — sub-page dari: master_tipe_header.php
- master_tipe_header_edit_simple.php — sub-page dari: master_tipe_header.php
- mekanik_management.php — sub-page dari: mekanik.php
- pelanggan_awal_piutang.php — sub-page dari: pelanggan.php, pelanggan_rst.php
- supplier_awal_hutang.php — sub-page dari: supplier.php
- input_garapan.php — sub-page dari: save_garapan.php
- input_pelanggan_awal.php — sub-page dari: pelanggan_add_enhanced.php, servis-carinopol.php
- servis-reguler-byr.php — sub-page dari: servis_edit_item_byr.php, servis_edit_paket_byr.php, servis_hapus_item_byr.php, servis_hapus_paket_byr.php
- workorder-input.php — sub-page dari: paket.php, workorder-detail-hapus.php, workorder-detail-view.php, workorder-list.php
- pengadaan_antarcab_terima.php — sub-page dari: pengadaan_antarcab.php, pengadaan_antarcab_detail.php
- penjualan_buat_servis.php — sub-page dari: penjualan_buat_servis_proses.php, penjualan_detail.php, save_pelanggan_servis.php
