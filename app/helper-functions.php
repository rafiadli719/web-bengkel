<?php
// File: helper-functions.php
// Helper functions untuk sistem tracking keluhan dan manajemen service

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
?>