<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
$no_service = $_GET['snoserv'] ?? '';

if (empty($no_service)) {
    echo "<script>alert('Nomor service tidak ditemukan!'); history.back();</script>";
    exit;
}

require_once "../config/koneksi.php";

// Get service data
$stmt = mysqli_prepare($koneksi, "SELECT s.*, p.namapelanggan, p.alamat, p.telephone,
                                 v.merek, v.tipe, v.warna, v.no_rangka, v.no_mesin,
                                 c.nama_cabang, c.alamat as alamat_cabang, c.telepon as telepon_cabang
                                 FROM tblservice s
                                 LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                                 LEFT JOIN view_cari_kendaraan v ON s.no_polisi = v.nopolisi
                                 LEFT JOIN tbcabang c ON s.kd_cabang = c.kode_cabang
                                 WHERE s.no_service = ?");
mysqli_stmt_bind_param($stmt, "s", $no_service);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$service_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$service_data) {
    echo "<script>alert('Data service tidak ditemukan!'); history.back();</script>";
    exit;
}

// Get service items (jasa)
$jasa_query = mysqli_prepare($koneksi, "SELECT sj.*, woh.nama_wo 
                                       FROM tblservis_jasa sj
                                       LEFT JOIN tbworkorderheader woh ON sj.no_item = woh.kode_wo
                                       WHERE sj.no_service = ?");
mysqli_stmt_bind_param($jasa_query, "s", $no_service);
mysqli_stmt_execute($jasa_query);
$jasa_result = mysqli_stmt_get_result($jasa_query);

// Get service parts (barang)
$barang_query = mysqli_prepare($koneksi, "SELECT sb.*, vci.namaitem 
                                         FROM tblservis_barang sb
                                         LEFT JOIN view_cari_item vci ON sb.no_item = vci.noitem
                                         WHERE sb.no_service = ?");
mysqli_stmt_bind_param($barang_query, "s", $no_service);
mysqli_stmt_execute($barang_query);
$barang_result = mysqli_stmt_get_result($barang_query);

// Set headers for CSV download
$filename = "Invoice_Service_Jemput_" . $no_service . "_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (to support Indonesian characters in Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Invoice Header
fputcsv($output, ['=== INVOICE SERVICE JEMPUT ANTAR ==='], ',');
fputcsv($output, [''], ','); // Empty line

// Company Information
fputcsv($output, ['INFORMASI BENGKEL'], ',');
fputcsv($output, ['Nama Bengkel:', $service_data['nama_cabang'] ?? 'Bengkel Motor ABC'], ',');
fputcsv($output, ['Alamat:', $service_data['alamat_cabang'] ?? 'Jl. Raya No. 123'], ',');
fputcsv($output, ['Telepon:', $service_data['telepon_cabang'] ?? '021-1234-5678'], ',');
fputcsv($output, ['Email:', 'info@bengkel.com'], ',');
fputcsv($output, [''], ','); // Empty line

// Service Information
fputcsv($output, ['INFORMASI SERVICE'], ',');
fputcsv($output, ['No. Service:', $no_service], ',');
fputcsv($output, ['Tanggal Service:', date('d/m/Y', strtotime($service_data['tanggal']))], ',');
fputcsv($output, ['Jam Service:', $service_data['jam'] ?? ''], ',');
fputcsv($output, ['Jenis Service:', 'JEMPUT ANTAR'], ',');
fputcsv($output, ['Status Service:', ($service_data['status'] == '2' ? 'SELESAI' : 'DALAM PROSES')], ',');
fputcsv($output, [''], ','); // Empty line

// Customer Information
fputcsv($output, ['INFORMASI PELANGGAN'], ',');
fputcsv($output, ['Nama Pelanggan:', $service_data['namapelanggan'] ?? ''], ',');
fputcsv($output, ['Kode Pelanggan:', $service_data['no_pelanggan'] ?? ''], ',');
fputcsv($output, ['Alamat:', $service_data['alamat'] ?? ''], ',');
fputcsv($output, ['Telepon:', $service_data['telephone'] ?? ''], ',');
fputcsv($output, [''], ','); // Empty line

// Vehicle Information
fputcsv($output, ['INFORMASI KENDARAAN'], ',');
fputcsv($output, ['No. Polisi:', $service_data['no_polisi'] ?? ''], ',');
fputcsv($output, ['Merek:', $service_data['merek'] ?? ''], ',');
fputcsv($output, ['Tipe:', $service_data['tipe'] ?? ''], ',');
fputcsv($output, ['Warna:', $service_data['warna'] ?? ''], ',');
fputcsv($output, ['No. Rangka:', $service_data['no_rangka'] ?? ''], ',');
fputcsv($output, ['No. Mesin:', $service_data['no_mesin'] ?? ''], ',');
fputcsv($output, [''], ','); // Empty line

// Service Details - Jasa
$total_jasa = 0;
$total_waktu = 0;

if (mysqli_num_rows($jasa_result) > 0) {
    fputcsv($output, ['=== DETAIL JASA SERVICE ==='], ',');
    fputcsv($output, ['No', 'Kode Item', 'Nama Jasa', 'Waktu (Menit)', 'Harga Satuan', 'Potongan (%)', 'Total Harga'], ',');
    
    $no_jasa = 1;
    mysqli_data_seek($jasa_result, 0); // Reset pointer
    while ($jasa = mysqli_fetch_assoc($jasa_result)) {
        $subtotal_jasa = $jasa['total'] ?? 0;
        $waktu_jasa = $jasa['waktu'] ?? 0;
        
        fputcsv($output, [
            $no_jasa,
            $jasa['no_item'] ?? '',
            $jasa['nama_wo'] ?? 'Jasa Service',
            $waktu_jasa,
            'Rp ' . number_format($jasa['harga'] ?? 0, 0, ',', '.'),
            ($jasa['potongan'] ?? 0) . '%',
            'Rp ' . number_format($subtotal_jasa, 0, ',', '.')
        ], ',');
        
        $total_jasa += $subtotal_jasa;
        $total_waktu += $waktu_jasa;
        $no_jasa++;
    }
    
    fputcsv($output, ['', '', '', '', '', 'SUBTOTAL JASA:', 'Rp ' . number_format($total_jasa, 0, ',', '.')], ',');
    fputcsv($output, ['', '', '', '', '', 'TOTAL WAKTU:', $total_waktu . ' menit'], ',');
    fputcsv($output, [''], ','); // Empty line
}

// Service Details - Barang/Sparepart
$total_barang = 0;

if (mysqli_num_rows($barang_result) > 0) {
    fputcsv($output, ['=== DETAIL BARANG/SPAREPART ==='], ',');
    fputcsv($output, ['No', 'Kode Item', 'Nama Barang', 'Qty', 'Harga Satuan', 'Potongan (%)', 'Total Harga'], ',');
    
    $no_barang = 1;
    mysqli_data_seek($barang_result, 0); // Reset pointer
    while ($barang = mysqli_fetch_assoc($barang_result)) {
        $subtotal_barang = $barang['total'] ?? 0;
        
        fputcsv($output, [
            $no_barang,
            $barang['no_item'] ?? '',
            $barang['namaitem'] ?? 'Sparepart',
            $barang['quantity'] ?? 0,
            'Rp ' . number_format($barang['harga_jual'] ?? 0, 0, ',', '.'),
            'Rp ' . number_format($barang['harga_jual'] ?? 0, 0, ',', '.'),
            ($barang['potongan'] ?? 0) . '%',
            'Rp ' . number_format($subtotal_barang, 0, ',', '.')
        ], ',');
        
        $total_barang += $subtotal_barang;
        $no_barang++;
    }
    
    fputcsv($output, ['', '', '', '', '', 'SUBTOTAL BARANG:', 'Rp ' . number_format($total_barang, 0, ',', '.')], ',');
    fputcsv($output, [''], ','); // Empty line
}

// Financial Summary
$subtotal = $total_jasa + $total_barang;
$diskon_persen = $service_data['diskon_persen'] ?? 0;
$diskon_nominal = $service_data['diskon_nom'] ?? 0;
$ppn_persen = $service_data['ppn_persen'] ?? 0;
$ppn_nominal = $service_data['ppn_nom'] ?? 0;
$total_grand = $service_data['total_grand'] ?? $subtotal;

fputcsv($output, ['=== RINGKASAN PEMBAYARAN ==='], ',');
fputcsv($output, ['Subtotal Jasa:', 'Rp ' . number_format($total_jasa, 0, ',', '.')], ',');
fputcsv($output, ['Subtotal Barang:', 'Rp ' . number_format($total_barang, 0, ',', '.')], ',');
fputcsv($output, ['SUBTOTAL:', 'Rp ' . number_format($subtotal, 0, ',', '.')], ',');

if ($diskon_persen > 0 || $diskon_nominal > 0) {
    fputcsv($output, ['Diskon (' . $diskon_persen . '%):', '(Rp ' . number_format($diskon_nominal, 0, ',', '.') . ')'], ',');
}

if ($ppn_persen > 0 || $ppn_nominal > 0) {
    fputcsv($output, ['PPN (' . $ppn_persen . '%):', 'Rp ' . number_format($ppn_nominal, 0, ',', '.')], ',');
}

fputcsv($output, ['TOTAL GRAND:', 'Rp ' . number_format($total_grand, 0, ',', '.')], ',');
fputcsv($output, [''], ','); // Empty line

// Payment Information
fputcsv($output, ['=== INFORMASI PEMBAYARAN ==='], ',');
fputcsv($output, ['Status Pembayaran:', ($service_data['status'] == '2' ? 'LUNAS' : 'BELUM LUNAS')], ',');
fputcsv($output, ['Metode Pembayaran:', 'TUNAI'], ',');
fputcsv($output, ['Tanggal Jatuh Tempo:', date('d/m/Y', strtotime($service_data['tanggal'] . ' +7 days'))], ',');
fputcsv($output, [''], ','); // Empty line

// Additional Service Information
if (!empty($service_data['keterangan'])) {
    fputcsv($output, ['=== KETERANGAN TAMBAHAN ==='], ',');
    // Split long text into multiple lines for better CSV readability
    $keterangan_lines = explode("\n", $service_data['keterangan']);
    foreach ($keterangan_lines as $line) {
        fputcsv($output, [trim($line)], ',');
    }
    fputcsv($output, [''], ','); // Empty line
}

// Keluhan Pelanggan (if any)
$keluhan_query = mysqli_prepare($koneksi, "SELECT keluhan, status_pengerjaan, 
                                          DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as tanggal_keluhan
                                          FROM tbservis_keluhan_status 
                                          WHERE no_service = ? 
                                          ORDER BY created_at DESC");
if ($keluhan_query) {
    mysqli_stmt_bind_param($keluhan_query, "s", $no_service);
    mysqli_stmt_execute($keluhan_query);
    $keluhan_result = mysqli_stmt_get_result($keluhan_query);
    
    if (mysqli_num_rows($keluhan_result) > 0) {
        fputcsv($output, ['=== KELUHAN PELANGGAN ==='], ',');
        fputcsv($output, ['No', 'Tanggal', 'Keluhan', 'Status Pengerjaan'], ',');
        
        $no_keluhan = 1;
        while ($keluhan = mysqli_fetch_assoc($keluhan_result)) {
            fputcsv($output, [
                $no_keluhan,
                $keluhan['tanggal_keluhan'] ?? date('d/m/Y'),
                $keluhan['keluhan'] ?? '',
                strtoupper($keluhan['status_pengerjaan'] ?? 'PENDING')
            ], ',');
            $no_keluhan++;
        }
        fputcsv($output, [''], ','); // Empty line
    }
    mysqli_stmt_close($keluhan_query);
}

// Work Orders (if any)
$wo_query = mysqli_prepare($koneksi, "SELECT wo.kode_wo, woh.nama_wo, wo.status_pengerjaan,
                                     wo.keterangan_tidak_selesai
                                     FROM tbservis_workorder wo
                                     LEFT JOIN tbworkorderheader woh ON wo.kode_wo = woh.kode_wo
                                     WHERE wo.no_service = ?");
if ($wo_query) {
    mysqli_stmt_bind_param($wo_query, "s", $no_service);
    mysqli_stmt_execute($wo_query);
    $wo_result = mysqli_stmt_get_result($wo_query);
    
    if (mysqli_num_rows($wo_result) > 0) {
        fputcsv($output, ['=== WORK ORDER ==='], ',');
        fputcsv($output, ['No', 'Kode WO', 'Nama Work Order', 'Status', 'Keterangan'], ',');
        
        $no_wo = 1;
        while ($wo = mysqli_fetch_assoc($wo_result)) {
            fputcsv($output, [
                $no_wo,
                $wo['kode_wo'] ?? '',
                $wo['nama_wo'] ?? '',
                strtoupper($wo['status_pengerjaan'] ?? 'PENDING'),
                $wo['keterangan_tidak_selesai'] ?? ''
            ], ',');
            $no_wo++;
        }
        fputcsv($output, [''], ','); // Empty line
    }
    mysqli_stmt_close($wo_query);
}

// Service History (Previous services for this vehicle)
$history_query = mysqli_prepare($koneksi, "SELECT no_service, DATE_FORMAT(tanggal, '%d/%m/%Y') as tgl_service, 
                                          total_grand, status
                                          FROM tblservice 
                                          WHERE no_polisi = ? AND no_service != ?
                                          ORDER BY tanggal DESC 
                                          LIMIT 5");
if ($history_query) {
    mysqli_stmt_bind_param($history_query, "ss", $service_data['no_polisi'], $no_service);
    mysqli_stmt_execute($history_query);
    $history_result = mysqli_stmt_get_result($history_query);
    
    if (mysqli_num_rows($history_result) > 0) {
        fputcsv($output, ['=== RIWAYAT SERVICE KENDARAAN (5 TERAKHIR) ==='], ',');
        fputcsv($output, ['No', 'No. Service', 'Tanggal', 'Total', 'Status'], ',');
        
        $no_history = 1;
        while ($history = mysqli_fetch_assoc($history_result)) {
            $status_history = '';
            switch($history['status']) {
                case '1': $status_history = 'AKTIF'; break;
                case '2': $status_history = 'SELESAI'; break;
                case '3': $status_history = 'FINISH'; break;
                default: $status_history = 'DRAFT'; break;
            }
            
            fputcsv($output, [
                $no_history,
                $history['no_service'],
                $history['tgl_service'],
                'Rp ' . number_format($history['total_grand'] ?? 0, 0, ',', '.'),
                $status_history
            ], ',');
            $no_history++;
        }
        fputcsv($output, [''], ','); // Empty line
    }
    mysqli_stmt_close($history_query);
}

// Technical Details
fputcsv($output, ['=== DETAIL TEKNIS ==='], ',');
fputcsv($output, ['Total Waktu Pengerjaan:', ($service_data['total_waktu'] ?? $total_waktu) . ' menit'], ',');
fputcsv($output, ['Jumlah Item Jasa:', mysqli_num_rows($jasa_result)], ',');
fputcsv($output, ['Jumlah Item Barang:', mysqli_num_rows($barang_result)], ',');
fputcsv($output, ['Cabang Service:', $service_data['nama_cabang'] ?? ''], ',');
fputcsv($output, [''], ','); // Empty line

// Terms & Conditions
fputcsv($output, ['=== SYARAT & KETENTUAN ==='], ',');
fputcsv($output, ['1. Garansi service berlaku 30 hari dari tanggal selesai'], ',');
fputcsv($output, ['2. Garansi sparepart sesuai dengan garansi pabrik'], ',');
fputcsv($output, ['3. Keluhan service harus dilaporkan maksimal 3 hari'], ',');
fputcsv($output, ['4. Motor yang tidak diambil dalam 30 hari akan dikenakan biaya penitipan'], ',');
fputcsv($output, ['5. Kehilangan kunci motor menjadi tanggung jawab pelanggan'], ',');
fputcsv($output, [''], ','); // Empty line

// Contact Information
fputcsv($output, ['=== KONTAK BENGKEL ==='], ',');
fputcsv($output, ['Telepon:', $service_data['telepon_cabang'] ?? '021-1234-5678'], ',');
fputcsv($output, ['WhatsApp:', '0812-3456-7890'], ',');
fputcsv($output, ['Email:', 'info@bengkel.com'], ',');
fputcsv($output, ['Website:', 'www.bengkel.com'], ',');
fputcsv($output, [''], ','); // Empty line

// Footer Information
fputcsv($output, ['=== INFORMASI DOKUMEN ==='], ',');
fputcsv($output, ['Tanggal Cetak:', date('d F Y, H:i:s') . ' WIB'], ',');
fputcsv($output, ['Dicetak Oleh:', $_SESSION['_nama'] ?? 'System'], ',');
fputcsv($output, ['Format File:', 'CSV (Comma Separated Values)'], ',');
fputcsv($output, ['Versi Sistem:', '2.0'], ',');
fputcsv($output, [''], ','); // Empty line

// Final Note
fputcsv($output, ['=== TERIMA KASIH ==='], ',');
fputcsv($output, ['Terima kasih atas kepercayaan Anda menggunakan layanan kami.'], ',');
fputcsv($output, ['Kepuasan pelanggan adalah prioritas utama kami.'], ',');
fputcsv($output, ['Hubungi kami jika ada pertanyaan atau keluhan.'], ',');
fputcsv($output, [''], ','); // Empty line
fputcsv($output, ['*** END OF INVOICE ***'], ',');

// Close queries
mysqli_stmt_close($jasa_query);
mysqli_stmt_close($barang_query);

// Close output stream
fclose($output);
exit;
?>