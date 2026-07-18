<?php
// File: helper-functions.php
// Helper functions untuk sistem tracking keluhan dan manajemen service

// F2-C: Diskon manual level-invoice (txtpotfaktur) butuh approval Supervisor/Manager sebelum bayar bisa diproses.
// Diskon item-level via diskon_source (member/promo) TIDAK kena guard ini — sudah sesuai SOP otomatis.
// Return true = boleh lanjut bayar. Return false = payment harus diblok (caller wajib exit).
if (!function_exists('checkDiskonApproval')) {
    function checkDiskonApproval($koneksi, $no_service, $persen_diskon, $nominal_diskon, $id_user, $kd_cabang) {
        $persen_diskon = (float)$persen_diskon;
        $nominal_diskon = (float)$nominal_diskon;
        if ($persen_diskon <= 0 && $nominal_diskon <= 0) {
            return true; // tidak ada diskon manual, tidak butuh approval
        }
        $ns = mysqli_real_escape_string($koneksi, $no_service);
        $chk_approved = mysqli_query($koneksi, "SELECT id FROM tb_approval_diskon WHERE no_service='$ns' AND status='approved' ORDER BY id DESC LIMIT 1");
        if ($chk_approved && mysqli_num_rows($chk_approved) > 0) {
            return true; // sudah di-approve supervisor sebelumnya
        }
        $chk_pending = mysqli_query($koneksi, "SELECT id FROM tb_approval_diskon WHERE no_service='$ns' AND status='pending' LIMIT 1");
        if ($chk_pending && mysqli_num_rows($chk_pending) > 0) {
            return false; // masih pending, jangan buat request duplikat
        }
        // Kalau request terakhir untuk no_service ini sudah ditolak dengan nilai diskon yang sama persis,
        // blok permanen tanpa bikin request baru otomatis (reject harus final, bukan bisa di-retry tanpa batas).
        $chk_rejected = mysqli_query($koneksi, "SELECT persen_diskon, nominal_diskon FROM tb_approval_diskon WHERE no_service='$ns' AND status='rejected' ORDER BY id DESC LIMIT 1");
        if ($chk_rejected && ($row_rej = mysqli_fetch_assoc($chk_rejected))) {
            if (abs((float)$row_rej['persen_diskon'] - $persen_diskon) < 0.01 && abs((float)$row_rej['nominal_diskon'] - $nominal_diskon) < 0.01) {
                return false; // diskon sama persis sudah ditolak, kasir wajib ubah nilai diskon buat request baru
            }
        }
        $id_user_esc = mysqli_real_escape_string($koneksi, $id_user);
        $kd_cabang_esc = mysqli_real_escape_string($koneksi, $kd_cabang);
        mysqli_query($koneksi, "INSERT INTO tb_approval_diskon
            (no_service, jenis, nominal_diskon, persen_diskon, status, id_user_cs, tanggal_request, kd_cabang)
            VALUES ('$ns', 'invoice', '$nominal_diskon', '$persen_diskon', 'pending', '$id_user_esc', NOW(), '$kd_cabang_esc')");
        return false; // request baru dibuat, blok pembayaran sampai di-approve
    }
}

// F2-A: DP/Down Payment servis mesin besar / part inden (Q9).
// Jawaban klarifikasi #3 (2026-07-04, Mba Dian): DP masuk & offset tampil 2 baris terpisah di laporan.
if (!function_exists('generateNoDP')) {
    function generateNoDP($koneksi, $kd_cabang) {
        $prefix = 'DP-' . date('Ymd') . '-';
        $q = mysqli_query($koneksi, "SELECT no_dp FROM tb_dp_servis WHERE no_dp LIKE '{$prefix}%' ORDER BY no_dp DESC LIMIT 1");
        $next = 1;
        if ($q && ($row = mysqli_fetch_assoc($q))) {
            $next = intval(substr($row['no_dp'], -4)) + 1;
        }
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}

// Total DP masih pending (belum di-offset/batal) untuk sebuah no_service.
if (!function_exists('getDpPendingTotal')) {
    function getDpPendingTotal($koneksi, $no_service) {
        $ns = mysqli_real_escape_string($koneksi, $no_service);
        $q = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_dp),0) AS total FROM tb_dp_servis WHERE no_service='$ns' AND status='pending'");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        return $row ? (float)$row['total'] : 0.0;
    }
}

// Tandai semua DP pending milik no_service sebagai offset (dipakai saat pelunasan).
if (!function_exists('offsetDpPending')) {
    function offsetDpPending($koneksi, $no_service) {
        $ns = mysqli_real_escape_string($koneksi, $no_service);
        mysqli_query($koneksi, "UPDATE tb_dp_servis SET status='offset', tanggal_offset=NOW() WHERE no_service='$ns' AND status='pending'");
    }
}

// Function untuk get progress keluhan
function getKeluhanProgress($koneksi, $keluhan_id) {
    $sql = mysqli_query($koneksi,"SELECT 
                                 COUNT(*) as total_proses,
                                 SUM(CASE WHEN status_proses = 'selesai' THEN 1 ELSE 0 END) as proses_selesai
                                 FROM tbservis_keluhan_tracking 
                                 WHERE keluhan_id = '$keluhan_id'");
    
    $data = mysqli_fetch_array($sql);
    
    if($data['total_proses'] > 0) {
        return [
            'total' => $data['total_proses'],
            'selesai' => $data['proses_selesai'],
            'progress' => round(($data['proses_selesai'] / $data['total_proses']) * 100)
        ];
    }
    
    return ['total' => 0, 'selesai' => 0, 'progress' => 0];
}

// Function untuk get service statistics
function getServiceStatistics($koneksi, $tgl_dari = null, $tgl_sampai = null, $cabang = null) {
    if(!$tgl_dari) $tgl_dari = date('Y-m-01');
    if(!$tgl_sampai) $tgl_sampai = date('Y-m-d');
    
    $where_conditions = ["DATE(s.tanggal) BETWEEN '$tgl_dari' AND '$tgl_sampai'"];
    if($cabang) {
        $where_conditions[] = "s.kd_cabang = '$cabang'";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $sql = mysqli_query($koneksi,"SELECT 
                                 COUNT(DISTINCT s.no_service) as total_service,
                                 COUNT(DISTINCT k.id) as total_keluhan,
                                 SUM(CASE WHEN k.status_pengerjaan = 'selesai' THEN 1 ELSE 0 END) as keluhan_selesai,
                                 SUM(CASE WHEN k.status_pengerjaan = 'diproses' THEN 1 ELSE 0 END) as keluhan_diproses,
                                 SUM(CASE WHEN k.status_pengerjaan = 'tidak_selesai' THEN 1 ELSE 0 END) as keluhan_tidak_selesai,
                                 AVG(s.total_waktu) as avg_waktu_service,
                                 SUM(s.total_grand) as total_revenue
                                 FROM tblservice s
                                 LEFT JOIN tbservis_keluhan_status k ON s.no_service = k.no_service
                                 $where_clause");
    
    return mysqli_fetch_array($sql);
}

// Function untuk get top keluhan
function getTopKeluhan($koneksi, $limit = 10, $tgl_dari = null, $tgl_sampai = null) {
    if(!$tgl_dari) $tgl_dari = date('Y-m-01');
    if(!$tgl_sampai) $tgl_sampai = date('Y-m-d');
    
    $sql = mysqli_query($koneksi,"SELECT 
                                 mk.kode_keluhan,
                                 mk.nama_keluhan,
                                 mk.kategori,
                                 mk.tingkat_prioritas,
                                 COUNT(k.id) as jumlah_kejadian,
                                 AVG(mk.estimasi_waktu) as avg_estimasi
                                 FROM tbmaster_keluhan mk
                                 JOIN tbservis_keluhan_status k ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                                 JOIN tblservice s ON k.no_service = s.no_service
                                 WHERE DATE(s.tanggal) BETWEEN '$tgl_dari' AND '$tgl_sampai'
                                 AND mk.status_aktif = '1'
                                 GROUP BY mk.id, mk.kode_keluhan, mk.nama_keluhan, mk.kategori, mk.tingkat_prioritas
                                 ORDER BY jumlah_kejadian DESC
                                 LIMIT $limit");
    
    $result = [];
    while($row = mysqli_fetch_array($sql)) {
        $result[] = $row;
    }
    
    return $result;
}

// Resolve nomekanik → nama. Jika tidak ditemukan di master, tampilkan kode.
// Ini menangani data lama Access (kode 001-083) yang nama aslinya tidak tersedia.
function getMekanikNama($koneksi, $nomekanik) {
    if (empty($nomekanik)) return '';
    static $cache = [];
    if (isset($cache[$nomekanik])) return $cache[$nomekanik];
    $safe = mysqli_real_escape_string($koneksi, $nomekanik);
    // Coba karyawan web app dulu (stored as nama_lengkap atau kode_karyawan)
    $q = mysqli_query($koneksi, "SELECT nama_lengkap FROM tbuser_karyawan WHERE kode_karyawan='$safe' LIMIT 1");
    if ($q && $row = mysqli_fetch_row($q)) { $cache[$nomekanik] = $row[0]; return $row[0]; }
    // Fallback ke tblmekanik (data Access-synced, kode 001-083)
    $q2 = mysqli_query($koneksi, "SELECT nama FROM tblmekanik WHERE nomekanik='$safe' LIMIT 1");
    if ($q2 && $row2 = mysqli_fetch_row($q2)) { $cache[$nomekanik] = $row2[0]; return $row2[0]; }
    $cache[$nomekanik] = $nomekanik;
    return $nomekanik;
}

// Resolve array nomekanik → string nama gabungan (untuk tampilan di nota/histori)
function getMekanikNamaGabung($koneksi, ...$kodes) {
    $names = [];
    foreach ($kodes as $k) {
        if (!empty($k)) {
            $names[] = getMekanikNama($koneksi, $k);
        }
    }
    return implode(', ', $names);
}

// Function untuk get mekanik performance
function getMekanikPerformance($koneksi, $tgl_dari = null, $tgl_sampai = null) {
    if(!$tgl_dari) $tgl_dari = date('Y-m-01');
    if(!$tgl_sampai) $tgl_sampai = date('Y-m-d');
    
    $sql = mysqli_query($koneksi,"SELECT 
                                 m.nomekanik,
                                 m.nama,
                                 COUNT(DISTINCT s.no_service) as total_service,
                                 COUNT(kt.id) as total_proses,
                                 SUM(CASE WHEN kt.status_proses = 'selesai' THEN 1 ELSE 0 END) as proses_selesai,
                                 AVG(CASE 
                                     WHEN kt.waktu_mulai IS NOT NULL AND kt.waktu_selesai IS NOT NULL 
                                     THEN TIMESTAMPDIFF(MINUTE, kt.waktu_mulai, kt.waktu_selesai)
                                     ELSE NULL 
                                 END) as avg_durasi_menit,
                                 SUM(s.total_grand) as total_revenue
                                 FROM tblmekanik m
                                 LEFT JOIN tblservice s ON (m.nomekanik = s.mekanik1 OR m.nomekanik = s.mekanik2 OR m.nomekanik = s.mekanik3 OR m.nomekanik = s.mekanik4)
                                 LEFT JOIN tbservis_keluhan_tracking kt ON m.nomekanik = kt.mekanik_id
                                 WHERE DATE(s.tanggal) BETWEEN '$tgl_dari' AND '$tgl_sampai'
                                 AND m.nama != '-'
                                 GROUP BY m.nomekanik, m.nama
                                 HAVING total_service > 0
                                 ORDER BY total_service DESC, proses_selesai DESC");
    
    $result = [];
    while($row = mysqli_fetch_array($sql)) {
        $completion_rate = $row['total_proses'] > 0 ? round(($row['proses_selesai'] / $row['total_proses']) * 100, 1) : 0;
        $row['completion_rate'] = $completion_rate;
        $result[] = $row;
    }
    
    return $result;
}

// Function untuk validate service data
function validateServiceData($service_data) {
    $errors = [];
    
    // Required fields
    $required_fields = ['no_pelanggan', 'no_polisi'];
    foreach($required_fields as $field) {
        if(empty($service_data[$field])) {
            $errors[] = "Field $field harus diisi";
        }
    }
    
    // Validate km
    if(isset($service_data['km_skr']) && $service_data['km_skr'] < 0) {
        $errors[] = "KM sekarang tidak boleh negatif";
    }
    
    if(isset($service_data['km_berikut']) && isset($service_data['km_skr'])) {
        if($service_data['km_berikut'] <= $service_data['km_skr']) {
            $errors[] = "KM berikut harus lebih besar dari KM sekarang";
        }
    }
    
    return $errors;
}

// Function untuk auto assign mekanik based on workload
function autoAssignMekanik($koneksi, $jumlah_mekanik_needed = 1, $keahlian_required = null) {
    // Get mekanik dengan workload paling rendah
    $keahlian_filter = $keahlian_required ? "AND keahlian = '$keahlian_required'" : "";
    
    $sql = mysqli_query($koneksi,"SELECT 
                                 m.nomekanik,
                                 m.nama,
                                 m.keahlian,
                                 COUNT(s.no_service) as current_workload
                                 FROM tblmekanik m
                                 LEFT JOIN tblservice s ON (m.nomekanik IN (s.mekanik1, s.mekanik2, s.mekanik3, s.mekanik4)
                                                           AND s.status IN ('1', '2', '3'))
                                 WHERE m.nama != '-' $keahlian_filter
                                 GROUP BY m.nomekanik, m.nama, m.keahlian
                                 ORDER BY current_workload ASC, m.nama ASC
                                 LIMIT $jumlah_mekanik_needed");
    
    $result = [];
    while($row = mysqli_fetch_array($sql)) {
        $result[] = $row;
    }
    
    return $result;
}

// Function untuk get keluhan recommendations
function getKeluhanRecommendations($koneksi, $keluhan_text, $limit = 5) {
    $keluhan_text = strtolower(trim($keluhan_text));
    $keywords = explode(' ', $keluhan_text);
    
    $recommendations = [];
    
    // Exact match
    $sql = mysqli_query($koneksi,"SELECT kode_keluhan, nama_keluhan, estimasi_waktu, tingkat_prioritas
                                 FROM tbmaster_keluhan 
                                 WHERE status_aktif = '1' 
                                 AND LOWER(nama_keluhan) = '$keluhan_text'
                                 LIMIT 1");
    
    if(mysqli_num_rows($sql) > 0) {
        $recommendations[] = mysqli_fetch_array($sql);
    }
    
    // Partial match
    if(count($recommendations) < $limit) {
        $sql = mysqli_query($koneksi,"SELECT kode_keluhan, nama_keluhan, estimasi_waktu, tingkat_prioritas
                                     FROM tbmaster_keluhan 
                                     WHERE status_aktif = '1' 
                                     AND LOWER(nama_keluhan) LIKE '%$keluhan_text%'
                                     ORDER BY tingkat_prioritas DESC, nama_keluhan ASC
                                     LIMIT " . ($limit - count($recommendations)));
        
        while($row = mysqli_fetch_array($sql)) {
            $recommendations[] = $row;
        }
    }
    
    // Keyword match
    if(count($recommendations) < $limit && count($keywords) > 0) {
        foreach($keywords as $keyword) {
            if(strlen($keyword) > 2 && count($recommendations) < $limit) {
                $sql = mysqli_query($koneksi,"SELECT kode_keluhan, nama_keluhan, estimasi_waktu, tingkat_prioritas
                                             FROM tbmaster_keluhan 
                                             WHERE status_aktif = '1' 
                                             AND (LOWER(nama_keluhan) LIKE '%$keyword%' 
                                                  OR LOWER(deskripsi) LIKE '%$keyword%')
                                             ORDER BY tingkat_prioritas DESC
                                             LIMIT " . ($limit - count($recommendations)));
                
                while($row = mysqli_fetch_array($sql) && count($recommendations) < $limit) {
                    // Check if not already in recommendations
                    $exists = false;
                    foreach($recommendations as $existing) {
                        if($existing['kode_keluhan'] == $row['kode_keluhan']) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if(!$exists) {
                        $recommendations[] = $row;
                    }
                }
            }
        }
    }
    
    return $recommendations;
}

// Function untuk format duration
function formatDuration($minutes) {
    if($minutes < 60) {
        return $minutes . ' menit';
    } elseif($minutes < 1440) { // Less than 24 hours
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . ' jam' . ($mins > 0 ? ' ' . $mins . ' menit' : '');
    } else {
        $days = floor($minutes / 1440);
        $hours = floor(($minutes % 1440) / 60);
        return $days . ' hari' . ($hours > 0 ? ' ' . $hours . ' jam' : '');
    }
}

// Function untuk generate notification
function createNotification($koneksi, $user_id, $type, $title, $message, $related_id = null) {
    $title = mysqli_real_escape_string($koneksi, $title);
    $message = mysqli_real_escape_string($koneksi, $message);
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, related_id, created_at) 
            VALUES ('$user_id', '$type', '$title', '$message', " . ($related_id ? "'$related_id'" : "NULL") . ", NOW())";
    
    return mysqli_query($koneksi, $sql);
}

// Function untuk log activity
function logActivity($koneksi, $user_id, $action, $description, $related_table = null, $related_id = null) {
    $description = mysqli_real_escape_string($koneksi, $description);
    
    $sql = "INSERT INTO activity_logs (user_id, action, description, related_table, related_id, created_at) 
            VALUES ('$user_id', '$action', '$description', " . 
            ($related_table ? "'$related_table'" : "NULL") . ", " . 
            ($related_id ? "'$related_id'" : "NULL") . ", NOW())";
    
    return mysqli_query($koneksi, $sql);
}

// Function untuk clean old data
function cleanOldTrackingData($koneksi, $days_to_keep = 90) {
    $cutoff_date = date('Y-m-d', strtotime("-$days_to_keep days"));
    
    // Delete old tracking data for completed services
    $sql = "DELETE kt FROM tbservis_keluhan_tracking kt
            JOIN tbservis_keluhan_status k ON kt.keluhan_id = k.id
            JOIN tblservice s ON k.no_service = s.no_service
            WHERE DATE(s.tanggal) < '$cutoff_date'
            AND s.status = '4'
            AND k.status_pengerjaan = 'selesai'";
    
    return mysqli_query($koneksi, $sql);
}

// Function untuk backup keluhan data
function backupKeluhanData($koneksi, $no_service) {
    // Create backup of keluhan and tracking data before deletion
    $backup_sql = "INSERT INTO tbservis_keluhan_backup 
                   SELECT k.*, NOW() as backup_date 
                   FROM tbservis_keluhan_status k 
                   WHERE k.no_service = '$no_service'";
    
    mysqli_query($koneksi, $backup_sql);
    
    $backup_tracking_sql = "INSERT INTO tbservis_keluhan_tracking_backup 
                           SELECT kt.*, NOW() as backup_date 
                           FROM tbservis_keluhan_tracking kt
                           JOIN tbservis_keluhan_status k ON kt.keluhan_id = k.id
                           WHERE k.no_service = '$no_service'";
    
    return mysqli_query($koneksi, $backup_tracking_sql);
}

// Task 3: konversi nota Penjualan Umum jadi servis (Work Order).
// Barang dari nota dibawa sebagai asal_barang='PENJUALAN' — sudah kepotong stok
// saat nota disimpan, jadi harus exclude dari potong-stok servis (lihat query
// pembayaran di servis-input-reguler.php/servis-garansi.php/servis-input-reguler-jemput.php).
if (!function_exists('buatServisDariPenjualan')) {
    function buatServisDariPenjualan($koneksi, $nopol, $notransaksi, $kd_cabang, $id_user) {
        $nopol = trim($nopol);
        $notransaksi_esc = mysqli_real_escape_string($koneksi, $notransaksi);

        // Idempotent: kalau nota ini sudah pernah dikonversi, kembalikan servis yang sudah ada
        $cek = mysqli_query($koneksi, "SELECT no_service FROM tblservice WHERE ref_no_penjualan_asal='$notransaksi_esc' LIMIT 1");
        if ($cek && ($row = mysqli_fetch_assoc($cek))) {
            return array('ok' => true, 'no_service' => $row['no_service']);
        }

        $stmtVehicle = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtVehicle, "s", $nopol);
        mysqli_stmt_execute($stmtVehicle);
        $vehicleResult = mysqli_stmt_get_result($stmtVehicle);
        if (!$vehicleResult || mysqli_num_rows($vehicleResult) === 0) {
            mysqli_stmt_close($stmtVehicle);
            return array('ok' => false, 'message' => 'Data kendaraan tidak ditemukan.');
        }
        mysqli_stmt_close($stmtVehicle);

        $stmtDuplicate = mysqli_prepare(
            $koneksi,
            "SELECT no_service FROM tblservice WHERE no_polisi = ? AND COALESCE(status_servis, '') NOT IN ('selesai', 'bayar', 'cancel') ORDER BY tanggal DESC, jam DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($stmtDuplicate, "s", $nopol);
        mysqli_stmt_execute($stmtDuplicate);
        $duplicateResult = mysqli_stmt_get_result($stmtDuplicate);
        if ($duplicateResult && ($duplicateRow = mysqli_fetch_assoc($duplicateResult))) {
            mysqli_stmt_close($stmtDuplicate);
            return array('ok' => false, 'message' => 'Masih ada servis aktif untuk nomor polisi ini.', 'no_service' => $duplicateRow['no_service']);
        }
        mysqli_stmt_close($stmtDuplicate);

        include_once __DIR__ . '/function_servis.php';
        date_default_timezone_set('Asia/Jakarta');
        $tgl_skr = date('Y/m/d');
        $waktu_skr = date('H:i');
        $no_service = FormatNoTrans(OtomatisID());

        $stmtInsert = mysqli_prepare(
            $koneksi,
            "INSERT INTO tblservice (no_service, tanggal, jam, no_pelanggan, no_polisi, kd_cabang, id_user, status_servis, ref_no_penjualan_asal) VALUES (?, ?, ?, ?, ?, ?, ?, 'datang', ?)"
        );
        mysqli_stmt_bind_param($stmtInsert, "ssssssis", $no_service, $tgl_skr, $waktu_skr, $nopol, $nopol, $kd_cabang, $id_user, $notransaksi);
        if (!mysqli_stmt_execute($stmtInsert)) {
            mysqli_stmt_close($stmtInsert);
            return array('ok' => false, 'message' => 'Gagal membuat servis: ' . mysqli_stmt_error($stmtInsert));
        }
        mysqli_stmt_close($stmtInsert);

        $nobaris = 0;
        $sqlItem = mysqli_query($koneksi, "SELECT no_item, quantity, harga_jual, potongan, total FROM tblpenjualan_detail WHERE no_transaksi='$notransaksi_esc'");
        while ($item = mysqli_fetch_assoc($sqlItem)) {
            $stmtItem = mysqli_prepare(
                $koneksi,
                "INSERT INTO tblservis_barang
                    (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total,
                     diskon_source, diskon_persen, diskon_nominal, id_promo, asal_barang)
                 VALUES (?, ?, ?, ?, 0, ?, ?, ?, 'none', 0, 0, 0, 'PENJUALAN')"
            );
            mysqli_stmt_bind_param($stmtItem, "sisdddd", $no_service, $nobaris, $item['no_item'], $item['quantity'], $item['harga_jual'], $item['potongan'], $item['total']);
            mysqli_stmt_execute($stmtItem);
            mysqli_stmt_close($stmtItem);
            $nobaris++;
        }

        return array('ok' => true, 'no_service' => $no_service);
    }
}
?>