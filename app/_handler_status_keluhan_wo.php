<?php
/**
 * Handler untuk Update Status Keluhan & Work Order
 * Include file ini di servis-input-*.php
 */

/**
 * Hitung diskon promo/member aktif untuk satu item pending, lalu insert
 * ke tblservis_barang/tblservis_jasa. Dipakai oleh approve single & bulk.
 */
function _insertApprovedServisItem($koneksi, $item, $no_service, $no_polisi_svc) {
    $tipe = $item['tipe'];
    $kode_item = $item['kode_item'];
    $quantity = (int)$item['quantity'];
    $harga_satuan = (float)$item['harga_satuan'];
    $waktu = (int)($item['waktu'] ?? 0);

    // Hitung diskon promo/member aktif untuk item ini
    $diskon_source = '';
    $diskon_persen = 0;
    $diskon_nominal = 0;
    $id_promo = 'NULL';
    if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
        $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $kode_item, $tipe, $harga_satuan);
        if(($disc['diskon_nominal'] ?? 0) > 0) {
            $diskon_persen = floatval($disc['diskon_persen']);
            $diskon_nominal = floatval($disc['diskon_nominal']);
            $diskon_source = (stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
            if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
            }
        }
    }

    if($tipe === 'barang') {
        $subtotal = ($harga_satuan * $quantity) - ($diskon_nominal * $quantity);
        if($subtotal < 0) { $subtotal = 0; }
        $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_barang WHERE no_service='$no_service'");
        $nb = $q_nb ? mysqli_fetch_assoc($q_nb)['n'] : 1;
        $ok = mysqli_query($koneksi, "INSERT INTO tblservis_barang (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('$no_service', '$nb', '$kode_item', '$quantity', 0, '$harga_satuan', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
        if($ok && $diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'barang', $kode_item); }
        return $ok;
    }

    // jasa
    $subtotal = $harga_satuan - $diskon_nominal;
    if($subtotal < 0) { $subtotal = 0; }
    $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_jasa WHERE no_service='$no_service'");
    $nb = $q_nb ? mysqli_fetch_assoc($q_nb)['n'] : 1;
    $has_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
    if($has_waktu && mysqli_num_rows($has_waktu) > 0) {
        $ok = mysqli_query($koneksi, "INSERT INTO tblservis_jasa (no_service, nobaris, no_item, harga, waktu, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('$no_service', '$nb', '$kode_item', '$harga_satuan', '$waktu', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
        if($ok && $diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'jasa', $kode_item); }
        return $ok;
    }
    $ok = mysqli_query($koneksi, "INSERT INTO tblservis_jasa (no_service, nobaris, no_item, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('$no_service', '$nb', '$kode_item', '$harga_satuan', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
    if($ok && $diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'jasa', $kode_item); }
    return $ok;
}

/**
 * Bangun teks catatan untuk satu item WO yang ditolak.
 * Dipakai oleh reject single & bulk.
 */
function _buildRejectServisItemNote($item, $alasan_tolak, $keterangan_tolak = '') {
    $tipe_readable = ($item['tipe'] == 'barang') ? 'Barang' : 'Jasa';
    $alasan_readable = str_replace('_', ' ', ucwords($alasan_tolak, '_'));
    $note = "$tipe_readable: {$item['nama_item']} - Alasan: $alasan_readable";
    if(!empty($keterangan_tolak)) {
        $note .= " - $keterangan_tolak";
    }
    return $note;
}

// ============================================
// HANDLER UPDATE STATUS KELUHAN
// ============================================

if(isset($_POST['btnupdatestatuskeluhan'])) {
    $keluhan_id = mysqli_real_escape_string($koneksi, $_POST['keluhan_id_status']);
    $status_pengerjaan = mysqli_real_escape_string($koneksi, $_POST['status_keluhan_update']);
    $keterangan = isset($_POST['keterangan_keluhan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan_keluhan']) : '';
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    
    // Validate: keterangan wajib jika status tidak_selesai
    if($status_pengerjaan == 'tidak_selesai' && empty($keterangan)) {
        echo "<script>alert('Keterangan wajib diisi untuk status Tidak Selesai!'); window.location.href='?snoserv=$no_service&tab=workorder-details';</script>";
        exit;
    }
    
    // Update status keluhan
    $query_update = "UPDATE tbservis_keluhan_status 
                    SET status_pengerjaan = '$status_pengerjaan',
                        keterangan_tidak_selesai = " . ($status_pengerjaan == 'tidak_selesai' ? "'$keterangan'" : "NULL") . "
                    WHERE id = '$keluhan_id'";
    
    if(mysqli_query($koneksi, $query_update)) {
        // Jika status tidak_selesai, tambahkan ke catatan servis
        if($status_pengerjaan == 'tidak_selesai' && !empty($keterangan)) {
            // Get keluhan text
            $q_keluhan = mysqli_query($koneksi, "SELECT keluhan FROM tbservis_keluhan_status WHERE id = '$keluhan_id'");
            $keluhan_data = mysqli_fetch_array($q_keluhan);
            $keluhan_text = $keluhan_data['keluhan'];
            
            // Get current catatan
            $q_service = mysqli_query($koneksi, "SELECT catatan FROM tblservice WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'");
            $service_data = mysqli_fetch_array($q_service);
            $catatan_lama = $service_data['catatan'] ?? '';
            
            // Append new note
            $catatan_baru = $catatan_lama;
            if(!empty($catatan_lama)) {
                $catatan_baru .= "\n\n";
            }
            $catatan_baru .= "[KELUHAN TIDAK SELESAI] $keluhan_text: $keterangan";
            
            // Update catatan
            $catatan_escaped = mysqli_real_escape_string($koneksi, $catatan_baru);
            mysqli_query($koneksi, "UPDATE tblservice SET catatan = '$catatan_escaped' WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'");
        }
        
        echo "<script>alert('Status keluhan berhasil diupdate!'); window.location.href='?snoserv=$no_service&tab=workorder-details';</script>";
    } else {
        echo "<script>alert('Gagal update status keluhan: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=workorder-details';</script>";
    }
    exit;
}

if(isset($_POST['btnapprove_wo_bulk'])) {
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
    $ids = isset($_POST['selected_item_ids']) ? $_POST['selected_item_ids'] : [];
    $user = $_SESSION['username'] ?? $_SESSION['_nama'] ?? 'system';
    $ok = 0; $fail = 0;
    if(!is_array($ids)) { $ids = [$ids]; }

    // Ambil no_polisi untuk perhitungan diskon promo/member aktif
    $q_cust = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service'");
    $cust = $q_cust ? mysqli_fetch_assoc($q_cust) : null;
    $no_polisi_svc = $cust['no_polisi'] ?? '';

    foreach($ids as $rid) {
        $item_id = mysqli_real_escape_string($koneksi, $rid);
        if($item_id === '') { $fail++; continue; }
        $q = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE id='$item_id' AND no_service='$no_service' AND status_approval='pending'");
        if(!$q || mysqli_num_rows($q) == 0) { $fail++; continue; }
        $item = mysqli_fetch_array($q);
        if(!_insertApprovedServisItem($koneksi, $item, $no_service, $no_polisi_svc)) { $fail++; continue; }
        mysqli_query($koneksi, "UPDATE tbservis_pending_items SET status_approval='disetujui', approved_by='$user', approved_at=NOW() WHERE id='$item_id'");
        $ok++;
    }
    $msg = 'Bulk approve selesai. Berhasil: ' . $ok . ', Gagal: ' . $fail;
    echo "<script>alert('" . addslashes($msg) . "'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
    exit;
}

if(isset($_POST['btnreject_wo_bulk'])) {
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
    $ids = isset($_POST['selected_item_ids']) ? $_POST['selected_item_ids'] : [];
    $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan_reject'] ?? '');
    $ket = isset($_POST['keterangan_reject']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan_reject']) : '';
    $user = $_SESSION['username'] ?? $_SESSION['_nama'] ?? 'system';
    if(empty($alasan)) {
        echo "<script>alert('Alasan penolakan wajib dipilih.'); window.history.back();</script>"; exit;
    }
    if(!is_array($ids)) { $ids = [$ids]; }
    $ok = 0; $fail = 0; $notes = [];
    foreach($ids as $rid) {
        $item_id = mysqli_real_escape_string($koneksi, $rid);
        if($item_id === '') { $fail++; continue; }
        $q = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE id='$item_id' AND no_service='$no_service' AND status_approval='pending'");
        if(!$q || mysqli_num_rows($q) == 0) { $fail++; continue; }
        $item = mysqli_fetch_array($q);
        $upd = mysqli_query($koneksi, "UPDATE tbservis_pending_items SET status_approval='ditolak', alasan_tolak='$alasan', keterangan_tolak=" . (empty($ket) ? "NULL" : "'$ket'") . ", approved_by='$user', approved_at=NOW() WHERE id='$item_id'");
        if(!$upd) { $fail++; continue; }
        $notes[] = _buildRejectServisItemNote($item, $alasan, $ket);
        $ok++;
    }
    if($ok > 0) {
        $q_service = mysqli_query($koneksi, "SELECT catatan FROM tblservice WHERE no_service = '$no_service'");
        $service_data = mysqli_fetch_array($q_service);
        $catatan_lama = $service_data['catatan'] ?? '';
        $catatan_baru = $catatan_lama;
        if(!empty($catatan_lama)) { $catatan_baru .= "\n\n"; }
        $catatan_baru .= "[ITEM WO DITOLAK - BULK]\n- " . implode("\n- ", $notes);
        $catatan_escaped = mysqli_real_escape_string($koneksi, $catatan_baru);
        mysqli_query($koneksi, "UPDATE tblservice SET catatan = '$catatan_escaped' WHERE no_service = '$no_service'");
    }
    $msg = 'Bulk reject selesai. Ditolak: ' . $ok . ', Gagal: ' . $fail;
    echo "<script>alert('" . addslashes($msg) . "'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
    exit;
}

// ============================================
// HANDLER UPDATE STATUS WORK ORDER
// ============================================

if(isset($_POST['btnupdatestatuswo'])) {
    $wo_id = mysqli_real_escape_string($koneksi, $_POST['wo_id_status']);
    $status_pengerjaan = mysqli_real_escape_string($koneksi, $_POST['status_wo_update']);
    $keterangan = isset($_POST['keterangan_wo']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan_wo']) : '';
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    
    // Validate: keterangan wajib jika status tidak_selesai
    if($status_pengerjaan == 'tidak_selesai' && empty($keterangan)) {
        echo "<script>alert('Keterangan wajib diisi untuk status Tidak Selesai!'); window.location.href='?snoserv=$no_service&tab=workorder-details';</script>";
        exit;
    }
    
    // Update status work order
    $query_update = "UPDATE tbservis_workorder 
                    SET status_pengerjaan = '$status_pengerjaan',
                        keterangan_tidak_selesai = " . ($status_pengerjaan == 'tidak_selesai' ? "'$keterangan'" : "NULL") . "
                    WHERE id = '$wo_id'";
    
    if(mysqli_query($koneksi, $query_update)) {
        // Jika status tidak_selesai, tambahkan ke catatan servis
        if($status_pengerjaan == 'tidak_selesai' && !empty($keterangan)) {
            // Get work order name
            $q_wo = mysqli_query($koneksi, "
                SELECT wh.nama_wo 
                FROM tbservis_workorder sw
                LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                WHERE sw.id = '$wo_id'
            ");
            $wo_data = mysqli_fetch_array($q_wo);
            $nama_wo = $wo_data['nama_wo'];
            
            // Get current catatan
            $q_service = mysqli_query($koneksi, "SELECT catatan FROM tblservice WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'");
            $service_data = mysqli_fetch_array($q_service);
            $catatan_lama = $service_data['catatan'] ?? '';
            
            // Append new note
            $catatan_baru = $catatan_lama;
            if(!empty($catatan_lama)) {
                $catatan_baru .= "\n\n";
            }
            $catatan_baru .= "[WORK ORDER TIDAK SELESAI] $nama_wo: $keterangan";
            
            // Update catatan
            $catatan_escaped = mysqli_real_escape_string($koneksi, $catatan_baru);
            mysqli_query($koneksi, "UPDATE tblservice SET catatan = '$catatan_escaped' WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'");
        }
        
        echo "<script>alert('Status work order berhasil diupdate!'); window.location.href='?snoserv=$no_service&tab=workorder-details';</script>";
    } else {
        echo "<script>alert('Gagal update status work order: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=workorder-details';</script>";
    }
    exit;
}

// ============================================
// HANDLER APPROVE WO ITEM (Barang/Jasa)
// ============================================

if(isset($_POST['btnsetujuiitem'])) {
    $item_id = mysqli_real_escape_string($koneksi, $_POST['item_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $user = $_SESSION['username'] ?? $_SESSION['_nama'] ?? 'system';
    
    // Get item data
    $query_item = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE id = '$item_id'");
    $item = mysqli_fetch_array($query_item);
    
    if($item) {
        $tipe_readable = ($item['tipe'] == 'barang') ? 'barang' : 'jasa';

        // Ambil no_polisi untuk perhitungan diskon promo/member aktif
        $q_cust = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service'");
        $cust = $q_cust ? mysqli_fetch_assoc($q_cust) : null;
        $no_polisi_svc = $cust['no_polisi'] ?? '';

        if(_insertApprovedServisItem($koneksi, $item, $no_service, $no_polisi_svc)) {
            // Update status
            mysqli_query($koneksi, "UPDATE tbservis_pending_items
                                   SET status_approval='disetujui',
                                       approved_by='$user',
                                       approved_at=NOW()
                                   WHERE id='$item_id'");

            echo "<script>alert('Item $tipe_readable berhasil disetujui dan ditambahkan ke service!'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan item $tipe_readable: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
        }
    } else {
        echo "<script>alert('Item tidak ditemukan!'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
    }
    exit;
}

// ============================================
// HANDLER REJECT WO ITEM (Barang/Jasa)
// ============================================

if(isset($_POST['btntolakitem'])) {
    $item_id = mysqli_real_escape_string($koneksi, $_POST['item_id']);
    $alasan_tolak = mysqli_real_escape_string($koneksi, $_POST['alasan_tolak']);
    $keterangan_tolak = isset($_POST['keterangan_tolak']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan_tolak']) : '';
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $user = $_SESSION['username'] ?? $_SESSION['_nama'] ?? 'system';
    
    // Validate alasan
    if(empty($alasan_tolak)) {
        echo "<script>alert('Alasan penolakan harus dipilih!'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
        exit;
    }
    
    // Get item info for catatan
    $query_item = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE id = '$item_id'");
    $item = mysqli_fetch_array($query_item);
    
    if($item) {
        // Update status
        $query_reject = "UPDATE tbservis_pending_items 
                        SET status_approval = 'ditolak',
                            alasan_tolak = '$alasan_tolak',
                            keterangan_tolak = " . (empty($keterangan_tolak) ? "NULL" : "'$keterangan_tolak'") . ",
                            approved_by = '$user',
                            approved_at = NOW()
                        WHERE id = '$item_id'";
        
        if(mysqli_query($koneksi, $query_reject)) {
            // Tambahkan ke catatan servis
            $tipe_readable = ($item['tipe'] == 'barang') ? 'Barang' : 'Jasa';
            $alasan_readable = str_replace('_', ' ', ucwords($alasan_tolak, '_'));
            
            $q_service = mysqli_query($koneksi, "SELECT catatan FROM tblservice WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'");
            $service_data = mysqli_fetch_array($q_service);
            $catatan_lama = $service_data['catatan'] ?? '';
            
            $catatan_baru = $catatan_lama;
            if(!empty($catatan_lama)) {
                $catatan_baru .= "\n\n";
            }
            
            $catatan_baru .= "[ITEM WO DITOLAK] $tipe_readable: {$item['nama_item']} - Alasan: $alasan_readable";
            if(!empty($keterangan_tolak)) {
                $catatan_baru .= " - $keterangan_tolak";
            }
            
            $catatan_escaped = mysqli_real_escape_string($koneksi, $catatan_baru);
            mysqli_query($koneksi, "UPDATE tblservice SET catatan = '$catatan_escaped' WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'");
            
            echo "<script>alert('Item ditolak dan dicatat!'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
        } else {
            echo "<script>alert('Gagal menolak item: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
        }
    } else {
        echo "<script>alert('Item tidak ditemukan!'); window.location.href='?snoserv=$no_service&tab=temuan-penawaran';</script>";
    }
    exit;
}
?>
