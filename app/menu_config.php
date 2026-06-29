<?php
/**
 * Menu Configuration
 * Define the sidebar menu structure and associated permissions here.
 */

return [
    [
        'title' => 'Dashboard',
        'url' => 'index.php',
        'icon' => 'fa-tachometer',
        'permission' => 'dashboard_read'
    ],
    [
        'title' => 'Data Master',
        'icon' => 'fa-cog',
        'permission' => 'master_read',
        'submenu' => [
            [
                'title' => 'Daftar Item',
                'icon' => 'fa-caret-right',
                'permission' => 'item_read',
                'submenu' => [
                    ['title' => 'Master Barang', 'url' => 'barang.php', 'permission' => 'barang_read'],
                    ['title' => 'Kategori Barang', 'url' => 'barang_kategori.php', 'permission' => 'barang_kategori_read'],
                    ['title' => 'Satuan Barang', 'url' => 'barang_satuan.php', 'permission' => 'barang_satuan_read'],
                    ['title' => 'Pabrik Barang', 'url' => 'barang_pabrik.php', 'permission' => 'barang_pabrik_read'],
                    ['title' => 'Rak Barang', 'url' => 'barang_rak.php', 'permission' => 'barang_rak_read'],
                    ['title' => 'Margin Harga Jual', 'url' => 'margin_jual.php', 'permission' => 'margin_jual_read'],
                    ['title' => 'Status Harga', 'url' => 'status_harga.php', 'permission' => 'status_harga_read'],
                    ['title' => 'Work Order/Paket', 'url' => 'paket.php', 'permission' => 'paket_read'],
                    ['title' => 'WO - Jenis Motor Mapping', 'url' => 'workorder-motor-mapping.php', 'permission' => 'workorder_motor_mapping_read'],
                    ['title' => 'Jasa - Jenis Motor Mapping', 'url' => 'jasa-motor-mapping.php', 'permission' => 'jasa_motor_mapping_read'],
                    ['title' => 'Item - Jenis Motor Mapping', 'url' => 'item-motor-mapping.php', 'permission' => 'item_motor_mapping_read'],
                    ['title' => 'Master Keluhan', 'url' => 'master-keluhan-crud.php', 'permission' => 'master_keluhan_read'],
                    ['title' => 'Keluhan - WO Mapping', 'url' => 'master-workorder-mapping.php', 'permission' => 'wo_mapping_read'],
                    ['title' => 'Fast Moves Mapping', 'url' => 'master-fastmoves.php', 'permission' => 'fastmoves_read'],
                    ['title' => 'Master Temuan', 'url' => 'master-temuan.php', 'permission' => 'master_temuan_read'],
                    ['title' => 'Temuan - Part Mapping', 'url' => 'master-temuan-mapping.php', 'permission' => 'master_temuan_mapping_read'],
                    ['title' => 'Temuan - Jasa Mapping', 'url' => 'master-temuan-mapping-jasa.php', 'permission' => 'master_temuan_mapping_jasa_read'],
                    ['title' => 'Master Barang Custom', 'url' => 'master-barang-custom.php', 'permission' => 'master_barang_custom_read'],
                    ['title' => 'Harga Jual Plus Jasa', 'url' => 'hjual_jasa.php', 'permission' => 'hjual_jasa_read'],
                ]
            ],
            [
                'title' => 'Pelanggan',
                'icon' => 'fa-caret-right',
                'permission' => 'pelanggan_read',
                'submenu' => [
                    ['title' => 'Master Pelanggan', 'url' => 'pelanggan.php', 'permission' => 'pelanggan_master_read'],
                    ['title' => 'Kategori Pelanggan', 'url' => 'pelanggan_kategori.php', 'permission' => 'pelanggan_kategori_read'],
                    ['title' => 'Loyalty Member Program', 'url' => 'member-loyalty-program.php', 'permission' => 'member_loyalty_read'],
                    ['title' => 'Statistik Pelanggan', 'url' => 'statistik-pelanggan.php', 'permission' => 'statistik_pelanggan_read'],
                ]
            ],
            [
                'title' => 'Supplier',
                'url' => 'supplier.php',
                'icon' => 'fa-caret-right',
                'permission' => 'supplier_read'
            ],
            [
                'title' => 'Cabang',
                'icon' => 'fa-caret-right',
                'permission' => 'cabang_read',
                'submenu' => [
                    ['title' => 'Master Cabang', 'url' => 'cabang.php', 'permission' => 'cabang_master_read'],
                    ['title' => 'Tipe Cabang', 'url' => 'cabang_tipe.php', 'permission' => 'cabang_tipe_read'],
                    ['title' => 'Setting Harga Antar Cabang', 'url' => 'setting_antarcabang.php', 'permission' => 'setting_antarcabang_read'],
                ]
            ],
            [
                'title' => 'Mekanik',
                'icon' => 'fa-caret-right',
                'permission' => 'mekanik_read',
                'submenu' => [
                    ['title' => 'Master Mekanik', 'url' => 'mekanik.php', 'permission' => 'mekanik_master_read'],
                    ['title' => 'Level Mekanik', 'url' => 'mekanik_level.php', 'permission' => 'mekanik_level_read'],
                    ['title' => 'Master Kepala Mekanik', 'url' => 'master_kepala_mekanik.php', 'permission' => 'kepala_mekanik_read'],
                    ['title' => 'Tarif Jemput Antar', 'url' => 'master-tarif-jemput.php', 'permission' => 'tarif_jemput_read'],
                ]
            ],
            [
                'title' => 'Sales',
                'url' => 'sales.php',
                'icon' => 'fa-caret-right',
                'permission' => 'sales_read'
            ],
            [
                'title' => 'Kendaraan',
                'icon' => 'fa-caret-right',
                'permission' => 'kendaraan_read',
                'submenu' => [
                    ['title' => 'Tipe Motor', 'url' => 'motor_tipe.php', 'permission' => 'motor_tipe_read'],
                    ['title' => 'Pabrik Motor', 'url' => 'motor_pabrik.php', 'permission' => 'motor_pabrik_read'],
                    ['title' => 'Kategori Motor', 'url' => 'motor_kategori.php', 'permission' => 'motor_kategori_read'],
                    ['title' => 'Kendaraan', 'url' => 'kendaraan.php', 'permission' => 'kendaraan_master_read'],
                    ['title' => 'Warna', 'url' => 'motor_warna.php', 'permission' => 'motor_warna_read'],
                ]
            ],
            [
                'title' => 'Wilayah',
                'icon' => 'fa-caret-right',
                'permission' => 'wilayah_read',
                'submenu' => [
                    ['title' => 'Propinsi', 'url' => 'prop.php', 'permission' => 'prop_read'],
                    ['title' => 'Kota/Kabupaten', 'url' => 'kab.php', 'permission' => 'kab_read'],
                    ['title' => 'Kecamatan', 'url' => 'kec.php', 'permission' => 'kec_read'],
                    ['title' => 'Desa/Kelurahan', 'url' => 'desa.php', 'permission' => 'desa_read'],
                ]
            ],
            [
                'title' => 'Akun Sumber Kas',
                'url' => 'akun_kas.php',
                'icon' => 'fa-caret-right',
                'permission' => 'akun_kas_read'
            ],
            [
                'title' => 'Akun Biaya',
                'url' => 'akun_biaya.php',
                'icon' => 'fa-caret-right',
                'permission' => 'akun_biaya_read'
            ],
            [
                'title' => 'Data User',
                'url' => 'user.php',
                'icon' => 'fa-caret-right',
                'permission' => 'user_read'
            ],
            [
                'title' => 'Master Posisi',
                'url' => 'master-posisi.php',
                'icon' => 'fa-caret-right',
                'permission' => 'master_posisi_read'
            ],
            [
                'title' => 'Master Karyawan',
                'url' => 'master_karyawan.php',
                'icon' => 'fa-caret-right',
                'permission' => 'master_karyawan_read'
            ],
            [
                'title' => 'Nominal Rupiah',
                'url' => 'keping.php',
                'icon' => 'fa-caret-right',
                'permission' => 'keping_read'
            ],
            [
                'title' => 'Access Sync',
                'url' => 'access-sync.php',
                'icon' => 'fa-caret-right',
                'permission' => 'master_read'
            ],
            [
                'title' => 'Laporan Sync Access',
                'url' => 'access-sync-report.php',
                'icon' => 'fa-caret-right',
                'permission' => 'master_read'
            ],
            [
                'title' => 'Monitor Sync Otomatis',
                'url' => 'access-sync-monitor.php',
                'icon' => 'fa-caret-right',
                'permission' => 'master_read'
            ],
        ]
    ],
    [
        'title' => 'Pembelian',
        'icon' => 'fa-list',
        'permission' => 'pembelian_menu_read',
        'submenu' => [
            ['title' => 'Dashboard MIN/MAX', 'url' => 'procurement_dashboard.php', 'icon' => 'fa-dashboard', 'permission' => 'procurement_dashboard_read'],
            ['title' => 'Rencana Order', 'url' => 'rencana_order.php', 'icon' => 'fa-calendar-check-o', 'permission' => 'rencana_order_read'],
            ['title' => 'Purchase Request (PR)', 'url' => 'pr_add.php', 'permission' => 'pr_read'],
            [
                'title' => 'Pesanan Pembelian (PO)',
                'icon' => 'fa-caret-right',
                'permission' => 'pesanan_pembelian_read',
                'submenu' => [
                    ['title' => 'Daftar PO', 'url' => 'pesanan_pembelian.php', 'permission' => 'pesanan_pembelian_read'],
                    ['title' => 'Input Manual', 'url' => 'pesanan_pembelian_add.php', 'permission' => 'pesanan_pembelian_add_read'],
                    ['title' => 'Upload Excel', 'url' => 'pesanan_pembelian_upload.php', 'permission' => 'pesanan_pembelian_upload_read'],
                ]
            ],
            ['title' => 'Delivery Order (DO)', 'url' => 'do_from_po.php', 'permission' => 'do_read'],
            ['title' => 'Daftar DO', 'url' => 'do_list.php', 'permission' => 'do_read'],
            [
                'title' => 'Pembelian (Invoice)',
                'icon' => 'fa-caret-right',
                'permission' => 'pembelian_read',
                'submenu' => [
                    ['title' => 'Daftar Pembelian', 'url' => 'pembelian.php', 'permission' => 'pembelian_read'],
                    ['title' => 'Dari PO', 'url' => 'pembelian_dari_po.php', 'permission' => 'pembelian_dari_po_read'],
                    ['title' => 'Input Manual', 'url' => 'pembelian_add.php', 'permission' => 'pembelian_add_read'],
                ]
            ],
            ['title' => 'Retur Pembelian', 'url' => 'retur_pembelian.php', 'icon' => 'fa-undo', 'permission' => 'pembelian_read'],
            ['title' => 'Pembayaran Hutang', 'url' => 'pmby_hutang.php', 'permission' => 'pmby_hutang_read'],
        ]
    ],
    [
        'title' => 'Penjualan',
        'icon' => 'fa-list',
        'permission' => 'penjualan_menu_read',
        'submenu' => [
            [
                'title' => 'Pesanan Penjualan',
                'icon' => 'fa-caret-right',
                'permission' => 'pesanan_penjualan_read',
                'submenu' => [
                    ['title' => 'Daftar Pesanan', 'url' => 'pesanan_penjualan.php', 'permission' => 'pesanan_penjualan_read'],
                    ['title' => 'Input Manual', 'url' => 'pesanan_penjualan_add.php', 'permission' => 'pesanan_penjualan_add_read'],
                ]
            ],
            [
                'title' => 'Penjualan',
                'icon' => 'fa-caret-right',
                'permission' => 'penjualan_read',
                'submenu' => [
                    ['title' => 'Daftar Penjualan', 'url' => 'penjualan.php', 'permission' => 'penjualan_read'],
                    ['title' => 'Dari Pesanan', 'url' => 'penjualan_dari_so.php', 'permission' => 'penjualan_dari_so_read'],
                    ['title' => 'Input Manual', 'url' => 'penjualan_add.php', 'permission' => 'penjualan_add_read'],
                ]
            ],
            ['title' => 'Retur Penjualan', 'url' => 'retur_penjualan.php', 'icon' => 'fa-undo', 'permission' => 'penjualan_read'],
            [
                'title' => 'Piutang',
                'icon' => 'fa-caret-right',
                'permission' => 'pmby_piutang_read',
                'submenu' => [
                    ['title' => 'Pembayaran Piutang', 'url' => 'pmby_piutang.php', 'permission' => 'pmby_piutang_read'],
                    ['title' => 'Input Pembayaran', 'url' => 'pmby_piutang_add.php', 'permission' => 'pmby_piutang_add_read'],
                ]
            ],
        ]
    ],
    [
        'title' => 'Antar Cabang',
        'icon' => 'fa-list',
        'permission' => 'antar_cabang_read',
        'submenu' => [
            [
                'title' => 'Cabang Sendiri (Internal)',
                'icon' => 'fa-caret-right',
                'permission' => 'antar_cabang_pesan_read',
                'submenu' => [
                    ['title' => 'Buat Pesanan', 'url' => 'pesanan_penjualan_cab_add.php', 'permission' => 'antar_cabang_pesan_read'],
                    ['title' => 'Tarik Data (Kirim)', 'url' => 'penjualan_cab_add.php', 'permission' => 'antar_cabang_tarik_read'],
                    ['title' => 'Penerimaan', 'url' => 'penerimaan_antarcab.php', 'permission' => 'antar_cabang_terima_read'],
                ]
            ],
            [
                'title' => 'Cabang Mitra (Eksternal)',
                'icon' => 'fa-caret-right',
                'permission' => 'antar_cabang_mitra_read',
                'submenu' => [
                    ['title' => 'Upload Excel', 'url' => 'penjualan_antarcab_upload.php', 'permission' => 'antar_cabang_mitra_upload_read'],
                    ['title' => 'Input Manual', 'url' => 'penjualan_mitra_add.php', 'permission' => 'antar_cabang_mitra_add_read'],
                    ['title' => 'Penerimaan Mitra', 'url' => 'penerimaan_mitra.php', 'permission' => 'antar_cabang_mitra_terima_read'],
                ]
            ],
            ['title' => 'Daftar Transaksi', 'url' => 'antarcab_list.php', 'permission' => 'antar_cabang_list_read'],
            [
                'title' => 'Pengadaan Barang',
                'icon' => 'fa-caret-right',
                'permission' => 'antar_cabang_read',
                'submenu' => [
                    ['title' => 'Daftar Permintaan', 'url' => 'pengadaan_antarcab.php', 'permission' => 'antar_cabang_read'],
                    ['title' => 'Buat Permintaan', 'url' => 'pengadaan_antarcab_add.php', 'permission' => 'antar_cabang_pesan_read'],
                ]
            ],
        ]
    ],
    [
        'title' => 'Servis',
        'icon' => 'fa-list',
        'permission' => 'servis_menu_read',
        'submenu' => [
            [
                'title' => 'Servis Reguler',
                'icon' => 'fa-caret-right',
                'permission' => 'servis_reguler_read',
                'submenu' => [
                    ['title' => 'Input Kepala Mekanik Harian', 'url' => 'input_kepala_mekanik_harian.php', 'permission' => 'input_km_harian_read'],
                    ['title' => 'Kelola Antrian Service', 'url' => 'kelola-antrian.php', 'permission' => 'kelola_antrian_read'],
                    ['title' => 'Input Servis', 'url' => 'servis-carinopol.php', 'permission' => 'input_servis_read'],
                    ['title' => 'Lihat Data', 'url' => 'servis-reguler.php', 'permission' => 'lihat_servis_read'],
                ]
            ],
            [
                'title' => 'Servis Garansi',
                'icon' => 'fa-caret-right',
                'permission' => 'servis_garansi_read',
                'submenu' => [
                    ['title' => 'Input Servis', 'url' => 'servis-carinopol-garansi.php', 'permission' => 'input_servis_garansi_read'],
                    ['title' => 'Lihat Data', 'url' => 'servis-garansi.php', 'permission' => 'lihat_servis_garansi_read'],
                ]
            ],
            [
                'title' => 'Servis Jemput Antar',
                'icon' => 'fa-caret-right',
                'permission' => 'servis_jemput_read',
                'submenu' => [
                    ['title' => 'Jadwal Penjemputan', 'url' => 'servis-reguler-jemput.php', 'permission' => 'jadwal_jemput_read'],
                    ['title' => 'Tracking Keluhan', 'url' => 'report-tracking-keluhan.php', 'permission' => 'tracking_keluhan_read'],
                ]
            ],
        ]
    ],
    [
        'title' => 'Penyesuaian Stok',
        'icon' => 'fa-list',
        'permission' => 'stok_menu_read',
        'submenu' => [
            [
                'title' => 'Manual',
                'icon' => 'fa-caret-right',
                'permission' => 'stok_manual_read',
                'submenu' => [
                    ['title' => 'Item Masuk', 'url' => 'penyesuaian-stok-masuk-manual.php', 'permission' => 'stok_masuk_manual_read'],
                    ['title' => 'Item Keluar', 'url' => 'penyesuaian-stok-keluar-manual.php', 'permission' => 'stok_keluar_manual_read'],
                ]
            ],
            ['title' => 'Otomatis', 'url' => 'penyesuaian-stok-otomatis.php', 'permission' => 'stok_otomatis_read'],
            ['title' => 'Lihat Stok Akhir', 'url' => 'stok-akhir.php', 'permission' => 'stok_akhir_read'],
            ['title' => 'Kartu Stok', 'url' => 'kartu_stok.php', 'icon' => 'fa-book', 'permission' => 'stok_akhir_read'],
        ]
    ],
    [
        'title' => 'Laporan',
        'icon' => 'fa-print',
        'permission' => 'laporan_menu_read',
        'submenu' => [
            ['title' => 'Pesanan Pembelian', 'url' => 'lap_pesanan_pembelian.php', 'permission' => 'lap_pesanan_pembelian_read'],
            ['title' => 'Pembelian', 'url' => 'lap_pembelian.php', 'permission' => 'lap_pembelian_read'],
            ['title' => 'Retur Pembelian', 'url' => 'lap_retur_pembelian.php', 'permission' => 'pembelian_read'],
            [
                'title' => 'Hutang',
                'icon' => 'fa-caret-right',
                'permission' => 'lap_pmby_hutang_read',
                'submenu' => [
                    ['title' => 'Pembayaran Hutang', 'url' => 'lap_pmby_hutang.php', 'permission' => 'lap_pmby_hutang_read'],
                    ['title' => 'Hutang per Supplier', 'url' => 'laporan_hutang_summary.php', 'permission' => 'lap_hutang_summary_read'],
                    ['title' => 'Detail Hutang', 'url' => 'laporan_hutang_detail.php', 'permission' => 'lap_hutang_detail_read'],
                ]
            ],
            ['title' => 'Pesanan Penjualan', 'url' => 'lap_pesanan_penjualan.php', 'permission' => 'lap_pesanan_penjualan_read'],
            ['title' => 'Penjualan', 'url' => 'lap_penjualan.php', 'permission' => 'lap_penjualan_read'],
            ['title' => 'Profit Penjualan', 'url' => 'lap_profit_penjualan.php', 'permission' => 'lap_penjualan_read'],
            ['title' => 'Retur Penjualan', 'url' => 'lap_retur_penjualan.php', 'permission' => 'penjualan_read'],
            [
                'title' => 'Piutang',
                'icon' => 'fa-caret-right',
                'permission' => 'lap_pmby_piutang_read',
                'submenu' => [
                    ['title' => 'Pembayaran Piutang', 'url' => 'lap_pmby_piutang.php', 'permission' => 'lap_pmby_piutang_read'],
                    ['title' => 'Piutang per Pelanggan', 'url' => 'laporan_piutang_summary.php', 'permission' => 'lap_piutang_summary_read'],
                    ['title' => 'Detail Piutang', 'url' => 'laporan_piutang_detail.php', 'permission' => 'lap_piutang_detail_read'],
                ]
            ],
            [
                'title' => 'Antar Cabang',
                'icon' => 'fa-caret-right',
                'permission' => 'lap_antarcab_read',
                'submenu' => [
                    ['title' => 'Pengiriman', 'url' => 'lap_antarcab_kirim.php', 'permission' => 'lap_antarcab_kirim_read'],
                    ['title' => 'Penerimaan', 'url' => 'lap_antarcab_terima.php', 'permission' => 'lap_antarcab_terima_read'],
                ]
            ],
            ['title' => 'Service', 'url' => 'lap_servis.php', 'permission' => 'lap_servis_read'],
            ['title' => 'Rekap Kunjungan Pelanggan', 'url' => 'lap_rekap_kunjungan.php', 'icon' => 'fa-users', 'permission' => 'lap_servis_read'],
            ['title' => 'Konsolidasi Access', 'url' => 'access-sync-report.php', 'permission' => 'laporan_menu_read'],
            ['title' => 'Laporan Cancel Service', 'url' => 'lap_cancel_servis.php', 'permission' => 'lap_cancel_servis_read'],
            ['title' => 'Kas Masuk', 'url' => 'lap_kas_masuk.php', 'permission' => 'lap_kas_masuk_read'],
            ['title' => 'Pengeluaran Kas', 'url' => 'lap_kas_keluar.php', 'permission' => 'lap_kas_keluar_read'],
            ['title' => 'Stok Masuk (Manual)', 'url' => 'lap_stok_masuk.php', 'permission' => 'lap_stok_masuk_read'],
            ['title' => 'Stok Keluar (Manual)', 'url' => 'lap_stok_keluar.php', 'permission' => 'lap_stok_keluar_read'],
        ]
    ],
];
