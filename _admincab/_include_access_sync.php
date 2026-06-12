<?php

function accessSyncCabangReferenceMap() {
    return [
        'PESALAKAN' => [
            'canonical_code' => 'PESALAKAN',
            'reference_code' => '201601001',
            'name' => 'FIT MOTOR ADIWERNA',
            'aliases' => ['PESALAKAN', 'ADIWERNA', 'FIT MOTOR ADIWERNA', '201601001', 'PES']
        ],
        'PACUL' => [
            'canonical_code' => 'PACUL',
            'reference_code' => '201809001',
            'name' => 'FIT MOTOR PACUL',
            'aliases' => ['PACUL', 'FIT MOTOR PACUL', '201809001']
        ],
        'CIKDITIRO' => [
            'canonical_code' => 'CIKDITIRO',
            'reference_code' => '202201001',
            'name' => 'FIT MOTOR CIKDITIRO',
            'aliases' => ['CIKDITIRO', 'CIK DITIRO', 'FIT MOTOR CIKDITIRO', '202201001']
        ],
        'TRAYEMAN' => [
            'canonical_code' => 'TRAYEMAN',
            'reference_code' => '202505001',
            'name' => 'FIT MOTOR TRAYEMAN',
            'aliases' => ['TRAYEMAN', 'FIT MOTOR TRAYEMAN', '202505001']
        ],
        'PST' => [
            'canonical_code' => 'PST',
            'reference_code' => '202601001',
            'name' => 'FIT MOTOR PUSAT',
            'aliases' => ['PST', 'PUSAT', 'FIT MOTOR PUSAT', '202601001', 'CAB001', 'KODE_CABANG_SESI_AND']
        ],
    ];
}

function accessSyncNormalizeCabangCode($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $normalizedValue = strtoupper(preg_replace('/\s+/', ' ', $value));
    foreach (accessSyncCabangReferenceMap() as $branch) {
        foreach ($branch['aliases'] as $alias) {
            if ($normalizedValue === strtoupper(trim((string) $alias))) {
                return $branch['canonical_code'];
            }
        }
    }

    return strtoupper($value);
}

function accessSyncDatasetConfigs() {
    return [
        'pembelian' => [
            'label' => 'Pembelian',
            'table' => 'stg_access_gabung_pembelian',
            'required' => ['no_transaksi'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_transaksi', 'no_baris', 'status',
                'cara_bayar', 'tanggal', 'no_order', 'tanggal_order', 'no_supplier', 'note',
                'total_qty_order', 'total_qty', 'total_beli', 'diskon', 'total_diskon', 'pajak',
                'total_pajak', 'total_akhir', 'total_retur', 'pembayaran', 'tanggal_jt',
                'tanggal_lunas', 'jumlah_bayar', 'user_input', 'id_tabel', 'no_item', 'qty_order',
                'quantity', 'qty_retur', 'harga_pokok', 'potongan', 'harga_sp', 'total',
                'sts_order', 'id_inv'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_transaksi' => ['notransaksi', 'no_transaksi', 'h_notransaksi'],
                'no_baris' => ['nobaris', 'no_baris', 'baris'],
                'status' => ['status'],
                'cara_bayar' => ['carabayar', 'cara_bayar'],
                'tanggal' => ['tanggal'],
                'no_order' => ['noorder', 'no_order'],
                'tanggal_order' => ['tanggalorder', 'tanggal_order'],
                'no_supplier' => ['nosupplier', 'no_supplier'],
                'note' => ['note', 'keterangan'],
                'total_qty_order' => ['totalqtyorder', 'total_qty_order'],
                'total_qty' => ['totalqty', 'total_qty'],
                'total_beli' => ['totalbeli', 'total_beli'],
                'diskon' => ['diskon'],
                'total_diskon' => ['totaldiskon', 'total_diskon'],
                'pajak' => ['pajak'],
                'total_pajak' => ['totalpajak', 'total_pajak'],
                'total_akhir' => ['totalakhir', 'total_akhir'],
                'total_retur' => ['totalretur', 'total_retur'],
                'pembayaran' => ['pembayaran'],
                'tanggal_jt' => ['tanggaljt', 'tanggal_jt'],
                'tanggal_lunas' => ['tanggallunas', 'tanggal_lunas'],
                'jumlah_bayar' => ['jumlahbayar', 'jumlah_bayar'],
                'user_input' => ['user', 'user_input'],
                'id_tabel' => ['idtabel', 'id_tabel'],
                'no_item' => ['noitem', 'no_item', 'd_noitem'],
                'qty_order' => ['qtyorder', 'qty_order'],
                'quantity' => ['quantity', 'qty'],
                'qty_retur' => ['qtyretur', 'qty_retur'],
                'harga_pokok' => ['hargapokok', 'harga_pokok'],
                'potongan' => ['potongan'],
                'harga_sp' => ['hargasp', 'harga_sp'],
                'total' => ['total'],
                'sts_order' => ['stsorder', 'sts_order'],
                'id_inv' => ['idinv', 'id_inv']
            ]
        ],
        'penjualan' => [
            'label' => 'Penjualan',
            'table' => 'stg_access_gabung_penjualan',
            'required' => ['no_transaksi'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_transaksi', 'no_baris', 'status',
                'cara_bayar', 'tanggal', 'no_order', 'tanggal_order', 'no_sales', 'no_pelanggan',
                'note', 'total_qty_order', 'total_qty', 'total_jual', 'diskon', 'total_diskon',
                'pajak', 'total_pajak', 'total_akhir', 'total_retur', 'pembayaran', 'tanggal_jt',
                'tanggal_lunas', 'jumlah_bayar', 'user_input', 'id_tabel', 'no_item', 'qty_order',
                'quantity', 'qty_retur', 'harga_jual', 'potongan', 'harga_sp', 'harga_pokok',
                'total', 'sts_order'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_transaksi' => ['notransaksi', 'no_transaksi', 'h_notransaksi'],
                'no_baris' => ['nobaris', 'no_baris', 'baris'],
                'status' => ['status'],
                'cara_bayar' => ['carabayar', 'cara_bayar'],
                'tanggal' => ['tanggal'],
                'no_order' => ['noorder', 'no_order'],
                'tanggal_order' => ['tanggalorder', 'tanggal_order'],
                'no_sales' => ['nosales', 'no_sales'],
                'no_pelanggan' => ['nopelanggan', 'no_pelanggan'],
                'note' => ['note', 'keterangan'],
                'total_qty_order' => ['totalqtyorder', 'total_qty_order'],
                'total_qty' => ['totalqty', 'total_qty'],
                'total_jual' => ['totaljual', 'total_jual'],
                'diskon' => ['diskon'],
                'total_diskon' => ['totaldiskon', 'total_diskon'],
                'pajak' => ['pajak'],
                'total_pajak' => ['totalpajak', 'total_pajak'],
                'total_akhir' => ['totalakhir', 'total_akhir'],
                'total_retur' => ['totalretur', 'total_retur'],
                'pembayaran' => ['pembayaran'],
                'tanggal_jt' => ['tanggaljt', 'tanggal_jt'],
                'tanggal_lunas' => ['tanggallunas', 'tanggal_lunas'],
                'jumlah_bayar' => ['jumlahbayar', 'jumlah_bayar'],
                'user_input' => ['user', 'user_input'],
                'id_tabel' => ['idtabel', 'id_tabel'],
                'no_item' => ['noitem', 'no_item', 'd_noitem'],
                'qty_order' => ['qtyorder', 'qty_order'],
                'quantity' => ['quantity', 'qty'],
                'qty_retur' => ['qtyretur', 'qty_retur'],
                'harga_jual' => ['hargajual', 'harga_jual'],
                'potongan' => ['potongan'],
                'harga_sp' => ['hargasp', 'harga_sp'],
                'harga_pokok' => ['hargapokok', 'harga_pokok'],
                'total' => ['total'],
                'sts_order' => ['stsorder', 'sts_order']
            ]
        ],
        'service' => [
            'label' => 'Service',
            'table' => 'stg_access_gabung_service',
            'required' => ['no_service'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_service', 'tanggal', 'jam',
                'no_pelanggan', 'no_polisi', 'mekanik1', 'mekanik2', 'mekanik3', 'mekanik4',
                'biayam1', 'biayam2', 'biayam3', 'biayam4', 'km_sekarang', 'km_berikut',
                'total_waktu', 'status', 'keterangan', 'subtotal_jasa', 'subtotal_item',
                'subtotal', 'diskon', 'total_diskon', 'pajak', 'total_pajak', 'total_akhir',
                'pembayaran', 'user_input', 'id_tabel', 'advisor'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_service' => ['noservice', 'no_service'],
                'tanggal' => ['tanggal'],
                'jam' => ['jam'],
                'no_pelanggan' => ['nopelanggan', 'no_pelanggan'],
                'no_polisi' => ['nopolisi', 'no_polisi', 'nopol'],
                'mekanik1' => ['mekanik1'],
                'mekanik2' => ['mekanik2'],
                'mekanik3' => ['mekanik3'],
                'mekanik4' => ['mekanik4'],
                'biayam1' => ['biayam1', 'biaya_m1'],
                'biayam2' => ['biayam2', 'biaya_m2'],
                'biayam3' => ['biayam3', 'biaya_m3'],
                'biayam4' => ['biayam4', 'biaya_m4'],
                'km_sekarang' => ['kmsekarang', 'km_sekarang'],
                'km_berikut' => ['kmberikut', 'km_berikut'],
                'total_waktu' => ['totalwaktu', 'total_waktu'],
                'status' => ['status'],
                'keterangan' => ['keterangan', 'note'],
                'subtotal_jasa' => ['subtotaljasa', 'subtotal_jasa'],
                'subtotal_item' => ['subtotalitem', 'subtotal_item'],
                'subtotal' => ['subtotal'],
                'diskon' => ['diskon'],
                'total_diskon' => ['totaldiskon', 'total_diskon'],
                'pajak' => ['pajak'],
                'total_pajak' => ['totalpajak', 'total_pajak'],
                'total_akhir' => ['totalakhir', 'total_akhir'],
                'pembayaran' => ['pembayaran'],
                'user_input' => ['user', 'user_input'],
                'id_tabel' => ['idtabel', 'id_tabel'],
                'advisor' => ['advisor', 'service_advisor']
            ]
        ],
        'service_barang' => [
            'label' => 'Service Histori Barang',
            'table' => 'stg_access_gabung_service_barang',
            'required' => ['no_service', 'no_item'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_service', 'tanggal', 'no_pelanggan',
                'no_polisi', 'mekanik1', 'mekanik2', 'mekanik3', 'mekanik4', 'biayam1', 'biayam2',
                'biayam3', 'biayam4', 'km_sekarang', 'km_berikut', 'total_waktu', 'status',
                'keterangan', 'subtotal_jasa', 'subtotal_item', 'subtotal', 'diskon', 'total_diskon',
                'pajak', 'total_pajak', 'total_akhir', 'pembayaran', 'user_input', 'id_tabel',
                'no_baris', 'no_item', 'quantity', 'qty_retur', 'harga', 'potongan', 'total'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_service' => ['noservice', 'no_service'],
                'tanggal' => ['tanggal'],
                'no_pelanggan' => ['nopelanggan', 'no_pelanggan'],
                'no_polisi' => ['nopolisi', 'no_polisi', 'nopol'],
                'mekanik1' => ['mekanik1'],
                'mekanik2' => ['mekanik2'],
                'mekanik3' => ['mekanik3'],
                'mekanik4' => ['mekanik4'],
                'biayam1' => ['biayam1', 'biaya_m1'],
                'biayam2' => ['biayam2', 'biaya_m2'],
                'biayam3' => ['biayam3', 'biaya_m3'],
                'biayam4' => ['biayam4', 'biaya_m4'],
                'km_sekarang' => ['kmsekarang', 'km_sekarang'],
                'km_berikut' => ['kmberikut', 'km_berikut'],
                'total_waktu' => ['totalwaktu', 'total_waktu'],
                'status' => ['status'],
                'keterangan' => ['keterangan', 'note'],
                'subtotal_jasa' => ['subtotaljasa', 'subtotal_jasa'],
                'subtotal_item' => ['subtotalitem', 'subtotal_item'],
                'subtotal' => ['subtotal'],
                'diskon' => ['diskon'],
                'total_diskon' => ['totaldiskon', 'total_diskon'],
                'pajak' => ['pajak'],
                'total_pajak' => ['totalpajak', 'total_pajak'],
                'total_akhir' => ['totalakhir', 'total_akhir'],
                'pembayaran' => ['pembayaran'],
                'user_input' => ['user', 'user_input'],
                'id_tabel' => ['idtabel', 'id_tabel'],
                'no_baris' => ['nobaris', 'no_baris'],
                'no_item' => ['noitem', 'no_item'],
                'quantity' => ['quantity', 'qty'],
                'qty_retur' => ['qtyretur', 'qty_retur'],
                'harga' => ['harga', 'harga_jual'],
                'potongan' => ['potongan'],
                'total' => ['total']
            ]
        ],
        'service_jasa' => [
            'label' => 'Service Histori Jasa',
            'table' => 'stg_access_gabung_service_jasa',
            'required' => ['no_service', 'no_item'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_service', 'tanggal', 'no_pelanggan',
                'no_polisi', 'mekanik1', 'mekanik2', 'mekanik3', 'mekanik4', 'biayam1', 'biayam2',
                'biayam3', 'biayam4', 'km_sekarang', 'km_berikut', 'total_waktu', 'status',
                'keterangan', 'subtotal_jasa', 'subtotal_item', 'subtotal', 'diskon', 'total_diskon',
                'pajak', 'total_pajak', 'total_akhir', 'pembayaran', 'user_input', 'id_tabel',
                'no_baris', 'no_item', 'waktu', 'harga', 'potongan', 'total'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_service' => ['noservice', 'no_service'],
                'tanggal' => ['tanggal'],
                'no_pelanggan' => ['nopelanggan', 'no_pelanggan'],
                'no_polisi' => ['nopolisi', 'no_polisi', 'nopol'],
                'mekanik1' => ['mekanik1'],
                'mekanik2' => ['mekanik2'],
                'mekanik3' => ['mekanik3'],
                'mekanik4' => ['mekanik4'],
                'biayam1' => ['biayam1', 'biaya_m1'],
                'biayam2' => ['biayam2', 'biaya_m2'],
                'biayam3' => ['biayam3', 'biaya_m3'],
                'biayam4' => ['biayam4', 'biaya_m4'],
                'km_sekarang' => ['kmsekarang', 'km_sekarang'],
                'km_berikut' => ['kmberikut', 'km_berikut'],
                'total_waktu' => ['totalwaktu', 'total_waktu'],
                'status' => ['status'],
                'keterangan' => ['keterangan', 'note'],
                'subtotal_jasa' => ['subtotaljasa', 'subtotal_jasa'],
                'subtotal_item' => ['subtotalitem', 'subtotal_item'],
                'subtotal' => ['subtotal'],
                'diskon' => ['diskon'],
                'total_diskon' => ['totaldiskon', 'total_diskon'],
                'pajak' => ['pajak'],
                'total_pajak' => ['totalpajak', 'total_pajak'],
                'total_akhir' => ['totalakhir', 'total_akhir'],
                'pembayaran' => ['pembayaran'],
                'user_input' => ['user', 'user_input'],
                'id_tabel' => ['idtabel', 'id_tabel'],
                'no_baris' => ['nobaris', 'no_baris'],
                'no_item' => ['noitem', 'no_item'],
                'waktu' => ['waktu'],
                'harga' => ['harga'],
                'potongan' => ['potongan'],
                'total' => ['total']
            ]
        ],
        'service_advisor' => [
            'label' => 'Service Advisor',
            'table' => 'stg_access_gabung_service_advisor',
            'required' => ['no_service', 'advisor'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_service', 'advisor'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_service' => ['noservice', 'no_service'],
                'advisor' => ['advisor', 'service_advisor']
            ]
        ],
        'item' => [
            'label' => 'Item Per Bengkel',
            'table' => 'stg_access_gabung_item',
            'required' => ['no_item'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_item', 'kode_barcode', 'nama_item',
                'jenis', 'satuan', 'harga_pokok', 'harga_jual', 'harga_jual2', 'harga_jual3',
                'quantity', 'stok_min', 'status_item', 'supplier1', 'supplier2', 'supplier3',
                'status_produk', 'rak_barang', 'jasa_waktu'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_item' => ['noitem', 'no_item', 'kode_item'],
                'kode_barcode' => ['kodebarcode', 'kode_barcode', 'barcode'],
                'nama_item' => ['namaitem', 'nama_item', 'item'],
                'jenis' => ['jenis', 'kategori'],
                'satuan' => ['satuan'],
                'harga_pokok' => ['hargapokok', 'harga_pokok'],
                'harga_jual' => ['hargajual', 'harga_jual'],
                'harga_jual2' => ['hargajual2', 'harga_jual2'],
                'harga_jual3' => ['hargajual3', 'harga_jual3'],
                'quantity' => ['quantity', 'qty', 'stok'],
                'stok_min' => ['stokmin', 'stok_min'],
                'status_item' => ['statusitem', 'status_item'],
                'supplier1' => ['supplier1'],
                'supplier2' => ['supplier2'],
                'supplier3' => ['supplier3'],
                'status_produk' => ['statusproduk', 'status_produk'],
                'rak_barang' => ['rakbarang', 'rak_barang'],
                'jasa_waktu' => ['jasawaktu', 'jasa_waktu']
            ]
        ],
        'supplier' => [
            'label' => 'Supplier',
            'table' => 'stg_access_gabung_supplier',
            'required' => ['no_supplier'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_supplier', 'nama_supplier', 'alamat',
                'kota', 'propinsi', 'kode_post', 'negara', 'telephone', 'fax', 'nama_bank',
                'no_account', 'atas_nama', 'kontak_person', 'email', 'note', 'saldo_awal',
                'per_tanggal', 'jml_bayar', 'sisa'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_supplier' => ['nosupplier', 'no_supplier'],
                'nama_supplier' => ['namasupplier', 'nama_supplier'],
                'alamat' => ['alamat'],
                'kota' => ['kota'],
                'propinsi' => ['propinsi', 'provinsi'],
                'kode_post' => ['kodepost', 'kode_post', 'kodepos'],
                'negara' => ['negara'],
                'telephone' => ['telephone', 'telp', 'telepon'],
                'fax' => ['fax'],
                'nama_bank' => ['namabank', 'nama_bank'],
                'no_account' => ['noaccount', 'no_account', 'rekening'],
                'atas_nama' => ['atasnama', 'atas_nama'],
                'kontak_person' => ['kontakperson', 'kontak_person', 'cp'],
                'email' => ['email'],
                'note' => ['note', 'keterangan'],
                'saldo_awal' => ['saldoawal', 'saldo_awal'],
                'per_tanggal' => ['pertanggal', 'per_tanggal'],
                'jml_bayar' => ['jmlbayar', 'jml_bayar'],
                'sisa' => ['sisa']
            ]
        ],
        'mekanik' => [
            'label' => 'Mekanik',
            'table' => 'stg_access_gabung_mekanik',
            'required' => ['no_mekanik'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_mekanik', 'nama', 'alamat', 'kota',
                'provinsi', 'no_telepon', 'note', 'keahlian'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_mekanik' => ['nomekanik', 'no_mekanik', 'kode_mekanik'],
                'nama' => ['nama', 'namamekanik'],
                'alamat' => ['alamat'],
                'kota' => ['kota'],
                'provinsi' => ['provinsi', 'propinsi'],
                'no_telepon' => ['notelepon', 'no_telepon', 'telephone', 'telepon'],
                'note' => ['note', 'keterangan'],
                'keahlian' => ['keahlian', 'skill']
            ]
        ],
        'kendaraan_pelanggan' => [
            'label' => 'Pelanggan & Kendaraan',
            'table' => 'stg_access_gabung_kendaraan_pelanggan',
            'required' => ['no_polisi'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_polisi', 'nama_pelanggan', 'merek',
                'tipe', 'jenis', 'warna', 'tahun_buat', 'telephone', 'grup', 'alamat', 'note',
                'kategori', 'kota', 'kontak_person'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_polisi' => ['nopolisi', 'no_polisi', 'nopol'],
                'nama_pelanggan' => ['namapelanggan', 'nama_pelanggan', 'pelanggan'],
                'merek' => ['merek', 'pabrik'],
                'tipe' => ['tipe'],
                'jenis' => ['jenis'],
                'warna' => ['warna'],
                'tahun_buat' => ['tahunbuat', 'tahun_buat', 'tahun'],
                'telephone' => ['telephone', 'telepon', 'telp'],
                'grup' => ['grup', 'group'],
                'alamat' => ['alamat'],
                'note' => ['note', 'keterangan'],
                'kategori' => ['kategori'],
                'kota' => ['kota'],
                'kontak_person' => ['kontakperson', 'kontak_person', 'cp']
            ]
        ],
        'pelanggan' => [
            'label' => 'Pelanggan',
            'table' => 'stg_access_gabung_pelanggan',
            'required' => ['no_pelanggan'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_pelanggan', 'nama_pelanggan',
                'alamat', 'kota', 'propinsi', 'kode_post', 'negara', 'telephone', 'fax',
                'kontak_person', 'note', 'potongan', 'tipe_pot', 'lavel_harga', 'kgrup'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_pelanggan' => ['nopelanggan', 'no_pelanggan'],
                'nama_pelanggan' => ['namapelanggan', 'nama_pelanggan'],
                'alamat' => ['alamat'],
                'kota' => ['kota'],
                'propinsi' => ['propinsi', 'provinsi'],
                'kode_post' => ['kodepost', 'kode_post', 'kodepos'],
                'negara' => ['negara'],
                'telephone' => ['telephone', 'telepon', 'telp'],
                'fax' => ['fax'],
                'kontak_person' => ['kontakperson', 'kontak_person', 'cp'],
                'note' => ['note', 'keterangan'],
                'potongan' => ['potongan'],
                'tipe_pot' => ['tipepot', 'tipe_pot'],
                'lavel_harga' => ['lavelharga', 'lavel_harga', 'level_harga'],
                'kgrup' => ['kgrup', 'grup_kode']
            ]
        ],
        'member_pelanggan' => [
            'label' => 'Member Pelanggan',
            'table' => 'stg_access_gabung_member_pelanggan',
            'required' => ['no_pelanggan'],
            'fields' => [
                'kd_cabang', 'sts_source', 'tanggal_data', 'no_pelanggan', 'tipe_member', 'no_polisi'
            ],
            'aliases' => [
                'kd_cabang' => ['kd_cabang', 'kode_cabang', 'cabang', 'sts'],
                'sts_source' => ['sts_source', 'source_status', 'status_source'],
                'tanggal_data' => ['tanggal_data', 'sync_date', 'tanggaldata'],
                'no_pelanggan' => ['nopelanggan', 'no_pelanggan'],
                'tipe_member' => ['tipemember', 'tipe_member', 'status_member'],
                'no_polisi' => ['nopolisi', 'no_polisi', 'nopol']
            ]
        ]
    ];
}

function accessSyncNormalizeHeader($header) {
    $header = strtolower(trim((string) $header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header);
    return trim($header, '_');
}

function accessSyncNormalizeValue($field, $value) {
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
    }

    if ($value === '') {
        return null;
    }

    $numericFields = [
        'no_baris', 'total_qty_order', 'total_qty', 'total_beli', 'diskon', 'total_diskon',
        'pajak', 'total_pajak', 'total_akhir', 'total_retur', 'pembayaran', 'jumlah_bayar',
        'qty_order', 'quantity', 'qty_retur', 'harga_pokok', 'potongan', 'harga_sp', 'total',
        'total_jual', 'harga_jual', 'biayam1', 'biayam2', 'biayam3', 'biayam4',
        'km_sekarang', 'km_berikut', 'total_waktu', 'subtotal_jasa', 'subtotal_item', 'subtotal',
        'harga_jual2', 'harga_jual3', 'stok_min', 'jasa_waktu', 'saldo_awal', 'jml_bayar', 'sisa',
        'potongan'
    ];

    $dateFields = ['tanggal_data', 'tanggal', 'tanggal_order', 'tanggal_jt', 'tanggal_lunas', 'per_tanggal'];

    if (in_array($field, $numericFields, true)) {
        $value = str_replace([',', ' '], ['', ''], (string) $value);
        return is_numeric($value) ? $value : null;
    }

    if (in_array($field, $dateFields, true)) {
        $timestamp = strtotime((string) $value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    return is_string($value) ? $value : (string) $value;
}

function accessSyncMapRow($datasetKey, $row, $sourceCabang = '') {
    $configs = accessSyncDatasetConfigs();
    if (!isset($configs[$datasetKey])) {
        return [[], ['Dataset tidak dikenali']];
    }

    $config = $configs[$datasetKey];
    $normalizedSource = [];
    foreach ((array) $row as $header => $value) {
        $normalizedSource[accessSyncNormalizeHeader($header)] = $value;
    }

    $mapped = [];
    foreach ($config['fields'] as $field) {
        $mapped[$field] = null;
        $aliases = isset($config['aliases'][$field]) ? $config['aliases'][$field] : [$field];
        foreach ($aliases as $alias) {
            $aliasKey = accessSyncNormalizeHeader($alias);
            if (array_key_exists($aliasKey, $normalizedSource)) {
                $mapped[$field] = accessSyncNormalizeValue($field, $normalizedSource[$aliasKey]);
                break;
            }
        }
    }

    if (empty($mapped['kd_cabang']) && $sourceCabang !== '') {
        $mapped['kd_cabang'] = strtoupper(trim($sourceCabang));
    }

    $errors = [];
    foreach ($config['required'] as $requiredField) {
        if (empty($mapped[$requiredField])) {
            $errors[] = 'Field wajib kosong: ' . $requiredField;
        }
    }

    if (!empty($mapped['kd_cabang'])) {
        $mapped['kd_cabang'] = accessSyncNormalizeCabangCode($mapped['kd_cabang']);
    } elseif ($sourceCabang !== '') {
        $mapped['kd_cabang'] = accessSyncNormalizeCabangCode($sourceCabang);
    }

    return [$mapped, $errors];
}

function accessSyncCreateRun($koneksi, $sourceName, $sourceFile, $syncMode, $metadata = []) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $fields = ['source_name', 'source_file', 'sync_mode', 'started_at', 'status'];
    $valuesSql = ['?', '?', '?', 'NOW()', "'running'"];
    $types = 'sss';
    $params = [$sourceName, $sourceFile, $syncMode];

    $optionalColumns = [
        'dataset_key' => 'dataset_key',
        'trigger_source' => 'trigger_source',
        'source_cabang' => 'source_cabang',
        'machine_name' => 'machine_name',
        'batch_key' => 'batch_key',
        'request_ip' => 'request_ip',
    ];

    foreach ($optionalColumns as $metaKey => $columnName) {
        if (accessSyncColumnExists($koneksi, 'sync_access_runs', $columnName)) {
            $fields[] = $columnName;
            $valuesSql[] = '?';
            $types .= 's';
            $params[] = isset($metadata[$metaKey]) ? (string) $metadata[$metaKey] : '';
        }
    }

    if (accessSyncColumnExists($koneksi, 'sync_access_runs', 'last_heartbeat_at')) {
        $fields[] = 'last_heartbeat_at';
        $valuesSql[] = 'NOW()';
    }

    $sql = "INSERT INTO sync_access_runs (" . implode(', ', $fields) . ")
            VALUES (" . implode(', ', $valuesSql) . ")";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $runId = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    return $runId;
}

function accessSyncLogError($koneksi, $runId, $datasetName, $rowKey, $errorMessage, $payload) {
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $sql = "INSERT INTO sync_access_row_errors (sync_run_id, dataset_name, row_key, error_message, raw_payload)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'issss', $runId, $datasetName, $rowKey, $errorMessage, $payloadJson);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncDeletePriorStaging($koneksi, $datasetKey, $sourceCabang = '') {
    $configs = accessSyncDatasetConfigs();
    if (!isset($configs[$datasetKey])) {
        return;
    }

    $table = $configs[$datasetKey]['table'];
    if ($sourceCabang !== '') {
        $sql = "DELETE FROM {$table} WHERE kd_cabang = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 's', $sourceCabang);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return;
    }

    mysqli_query($koneksi, "TRUNCATE TABLE {$table}");
}

function accessSyncInsertRow($koneksi, $datasetKey, $runId, $mappedRow, $rawRow) {
    $configs = accessSyncDatasetConfigs();
    $config = $configs[$datasetKey];
    $fields = $config['fields'];

    $insertFields = array_merge(['sync_run_id'], $fields, ['raw_payload']);
    $placeholders = implode(',', array_fill(0, count($insertFields), '?'));
    $sql = "INSERT INTO {$config['table']} (" . implode(',', $insertFields) . ") VALUES ({$placeholders})";
    $stmt = mysqli_prepare($koneksi, $sql);

    $types = 'i';
    $values = [$runId];
    foreach ($fields as $field) {
        $types .= 's';
        $values[] = isset($mappedRow[$field]) ? (string) $mappedRow[$field] : null;
    }
    $types .= 's';
    $values[] = json_encode($rawRow, JSON_UNESCAPED_UNICODE);

    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $error = $ok ? '' : mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    return [$ok, $error];
}

function accessSyncFinishRun($koneksi, $runId, $status, $totalRows, $successRows, $failedRows, $notes = '', $metadata = []) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $assignments = [
        'finished_at = NOW()',
        'status = ?',
        'total_rows = ?',
        'success_rows = ?',
        'failed_rows = ?',
        'notes = ?'
    ];
    $types = 'siiis';
    $params = [$status, (int) $totalRows, (int) $successRows, (int) $failedRows, (string) $notes];

    $optionalColumns = [
        'merge_status' => 'merge_status',
        'merge_processed' => 'merge_processed',
        'merge_inserted' => 'merge_inserted',
        'merge_updated' => 'merge_updated',
        'merge_upserted' => 'merge_upserted',
    ];

    foreach ($optionalColumns as $metaKey => $columnName) {
        if (!array_key_exists($metaKey, $metadata) || !accessSyncColumnExists($koneksi, 'sync_access_runs', $columnName)) {
            continue;
        }
        $assignments[] = $columnName . ' = ?';
        if (in_array($metaKey, ['merge_processed', 'merge_inserted', 'merge_updated', 'merge_upserted'], true)) {
            $types .= 'i';
            $params[] = (int) $metadata[$metaKey];
        } else {
            $types .= 's';
            $params[] = (string) $metadata[$metaKey];
        }
    }

    if (accessSyncColumnExists($koneksi, 'sync_access_runs', 'last_heartbeat_at')) {
        $assignments[] = 'last_heartbeat_at = NOW()';
    }

    $sql = "UPDATE sync_access_runs
            SET " . implode(', ', $assignments) . "
            WHERE id = ?";
    $types .= 'i';
    $params[] = (int) $runId;
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncAllowedExtension($filename) {
    $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
    return in_array($ext, ['csv', 'xls', 'xlsx'], true);
}

function accessSyncExecuteStatementOrThrow($stmt) {
    if (!$stmt) {
        throw new RuntimeException('Statement prepare gagal');
    }
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException(mysqli_stmt_error($stmt));
    }
}

function accessSyncMasterDatasets() {
    return ['item', 'supplier', 'mekanik', 'kendaraan_pelanggan', 'pelanggan', 'member_pelanggan'];
}

function accessSyncIsMasterDataset($datasetKey) {
    return in_array($datasetKey, accessSyncMasterDatasets(), true);
}

function accessSyncTransactionDatasets() {
    return ['pembelian', 'penjualan', 'service', 'service_barang', 'service_jasa', 'service_advisor'];
}

function accessSyncIsTransactionDataset($datasetKey) {
    return in_array($datasetKey, accessSyncTransactionDatasets(), true);
}

function accessSyncNextPelangganGroupCode($koneksi) {
    $result = mysqli_query($koneksi, "SELECT MAX(CAST(kgrup AS UNSIGNED)) AS max_code FROM tblpelanggangrup");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $next = isset($row['max_code']) ? ((int) $row['max_code']) + 1 : 1;
    return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
}

function accessSyncEnsurePelangganGroup($koneksi, $groupName) {
    if (!function_exists('fitmotorMapGrupNameToTier')) {
        require_once __DIR__ . '/_include_kategori_member.php';
    }

    $groupName = trim((string) $groupName);
    if ($groupName === '') {
        return '001';
    }

    if (preg_match('/^\d+$/', $groupName)) {
        return str_pad($groupName, 3, '0', STR_PAD_LEFT);
    }

    $normalized = strtoupper($groupName);
    $candidates = [];

    // Prefer the canonical "MEMBER SILVER/GOLD/PLATINUM" group for abbreviated
    // or fuzzy tier names (e.g. "SIL", "MEMBER SIL", "GOL") so syncs don't keep
    // creating duplicate groups with a different (usually 0%) discount.
    $canonicalByTier = ['Silver' => 'MEMBER SILVER', 'Gold' => 'MEMBER GOLD', 'Platinum' => 'MEMBER PLATINUM'];
    $tier = fitmotorMapGrupNameToTier($normalized);
    if (isset($canonicalByTier[$tier]) && $normalized !== $canonicalByTier[$tier]) {
        $candidates[] = $canonicalByTier[$tier];
    }

    $candidates[] = $normalized;
    if (strpos($normalized, 'MEMBER ') !== 0 && $normalized !== 'BENGKEL') {
        $candidates[] = 'MEMBER ' . $normalized;
    }

    foreach ($candidates as $candidate) {
        $stmt = mysqli_prepare($koneksi, "SELECT kgrup FROM tblpelanggangrup WHERE UPPER(grup) = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $candidate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row && !empty($row['kgrup'])) {
            return $row['kgrup'];
        }
    }

    $newCode = accessSyncNextPelangganGroupCode($koneksi);
    $finalName = end($candidates);
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tblpelanggangrup (kgrup, grup, diskon) VALUES (?, ?, 0)");
    mysqli_stmt_bind_param($stmt, 'ss', $newCode, $finalName);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return $newCode;
}

function accessSyncFindRunRows($koneksi, $table, $runId) {
    $runId = (int) $runId;
    return mysqli_query($koneksi, "SELECT * FROM {$table} WHERE sync_run_id = {$runId} ORDER BY id ASC");
}

function accessSyncUpsertSupplier($koneksi, $row) {
    $defaults = [
        'nama_supplier' => $row['nama_supplier'] ?? '',
        'alamat' => $row['alamat'] ?? '',
        'kota' => $row['kota'] ?? '',
        'propinsi' => $row['propinsi'] ?? '',
        'kode_post' => $row['kode_post'] ?? '',
        'negara' => $row['negara'] ?? 'Indonesia',
        'telephone' => $row['telephone'] ?? '',
        'fax' => $row['fax'] ?? '',
        'nama_bank' => $row['nama_bank'] ?? '',
        'no_account' => $row['no_account'] ?? '',
        'atas_nama' => $row['atas_nama'] ?? '',
        'kontak_person' => $row['kontak_person'] ?? '',
        'email' => $row['email'] ?? '',
        'note' => $row['note'] ?? '',
        'saldo_awal' => $row['saldo_awal'] ?? '0',
        'per_tanggal' => !empty($row['per_tanggal']) ? substr($row['per_tanggal'], 0, 10) : date('Y-m-d'),
        'jml_bayar' => $row['jml_bayar'] ?? '0',
        'sisa' => $row['sisa'] ?? '0',
        'kd_cabang' => $row['kd_cabang'] ?? '',
        'no_supplier' => $row['no_supplier'] ?? ''
    ];

    $existsStmt = mysqli_prepare($koneksi, "SELECT nosupplier FROM tblsupplier WHERE nosupplier = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $defaults['no_supplier']);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tblsupplier SET namasupplier=?, alamat=?, kota=?, propinsi=?, kodepost=?, negara=?, telephone=?, fax=?, namabank=?, noaccount=?, atasnama=?, kontakperson=?, email=?, note=?, saldoawal=?, pertanggal=?, jmlbayar=?, sisa=?, kd_cabang=? WHERE nosupplier=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssssssssssssssssss',
            $defaults['nama_supplier'], $defaults['alamat'], $defaults['kota'], $defaults['propinsi'], $defaults['kode_post'],
            $defaults['negara'], $defaults['telephone'], $defaults['fax'], $defaults['nama_bank'], $defaults['no_account'],
            $defaults['atas_nama'], $defaults['kontak_person'], $defaults['email'], $defaults['note'], $defaults['saldo_awal'],
            $defaults['per_tanggal'], $defaults['jml_bayar'], $defaults['sisa'], $defaults['kd_cabang'], $defaults['no_supplier']
        );
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $tipePemasok = 'perusahaan';
    $lamaHariKirim = '0';
    $jangkaWaktuKredit = '0';
    $sql = "INSERT INTO tblsupplier (nosupplier, namasupplier, tipe_pemasok, alamat, kota, propinsi, kodepost, negara, telephone, fax, namabank, noaccount, atasnama, kontakperson, email, note, saldoawal, pertanggal, jmlbayar, sisa, kd_cabang, lama_hari_kirim, jangka_waktu_kredit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssssss',
        $defaults['no_supplier'], $defaults['nama_supplier'], $tipePemasok, $defaults['alamat'], $defaults['kota'], $defaults['propinsi'],
        $defaults['kode_post'], $defaults['negara'], $defaults['telephone'], $defaults['fax'], $defaults['nama_bank'],
        $defaults['no_account'], $defaults['atas_nama'], $defaults['kontak_person'], $defaults['email'], $defaults['note'],
        $defaults['saldo_awal'], $defaults['per_tanggal'], $defaults['jml_bayar'], $defaults['sisa'], $defaults['kd_cabang'],
        $lamaHariKirim, $jangkaWaktuKredit
    );
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncUpsertMekanik($koneksi, $row) {
    $existsStmt = mysqli_prepare($koneksi, "SELECT kode_karyawan FROM tbuser_karyawan WHERE kode_karyawan = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $row['no_mekanik']);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tbuser_karyawan SET nama_lengkap=?, alamat=?, telp=?, spesialisasi=?, kode_cabang=? WHERE kode_karyawan=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssss', $row['nama'], $row['alamat'], $row['no_telepon'], $row['keahlian'], $row['kd_cabang'], $row['no_mekanik']);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $kodePosisi = 'MK';
    $kodeLevel = 'MK-1';
    $sql = "INSERT INTO tbuser_karyawan (kode_karyawan, nama_lengkap, kode_posisi, kode_level, kode_cabang, telp, alamat, spesialisasi) VALUES (?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssssss', $row['no_mekanik'], $row['nama'], $kodePosisi, $kodeLevel, $row['kd_cabang'], $row['no_telepon'], $row['alamat'], $row['keahlian']);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncUpsertItem($koneksi, $row) {
    $defaults = [
        'no_item' => $row['no_item'] ?? '',
        'kode_barcode' => $row['kode_barcode'] ?? '',
        'nama_item' => $row['nama_item'] ?? '',
        'jenis' => $row['jenis'] ?? '',
        'satuan' => $row['satuan'] ?? 'PCS',
        'harga_pokok' => $row['harga_pokok'] ?? '0',
        'harga_jual' => $row['harga_jual'] ?? '0',
        'harga_jual2' => $row['harga_jual2'] ?? ($row['harga_jual'] ?? '0'),
        'harga_jual3' => $row['harga_jual3'] ?? ($row['harga_jual'] ?? '0'),
        'quantity' => $row['quantity'] ?? '0',
        'stok_min' => $row['stok_min'] ?? '0',
        'status_item' => $row['status_item'] ?? '',
        'supplier1' => $row['supplier1'] ?? '',
        'supplier2' => $row['supplier2'] ?? '',
        'supplier3' => $row['supplier3'] ?? '',
        'status_produk' => !empty($row['status_produk']) ? substr((string) $row['status_produk'], 0, 1) : '1',
        'rak_barang' => !empty($row['rak_barang']) ? substr((string) $row['rak_barang'], 0, 3) : '',
        'jasa_waktu' => $row['jasa_waktu'] ?? '0'
    ];

    $existsStmt = mysqli_prepare($koneksi, "SELECT noitem FROM tblitem WHERE noitem = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $defaults['no_item']);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tblitem SET kodebarcode=?, namaitem=?, jenis=?, satuan=?, hargapokok=?, hargajual=?, hargajual2=?, hargajual3=?, quantity=?, stokmin=?, statusitem=?, supplier=?, supplier2=?, supplier3=?, statusproduk=?, rakbarang=?, jasawaktu=? WHERE noitem=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssssssssssssssss',
            $defaults['kode_barcode'], $defaults['nama_item'], $defaults['jenis'], $defaults['satuan'], $defaults['harga_pokok'],
            $defaults['harga_jual'], $defaults['harga_jual2'], $defaults['harga_jual3'], $defaults['quantity'], $defaults['stok_min'],
            $defaults['status_item'], $defaults['supplier1'], $defaults['supplier2'], $defaults['supplier3'], $defaults['status_produk'],
            $defaults['rak_barang'], $defaults['jasa_waktu'], $defaults['no_item']
        );
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $zero = '0';
    $empty = '';
    $sql = "INSERT INTO tblitem (noitem, kodebarcode, namaitem, jenis, satuan, hargapokok, hargajual, hargajual2, hargajual3, hjqtyd2, hjqtyd3, hjqtys1, hjqtys2, totalpokok, quantity, stokmin, statusitem, supplier, supplier2, supplier3, statusproduk, gambar, note, rakbarang, jasawaktu, jasasatuanwaktu, jeniskomisi, komisiprosen, komisinominal, inv_idawal, inv_jmlawal, inv_hrgawal, inv_tglawal, stok_maks, kd_pabrik, kd_etalase, jenis_jasa) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssssssssssssssssssss',
        $defaults['no_item'], $defaults['kode_barcode'], $defaults['nama_item'], $defaults['jenis'], $defaults['satuan'],
        $defaults['harga_pokok'], $defaults['harga_jual'], $defaults['harga_jual2'], $defaults['harga_jual3'],
        $zero, $zero, $zero, $zero, $zero, $defaults['quantity'], $defaults['stok_min'], $defaults['status_item'],
        $defaults['supplier1'], $defaults['supplier2'], $defaults['supplier3'], $defaults['status_produk'], $empty,
        $empty, $defaults['rak_barang'], $defaults['jasa_waktu'], $empty, $empty, $zero, $zero, $zero, $zero,
        $zero, date('Y-m-d'), $zero, $zero, $empty, $zero
    );
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncUpsertPelanggan($koneksi, $row) {
    if (!function_exists('fitmotorApplyMemberTierFloor')) {
        require_once __DIR__ . '/_include_kategori_member.php';
    }

    $defaults = [
        'no_pelanggan' => $row['no_pelanggan'] ?? '',
        'nama_pelanggan' => $row['nama_pelanggan'] ?? '',
        'alamat' => $row['alamat'] ?? '',
        'kota' => $row['kota'] ?? '',
        'propinsi' => $row['propinsi'] ?? '',
        'kode_post' => $row['kode_post'] ?? '',
        'negara' => $row['negara'] ?? 'Indonesia',
        'telephone' => $row['telephone'] ?? '',
        'fax' => $row['fax'] ?? '',
        'kontak_person' => $row['kontak_person'] ?? '',
        'note' => $row['note'] ?? '',
        'potongan' => $row['potongan'] ?? '0',
        'tipe_pot' => !empty($row['tipe_pot']) ? $row['tipe_pot'] : 'C',
        'lavel_harga' => !empty($row['lavel_harga']) ? $row['lavel_harga'] : 'A',
        'kgrup' => $row['kgrup'] ?? '001'
    ];

    $existsStmt = mysqli_prepare($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $defaults['no_pelanggan']);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($existsStmt);

    $groupCode = !empty($defaults['kgrup']) ? accessSyncEnsurePelangganGroup($koneksi, $defaults['kgrup']) : '001';

    if ($found) {
        $sql = "UPDATE tblpelanggan SET namapelanggan=?, alamat=?, kota=?, propinsi=?, kodepost=?, negara=?, telephone=?, fax=?, kontakperson=?, note=?, potongan=?, tipepot=?, lavelharga=?, kgrup=? WHERE nopelanggan=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'sssssssssssssss',
            $defaults['nama_pelanggan'], $defaults['alamat'], $defaults['kota'], $defaults['propinsi'], $defaults['kode_post'],
            $defaults['negara'], $defaults['telephone'], $defaults['fax'], $defaults['kontak_person'], $defaults['note'],
            $defaults['potongan'], $defaults['tipe_pot'], $defaults['lavel_harga'], $groupCode, $defaults['no_pelanggan']
        );
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        fitmotorApplyMemberTierFloor($koneksi, $defaults['no_pelanggan'], $defaults['kgrup']);
        return 'updated';
    }

    $patokan = '';
    $klat = '';
    $klong = '';
    $panggilan = '';
    $saldoAwal = '0';
    $perTanggal = date('Y-m-d');
    $tglLahir = '0001-01-01';
    $idPanggilan = '0';
    $sql = "INSERT INTO tblpelanggan (nopelanggan, namapelanggan, alamat, kota, propinsi, kodepost, negara, telephone, fax, kontakperson, note, potongan, tipepot, lavelharga, kgrup, patokan, klat, klong, panggilan, saldoawal, pertanggal, tgllahir, id_panggilan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssssss',
        $defaults['no_pelanggan'], $defaults['nama_pelanggan'], $defaults['alamat'], $defaults['kota'], $defaults['propinsi'],
        $defaults['kode_post'], $defaults['negara'], $defaults['telephone'], $defaults['fax'], $defaults['kontak_person'],
        $defaults['note'], $defaults['potongan'], $defaults['tipe_pot'], $defaults['lavel_harga'], $groupCode,
        $patokan, $klat, $klong, $panggilan, $saldoAwal, $perTanggal, $tglLahir, $idPanggilan
    );
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    fitmotorApplyMemberTierFloor($koneksi, $defaults['no_pelanggan'], $defaults['kgrup']);
    return 'inserted';
}

function accessSyncUpsertKendaraanPelanggan($koneksi, $row) {
    $pelangganRow = [
        'no_pelanggan' => $row['no_polisi'],
        'nama_pelanggan' => $row['nama_pelanggan'],
        'alamat' => $row['alamat'],
        'kota' => $row['kota'],
        'propinsi' => '',
        'kode_post' => '',
        'negara' => 'Indonesia',
        'telephone' => $row['telephone'],
        'fax' => '',
        'kontak_person' => $row['kontak_person'],
        'note' => $row['note'],
        'potongan' => 0,
        'tipe_pot' => 'C',
        'lavel_harga' => 'A',
        'kgrup' => $row['grup'] ?: '001'
    ];
    accessSyncUpsertPelanggan($koneksi, $pelangganRow);

    $existsStmt = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $row['no_polisi']);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tblkendaraan SET pemilik=?, alamat=?, tipe=?, jenis=?, tahun_buat=?, warna=?, note=? WHERE nopolisi=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssssss', $row['nama_pelanggan'], $row['alamat'], $row['tipe'], $row['jenis'], $row['tahun_buat'], $row['warna'], $row['note'], $row['no_polisi']);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $defaultMerek = '0';
    $defaultKodeTipe = '0';
    $defaultKodeJenis = '0';
    $defaultTahunRakit = '';
    $defaultKodeWarna = '0';
    $sql = "INSERT INTO tblkendaraan (nopolisi, pemilik, alamat, kode_merek, tipe, kode_tipe, jenis, kode_jenis, tahun_buat, tahun_rakit, warna, kode_warna, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'sssssssssssss', $row['no_polisi'], $row['nama_pelanggan'], $row['alamat'], $defaultMerek, $row['tipe'], $defaultKodeTipe, $row['jenis'], $defaultKodeJenis, $row['tahun_buat'], $defaultTahunRakit, $row['warna'], $defaultKodeWarna, $row['note']);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncApplyMemberPelanggan($koneksi, $row) {
    if (!function_exists('fitmotorApplyMemberTierFloor')) {
        require_once __DIR__ . '/_include_kategori_member.php';
    }

    $groupCode = accessSyncEnsurePelangganGroup($koneksi, $row['tipe_member']);
    $existsStmt = mysqli_prepare($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $row['no_pelanggan']);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tblpelanggan SET kgrup=? WHERE nopelanggan=?");
        mysqli_stmt_bind_param($stmt, 'ss', $groupCode, $row['no_pelanggan']);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        fitmotorApplyMemberTierFloor($koneksi, $row['no_pelanggan'], $row['tipe_member']);
        return 'updated';
    }

    $empty = '';
    $zero = '0';
    $patokan = '';
    $klat = '';
    $klong = '';
    $panggilan = '';
    $saldoAwal = '0';
    $perTanggal = date('Y-m-d');
    $tglLahir = '0001-01-01';
    $idPanggilan = '0';
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tblpelanggan (nopelanggan, namapelanggan, alamat, kota, propinsi, kodepost, negara, telephone, fax, kontakperson, note, potongan, tipepot, lavelharga, kgrup, patokan, klat, klong, panggilan, saldoawal, pertanggal, tgllahir, id_panggilan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssssss',
        $row['no_pelanggan'], $row['no_pelanggan'], $empty, $empty, $empty, $empty, $empty, $empty, $empty, $empty, $empty, $zero, $empty, $empty, $groupCode,
        $patokan, $klat, $klong, $panggilan, $saldoAwal, $perTanggal, $tglLahir, $idPanggilan
    );
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    fitmotorApplyMemberTierFloor($koneksi, $row['no_pelanggan'], $row['tipe_member']);
    return 'inserted';
}

function accessSyncMergeMasterRun($koneksi, $datasetKey, $runId) {
    $configs = accessSyncDatasetConfigs();
    if (!isset($configs[$datasetKey]) || !accessSyncIsMasterDataset($datasetKey)) {
        return ['success' => false, 'message' => 'Dataset tidak didukung untuk merge master'];
    }

    $result = accessSyncFindRunRows($koneksi, $configs[$datasetKey]['table'], $runId);
    if (!$result) {
        return ['success' => false, 'message' => mysqli_error($koneksi)];
    }

    $processed = 0;
    $inserted = 0;
    $updated = 0;

    mysqli_begin_transaction($koneksi);
    try {
        while ($row = mysqli_fetch_assoc($result)) {
            $processed++;
            $status = '';
            switch ($datasetKey) {
                case 'supplier':
                    $status = accessSyncUpsertSupplier($koneksi, $row);
                    break;
                case 'mekanik':
                    $status = accessSyncUpsertMekanik($koneksi, $row);
                    break;
                case 'item':
                    $status = accessSyncUpsertItem($koneksi, $row);
                    break;
                case 'pelanggan':
                    $status = accessSyncUpsertPelanggan($koneksi, $row);
                    break;
                case 'kendaraan_pelanggan':
                    $status = accessSyncUpsertKendaraanPelanggan($koneksi, $row);
                    break;
                case 'member_pelanggan':
                    $status = accessSyncApplyMemberPelanggan($koneksi, $row);
                    break;
            }

            if ($status === 'inserted') {
                $inserted++;
            } elseif ($status === 'updated') {
                $updated++;
            }
        }

        mysqli_commit($koneksi);
        return [
            'success' => true,
            'processed' => $processed,
            'inserted' => $inserted,
            'updated' => $updated
        ];
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function accessSyncUpsertKonsolidasiPembelianHeader($koneksi, $row, $runId) {
    $sql = "INSERT INTO konsolidasi_pembelian_header
        (kd_cabang, no_transaksi, status, cara_bayar, tanggal, no_order, tanggal_order, no_supplier, note, total_qty_order, total_qty, total_beli, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user_input, id_tabel, source_run_id, source_file_date)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        status=VALUES(status), cara_bayar=VALUES(cara_bayar), tanggal=VALUES(tanggal), no_order=VALUES(no_order),
        tanggal_order=VALUES(tanggal_order), no_supplier=VALUES(no_supplier), note=VALUES(note),
        total_qty_order=VALUES(total_qty_order), total_qty=VALUES(total_qty), total_beli=VALUES(total_beli),
        diskon=VALUES(diskon), total_diskon=VALUES(total_diskon), pajak=VALUES(pajak), total_pajak=VALUES(total_pajak),
        total_akhir=VALUES(total_akhir), total_retur=VALUES(total_retur), pembayaran=VALUES(pembayaran),
        tanggal_jt=VALUES(tanggal_jt), tanggal_lunas=VALUES(tanggal_lunas), jumlah_bayar=VALUES(jumlah_bayar),
        user_input=VALUES(user_input), id_tabel=VALUES(id_tabel), source_run_id=VALUES(source_run_id), source_file_date=VALUES(source_file_date)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [
        $row['kd_cabang'], $row['no_transaksi'], $row['status'], $row['cara_bayar'], $row['tanggal'], $row['no_order'],
        $row['tanggal_order'], $row['no_supplier'], $row['note'], $row['total_qty_order'], $row['total_qty'],
        $row['total_beli'], $row['diskon'], $row['total_diskon'], $row['pajak'], $row['total_pajak'],
        $row['total_akhir'], $row['total_retur'], $row['pembayaran'], $row['tanggal_jt'], $row['tanggal_lunas'],
        $row['jumlah_bayar'], $row['user_input'], $row['id_tabel'], (string)$runId, $row['tanggal_data']
    ];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncUpsertKonsolidasiPembelianDetail($koneksi, $row, $runId) {
    $sql = "INSERT INTO konsolidasi_pembelian_detail
        (kd_cabang, no_transaksi, no_baris, no_item, qty_order, quantity, qty_retur, harga_pokok, potongan, harga_sp, total, sts_order, id_inv, source_run_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        qty_order=VALUES(qty_order), quantity=VALUES(quantity), qty_retur=VALUES(qty_retur), harga_pokok=VALUES(harga_pokok),
        potongan=VALUES(potongan), harga_sp=VALUES(harga_sp), total=VALUES(total), sts_order=VALUES(sts_order), id_inv=VALUES(id_inv), source_run_id=VALUES(source_run_id)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [
        $row['kd_cabang'], $row['no_transaksi'], (string)$row['no_baris'], $row['no_item'], $row['qty_order'], $row['quantity'],
        $row['qty_retur'], $row['harga_pokok'], $row['potongan'], $row['harga_sp'], $row['total'], $row['sts_order'], $row['id_inv'], (string)$runId
    ];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncUpsertKonsolidasiPenjualanHeader($koneksi, $row, $runId) {
    $sql = "INSERT INTO konsolidasi_penjualan_header
        (kd_cabang, no_transaksi, status, cara_bayar, tanggal, no_order, tanggal_order, no_sales, no_pelanggan, note, total_qty_order, total_qty, total_jual, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user_input, id_tabel, source_run_id, source_file_date)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        status=VALUES(status), cara_bayar=VALUES(cara_bayar), tanggal=VALUES(tanggal), no_order=VALUES(no_order),
        tanggal_order=VALUES(tanggal_order), no_sales=VALUES(no_sales), no_pelanggan=VALUES(no_pelanggan), note=VALUES(note),
        total_qty_order=VALUES(total_qty_order), total_qty=VALUES(total_qty), total_jual=VALUES(total_jual),
        diskon=VALUES(diskon), total_diskon=VALUES(total_diskon), pajak=VALUES(pajak), total_pajak=VALUES(total_pajak),
        total_akhir=VALUES(total_akhir), total_retur=VALUES(total_retur), pembayaran=VALUES(pembayaran),
        tanggal_jt=VALUES(tanggal_jt), tanggal_lunas=VALUES(tanggal_lunas), jumlah_bayar=VALUES(jumlah_bayar),
        user_input=VALUES(user_input), id_tabel=VALUES(id_tabel), source_run_id=VALUES(source_run_id), source_file_date=VALUES(source_file_date)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [
        $row['kd_cabang'], $row['no_transaksi'], $row['status'], $row['cara_bayar'], $row['tanggal'], $row['no_order'],
        $row['tanggal_order'], $row['no_sales'], $row['no_pelanggan'], $row['note'], $row['total_qty_order'], $row['total_qty'],
        $row['total_jual'], $row['diskon'], $row['total_diskon'], $row['pajak'], $row['total_pajak'],
        $row['total_akhir'], $row['total_retur'], $row['pembayaran'], $row['tanggal_jt'], $row['tanggal_lunas'],
        $row['jumlah_bayar'], $row['user_input'], $row['id_tabel'], (string)$runId, $row['tanggal_data']
    ];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncUpsertKonsolidasiPenjualanDetail($koneksi, $row, $runId) {
    $sql = "INSERT INTO konsolidasi_penjualan_detail
        (kd_cabang, no_transaksi, no_baris, no_item, qty_order, quantity, qty_retur, harga_jual, potongan, harga_sp, harga_pokok, total, sts_order, source_run_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        qty_order=VALUES(qty_order), quantity=VALUES(quantity), qty_retur=VALUES(qty_retur), harga_jual=VALUES(harga_jual),
        potongan=VALUES(potongan), harga_sp=VALUES(harga_sp), harga_pokok=VALUES(harga_pokok), total=VALUES(total),
        sts_order=VALUES(sts_order), source_run_id=VALUES(source_run_id)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [
        $row['kd_cabang'], $row['no_transaksi'], (string)$row['no_baris'], $row['no_item'], $row['qty_order'], $row['quantity'],
        $row['qty_retur'], $row['harga_jual'], $row['potongan'], $row['harga_sp'], $row['harga_pokok'], $row['total'], $row['sts_order'], (string)$runId
    ];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncUpsertKonsolidasiService($koneksi, $row, $runId) {
    $sql = "INSERT INTO konsolidasi_service
        (kd_cabang, no_service, tanggal, jam, no_pelanggan, no_polisi, mekanik1, mekanik2, mekanik3, mekanik4, biayam1, biayam2, biayam3, biayam4, km_sekarang, km_berikut, total_waktu, status, keterangan, subtotal_jasa, subtotal_item, subtotal, diskon, total_diskon, pajak, total_pajak, total_akhir, pembayaran, user_input, id_tabel, advisor, source_run_id, source_file_date)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        tanggal=VALUES(tanggal), jam=VALUES(jam), no_pelanggan=VALUES(no_pelanggan), no_polisi=VALUES(no_polisi),
        mekanik1=VALUES(mekanik1), mekanik2=VALUES(mekanik2), mekanik3=VALUES(mekanik3), mekanik4=VALUES(mekanik4),
        biayam1=VALUES(biayam1), biayam2=VALUES(biayam2), biayam3=VALUES(biayam3), biayam4=VALUES(biayam4),
        km_sekarang=VALUES(km_sekarang), km_berikut=VALUES(km_berikut), total_waktu=VALUES(total_waktu),
        status=VALUES(status), keterangan=VALUES(keterangan), subtotal_jasa=VALUES(subtotal_jasa), subtotal_item=VALUES(subtotal_item),
        subtotal=VALUES(subtotal), diskon=VALUES(diskon), total_diskon=VALUES(total_diskon), pajak=VALUES(pajak),
        total_pajak=VALUES(total_pajak), total_akhir=VALUES(total_akhir), pembayaran=VALUES(pembayaran),
        user_input=VALUES(user_input), id_tabel=VALUES(id_tabel), advisor=VALUES(advisor), source_run_id=VALUES(source_run_id), source_file_date=VALUES(source_file_date)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [
        $row['kd_cabang'], $row['no_service'], $row['tanggal'], $row['jam'], $row['no_pelanggan'], $row['no_polisi'],
        $row['mekanik1'], $row['mekanik2'], $row['mekanik3'], $row['mekanik4'], $row['biayam1'], $row['biayam2'],
        $row['biayam3'], $row['biayam4'], $row['km_sekarang'], $row['km_berikut'], $row['total_waktu'], $row['status'],
        $row['keterangan'], $row['subtotal_jasa'], $row['subtotal_item'], $row['subtotal'], $row['diskon'],
        $row['total_diskon'], $row['pajak'], $row['total_pajak'], $row['total_akhir'], $row['pembayaran'],
        $row['user_input'], $row['id_tabel'], $row['advisor'], (string)$runId, $row['tanggal_data']
    ];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
}

function accessSyncMapLegacyServiceStatus($status) {
    $status = trim((string) $status);
    $upperStatus = strtoupper($status);
    if (in_array($upperStatus, ['DATANG', 'MASUK', 'OPEN'], true)) {
        return '1';
    }
    if (in_array($upperStatus, ['BAYAR', 'PAID'], true)) {
        return '2';
    }
    if (in_array($upperStatus, ['SELESAI', 'FINISH', 'CLOSE', 'CLOSED'], true)) {
        return '4';
    }
    if ($status === '3') {
        return '4';
    }
    if ($status === '2') {
        return '2';
    }
    return $status !== '' ? $status : '1';
}

function accessSyncMapLegacyServiceStatusServis($status) {
    $status = trim((string) $status);
    $upperStatus = strtoupper($status);
    if (in_array($upperStatus, ['DATANG', 'MASUK', 'OPEN'], true)) {
        return 'datang';
    }
    if (in_array($upperStatus, ['BAYAR', 'PAID'], true)) {
        return 'bayar';
    }
    if (in_array($upperStatus, ['SELESAI', 'FINISH', 'CLOSE', 'CLOSED'], true)) {
        return 'selesai';
    }
    if ($status === '3') {
        return 'selesai';
    }
    if ($status === '2') {
        return 'bayar';
    }
    if ($status === '4') {
        return 'selesai';
    }
    return 'datang';
}

function accessSyncNormalizeTimeString($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
        return substr($value, 0, 5);
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }

    return date('H:i', $timestamp);
}

function accessSyncUpsertServiceHistoryHeader($koneksi, $row) {
    $noService = trim((string) ($row['no_service'] ?? ''));
    if ($noService === '') {
        return 'skipped';
    }

    $tanggal = accessSyncNormalizeDate($row['tanggal'] ?? '');
    if ($tanggal === '') {
        $tanggal = date('Y-m-d');
    }

    $jam = accessSyncNormalizeTimeString($row['jam'] ?? ($row['tanggal'] ?? ''));
    $statusLegacy = accessSyncMapLegacyServiceStatus($row['status'] ?? '');
    $statusServis = accessSyncMapLegacyServiceStatusServis($row['status'] ?? '');
    $kdCabang = accessSyncNormalizeCabangCode($row['kd_cabang'] ?? '');

    $defaults = [
        'tanggal' => $tanggal,
        'jam' => $jam,
        'no_pelanggan' => trim((string) ($row['no_pelanggan'] ?? '')),
        'no_polisi' => trim((string) ($row['no_polisi'] ?? '')),
        'status' => $statusLegacy,
        'kd_cabang' => $kdCabang,
        'id_user' => 0,
        'total' => (string) ($row['subtotal'] ?? $row['total_akhir'] ?? 0),
        'diskon_nom' => (string) ($row['total_diskon'] ?? $row['diskon'] ?? 0),
        'ppn_nom' => (string) ($row['total_pajak'] ?? $row['pajak'] ?? 0),
        'total_grand' => (string) ($row['total_akhir'] ?? $row['subtotal'] ?? 0),
        'bayar' => (string) ($row['pembayaran'] ?? 0),
        'jenis_bayar' => 0,
        'total_waktu' => (int) ($row['total_waktu'] ?? 0),
        'keterangan' => substr(trim((string) ($row['keterangan'] ?? '')), 0, 100),
        'km_skr' => (int) ($row['km_sekarang'] ?? 0),
        'mekanik1' => trim((string) ($row['mekanik1'] ?? '')),
        'mekanik2' => trim((string) ($row['mekanik2'] ?? '')),
        'mekanik3' => trim((string) ($row['mekanik3'] ?? '')),
        'mekanik4' => trim((string) ($row['mekanik4'] ?? '')),
        'biayaM1' => (string) ($row['biayam1'] ?? 0),
        'biayaM2' => (string) ($row['biayam2'] ?? 0),
        'biayaM3' => (string) ($row['biayam3'] ?? 0),
        'biayaM4' => (string) ($row['biayam4'] ?? 0),
        'km_berikut' => (int) ($row['km_berikut'] ?? 0),
        'subtotal_jasa' => (string) ($row['subtotal_jasa'] ?? 0),
        'subtotal_item' => (string) ($row['subtotal_item'] ?? 0),
        'subtotal' => (string) ($row['subtotal'] ?? 0),
        'total_diskon' => (string) ($row['total_diskon'] ?? $row['diskon'] ?? 0),
        'total_pajak' => (string) ($row['total_pajak'] ?? $row['pajak'] ?? 0),
        'total_akhir' => (string) ($row['total_akhir'] ?? 0),
        'status_servis' => $statusServis,
        'metode_pembayaran' => 'Tunai',
    ];

    $existsStmt = mysqli_prepare($koneksi, "SELECT no_service FROM tblservice WHERE no_service = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $noService);
    mysqli_stmt_execute($existsStmt);
    $existsResult = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($existsResult);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tblservice SET tanggal=?, jam=?, no_pelanggan=?, no_polisi=?, status=?, kd_cabang=?, total=?, diskon_nom=?, ppn_nom=?, total_grand=?, bayar=?, total_waktu=?, keterangan=?, km_skr=?, mekanik1=?, mekanik2=?, mekanik3=?, mekanik4=?, biayaM1=?, biayaM2=?, biayaM3=?, biayaM4=?, km_berikut=?, subtotal_jasa=?, subtotal_item=?, subtotal=?, total_diskon=?, total_pajak=?, total_akhir=?, status_servis=?, metode_pembayaran=?, updated_at=NOW() WHERE no_service=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        $params = [
            $defaults['tanggal'], $defaults['jam'], $defaults['no_pelanggan'], $defaults['no_polisi'], $defaults['status'], $defaults['kd_cabang'],
            (string) $defaults['total'], (string) $defaults['diskon_nom'], (string) $defaults['ppn_nom'], (string) $defaults['total_grand'], (string) $defaults['bayar'], (string) $defaults['total_waktu'],
            $defaults['keterangan'], (string) $defaults['km_skr'], $defaults['mekanik1'], $defaults['mekanik2'], $defaults['mekanik3'], $defaults['mekanik4'],
            (string) $defaults['biayaM1'], (string) $defaults['biayaM2'], (string) $defaults['biayaM3'], (string) $defaults['biayaM4'], (string) $defaults['km_berikut'],
            (string) $defaults['subtotal_jasa'], (string) $defaults['subtotal_item'], (string) $defaults['subtotal'], (string) $defaults['total_diskon'], (string) $defaults['total_pajak'],
            (string) $defaults['total_akhir'], $defaults['status_servis'], $defaults['metode_pembayaran'], $noService
        ];
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $sql = "INSERT INTO tblservice (no_service, tanggal, jam, no_pelanggan, no_polisi, status, kd_cabang, id_user, total, diskon_nom, ppn_nom, total_grand, bayar, jenis_bayar, total_waktu, keterangan, km_skr, mekanik1, mekanik2, mekanik3, mekanik4, biayaM1, biayaM2, biayaM3, biayaM4, km_berikut, subtotal_jasa, subtotal_item, subtotal, total_diskon, total_pajak, total_akhir, status_servis, metode_pembayaran)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [
        $noService, $defaults['tanggal'], $defaults['jam'], $defaults['no_pelanggan'], $defaults['no_polisi'], $defaults['status'], $defaults['kd_cabang'], (string) $defaults['id_user'],
        (string) $defaults['total'], (string) $defaults['diskon_nom'], (string) $defaults['ppn_nom'], (string) $defaults['total_grand'], (string) $defaults['bayar'], (string) $defaults['jenis_bayar'], (string) $defaults['total_waktu'],
        $defaults['keterangan'], (string) $defaults['km_skr'], $defaults['mekanik1'], $defaults['mekanik2'], $defaults['mekanik3'], $defaults['mekanik4'],
        (string) $defaults['biayaM1'], (string) $defaults['biayaM2'], (string) $defaults['biayaM3'], (string) $defaults['biayaM4'], (string) $defaults['km_berikut'],
        (string) $defaults['subtotal_jasa'], (string) $defaults['subtotal_item'], (string) $defaults['subtotal'], (string) $defaults['total_diskon'], (string) $defaults['total_pajak'], (string) $defaults['total_akhir'],
        $defaults['status_servis'], $defaults['metode_pembayaran']
    ];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncUpsertServiceHistoryBarang($koneksi, $row) {
    $serviceAction = accessSyncUpsertServiceHistoryHeader($koneksi, $row);
    $noService = trim((string) ($row['no_service'] ?? ''));
    $noItem = trim((string) ($row['no_item'] ?? ''));
    if ($noService === '' || $noItem === '') {
        return $serviceAction === 'skipped' ? 'skipped' : $serviceAction;
    }

    $noBaris = (int) ($row['no_baris'] ?? 0);
    $quantity = (int) ($row['quantity'] ?? 0);
    $qtyRetur = (int) ($row['qty_retur'] ?? 0);
    $hargaJual = (float) ($row['harga'] ?? 0);
    $potongan = (float) ($row['potongan'] ?? 0);
    $total = (float) ($row['total'] ?? 0);

    $existsStmt = mysqli_prepare($koneksi, "SELECT id FROM tblservis_barang WHERE no_service = ? AND nobaris = ? AND no_item = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 'sis', $noService, $noBaris, $noItem);
    mysqli_stmt_execute($existsStmt);
    $existsResult = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($existsResult);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tblservis_barang SET quantity=?, qty_retur=?, harga_jual=?, potongan=?, total=?, updated_at=NOW() WHERE id=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        $id = (int) $found['id'];
        $params = [(string) $quantity, (string) $qtyRetur, (string) $hargaJual, (string) $potongan, (string) $total, (string) $id];
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $sql = "INSERT INTO tblservis_barang (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total) VALUES (?,?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [$noService, (string) $noBaris, $noItem, (string) $quantity, (string) $qtyRetur, (string) $hargaJual, (string) $potongan, (string) $total];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncUpsertServiceHistoryJasa($koneksi, $row) {
    $serviceAction = accessSyncUpsertServiceHistoryHeader($koneksi, $row);
    $noService = trim((string) ($row['no_service'] ?? ''));
    $noItem = trim((string) ($row['no_item'] ?? ''));
    if ($noService === '' || $noItem === '') {
        return $serviceAction === 'skipped' ? 'skipped' : $serviceAction;
    }

    $noBaris = (int) ($row['no_baris'] ?? 0);
    $waktu = (int) ($row['waktu'] ?? 0);
    $harga = (float) ($row['harga'] ?? 0);
    $potongan = (float) ($row['potongan'] ?? 0);
    $total = (float) ($row['total'] ?? 0);

    $existsStmt = mysqli_prepare($koneksi, "SELECT id FROM tblservis_jasa WHERE no_service = ? AND nobaris = ? AND no_item = ? LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 'sis', $noService, $noBaris, $noItem);
    mysqli_stmt_execute($existsStmt);
    $existsResult = mysqli_stmt_get_result($existsStmt);
    $found = mysqli_fetch_assoc($existsResult);
    mysqli_stmt_close($existsStmt);

    if ($found) {
        $sql = "UPDATE tblservis_jasa SET waktu=?, harga=?, potongan=?, total=?, updated_at=NOW() WHERE id=?";
        $stmt = mysqli_prepare($koneksi, $sql);
        $id = (int) $found['id'];
        $params = [(string) $waktu, (string) $harga, (string) $potongan, (string) $total, (string) $id];
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return 'updated';
    }

    $sql = "INSERT INTO tblservis_jasa (no_service, nobaris, no_item, waktu, harga, potongan, total) VALUES (?,?,?,?,?,?,?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    $params = [$noService, (string) $noBaris, $noItem, (string) $waktu, (string) $harga, (string) $potongan, (string) $total];
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return 'inserted';
}

function accessSyncReplaceServiceAdvisor($koneksi, $row) {
    $noService = trim((string) ($row['no_service'] ?? ''));
    $advisor = trim((string) ($row['advisor'] ?? ''));
    if ($noService === '' || $advisor === '') {
        return 'skipped';
    }

    $deleteStmt = mysqli_prepare($koneksi, "DELETE FROM tblservice_advisor WHERE no_service = ?");
    mysqli_stmt_bind_param($deleteStmt, 's', $noService);
    accessSyncExecuteStatementOrThrow($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    $insertStmt = mysqli_prepare($koneksi, "INSERT INTO tblservice_advisor (no_service, advisor) VALUES (?, ?)");
    mysqli_stmt_bind_param($insertStmt, 'ss', $noService, $advisor);
    accessSyncExecuteStatementOrThrow($insertStmt);
    mysqli_stmt_close($insertStmt);
    return 'replaced';
}

/**
 * Setelah header service di-sync ke tblservice, hitung ulang statistik_pelanggan
 * (total_transaksi, total_nominal, jumlah_kunjungan, dll) berdasarkan riwayat
 * tblservice yang sudah bayar, lalu pastikan status_member tidak turun di bawah
 * tier yang sudah disinkronkan dari fitmotor (floor).
 */
function accessSyncRefreshStatistikPelangganAfterServiceSync($koneksi, $row) {
    $noPelanggan = trim((string) ($row['no_pelanggan'] ?? ''));
    if ($noPelanggan === '') {
        return;
    }

    if (!function_exists('updateStatistikPelangganAfterPayment')) {
        require_once __DIR__ . '/_include_statistik_pelanggan.php';
    }
    if (!function_exists('fitmotorApplyMemberTierFloor')) {
        require_once __DIR__ . '/_include_kategori_member.php';
    }

    $noPelangganEsc = mysqli_real_escape_string($koneksi, $noPelanggan);
    $existingResult = mysqli_query($koneksi, "SELECT status_member FROM statistik_pelanggan WHERE no_pelanggan = '$noPelangganEsc' LIMIT 1");
    $existing = $existingResult ? mysqli_fetch_assoc($existingResult) : null;
    $tierFloor = $existing['status_member'] ?? 'Bronze';

    $noService = trim((string) ($row['no_service'] ?? ''));
    updateStatistikPelangganAfterPayment($koneksi, $noPelanggan, $noService);

    fitmotorApplyMemberTierFloor($koneksi, $noPelanggan, $tierFloor);
}

function accessSyncMergeTransactionRun($koneksi, $datasetKey, $runId) {
    $configs = accessSyncDatasetConfigs();
    if (!isset($configs[$datasetKey]) || !accessSyncIsTransactionDataset($datasetKey)) {
        return ['success' => false, 'message' => 'Dataset tidak didukung untuk merge transaksi'];
    }

    $result = accessSyncFindRunRows($koneksi, $configs[$datasetKey]['table'], $runId);
    if (!$result) {
        return ['success' => false, 'message' => mysqli_error($koneksi)];
    }

    $processed = 0;
    $upserted = 0;
    mysqli_begin_transaction($koneksi);
    try {
        while ($row = mysqli_fetch_assoc($result)) {
            $processed++;
            if ($datasetKey === 'pembelian') {
                accessSyncUpsertKonsolidasiPembelianHeader($koneksi, $row, $runId);
                accessSyncUpsertKonsolidasiPembelianDetail($koneksi, $row, $runId);
            } elseif ($datasetKey === 'penjualan') {
                accessSyncUpsertKonsolidasiPenjualanHeader($koneksi, $row, $runId);
                accessSyncUpsertKonsolidasiPenjualanDetail($koneksi, $row, $runId);
            } elseif ($datasetKey === 'service') {
                accessSyncUpsertKonsolidasiService($koneksi, $row, $runId);
                accessSyncUpsertServiceHistoryHeader($koneksi, $row);
                accessSyncRefreshStatistikPelangganAfterServiceSync($koneksi, $row);
            } elseif ($datasetKey === 'service_barang') {
                accessSyncUpsertServiceHistoryBarang($koneksi, $row);
            } elseif ($datasetKey === 'service_jasa') {
                accessSyncUpsertServiceHistoryJasa($koneksi, $row);
            } elseif ($datasetKey === 'service_advisor') {
                accessSyncReplaceServiceAdvisor($koneksi, $row);
            }
            $upserted++;
        }
        mysqli_commit($koneksi);
        return ['success' => true, 'processed' => $processed, 'upserted' => $upserted];
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function accessSyncNormalizeDate($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
}

function accessSyncFetchCabangOptions($koneksi) {
    $rows = [];
    $result = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang ASC");
    if (!$result) {
        return $rows;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function accessSyncBuildConsolidationFilterSql($filters, &$params, &$types, $dateColumn = 'COALESCE(tanggal, DATE(created_at))') {
    $clauses = [];
    $params = [];
    $types = '';

    if (!empty($filters['kd_cabang'])) {
        $clauses[] = 'kd_cabang = ?';
        $params[] = strtoupper(trim((string) $filters['kd_cabang']));
        $types .= 's';
    }

    if (!empty($filters['date_from'])) {
        $clauses[] = $dateColumn . ' >= ?';
        $params[] = $filters['date_from'];
        $types .= 's';
    }

    if (!empty($filters['date_to'])) {
        $clauses[] = $dateColumn . ' <= ?';
        $params[] = $filters['date_to'];
        $types .= 's';
    }

    return $clauses;
}

function accessSyncGetConsolidationTableMap() {
    return [
        'pembelian' => [
            'table' => 'konsolidasi_pembelian_header',
            'label' => 'Pembelian',
            'sum_column' => 'total_akhir'
        ],
        'penjualan' => [
            'table' => 'konsolidasi_penjualan_header',
            'label' => 'Penjualan',
            'sum_column' => 'total_akhir'
        ],
        'service' => [
            'table' => 'konsolidasi_service',
            'label' => 'Service',
            'sum_column' => 'total_akhir'
        ]
    ];
}

function accessSyncFetchConsolidationMetrics($koneksi, $filters = []) {
    $tables = accessSyncGetConsolidationTableMap();
    $metrics = [];

    foreach ($tables as $datasetKey => $config) {
        $params = [];
        $types = '';
        $clauses = accessSyncBuildConsolidationFilterSql($filters, $params, $types);
        $sql = "SELECT COUNT(*) AS total_rows, COALESCE(SUM({$config['sum_column']}), 0) AS total_amount, MAX(COALESCE(tanggal, DATE(created_at))) AS last_date FROM {$config['table']}";
        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $stmt = mysqli_prepare($koneksi, $sql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $metrics[$datasetKey] = [
            'label' => $config['label'],
            'total_rows' => $row ? (int) $row['total_rows'] : 0,
            'total_amount' => $row ? (float) $row['total_amount'] : 0,
            'last_date' => $row ? $row['last_date'] : null
        ];
    }

    return $metrics;
}

function accessSyncFetchCabangConsolidationSummary($koneksi, $filters = []) {
    $tables = accessSyncGetConsolidationTableMap();
    $summary = [];

    foreach ($tables as $datasetKey => $config) {
        $params = [];
        $types = '';
        $clauses = accessSyncBuildConsolidationFilterSql($filters, $params, $types);
        $sql = "SELECT kd_cabang, COUNT(*) AS total_rows, COALESCE(SUM({$config['sum_column']}), 0) AS total_amount
                FROM {$config['table']}";
        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }
        $sql .= ' GROUP BY kd_cabang ORDER BY kd_cabang ASC';

        $stmt = mysqli_prepare($koneksi, $sql);
        if (!empty($types)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $kdCabang = $row['kd_cabang'];
            if (!isset($summary[$kdCabang])) {
                $summary[$kdCabang] = [
                    'kd_cabang' => $kdCabang,
                    'pembelian_rows' => 0,
                    'pembelian_amount' => 0,
                    'penjualan_rows' => 0,
                    'penjualan_amount' => 0,
                    'service_rows' => 0,
                    'service_amount' => 0
                ];
            }

            $summary[$kdCabang][$datasetKey . '_rows'] = (int) $row['total_rows'];
            $summary[$kdCabang][$datasetKey . '_amount'] = (float) $row['total_amount'];
        }

        mysqli_stmt_close($stmt);
    }

    ksort($summary);
    return array_values($summary);
}

function accessSyncFetchRecentConsolidatedRows($koneksi, $datasetKey, $filters = [], $limit = 12) {
    $limit = (int) $limit;
    if ($limit <= 0) {
        $limit = 12;
    }
    if ($limit > 100) {
        $limit = 100;
    }

    $configs = [
        'pembelian' => [
            'table' => 'konsolidasi_pembelian_header',
            'columns' => 'no_transaksi, COALESCE(tanggal, DATE(created_at)) AS tanggal, kd_cabang, no_supplier, total_qty, total_akhir, status',
            'order' => 'COALESCE(tanggal, DATE(created_at)) DESC, no_transaksi DESC'
        ],
        'penjualan' => [
            'table' => 'konsolidasi_penjualan_header',
            'columns' => 'no_transaksi, COALESCE(tanggal, DATE(created_at)) AS tanggal, kd_cabang, no_pelanggan, total_qty, total_akhir, status',
            'order' => 'COALESCE(tanggal, DATE(created_at)) DESC, no_transaksi DESC'
        ],
        'service' => [
            'table' => 'konsolidasi_service',
            'columns' => 'no_service, COALESCE(tanggal, DATE(created_at)) AS tanggal, jam, kd_cabang, no_pelanggan, no_polisi, total_akhir, status',
            'order' => 'COALESCE(tanggal, DATE(created_at)) DESC, jam DESC, no_service DESC'
        ]
    ];

    if (!isset($configs[$datasetKey])) {
        return [];
    }

    $params = [];
    $types = '';
    $clauses = accessSyncBuildConsolidationFilterSql($filters, $params, $types);
    $sql = "SELECT {$configs[$datasetKey]['columns']} FROM {$configs[$datasetKey]['table']}";
    if (!empty($clauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }
    $sql .= " ORDER BY {$configs[$datasetKey]['order']} LIMIT " . $limit;

    $stmt = mysqli_prepare($koneksi, $sql);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function accessSyncLoadApiEnv() {
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envBootstrap = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'access_sync_env.php';
    if (is_file($envBootstrap)) {
        include_once $envBootstrap;
    }

    $loaded = true;
}

function accessSyncGetApiToken() {
    accessSyncLoadApiEnv();

    $token = getenv('ACCESS_SYNC_TOKEN');
    if ($token === false || $token === '') {
        if (defined('ACCESS_SYNC_TOKEN')) {
            $token = ACCESS_SYNC_TOKEN;
        } else {
            $token = '';
        }
    }

    return trim((string) $token);
}

function accessSyncValidateApiToken($providedToken) {
    $expectedToken = accessSyncGetApiToken();
    if ($expectedToken === '') {
        return false;
    }

    $providedToken = trim((string) $providedToken);
    if ($providedToken === '') {
        return false;
    }

    return hash_equals($expectedToken, $providedToken);
}

function accessSyncColumnExists($koneksi, $tableName, $columnName) {
    static $cache = [];

    $cacheKey = $tableName . '.' . $columnName;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $safeTable = mysqli_real_escape_string($koneksi, $tableName);
    $safeColumn = mysqli_real_escape_string($koneksi, $columnName);
    $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
    $result = mysqli_query($koneksi, $sql);
    $exists = $result && mysqli_num_rows($result) > 0;
    if ($result) {
        mysqli_free_result($result);
    }

    $cache[$cacheKey] = $exists;
    return $exists;
}

function accessSyncEnsureRuntimeSchema($koneksi) {
    static $done = false;
    if ($done || !$koneksi) {
        return;
    }

    $runtimeColumns = [
        'dataset_key' => "ALTER TABLE sync_access_runs ADD COLUMN dataset_key VARCHAR(50) NULL AFTER source_name",
        'trigger_source' => "ALTER TABLE sync_access_runs ADD COLUMN trigger_source VARCHAR(50) NULL AFTER dataset_key",
        'source_cabang' => "ALTER TABLE sync_access_runs ADD COLUMN source_cabang VARCHAR(30) NULL AFTER trigger_source",
        'machine_name' => "ALTER TABLE sync_access_runs ADD COLUMN machine_name VARCHAR(120) NULL AFTER source_cabang",
        'batch_key' => "ALTER TABLE sync_access_runs ADD COLUMN batch_key VARCHAR(100) NULL AFTER machine_name",
        'request_ip' => "ALTER TABLE sync_access_runs ADD COLUMN request_ip VARCHAR(45) NULL AFTER batch_key",
        'merge_status' => "ALTER TABLE sync_access_runs ADD COLUMN merge_status VARCHAR(20) NULL AFTER failed_rows",
        'merge_processed' => "ALTER TABLE sync_access_runs ADD COLUMN merge_processed INT NOT NULL DEFAULT 0 AFTER merge_status",
        'merge_inserted' => "ALTER TABLE sync_access_runs ADD COLUMN merge_inserted INT NOT NULL DEFAULT 0 AFTER merge_processed",
        'merge_updated' => "ALTER TABLE sync_access_runs ADD COLUMN merge_updated INT NOT NULL DEFAULT 0 AFTER merge_inserted",
        'merge_upserted' => "ALTER TABLE sync_access_runs ADD COLUMN merge_upserted INT NOT NULL DEFAULT 0 AFTER merge_updated",
        'last_heartbeat_at' => "ALTER TABLE sync_access_runs ADD COLUMN last_heartbeat_at DATETIME NULL AFTER finished_at",
    ];

    foreach ($runtimeColumns as $columnName => $statement) {
        if (!accessSyncColumnExists($koneksi, 'sync_access_runs', $columnName)) {
            @mysqli_query($koneksi, $statement);
        }
    }

    $runtimeIndexes = [
        "ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_dataset (dataset_key)",
        "ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_source_cabang (source_cabang)",
        "ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_started_at (started_at)",
        "ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_sync_mode (sync_mode)"
    ];
    foreach ($runtimeIndexes as $statement) {
        @mysqli_query($koneksi, $statement);
    }

    $runtimeTables = [
        "CREATE TABLE IF NOT EXISTS stg_access_gabung_service_barang (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sync_run_id BIGINT UNSIGNED NOT NULL,
            kd_cabang VARCHAR(30) NULL,
            sts_source VARCHAR(50) NULL,
            tanggal_data DATETIME NULL,
            no_service VARCHAR(50) NOT NULL,
            tanggal DATETIME NULL,
            no_pelanggan VARCHAR(50) NULL,
            no_polisi VARCHAR(30) NULL,
            mekanik1 VARCHAR(50) NULL,
            mekanik2 VARCHAR(50) NULL,
            mekanik3 VARCHAR(50) NULL,
            mekanik4 VARCHAR(50) NULL,
            biayam1 DECIMAL(18,2) NULL,
            biayam2 DECIMAL(18,2) NULL,
            biayam3 DECIMAL(18,2) NULL,
            biayam4 DECIMAL(18,2) NULL,
            km_sekarang DECIMAL(18,2) NULL,
            km_berikut DECIMAL(18,2) NULL,
            total_waktu DECIMAL(18,2) NULL,
            status VARCHAR(30) NULL,
            keterangan TEXT NULL,
            subtotal_jasa DECIMAL(18,2) NULL,
            subtotal_item DECIMAL(18,2) NULL,
            subtotal DECIMAL(18,2) NULL,
            diskon DECIMAL(18,2) NULL,
            total_diskon DECIMAL(18,2) NULL,
            pajak DECIMAL(18,2) NULL,
            total_pajak DECIMAL(18,2) NULL,
            total_akhir DECIMAL(18,2) NULL,
            pembayaran DECIMAL(18,2) NULL,
            user_input VARCHAR(100) NULL,
            id_tabel VARCHAR(50) NULL,
            no_baris INT NULL,
            no_item VARCHAR(50) NULL,
            quantity DECIMAL(18,2) NULL,
            qty_retur DECIMAL(18,2) NULL,
            harga DECIMAL(18,2) NULL,
            potongan DECIMAL(18,2) NULL,
            total DECIMAL(18,2) NULL,
            raw_payload JSON NULL,
            KEY idx_stg_service_barang_sync (sync_run_id),
            KEY idx_stg_service_barang_no (no_service),
            KEY idx_stg_service_barang_item (no_item),
            KEY idx_stg_service_barang_cabang (kd_cabang)
        )",
        "CREATE TABLE IF NOT EXISTS stg_access_gabung_service_jasa (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sync_run_id BIGINT UNSIGNED NOT NULL,
            kd_cabang VARCHAR(30) NULL,
            sts_source VARCHAR(50) NULL,
            tanggal_data DATETIME NULL,
            no_service VARCHAR(50) NOT NULL,
            tanggal DATETIME NULL,
            no_pelanggan VARCHAR(50) NULL,
            no_polisi VARCHAR(30) NULL,
            mekanik1 VARCHAR(50) NULL,
            mekanik2 VARCHAR(50) NULL,
            mekanik3 VARCHAR(50) NULL,
            mekanik4 VARCHAR(50) NULL,
            biayam1 DECIMAL(18,2) NULL,
            biayam2 DECIMAL(18,2) NULL,
            biayam3 DECIMAL(18,2) NULL,
            biayam4 DECIMAL(18,2) NULL,
            km_sekarang DECIMAL(18,2) NULL,
            km_berikut DECIMAL(18,2) NULL,
            total_waktu DECIMAL(18,2) NULL,
            status VARCHAR(30) NULL,
            keterangan TEXT NULL,
            subtotal_jasa DECIMAL(18,2) NULL,
            subtotal_item DECIMAL(18,2) NULL,
            subtotal DECIMAL(18,2) NULL,
            diskon DECIMAL(18,2) NULL,
            total_diskon DECIMAL(18,2) NULL,
            pajak DECIMAL(18,2) NULL,
            total_pajak DECIMAL(18,2) NULL,
            total_akhir DECIMAL(18,2) NULL,
            pembayaran DECIMAL(18,2) NULL,
            user_input VARCHAR(100) NULL,
            id_tabel VARCHAR(50) NULL,
            no_baris INT NULL,
            no_item VARCHAR(50) NULL,
            waktu DECIMAL(18,2) NULL,
            harga DECIMAL(18,2) NULL,
            potongan DECIMAL(18,2) NULL,
            total DECIMAL(18,2) NULL,
            raw_payload JSON NULL,
            KEY idx_stg_service_jasa_sync (sync_run_id),
            KEY idx_stg_service_jasa_no (no_service),
            KEY idx_stg_service_jasa_item (no_item),
            KEY idx_stg_service_jasa_cabang (kd_cabang)
        )",
        "CREATE TABLE IF NOT EXISTS stg_access_gabung_service_advisor (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sync_run_id BIGINT UNSIGNED NOT NULL,
            kd_cabang VARCHAR(30) NULL,
            sts_source VARCHAR(50) NULL,
            tanggal_data DATETIME NULL,
            no_service VARCHAR(50) NOT NULL,
            advisor VARCHAR(100) NULL,
            raw_payload JSON NULL,
            KEY idx_stg_service_advisor_sync (sync_run_id),
            KEY idx_stg_service_advisor_no (no_service),
            KEY idx_stg_service_advisor_cabang (kd_cabang)
        )"
    ];
    foreach ($runtimeTables as $statement) {
        @mysqli_query($koneksi, $statement);
    }

    $done = true;
}

function accessSyncDatasetLabel($datasetKey) {
    $configs = accessSyncDatasetConfigs();
    if (isset($configs[$datasetKey]['label'])) {
        return $configs[$datasetKey]['label'];
    }

    if ($datasetKey === 'cabang') {
        return 'Master Cabang';
    }

    return ucwords(str_replace('_', ' ', (string) $datasetKey));
}

function accessSyncRunDatasetSync($koneksi, $datasetKey, $rows, $options = []) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $configs = accessSyncDatasetConfigs();
    if (!isset($configs[$datasetKey])) {
        return ['success' => false, 'message' => 'Dataset tidak valid'];
    }

    if (!is_array($rows) || empty($rows)) {
        return ['success' => false, 'message' => 'Payload rows kosong'];
    }

    $sourceCabang = isset($options['source_cabang']) ? accessSyncNormalizeCabangCode($options['source_cabang']) : '';
    $mode = isset($options['mode']) && $options['mode'] === 'replace' ? 'replace' : 'append';
    $sourceName = !empty($options['source_name']) ? (string) $options['source_name'] : accessSyncDatasetLabel($datasetKey);
    $sourceFile = !empty($options['source_file']) ? (string) $options['source_file'] : ('api-' . $datasetKey . '.json');
    $syncMode = !empty($options['sync_mode']) ? (string) $options['sync_mode'] : 'incremental';
    $triggerSource = !empty($options['trigger_source']) ? (string) $options['trigger_source'] : 'api_auto';
    $machineName = !empty($options['machine_name']) ? (string) $options['machine_name'] : php_uname('n');
    $batchKey = !empty($options['batch_key']) ? (string) $options['batch_key'] : '';
    $requestIp = !empty($options['request_ip']) ? (string) $options['request_ip'] : '';
    $autoMerge = array_key_exists('auto_merge', $options) ? (bool) $options['auto_merge'] : true;

    mysqli_begin_transaction($koneksi);
    try {
        $runId = accessSyncCreateRun($koneksi, $sourceName, $sourceFile, $syncMode, [
            'dataset_key' => $datasetKey,
            'trigger_source' => $triggerSource,
            'source_cabang' => $sourceCabang,
            'machine_name' => $machineName,
            'batch_key' => $batchKey,
            'request_ip' => $requestIp,
        ]);

        if ($mode === 'replace') {
            accessSyncDeletePriorStaging($koneksi, $datasetKey, $sourceCabang);
        }

        $totalRows = 0;
        $successRows = 0;
        $failedRows = 0;
        foreach ($rows as $row) {
            $totalRows++;
            list($mapped, $errors) = accessSyncMapRow($datasetKey, $row, $sourceCabang);
            $rowKey = '';
            foreach (['no_transaksi', 'no_service', 'no_supplier', 'no_mekanik', 'no_pelanggan', 'no_item', 'no_polisi'] as $keyName) {
                if (!empty($mapped[$keyName])) {
                    $rowKey = (string) $mapped[$keyName];
                    break;
                }
            }

            if (!empty($errors)) {
                $failedRows++;
                accessSyncLogError($koneksi, $runId, $datasetKey, $rowKey, implode('; ', $errors), $row);
                continue;
            }

            list($saved, $saveError) = accessSyncInsertRow($koneksi, $datasetKey, $runId, $mapped, $row);
            if ($saved) {
                $successRows++;
            } else {
                $failedRows++;
                accessSyncLogError($koneksi, $runId, $datasetKey, $rowKey, $saveError, $row);
            }
        }

        $mergeMeta = [
            'merge_status' => 'skipped',
            'merge_processed' => 0,
            'merge_inserted' => 0,
            'merge_updated' => 0,
            'merge_upserted' => 0,
        ];
        $notesParts = [
            'Mode=' . $mode,
            'Trigger=' . $triggerSource,
            'Dataset=' . $datasetKey
        ];

        if ($autoMerge && $successRows > 0) {
            if (accessSyncIsMasterDataset($datasetKey)) {
                $merge = accessSyncMergeMasterRun($koneksi, $datasetKey, $runId);
                if (!$merge['success']) {
                    throw new RuntimeException($merge['message']);
                }
                $mergeMeta['merge_status'] = 'success';
                $mergeMeta['merge_processed'] = (int) ($merge['processed'] ?? 0);
                $mergeMeta['merge_inserted'] = (int) ($merge['inserted'] ?? 0);
                $mergeMeta['merge_updated'] = (int) ($merge['updated'] ?? 0);
                $notesParts[] = 'Merge=' . $mergeMeta['merge_processed'] . ' row';
            } elseif (accessSyncIsTransactionDataset($datasetKey)) {
                $merge = accessSyncMergeTransactionRun($koneksi, $datasetKey, $runId);
                if (!$merge['success']) {
                    throw new RuntimeException($merge['message']);
                }
                $mergeMeta['merge_status'] = 'success';
                $mergeMeta['merge_processed'] = (int) ($merge['processed'] ?? 0);
                $mergeMeta['merge_upserted'] = (int) ($merge['upserted'] ?? 0);
                $notesParts[] = 'Merge=' . $mergeMeta['merge_processed'] . ' row';
            }
        }

        $status = $failedRows > 0 ? ($successRows > 0 ? 'partial' : 'failed') : 'success';
        accessSyncFinishRun($koneksi, $runId, $status, $totalRows, $successRows, $failedRows, implode('. ', $notesParts), $mergeMeta);
        mysqli_commit($koneksi);

        return [
            'success' => true,
            'run_id' => $runId,
            'dataset' => $datasetKey,
            'status' => $status,
            'summary' => [
                'total' => $totalRows,
                'success' => $successRows,
                'failed' => $failedRows,
            ],
            'merge' => $mergeMeta
        ];
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function accessSyncUpsertCabangRow($koneksi, $row) {
    $rawCode = trim((string) ($row['kode_cabang'] ?? $row['kode'] ?? ''));
    $rawName = trim((string) ($row['nama_cabang'] ?? $row['cabang'] ?? ''));
    if ($rawCode === '' && $rawName === '') {
        return ['success' => false, 'message' => 'Kode dan nama cabang kosong'];
    }

    $canonicalCode = accessSyncNormalizeCabangCode($rawCode !== '' ? $rawCode : $rawName);
    $referenceMap = accessSyncCabangReferenceMap();
    $reference = isset($referenceMap[$canonicalCode]) ? $referenceMap[$canonicalCode] : null;
    $namaCabang = $rawName !== '' ? $rawName : ($reference['name'] ?? $canonicalCode);
    $refCode = $reference['reference_code'] ?? preg_replace('/\D+/', '', $rawCode);

    $stmt = mysqli_prepare($koneksi, "SELECT kode_cabang FROM tbcabang WHERE kode_cabang = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $canonicalCode);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_stmt_get_result($stmt);
    $found = mysqli_fetch_assoc($exists);
    mysqli_stmt_close($stmt);

    if ($found) {
        $stmt = mysqli_prepare($koneksi, "UPDATE tbcabang SET cabang_ref_kode = ?, nama_cabang = ? WHERE kode_cabang = ?");
        mysqli_stmt_bind_param($stmt, 'sss', $refCode, $namaCabang, $canonicalCode);
        accessSyncExecuteStatementOrThrow($stmt);
        mysqli_stmt_close($stmt);
        return ['success' => true, 'action' => 'updated', 'kode_cabang' => $canonicalCode];
    }

    $perusahaanId = 1;
    $entryYear = date('Y');
    $entryMonth = date('m');
    $tipeCabang = 'CABANG';
    $empty = '';
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tbcabang (kode_cabang, cabang_ref_kode, perusahaan_id, nama_cabang, entry_year, entry_month, alamat_cabang, google_maps_cabang, lat_cabang, long_cabang, tipe_cabang) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssissssssss', $canonicalCode, $refCode, $perusahaanId, $namaCabang, $entryYear, $entryMonth, $empty, $empty, $empty, $empty, $tipeCabang);
    accessSyncExecuteStatementOrThrow($stmt);
    mysqli_stmt_close($stmt);
    return ['success' => true, 'action' => 'inserted', 'kode_cabang' => $canonicalCode];
}

function accessSyncRunCabangSync($koneksi, $rows, $options = []) {
    accessSyncEnsureRuntimeSchema($koneksi);

    if (!is_array($rows) || empty($rows)) {
        return ['success' => false, 'message' => 'Payload rows kosong'];
    }

    mysqli_begin_transaction($koneksi);
    try {
        $runId = accessSyncCreateRun(
            $koneksi,
            $options['source_name'] ?? 'Master Cabang',
            $options['source_file'] ?? 'api-cabang.json',
            $options['sync_mode'] ?? 'incremental',
            [
                'dataset_key' => 'cabang',
                'trigger_source' => $options['trigger_source'] ?? 'api_auto',
                'source_cabang' => '',
                'machine_name' => $options['machine_name'] ?? php_uname('n'),
                'batch_key' => $options['batch_key'] ?? '',
                'request_ip' => $options['request_ip'] ?? '',
            ]
        );

        $processed = 0;
        $inserted = 0;
        $updated = 0;
        $failed = 0;
        foreach ($rows as $row) {
            $processed++;
            $result = accessSyncUpsertCabangRow($koneksi, (array) $row);
            if (!$result['success']) {
                $failed++;
                accessSyncLogError($koneksi, $runId, 'cabang', '', $result['message'], $row);
                continue;
            }
            if ($result['action'] === 'inserted') {
                $inserted++;
            } else {
                $updated++;
            }
        }

        $status = $failed > 0 ? (($inserted + $updated) > 0 ? 'partial' : 'failed') : 'success';
        accessSyncFinishRun($koneksi, $runId, $status, $processed, $inserted + $updated, $failed, 'Dataset=cabang. Trigger=api_auto', [
            'merge_status' => 'success',
            'merge_processed' => $processed,
            'merge_inserted' => $inserted,
            'merge_updated' => $updated,
            'merge_upserted' => $inserted + $updated,
        ]);
        mysqli_commit($koneksi);

        return [
            'success' => true,
            'run_id' => $runId,
            'dataset' => 'cabang',
            'status' => $status,
            'summary' => [
                'total' => $processed,
                'success' => $inserted + $updated,
                'failed' => $failed,
            ],
            'merge' => [
                'merge_status' => 'success',
                'merge_processed' => $processed,
                'merge_inserted' => $inserted,
                'merge_updated' => $updated,
                'merge_upserted' => $inserted + $updated,
            ],
        ];
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function accessSyncMonitorAutoWhereSql() {
    return "(sync_mode <> 'manual_upload' OR COALESCE(trigger_source, '') IN ('api_auto', 'api_manual', 'scheduler', 'cli_test'))";
}

function accessSyncMonitorStatusInfo($lastRunAt, $lastStatus = '') {
    if (empty($lastRunAt)) {
        return ['code' => 'offline', 'label' => 'Belum sync'];
    }

    $minutes = (time() - strtotime($lastRunAt)) / 60;
    if ((string) $lastStatus === 'failed') {
        return ['code' => 'failed', 'label' => 'Gagal terakhir'];
    }
    if ($minutes <= 30) {
        return ['code' => 'online', 'label' => 'Online'];
    }
    if ($minutes <= 120) {
        return ['code' => 'warning', 'label' => 'Terlambat'];
    }
    return ['code' => 'offline', 'label' => 'Offline'];
}

function accessSyncFetchMonitorSummary($koneksi) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $sql = "SELECT
                COUNT(*) AS total_runs,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS total_success,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) AS total_partial,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS total_failed,
                SUM(CASE WHEN started_at >= NOW() - INTERVAL 24 HOUR THEN 1 ELSE 0 END) AS runs_24h,
                SUM(CASE WHEN started_at >= NOW() - INTERVAL 24 HOUR THEN success_rows ELSE 0 END) AS rows_success_24h,
                SUM(CASE WHEN started_at >= NOW() - INTERVAL 24 HOUR THEN failed_rows ELSE 0 END) AS rows_failed_24h,
                MAX(COALESCE(finished_at, started_at)) AS last_sync_at
            FROM sync_access_runs
            WHERE " . accessSyncMonitorAutoWhereSql();
    $result = mysqli_query($koneksi, $sql);
    return $result ? mysqli_fetch_assoc($result) : [];
}

function accessSyncFetchMonitorDatasetSummary($koneksi, $limit = 20) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $limit = (int) $limit;
    if ($limit <= 0) {
        $limit = 20;
    }

    $sql = "SELECT
                COALESCE(dataset_key, source_name) AS dataset_key,
                COUNT(*) AS total_runs,
                SUM(success_rows) AS success_rows,
                SUM(failed_rows) AS failed_rows,
                SUM(merge_inserted) AS merge_inserted,
                SUM(merge_updated) AS merge_updated,
                SUM(merge_upserted) AS merge_upserted,
                MAX(COALESCE(finished_at, started_at)) AS last_sync_at,
                SUBSTRING_INDEX(GROUP_CONCAT(status ORDER BY id DESC SEPARATOR ','), ',', 1) AS last_status
            FROM sync_access_runs
            WHERE " . accessSyncMonitorAutoWhereSql() . "
            GROUP BY COALESCE(dataset_key, source_name)
            ORDER BY MAX(COALESCE(finished_at, started_at)) DESC
            LIMIT {$limit}";
    $result = mysqli_query($koneksi, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['dataset_label'] = accessSyncDatasetLabel($row['dataset_key']);
            $rows[] = $row;
        }
    }
    return $rows;
}

function accessSyncFetchMonitorCabangSummary($koneksi, $limit = 20) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $limit = (int) $limit;
    if ($limit <= 0) {
        $limit = 20;
    }

    $sql = "SELECT
                r.source_cabang,
                COALESCE(c.nama_cabang, r.source_cabang) AS nama_cabang,
                COUNT(*) AS total_runs,
                SUM(r.success_rows) AS success_rows,
                SUM(r.failed_rows) AS failed_rows,
                MAX(COALESCE(r.finished_at, r.started_at)) AS last_sync_at,
                SUBSTRING_INDEX(GROUP_CONCAT(r.status ORDER BY r.id DESC SEPARATOR ','), ',', 1) AS last_status
            FROM sync_access_runs r
            LEFT JOIN tbcabang c
                ON c.kode_cabang COLLATE utf8mb4_unicode_ci = r.source_cabang COLLATE utf8mb4_unicode_ci
            WHERE " . accessSyncMonitorAutoWhereSql() . " AND COALESCE(r.source_cabang, '') <> ''
            GROUP BY r.source_cabang, COALESCE(c.nama_cabang, r.source_cabang)
            ORDER BY MAX(COALESCE(r.finished_at, r.started_at)) DESC
            LIMIT {$limit}";
    $result = mysqli_query($koneksi, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function accessSyncFetchRecentRuns($koneksi, $limit = 50) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $limit = (int) $limit;
    if ($limit <= 0) {
        $limit = 50;
    }

    $sql = "SELECT id, source_name, dataset_key, source_file, sync_mode, trigger_source, source_cabang, machine_name, started_at, finished_at, status, total_rows, success_rows, failed_rows, merge_status, merge_processed, merge_inserted, merge_updated, merge_upserted, notes
            FROM sync_access_runs
            WHERE " . accessSyncMonitorAutoWhereSql() . "
            ORDER BY id DESC
            LIMIT {$limit}";
    $result = mysqli_query($koneksi, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['dataset_label'] = accessSyncDatasetLabel($row['dataset_key']);
            $rows[] = $row;
        }
    }
    return $rows;
}

function accessSyncFetchRecentErrors($koneksi, $limit = 30) {
    accessSyncEnsureRuntimeSchema($koneksi);

    $limit = (int) $limit;
    if ($limit <= 0) {
        $limit = 30;
    }

    $sql = "SELECT e.id, e.sync_run_id, e.dataset_name, e.row_key, e.error_message, e.created_at,
                   r.source_cabang, r.machine_name, r.status
            FROM sync_access_row_errors e
            INNER JOIN sync_access_runs r ON r.id = e.sync_run_id
            WHERE " . accessSyncMonitorAutoWhereSql() . "
            ORDER BY e.id DESC
            LIMIT {$limit}";
    $result = mysqli_query($koneksi, $sql);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['dataset_label'] = accessSyncDatasetLabel($row['dataset_name']);
            $rows[] = $row;
        }
    }
    return $rows;
}
