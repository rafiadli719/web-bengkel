<?php
/**
 * MinMaxCalculator Class
 * Kelas untuk menghitung MIN/MAX stok berdasarkan 84 hari (12 minggu)
 *
 * Aturan Kalkulasi:
 * - Data 84 hari (12 minggu): W1-W12
 * - MIN Stok = MAX penjualan 1 minggu (tertinggi dari W1-W12)
 * - MAX Stok = MAX penjualan 2 minggu berturut-turut
 * - Kategori berdasarkan frekuensi transaksi:
 *   - A: Interval 1-3 hari (22-84 transaksi/84 hari)
 *   - B: Interval 4-12 hari (7-21 transaksi/84 hari)
 *   - C: Interval >12 hari (4-6 transaksi/84 hari)
 *   - D: Interval >30 hari atau tidak ada transaksi (0-3 transaksi/84 hari)
 *   - E: Non-Stock (tidak ada transaksi sama sekali)
 * - Lead time: 3 hari
 */

class MinMaxCalculator
{
    private $koneksi;
    private $periodeHari = 84;
    private $leadTimeHari = 3;

    public function __construct($koneksi)
    {
        $this->koneksi = $koneksi;
    }

    /**
     * Populate penjualan harian dari tbstok (84 hari terakhir)
     */
    public function populatePenjualanHarian()
    {
        $startDate = date('Y-m-d', strtotime("-{$this->periodeHari} days"));

        // Hapus data lama
        $sql = "DELETE FROM tblpenjualan_harian WHERE tanggal < ?";
        $stmt = mysqli_prepare($this->koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 's', $startDate);
        mysqli_stmt_execute($stmt);

        // Insert dari tbstok (tipe 3=Penjualan, 4=Penjualan Service)
        $sql = "INSERT INTO tblpenjualan_harian (no_item, kd_cabang, tanggal, qty_jual)
                SELECT
                    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
                    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci,
                    tanggal,
                    SUM(keluar) AS qty_jual
                FROM tbstok
                WHERE tipe IN ('3', '4')
                  AND tanggal >= ?
                  AND keluar > 0
                GROUP BY
                    CONVERT(no_item USING utf8mb4) COLLATE utf8mb4_general_ci,
                    CONVERT(kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci,
                    tanggal
                ON DUPLICATE KEY UPDATE
                    qty_jual = VALUES(qty_jual)";

        $stmt = mysqli_prepare($this->koneksi, $sql);
        mysqli_stmt_bind_param($stmt, 's', $startDate);
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Populate penjualan mingguan dari harian
     */
    public function populatePenjualanMingguan()
    {
        $sql = "INSERT INTO tblpenjualan_mingguan (no_item, kd_cabang, tahun, minggu, qty_jual, jml_transaksi)
                SELECT
                    no_item,
                    kd_cabang,
                    YEAR(tanggal) AS tahun,
                    WEEK(tanggal, 1) AS minggu,
                    SUM(qty_jual) AS qty_jual,
                    COUNT(*) AS jml_transaksi
                FROM tblpenjualan_harian
                WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 84 DAY)
                GROUP BY no_item, kd_cabang, YEAR(tanggal), WEEK(tanggal, 1)
                ON DUPLICATE KEY UPDATE
                    qty_jual = VALUES(qty_jual),
                    jml_transaksi = VALUES(jml_transaksi),
                    updated_at = NOW()";

        return mysqli_query($this->koneksi, $sql);
    }

    /**
     * Hitung MIN/MAX dan Klasifikasi untuk satu cabang atau semua cabang
     */
    public function hitungMinMax($kdCabang = null)
    {
        // Populate data penjualan
        $this->populatePenjualanHarian();
        $this->populatePenjualanMingguan();

        $startDate = date('Y-m-d', strtotime("-{$this->periodeHari} days"));
        $yearWeekStart = date('Y', strtotime($startDate)) * 100 + date('W', strtotime($startDate));

        $cabangCondition = $kdCabang ? "AND pm.kd_cabang = '" . mysqli_real_escape_string($this->koneksi, $kdCabang) . "'" : "";

        // Query untuk mendapatkan data mingguan per item per cabang
        $sql = "INSERT INTO tblitem_minmax (
                    no_item, kd_cabang,
                    w1, w2, w3, w4, w5, w6, w7, w8, w9, w10, w11, w12,
                    max_1w, max_2w, min_stok, max_stok,
                    total_transaksi_84hari, avg_interval_hari, kategori,
                    stok_saat_ini, lead_time_hari
                )
                SELECT
                    pm.no_item,
                    pm.kd_cabang,
                    MAX(CASE WHEN rn = 1 THEN qty_jual ELSE 0 END) AS w1,
                    MAX(CASE WHEN rn = 2 THEN qty_jual ELSE 0 END) AS w2,
                    MAX(CASE WHEN rn = 3 THEN qty_jual ELSE 0 END) AS w3,
                    MAX(CASE WHEN rn = 4 THEN qty_jual ELSE 0 END) AS w4,
                    MAX(CASE WHEN rn = 5 THEN qty_jual ELSE 0 END) AS w5,
                    MAX(CASE WHEN rn = 6 THEN qty_jual ELSE 0 END) AS w6,
                    MAX(CASE WHEN rn = 7 THEN qty_jual ELSE 0 END) AS w7,
                    MAX(CASE WHEN rn = 8 THEN qty_jual ELSE 0 END) AS w8,
                    MAX(CASE WHEN rn = 9 THEN qty_jual ELSE 0 END) AS w9,
                    MAX(CASE WHEN rn = 10 THEN qty_jual ELSE 0 END) AS w10,
                    MAX(CASE WHEN rn = 11 THEN qty_jual ELSE 0 END) AS w11,
                    MAX(CASE WHEN rn = 12 THEN qty_jual ELSE 0 END) AS w12,
                    MAX(qty_jual) AS max_1w,
                    0 AS max_2w,
                    MAX(qty_jual) AS min_stok,
                    0 AS max_stok,
                    SUM(jml_transaksi) AS total_transaksi_84hari,
                    CASE WHEN SUM(jml_transaksi) > 0 THEN 84.0 / SUM(jml_transaksi) ELSE 999 END AS avg_interval_hari,
                    CASE
                        WHEN SUM(jml_transaksi) >= 22 THEN 'A'
                        WHEN SUM(jml_transaksi) >= 7 THEN 'B'
                        WHEN SUM(jml_transaksi) >= 4 THEN 'C'
                        WHEN SUM(jml_transaksi) >= 1 THEN 'D'
                        ELSE 'E'
                    END AS kategori,
                    COALESCE((SELECT SUM(masuk - keluar) FROM tbstok s
                              WHERE CONVERT(s.no_item USING utf8mb4) COLLATE utf8mb4_general_ci = pm.no_item
                              AND CONVERT(s.kd_cabang USING utf8mb4) COLLATE utf8mb4_general_ci = pm.kd_cabang), 0) AS stok_saat_ini,
                    {$this->leadTimeHari} AS lead_time_hari
                FROM (
                    SELECT
                        no_item, kd_cabang, tahun, minggu, qty_jual, jml_transaksi,
                        ROW_NUMBER() OVER (PARTITION BY no_item, kd_cabang ORDER BY tahun DESC, minggu DESC) AS rn
                    FROM tblpenjualan_mingguan
                    WHERE (tahun * 100 + minggu) >= {$yearWeekStart}
                ) pm
                WHERE pm.rn <= 12 {$cabangCondition}
                GROUP BY pm.no_item, pm.kd_cabang
                ON DUPLICATE KEY UPDATE
                    w1 = VALUES(w1), w2 = VALUES(w2), w3 = VALUES(w3), w4 = VALUES(w4),
                    w5 = VALUES(w5), w6 = VALUES(w6), w7 = VALUES(w7), w8 = VALUES(w8),
                    w9 = VALUES(w9), w10 = VALUES(w10), w11 = VALUES(w11), w12 = VALUES(w12),
                    max_1w = VALUES(max_1w),
                    min_stok = VALUES(min_stok),
                    total_transaksi_84hari = VALUES(total_transaksi_84hari),
                    avg_interval_hari = VALUES(avg_interval_hari),
                    kategori = VALUES(kategori),
                    stok_saat_ini = VALUES(stok_saat_ini),
                    updated_at = NOW()";

        $result = mysqli_query($this->koneksi, $sql);

        if (!$result) {
            return ['success' => false, 'error' => mysqli_error($this->koneksi)];
        }

        // Update MAX 2W (penjualan tertinggi 2 minggu berturut-turut)
        $cabangConditionUpdate = $kdCabang ? "WHERE kd_cabang = '" . mysqli_real_escape_string($this->koneksi, $kdCabang) . "'" : "";

        $sql = "UPDATE tblitem_minmax m
                SET max_2w = GREATEST(
                    w1 + w2, w2 + w3, w3 + w4, w4 + w5, w5 + w6,
                    w6 + w7, w7 + w8, w8 + w9, w9 + w10, w10 + w11, w11 + w12
                ),
                max_stok = GREATEST(
                    w1 + w2, w2 + w3, w3 + w4, w4 + w5, w5 + w6,
                    w6 + w7, w7 + w8, w8 + w9, w9 + w10, w10 + w11, w11 + w12
                )
                {$cabangConditionUpdate}";

        mysqli_query($this->koneksi, $sql);

        return ['success' => true];
    }

    /**
     * Get daftar item yang perlu order (stok < MIN)
     */
    public function getItemPerluOrder($kdCabang = null, $kategori = null, $limit = 100)
    {
        $conditions = ["m.stok_saat_ini < m.min_stok", "m.kategori NOT IN ('E')"];

        if ($kdCabang) {
            $conditions[] = "m.kd_cabang = '" . mysqli_real_escape_string($this->koneksi, $kdCabang) . "'";
        }
        if ($kategori) {
            $conditions[] = "m.kategori = '" . mysqli_real_escape_string($this->koneksi, $kategori) . "'";
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT
                    m.no_item,
                    COALESCE(i.namaitem, m.no_item) AS nama_item,
                    m.kd_cabang,
                    c.nama_cabang,
                    m.stok_saat_ini,
                    m.min_stok,
                    m.max_stok,
                    m.kategori,
                    m.total_transaksi_84hari,
                    ROUND(m.avg_interval_hari, 1) AS avg_interval_hari,
                    m.lead_time_hari,
                    GREATEST(0, m.min_stok - m.stok_saat_ini) AS qty_kurang,
                    GREATEST(0, m.max_stok - m.stok_saat_ini) AS qty_order_suggest,
                    CASE
                        WHEN m.stok_saat_ini < ROUND(m.min_stok / 2, 0) THEN 'URGENT'
                        WHEN m.stok_saat_ini < m.min_stok THEN 'WARNING'
                        ELSE 'OK'
                    END AS status_stok,
                    m.supplier1,
                    m.supplier2,
                    i.hargapokok
                FROM tblitem_minmax m
                LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
                LEFT JOIN tbcabang c ON m.kd_cabang = c.kode_cabang
                WHERE {$whereClause}
                ORDER BY
                    CASE WHEN m.stok_saat_ini < ROUND(m.min_stok / 2, 0) THEN 1 ELSE 2 END,
                    m.kategori ASC,
                    m.kd_cabang,
                    m.no_item
                LIMIT {$limit}";

        $result = mysqli_query($this->koneksi, $sql);
        $items = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * Get summary per kategori per cabang
     */
    public function getSummaryPerKategori($kdCabang = null)
    {
        $cabangCondition = $kdCabang ? "WHERE m.kd_cabang = '" . mysqli_real_escape_string($this->koneksi, $kdCabang) . "'" : "";

        $sql = "SELECT
                    m.kd_cabang,
                    c.nama_cabang,
                    m.kategori,
                    COUNT(*) AS jml_item,
                    SUM(CASE WHEN m.stok_saat_ini < m.min_stok THEN 1 ELSE 0 END) AS jml_perlu_order,
                    SUM(CASE WHEN m.stok_saat_ini < ROUND(m.min_stok / 2, 0) THEN 1 ELSE 0 END) AS jml_urgent
                FROM tblitem_minmax m
                LEFT JOIN tbcabang c ON m.kd_cabang = c.kode_cabang
                {$cabangCondition}
                GROUP BY m.kd_cabang, c.nama_cabang, m.kategori
                ORDER BY m.kd_cabang, m.kategori";

        $result = mysqli_query($this->koneksi, $sql);
        $summary = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $summary[] = $row;
            }
        }

        return $summary;
    }

    /**
     * Get detail MIN/MAX untuk satu item
     */
    public function getItemDetail($noItem, $kdCabang = null)
    {
        $cabangCondition = $kdCabang ? "AND m.kd_cabang = '" . mysqli_real_escape_string($this->koneksi, $kdCabang) . "'" : "";
        $escapedItem = mysqli_real_escape_string($this->koneksi, $noItem);

        $sql = "SELECT
                    m.*,
                    i.namaitem AS nama_item,
                    i.jenis,
                    i.hargapokok,
                    c.nama_cabang
                FROM tblitem_minmax m
                LEFT JOIN tblitem i ON m.no_item = (i.noitem COLLATE utf8mb4_general_ci)
                LEFT JOIN tbcabang c ON m.kd_cabang = c.kode_cabang
                WHERE m.no_item = '{$escapedItem}' {$cabangCondition}
                ORDER BY m.kd_cabang";

        $result = mysqli_query($this->koneksi, $sql);
        $details = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $details[] = $row;
            }
        }

        return $details;
    }

    /**
     * Get daftar cabang dengan statistik stok
     */
    public function getCabangStats()
    {
        $sql = "SELECT
                    c.kode_cabang,
                    c.nama_cabang,
                    COUNT(DISTINCT m.no_item) AS total_item,
                    SUM(CASE WHEN m.stok_saat_ini < m.min_stok AND m.kategori != 'E' THEN 1 ELSE 0 END) AS jml_perlu_order,
                    SUM(CASE WHEN m.stok_saat_ini < ROUND(m.min_stok / 2, 0) AND m.kategori != 'E' THEN 1 ELSE 0 END) AS jml_urgent,
                    SUM(CASE WHEN m.kategori = 'A' THEN 1 ELSE 0 END) AS kat_A,
                    SUM(CASE WHEN m.kategori = 'B' THEN 1 ELSE 0 END) AS kat_B,
                    SUM(CASE WHEN m.kategori = 'C' THEN 1 ELSE 0 END) AS kat_C,
                    SUM(CASE WHEN m.kategori = 'D' THEN 1 ELSE 0 END) AS kat_D,
                    SUM(CASE WHEN m.kategori = 'E' THEN 1 ELSE 0 END) AS kat_E
                FROM tbcabang c
                LEFT JOIN tblitem_minmax m ON c.kode_cabang = m.kd_cabang
                GROUP BY c.kode_cabang, c.nama_cabang
                ORDER BY c.nama_cabang";

        $result = mysqli_query($this->koneksi, $sql);
        $stats = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $stats[] = $row;
            }
        }

        return $stats;
    }

    /**
     * Generate saran transfer antar cabang (surplus ke deficit)
     */
    public function generateSaranTransfer($noItem = null)
    {
        $itemCondition = $noItem ? "AND m.no_item = '" . mysqli_real_escape_string($this->koneksi, $noItem) . "'" : "";

        // Cari cabang dengan surplus (stok > MAX)
        $sqlSurplus = "SELECT m.no_item, m.kd_cabang, m.stok_saat_ini, m.max_stok,
                              (m.stok_saat_ini - m.max_stok) AS surplus
                       FROM tblitem_minmax m
                       WHERE m.stok_saat_ini > m.max_stok {$itemCondition}
                       ORDER BY m.no_item, surplus DESC";

        // Cari cabang dengan deficit (stok < MIN)
        $sqlDeficit = "SELECT m.no_item, m.kd_cabang, m.stok_saat_ini, m.min_stok, m.max_stok,
                              (m.min_stok - m.stok_saat_ini) AS deficit
                       FROM tblitem_minmax m
                       WHERE m.stok_saat_ini < m.min_stok AND m.kategori != 'E' {$itemCondition}
                       ORDER BY m.no_item, deficit DESC";

        $resSurplus = mysqli_query($this->koneksi, $sqlSurplus);
        $resDeficit = mysqli_query($this->koneksi, $sqlDeficit);

        $surplus = [];
        $deficit = [];

        if ($resSurplus) {
            while ($row = mysqli_fetch_assoc($resSurplus)) {
                $surplus[$row['no_item']][] = $row;
            }
        }

        if ($resDeficit) {
            while ($row = mysqli_fetch_assoc($resDeficit)) {
                $deficit[$row['no_item']][] = $row;
            }
        }

        // Generate saran transfer
        $saranTransfer = [];

        foreach ($deficit as $item => $defCabangList) {
            if (!isset($surplus[$item])) continue;

            foreach ($defCabangList as $def) {
                foreach ($surplus[$item] as &$sur) {
                    if ($sur['surplus'] <= 0) continue;
                    if ($def['deficit'] <= 0) continue;

                    $qtyTransfer = min($sur['surplus'], $def['deficit']);

                    $saranTransfer[] = [
                        'no_item' => $item,
                        'dari_cabang' => $sur['kd_cabang'],
                        'ke_cabang' => $def['kd_cabang'],
                        'qty_suggest' => $qtyTransfer
                    ];

                    $sur['surplus'] -= $qtyTransfer;
                    $def['deficit'] -= $qtyTransfer;
                }
            }
        }

        return $saranTransfer;
    }

    /**
     * Get klasifikasi label dan warna
     */
    public static function getKategoriInfo($kategori)
    {
        $info = [
            'A' => ['label' => 'Fast Moving', 'badge' => 'success', 'desc' => 'Interval 1-3 hari'],
            'B' => ['label' => 'Medium Moving', 'badge' => 'info', 'desc' => 'Interval 4-12 hari'],
            'C' => ['label' => 'Slow Moving', 'badge' => 'warning', 'desc' => 'Interval >12 hari'],
            'D' => ['label' => 'Dead Stock', 'badge' => 'danger', 'desc' => '>30 hari tidak ada transaksi'],
            'E' => ['label' => 'Non-Stock', 'badge' => 'default', 'desc' => 'Tidak ada transaksi 84 hari']
        ];

        return isset($info[$kategori]) ? $info[$kategori] : ['label' => 'Unknown', 'badge' => 'default', 'desc' => ''];
    }
}
