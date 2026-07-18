<?php
/**
 * File: _include_statistik_pelanggan.php
 * Deskripsi: Helper untuk menampilkan status member pelanggan di halaman servis
 * Include file ini di halaman servis-input-*.php
 */

/**
 * ========================================
 * AUTO-UPDATE FUNCTIONS (NEW)
 * ========================================
 */

/**
 * Check if kategori member type is enabled globally
 * 
 * @param mysqli $koneksi Database connection
 * @param string $type 'nominal' or 'kunjungan'
 * @return bool True if enabled, false if disabled
 */
function isKategoriMemberEnabled($koneksi, $type = 'nominal') {
    $type = mysqli_real_escape_string($koneksi, $type);
    
    $query = "SELECT is_enabled FROM setting_kategori_member WHERE tipe_kategori = '$type' LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return (bool)$row['is_enabled'];
    }
    
    // Default: enabled if no setting found
    return true;
}

/**
 * Check if an item is excluded from member discount
 * HYBRID LOGIC: Item Setting (if set) > Jenis Setting > Default (False)
 * 
 * @param mysqli $koneksi Database connection
 * @param string $noitem Item code
 * @return bool True if excluded from discount, false if can get discount
 */
function isItemExcludedFromMemberDiscount($koneksi, $noitem) {
    $noitem = mysqli_real_escape_string($koneksi, $noitem);
    
    // Get item's exclude setting and jenis setting
    // We check BOTH in one query
    // item_exclude: 0=Dapat, 1=Tidak, NULL=Ikut Jenis
    // jenis_exclude: 0=Dapat, 1=Tidak (Default 0 in DB)
    
    $query = "SELECT i.exclude_diskon_member as item_exclude, 
                     h.exclude_diskon_member as jenis_exclude
              FROM tblitem i
              LEFT JOIN tbhargajual h ON i.jenis = h.jenis
              WHERE i.noitem = '$noitem'
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return false; // Item not found, default allow
    }
    
    $row = mysqli_fetch_assoc($result);
    
    // 1. Priority: Item Specific Setting
    if (!is_null($row['item_exclude'])) {
        // If explicitly set (0 or 1), use it
        return (bool)$row['item_exclude'];
    }
    
    // 2. Fallback: Jenis Rule
    // If not set in item, use Jenis setting (default 0/False if null)
    return (bool)($row['jenis_exclude'] ?? 0);
}

/**
 * Get member discount for an item, considering exclusion settings
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Customer number
 * @param string $noitem Item code (optional - if provided, checks exclusion)
 * @param string $item_type 'barang' or 'jasa'
 * @return float Discount percentage (0 if excluded)
 */
function getMemberDiscountForItem($koneksi, $no_pelanggan, $noitem = '', $item_type = 'barang') {
    // Get effective discount for customer
    $diskon = getEffectiveDiskonPelanggan($koneksi, $no_pelanggan);
    
    // Get the appropriate discount based on item type
    $discount_persen = ($item_type == 'jasa') ? $diskon['diskon_jasa'] : $diskon['diskon_barang'];
    
    // If no item specified or no discount, return as-is
    if (empty($noitem) || $discount_persen <= 0) {
        return $discount_persen;
    }
    
    // Check if item is excluded from member discount
    if (isItemExcludedFromMemberDiscount($koneksi, $noitem)) {
        return 0; // Item excluded - no discount
    }
    
    return $discount_persen;
}

/**
 * Determine member tier based on value and type
 * Checks global setting first - if type is disabled, returns 'Bronze' (neutral)
 * 
 * @param mysqli $koneksi Database connection
 * @param float $value Total nominal or total kunjungan
 * @param string $type 'nominal' or 'kunjungan'
 * @return string Member tier (Bronze/Silver/Gold/Platinum)
 */
function determineMemberTier($koneksi, $value, $type = 'nominal') {
    // Check if this category type is enabled globally
    if (!isKategoriMemberEnabled($koneksi, $type)) {
        return 'Bronze'; // Return neutral tier if type is disabled
    }
    
    $query = "SELECT nama_kategori, min_value, max_value
              FROM master_kategori_member
              WHERE tipe_kategori = '$type'
                AND is_active = 1
              ORDER BY urutan ASC";
    
    $result = mysqli_query($koneksi, $query);
    
    if (!$result) {
        return 'Bronze'; // Default if query fails
    }
    
    $tier = 'Bronze'; // Default
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($value >= $row['min_value']) {
            // Check if value is within range
            if ($row['max_value'] === null || $value <= $row['max_value']) {
                $tier = $row['nama_kategori'];
                // Don't break - continue to find highest matching tier
            } elseif ($row['max_value'] !== null && $value > $row['max_value']) {
                // Value exceeds this tier, might qualify for next tier
                continue;
            }
        }
    }
    
    return $tier;
}

/**
 * Update statistik pelanggan after service payment
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Customer number
 * @param string $no_service Service number (optional - for logging)
 * @return bool Success status
 */
function updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan, $no_service = '') {
    // Escape input
    $no_pelanggan = mysqli_real_escape_string($koneksi, $no_pelanggan);
    $no_service = mysqli_real_escape_string($koneksi, $no_service);
    
    // 1. Calculate totals from tblservice
    $query_totals = "SELECT 
                        COUNT(*) as total_transaksi,
                        COALESCE(SUM(total_akhir), 0) as total_nominal,
                        COALESCE(AVG(total_akhir), 0) as rata_rata_transaksi,
                        MIN(tanggal) as tanggal_pertama,
                        MAX(tanggal) as tanggal_terakhir,
                        COUNT(DISTINCT no_polisi) as total_motor
                    FROM tblservice
                    WHERE no_pelanggan = '$no_pelanggan'
                      AND status_servis IN ('bayar', 'selesai')
                      AND total_akhir > 0";
    
    $result = mysqli_query($koneksi, $query_totals);
    
    if (!$result) {
        error_log("Failed to calculate totals for customer: $no_pelanggan - " . mysqli_error($koneksi));
        return false;
    }
    
    $totals = mysqli_fetch_assoc($result);
    
    // 2. Determine member tier based on nominal
    $status_member = determineMemberTier($koneksi, $totals['total_nominal'], 'nominal');
    
    // 3. Calculate kunjungan stats
    $jumlah_kunjungan = $totals['total_transaksi'];
    $kategori_member_kunjungan = determineMemberTier($koneksi, $jumlah_kunjungan, 'kunjungan');
    
    // 4. Calculate time-based metrics
    $lama_tidak_datang = 0;
    $lama_menjadi_pelanggan = 0;
    $rata_jarak_kunjungan = 0;
    
    if ($totals['tanggal_terakhir']) {
        $lama_tidak_datang = floor((strtotime('now') - strtotime($totals['tanggal_terakhir'])) / 86400);
    }
    
    if ($totals['tanggal_pertama']) {
        $lama_menjadi_pelanggan = floor((strtotime('now') - strtotime($totals['tanggal_pertama'])) / 86400);
    }
    
    if ($jumlah_kunjungan > 1 && $lama_menjadi_pelanggan > 0) {
        $rata_jarak_kunjungan = floor($lama_menjadi_pelanggan / ($jumlah_kunjungan - 1));
    }
    
    // 5. Estimate next visit
    $estimasi_datang_berikutnya = 'NULL';
    if ($rata_jarak_kunjungan > 0 && $totals['tanggal_terakhir']) {
        $next_date = date('Y-m-d', strtotime($totals['tanggal_terakhir'] . " + {$rata_jarak_kunjungan} days"));
        $estimasi_datang_berikutnya = "'$next_date'";
    }

    // Tidak ada transaksi 'bayar' dengan total_akhir > 0 -> MIN/MAX tanggal NULL,
    // gunakan literal SQL NULL agar tidak gagal pada kolom DATE (strict mode).
    $tanggal_terakhir_sql = $totals['tanggal_terakhir'] ? "'" . mysqli_real_escape_string($koneksi, $totals['tanggal_terakhir']) . "'" : 'NULL';
    $tanggal_pertama_sql = $totals['tanggal_pertama'] ? "'" . mysqli_real_escape_string($koneksi, $totals['tanggal_pertama']) . "'" : 'NULL';

    // 6. UPSERT to statistik_pelanggan
    $query_upsert = "INSERT INTO statistik_pelanggan (
                        no_pelanggan,
                        status_member,
                        kategori_member_kunjungan,
                        total_nominal,
                        total_transaksi,
                        jumlah_kunjungan,
                        kedatangan_terakhir,
                        rata_rata_transaksi,
                        rata_jarak_kunjungan,
                        tanggal_terakhir_transaksi,
                        tanggal_pertama_transaksi,
                        lama_tidak_datang,
                        lama_menjadi_pelanggan,
                        estimasi_datang_berikutnya,
                        total_motor
                    ) VALUES (
                        '$no_pelanggan',
                        '$status_member',
                        '$kategori_member_kunjungan',
                        {$totals['total_nominal']},
                        {$totals['total_transaksi']},
                        $jumlah_kunjungan,
                        $jumlah_kunjungan,
                        {$totals['rata_rata_transaksi']},
                        $rata_jarak_kunjungan,
                        $tanggal_terakhir_sql,
                        $tanggal_pertama_sql,
                        $lama_tidak_datang,
                        $lama_menjadi_pelanggan,
                        $estimasi_datang_berikutnya,
                        {$totals['total_motor']}
                    ) ON DUPLICATE KEY UPDATE
                        status_member = '$status_member',
                        kategori_member_kunjungan = '$kategori_member_kunjungan',
                        total_nominal = {$totals['total_nominal']},
                        total_transaksi = {$totals['total_transaksi']},
                        jumlah_kunjungan = $jumlah_kunjungan,
                        kedatangan_terakhir = $jumlah_kunjungan,
                        rata_rata_transaksi = {$totals['rata_rata_transaksi']},
                        rata_jarak_kunjungan = $rata_jarak_kunjungan,
                        tanggal_terakhir_transaksi = $tanggal_terakhir_sql,
                        tanggal_pertama_transaksi = $tanggal_pertama_sql,
                        lama_tidak_datang = $lama_tidak_datang,
                        lama_menjadi_pelanggan = $lama_menjadi_pelanggan,
                        estimasi_datang_berikutnya = $estimasi_datang_berikutnya,
                        total_motor = {$totals['total_motor']}";
    
    $success = mysqli_query($koneksi, $query_upsert);
    
    // 7. Log update
    if ($success) {
        error_log("✅ Statistik updated for customer: $no_pelanggan" . 
                  ($no_service ? " (Service: $no_service)" : "") . 
                  " - Tier: $status_member (Nominal) / $kategori_member_kunjungan (Kunjungan)");
    } else {
        error_log("❌ Failed to update statistik for customer: $no_pelanggan - " . mysqli_error($koneksi));
    }
    
    return $success;
}

/**
 * Update cancel statistics for customer
 * Called after service cancellation to track cancel history
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Customer number
 * @return bool Success status
 */
function updateCancelStatistikPelanggan($koneksi, $no_pelanggan) {
    // Escape input
    $no_pelanggan = mysqli_real_escape_string($koneksi, $no_pelanggan);
    
    // Check if statistik_pelanggan record exists
    $check_query = "SELECT no_pelanggan FROM statistik_pelanggan WHERE no_pelanggan = '$no_pelanggan'";
    $check_result = mysqli_query($koneksi, $check_query);
    
    if (!$check_result || mysqli_num_rows($check_result) == 0) {
        // No statistik record yet, cannot update cancel stats
        error_log("⚠️ No statistik record for customer: $no_pelanggan - skipping cancel update");
        return false;
    }
    
    // 1. Calculate cancel statistics from tb_log_cancel_servis
    $query_cancel = "SELECT 
                        COUNT(*) as total_cancel,
                        MAX(tanggal_cancel) as tanggal_terakhir,
                        SUM(CASE WHEN kategori_alasan = 'customer_request' THEN 1 ELSE 0 END) as cancel_request,
                        SUM(CASE WHEN kategori_alasan = 'no_stock' THEN 1 ELSE 0 END) as cancel_stock,
                        SUM(CASE WHEN kategori_alasan = 'no_mekanik' THEN 1 ELSE 0 END) as cancel_mekanik,
                        SUM(CASE WHEN kategori_alasan = 'customer_no_show' THEN 1 ELSE 0 END) as cancel_noshow,
                        SUM(CASE WHEN kategori_alasan = 'lainnya' THEN 1 ELSE 0 END) as cancel_lainnya
                    FROM tb_log_cancel_servis lc
                    JOIN tblservice s ON lc.no_service = s.no_service
                    WHERE s.no_pelanggan = '$no_pelanggan'";
                    
    $result_cancel = mysqli_query($koneksi, $query_cancel);
    
    if (!$result_cancel) {
        error_log("❌ Failed to calculate cancel stats for customer: $no_pelanggan - " . mysqli_error($koneksi));
        return false;
    }
    
    $cancel_data = mysqli_fetch_assoc($result_cancel);
    
    // 2. Get total transaksi (jadi service) from statistik_pelanggan
    $query_transaksi = "SELECT jumlah_kunjungan FROM statistik_pelanggan WHERE no_pelanggan = '$no_pelanggan'";
    $result_transaksi = mysqli_query($koneksi, $query_transaksi);
    $transaksi_data = mysqli_fetch_assoc($result_transaksi);
    $jumlah_kunjungan = $transaksi_data['jumlah_kunjungan'] ?? 0;
    
    // 3. Calculate cancel rate
    $jumlah_cancel = $cancel_data['total_cancel'];
    $total_booking = $jumlah_kunjungan + $jumlah_cancel;
    $cancel_rate = $total_booking > 0 ? ($jumlah_cancel / $total_booking) * 100 : 0;
    
    // 4. UPDATE statistik_pelanggan with cancel data
    $tanggal_cancel_terakhir = $cancel_data['tanggal_terakhir'] ? "'" . date('Y-m-d', strtotime($cancel_data['tanggal_terakhir'])) . "'" : "NULL";
    
    $query_update = "UPDATE statistik_pelanggan SET
        jumlah_cancel = {$cancel_data['total_cancel']},
        cancel_rate = " . number_format($cancel_rate, 2, '.', '') . ",
        tanggal_cancel_terakhir = $tanggal_cancel_terakhir,
        cancel_customer_request = {$cancel_data['cancel_request']},
        cancel_no_stock = {$cancel_data['cancel_stock']},
        cancel_no_mekanik = {$cancel_data['cancel_mekanik']},
        cancel_no_show = {$cancel_data['cancel_noshow']},
        cancel_lainnya = {$cancel_data['cancel_lainnya']},
        last_updated = NOW()
        WHERE no_pelanggan = '$no_pelanggan'";
        
    $success = mysqli_query($koneksi, $query_update);
    
    // 5. Log update
    if ($success) {
        error_log("✅ Cancel statistik updated for customer: $no_pelanggan - " . 
                  "Total cancel: {$cancel_data['total_cancel']}, Cancel rate: " . 
                  number_format($cancel_rate, 1) . "%");
    } else {
        error_log("❌ Failed to update cancel statistik for customer: $no_pelanggan - " . mysqli_error($koneksi));
    }
    
    return $success;
}

// Function untuk get status member pelanggan (DUAL SYSTEM: Nominal + Kunjungan)
function getStatusMemberPelanggan($koneksi, $no_pelanggan) {
    $query = "SELECT 
                sp.status_member,
                sp.kategori_member_kunjungan,
                sp.total_nominal,
                sp.total_transaksi,
                sp.jumlah_kunjungan,
                sp.kedatangan_terakhir,
                sp.rata_rata_transaksi,
                sp.rata_jarak_kunjungan,
                sp.tanggal_terakhir_transaksi,
                sp.lama_tidak_datang
              FROM statistik_pelanggan sp
              WHERE sp.no_pelanggan = '$no_pelanggan'";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_array($result);
    } else {
        // Default untuk pelanggan baru
        return [
            'status_member' => 'Bronze',
            'kategori_member_kunjungan' => 'Bronze',
            'total_nominal' => 0,
            'total_transaksi' => 0,
            'jumlah_kunjungan' => 0,
            'kedatangan_terakhir' => 0,
            'rata_rata_transaksi' => 0,
            'rata_jarak_kunjungan' => 0,
            'tanggal_terakhir_transaksi' => null,
            'lama_tidak_datang' => 0
        ];
    }
}

// Function untuk tampilkan badge status member
function displayStatusMemberBadge($status_member, $show_icon = true) {
    $badge_color = '';
    $icon = '';

    $status_member = ucfirst(strtolower(trim((string)$status_member)));
    switch($status_member) {
        case 'Bronze':
            $badge_color = '#CD7F32';
            $icon = '🥉';
            break;
        case 'Silver':
            $badge_color = '#C0C0C0';
            $icon = '🥈';
            break;
        case 'Gold':
            $badge_color = '#FFD700';
            $icon = '🥇';
            break;
        case 'Platinum':
            $badge_color = '#E5E4E2';
            $icon = '💎';
            break;
        default:
            $badge_color = '#CD7F32';
            $icon = '🥉';
            $status_member = 'Bronze';
    }
    
    $display_icon = $show_icon ? $icon . ' ' : '';
    
    return "<span style='background: {$badge_color}; color: " . ($status_member == 'Gold' || $status_member == 'Platinum' ? '#000' : '#fff') . "; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 11px;'>{$display_icon}{$status_member}</span>";
}

// Function untuk get benefit member
function getMemberBenefits($status_member) {
    $benefits = [];
    
    switch($status_member) {
        case 'Silver':
            $benefits = [
                'Diskon 10% untuk service',
                'Prioritas antrian'
            ];
            break;
        case 'Gold':
            $benefits = [
                'Diskon 15% untuk service',
                'Prioritas antrian',
                'Gratis cuci motor'
            ];
            break;
        case 'Platinum':
            $benefits = [
                'Diskon 20% untuk service',
                'Prioritas antrian VIP',
                'Gratis cuci motor & oli',
                'Jemput antar gratis'
            ];
            break;
        default: // Bronze
            $benefits = [
                'Member standar',
                'Akses ke semua layanan'
            ];
    }
    
    return $benefits;
}

// Function untuk tampilkan info statistik pelanggan
function displayStatistikPelangganInfo($koneksi, $no_pelanggan) {
    $data = getStatusMemberPelanggan($koneksi, $no_pelanggan);
    
    if(!$data) return '';
    
    $status_badge = displayStatusMemberBadge($data['status_member']);
    $benefits = getMemberBenefits($data['status_member']);
    
    // Hitung progress ke level berikutnya
    $current_total = $data['total_nominal'];
    $next_level = '';
    $next_target = 0;
    $progress_percent = 0;
    
    if($data['status_member'] == 'Bronze') {
        $next_level = 'Silver';
        $next_target = 2000000;
        $progress_percent = ($current_total / $next_target) * 100;
    } elseif($data['status_member'] == 'Silver') {
        $next_level = 'Gold';
        $next_target = 5000000;
        $progress_percent = ($current_total / $next_target) * 100;
    } elseif($data['status_member'] == 'Gold') {
        $next_level = 'Platinum';
        $next_target = 10000000;
        $progress_percent = ($current_total / $next_target) * 100;
    } else {
        $next_level = 'MAX';
        $progress_percent = 100;
    }
    
    $progress_percent = min($progress_percent, 100);
    
    ob_start();
    ?>
    <div class="alert alert-info" style="margin-bottom: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none;">
        <div class="row">
            <div class="col-xs-12 col-sm-6">
                <h4 style="margin-top: 0; color: #fff;">
                    <i class="fa fa-trophy"></i> Status Member: <?php echo $status_badge; ?>
                </h4>
                <div style="margin-top: 10px;">
                    <strong>Total Transaksi:</strong> <?php echo $data['total_transaksi']; ?>x<br>
                    <strong>Total Nominal:</strong> Rp <?php echo number_format($data['total_nominal'], 0, ',', '.'); ?><br>
                    <strong>Rata-rata:</strong> Rp <?php echo number_format($data['rata_rata_transaksi'], 0, ',', '.'); ?>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <h5 style="color: #fff;"><i class="fa fa-gift"></i> Benefit Member:</h5>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach($benefits as $benefit): ?>
                    <li><?php echo $benefit; ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if($next_level != 'MAX'): ?>
                <div style="margin-top: 10px;">
                    <small>Progress ke <?php echo $next_level; ?>:</small>
                    <div class="progress" style="height: 20px; margin-bottom: 0;">
                        <div class="progress-bar progress-bar-warning" style="width: <?php echo $progress_percent; ?>%;">
                            <?php echo number_format($progress_percent, 0); ?>%
                        </div>
                    </div>
                    <small>Kurang Rp <?php echo number_format($next_target - $current_total, 0, ',', '.'); ?> lagi!</small>
                </div>
                <?php else: ?>
                <div style="margin-top: 10px;">
                    <span class="label label-warning" style="font-size: 12px;">
                        <i class="fa fa-star"></i> LEVEL MAKSIMUM!
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Function untuk kirim WhatsApp setelah pembayaran
function sendWhatsAppAfterPayment($koneksi, $no_service, $auto_send = false) {
    // Check if WhatsApp automation is enabled
    $config_file = __DIR__ . '/config_whatsapp.php';
    
    if(file_exists($config_file)) {
        include_once $config_file;
        
        if(defined('WA_API_ENABLED') && WA_API_ENABLED && $auto_send) {
            // Auto send via API
            include_once __DIR__ . '/class_whatsapp_automation.php';
            
            $wa_api_key = defined('WA_API_KEY') ? WA_API_KEY : '';
            $wa_api_url = defined('WA_API_URL') ? WA_API_URL : '';
            
            $wa = new WhatsAppAutomation($koneksi, $wa_api_key, $wa_api_url);
            $result = $wa->sendTerimaKasih($no_service);
            
            return $result;
        }
    }
    
    // Manual mode - generate WhatsApp Web link
    include_once __DIR__ . '/class_whatsapp_automation.php';
    $wa = new WhatsAppAutomation($koneksi);
    $result = $wa->sendTerimaKasih($no_service);
    
    return $result;
}

// Function untuk tampilkan tombol WhatsApp
function displayWhatsAppButton($no_service, $show_text = true) {
    $text = $show_text ? ' Kirim Ucapan Terima Kasih' : '';
    
    return "<a href='statistik_pelanggan_send_wa.php?no_service={$no_service}' target='_blank' class='btn btn-success'>
                <i class='fa fa-whatsapp'></i>{$text}
            </a>";
}

// ========================================
// FUNCTION BARU: DUAL SYSTEM (Nominal + Kunjungan)
// ========================================

// Function untuk tampilkan info DUAL SYSTEM (Nominal + Kunjungan)
function displayStatistikPelangganDualSystem($koneksi, $no_pelanggan) {
    $data = getStatusMemberPelanggan($koneksi, $no_pelanggan);
    
    if(!$data) return '';
    
    $status_badge_nominal = displayStatusMemberBadge($data['status_member']);
    $status_badge_kunjungan = displayStatusMemberBadge($data['kategori_member_kunjungan']);
    
    // Ambil benefit dari member tertinggi
    $highest_member = ($data['status_member'] == 'Platinum' || $data['kategori_member_kunjungan'] == 'Platinum') ? 'Platinum' :
                      (($data['status_member'] == 'Gold' || $data['kategori_member_kunjungan'] == 'Gold') ? 'Gold' :
                      (($data['status_member'] == 'Silver' || $data['kategori_member_kunjungan'] == 'Silver') ? 'Silver' : 'Bronze'));
    
    $benefits = getMemberBenefits($highest_member);
    
    ob_start();
    ?>
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
        <div class="row">
            <!-- Kolom Kiri: Member Status -->
            <div class="col-xs-12 col-sm-6">
                <!-- Member Nominal -->
                <div style="background: #fff; border: 2px solid #e3e6ea; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: #495057; font-size: 13px;">
                            <i class="fa fa-money" style="color: #28a745;"></i> Member Berdasarkan Nominal
                        </strong>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <?php echo $status_badge_nominal; ?>
                    </div>
                    <div style="font-size: 11px; color: #6c757d;">
                        <strong>Total:</strong> Rp <?php echo number_format($data['total_nominal'], 0, ',', '.'); ?><br>
                        <strong>Rata-rata:</strong> Rp <?php echo number_format($data['rata_rata_transaksi'], 0, ',', '.'); ?>
                    </div>
                </div>
                
                <!-- Member Kunjungan -->
                <div style="background: #fff; border: 2px solid #e3e6ea; border-radius: 6px; padding: 12px;">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: #495057; font-size: 13px;">
                            <i class="fa fa-users" style="color: #007bff;"></i> Member Berdasarkan Kunjungan
                        </strong>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <?php echo $status_badge_kunjungan; ?>
                    </div>
                    <div style="font-size: 11px; color: #6c757d;">
                        <strong>Total Kunjungan:</strong> <?php echo $data['jumlah_kunjungan']; ?>x<br>
                        <strong>Kedatangan Ke:</strong> <?php echo $data['kedatangan_terakhir']; ?>
                        <?php if($data['rata_jarak_kunjungan'] > 0): ?>
                        <br><strong>Rata-rata Jarak:</strong> <?php echo number_format($data['rata_jarak_kunjungan'], 0); ?> hari
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Kolom Kanan: Benefit & Info -->
            <div class="col-xs-12 col-sm-6">
                <!-- Benefit -->
                <div style="background: #fff; border: 2px solid #e3e6ea; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                    <div style="margin-bottom: 8px;">
                        <strong style="color: #495057; font-size: 13px;">
                            <i class="fa fa-gift" style="color: #ffc107;"></i> Benefit Member
                        </strong>
                    </div>
                    <ul style="margin: 0; padding-left: 18px; font-size: 11px; color: #6c757d;">
                        <?php foreach($benefits as $benefit): ?>
                        <li style="margin-bottom: 3px;"><?php echo $benefit; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Info Terakhir Datang -->
                <?php if($data['lama_tidak_datang'] > 0 && $data['tanggal_terakhir_transaksi']): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
                    <div style="font-size: 11px; color: #856404;">
                        <i class="fa fa-clock-o"></i> 
                        <strong>Terakhir Datang:</strong><br>
                        <?php echo date('d/m/Y', strtotime($data['tanggal_terakhir_transaksi'])); ?>
                        <span style="color: #dc3545;">(<?php echo $data['lama_tidak_datang']; ?> hari lalu)</span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tombol Dashboard -->
                <div style="text-align: center;">
                    <a href="statistik_pelanggan_dashboard.php" target="_blank" 
                       class="btn btn-sm btn-primary" 
                       style="font-size: 11px; padding: 5px 12px;">
                        <i class="fa fa-bar-chart"></i> Lihat Dashboard Lengkap
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Function untuk tampilkan info COMPACT (untuk space terbatas)
function displayStatistikPelangganCompact($koneksi, $no_pelanggan) {
    $data = getStatusMemberPelanggan($koneksi, $no_pelanggan);
    
    if(!$data) return '';
    
    $status_badge_nominal = displayStatusMemberBadge($data['status_member'], false);
    $status_badge_kunjungan = displayStatusMemberBadge($data['kategori_member_kunjungan'], false);
    
    ob_start();
    ?>
    <div style="display: inline-block; padding: 8px 12px; background: #f5f5f5; border-radius: 8px; margin-right: 10px;">
        <small style="color: #666;">Member (Nominal):</small>
        <?php echo $status_badge_nominal; ?>
    </div>
    <div style="display: inline-block; padding: 8px 12px; background: #f5f5f5; border-radius: 8px;">
        <small style="color: #666;">Member (Kunjungan):</small>
        <?php echo $status_badge_kunjungan; ?>
        <small style="color: #666; margin-left: 5px;">(<?php echo $data['jumlah_kunjungan']; ?>x)</small>
    </div>
    <?php
    return ob_get_clean();
}

// Function untuk get icon member
function getStatusMemberIcon($status_member) {
    $icons = [
        'Bronze' => '🥉',
        'Silver' => '🥈',
        'Gold' => '🥇',
        'Platinum' => '💎'
    ];
    
    return $icons[$status_member] ?? '🥉';
}

// ========================================
// FUNCTION BARU: GET DATA STATISTIK PELANGGAN LENGKAP UNTUK MODAL
// ========================================

function getStatistikPelangganLengkap($koneksi, $no_pelanggan) {
    // Query data pelanggan lengkap
    $query = "SELECT 
                p.nopelanggan,
                p.namapelanggan,
                p.alamat,
                p.telephone,
                p.kota,
                p.propinsi,
                sp.status_member,
                sp.kategori_member_kunjungan,
                sp.total_nominal,
                sp.total_transaksi,
                sp.jumlah_kunjungan,
                sp.kedatangan_terakhir,
                sp.rata_rata_transaksi,
                sp.rata_jarak_kunjungan,
                sp.tanggal_terakhir_transaksi,
                sp.tanggal_pertama_transaksi,
                sp.lama_tidak_datang,
                sp.lama_menjadi_pelanggan,
                sp.estimasi_datang_berikutnya,
                sp.total_motor
              FROM tblpelanggan p
              LEFT JOIN statistik_pelanggan sp ON p.nopelanggan = sp.no_pelanggan
              WHERE p.nopelanggan = '$no_pelanggan'";
    
    $result = mysqli_query($koneksi, $query);
    $data_pelanggan = mysqli_fetch_assoc($result);
    
    if(!$data_pelanggan) {
        return null;
    }
    
    // Hitung transaksi terbesar dan terkecil secara manual
    $query_minmax = "SELECT 
                        MAX(total_akhir) as transaksi_terbesar,
                        MIN(total_akhir) as transaksi_terkecil
                     FROM tblservice
                     WHERE no_pelanggan = '$no_pelanggan'
                       AND status_servis IN ('bayar', 'selesai')
                       AND total_akhir > 0";
    
    $result_minmax = mysqli_query($koneksi, $query_minmax);
    if($result_minmax && mysqli_num_rows($result_minmax) > 0) {
        $minmax = mysqli_fetch_assoc($result_minmax);
        $data_pelanggan['transaksi_terbesar'] = $minmax['transaksi_terbesar'] ?? 0;
        $data_pelanggan['transaksi_terkecil'] = $minmax['transaksi_terkecil'] ?? 0;
    } else {
        $data_pelanggan['transaksi_terbesar'] = 0;
        $data_pelanggan['transaksi_terkecil'] = 0;
    }
    
    // Query data kendaraan
    // Note: nopelanggan di tblpelanggan adalah nomor polisi kendaraan
    $query_kendaraan = "SELECT 
                            k.nopolisi,
                            k.jenis,
                            k.tipe,
                            k.warna,
                            pm.merek,
                            COUNT(s.no_service) as total_service
                        FROM tblkendaraan k
                        LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                        LEFT JOIN tblservice s ON k.nopolisi = s.no_polisi AND s.status_servis IN ('bayar', 'selesai')
                        WHERE k.nopolisi = '$no_pelanggan'
                        GROUP BY k.nopolisi
                        ORDER BY total_service DESC";
    
    $result_kendaraan = mysqli_query($koneksi, $query_kendaraan);
    $data_kendaraan = [];
    while($row = mysqli_fetch_assoc($result_kendaraan)) {
        $data_kendaraan[] = $row;
    }
    
    // Query riwayat transaksi terakhir (5 terakhir)
    $query_riwayat = "SELECT 
                        s.no_service,
                        s.tanggal,
                        s.total_akhir,
                        s.no_polisi,
                        k.jenis,
                        k.tipe
                      FROM tblservice s
                      LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
                      WHERE s.no_pelanggan = '$no_pelanggan'
                        AND s.status_servis IN ('bayar', 'selesai')
                      ORDER BY s.tanggal DESC
                      LIMIT 5";
    
    $result_riwayat = mysqli_query($koneksi, $query_riwayat);
    $data_riwayat = [];
    while($row = mysqli_fetch_assoc($result_riwayat)) {
        $data_riwayat[] = $row;
    }
    
    // Query benefit dari master kategori member
    $status_member_tertinggi = ($data_pelanggan['status_member'] == 'Platinum' || $data_pelanggan['kategori_member_kunjungan'] == 'Platinum') ? 'Platinum' :
                               (($data_pelanggan['status_member'] == 'Gold' || $data_pelanggan['kategori_member_kunjungan'] == 'Gold') ? 'Gold' :
                               (($data_pelanggan['status_member'] == 'Silver' || $data_pelanggan['kategori_member_kunjungan'] == 'Silver') ? 'Silver' : 'Bronze'));
    
    $query_benefit = "SELECT benefit_text, diskon_persen 
                      FROM master_kategori_member 
                      WHERE nama_kategori = '$status_member_tertinggi' 
                        AND tipe_kategori = 'nominal' 
                        AND is_active = 1 
                      LIMIT 1";
    
    $result_benefit = mysqli_query($koneksi, $query_benefit);
    $benefit_data = mysqli_fetch_assoc($result_benefit);
    
    return [
        'pelanggan' => $data_pelanggan,
        'kendaraan' => $data_kendaraan,
        'riwayat' => $data_riwayat,
        'benefit' => $benefit_data,
        'status_tertinggi' => $status_member_tertinggi
    ];
}

// Function untuk render modal statistik pelanggan lengkap
function renderModalStatistikPelanggan($koneksi, $no_pelanggan) {
    $data = getStatistikPelangganLengkap($koneksi, $no_pelanggan);
    
    if(!$data) {
        return '<div class="alert alert-warning">Data pelanggan tidak ditemukan</div>';
    }
    
    $p = $data['pelanggan'];
    $kendaraan = $data['kendaraan'];
    $riwayat = $data['riwayat'];
    $benefit = $data['benefit'];
    $status_tertinggi = $data['status_tertinggi'];
    
    $icon_nominal = getStatusMemberIcon($p['status_member']);
    $icon_kunjungan = getStatusMemberIcon($p['kategori_member_kunjungan']);
    
    ob_start();
    ?>
    <!-- Modal Statistik Pelanggan -->
    <div class="modal fade" id="modalStatistikPelanggan" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" style="width: 90%; max-width: 1000px;">
            <div class="modal-content">
                <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#9ca3af;opacity:1;text-shadow:none;">&times;</button>
                    <h4 class="modal-title" style="color:#1f2937;font-size:17px;font-weight:600;">
                        <i class="fa fa-user-circle" style="color:#4f46e5;margin-right:6px;"></i> Statistik Pelanggan Lengkap
                    </h4>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    
                    <!-- Info Pelanggan -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="widget-box" style="border: 2px solid #667eea;">
                                <div class="widget-header widget-header-flat" style="background: #667eea; color: #fff;">
                                    <h5 class="widget-title"><i class="fa fa-user"></i> Informasi Pelanggan</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <table class="table table-borderless" style="margin-bottom: 0;">
                                                    <tr>
                                                        <td width="40%"><strong>Nama</strong></td>
                                                        <td>: <?php echo htmlspecialchars($p['namapelanggan']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>No. Pelanggan</strong></td>
                                                        <td>: <?php echo htmlspecialchars($p['nopelanggan']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Telepon</strong></td>
                                                        <td>: <?php echo htmlspecialchars($p['telephone']); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-sm-6">
                                                <table class="table table-borderless" style="margin-bottom: 0;">
                                                    <tr>
                                                        <td width="40%"><strong>Alamat</strong></td>
                                                        <td>: <?php echo htmlspecialchars($p['alamat']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Kota</strong></td>
                                                        <td>: <?php echo htmlspecialchars($p['kota']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Pelanggan Sejak</strong></td>
                                                        <td>: <?php echo $p['tanggal_pertama_transaksi'] ? date('d/m/Y', strtotime($p['tanggal_pertama_transaksi'])) : '-'; ?>
                                                            <?php if($p['lama_menjadi_pelanggan']): ?>
                                                            <span class="label label-info"><?php echo $p['lama_menjadi_pelanggan']; ?> hari</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-10"></div>
                    
                    <!-- Status Member -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="widget-box" style="border: 2px solid #28a745;">
                                <div class="widget-header widget-header-flat" style="background: #28a745; color: #fff;">
                                    <h5 class="widget-title"><i class="fa fa-money"></i> Member Berdasarkan Nominal</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main" style="padding: 15px;">
                                        <div style="text-align: center; margin-bottom: 15px;">
                                            <span style="font-size: 48px;"><?php echo $icon_nominal; ?></span>
                                            <h3 style="margin: 10px 0;"><?php echo displayStatusMemberBadge($p['status_member']); ?></h3>
                                        </div>
                                        <table class="table table-striped table-bordered">
                                            <tr>
                                                <td><strong>Total Nominal</strong></td>
                                                <td class="text-right"><strong style="color: #28a745;">Rp <?php echo number_format($p['total_nominal'], 0, ',', '.'); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Rata-rata/Transaksi</strong></td>
                                                <td class="text-right">Rp <?php echo number_format($p['rata_rata_transaksi'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Transaksi Terbesar</strong></td>
                                                <td class="text-right">Rp <?php echo number_format($p['transaksi_terbesar'], 0, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Transaksi Terkecil</strong></td>
                                                <td class="text-right">Rp <?php echo number_format($p['transaksi_terkecil'], 0, ',', '.'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="widget-box" style="border: 2px solid #007bff;">
                                <div class="widget-header widget-header-flat" style="background: #007bff; color: #fff;">
                                    <h5 class="widget-title"><i class="fa fa-users"></i> Member Berdasarkan Kunjungan</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main" style="padding: 15px;">
                                        <div style="text-align: center; margin-bottom: 15px;">
                                            <span style="font-size: 48px;"><?php echo $icon_kunjungan; ?></span>
                                            <h3 style="margin: 10px 0;"><?php echo displayStatusMemberBadge($p['kategori_member_kunjungan']); ?></h3>
                                        </div>
                                        <table class="table table-striped table-bordered">
                                            <tr>
                                                <td><strong>Total Kunjungan</strong></td>
                                                <td class="text-right"><strong style="color: #007bff;"><?php echo $p['jumlah_kunjungan']; ?>x</strong></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Kedatangan Ke</strong></td>
                                                <td class="text-right"><?php echo $p['kedatangan_terakhir']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Rata Jarak Kunjungan</strong></td>
                                                <td class="text-right"><?php echo number_format($p['rata_jarak_kunjungan'], 0); ?> hari</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Terakhir Datang</strong></td>
                                                <td class="text-right">
                                                    <?php if($p['tanggal_terakhir_transaksi']): ?>
                                                    <?php echo date('d/m/Y', strtotime($p['tanggal_terakhir_transaksi'])); ?>
                                                    <br><small class="text-danger">(<?php echo $p['lama_tidak_datang']; ?> hari lalu)</small>
                                                    <?php else: ?>
                                                    -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-10"></div>
                    
                    <!-- Benefit Member -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning" style="background: #fff3cd; border: 2px solid #ffc107;">
                                <h4 style="margin-top: 0;"><i class="fa fa-gift"></i> Benefit Member <?php echo $status_tertinggi; ?></h4>
                                <?php if($benefit && $benefit['benefit_text']): ?>
                                <div style="margin-top: 10px;">
                                    <strong>Diskon:</strong> <span class="label label-success" style="font-size: 14px;"><?php echo $benefit['diskon_persen']; ?>%</span>
                                </div>
                                <div style="margin-top: 10px; white-space: pre-line;">
                                    <?php echo htmlspecialchars($benefit['benefit_text']); ?>
                                </div>
                                <?php else: ?>
                                <p>Benefit member standar</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kendaraan Terdaftar -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat" style="background: #17a2b8; color: #fff;">
                                    <h5 class="widget-title"><i class="fa fa-motorcycle"></i> Kendaraan Terdaftar (<?php echo count($kendaraan); ?> Motor)</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>No. Polisi</th>
                                                    <th>Merek</th>
                                                    <th>Tipe</th>
                                                    <th>Jenis</th>
                                                    <th>Warna</th>
                                                    <th class="text-center">Total Service</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(count($kendaraan) > 0): ?>
                                                    <?php foreach($kendaraan as $k): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($k['nopolisi']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($k['merek']); ?></td>
                                                        <td><?php echo htmlspecialchars($k['tipe']); ?></td>
                                                        <td><?php echo htmlspecialchars($k['jenis']); ?></td>
                                                        <td><?php echo htmlspecialchars($k['warna']); ?></td>
                                                        <td class="text-center">
                                                            <span class="label label-info"><?php echo $k['total_service']; ?>x</span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">Belum ada kendaraan terdaftar</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-10"></div>
                    
                    <!-- Riwayat Transaksi Terakhir -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat" style="background: #6c757d; color: #fff;">
                                    <h5 class="widget-title"><i class="fa fa-history"></i> Riwayat Transaksi Terakhir (5 Terakhir)</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main no-padding">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>No. Service</th>
                                                    <th>Kendaraan</th>
                                                    <th class="text-right">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(count($riwayat) > 0): ?>
                                                    <?php foreach($riwayat as $r): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($r['tanggal'])); ?></td>
                                                        <td><strong><?php echo htmlspecialchars($r['no_service']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($r['no_polisi']); ?> - <?php echo htmlspecialchars($r['jenis']); ?> <?php echo htmlspecialchars($r['tipe']); ?></td>
                                                        <td class="text-right"><strong>Rp <?php echo number_format($r['total_akhir'], 0, ',', '.'); ?></strong></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Belum ada riwayat transaksi</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Tutup
                    </button>
                    <a href="detail_pelanggan.php?nopelanggan=<?php echo urlencode($no_pelanggan); ?>" target="_blank" class="btn btn-primary">
                        <i class="fa fa-external-link"></i> Lihat Detail Lengkap
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ========================================
// FUNCTION BARU: HISTORY SERVICE PELANGGAN
// ========================================

/**
 * Get diskon persen berdasarkan status member dari master_kategori_member
 *
 * @param mysqli $koneksi Database connection
 * @param string $status_member Status member (Bronze/Silver/Gold/Platinum)
 * @return float Persentase diskon
 */
function getDiskonPersenByTier($koneksi, $status_member) {
    $status_member = mysqli_real_escape_string($koneksi, $status_member);

    $query = "SELECT diskon_persen FROM master_kategori_member
              WHERE nama_kategori = '$status_member'
              AND tipe_kategori = 'nominal'
              AND is_active = 1
              LIMIT 1";

    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return floatval($row['diskon_persen']);
    }

    // Default diskon berdasarkan tier
    $default_diskon = [
        'Bronze' => 0,
        'Silver' => 10,
        'Gold' => 15,
        'Platinum' => 20
    ];

    return $default_diskon[$status_member] ?? 0;
}

/**
 * Get diskon detail (jasa & barang) berdasarkan status member
 * Mengecek setting global terlebih dahulu
 *
 * @param mysqli $koneksi Database connection
 * @param string $status_member Status member (Bronze/Silver/Gold/Platinum)
 * @param string $type 'nominal' or 'kunjungan' - which system to use
 * @return array ['diskon_jasa' => float, 'diskon_barang' => float]
 */
function getDiskonDetailByTier($koneksi, $status_member, $type = 'nominal') {
    $status_member = mysqli_real_escape_string($koneksi, $status_member);
    $type = mysqli_real_escape_string($koneksi, $type);
    
    // Check if this type is enabled globally
    if (!isKategoriMemberEnabled($koneksi, $type)) {
        // If disabled, return zero discounts
        return [
            'diskon_jasa' => 0,
            'diskon_barang' => 0
        ];
    }

    $query = "SELECT diskon_jasa, diskon_barang, diskon_persen 
              FROM master_kategori_member
              WHERE nama_kategori = '$status_member'
              AND tipe_kategori = '$type'
              AND is_active = 1
              LIMIT 1";

    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return [
            'diskon_jasa' => floatval($row['diskon_jasa'] ?? $row['diskon_persen'] ?? 0),
            'diskon_barang' => floatval($row['diskon_barang'] ?? 0)
        ];
    }

    // Default diskon berdasarkan tier
    $default_diskon = [
        'Bronze' => ['diskon_jasa' => 0, 'diskon_barang' => 0],
        'Silver' => ['diskon_jasa' => 10, 'diskon_barang' => 3],
        'Gold' => ['diskon_jasa' => 15, 'diskon_barang' => 5],
        'Platinum' => ['diskon_jasa' => 20, 'diskon_barang' => 10]
    ];

    return $default_diskon[$status_member] ?? ['diskon_jasa' => 0, 'diskon_barang' => 0];
}

/**
 * Get the effective diskon for a customer
 * If nominal is disabled, use kunjungan tier; if both disabled, return zero
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Customer number
 * @return array ['diskon_jasa' => float, 'diskon_barang' => float, 'tier_used' => string, 'type_used' => string]
 */
function getEffectiveDiskonPelanggan($koneksi, $no_pelanggan) {
    $no_pelanggan = mysqli_real_escape_string($koneksi, $no_pelanggan);
    
    // Get customer statistics
    $data = getStatusMemberPelanggan($koneksi, $no_pelanggan);
    
    $nominal_enabled = isKategoriMemberEnabled($koneksi, 'nominal');
    $kunjungan_enabled = isKategoriMemberEnabled($koneksi, 'kunjungan');
    
    // Determine which tier and type to use
    if ($nominal_enabled) {
        // Prefer nominal system
        $tier = $data['status_member'] ?? 'Bronze';
        $type = 'nominal';
    } elseif ($kunjungan_enabled) {
        // Fallback to kunjungan system
        $tier = $data['kategori_member_kunjungan'] ?? 'Bronze';
        $type = 'kunjungan';
    } else {
        // Both disabled - no discount
        return [
            'diskon_jasa' => 0,
            'diskon_barang' => 0,
            'tier_used' => 'Bronze',
            'type_used' => 'none'
        ];
    }
    
    $diskon = getDiskonDetailByTier($koneksi, $tier, $type);
    
    return [
        'diskon_jasa' => $diskon['diskon_jasa'],
        'diskon_barang' => $diskon['diskon_barang'],
        'tier_used' => $tier,
        'type_used' => $type
    ];
}

/**
 * Simpan history lengkap service pelanggan saat pembayaran
 * Termasuk keluhan, temuan, pengerjaan, barang, jasa, dan mekanik
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_service Nomor service
 * @param string $tipe_service Tipe service (reguler/jemput/garansi)
 * @return array Result with status and message
 */
function saveHistoryServicePelanggan($koneksi, $no_service, $tipe_service = 'reguler') {
    $no_service = mysqli_real_escape_string($koneksi, $no_service);

    // 1. Ambil data service utama
    $query_service = "SELECT s.*,
                             p.namapelanggan, p.telephone,
                             k.jenis as jenis_motor, k.tipe as tipe_motor_detail,
                             pm.merek as merek_motor,
                             c.nama as nama_cabang
                      FROM tblservice s
                      LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                      LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
                      LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                      LEFT JOIN tblcabang c ON s.kd_cabang = c.kd_cabang
                      WHERE s.no_service = '$no_service'";

    $result_service = mysqli_query($koneksi, $query_service);

    if (!$result_service || mysqli_num_rows($result_service) == 0) {
        return ['status' => false, 'message' => 'Service tidak ditemukan'];
    }

    $service = mysqli_fetch_assoc($result_service);

    // 2. Ambil status member sebelum transaksi ini
    $status_member_sebelum = 'Bronze';
    $query_stat = "SELECT status_member FROM statistik_pelanggan WHERE no_pelanggan = '" .
                  mysqli_real_escape_string($koneksi, $service['no_pelanggan']) . "'";
    $result_stat = mysqli_query($koneksi, $query_stat);
    if ($result_stat && mysqli_num_rows($result_stat) > 0) {
        $stat_data = mysqli_fetch_assoc($result_stat);
        $status_member_sebelum = $stat_data['status_member'];
    }

    // 3. Ambil keluhan
    $query_keluhan = "SELECT keluhan, kode_keluhan, status_pengerjaan, keterangan_tidak_selesai
                      FROM tbservis_keluhan_status
                      WHERE no_service = '$no_service'";
    $result_keluhan = mysqli_query($koneksi, $query_keluhan);
    $keluhan_list = [];
    $jumlah_keluhan = 0;
    while ($row = mysqli_fetch_assoc($result_keluhan)) {
        $keluhan_list[] = $row;
        $jumlah_keluhan++;
    }

    // 4. Ambil temuan
    $query_temuan = "SELECT t.*, m.nama_temuan
                     FROM tbservis_temuan t
                     LEFT JOIN tbmaster_temuan m ON t.kode_temuan = m.kode_temuan
                     WHERE t.no_service = '$no_service'";
    $result_temuan = mysqli_query($koneksi, $query_temuan);
    $temuan_list = [];
    $jumlah_temuan = 0;
    $temuan_disetujui = 0;
    $temuan_ditolak = 0;
    while ($row = mysqli_fetch_assoc($result_temuan)) {
        $temuan_list[] = $row;
        $jumlah_temuan++;
        if ($row['status_temuan'] == 'disetujui' || $row['status_temuan'] == 'selesai') {
            $temuan_disetujui++;
        } elseif ($row['status_temuan'] == 'ditolak') {
            $temuan_ditolak++;
        }
    }

    // 5. Ambil workorder
    $query_wo = "SELECT w.*, m.nama_wo
                 FROM tbservis_workorder w
                 LEFT JOIN tbmaster_workorder m ON w.kode_wo = m.kode_wo
                 WHERE w.no_service = '$no_service'";
    $result_wo = mysqli_query($koneksi, $query_wo);
    $workorder_list = [];
    $jumlah_workorder = 0;
    while ($row = mysqli_fetch_assoc($result_wo)) {
        $workorder_list[] = $row;
        $jumlah_workorder++;
    }

    // 6. Ambil barang/sparepart
    $query_barang = "SELECT sb.*, i.namaitem
                     FROM tbservis_barang sb
                     LEFT JOIN tblitem i ON sb.kd_item = i.noitem
                     WHERE sb.no_service = '$no_service'";
    $result_barang = mysqli_query($koneksi, $query_barang);
    $barang_list = [];
    $jumlah_barang = 0;
    while ($row = mysqli_fetch_assoc($result_barang)) {
        $barang_list[] = $row;
        $jumlah_barang++;
    }

    // 7. Ambil jasa
    $query_jasa = "SELECT sj.*, j.nama_jasa
                   FROM tbservis_jasa sj
                   LEFT JOIN tbjasa j ON sj.kd_jasa = j.kd_jasa
                   WHERE sj.no_service = '$no_service'";
    $result_jasa = mysqli_query($koneksi, $query_jasa);
    $jasa_list = [];
    $jumlah_jasa = 0;
    while ($row = mysqli_fetch_assoc($result_jasa)) {
        $jasa_list[] = $row;
        $jumlah_jasa++;
    }

    // 8. Hitung nilai diskon
    $subtotal = floatval($service['total_service'] ?? 0) + floatval($service['total_barang'] ?? 0);
    $diskon_member_persen = floatval($service['potongan'] ?? 0);
    $diskon_member_nominal = $subtotal * ($diskon_member_persen / 100);
    $diskon_tambahan_persen = floatval($service['pot_faktur'] ?? 0);
    $diskon_tambahan_nominal = $subtotal * ($diskon_tambahan_persen / 100);
    $total_diskon = $diskon_member_nominal + $diskon_tambahan_nominal;
    $ppn_persen = floatval($service['pajak'] ?? 0);
    $setelah_diskon = $subtotal - $total_diskon;
    $ppn_nominal = $setelah_diskon * ($ppn_persen / 100);

    // 9. Escape JSON data
    $keluhan_json = mysqli_real_escape_string($koneksi, json_encode($keluhan_list, JSON_UNESCAPED_UNICODE));
    $temuan_json = mysqli_real_escape_string($koneksi, json_encode($temuan_list, JSON_UNESCAPED_UNICODE));
    $workorder_json = mysqli_real_escape_string($koneksi, json_encode($workorder_list, JSON_UNESCAPED_UNICODE));
    $barang_json = mysqli_real_escape_string($koneksi, json_encode($barang_list, JSON_UNESCAPED_UNICODE));
    $jasa_json = mysqli_real_escape_string($koneksi, json_encode($jasa_list, JSON_UNESCAPED_UNICODE));

    // 10. Insert ke tb_history_service_pelanggan
    $query_insert = "INSERT INTO tb_history_service_pelanggan (
        no_pelanggan, no_polisi, no_service, tanggal_service, jam_service, tipe_service,
        total_jasa, total_barang, subtotal,
        diskon_member_persen, diskon_member_nominal,
        diskon_tambahan_persen, diskon_tambahan_nominal, total_diskon,
        ppn_persen, ppn_nominal, total_bayar, jumlah_bayar, kembalian, metode_pembayaran,
        km_service, tipe_motor, merek_motor,
        keluhan_list, jumlah_keluhan,
        temuan_list, jumlah_temuan, temuan_disetujui, temuan_ditolak,
        workorder_list, jumlah_workorder,
        barang_list, jumlah_item_barang,
        jasa_list, jumlah_item_jasa,
        kepala_mekanik1, kepala_mekanik2, persen_kepala1, persen_kepala2,
        admin1, admin2, persen_admin1, persen_admin2,
        mekanik1, mekanik2, mekanik3, mekanik4,
        persen_mekanik1, persen_mekanik2, persen_mekanik3, persen_mekanik4,
        status_member_sebelum, status_member_sesudah, naik_tier,
        keterangan, kode_cabang, nama_cabang,
        user_bayar
    ) VALUES (
        '" . mysqli_real_escape_string($koneksi, $service['no_pelanggan']) . "',
        '" . mysqli_real_escape_string($koneksi, $service['no_polisi']) . "',
        '$no_service',
        '" . mysqli_real_escape_string($koneksi, $service['tanggal']) . "',
        '" . mysqli_real_escape_string($koneksi, $service['jam']) . "',
        '$tipe_service',
        " . floatval($service['total_service'] ?? 0) . ",
        " . floatval($service['total_barang'] ?? 0) . ",
        $subtotal,
        $diskon_member_persen,
        $diskon_member_nominal,
        $diskon_tambahan_persen,
        $diskon_tambahan_nominal,
        $total_diskon,
        $ppn_persen,
        $ppn_nominal,
        " . floatval($service['total_akhir'] ?? 0) . ",
        " . floatval($service['bayar'] ?? 0) . ",
        " . floatval($service['kembalian'] ?? 0) . ",
        '" . mysqli_real_escape_string($koneksi, $service['metode_pembayaran'] ?? 'Tunai') . "',
        " . intval($service['km_skr'] ?? 0) . ",
        '" . mysqli_real_escape_string($koneksi, $service['tipe_motor_detail'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['merek_motor'] ?? '') . "',
        '$keluhan_json', $jumlah_keluhan,
        '$temuan_json', $jumlah_temuan, $temuan_disetujui, $temuan_ditolak,
        '$workorder_json', $jumlah_workorder,
        '$barang_json', $jumlah_barang,
        '$jasa_json', $jumlah_jasa,
        '" . mysqli_real_escape_string($koneksi, $service['kepala_mekanik1'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['kepala_mekanik2'] ?? '') . "',
        " . intval($service['persen_kepala_mekanik1'] ?? 0) . ",
        " . intval($service['persen_kepala_mekanik2'] ?? 0) . ",
        '" . mysqli_real_escape_string($koneksi, $service['admin1'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['admin2'] ?? '') . "',
        " . intval($service['persen_admin1'] ?? 0) . ",
        " . intval($service['persen_admin2'] ?? 0) . ",
        '" . mysqli_real_escape_string($koneksi, $service['mekanik1'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['mekanik2'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['mekanik3'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['mekanik4'] ?? '') . "',
        " . intval($service['persen_mekanik1'] ?? 0) . ",
        " . intval($service['persen_mekanik2'] ?? 0) . ",
        " . intval($service['persen_mekanik3'] ?? 0) . ",
        " . intval($service['persen_mekanik4'] ?? 0) . ",
        '$status_member_sebelum',
        '$status_member_sebelum',
        0,
        '" . mysqli_real_escape_string($koneksi, $service['keterangan'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['kd_cabang'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $service['nama_cabang'] ?? '') . "',
        '" . mysqli_real_escape_string($koneksi, $_SESSION['username'] ?? 'system') . "'
    ) ON DUPLICATE KEY UPDATE
        total_jasa = VALUES(total_jasa),
        total_barang = VALUES(total_barang),
        subtotal = VALUES(subtotal),
        diskon_member_persen = VALUES(diskon_member_persen),
        diskon_member_nominal = VALUES(diskon_member_nominal),
        total_bayar = VALUES(total_bayar),
        jumlah_bayar = VALUES(jumlah_bayar),
        kembalian = VALUES(kembalian),
        keluhan_list = VALUES(keluhan_list),
        temuan_list = VALUES(temuan_list),
        workorder_list = VALUES(workorder_list),
        barang_list = VALUES(barang_list),
        jasa_list = VALUES(jasa_list),
        updated_at = NOW()";

    $success = mysqli_query($koneksi, $query_insert);

    if (!$success) {
        error_log("Failed to save history service: " . mysqli_error($koneksi));
        return ['status' => false, 'message' => 'Gagal menyimpan history: ' . mysqli_error($koneksi)];
    }

    // 11. Simpan history mekanik
    saveHistoryMekanikServis($koneksi, $no_service, $service);

    // 12. Log success
    error_log("History service saved for: $no_service");

    return ['status' => true, 'message' => 'History service berhasil disimpan'];
}

/**
 * Simpan history mekanik per service
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_service Nomor service
 * @param array $service Data service
 */
function saveHistoryMekanikServis($koneksi, $no_service, $service) {
    $no_service = mysqli_real_escape_string($koneksi, $no_service);
    $tanggal = mysqli_real_escape_string($koneksi, $service['tanggal']);
    $kd_cabang = mysqli_real_escape_string($koneksi, $service['kd_cabang'] ?? '');
    $total_jasa = floatval($service['total_service'] ?? 0);

    // Hapus history lama untuk service ini (jika ada update)
    mysqli_query($koneksi, "DELETE FROM tb_history_mekanik_servis WHERE no_service = '$no_service'");

    $mekanik_data = [];

    // Kepala Mekanik 1
    if (!empty($service['kepala_mekanik1'])) {
        $persen = intval($service['persen_kepala_mekanik1'] ?? 0);
        $mekanik_data[] = [
            'tipe_role' => 'kepala_mekanik',
            'urutan' => 1,
            'nama' => $service['kepala_mekanik1'],
            'persen' => $persen,
            'pendapatan' => $total_jasa * ($persen / 100)
        ];
    }

    // Kepala Mekanik 2
    if (!empty($service['kepala_mekanik2'])) {
        $persen = intval($service['persen_kepala_mekanik2'] ?? 0);
        $mekanik_data[] = [
            'tipe_role' => 'kepala_mekanik',
            'urutan' => 2,
            'nama' => $service['kepala_mekanik2'],
            'persen' => $persen,
            'pendapatan' => $total_jasa * ($persen / 100)
        ];
    }

    // Admin 1
    if (!empty($service['admin1'])) {
        $persen = intval($service['persen_admin1'] ?? 0);
        $mekanik_data[] = [
            'tipe_role' => 'admin',
            'urutan' => 1,
            'nama' => $service['admin1'],
            'persen' => $persen,
            'pendapatan' => $total_jasa * ($persen / 100)
        ];
    }

    // Admin 2
    if (!empty($service['admin2'])) {
        $persen = intval($service['persen_admin2'] ?? 0);
        $mekanik_data[] = [
            'tipe_role' => 'admin',
            'urutan' => 2,
            'nama' => $service['admin2'],
            'persen' => $persen,
            'pendapatan' => $total_jasa * ($persen / 100)
        ];
    }

    // Mekanik 1-4
    for ($i = 1; $i <= 4; $i++) {
        $key = "mekanik$i";
        $persen_key = "persen_mekanik$i";
        if (!empty($service[$key])) {
            $persen = intval($service[$persen_key] ?? 0);
            $mekanik_data[] = [
                'tipe_role' => 'mekanik',
                'urutan' => $i,
                'nama' => $service[$key],
                'persen' => $persen,
                'pendapatan' => $total_jasa * ($persen / 100)
            ];
        }
    }

    // Insert semua mekanik
    foreach ($mekanik_data as $m) {
        $query = "INSERT INTO tb_history_mekanik_servis
                  (no_service, tanggal_service, tipe_role, urutan, nama_karyawan, persen_kerja, total_jasa_service, pendapatan_jasa, kode_cabang)
                  VALUES (
                      '$no_service',
                      '$tanggal',
                      '{$m['tipe_role']}',
                      {$m['urutan']},
                      '" . mysqli_real_escape_string($koneksi, $m['nama']) . "',
                      {$m['persen']},
                      $total_jasa,
                      {$m['pendapatan']},
                      '$kd_cabang'
                  )";
        mysqli_query($koneksi, $query);
    }
}

/**
 * Cek dan log jika pelanggan naik tier setelah pembayaran
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @param string $no_service Nomor service
 * @param string $tier_lama Tier sebelum transaksi
 * @return array Result with naik_tier status
 */
function checkAndLogNaikTier($koneksi, $no_pelanggan, $no_service, $tier_lama) {
    $no_pelanggan = mysqli_real_escape_string($koneksi, $no_pelanggan);
    $no_service = mysqli_real_escape_string($koneksi, $no_service);

    // Ambil tier baru dari statistik
    $query = "SELECT sp.status_member, sp.total_nominal, sp.jumlah_kunjungan,
                     p.namapelanggan
              FROM statistik_pelanggan sp
              LEFT JOIN tblpelanggan p ON sp.no_pelanggan = p.nopelanggan
              WHERE sp.no_pelanggan = '$no_pelanggan'";

    $result = mysqli_query($koneksi, $query);

    if (!$result || mysqli_num_rows($result) == 0) {
        return ['naik_tier' => false];
    }

    $data = mysqli_fetch_assoc($result);
    $tier_baru = $data['status_member'];

    // Bandingkan tier
    $tier_order = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Platinum' => 4];
    $old_order = $tier_order[$tier_lama] ?? 1;
    $new_order = $tier_order[$tier_baru] ?? 1;

    if ($new_order > $old_order) {
        // Naik tier!
        $diskon_lama = getDiskonPersenByTier($koneksi, $tier_lama);
        $diskon_baru = getDiskonPersenByTier($koneksi, $tier_baru);

        // Insert log naik tier
        $query_log = "INSERT INTO tb_log_naik_tier_member
                      (no_pelanggan, nama_pelanggan, no_service, tier_lama, tier_baru,
                       total_nominal_saat_naik, total_kunjungan_saat_naik,
                       diskon_lama, diskon_baru, kode_cabang)
                      VALUES (
                          '$no_pelanggan',
                          '" . mysqli_real_escape_string($koneksi, $data['namapelanggan']) . "',
                          '$no_service',
                          '$tier_lama',
                          '$tier_baru',
                          " . floatval($data['total_nominal']) . ",
                          " . intval($data['jumlah_kunjungan']) . ",
                          $diskon_lama,
                          $diskon_baru,
                          '" . mysqli_real_escape_string($koneksi, $_SESSION['kd_cabang'] ?? '') . "'
                      )";

        mysqli_query($koneksi, $query_log);

        // Update history service dengan info naik tier
        $query_update = "UPDATE tb_history_service_pelanggan
                         SET status_member_sesudah = '$tier_baru',
                             naik_tier = 1,
                             tier_baru = '$tier_baru'
                         WHERE no_service = '$no_service'";
        mysqli_query($koneksi, $query_update);

        error_log("Customer $no_pelanggan naik tier dari $tier_lama ke $tier_baru");

        return [
            'naik_tier' => true,
            'tier_lama' => $tier_lama,
            'tier_baru' => $tier_baru,
            'diskon_lama' => $diskon_lama,
            'diskon_baru' => $diskon_baru
        ];
    }

    return ['naik_tier' => false];
}

/**
 * Fungsi utama untuk proses after payment
 * Memanggil semua fungsi update dan history
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @param string $no_service Nomor service
 * @param string $tipe_service Tipe service (reguler/jemput/garansi)
 * @return array Result with all update status
 */
function processAfterPayment($koneksi, $no_pelanggan, $no_service, $tipe_service = 'reguler') {
    $result = [
        'statistik' => false,
        'history' => false,
        'naik_tier' => false,
        'tier_info' => null
    ];

    // 1. Simpan tier lama sebelum update
    $tier_lama = 'Bronze';
    $query_old = "SELECT status_member FROM statistik_pelanggan WHERE no_pelanggan = '" .
                 mysqli_real_escape_string($koneksi, $no_pelanggan) . "'";
    $result_old = mysqli_query($koneksi, $query_old);
    if ($result_old && mysqli_num_rows($result_old) > 0) {
        $old_data = mysqli_fetch_assoc($result_old);
        $tier_lama = $old_data['status_member'];
    }

    // 2. Update statistik pelanggan
    $result['statistik'] = updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan, $no_service);

    // 3. Simpan history service lengkap
    $history_result = saveHistoryServicePelanggan($koneksi, $no_service, $tipe_service);
    $result['history'] = $history_result['status'];

    // 4. Cek apakah naik tier
    $tier_check = checkAndLogNaikTier($koneksi, $no_pelanggan, $no_service, $tier_lama);
    $result['naik_tier'] = $tier_check['naik_tier'];
    $result['tier_info'] = $tier_check;

    return $result;
}

/**
 * Get riwayat service pelanggan dari history
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @param int $limit Jumlah record
 * @return array Riwayat service
 */
function getRiwayatServicePelanggan($koneksi, $no_pelanggan, $limit = 10) {
    $no_pelanggan = mysqli_real_escape_string($koneksi, $no_pelanggan);

    $query = "SELECT * FROM tb_history_service_pelanggan
              WHERE no_pelanggan = '$no_pelanggan'
              ORDER BY tanggal_service DESC, created_at DESC
              LIMIT $limit";

    $result = mysqli_query($koneksi, $query);
    $riwayat = [];

    while ($row = mysqli_fetch_assoc($result)) {
        // Decode JSON fields
        $row['keluhan_list'] = json_decode($row['keluhan_list'], true) ?: [];
        $row['temuan_list'] = json_decode($row['temuan_list'], true) ?: [];
        $row['workorder_list'] = json_decode($row['workorder_list'], true) ?: [];
        $row['barang_list'] = json_decode($row['barang_list'], true) ?: [];
        $row['jasa_list'] = json_decode($row['jasa_list'], true) ?: [];
        $riwayat[] = $row;
    }

    return $riwayat;
}

/**
 * Get riwayat service per kendaraan
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_polisi Nomor polisi
 * @param int $limit Jumlah record
 * @return array Riwayat service
 */
function getRiwayatServiceKendaraan($koneksi, $no_polisi, $limit = 10) {
    $no_polisi = mysqli_real_escape_string($koneksi, $no_polisi);

    $query = "SELECT * FROM tb_history_service_pelanggan
              WHERE no_polisi = '$no_polisi'
              ORDER BY tanggal_service DESC, created_at DESC
              LIMIT $limit";

    $result = mysqli_query($koneksi, $query);
    $riwayat = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $row['keluhan_list'] = json_decode($row['keluhan_list'], true) ?: [];
        $row['temuan_list'] = json_decode($row['temuan_list'], true) ?: [];
        $row['workorder_list'] = json_decode($row['workorder_list'], true) ?: [];
        $row['barang_list'] = json_decode($row['barang_list'], true) ?: [];
        $row['jasa_list'] = json_decode($row['jasa_list'], true) ?: [];
        $riwayat[] = $row;
    }

    return $riwayat;
}
?>
