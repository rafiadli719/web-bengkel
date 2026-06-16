<?php
/**
 * AJAX Endpoint untuk validasi dan simpan Penjualan Antar Cabang dari upload Excel
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include "../../config/koneksi.php";

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';

function getAntarCabangSetting($koneksi, $kd_cabang_asal, $tipe_cabang_tujuan) {
    $kd_cabang_asal = mysqli_real_escape_string($koneksi, (string)$kd_cabang_asal);
    $tipe_cabang_tujuan = mysqli_real_escape_string($koneksi, (string)$tipe_cabang_tujuan);

    $setting = [
        'diskon_persen' => 0.0,
        'margin_persen' => 0.0,
        'tempo_hari' => 0,
        'cara_bayar' => 'Tunai',
    ];

    $q_setting = mysqli_query(
        $koneksi,
        "SELECT * FROM tbl_setting_antarcabang
         WHERE aktif='1'
           AND (kd_cabang='$kd_cabang_asal' OR kd_cabang='' OR kd_cabang IS NULL)
           AND (tipe_cabang_tujuan='$tipe_cabang_tujuan' OR tipe_cabang_tujuan='' OR tipe_cabang_tujuan IS NULL)
         ORDER BY (kd_cabang='$kd_cabang_asal') DESC, (tipe_cabang_tujuan='$tipe_cabang_tujuan') DESC, id DESC
         LIMIT 1"
    );

    if ($q_setting && mysqli_num_rows($q_setting) > 0) {
        $r = mysqli_fetch_assoc($q_setting);
        if (isset($r['diskon_persen'])) {
            $setting['diskon_persen'] = (float)$r['diskon_persen'];
        }
        if (isset($r['margin_persen'])) {
            $setting['margin_persen'] = (float)$r['margin_persen'];
        }
        if (isset($r['tempo_hari'])) {
            $setting['tempo_hari'] = (int)$r['tempo_hari'];
        } elseif (isset($r['syarat_hari_default'])) {
            $setting['tempo_hari'] = (int)$r['syarat_hari_default'];
        }
        if (isset($r['cara_bayar']) && $r['cara_bayar'] !== '') {
            $setting['cara_bayar'] = $r['cara_bayar'];
        }
    } else {
        if ((string)$tipe_cabang_tujuan === '1') {
            $setting['diskon_persen'] = 100.0;
            $setting['margin_persen'] = 0.0;
            $setting['tempo_hari'] = 0;
            $setting['cara_bayar'] = 'Tunai';
        } else {
            $setting['diskon_persen'] = 0.0;
            $setting['margin_persen'] = 5.0;
            $setting['tempo_hari'] = 10;
            $setting['cara_bayar'] = 'Kredit';
        }
    }

    if (strtolower((string)$setting['cara_bayar']) !== 'kredit') {
        $setting['tempo_hari'] = 0;
    }

    return $setting;
}

function columnExists($koneksi, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $table_esc = mysqli_real_escape_string($koneksi, (string)$table);
    $column_esc = mysqli_real_escape_string($koneksi, (string)$column);
    $q = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table_esc` LIKE '$column_esc'");
    $cache[$key] = ($q && mysqli_num_rows($q) > 0);
    return $cache[$key];
}

// Get user name
$user_query = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = $user_query ? mysqli_fetch_array($user_query) : null;
$nama_user = $user_data ? $user_data['nama_user'] : 'System';

// Get cabang info
$cab_query = mysqli_query($koneksi, "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
$cab_data = $cab_query ? mysqli_fetch_array($cab_query) : null;
$tipe_cabang_asal = $cab_data ? $cab_data['tipe_cabang'] : '1';

try {
    switch ($action) {
        case 'get_setting':
            $cabang_tujuan = isset($_POST['cabang_tujuan']) ? mysqli_real_escape_string($koneksi, $_POST['cabang_tujuan']) : '';
            if (empty($cabang_tujuan)) {
                echo json_encode(['success' => false, 'error' => 'Cabang tujuan harus dipilih']);
                exit;
            }

            $cab_tujuan_query = mysqli_query($koneksi, "SELECT tipe_cabang FROM tbcabang WHERE kode_cabang='$cabang_tujuan'");
            $cab_tujuan_data = mysqli_fetch_array($cab_tujuan_query);
            $tipe_cabang_tujuan = $cab_tujuan_data ? $cab_tujuan_data['tipe_cabang'] : '2';

            $setting = getAntarCabangSetting($koneksi, $kd_cabang, $tipe_cabang_tujuan);

            echo json_encode([
                'success' => true,
                'tipe_cabang_tujuan' => $tipe_cabang_tujuan,
                'setting' => $setting,
            ]);
            break;

        case 'validate':
            // Validate items from Excel
            $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
            $cabang_tujuan = isset($_POST['cabang_tujuan']) ? mysqli_real_escape_string($koneksi, $_POST['cabang_tujuan']) : '';

            if (empty($items)) {
                echo json_encode(['success' => false, 'error' => 'Tidak ada data item']);
                exit;
            }

            if (empty($cabang_tujuan)) {
                echo json_encode(['success' => false, 'error' => 'Cabang tujuan harus dipilih']);
                exit;
            }

            // Get cabang tujuan info for pricing
            $cab_tujuan_query = mysqli_query($koneksi, "SELECT tipe_cabang FROM tbcabang WHERE kode_cabang='$cabang_tujuan'");
            $cab_tujuan_data = mysqli_fetch_array($cab_tujuan_query);
            $tipe_cabang_tujuan = $cab_tujuan_data ? $cab_tujuan_data['tipe_cabang'] : '2';

            $setting = getAntarCabangSetting($koneksi, $kd_cabang, $tipe_cabang_tujuan);
            $margin_persen = (float)$setting['margin_persen'];

            $result = [];
            $summary = ['total' => count($items), 'ok' => 0, 'warning' => 0, 'error' => 0];

            foreach ($items as $item) {
                $kode_input = isset($item['kode']) ? (string)$item['kode'] : '';
                $kode_clean = strtoupper(trim($kode_input));
                $kode_clean = preg_replace('/\s+/', '', $kode_clean);
                $kode_clean = preg_replace('/[^\x20-\x7E]/', '', $kode_clean);
                $kode = mysqli_real_escape_string($koneksi, $kode_clean);

                $nama = isset($item['nama']) ? $item['nama'] : '';
                $qty = (int)$item['qty'];
                $harga = (float)$item['harga'];

                // Check if item exists in database
                $check_query = mysqli_query($koneksi, "SELECT
                        i.noitem,
                        i.namaitem,
                        i.hargapokok,
                        COALESCE(st.saldo, i.quantity, 0) AS stok
                    FROM tblitem i
                    LEFT JOIN (
                        SELECT no_item, SUM(masuk - keluar) AS saldo
                        FROM tbstok
                        WHERE kd_cabang = '$kd_cabang'
                        GROUP BY no_item
                    ) st ON st.no_item = i.noitem
                    WHERE (i.noitem = '$kode' OR i.kodebarcode = '$kode')
                      AND (i.statusitem = '1' OR i.statusitem IS NULL OR i.statusitem = '')
                    LIMIT 1");

                if (!$check_query) {
                    $result[] = [
                        'kode' => $kode_clean,
                        'nama' => $nama,
                        'qty' => $qty,
                        'harga' => $harga,
                        'hpp' => 0,
                        'stok' => 0,
                        'status' => 'ERROR',
                        'message' => 'Query item gagal: ' . mysqli_error($koneksi)
                    ];
                    $summary['error']++;
                    continue;
                }

                if ($check_query && mysqli_num_rows($check_query) > 0) {
                    $db_item = mysqli_fetch_assoc($check_query);
                    $hpp = (float)$db_item['hargapokok'];
                    $stok = (int)$db_item['stok'];

                    // Calculate price with margin
                    if ($harga <= 0) {
                        $harga = $hpp * ((100 + $margin_persen) / 100);
                    }

                    // Check stock availability
                    $status = 'OK';
                    $message = '';

                    if ($qty > $stok) {
                        $status = 'WARNING';
                        $message = "Stok tersedia: $stok";
                        $summary['warning']++;
                    } else {
                        $summary['ok']++;
                    }

                    $result[] = [
                        'kode' => $db_item['noitem'],
                        'nama' => $db_item['namaitem'],
                        'qty' => $qty,
                        'harga' => $harga,
                        'hpp' => $hpp,
                        'stok' => $stok,
                        'status' => $status,
                        'message' => $message
                    ];
                } else {
                    // Item not found
                    $result[] = [
                        'kode' => $kode_clean,
                        'nama' => $nama,
                        'qty' => $qty,
                        'harga' => $harga,
                        'hpp' => 0,
                        'stok' => 0,
                        'status' => 'ERROR',
                        'message' => 'Item tidak ditemukan (cek Kode Barang / Barcode)'
                    ];
                    $summary['error']++;
                }

                // Validate qty
                if ($qty <= 0) {
                    $result[count($result) - 1]['status'] = 'ERROR';
                    $result[count($result) - 1]['message'] = 'Qty harus lebih dari 0';
                    // Adjust counters
                    if ($result[count($result) - 1]['status'] == 'OK') {
                        $summary['ok']--;
                        $summary['error']++;
                    } elseif ($result[count($result) - 1]['status'] == 'WARNING') {
                        $summary['warning']--;
                        $summary['error']++;
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $result,
                'summary' => $summary,
                'tipe_cabang_tujuan' => $tipe_cabang_tujuan,
                'setting' => $setting
            ]);
            break;

        case 'save':
            // Save validated items to Pesanan Penjualan
            $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
            $cabang_tujuan = isset($_POST['cabang_tujuan']) ? mysqli_real_escape_string($koneksi, $_POST['cabang_tujuan']) : '';
            $tanggal_input = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('d/m/Y');
            $note = isset($_POST['note']) ? mysqli_real_escape_string($koneksi, $_POST['note']) : '';

            // Convert date format
            if (strpos($tanggal_input, '/') !== false) {
                $parts = explode('/', $tanggal_input);
                $tanggal = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            } else {
                $tanggal = $tanggal_input;
            }

            if (empty($items)) {
                echo json_encode(['success' => false, 'error' => 'Tidak ada data item']);
                exit;
            }

            if (empty($cabang_tujuan)) {
                echo json_encode(['success' => false, 'error' => 'Cabang tujuan harus dipilih']);
                exit;
            }

            // Generate Order number: PJ + YY + 9 digit sequence
            $prefix = "PJ" . date('y');
            $last_query = mysqli_query($koneksi, "SELECT no_order FROM tblorderjual_header WHERE no_order LIKE '$prefix%' ORDER BY no_order DESC LIMIT 1");
            if ($last_query && mysqli_num_rows($last_query) > 0) {
                $last_row = mysqli_fetch_assoc($last_query);
                $last_num = (int)substr($last_row['no_order'], 4);
                $new_num = $last_num + 1;
            } else {
                $new_num = 1;
            }
            $no_order = $prefix . str_pad($new_num, 9, '0', STR_PAD_LEFT);

            // Start transaction
            mysqli_begin_transaction($koneksi);

            try {
                // Calculate totals
                $total_qty = 0;
                $total_jual = 0;
                $total_diskon = 0;
                $total_akhir = 0;

                // Get cabang tujuan info
                $cab_tujuan_query = mysqli_query($koneksi, "SELECT tipe_cabang, nama_cabang FROM tbcabang WHERE kode_cabang='$cabang_tujuan'");
                $cab_tujuan_data = mysqli_fetch_array($cab_tujuan_query);
                $tipe_cabang_tujuan = $cab_tujuan_data ? $cab_tujuan_data['tipe_cabang'] : '2';

                $no_pelanggan = '';
                $q_pel = mysqli_query($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan='$cabang_tujuan' LIMIT 1");
                if($q_pel && mysqli_num_rows($q_pel) > 0){
                    $r_pel = mysqli_fetch_array($q_pel);
                    $no_pelanggan = $r_pel['nopelanggan'];
                } else {
                    $nama_cabang_tujuan = $cab_tujuan_data ? mysqli_real_escape_string($koneksi, (string)$cab_tujuan_data['nama_cabang']) : '';
                    if($nama_cabang_tujuan !== ''){
                        $q_pel = mysqli_query($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE namapelanggan LIKE '%$nama_cabang_tujuan%' LIMIT 1");
                        if($q_pel && mysqli_num_rows($q_pel) > 0){
                            $r_pel = mysqli_fetch_array($q_pel);
                            $no_pelanggan = $r_pel['nopelanggan'];
                        }
                    }
                }

                if($no_pelanggan === ''){
                    throw new Exception("Pelanggan untuk cabang tujuan '$cabang_tujuan' belum ada. Silakan buat pelanggan dengan kode '$cabang_tujuan'.");
                }

                $setting = getAntarCabangSetting($koneksi, $kd_cabang, $tipe_cabang_tujuan);
                $cara_bayar = $setting['cara_bayar'];
                $syarat_hari = (int)$setting['tempo_hari'];
                $diskon_persen = (float)$setting['diskon_persen'];

                $table_hdr = 'tblorderjual_header';
                $cols = [];
                $vals = [];

                $add = function($col, $val) use (&$cols, &$vals, $koneksi, $table_hdr) {
                    if (!columnExists($koneksi, $table_hdr, $col)) {
                        return;
                    }
                    $cols[] = $col;
                    $vals[] = $val;
                };

                $add('no_order', "'$no_order'");
                $add('status', "'0'");
                $add('tanggal', "'$tanggal'");
                $add('no_sales', "''");
                $add('no_pelanggan', "'$no_pelanggan'");
                $add('note', "'$note'");
                $add('total_qty', "'0'");
                $add('total_terima', "'0'");
                $add('total_jual', "'0'");
                $add('diskon', "'$diskon_persen'");
                $add('total_diskon', "'0'");
                $add('pajak', "'0'");
                $add('total_pajak', "'0'");
                $add('total_akhir', "'0'");
                $add('pembayaran', "'0'");
                $add('user', "'$nama_user'");
                $add('id_tabel', "''");
                $add('kd_cabang', "'$kd_cabang'");

                $add('tipe_trx', "'Antar Cabang'");
                $add('tipe_transaksi', "'ANTAR_CABANG'");
                $add('order_ke', "'$cabang_tujuan'");
                $add('kd_cabang_tujuan', "'$cabang_tujuan'");

                if (empty($cols)) {
                    throw new Exception('Struktur tabel header tidak sesuai (kolom tidak ditemukan).');
                }

                $sql_header = "INSERT INTO $table_hdr (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";

                if (!mysqli_query($koneksi, $sql_header)) {
                    throw new Exception('Gagal menyimpan header: ' . mysqli_error($koneksi));
                }

                // Insert details
                $nobaris = 0;
                foreach ($items as $item) {
                    if ($item['status'] === 'ERROR') continue;

                    $kode = mysqli_real_escape_string($koneksi, $item['kode']);
                    $qty = (int)$item['qty'];
                    $harga = (float)$item['harga'];
                    $hpp = isset($item['hpp']) ? (float)$item['hpp'] : 0;
                    $subtotal = $qty * $harga;
                    $diskon_nominal = ($subtotal * $diskon_persen) / 100;
                    $total_after_diskon = $subtotal - $diskon_nominal;
                    if ($total_after_diskon < 0) {
                        $total_after_diskon = 0;
                    }

                    $total_qty += $qty;
                    $total_jual += $subtotal;
                    $total_diskon += $diskon_nominal;
                    $total_akhir += $total_after_diskon;
                    $nobaris++;

                    $sql_detail = "INSERT INTO tblorderjual_detail
                        (no_order, nobaris, no_item, quantity, qty_terima, harga_jual, potongan, harga_sp, harga_pokok, total,
                         user, kd_cabang, status_trx, margin_jual)
                        VALUES
                        ('$no_order', '$nobaris', '$kode', '$qty', '0', '$harga', '$diskon_persen', '0', '$hpp', '$total_after_diskon',
                         '$nama_user', '$kd_cabang', '1', '0')";

                    if (!mysqli_query($koneksi, $sql_detail)) {
                        throw new Exception('Gagal menyimpan detail: ' . mysqli_error($koneksi));
                    }
                }

                // Update header totals using totals after diskon
                if (!mysqli_query($koneksi, "UPDATE tblorderjual_header SET total_qty='$total_qty', total_jual='$total_jual', diskon='$diskon_persen', total_diskon='$total_diskon', total_akhir='$total_akhir', pembayaran='0' WHERE no_order='$no_order'")) {
                    throw new Exception('Gagal update total header: ' . mysqli_error($koneksi));
                }

                mysqli_commit($koneksi);

                echo json_encode([
                    'success' => true,
                    'message' => 'Pesanan Penjualan Antar Cabang berhasil dibuat',
                    'no_order' => $no_order,
                    'total_item' => count(array_filter($items, function($i) { return $i['status'] !== 'ERROR'; })),
                    'total_qty' => $total_qty,
                    'total_order' => $total_akhir,
                    'tipe_cabang_tujuan' => $tipe_cabang_tujuan,
                    'setting' => $setting
                ]);

            } catch (Exception $e) {
                mysqli_rollback($koneksi);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
