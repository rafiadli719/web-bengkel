<?php
/**
 * HELPER FUNCTIONS UNTUK TARIF JEMPUT ANTAR
 * ==========================================
 * Fungsi-fungsi untuk menghitung tarif jemput antar berdasarkan sistem range
 * Dibuat: 13 Oktober 2025
 */

/**
 * Menghitung tarif jemput berdasarkan jenis motor dan jarak
 * 
 * @param mysqli $koneksi Database connection
 * @param string $jenis_motor 'Motor Jalan' atau 'Motor Mogok'
 * @param float $jarak Jarak dalam kilometer
 * @return array Array dengan informasi tarif
 */
function hitungTarifJemput($koneksi, $jenis_motor, $jarak) {
    $result = [
        'success' => false,
        'tarif' => 0,
        'breakdown' => '',
        'range_info' => '',
        'error' => ''
    ];
    
    try {
        // Validasi input
        if (empty($jenis_motor) || !in_array($jenis_motor, ['Motor Jalan', 'Motor Mogok'])) {
            $result['error'] = 'Jenis motor tidak valid';
            return $result;
        }
        
        if ($jarak < 0) {
            $result['error'] = 'Jarak tidak boleh negatif';
            return $result;
        }
        
        // Cari range tarif yang sesuai
        $stmt = mysqli_prepare($koneksi, "
            SELECT id, jarak_min, jarak_max, tarif_dasar, tarif_per_km, keterangan
            FROM master_tarif_jemput_range 
            WHERE jenis_motor = ? 
              AND aktif = 1 
              AND ? >= jarak_min 
              AND (jarak_max IS NULL OR ? <= jarak_max)
            ORDER BY jarak_min DESC 
            LIMIT 1
        ");
        
        mysqli_stmt_bind_param($stmt, "sdd", $jenis_motor, $jarak, $jarak);
        mysqli_stmt_execute($stmt);
        $query_result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($query_result)) {
            $tarif_dasar = $row['tarif_dasar'];
            $tarif_per_km = $row['tarif_per_km'];
            $jarak_min = $row['jarak_min'];
            $jarak_max = $row['jarak_max'];
            
            // Hitung jarak tambahan
            $jarak_tambahan = max(0, $jarak - $jarak_min);
            
            // Hitung tarif total
            $tarif_total = $tarif_dasar + (ceil($jarak_tambahan) * $tarif_per_km);
            
            // Format range info
            $range_text = number_format($jarak_min, 1);
            if ($jarak_max) {
                $range_text .= ' - ' . number_format($jarak_max, 1);
            } else {
                $range_text .= ' - ∞';
            }
            $range_text .= ' KM';
            
            // Format breakdown
            $breakdown = "Range: {$range_text}\n";
            $breakdown .= "Tarif Dasar: Rp " . number_format($tarif_dasar, 0, ',', '.') . "\n";
            
            if ($jarak_tambahan > 0 && $tarif_per_km > 0) {
                $breakdown .= "Jarak Tambahan: " . number_format($jarak_tambahan, 1) . " KM\n";
                $breakdown .= "Tarif per KM: Rp " . number_format($tarif_per_km, 0, ',', '.') . "\n";
                $breakdown .= "Tambahan: " . ceil($jarak_tambahan) . " × Rp " . number_format($tarif_per_km, 0, ',', '.') . " = Rp " . number_format(ceil($jarak_tambahan) * $tarif_per_km, 0, ',', '.') . "\n";
            }
            
            $breakdown .= "Total: Rp " . number_format($tarif_total, 0, ',', '.');
            
            $result['success'] = true;
            $result['tarif'] = $tarif_total;
            $result['breakdown'] = $breakdown;
            $result['range_info'] = $range_text;
            
        } else {
            $result['error'] = "Tidak ditemukan range tarif untuk jenis motor '{$jenis_motor}' dengan jarak {$jarak} KM";
        }
        
        mysqli_stmt_close($stmt);
        
    } catch (Exception $e) {
        $result['error'] = 'Error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Mendapatkan semua range tarif aktif
 * 
 * @param mysqli $koneksi Database connection
 * @return array Array dengan data range tarif
 */
function getAllTarifRanges($koneksi) {
    $ranges = [];
    
    try {
        $query = mysqli_query($koneksi, "
            SELECT jenis_motor, jarak_min, jarak_max, tarif_dasar, tarif_per_km, keterangan
            FROM master_tarif_jemput_range 
            WHERE aktif = 1 
            ORDER BY jenis_motor, jarak_min
        ");
        
        while ($row = mysqli_fetch_assoc($query)) {
            $ranges[] = $row;
        }
        
    } catch (Exception $e) {
        error_log("Error getting tarif ranges: " . $e->getMessage());
    }
    
    return $ranges;
}

/**
 * Mendapatkan tarif untuk berbagai jarak sekaligus (untuk preview)
 * 
 * @param mysqli $koneksi Database connection
 * @param string $jenis_motor 'Motor Jalan' atau 'Motor Mogok'
 * @param array $jarak_list Array jarak yang ingin dihitung
 * @return array Array dengan hasil perhitungan untuk setiap jarak
 */
function hitungMultipleTarif($koneksi, $jenis_motor, $jarak_list) {
    $results = [];
    
    foreach ($jarak_list as $jarak) {
        $results[] = [
            'jarak' => $jarak,
            'tarif_info' => hitungTarifJemput($koneksi, $jenis_motor, $jarak)
        ];
    }
    
    return $results;
}

/**
 * Validasi apakah range tarif baru tidak bertabrakan dengan yang sudah ada
 * 
 * @param mysqli $koneksi Database connection
 * @param string $jenis_motor Jenis motor
 * @param float $jarak_min Jarak minimum baru
 * @param float $jarak_max Jarak maksimum baru (null jika unlimited)
 * @param int $exclude_id ID yang dikecualikan (untuk edit)
 * @return array Result validasi
 */
function validateTarifRange($koneksi, $jenis_motor, $jarak_min, $jarak_max, $exclude_id = null) {
    $result = [
        'valid' => true,
        'error' => ''
    ];
    
    try {
        // Query untuk cek overlap
        $where_clause = "jenis_motor = ? AND aktif = 1";
        $params = [$jenis_motor];
        $types = "s";
        
        if ($exclude_id) {
            $where_clause .= " AND id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        // Cek overlap dengan range yang sudah ada
        $overlap_query = "
            SELECT id, jarak_min, jarak_max 
            FROM master_tarif_jemput_range 
            WHERE {$where_clause}
              AND (
                  (? >= jarak_min AND (jarak_max IS NULL OR ? <= jarak_max))
                  OR (? >= jarak_min AND (jarak_max IS NULL OR ? <= jarak_max))
                  OR (jarak_min >= ? AND (? IS NULL OR jarak_min <= ?))
              )
        ";
        
        $stmt = mysqli_prepare($koneksi, $overlap_query);
        
        // Add parameters for overlap check
        $params = array_merge($params, [$jarak_min, $jarak_min, $jarak_max, $jarak_max, $jarak_min, $jarak_max, $jarak_max]);
        $types .= "ddddddd";
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $overlap_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($overlap_result) > 0) {
            $existing = mysqli_fetch_assoc($overlap_result);
            $existing_range = number_format($existing['jarak_min'], 1);
            if ($existing['jarak_max']) {
                $existing_range .= ' - ' . number_format($existing['jarak_max'], 1);
            } else {
                $existing_range .= ' - ∞';
            }
            
            $result['valid'] = false;
            $result['error'] = "Range bertabrakan dengan range yang sudah ada: {$existing_range} KM";
        }
        
        mysqli_stmt_close($stmt);
        
    } catch (Exception $e) {
        $result['valid'] = false;
        $result['error'] = 'Error validasi: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Format tarif untuk display
 * 
 * @param int $tarif Nilai tarif
 * @return string Tarif yang sudah diformat
 */
function formatTarif($tarif) {
    return 'Rp ' . number_format($tarif, 0, ',', '.');
}

/**
 * Generate contoh perhitungan tarif untuk dokumentasi
 * 
 * @param mysqli $koneksi Database connection
 * @return string HTML contoh perhitungan
 */
function generateContohPerhitungan($koneksi) {
    $html = '<div class="alert alert-info">';
    $html .= '<h5><i class="fa fa-calculator"></i> Contoh Perhitungan Tarif:</h5>';
    
    // Contoh untuk Motor Jalan
    $contoh_jarak = [1.5, 3.2, 7.8, 12.5];
    
    foreach (['Motor Jalan', 'Motor Mogok'] as $jenis) {
        $html .= "<h6><strong>{$jenis}:</strong></h6><ul>";
        
        foreach ($contoh_jarak as $jarak) {
            $tarif_info = hitungTarifJemput($koneksi, $jenis, $jarak);
            if ($tarif_info['success']) {
                $html .= "<li>Jarak {$jarak} KM = " . formatTarif($tarif_info['tarif']) . "</li>";
            }
        }
        
        $html .= '</ul>';
    }
    
    $html .= '</div>';
    return $html;
}

?>
