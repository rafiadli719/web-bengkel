<?php
/**
 * Export Data Temuan & Penawaran Part
 * Format: Excel (.xls) atau PDF
 *
 * Parameter URL:
 * - format: excel / pdf (default: excel)
 * - no_service: nomor service order (optional, untuk filter specific service)
 * - type: temuan / penawaran / all (default: all)
 * - status: filter status penawaran (pending/disetujui/ditolak) (optional)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/export_error.log');

// Start output buffering to prevent any output before headers
ob_start();

session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    include "../config/koneksi.php";

    // Parameters
    $format = $_GET['format'] ?? 'excel';
    $no_service = $_GET['no_service'] ?? '';
    $type = $_GET['type'] ?? 'all'; // temuan / penawaran / all
    $status_filter = $_GET['status'] ?? ''; // untuk filter status penawaran

    // Set filename
    $filename_prefix = 'temuan_penawaran_export_';
    if($no_service) {
        $filename_prefix = 'temuan_penawaran_' . $no_service . '_';
    }
    $filename = $filename_prefix . date('Y-m-d_His');

    // ========================================
    // ESTIMASI FORMAT - Formulir Estimasi Biaya Servis
    // ========================================
    if($format == 'estimasi') {
        if(empty($no_service)) {
            ob_end_clean();
            die("Error: Parameter no_service wajib diisi untuk format estimasi");
        }

        try {
            // Check if dompdf exists
            $dompdf_path = __DIR__ . "/dompdf/autoload.inc.php";
            if(!file_exists($dompdf_path)) {
                throw new Exception("Dompdf library not found at: " . $dompdf_path);
            }

            require_once($dompdf_path);
            $dompdf = new \Dompdf\Dompdf();

            // ========================================
            // QUERY DATA SERVICE & PELANGGAN
            // Kolom sesuai schema: telephone (bukan nohp), tipe dari tblkendaraan
            // ========================================
            $sql_service = "SELECT 
                            s.no_service,
                            s.tanggal,
                            s.no_pelanggan,
                            s.no_polisi,
                            COALESCE(s.km_skr, 0) as km_motor,
                            COALESCE(s.keterangan, '') as catatan,
                            COALESCE(p.namapelanggan, '') as namapelanggan,
                            COALESCE(p.alamat, '') as alamat,
                            COALESCE(p.telephone, p.notlp, '') as nohp,
                            COALESCE(p.kota, '') as kota,
                            COALESCE(k.tipe, '') as tipe_motor
                          FROM tblservice s
                          LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                          LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
                          WHERE s.no_service = '$no_service'
                          LIMIT 1";
            
            $result_service = mysqli_query($koneksi, $sql_service);
            if(!$result_service || mysqli_num_rows($result_service) == 0) {
                throw new Exception("Service tidak ditemukan: $no_service (Query error: " . mysqli_error($koneksi) . ")");
            }
            
            $service = mysqli_fetch_assoc($result_service);

            // ========================================
            // QUERY KELUHAN PELANGGAN
            // ========================================
            $sql_keluhan = "SELECT keluhan, status_pengerjaan 
                           FROM tbservis_keluhan_status 
                           WHERE no_service = '$no_service' 
                           ORDER BY id ASC";
            $result_keluhan = mysqli_query($koneksi, $sql_keluhan);
            $keluhan_list = [];
            if($result_keluhan) {
                while($row = mysqli_fetch_assoc($result_keluhan)) {
                    $keluhan_list[] = $row;
                }
            }

            // ========================================
            // QUERY ITEM JASA (dari SPK)
            // ========================================
            $sql_jasa = "SELECT sj.no_item, COALESCE(ij.namaitem, sj.no_item) as nama_item, 
                                sj.harga, sj.potongan, sj.total
                         FROM tblservis_jasa sj
                         LEFT JOIN tblitem_jasa ij ON sj.no_item = ij.noitem
                         WHERE sj.no_service = '$no_service'
                         ORDER BY sj.nobaris ASC";
            $result_jasa = mysqli_query($koneksi, $sql_jasa);
            $items_jasa = [];
            $subtotal_jasa = 0;
            if($result_jasa) {
                while($row = mysqli_fetch_assoc($result_jasa)) {
                    $items_jasa[] = $row;
                    $subtotal_jasa += $row['total'];
                }
            }

            // ========================================
            // QUERY ITEM BARANG (dari SPK)
            // ========================================
            $sql_barang = "SELECT sb.no_item, COALESCE(i.namaitem, sb.no_item) as nama_item,
                                  sb.quantity, sb.harga_jual, sb.potongan, sb.total
                           FROM tblservis_barang sb
                           LEFT JOIN tblitem i ON sb.no_item = i.noitem
                           WHERE sb.no_service = '$no_service'
                           ORDER BY sb.nobaris ASC";
            $result_barang = mysqli_query($koneksi, $sql_barang);
            $items_barang = [];
            $subtotal_barang = 0;
            if($result_barang) {
                while($row = mysqli_fetch_assoc($result_barang)) {
                    $items_barang[] = $row;
                    $subtotal_barang += $row['total'];
                }
            }

            $subtotal_servis = $subtotal_jasa + $subtotal_barang;

            // ========================================
            // QUERY TEMUAN - PRIORITAS URGENT (urgensi tinggi/kritis)
            // ========================================
            $sql_urgent = "SELECT 
                            t.*,
                            COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display
                          FROM tbservis_temuan t
                          LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                          WHERE t.no_service = '$no_service'
                          AND t.tingkat_urgensi IN ('tinggi', 'kritis', 'urgent')
                          ORDER BY t.created_at ASC";
            
            $result_urgent = mysqli_query($koneksi, $sql_urgent);
            $temuan_urgent = [];
            $total_urgent = 0;
            
            if($result_urgent) {
                while($row = mysqli_fetch_assoc($result_urgent)) {
                    $temuan_urgent[] = $row;
                    $total_urgent += floatval($row['estimasi_biaya'] ?? 0);
                }
            }

            // ========================================
            // QUERY TEMUAN - PRIORITAS TIDAK URGENT (urgensi rendah/sedang)
            // ========================================
            $sql_non_urgent = "SELECT 
                                t.*,
                                COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display
                              FROM tbservis_temuan t
                              LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                              WHERE t.no_service = '$no_service'
                              AND (t.tingkat_urgensi IN ('rendah', 'sedang') OR t.tingkat_urgensi IS NULL)
                              ORDER BY t.created_at ASC";
            
            $result_non_urgent = mysqli_query($koneksi, $sql_non_urgent);
            $temuan_non_urgent = [];
            $total_non_urgent = 0;
            
            if($result_non_urgent) {
                while($row = mysqli_fetch_assoc($result_non_urgent)) {
                    $temuan_non_urgent[] = $row;
                    $total_non_urgent += floatval($row['estimasi_biaya'] ?? 0);
                }
            }

            // ========================================
            // QUERY PENAWARAN PART (pending/disetujui untuk estimasi)
            // ========================================
            $sql_penawaran = "SELECT 
                                p.*,
                                t.tingkat_urgensi
                              FROM tbservis_penawaran_part p
                              LEFT JOIN tbservis_temuan t ON p.temuan_id = t.id
                              WHERE p.no_service = '$no_service'
                              AND p.status_penawaran IN ('pending', 'disetujui')
                              ORDER BY t.tingkat_urgensi DESC, p.created_at ASC";
            
            $result_penawaran = mysqli_query($koneksi, $sql_penawaran);
            $penawaran_urgent = [];
            $penawaran_non_urgent = [];
            
            if($result_penawaran) {
                while($row = mysqli_fetch_assoc($result_penawaran)) {
                    if(in_array($row['tingkat_urgensi'], ['tinggi', 'kritis', 'urgent'])) {
                        $penawaran_urgent[] = $row;
                        $total_urgent += floatval($row['total_harga'] ?? 0);
                    } else {
                        $penawaran_non_urgent[] = $row;
                        $total_non_urgent += floatval($row['total_harga'] ?? 0);
                    }
                }
            }

            // Grand Total
            $grand_total = $subtotal_servis + $total_urgent + $total_non_urgent;

            // ========================================
            // BUILD HTML FOR PDF - Clean Structure
            // ========================================
            
            // Get logo path for embedding
            $logo_path = __DIR__ . '/../file_upload/logo.png';
            $logo_data = '';
            if(file_exists($logo_path)) {
                $logo_data = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));
            }
            
            $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { 
        margin: 10mm 15mm; 
    }
    body { 
        font-family: Helvetica, Arial, sans-serif; 
        font-size: 9pt; 
        line-height: 1.3;
        color: #333;
        margin: 0;
        padding: 0;
    }
    
    /* Tables */
    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    }
    td {
        vertical-align: top;
        padding: 0;
    }
    
    /* Header Table (Nested) */
    .header-table td {
        vertical-align: middle;
    }
    .logo-cell img {
        width: 140px;
        height: auto;
    }
    .title-cell {
        text-align: right;
    }
    .title-cell h1 {
        font-size: 14pt;
        margin: 0 0 5px 0;
        color: #333;
    }
    .title-cell .service-no {
        font-size: 10pt;
        color: #666;
    }
    
    /* Section Titles */
    .section-header { 
        font-weight: bold; 
        font-size: 10pt;
        background-color: #f2f2f2;
        padding: 6px 10px;
        border-left: 4px solid #d32f2f;
        margin-bottom: 5px; 
    }
    
    /* Data Table (Nested) */
    .info-table td {
        padding: 3px 5px;
    }
    .label {
        width: 100px;
        font-weight: bold;
    }
    
    /* Content Tables (Bordered) */
    .bordered-table {
        width: 100%;
        border: 1px solid #ccc;
    }
    .bordered-table th,
    .bordered-table td {
        border: 1px solid #ccc;
        padding: 5px;
        text-align: left;
    }
    .bordered-table th {
        background-color: #e0e0e0;
        font-weight: bold;
    }
    
    /* Utilities */
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .cat-jasa { background-color: #e3f2fd; }
    .cat-barang { background-color: #fff3e0; }
    
    .urgent-text { color: #d32f2f; font-weight:bold; }
    .non-urgent-text { color: #1976d2; font-weight:bold; }
    
    .grand-total-box { 
        text-align: center;
        background-color: #d32f2f;
        color: white;
        padding: 10px;
        font-size: 11pt;
        font-weight: bold;
    }
    
    .note-text { 
        font-size: 8pt; 
        color: #666; 
        font-style: italic;
    }
    
    /* Approval */
    .approval-container {
        border: 1px solid #999;
        padding: 10px;
    }
    .signature-box {
        height: 40px;
        border-bottom: 1px solid #333;
    }
</style>
</head>
<body>

<!-- MASTER TABLE (Atomic Rows) -->
<table style="width: 100%; border: none;">

    <!-- 1. HEADER ROW -->
    <tr>
        <td>
            <table class="header-table">
                <tr>
                    <td class="logo-cell" width="50%">' . ($logo_data ? '<img src="' . $logo_data . '" alt="FIT MOTOR">' : '<strong>FIT MOTOR</strong>') . '</td>
                    <td class="title-cell" width="50%">
                        <h1>INFO ESTIMASI BIAYA SERVIS</h1>
                        <div class="service-no">No: ' . htmlspecialchars($service['no_service']) . ' | Tgl: ' . date('d/m/Y', strtotime($service['tanggal'])) . '</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- SPACER -->
    <tr><td height="15"></td></tr>

    <!-- 2. DATA PELANGGAN TITLE -->
    <tr>
        <td class="section-header">DATA PELANGGAN</td>
    </tr>
    <!-- DATA PELANGGAN CONTENT -->
    <tr>
        <td>
            <table class="info-table">
                <tr>
                    <td class="label">Nama</td>
                    <td>: ' . htmlspecialchars($service['namapelanggan'] ?: '-') . '</td>
                    <td class="label">No HP</td>
                    <td>: ' . htmlspecialchars($service['nohp'] ?: '-') . '</td>
                </tr>
                <tr>
                    <td class="label">No Polisi</td>
                    <td>: ' . htmlspecialchars($service['no_polisi'] ?: '-') . '</td>
                    <td class="label">Tipe Motor</td>
                    <td>: ' . htmlspecialchars($service['tipe_motor'] ?: '-') . '</td>
                </tr>
                <tr>
                    <td class="label">KM</td>
                    <td colspan="3">: ' . number_format(floatval($service['km_motor']), 0, ',', '.') . '</td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- SPACER -->
    <tr><td height="15"></td></tr>

    <!-- 3. KELUHAN TITLE -->
    <tr>
        <td class="section-header">KELUHAN PELANGGAN</td>
    </tr>
    <!-- KELUHAN CONTENT -->
    <tr>
        <td>';
        if(count($keluhan_list) > 0) {
            $html .= '<table class="bordered-table">
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="75%">Keluhan</th>
                    <th width="20%" class="text-center">Status</th>
                </tr>';
            $no = 0;
            foreach($keluhan_list as $k) {
                $no++;
                $status_badge = ($k['status_pengerjaan'] == 'selesai') ? 'Selesai' : ucfirst($k['status_pengerjaan']);
                $html .= '<tr>
                    <td class="text-center">' . $no . '</td>
                    <td>' . htmlspecialchars($k['keluhan']) . '</td>
                    <td class="text-center">' . $status_badge . '</td>
                </tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<div class="note-text">Tidak ada keluhan tercatat.</div>';
        }
    $html .= '</td>
    </tr>

    <!-- SPACER -->
    <tr><td height="15"></td></tr>

    <!-- 4. RINCIAN SERVIS TITLE -->
    <tr>
        <td class="section-header">RINCIAN SERVIS (Sesuai SPK)</td>
    </tr>
    <!-- RINCIAN SERVIS CONTENT -->
    <tr>
        <td>';
        $has_items = (count($items_jasa) > 0 || count($items_barang) > 0);
        if($has_items) {
            $html .= '<table class="bordered-table">
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="50%">Nama Item</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="17%" class="text-right">Harga</th>
                    <th width="18%" class="text-right">Total</th>
                </tr>';
            
            $no = 0;
            // Jasa
            if(count($items_jasa) > 0) {
                $html .= '<tr><td colspan="5" class="cat-jasa"><em>Jasa</em></td></tr>';
                foreach($items_jasa as $item) {
                    $no++;
                    $html .= '<tr>
                        <td class="text-center">' . $no . '</td>
                        <td>' . htmlspecialchars($item['nama_item']) . '</td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp ' . number_format(floatval($item['harga']), 0, ',', '.') . '</td>
                        <td class="text-right">Rp ' . number_format(floatval($item['total']), 0, ',', '.') . '</td>
                    </tr>';
                }
            }
            // Barang
            if(count($items_barang) > 0) {
                $html .= '<tr><td colspan="5" class="cat-barang"><em>Sparepart</em></td></tr>';
                foreach($items_barang as $item) {
                    $no++;
                    $html .= '<tr>
                        <td class="text-center">' . $no . '</td>
                        <td>' . htmlspecialchars($item['nama_item']) . '</td>
                        <td class="text-center">' . $item['quantity'] . '</td>
                        <td class="text-right">Rp ' . number_format(floatval($item['harga_jual']), 0, ',', '.') . '</td>
                        <td class="text-right">Rp ' . number_format(floatval($item['total']), 0, ',', '.') . '</td>
                    </tr>';
                }
            }
            $html .= '<tr>
                <td colspan="4" class="text-right"><strong>Subtotal Servis :</strong></td>
                <td class="text-right"><strong>Rp ' . number_format($subtotal_servis, 0, ',', '.') . '</strong></td>
            </tr>';
            $html .= '</table>';
        } else {
            $html .= '<div class="note-text">Belum ada item servis.</div>';
        }
    $html .= '</td>
    </tr>

    <!-- SPACER -->
    <tr><td height="15"></td></tr>

    <!-- 5. REKOMENDASI TITLE -->
    <tr>
        <td class="section-header">REKOMENDASI PENGGANTIAN PART</td>
    </tr>
    <!-- REKOMENDASI NOTES -->
    <tr>
        <td class="note-text" style="padding-bottom:5px;">
            (Hasil Pengecekan Mekanik) - Opsional, dikerjakan jika disetujui.
        </td>
    </tr>
    <!-- REKOMENDASI URGENT -->
    <tr>
        <td class="urgent-text" style="padding-bottom:3px;">PRIORITAS URGENT (Disarankan Segera)</td>
    </tr>
    <tr>
        <td>';
        if(count($temuan_urgent) > 0 || count($penawaran_urgent) > 0) {
            $html .= '<table class="bordered-table">';
            $no = 0;
            foreach($temuan_urgent as $t) {
                $no++;
                $html .= '<tr>
                    <td width="5%" class="text-center">' . $no . '</td>
                    <td width="75%">' . htmlspecialchars($t['nama_temuan_display']) . '</td>
                    <td width="20%" class="text-right">Rp ' . number_format($t['estimasi_biaya'], 0, ',', '.') . '</td>
                </tr>';
            }
            foreach($penawaran_urgent as $p) {
                $no++;
                $html .= '<tr>
                    <td width="5%" class="text-center">' . $no . '</td>
                    <td width="75%">' . htmlspecialchars($p['nama_barang']) . ' (x' . $p['quantity'] . ')</td>
                    <td width="20%" class="text-right">Rp ' . number_format($p['total_harga'], 0, ',', '.') . '</td>
                </tr>';
            }
            $html .= '<tr>
                <td colspan="2" class="text-right urgent-text">Total Urgent :</td>
                <td class="text-right urgent-text">Rp ' . number_format($total_urgent, 0, ',', '.') . '</td>
            </tr>';
            $html .= '</table>';
        } else {
            $html .= '<div class="note-text">Tidak ada rekomendasi urgent.</div>';
        }
    $html .= '</td>
    </tr>

    <!-- SPACER -->
    <tr><td height="10"></td></tr>

    <!-- REKOMENDASI NON-URGENT -->
    <tr>
        <td class="non-urgent-text" style="padding-bottom:3px;">PRIORITAS TIDAK URGENT (Masih Bisa Dipakai)</td>
    </tr>
    <tr>
        <td>';
        if(count($temuan_non_urgent) > 0 || count($penawaran_non_urgent) > 0) {
            $html .= '<table class="bordered-table">';
            $no = 0;
            foreach($temuan_non_urgent as $t) {
                $no++;
                $html .= '<tr>
                    <td width="5%" class="text-center">' . $no . '</td>
                    <td width="75%">' . htmlspecialchars($t['nama_temuan_display']) . '</td>
                    <td width="20%" class="text-right">Rp ' . number_format($t['estimasi_biaya'], 0, ',', '.') . '</td>
                </tr>';
            }
            foreach($penawaran_non_urgent as $p) {
                $no++;
                $html .= '<tr>
                    <td width="5%" class="text-center">' . $no . '</td>
                    <td width="75%">' . htmlspecialchars($p['nama_barang']) . ' (x' . $p['quantity'] . ')</td>
                    <td width="20%" class="text-right">Rp ' . number_format($p['total_harga'], 0, ',', '.') . '</td>
                </tr>';
            }
            $html .= '<tr>
                <td colspan="2" class="text-right non-urgent-text">Total Tidak Urgent :</td>
                <td class="text-right non-urgent-text">Rp ' . number_format($total_non_urgent, 0, ',', '.') . '</td>
            </tr>';
            $html .= '</table>';
        } else {
            $html .= '<div class="note-text">Tidak ada rekomendasi non-urgent.</div>';
        }
    $html .= '</td>
    </tr>

    <!-- SPACER -->
    <tr><td height="15"></td></tr>

    <!-- 6. RINGKASAN TITLE -->
    <tr>
        <td class="section-header">RINGKASAN BIAYA</td>
    </tr>
    <!-- RINGKASAN CONTENT -->
    <tr>
        <td>
            <table class="info-table">
                <tr>
                    <td width="60%"></td>
                    <td width="25%">Subtotal Servis</td>
                    <td width="15%" class="text-right">Rp ' . number_format($subtotal_servis, 0, ',', '.') . '</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="urgent-text">Estimasi Urgent</td>
                    <td class="text-right urgent-text">Rp ' . number_format($total_urgent, 0, ',', '.') . '</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="non-urgent-text">Estimasi Tidak Urgent</td>
                    <td class="text-right non-urgent-text">Rp ' . number_format($total_non_urgent, 0, ',', '.') . '</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding-top: 10px;">
            <div class="grand-total-box">
                GRAND TOTAL ESTIMASI: Rp ' . number_format($grand_total, 0, ',', '.') . '
            </div>
        </td>
    </tr>

    <!-- SPACER -->
    <tr><td height="15"></td></tr>

    <!-- 7. PERSETUJUAN TITLE -->
    <tr>
        <td class="section-header">PERSETUJUAN PELANGGAN</td>
    </tr>
    <!-- PERSETUJUAN CONTENT -->
    <tr>
        <td>
            <!-- Table as Border Container -->
            <table style="width: 100%; border: 1px solid #999; border-collapse: separate; border-spacing: 0;">
                <tr>
                    <td style="padding: 10px;">
                        
                        <!-- Checkboxes -->
                        <table style="width: 100%; margin-bottom: 20px;">
                            <tr><td style="padding-bottom: 5px;"><span style="border:1px solid #000; display:inline-block; width:12px; height:12px; margin-right:5px;">&nbsp;</span> Setuju dikerjakan sesuai estimasi</td></tr>
                            <tr><td style="padding-bottom: 5px;"><span style="border:1px solid #000; display:inline-block; width:12px; height:12px; margin-right:5px;">&nbsp;</span> Setuju servis + urgent saja</td></tr>
                            <tr><td style="padding-bottom: 5px;"><span style="border:1px solid #000; display:inline-block; width:12px; height:12px; margin-right:5px;">&nbsp;</span> Setuju servis saja</td></tr>
                            <tr><td style="padding-bottom: 5px;"><span style="border:1px solid #000; display:inline-block; width:12px; height:12px; margin-right:5px;">&nbsp;</span> Tidak setuju / ditunda</td></tr>
                        </table>
                        
                        <!-- Signatures -->
                        <table style="width: 100%;">
                            <tr>
                                <td width="55%" style="vertical-align: bottom;">
                                    Tanda tangan pelanggan:<br><br><br><br>
                                    <div style="border-bottom: 1px solid #000; width: 200px;"></div>
                                </td>
                                <td width="45%" style="vertical-align: top;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="padding-bottom: 20px;">Nama Terang: ________________</td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal: ________________</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    
    <!-- SPACER -->
    <tr><td height="10"></td></tr>

    <!-- FOOTER -->
    <tr>
        <td class="note-text" style="border-top:1px solid #ccc; padding-top:5px;">
            <strong>Catatan:</strong> Biaya aktual dapat berubah apabila ditemukan kerusakan tambahan dan akan dikonfirmasi ulang ke pelanggan.
        </td>
    </tr>

</table>

</body>
</html>';

            // Generate PDF
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Clean output buffer before streaming
            ob_end_clean();

            $filename_estimasi = 'estimasi_servis_' . $no_service . '_' . date('Ymd_His') . '.pdf';
            $dompdf->stream($filename_estimasi, array("Attachment" => true));
            exit;

        } catch (Exception $e) {
            ob_end_clean();
            die("Error generating estimasi PDF: " . $e->getMessage() . "<br><br>Stack trace:<br>" . nl2br($e->getTraceAsString()));
        }
    }

    // ========================================
    // PDF FORMAT
    // ========================================
    if($format == 'pdf') {
        try {
            // Check if dompdf exists
            $dompdf_path = __DIR__ . "/dompdf/autoload.inc.php";
            if(!file_exists($dompdf_path)) {
                throw new Exception("Dompdf library not found at: " . $dompdf_path);
            }

            require_once($dompdf_path);
            $dompdf = new \Dompdf\Dompdf();
        } catch (Exception $e) {
            ob_end_clean();
            die("Error loading Dompdf: " . $e->getMessage() . "<br><br>Stack trace:<br>" . nl2br($e->getTraceAsString()));
        }

        // Build HTML for PDF
        $html = '<html><head>';
        $html .= '<style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
            h1 { color: #2c3e50; font-size: 18pt; margin-bottom: 10px; border-bottom: 3px solid #3498db; padding-bottom: 5px; }
            h2 { color: white; background: #3498db; padding: 8px; font-size: 14pt; margin-top: 20px; }
            h2.penawaran { background: #27ae60; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 9pt; }
            th { background: #ecf0f1; color: #2c3e50; padding: 8px; text-align: left; border: 1px solid #bdc3c7; font-weight: bold; }
            td { padding: 6px; border: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .pending { background: #fff3cd !important; }
            .disetujui { background: #d4edda !important; }
            .ditolak { background: #f8d7da !important; }
            .summary { background: #3498db; color: white; font-weight: bold; }
            .meta-info { font-size: 9pt; color: #7f8c8d; margin-bottom: 15px; }
            .page-break { page-break-after: always; }
        </style>';
        $html .= '</head><body>';

        // Header
        $html .= '<h1>LAPORAN TEMUAN & PENAWARAN PART</h1>';
        $html .= '<div class="meta-info">';
        $html .= 'Tanggal Export: ' . date('d/m/Y H:i:s') . '<br>';
        $html .= 'User: ' . htmlspecialchars($_SESSION['_nama'] ?? 'System') . '<br>';
        if($no_service) {
            $html .= 'No. Service: ' . htmlspecialchars($no_service) . '<br>';
        }
        $html .= '</div>';

        // ========================================
        // EXPORT TEMUAN
        // ========================================
        if($type == 'temuan' || $type == 'all') {
            $html .= '<h2>DATA TEMUAN HASIL PENGECEKAN</h2>';
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th width="3%">No</th>';
            $html .= '<th width="12%">No. Service</th>';
            $html .= '<th width="20%">Nama Temuan</th>';
            $html .= '<th width="10%">Kategori</th>';
            $html .= '<th width="12%">Jenis</th>';
            $html .= '<th width="8%">Urgensi</th>';
            $html .= '<th width="10%">Status</th>';
            $html .= '<th width="12%">Estimasi</th>';
            $html .= '<th width="13%">Mekanik</th>';
            $html .= '</tr></thead><tbody>';

            // Query temuan
            $where_clause = "1=1";
            if($no_service != '') {
                $where_clause .= " AND t.no_service='$no_service'";
            }

            $sql_temuan = "SELECT
                            t.*,
                            s.tanggal as tanggal_service,
                            COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display,
                            mt.kategori as kategori_temuan
                          FROM tbservis_temuan t
                          LEFT JOIN tblservice s ON t.no_service = s.no_service
                          LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                          WHERE $where_clause
                          ORDER BY t.created_at DESC";

            $result_temuan = mysqli_query($koneksi, $sql_temuan);

            if(!$result_temuan) {
                ob_end_clean();
                die("Error query temuan (PDF): " . mysqli_error($koneksi) . "<br><br>Query: <pre>" . htmlspecialchars($sql_temuan) . "</pre>");
            }

            $no = 0;

            while($temuan = mysqli_fetch_array($result_temuan)) {
                $no++;
                $jenis = $temuan['jenis_perbaikan'] == 'penggantian_part' ? 'Ganti Part' : 'Setting';

                $html .= '<tr>';
                $html .= '<td class="text-center">' . $no . '</td>';
                $html .= '<td>' . htmlspecialchars($temuan['no_service']) . '</td>';
                $html .= '<td>' . htmlspecialchars($temuan['nama_temuan_display']) . '</td>';
                $html .= '<td>' . htmlspecialchars($temuan['kategori_temuan'] ?: '-') . '</td>';
                $html .= '<td>' . $jenis . '</td>';
                $html .= '<td class="text-center">' . ucfirst($temuan['tingkat_urgensi']) . '</td>';
                $html .= '<td class="text-center">' . ucfirst($temuan['status_temuan']) . '</td>';
                $html .= '<td class="text-right">Rp ' . number_format($temuan['estimasi_biaya'], 0, ',', '.') . '</td>';
                $html .= '<td>' . htmlspecialchars($temuan['mekanik_name'] ?: '-') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        // ========================================
        // EXPORT PENDING ITEMS DARI WORK ORDER
        // ========================================
        if($type == 'all' || $type == 'pending') {
            // Query pending items dari Work Order
            $where_pending = "pi.status_approval = 'pending'";
            if($no_service != '') {
                $where_pending .= " AND pi.no_service='$no_service'";
            }

            $sql_pending = "SELECT 
                                pi.*,
                                sw.kode_wo,
                                wh.nama_wo
                            FROM tbservis_pending_items pi
                            LEFT JOIN tbservis_workorder sw ON pi.wo_id = sw.id
                            LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                            WHERE $where_pending
                            ORDER BY pi.created_at DESC";
            $result_pending = mysqli_query($koneksi, $sql_pending);
            
            if($result_pending && mysqli_num_rows($result_pending) > 0) {
                $html .= '<h2 style="background: #f39c12;">ITEM PENDING DARI WORK ORDER</h2>';
                $html .= '<table>';
                $html .= '<thead><tr>';
                $html .= '<th width="3%">No</th>';
                $html .= '<th width="12%">No. Service</th>';
                $html .= '<th width="15%">Work Order</th>';
                $html .= '<th width="8%">Tipe</th>';
                $html .= '<th width="10%">Kode</th>';
                $html .= '<th width="20%">Nama Item</th>';
                $html .= '<th width="5%">Qty</th>';
                $html .= '<th width="12%">Harga</th>';
                $html .= '<th width="12%">Total</th>';
                $html .= '</tr></thead><tbody>';

                $no = 0;
                $total_pending = 0;
                while($pending = mysqli_fetch_array($result_pending)) {
                    $no++;
                    $tipe_text = ($pending['tipe'] == 'barang') ? 'Barang' : 'Jasa';
                    $wo_display = $pending['nama_wo'] ? $pending['nama_wo'] : $pending['kode_wo'];
                    $total_pending += $pending['total'];

                    $html .= '<tr class="pending">';
                    $html .= '<td class="text-center">' . $no . '</td>';
                    $html .= '<td>' . htmlspecialchars($pending['no_service']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($wo_display) . '</td>';
                    $html .= '<td class="text-center">' . $tipe_text . '</td>';
                    $html .= '<td>' . htmlspecialchars($pending['kode_item']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($pending['nama_item']) . '</td>';
                    $html .= '<td class="text-center">' . $pending['quantity'] . '</td>';
                    $html .= '<td class="text-right">Rp ' . number_format($pending['harga_satuan'], 0, ',', '.') . '</td>';
                    $html .= '<td class="text-right">Rp ' . number_format($pending['total'], 0, ',', '.') . '</td>';
                    $html .= '</tr>';
                }

                // Summary row
                $html .= '<tr class="summary" style="background: #f39c12;">';
                $html .= '<td colspan="8" class="text-right">TOTAL PENDING:</td>';
                $html .= '<td class="text-right">Rp ' . number_format($total_pending, 0, ',', '.') . '</td>';
                $html .= '</tr>';

                $html .= '</tbody></table>';
            }
        }

        // ========================================
        // EXPORT PENAWARAN PART
        // ========================================
        if($type == 'penawaran' || $type == 'all') {
            $html .= '<h2 class="penawaran">DATA PENAWARAN PART</h2>';
            $html .= '<table>';
            $html .= '<thead><tr>';
            $html .= '<th width="3%">No</th>';
            $html .= '<th width="12%">No. Service</th>';
            $html .= '<th width="15%">Temuan</th>';
            $html .= '<th width="20%">Nama Part</th>';
            $html .= '<th width="5%">Qty</th>';
            $html .= '<th width="12%">Harga</th>';
            $html .= '<th width="12%">Total</th>';
            $html .= '<th width="10%">Status</th>';
            $html .= '<th width="11%">User</th>';
            $html .= '</tr></thead><tbody>';

            // Query penawaran
            $where_clause = "1=1";
            if($no_service != '') {
                $where_clause .= " AND p.no_service='$no_service'";
            }
            if($status_filter != '') {
                $where_clause .= " AND p.status_penawaran='$status_filter'";
            }

            $sql_penawaran = "SELECT
                                p.*,
                                COALESCE(mt.nama_temuan, t.temuan_custom, 'Umum') as nama_temuan
                              FROM tbservis_penawaran_part p
                              LEFT JOIN tbservis_temuan t ON p.temuan_id = t.id
                              LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                              WHERE $where_clause
                              ORDER BY p.created_at DESC";

            $result_penawaran = mysqli_query($koneksi, $sql_penawaran);

            if(!$result_penawaran) {
                ob_end_clean();
                die("Error query penawaran (PDF): " . mysqli_error($koneksi) . "<br><br>Query: <pre>" . htmlspecialchars($sql_penawaran) . "</pre>");
            }

            $no = 0;
            $total_nilai = 0;

            while($penawaran = mysqli_fetch_array($result_penawaran)) {
                $no++;

                // Row class based on status
                $row_class = '';
                if($penawaran['status_penawaran'] == 'pending') $row_class = 'pending';
                elseif($penawaran['status_penawaran'] == 'disetujui') $row_class = 'disetujui';
                elseif($penawaran['status_penawaran'] == 'ditolak') $row_class = 'ditolak';

                if($penawaran['status_penawaran'] != 'ditolak') {
                    $total_nilai += $penawaran['total_harga'];
                }

                $html .= '<tr class="' . $row_class . '">';
                $html .= '<td class="text-center">' . $no . '</td>';
                $html .= '<td>' . htmlspecialchars($penawaran['no_service']) . '</td>';
                $html .= '<td>' . htmlspecialchars($penawaran['nama_temuan']) . '</td>';
                $html .= '<td>' . htmlspecialchars($penawaran['nama_barang']) . '</td>';
                $html .= '<td class="text-center">' . $penawaran['quantity'] . '</td>';
                $html .= '<td class="text-right">Rp ' . number_format($penawaran['harga_satuan'], 0, ',', '.') . '</td>';
                $html .= '<td class="text-right">Rp ' . number_format($penawaran['total_harga'], 0, ',', '.') . '</td>';
                $html .= '<td class="text-center">' . ucfirst($penawaran['status_penawaran']) . '</td>';
                $html .= '<td>' . htmlspecialchars($penawaran['user_penawaran'] ?: '-') . '</td>';
                $html .= '</tr>';
            }

            // Summary row
            $summary_query = "SELECT
                                COUNT(*) as total,
                                SUM(CASE WHEN status_penawaran='pending' THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN status_penawaran='disetujui' THEN 1 ELSE 0 END) as disetujui,
                                SUM(CASE WHEN status_penawaran='ditolak' THEN 1 ELSE 0 END) as ditolak
                              FROM tbservis_penawaran_part p
                              WHERE $where_clause";
            $summary_result = mysqli_query($koneksi, $summary_query);

            if(!$summary_result) {
                ob_end_clean();
                die("Error query summary (PDF): " . mysqli_error($koneksi) . "<br><br>Query: <pre>" . htmlspecialchars($summary_query) . "</pre>");
            }

            $summary = mysqli_fetch_array($summary_result);

            $html .= '<tr class="summary">';
            $html .= '<td colspan="6" class="text-right">TOTAL NILAI:</td>';
            $html .= '<td class="text-right">Rp ' . number_format($total_nilai, 0, ',', '.') . '</td>';
            $html .= '<td colspan="2">Total: ' . $summary['total'] . ' | Pending: ' . $summary['pending'] . ' | Disetujui: ' . $summary['disetujui'] . ' | Ditolak: ' . $summary['ditolak'] . '</td>';
            $html .= '</tr>';

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        // Generate PDF
        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            // Clean output buffer before streaming
            ob_end_clean();

            $dompdf->stream($filename . '.pdf', array("Attachment" => true));
            exit;
        } catch (Exception $e) {
            ob_end_clean();
            die("Error generating PDF: " . $e->getMessage() . "<br><br>Stack trace:<br>" . nl2br($e->getTraceAsString()));
        }
    }

    // ========================================
    // EXCEL FORMAT
    // ========================================
    else {
        // Clean output buffer before sending headers
        ob_end_clean();

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');

        // ========================================
        // EXPORT TEMUAN
        // ========================================
        if($type == 'temuan' || $type == 'all') {
            echo '<h2 style="background-color: #3498db; color: white; padding: 10px;">DATA TEMUAN HASIL PENGECEKAN</h2>';
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<tr style="background-color: #3498db; color: white; font-weight: bold;">';
            echo '<td>No</td>';
            echo '<td>No. Service</td>';
            echo '<td>Tanggal</td>';
            echo '<td>Nama Temuan</td>';
            echo '<td>Kode Temuan</td>';
            echo '<td>Kategori</td>';
            echo '<td>Jenis Perbaikan</td>';
            echo '<td>Tingkat Urgensi</td>';
            echo '<td>Status</td>';
            echo '<td>Estimasi Biaya</td>';
            echo '<td>Biaya Aktual</td>';
            echo '<td>Mekanik</td>';
            echo '<td>Keluhan Terkait</td>';
            echo '<td>Deskripsi Detail</td>';
            echo '<td>Keterangan Tidak Selesai</td>';
            echo '<td>Dibuat Oleh</td>';
            echo '<td>Tanggal Dibuat</td>';
            echo '</tr>';

            // Query temuan
            $where_clause = "1=1";
            if($no_service != '') {
                $where_clause .= " AND t.no_service='$no_service'";
            }

            $sql_temuan = "SELECT
                            t.*,
                            s.tanggal as tanggal_service,
                            s.no_pelanggan,
                            COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display,
                            mt.kategori as kategori_temuan,
                            k.keluhan,
                            p.namapelanggan
                          FROM tbservis_temuan t
                          LEFT JOIN tblservice s ON t.no_service = s.no_service
                          LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                          LEFT JOIN tbservis_keluhan_status k ON t.keluhan_id = k.id
                          LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                          WHERE $where_clause
                          ORDER BY t.created_at DESC";

            $result_temuan = mysqli_query($koneksi, $sql_temuan);

            if(!$result_temuan) {
                die("Error query temuan (Excel): " . mysqli_error($koneksi) . "<br><br>Query: <pre>" . htmlspecialchars($sql_temuan) . "</pre>");
            }

            $no = 0;

            while($temuan = mysqli_fetch_array($result_temuan)) {
                $no++;

                $jenis_perbaikan_text = $temuan['jenis_perbaikan'] == 'penggantian_part' ? 'Penggantian Part' : 'Setting/Servis';
                $urgensi_text = ucfirst($temuan['tingkat_urgensi']);
                $status_text = ucfirst($temuan['status_temuan']);

                echo '<tr>';
                echo '<td>' . $no . '</td>';
                echo '<td>' . htmlspecialchars($temuan['no_service']) . '</td>';
                echo '<td>' . date('d/m/Y', strtotime($temuan['tanggal_service'])) . '</td>';
                echo '<td>' . htmlspecialchars($temuan['nama_temuan_display']) . '</td>';
                echo '<td>' . htmlspecialchars($temuan['kode_temuan'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($temuan['kategori_temuan'] ?: '-') . '</td>';
                echo '<td>' . $jenis_perbaikan_text . '</td>';
                echo '<td>' . $urgensi_text . '</td>';
                echo '<td>' . $status_text . '</td>';
                echo '<td align="right">' . number_format($temuan['estimasi_biaya'], 0, ',', '.') . '</td>';
                echo '<td align="right">' . number_format($temuan['biaya_actual'], 0, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($temuan['mekanik_name'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($temuan['keluhan'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($temuan['deskripsi_temuan'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($temuan['keterangan_tidak_selesai'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($temuan['created_by'] ?: '-') . '</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($temuan['created_at'])) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '<br><br>';
        }

        // ========================================
        // EXPORT PENDING ITEMS DARI WORK ORDER
        // ========================================
        if($type == 'all' || $type == 'pending') {
            // Query pending items dari Work Order
            $where_pending = "pi.status_approval = 'pending'";
            if($no_service != '') {
                $where_pending .= " AND pi.no_service='$no_service'";
            }

            $sql_pending = "SELECT 
                                pi.*,
                                sw.kode_wo,
                                wh.nama_wo
                            FROM tbservis_pending_items pi
                            LEFT JOIN tbservis_workorder sw ON pi.wo_id = sw.id
                            LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                            WHERE $where_pending
                            ORDER BY pi.created_at DESC";
            $result_pending = mysqli_query($koneksi, $sql_pending);
            
            if($result_pending && mysqli_num_rows($result_pending) > 0) {
                echo '<h2 style="background-color: #f39c12; color: white; padding: 10px;">ITEM PENDING DARI WORK ORDER</h2>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<tr style="background-color: #f39c12; color: white; font-weight: bold;">';
                echo '<td>No</td>';
                echo '<td>No. Service</td>';
                echo '<td>Kode WO</td>';
                echo '<td>Nama Work Order</td>';
                echo '<td>Tipe</td>';
                echo '<td>Kode Item</td>';
                echo '<td>Nama Item</td>';
                echo '<td>Qty</td>';
                echo '<td>Harga Satuan</td>';
                echo '<td>Total</td>';
                echo '<td>Status</td>';
                echo '<td>Tanggal</td>';
                echo '</tr>';

                $no = 0;
                $total_pending = 0;
                while($pending = mysqli_fetch_array($result_pending)) {
                    $no++;
                    $tipe_text = ($pending['tipe'] == 'barang') ? 'Barang' : 'Jasa';
                    $wo_display = $pending['nama_wo'] ? $pending['nama_wo'] : '-';
                    $total_pending += $pending['total'];

                    echo '<tr style="background-color: #fff3cd;">';
                    echo '<td>' . $no . '</td>';
                    echo '<td>' . htmlspecialchars($pending['no_service']) . '</td>';
                    echo '<td>' . htmlspecialchars($pending['kode_wo']) . '</td>';
                    echo '<td>' . htmlspecialchars($wo_display) . '</td>';
                    echo '<td>' . $tipe_text . '</td>';
                    echo '<td>' . htmlspecialchars($pending['kode_item']) . '</td>';
                    echo '<td>' . htmlspecialchars($pending['nama_item']) . '</td>';
                    echo '<td align="center">' . $pending['quantity'] . '</td>';
                    echo '<td align="right">' . number_format($pending['harga_satuan'], 0, ',', '.') . '</td>';
                    echo '<td align="right"><strong>' . number_format($pending['total'], 0, ',', '.') . '</strong></td>';
                    echo '<td>' . ucfirst($pending['status_approval']) . '</td>';
                    echo '<td>' . date('d/m/Y H:i', strtotime($pending['created_at'])) . '</td>';
                    echo '</tr>';
                }

                // Summary
                echo '<tr style="background-color: #f39c12; color: white; font-weight: bold;">';
                echo '<td colspan="9" align="right">TOTAL PENDING:</td>';
                echo '<td align="right">' . number_format($total_pending, 0, ',', '.') . '</td>';
                echo '<td colspan="2">Total: ' . $no . ' items</td>';
                echo '</tr>';

                echo '</table>';
                echo '<br><br>';
            }
        }

        // ========================================
        // EXPORT PENAWARAN PART
        // ========================================
        if($type == 'penawaran' || $type == 'all') {
            echo '<h2 style="background-color: #27ae60; color: white; padding: 10px;">DATA PENAWARAN PART</h2>';
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<tr style="background-color: #27ae60; color: white; font-weight: bold;">';
            echo '<td>No</td>';
            echo '<td>No. Service</td>';
            echo '<td>Tanggal Service</td>';
            echo '<td>Pelanggan</td>';
            echo '<td>Temuan Terkait</td>';
            echo '<td>Kode Part</td>';
            echo '<td>Nama Part</td>';
            echo '<td>Qty</td>';
            echo '<td>Harga Satuan</td>';
            echo '<td>Total Harga</td>';
            echo '<td>Status</td>';
            echo '<td>Alasan Tolak</td>';
            echo '<td>Keterangan Tolak</td>';
            echo '<td>Tanggal Penawaran</td>';
            echo '<td>User Penawaran</td>';
            echo '<td>Tanggal Respon</td>';
            echo '<td>User Respon</td>';
            echo '<td>Sumber</td>';
            echo '</tr>';

            // Query penawaran
            $where_clause = "1=1";
            if($no_service != '') {
                $where_clause .= " AND p.no_service='$no_service'";
            }
            if($status_filter != '') {
                $where_clause .= " AND p.status_penawaran='$status_filter'";
            }

            $sql_penawaran = "SELECT
                                p.*,
                                s.tanggal as tanggal_service,
                                s.no_pelanggan,
                                pel.namapelanggan,
                                COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan
                              FROM tbservis_penawaran_part p
                              LEFT JOIN tblservice s ON p.no_service = s.no_service
                              LEFT JOIN tblpelanggan pel ON s.no_pelanggan = pel.nopelanggan
                              LEFT JOIN tbservis_temuan t ON p.temuan_id = t.id
                              LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                              WHERE $where_clause
                              ORDER BY p.created_at DESC";

            $result_penawaran = mysqli_query($koneksi, $sql_penawaran);

            if(!$result_penawaran) {
                die("Error query penawaran (Excel): " . mysqli_error($koneksi) . "<br><br>Query: <pre>" . htmlspecialchars($sql_penawaran) . "</pre>");
            }

            $no = 0;

            while($penawaran = mysqli_fetch_array($result_penawaran)) {
                $no++;

                $status_text = ucfirst($penawaran['status_penawaran']);
                $alasan_map = [
                    'customer_tidak_mau' => 'Customer tidak mau',
                    'stok_bengkel_kosong' => 'Stok bengkel kosong',
                    'stok_supplier_kosong' => 'Stok supplier kosong',
                    'harga_tidak_cocok' => 'Harga tidak cocok',
                    'lainnya' => 'Lainnya'
                ];
                $alasan_text = $alasan_map[$penawaran['alasan_tolak']] ?? '-';

                $sumber = $penawaran['is_from_suggestion'] == 1 ? 'Auto-Suggest' : 'Manual';

                // Color coding for status
                $row_bg = '';
                if($penawaran['status_penawaran'] == 'pending') {
                    $row_bg = '#fff3cd';
                } elseif($penawaran['status_penawaran'] == 'disetujui') {
                    $row_bg = '#d4edda';
                } elseif($penawaran['status_penawaran'] == 'ditolak') {
                    $row_bg = '#f8d7da';
                }

                echo '<tr' . ($row_bg ? ' style="background-color: ' . $row_bg . ';"' : '') . '>';
                echo '<td>' . $no . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['no_service']) . '</td>';
                echo '<td>' . ($penawaran['tanggal_service'] ? date('d/m/Y', strtotime($penawaran['tanggal_service'])) : '-') . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['namapelanggan'] ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['nama_temuan'] ?: 'Umum (tanpa temuan)') . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['kode_barang']) . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['nama_barang']) . '</td>';
                echo '<td align="center">' . $penawaran['quantity'] . '</td>';
                echo '<td align="right">' . number_format($penawaran['harga_satuan'], 0, ',', '.') . '</td>';
                echo '<td align="right"><strong>' . number_format($penawaran['total_harga'], 0, ',', '.') . '</strong></td>';
                echo '<td><strong>' . $status_text . '</strong></td>';
                echo '<td>' . $alasan_text . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['keterangan_tolak'] ?: '-') . '</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($penawaran['tanggal_penawaran'])) . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['user_penawaran'] ?: '-') . '</td>';
                echo '<td>' . ($penawaran['tanggal_respon'] ? date('d/m/Y H:i', strtotime($penawaran['tanggal_respon'])) : '-') . '</td>';
                echo '<td>' . htmlspecialchars($penawaran['user_respon'] ?: '-') . '</td>';
                echo '<td>' . $sumber . '</td>';
                echo '</tr>';
            }

            // Summary row
            $summary_query = "SELECT
                                COUNT(*) as total,
                                SUM(CASE WHEN status_penawaran='pending' THEN 1 ELSE 0 END) as pending,
                                SUM(CASE WHEN status_penawaran='disetujui' THEN 1 ELSE 0 END) as disetujui,
                                SUM(CASE WHEN status_penawaran='ditolak' THEN 1 ELSE 0 END) as ditolak,
                                SUM(CASE WHEN status_penawaran IN ('pending','disetujui') THEN total_harga ELSE 0 END) as total_nilai
                              FROM tbservis_penawaran_part p
                              WHERE $where_clause";
            $summary_result = mysqli_query($koneksi, $summary_query);

            if(!$summary_result) {
                die("Error query summary (Excel): " . mysqli_error($koneksi) . "<br><br>Query: <pre>" . htmlspecialchars($summary_query) . "</pre>");
            }

            $summary = mysqli_fetch_array($summary_result);

            echo '<tr style="background-color: #3498db; color: white; font-weight: bold;">';
            echo '<td colspan="9" align="right">RINGKASAN:</td>';
            echo '<td align="right">' . number_format($summary['total_nilai'], 0, ',', '.') . '</td>';
            echo '<td colspan="8">Total: ' . $summary['total'] . ' | Pending: ' . $summary['pending'] . ' | Disetujui: ' . $summary['disetujui'] . ' | Ditolak: ' . $summary['ditolak'] . '</td>';
            echo '</tr>';
            echo '</table>';
        }

        // Footer
        echo '<br>';
        echo '<p><small>Diekspor pada: ' . date('d/m/Y H:i:s') . ' oleh ' . htmlspecialchars($_SESSION['_nama'] ?? 'System') . '</small></p>';
    }
}
?>
