<?php
/**
 * GENERATE PDF ESTIMASI
 * PDF untuk estimasi servis berisi: Keluhan, Work Order, Temuan, dan Penawaran Part
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/koneksi.php';

// Get no_service
$no_service = isset($_GET['no_service']) ? mysqli_real_escape_string($koneksi, $_GET['no_service']) : '';

if(empty($no_service)) {
    die('Nomor service tidak ditemukan');
}

// Get service data
$query = "SELECT 
            s.*,
            DATE_FORMAT(s.tanggal,'%d/%m/%Y') AS tanggal_format,
            p.namapelanggan,
            p.alamat as alamat_pelanggan,
            p.telephone,
            k.pemilik,
            k.jenis,
            k.tipe,
            k.warna,
            pm.merek
          FROM tblservice s
          LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
          LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
          LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
          WHERE s.no_service='$no_service'";

$result = mysqli_query($koneksi, $query);

if(!$result) {
    die('Database error: ' . mysqli_error($koneksi));
}

if(mysqli_num_rows($result) == 0) {
    die('Data service tidak ditemukan');
}

$data = mysqli_fetch_assoc($result);

// Get keluhan
$query_keluhan = "SELECT keluhan, status_pengerjaan
                  FROM tbservis_keluhan_status
                  WHERE no_service='$no_service'
                  ORDER BY id";
$result_keluhan = mysqli_query($koneksi, $query_keluhan);

if(!$result_keluhan) {
    die('Error query keluhan: ' . mysqli_error($koneksi));
}

// Get work order
$query_wo = "SELECT wo.kode_wo, wh.nama_wo, wo.status_pengerjaan
             FROM tbservis_workorder wo
             LEFT JOIN tbworkorderheader wh ON wo.kode_wo = wh.kode_wo
             WHERE wo.no_service='$no_service'
             ORDER BY wo.id";
$result_wo = mysqli_query($koneksi, $query_wo);

if(!$result_wo) {
    die('Error query work order: ' . mysqli_error($koneksi));
}

// Get temuan
$query_temuan = "SELECT
                    st.id,
                    st.kode_temuan,
                    COALESCE(mt.nama_temuan, st.temuan_custom) as temuan,
                    COALESCE(mt.kategori, 'Lainnya') as kategori,
                    st.tingkat_urgensi,
                    st.estimasi_biaya,
                    st.status_temuan,
                    st.deskripsi_temuan
                 FROM tbservis_temuan st
                 LEFT JOIN tbmaster_temuan mt ON st.kode_temuan = mt.kode_temuan
                 WHERE st.no_service='$no_service'
                 ORDER BY st.id";
$result_temuan = mysqli_query($koneksi, $query_temuan);

if(!$result_temuan) {
    die('Error query temuan: ' . mysqli_error($koneksi));
}

// Get penawaran part + pending work order items
$query_penawaran = "SELECT kode_barang, nama_barang, quantity, harga_satuan, total_harga, status_penawaran
                    FROM tbservis_penawaran_part
                    WHERE no_service='$no_service'
                    UNION ALL
                    SELECT p.kode_item as kode_barang, 
                           p.nama_item as nama_barang, 
                           p.quantity, 
                           p.harga_satuan, 
                           p.total as total_harga, 
                           'PENDING' as status_penawaran
                    FROM tbservis_pending_items p
                    WHERE p.no_service='$no_service'
                      AND p.tipe IN ('barang', 'jasa')
                      AND p.status_approval='pending'
                    ORDER BY status_penawaran, kode_barang";
$result_penawaran = mysqli_query($koneksi, $query_penawaran);

if(!$result_penawaran) {
    die('Error query penawaran: ' . mysqli_error($koneksi));
}

// Generate HTML untuk PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Estimasi Servis <?php echo $no_service; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        .info-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 5px;
        }
        .info-table td {
            padding: 3px 5px;
            vertical-align: top;
        }
        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 8px 10px;
            margin-top: 15px;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .detail-table th,
        .detail-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        .detail-table th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }
        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        .summary-box {
            background-color: #ecf0f1;
            border: 2px solid #34495e;
            padding: 15px;
            margin-top: 20px;
        }
        .summary-table {
            width: 100%;
        }
        .summary-table td {
            padding: 5px 10px;
        }
        .grand-total {
            font-size: 14px;
            font-weight: bold;
            background-color: #34495e;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 15px;
            color: #999;
            font-style: italic;
        }
        .note {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
            font-style: italic;
        }
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background-color: #229954;
        }
        .btn i {
            margin-right: 5px;
        }
        /* Inline SVG icons to avoid CDN blocking */
        .icon-arrow-left {
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-right: 5px;
            vertical-align: middle;
        }
        .icon-pdf {
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-right: 5px;
            vertical-align: middle;
        }
        @media print {
            .action-buttons {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Action Buttons (Hidden when printing) -->
    <?php
    // Determine back URL from referer or default to service input
    $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '#';
    
    // If no referer, try to construct URL based on service type
    if($back_url == '#' || strpos($back_url, 'servis-estimasi-pdf') !== false) {
        // Detect service type from no_service prefix
        if(strpos($no_service, 'GAR-') === 0) {
            $back_url = "servis-garansi.php?snoserv={$no_service}&tab=temuan-penawaran";
        } elseif(strpos($no_service, 'JMP-') === 0) {
            $back_url = "servis-input-reguler-jemput.php?snoserv={$no_service}&tab=temuan-penawaran";
        } elseif(strpos($no_service, 'RST-') === 0) {
            $back_url = "servis-input-reguler-rst.php?snoserv={$no_service}&tab=temuan-penawaran";
        } else {
            $back_url = "servis-input-reguler.php?snoserv={$no_service}&tab=temuan-penawaran";
        }
    }
    ?>
    <div class="action-buttons">
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-primary" title="Kembali ke Input Servis">
            <svg class="icon-arrow-left" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
            </svg>
            Kembali
        </a>
        <a href="?no_service=<?php echo $no_service; ?>&download=1" class="btn btn-success" title="Download PDF">
            <svg class="icon-pdf" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
            </svg>
            Download PDF
        </a>
    </div>
    
    <div class="header">
        <h2>ESTIMASI SERVIS</h2>
        <p>Dokumen Estimasi Biaya & Penawaran</p>
        <h3><?php echo $no_service; ?></h3>
    </div>
    
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td width="15%"><strong>No. Service</strong></td>
                <td width="2%">:</td>
                <td width="33%"><?php echo $data['no_service']; ?></td>
                <td width="15%"><strong>Tanggal</strong></td>
                <td width="2%">:</td>
                <td width="33%"><?php echo $data['tanggal_format']; ?></td>
            </tr>
            <tr>
                <td><strong>Pelanggan</strong></td>
                <td>:</td>
                <td><?php echo $data['namapelanggan'] ?: $data['pemilik']; ?></td>
                <td><strong>Telepon</strong></td>
                <td>:</td>
                <td><?php echo $data['telephone']; ?></td>
            </tr>
            <tr>
                <td><strong>Kendaraan</strong></td>
                <td>:</td>
                <td><?php echo $data['no_polisi']; ?> - <?php echo $data['merek'] . ' ' . $data['tipe']; ?></td>
                <td><strong>KM</strong></td>
                <td>:</td>
                <td><?php echo number_format($data['km_skr'], 0, ',', '.'); ?> km</td>
            </tr>
        </table>
    </div>
    
    <!-- SECTION 1: KELUHAN -->
    <div class="section-title">1. KELUHAN PELANGGAN</div>
    <?php if(mysqli_num_rows($result_keluhan) > 0): ?>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="75%">Keluhan</th>
                <th width="20%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($keluhan = mysqli_fetch_assoc($result_keluhan)): 
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $keluhan['keluhan']; ?></td>
                <td class="text-center"><?php echo strtoupper($keluhan['status_pengerjaan']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">Tidak ada keluhan yang tercatat</div>
    <?php endif; ?>
    
    <!-- SECTION 2: WORK ORDER -->
    <div class="section-title">2. WORK ORDER</div>
    <?php if(mysqli_num_rows($result_wo) > 0): ?>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode WO</th>
                <th width="60%">Nama Work Order</th>
                <th width="20%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            mysqli_data_seek($result_wo, 0); // Reset pointer
            while($wo = mysqli_fetch_assoc($result_wo)): 
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo $wo['kode_wo']; ?></td>
                <td><?php echo $wo['nama_wo']; ?></td>
                <td class="text-center"><?php echo strtoupper($wo['status_pengerjaan']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">Tidak ada work order</div>
    <?php endif; ?>
    
    <!-- SECTION 3: TEMUAN -->
    <div class="section-title">3. TEMUAN MEKANIK</div>
    <?php if(mysqli_num_rows($result_temuan) > 0): ?>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="40%">Temuan</th>
                <th width="12%">Kategori</th>
                <th width="10%">Urgensi</th>
                <th width="15%">Estimasi Biaya</th>
                <th width="18%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_estimasi_temuan = 0;
            mysqli_data_seek($result_temuan, 0);
            while($temuan = mysqli_fetch_assoc($result_temuan)): 
                $total_estimasi_temuan += $temuan['estimasi_biaya'];
                
                // Status class
                $status_class = '';
                if($temuan['status_temuan'] == 'pending') $status_class = 'status-pending';
                elseif($temuan['status_temuan'] == 'disetujui') $status_class = 'status-approved';
                elseif($temuan['status_temuan'] == 'ditolak') $status_class = 'status-rejected';
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $temuan['temuan']; ?></td>
                <td class="text-center"><?php echo strtoupper($temuan['kategori']); ?></td>
                <td class="text-center"><?php echo strtoupper($temuan['tingkat_urgensi']); ?></td>
                <td class="text-right">Rp <?php echo number_format($temuan['estimasi_biaya'], 0, ',', '.'); ?></td>
                <td class="text-center <?php echo $status_class; ?>"><?php echo strtoupper($temuan['status_temuan']); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="4" class="text-right"><strong>Subtotal Temuan</strong></td>
                <td class="text-right"><strong>Rp <?php echo number_format($total_estimasi_temuan, 0, ',', '.'); ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">Tidak ada temuan mekanik</div>
    <?php 
    $total_estimasi_temuan = 0;
    endif; 
    ?>
    
    <!-- SECTION 4: PENAWARAN PART -->
    <div class="section-title">4. PENAWARAN PART</div>
    <?php if(mysqli_num_rows($result_penawaran) > 0): ?>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Kode</th>
                <th width="35%">Nama Part</th>
                <th width="8%">Qty</th>
                <th width="15%">Harga Satuan</th>
                <th width="15%">Total</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_penawaran = 0;
            mysqli_data_seek($result_penawaran, 0);
            while($penawaran = mysqli_fetch_assoc($result_penawaran)): 
                $total_penawaran += $penawaran['total_harga'];
                
                // Status class
                $status_class = '';
                if($penawaran['status_penawaran'] == 'pending') $status_class = 'status-pending';
                elseif($penawaran['status_penawaran'] == 'disetujui') $status_class = 'status-approved';
                elseif($penawaran['status_penawaran'] == 'ditolak') $status_class = 'status-rejected';
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo $penawaran['kode_barang']; ?></td>
                <td><?php echo $penawaran['nama_barang']; ?></td>
                <td class="text-center"><?php echo $penawaran['quantity']; ?></td>
                <td class="text-right">Rp <?php echo number_format($penawaran['harga_satuan'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?php echo number_format($penawaran['total_harga'], 0, ',', '.'); ?></td>
                <td class="text-center <?php echo $status_class; ?>"><?php echo strtoupper($penawaran['status_penawaran']); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="5" class="text-right"><strong>Subtotal Penawaran Part</strong></td>
                <td class="text-right"><strong>Rp <?php echo number_format($total_penawaran, 0, ',', '.'); ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">Tidak ada penawaran part</div>
    <?php 
    $total_penawaran = 0;
    endif; 
    ?>
    
    <!-- SUMMARY -->
    <div class="summary-box">
        <h4 style="margin-top: 0;">RINGKASAN ESTIMASI</h4>
        <table class="summary-table">
            <tr>
                <td width="70%"><strong>Total Estimasi Temuan</strong></td>
                <td width="30%" class="text-right">Rp <?php echo number_format($total_estimasi_temuan, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Total Penawaran Part</strong></td>
                <td class="text-right">Rp <?php echo number_format($total_penawaran, 0, ',', '.'); ?></td>
            </tr>
            <tr class="grand-total">
                <td style="padding: 10px;"><strong>TOTAL ESTIMASI</strong></td>
                <td class="text-right" style="padding: 10px;"><strong>Rp <?php echo number_format($total_estimasi_temuan + $total_penawaran, 0, ',', '.'); ?></strong></td>
            </tr>
        </table>
    </div>
    
    <div class="note">
        <p><strong>Catatan Penting:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Estimasi ini belum final dan menunggu approval dari pelanggan</li>
            <li>Harga dapat berubah sesuai kondisi aktual di lapangan</li>
            <li>Item dengan status PENDING masih menunggu persetujuan</li>
            <li>Dokumen ini digenerate otomatis pada <?php echo date('d/m/Y H:i'); ?></li>
        </ul>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Check if user wants to download as PDF
$download_pdf = isset($_GET['download']) && $_GET['download'] == '1';

if($download_pdf) {
    // Try to generate PDF using dompdf
    $dompdf_path = __DIR__ . '/dompdf/autoload.inc.php';
    
    if(file_exists($dompdf_path)) {
        try {
            // Suppress deprecation warnings for PHP 8+ compatibility
            $old_error_level = error_reporting();
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            
            require_once $dompdf_path;
            
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', false); // Disable HTML5 to avoid deprecation warnings
            $options->set('isRemoteEnabled', true);
            $options->set('chroot', __DIR__);
            
            $dompdf = new \Dompdf\Dompdf($options);
            
            // Remove action buttons and fix HTML for better PDF compatibility
            $html_for_pdf = preg_replace('/<div class="action-buttons">.*?<\/div>/s', '', $html);
            
            // Simplify DOCTYPE for better compatibility
            $html_for_pdf = str_replace('<!DOCTYPE html>', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">', $html_for_pdf);
            
            $dompdf->loadHtml($html_for_pdf);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Restore error reporting
            error_reporting($old_error_level);
            
            // Output PDF for download
            $dompdf->stream("Estimasi-" . $no_service . ".pdf", array("Attachment" => 1));
            exit;
        } catch(Exception $e) {
            // Restore error reporting in case of error
            if(isset($old_error_level)) error_reporting($old_error_level);
            
            // If dompdf fails, show error
            die('Error generating PDF: ' . $e->getMessage() . '<br><br><a href="?no_service=' . $no_service . '">Back to HTML version</a>');
        }
    } else {
        // Dompdf not found - use simple HTML to PDF instruction
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h2>Download PDF</h2>';
        echo '<p>Dompdf library not found at: ' . htmlspecialchars($dompdf_path) . '</p>';
        echo '<p><strong>Alternative:</strong> Use your browser\'s Print function and select "Save as PDF"</p>';
        echo '<ul>';
        echo '<li>Press Ctrl+P (Windows/Linux) or Cmd+P (Mac)</li>';
        echo '<li>Select "Save as PDF" or "Microsoft Print to PDF"</li>';
        echo '<li>Click Save</li>';
        echo '</ul>';
        echo '<p><a href="?no_service=' . $no_service . '" class="btn btn-primary">← Back to Preview</a></p>';
        echo '<script>setTimeout(function(){ window.print(); }, 1000);</script>';
        exit;
    }
} else {
    // Output HTML with download button
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
}
?>
