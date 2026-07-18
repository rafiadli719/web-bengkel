<?php
/**
 * HELPER FUNCTIONS: KATEGORI MEMBER & DISKON
 * ============================================================================
 * File ini berisi fungsi-fungsi helper untuk mengakses data kategori member,
 * status member, dan diskon pelanggan dari tabel statistik_pelanggan.
 * 
 * Menggantikan sistem lama (tblpelanggangrup) dengan sistem baru yang otomatis.
 * 
 * @version 1.0
 * @date 5 November 2025
 * ============================================================================
 */

/**
 * Get informasi lengkap kategori member pelanggan
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return array|null Array berisi info member atau null jika tidak ditemukan
 */
function getKategoriMemberPelanggan($koneksi, $no_pelanggan) {
    $no_pelanggan = mysqli_real_escape_string($koneksi, $no_pelanggan);
    
    $query = "SELECT
                s.status_member,
                s.diskon_persen,
                s.total_transaksi,
                s.total_nominal,
                s.rata_rata_transaksi,
                s.tanggal_terakhir_transaksi,
                s.lama_tidak_datang,
                m.benefit,
                m.min_nominal,
                m.max_nominal,
                m.masa_garansi_hari,
                m.masa_garansi_maks_hari
            FROM statistik_pelanggan s
            LEFT JOIN tbmaster_kategori_member m ON s.status_member = m.status_member
            WHERE s.no_pelanggan = '$no_pelanggan'
            LIMIT 1";

    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    // Jika belum ada statistik, return default Bronze
    return [
        'status_member' => 'Bronze',
        'diskon_persen' => 0,
        'total_transaksi' => 0,
        'total_nominal' => 0,
        'rata_rata_transaksi' => 0,
        'tanggal_terakhir_transaksi' => null,
        'lama_tidak_datang' => null,
        'benefit' => 'Pelanggan baru',
        'min_nominal' => 0,
        'max_nominal' => 1999999.99,
        'masa_garansi_hari' => 0,
        'masa_garansi_maks_hari' => 7
    ];
}

/**
 * F1-A: Get masa garansi (hari) pelanggan berdasarkan tier member, dinamis dari
 * tbmaster_kategori_member (editable, bukan hardcode). Jawaban klarifikasi A3 (2026-07-04).
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return array ['standar' => int hari batas hijau, 'maks' => int hari batas kuning/expired]
 */
function getMasaGaransiHari($koneksi, $no_pelanggan) {
    $info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);
    return [
        'standar' => isset($info['masa_garansi_hari']) ? (int)$info['masa_garansi_hari'] : 7,
        'maks' => isset($info['masa_garansi_maks_hari']) ? (int)$info['masa_garansi_maks_hari'] : 14,
    ];
}

/**
 * Get diskon pelanggan (dalam persen)
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return float Persentase diskon (0-100)
 */
function getDiskonPelanggan($koneksi, $no_pelanggan) {
    $info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);
    return floatval($info['diskon_persen']);
}

/**
 * Get status member pelanggan
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return string Status member (Bronze/Silver/Gold/Platinum)
 */
function getStatusMember($koneksi, $no_pelanggan) {
    $info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);
    return $info['status_member'];
}

/**
 * Map nama grup/tipe member dari data master sync fitmotor (mis. "MEMBER GOLD",
 * "MEMBER SIL", "Gold", "BENGKEL") ke tier status_member standar (Bronze/Silver/Gold/Platinum).
 *
 * @param string $grup_atau_tipe Nama grup (tblpelanggangrup.grup) atau tipe member mentah dari sync
 * @return string Bronze|Silver|Gold|Platinum
 */
if (!function_exists('fitmotorMapGrupNameToTier')) {
function fitmotorMapGrupNameToTier($grup_atau_tipe) {
    $normalized = strtoupper(trim((string) $grup_atau_tipe));

    if ($normalized === '') {
        return 'Bronze';
    }
    if (strpos($normalized, 'PLAT') !== false) {
        return 'Platinum';
    }
    if (strpos($normalized, 'GOLD') !== false || substr($normalized, -3) === 'GOL') {
        return 'Gold';
    }
    if (strpos($normalized, 'SILVER') !== false || substr($normalized, -3) === 'SIL') {
        return 'Silver';
    }
    return 'Bronze';
}
}

/**
 * Urutan tier untuk perbandingan (semakin tinggi semakin besar manfaatnya).
 *
 * @param string $tier Bronze|Silver|Gold|Platinum
 * @return int
 */
if (!function_exists('fitmotorMemberTierRank')) {
function fitmotorMemberTierRank($tier) {
    $ranks = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Platinum' => 4];
    return $ranks[$tier] ?? 1;
}
}

/**
 * Pastikan statistik_pelanggan.status_member pelanggan minimal sesuai tier dari
 * data master yang sudah disync dari fitmotor (kgrup/tipe_member), tanpa menurunkan
 * tier yang sudah dihitung dari riwayat transaksi lokal (jumlah_kunjungan/total_nominal).
 * Membuat baris statistik_pelanggan jika belum ada, dan menyelaraskan diskon_persen
 * sesuai master_kategori_member untuk tier hasil akhir.
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @param string $grup_atau_tipe Nama grup (tblpelanggangrup.grup) atau tipe member dari sync
 */
if (!function_exists('fitmotorApplyMemberTierFloor')) {
function fitmotorApplyMemberTierFloor($koneksi, $no_pelanggan, $grup_atau_tipe) {
    $tierFloor = fitmotorMapGrupNameToTier($grup_atau_tipe);
    $no_pelanggan_esc = mysqli_real_escape_string($koneksi, $no_pelanggan);
    $tierFloor_esc = mysqli_real_escape_string($koneksi, $tierFloor);

    $sql = "INSERT INTO statistik_pelanggan (no_pelanggan, status_member)
            VALUES ('$no_pelanggan_esc', '$tierFloor_esc')
            ON DUPLICATE KEY UPDATE
            status_member = IF(
                FIELD(status_member, 'Bronze','Silver','Gold','Platinum') < FIELD(VALUES(status_member), 'Bronze','Silver','Gold','Platinum'),
                VALUES(status_member),
                status_member
            )";
    mysqli_query($koneksi, $sql);

    mysqli_query($koneksi, "UPDATE statistik_pelanggan sp
        JOIN master_kategori_member m ON m.nama_kategori = sp.status_member AND m.tipe_kategori = 'nominal'
        SET sp.diskon_persen = m.diskon_persen
        WHERE sp.no_pelanggan = '$no_pelanggan_esc'");
}
}

/**
 * Hitung total bayar setelah diskon
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @param float $subtotal Subtotal sebelum diskon
 * @return array Array berisi subtotal, diskon_persen, diskon_nominal, total_bayar
 */
function hitungTotalDenganDiskon($koneksi, $no_pelanggan, $subtotal) {
    $diskon_persen = getDiskonPelanggan($koneksi, $no_pelanggan);
    $diskon_nominal = $subtotal * ($diskon_persen / 100);
    $total_bayar = $subtotal - $diskon_nominal;
    
    return [
        'subtotal' => $subtotal,
        'diskon_persen' => $diskon_persen,
        'diskon_nominal' => $diskon_nominal,
        'total_bayar' => $total_bayar
    ];
}

/**
 * Display badge status member dengan warna
 * 
 * @param string $status_member Status member (Bronze/Silver/Gold/Platinum)
 * @return string HTML badge
 */
function displayBadgeStatusMember($status_member) {
    $badges = [
        'Bronze' => '<span class="badge" style="background-color: #CD7F32; color: white;">🥉 Bronze</span>',
        'Silver' => '<span class="badge" style="background-color: #C0C0C0; color: black;">🥈 Silver</span>',
        'Gold' => '<span class="badge" style="background-color: #FFD700; color: black;">🥇 Gold</span>',
        'Platinum' => '<span class="badge" style="background-color: #E5E4E2; color: black;">💎 Platinum</span>'
    ];
    
    return $badges[$status_member] ?? '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * Display info lengkap kategori member di halaman input servis
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return string HTML output
 */
function displayInfoKategoriMember($koneksi, $no_pelanggan) {
    $info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);
    
    if (!$info || $info['total_transaksi'] == 0) {
        return '<div class="alert alert-info">
                    <strong>Pelanggan Baru</strong><br>
                    Belum ada riwayat transaksi. Status: Bronze (0% diskon)
                </div>';
    }
    
    $status = $info['status_member'];
    $diskon = number_format($info['diskon_persen'], 0);
    $total_transaksi = number_format($info['total_transaksi'], 0);
    $total_nominal = number_format($info['total_nominal'], 0, ',', '.');
    $rata_rata = number_format($info['rata_rata_transaksi'], 0, ',', '.');
    
    // Hitung progress ke level berikutnya
    $progress_html = '';
    if ($info['max_nominal'] !== null) {
        $current = $info['total_nominal'];
        $min = $info['min_nominal'];
        $max = $info['max_nominal'];
        $range = $max - $min;
        $progress = (($current - $min) / $range) * 100;
        $sisa = $max - $current;
        
        $progress_html = '
        <div class="mt-2">
            <small>Progress ke level berikutnya:</small>
            <div class="progress" style="height: 20px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" 
                     style="width: ' . $progress . '%"
                     aria-valuenow="' . $progress . '" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                    ' . number_format($progress, 0) . '%
                </div>
            </div>
            <small class="text-muted">Kurang Rp ' . number_format($sisa, 0, ',', '.') . ' lagi!</small>
        </div>';
    } else {
        $progress_html = '<div class="mt-2"><small class="text-success">✅ Level tertinggi tercapai!</small></div>';
    }
    
    // Benefit list
    $benefit_html = '';
    if (!empty($info['benefit'])) {
        $benefits = explode("\n", $info['benefit']);
        $benefit_html = '<ul class="mb-0" style="font-size: 12px;">';
        foreach ($benefits as $benefit) {
            $benefit = trim($benefit);
            if (!empty($benefit) && strpos($benefit, 'Benefit') === false) {
                $benefit_html .= '<li>' . htmlspecialchars($benefit) . '</li>';
            }
        }
        $benefit_html .= '</ul>';
    }
    
    $html = '
    <div class="card border-primary mb-3">
        <div class="card-header bg-primary text-white">
            <strong>🏆 Status Member: ' . displayBadgeStatusMember($status) . '</strong>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Total Transaksi:</strong> ' . $total_transaksi . 'x</p>
                    <p class="mb-1"><strong>Total Nominal:</strong> Rp ' . $total_nominal . '</p>
                    <p class="mb-1"><strong>Rata-rata:</strong> Rp ' . $rata_rata . '</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Diskon Member:</strong> <span class="badge badge-success">' . $diskon . '%</span></p>
                    ' . $progress_html . '
                </div>
            </div>
            
            <hr>
            
            <div class="mt-2">
                <strong>🎁 Benefit Member ' . $status . ':</strong>
                ' . $benefit_html . '
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Display info singkat (untuk tampilan compact)
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return string HTML output
 */
function displayInfoKategoriMemberCompact($koneksi, $no_pelanggan) {
    $info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);
    
    $status = $info['status_member'];
    $diskon = number_format($info['diskon_persen'], 0);
    
    return '<div class="alert alert-info mb-2 p-2">
                <strong>Member:</strong> ' . displayBadgeStatusMember($status) . ' 
                <strong class="ml-2">Diskon:</strong> <span class="badge badge-success">' . $diskon . '%</span>
            </div>';
}

/**
 * Get semua kategori member (untuk dropdown/select)
 * 
 * @param mysqli $koneksi Database connection
 * @return array Array of kategori member
 */
function getAllKategoriMember($koneksi) {
    $query = "SELECT * FROM tbmaster_kategori_member ORDER BY urutan ASC";
    $result = mysqli_query($koneksi, $query);
    
    $kategori = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $kategori[] = $row;
    }
    
    return $kategori;
}

/**
 * Get statistik summary semua kategori member
 * 
 * @param mysqli $koneksi Database connection
 * @return array Array berisi summary per kategori
 */
function getStatistikKategoriMember($koneksi) {
    $query = "SELECT 
                status_member,
                COUNT(*) AS jumlah_pelanggan,
                SUM(total_nominal) AS total_revenue,
                AVG(total_nominal) AS avg_revenue_per_pelanggan,
                AVG(diskon_persen) AS avg_diskon
            FROM statistik_pelanggan
            GROUP BY status_member
            ORDER BY FIELD(status_member, 'Bronze', 'Silver', 'Gold', 'Platinum')";
    
    $result = mysqli_query($koneksi, $query);
    
    $stats = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stats[] = $row;
    }
    
    return $stats;
}

/**
 * Check apakah pelanggan eligible untuk upgrade status
 * 
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return array Info upgrade eligibility
 */
function checkUpgradeEligibility($koneksi, $no_pelanggan) {
    $info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);
    
    if ($info['max_nominal'] === null) {
        return [
            'eligible' => false,
            'message' => 'Anda sudah di level tertinggi (Platinum)!',
            'next_level' => null,
            'sisa_nominal' => 0
        ];
    }
    
    $sisa = $info['max_nominal'] - $info['total_nominal'] + 0.01; // +0.01 untuk masuk ke level berikutnya
    
    $next_level_map = [
        'Bronze' => 'Silver',
        'Silver' => 'Gold',
        'Gold' => 'Platinum'
    ];
    
    return [
        'eligible' => true,
        'message' => 'Transaksi lagi Rp ' . number_format($sisa, 0, ',', '.') . ' untuk naik ke ' . $next_level_map[$info['status_member']],
        'next_level' => $next_level_map[$info['status_member']],
        'sisa_nominal' => $sisa
    ];
}

/**
 * Format rupiah
 * 
 * @param float $angka Angka yang akan diformat
 * @return string Format rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Get icon untuk status member
 * 
 * @param string $status_member Status member
 * @return string Emoji icon
 */
function getIconStatusMember($status_member) {
    $icons = [
        'Bronze' => '🥉',
        'Silver' => '🥈',
        'Gold' => '🥇',
        'Platinum' => '💎'
    ];
    
    return $icons[$status_member] ?? '❓';
}

/**
 * Get warna untuk status member
 *
 * @param string $status_member Status member
 * @return string Hex color code
 */
function getColorStatusMember($status_member) {
    $colors = [
        'Bronze' => '#CD7F32',
        'Silver' => '#C0C0C0',
        'Gold' => '#FFD700',
        'Platinum' => '#E5E4E2'
    ];

    return $colors[$status_member] ?? '#6c757d';
}

/**
 * Get discount data from session (set by discount preview modal)
 *
 * @return array|null Session discount data or null
 */
function getSessionDiscount() {
    if(isset($_SESSION['service_discount']) &&
       is_array($_SESSION['service_discount']) &&
       $_SESSION['service_discount']['apply_discount'] == 1) {

        // Check if session is not expired (15 minutes max)
        $timestamp = $_SESSION['service_discount']['timestamp'] ?? 0;
        if(time() - $timestamp < 900) { // 15 minutes
            return $_SESSION['service_discount'];
        }
    }
    return null;
}

/**
 * Get active discount for service input
 * Returns the applicable discount based on priority: periode > member
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Nomor pelanggan
 * @return array Discount info with type, jasa discount, barang discount
 */
function getActiveDiscountForService($koneksi, $no_pelanggan) {
    $result = [
        'discount_type' => 'none',
        'discount_source' => '',
        'diskon_jasa' => 0,
        'diskon_barang' => 0,
        'promo_id' => null,
        'promo_name' => '',
        'member_kategori' => '',
        'apply_discount' => false
    ];

    // 1. Check session discount first (from preview modal)
    $session_discount = getSessionDiscount();
    if($session_discount && $session_discount['nopol'] == $no_pelanggan) {
        $discount_data = $session_discount['discount_data'];

        if($session_discount['discount_type'] == 'periode' && !empty($discount_data)) {
            // Periode/Promo discount
            $result['discount_type'] = 'periode';
            $result['discount_source'] = 'Session - Promo';
            $result['promo_id'] = $discount_data['promo_id'] ?? null;

            // For periode, the discount may vary per item - store promo info
            if($discount_data['tipe_promo'] == 'persen') {
                $result['diskon_jasa'] = floatval($discount_data['nilai'] ?? 0);
                $result['diskon_barang'] = floatval($discount_data['nilai'] ?? 0);
            }
            $result['apply_discount'] = true;

        } else if($session_discount['discount_type'] == 'member' && !empty($discount_data)) {
            // Member discount
            $result['discount_type'] = 'member';
            $result['discount_source'] = 'Session - Member ' . ($discount_data['kategori'] ?? '');
            $result['diskon_jasa'] = floatval($discount_data['diskon_jasa'] ?? 0);
            $result['diskon_barang'] = floatval($discount_data['diskon_barang'] ?? 0);
            $result['member_kategori'] = $discount_data['kategori'] ?? '';
            $result['apply_discount'] = true;
        }

        return $result;
    }

    // 2. If no session discount, check active promos
    $today = date('Y-m-d');
    $sql_promo = "SELECT id_promo, nama_promo, tipe_promo, nilai_promo
                  FROM master_diskon_periode
                  WHERE status_aktif = 1
                    AND tanggal_mulai <= '$today'
                    AND tanggal_selesai >= '$today'
                  ORDER BY created_at DESC LIMIT 1";
    $res_promo = mysqli_query($koneksi, $sql_promo);

    if($res_promo && mysqli_num_rows($res_promo) > 0) {
        $promo = mysqli_fetch_assoc($res_promo);
        $result['discount_type'] = 'periode';
        $result['discount_source'] = 'Active Promo: ' . $promo['nama_promo'];
        $result['promo_id'] = $promo['id_promo'];
        $result['promo_name'] = $promo['nama_promo'];

        if($promo['tipe_promo'] == 'persen') {
            $result['diskon_jasa'] = floatval($promo['nilai_promo']);
            $result['diskon_barang'] = floatval($promo['nilai_promo']);
        }
        $result['apply_discount'] = true;

        return $result;
    }

    // 3. Fallback to member discount
    $no_pelanggan_escaped = mysqli_real_escape_string($koneksi, $no_pelanggan);

    // Get active member system type
    $sql_setting = "SELECT tipe_kategori FROM setting_kategori_member WHERE is_enabled = 1 LIMIT 1";
    $res_setting = mysqli_query($koneksi, $sql_setting);
    $member_type = 'nominal';
    if($res_setting && $row = mysqli_fetch_assoc($res_setting)) {
        $member_type = $row['tipe_kategori'];
    }

    // Get customer's member status
    $status_field = ($member_type == 'kunjungan') ? 'kategori_member_kunjungan' : 'status_member';
    $sql_stat = "SELECT $status_field as status_member FROM statistik_pelanggan WHERE no_pelanggan = '$no_pelanggan_escaped' LIMIT 1";
    $res_stat = mysqli_query($koneksi, $sql_stat);
    $member_status = 'Bronze';
    if($res_stat && $row = mysqli_fetch_assoc($res_stat)) {
        $member_status = $row['status_member'] ?: 'Bronze';
    }

    // Get discount for this member status
    $sql_member = "SELECT nama_kategori, diskon_jasa, diskon_barang
                   FROM master_kategori_member
                   WHERE nama_kategori = '$member_status'
                     AND tipe_kategori = '$member_type'
                     AND is_active = 1
                   LIMIT 1";
    $res_member = mysqli_query($koneksi, $sql_member);

    if($res_member && $row = mysqli_fetch_assoc($res_member)) {
        $result['discount_type'] = 'member';
        $result['discount_source'] = 'Member ' . $row['nama_kategori'];
        $result['diskon_jasa'] = floatval($row['diskon_jasa']);
        $result['diskon_barang'] = floatval($row['diskon_barang']);
        $result['member_kategori'] = $row['nama_kategori'];
        $result['apply_discount'] = ($result['diskon_jasa'] > 0 || $result['diskon_barang'] > 0);
    }

    return $result;
}

/**
 * Check if an item is excluded from member discount
 *
 * @param mysqli $koneksi Database connection
 * @param string $noitem Item code
 * @return bool True if excluded, false if can receive discount
 */
function isItemExcludedFromDiscount($koneksi, $noitem) {
    $noitem = mysqli_real_escape_string($koneksi, $noitem);

    // Get item's jenis and exclusion status
    $sql = "SELECT
                i.exclude_diskon_member as item_exclude,
                h.exclude_diskon_member as jenis_exclude,
                i.jenis
            FROM tblitem i
            LEFT JOIN tbhargajual h ON i.jenis = h.jenis
            WHERE i.noitem = '$noitem'
            LIMIT 1";

    $result = mysqli_query($koneksi, $sql);
    if($result && $row = mysqli_fetch_assoc($result)) {
        // Item-level override takes precedence
        if($row['item_exclude'] !== null) {
            return $row['item_exclude'] == 1;
        }
        // Fall back to jenis/category level
        return $row['jenis_exclude'] == 1;
    }

    return false; // Default: not excluded
}

/**
 * Hitung jumlah kunjungan (no_service unik, status_servis='bayar') customer
 * dalam N hari terakhir dari hari ini. Dipakai untuk syarat_kelayakan
 * jenis 'jumlah_kunjungan' (FSD_PROMO.md FR-09).
 */
function countKunjunganRolling($koneksi, $no_pelanggan, $rolling_hari) {
    $rolling_hari = (int)$rolling_hari;
    $stmt = mysqli_prepare($koneksi, "SELECT COUNT(DISTINCT no_service) c
                                       FROM tblservice
                                       WHERE no_pelanggan=?
                                         AND status_servis='bayar'
                                         AND tanggal >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                                         AND tanggal <= CURDATE()");
    mysqli_stmt_bind_param($stmt, "si", $no_pelanggan, $rolling_hari);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return (int)($row['c'] ?? 0);
}

/**
 * Evaluasi apakah 1 promo eligible untuk customer tertentu berdasarkan
 * baris master_diskon_periode_syarat (kategori_member/minimum_total_servis/
 * jumlah_kunjungan/paket_workorder), dikombinasikan sesuai mode_syarat (AND/OR).
 * 0 baris syarat = eligible untuk siapa saja (FSD_PROMO.md §5.3).
 */
function evaluateSyaratPromo($koneksi, $id_promo, $mode_syarat, $no_pelanggan) {
    $stmt = mysqli_prepare($koneksi, "SELECT jenis_syarat, operator, nilai, rolling_hari
                                       FROM master_diskon_periode_syarat WHERE id_promo=?");
    mysqli_stmt_bind_param($stmt, "i", $id_promo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $syarat_list = [];
    while($row = mysqli_fetch_assoc($res)) { $syarat_list[] = $row; }
    if(empty($syarat_list)) return true;

    $hasil = [];
    foreach($syarat_list as $s) {
        $nilai_list = array_map('trim', explode(',', $s['nilai']));
        $lolos = false;

        if($s['jenis_syarat'] == 'kategori_member') {
            $stmt2 = mysqli_prepare($koneksi, "SELECT status_member FROM statistik_pelanggan WHERE no_pelanggan=? LIMIT 1");
            mysqli_stmt_bind_param($stmt2, "s", $no_pelanggan);
            mysqli_stmt_execute($stmt2);
            $row2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
            $kategori = $row2['status_member'] ?? 'Bronze';
            $lolos = in_array($kategori, $nilai_list);

        } else if($s['jenis_syarat'] == 'minimum_total_servis') {
            $stmt2 = mysqli_prepare($koneksi, "SELECT total_nominal FROM statistik_pelanggan WHERE no_pelanggan=? LIMIT 1");
            mysqli_stmt_bind_param($stmt2, "s", $no_pelanggan);
            mysqli_stmt_execute($stmt2);
            $row2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
            $total = floatval($row2['total_nominal'] ?? 0);
            $batas = floatval($s['nilai']);
            $lolos = ($s['operator'] == '<=') ? ($total <= $batas) : ($total >= $batas);

        } else if($s['jenis_syarat'] == 'jumlah_kunjungan') {
            $jumlah = countKunjunganRolling($koneksi, $no_pelanggan, $s['rolling_hari']);
            $batas = (int)$s['nilai'];
            $lolos = ($s['operator'] == '<=') ? ($jumlah <= $batas) : ($jumlah >= $batas);

        } else if($s['jenis_syarat'] == 'paket_workorder') {
            $placeholders = implode(',', array_fill(0, count($nilai_list), '?'));
            $stmt2 = mysqli_prepare($koneksi, "SELECT COUNT(*) c FROM tbservis_workorder w
                                                INNER JOIN tblservice sv ON sv.no_service=w.no_service
                                                WHERE sv.no_pelanggan=? AND w.kode_wo IN ($placeholders)");
            $types = "s" . str_repeat("s", count($nilai_list));
            $bind_names = [$stmt2, $types, $no_pelanggan];
            foreach($nilai_list as $k => $v) { $bind_names[] = $nilai_list[$k]; }
            $refs = [];
            foreach($bind_names as $k => $v) { $refs[$k] = &$bind_names[$k]; }
            call_user_func_array('mysqli_stmt_bind_param', $refs);
            mysqli_stmt_execute($stmt2);
            $row2 = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
            $lolos = ((int)($row2['c'] ?? 0)) > 0;
        }

        $hasil[] = $lolos;
    }

    if($mode_syarat == 'OR') {
        return in_array(true, $hasil, true);
    }
    return !in_array(false, $hasil, true);
}

/**
 * Cari semua promo aktif yang menyasar item/jasa ini (via master_diskon_periode_target),
 * lolos scope cabang (master_diskon_periode_cabang, 0 baris = semua cabang), dan lolos
 * syarat kelayakan (evaluateSyaratPromo). Diurutkan created_at ASC untuk stacking (FR-02).
 */
function getEligiblePromosForItem($koneksi, $no_pelanggan, $kd_cabang, $item_type, $noitem) {
    $stmt = mysqli_prepare($koneksi, "SELECT p.id_promo, p.nama_promo, p.tipe_promo, p.nilai_promo,
                                              p.stackable, p.boleh_gabung_diskon_member, p.mode_syarat
                                       FROM master_diskon_periode p
                                       INNER JOIN master_diskon_periode_target t ON t.id_promo=p.id_promo
                                       WHERE p.status_aktif=1
                                         AND p.tanggal_mulai <= CURDATE()
                                         AND p.tanggal_selesai >= CURDATE()
                                         AND t.target_type=? AND t.target_id=?
                                       ORDER BY p.created_at ASC");
    mysqli_stmt_bind_param($stmt, "ss", $item_type, $noitem);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $eligible = [];
    while($promo = mysqli_fetch_assoc($res)) {
        $id_promo = (int)$promo['id_promo'];

        // Cek scope cabang: 0 baris = semua cabang
        $stmt_cb = mysqli_prepare($koneksi, "SELECT kd_cabang FROM master_diskon_periode_cabang WHERE id_promo=?");
        mysqli_stmt_bind_param($stmt_cb, "i", $id_promo);
        mysqli_stmt_execute($stmt_cb);
        $res_cb = mysqli_stmt_get_result($stmt_cb);
        $cabang_list = [];
        while($row_cb = mysqli_fetch_assoc($res_cb)) { $cabang_list[] = $row_cb['kd_cabang']; }
        if(!empty($cabang_list) && !in_array($kd_cabang, $cabang_list)) {
            continue;
        }

        if(!evaluateSyaratPromo($koneksi, $id_promo, $promo['mode_syarat'], $no_pelanggan)) {
            continue;
        }

        $eligible[] = $promo;
    }

    return $eligible;
}

/**
 * Calculate discount for a single item — multi-promo stacking berurutan
 * (FSD_PROMO.md §8 Alur B). Kembalikan juga promo_breakdown untuk keperluan
 * audit trail (promo_usage_log) oleh caller — belum di-wire otomatis ke
 * 3 handler transaksi, lihat FSD_PROMO.md Open Items.
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Customer ID
 * @param string $noitem Item code
 * @param string $item_type 'barang' or 'jasa'
 * @param float $harga Original price
 * @param string|null $kd_cabang Kode cabang transaksi berjalan (default: sesi login)
 * @return array With diskon_persen, diskon_nominal, harga_akhir, promo_breakdown
 */
function calculateItemDiscount($koneksi, $no_pelanggan, $noitem, $item_type, $harga, $kd_cabang = null) {
    $result = [
        'harga_awal' => $harga,
        'diskon_persen' => 0,
        'diskon_nominal' => 0,
        'harga_akhir' => $harga,
        'discount_source' => '',
        'promo_breakdown' => []
    ];

    if($kd_cabang === null) {
        $kd_cabang = $_SESSION['_cabang'] ?? '';
    }

    $eligible = getEligiblePromosForItem($koneksi, $no_pelanggan, $kd_cabang, $item_type, $noitem);

    // Kalau ada promo non-stackable di antara yang eligible, hanya pakai 1
    // (id_promo terbesar/terbaru menang) — fallback anomali, lihat Edge Case #3.
    $non_stackable = array_values(array_filter($eligible, function($p) { return (int)$p['stackable'] === 0; }));
    if(!empty($non_stackable)) {
        usort($non_stackable, function($a, $b) { return $b['id_promo'] <=> $a['id_promo']; });
        $eligible = [$non_stackable[0]];
    }

    $semua_boleh_gabung_member = true;
    $harga_sisa = $harga;
    $total_potongan = 0;
    $urutan = 1;
    $nama_promo_dipakai = [];

    foreach($eligible as $promo) {
        if((int)$promo['boleh_gabung_diskon_member'] === 0) {
            $semua_boleh_gabung_member = false;
        }
        $potongan_ini = ($promo['tipe_promo'] == 'persen')
            ? $harga_sisa * (floatval($promo['nilai_promo']) / 100)
            : min(floatval($promo['nilai_promo']), $harga_sisa);

        $harga_sisa -= $potongan_ini;
        $total_potongan += $potongan_ini;
        $nama_promo_dipakai[] = $promo['nama_promo'];
        $result['promo_breakdown'][] = [
            'id_promo' => (int)$promo['id_promo'],
            'nilai_potongan' => $potongan_ini,
            'urutan_stacking' => $urutan
        ];
        $urutan++;
    }

    $promo_diterapkan = !empty($eligible);

    // Diskon member — dihitung untuk dibandingkan (kalau ada promo yang tidak
    // boleh gabung) atau digabung berurutan setelah promo (kalau semua boleh).
    $diskon_member_persen = 0;
    $member_kategori = '';
    if(!isItemExcludedFromDiscount($koneksi, $noitem)) {
        $discount_member = getActiveDiscountForService($koneksi, $no_pelanggan);
        if($discount_member['discount_type'] == 'member' && $discount_member['apply_discount']) {
            $diskon_member_persen = ($item_type == 'jasa') ? $discount_member['diskon_jasa'] : $discount_member['diskon_barang'];
            $member_kategori = $discount_member['member_kategori'];
        }
    }

    if($promo_diterapkan && $diskon_member_persen > 0) {
        if($semua_boleh_gabung_member) {
            $potongan_member = $harga_sisa * ($diskon_member_persen / 100);
            $harga_sisa -= $potongan_member;
            $total_potongan += $potongan_member;
            $result['discount_source'] = 'Promo: ' . implode(', ', $nama_promo_dipakai) . ' + Member ' . $member_kategori;
        } else {
            $potongan_member_saja = $harga * ($diskon_member_persen / 100);
            if($potongan_member_saja > $total_potongan) {
                $total_potongan = $potongan_member_saja;
                $result['discount_source'] = 'Member ' . $member_kategori;
                $result['promo_breakdown'] = [];
            } else {
                $result['discount_source'] = 'Promo: ' . implode(', ', $nama_promo_dipakai);
            }
        }
    } else if($promo_diterapkan) {
        $result['discount_source'] = 'Promo: ' . implode(', ', $nama_promo_dipakai);
    } else if($diskon_member_persen > 0) {
        $total_potongan = $harga * ($diskon_member_persen / 100);
        $result['discount_source'] = 'Member ' . $member_kategori;
    }

    $result['diskon_nominal'] = $total_potongan;
    $result['diskon_persen'] = ($harga > 0) ? ($total_potongan / $harga * 100) : 0;
    $result['harga_akhir'] = $harga - $total_potongan;

    return $result;
}

/**
 * Clear session discount after service is saved
 */
function clearSessionDiscount() {
    if(isset($_SESSION['service_discount'])) {
        unset($_SESSION['service_discount']);
    }
}

/**
 * Display active discount info banner for service input page
 *
 * @param mysqli $koneksi Database connection
 * @param string $no_pelanggan Customer ID
 * @return string HTML output
 */
function displayActiveDiscountBanner($koneksi, $no_pelanggan) {
    $discount = getActiveDiscountForService($koneksi, $no_pelanggan);

    if(!$discount['apply_discount']) {
        return '';
    }

    $html = '';

    if($discount['discount_type'] == 'periode') {
        $html = '
        <div class="alert alert-success" style="border-left: 5px solid #2ecc71; margin-bottom: 15px;">
            <i class="ace-icon fa fa-star bigger-120"></i>
            <strong>PROMO AKTIF!</strong> - ' . htmlspecialchars($discount['promo_name'] ?: $discount['discount_source']) . '
            <span class="pull-right">
                <span class="badge badge-warning">Diskon akan diterapkan otomatis</span>
            </span>
        </div>';
    } else if($discount['discount_type'] == 'member') {
        $html = '
        <div class="alert alert-info" style="border-left: 5px solid ' . getColorStatusMember($discount['member_kategori']) . '; margin-bottom: 15px;">
            <i class="ace-icon fa fa-user bigger-120"></i>
            <strong>DISKON MEMBER ' . strtoupper($discount['member_kategori']) . '</strong>
            <span class="pull-right">
                Jasa: <span class="badge badge-success">' . $discount['diskon_jasa'] . '%</span>
                Barang: <span class="badge badge-primary">' . $discount['diskon_barang'] . '%</span>
            </span>
        </div>';
    }

    return $html;
}

?>
