<?php
/**
 * GENERATE PDF INVOICE
 * File ini generate PDF dari invoice servis untuk dikirim ke WhatsApp
 */

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
            pg.grup as grup_pelanggan,
            k.pemilik,
            k.jenis,
            k.tipe,
            k.warna,
            k.no_rangka,
            k.no_mesin,
            pm.merek
          FROM tblservice s
          LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
          LEFT JOIN tblpelanggangrup pg ON p.kgrup = pg.kgrup
          LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
          LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
          WHERE s.no_service='$no_service'";

$result = mysqli_query($koneksi, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    die('Data service tidak ditemukan');
}

$data = mysqli_fetch_assoc($result);

// Get jasa
$query_jasa = "SELECT 
                sj.*,
                COALESCE(wh.nama_wo, ij.namaitem, 'Item Tidak Diketahui') as nama_item
               FROM tblservis_jasa sj
               LEFT JOIN tbworkorderheader wh ON sj.no_item = wh.kode_wo
               LEFT JOIN tblitem_jasa ij ON sj.no_item = ij.noitem
               WHERE sj.no_service='$no_service'
               ORDER BY sj.nobaris";
$result_jasa = mysqli_query($koneksi, $query_jasa);

// Get barang
$query_barang = "SELECT 
                  sb.*,
                  COALESCE(vi.namaitem, 'Item Tidak Diketahui') as nama_item,
                  COALESCE(vi.satuan, 'PCS') as satuan
                 FROM tblservis_barang sb
                 LEFT JOIN view_cari_item vi ON sb.no_item = vi.noitem
                 WHERE sb.no_service='$no_service'
                 ORDER BY sb.nobaris";
$result_barang = mysqli_query($koneksi, $query_barang);

// Data Perusahaan (sumber sama dengan nota lain, bukan hardcode per-cabang)
$cari_perusahaan = mysqli_query($koneksi, "SELECT * FROM tbsetting");
$tm_perusahaan = mysqli_fetch_array($cari_perusahaan);
$nama_perusahaan = $tm_perusahaan['nama_perusahaan'] ?? 'FIT MOTOR';
$alamat_perusahaan = $tm_perusahaan['alamat'] ?? '';

// Generate HTML untuk PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo $no_service; ?></title>
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
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 3px 5px;
            vertical-align: top;
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
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><?php echo htmlspecialchars($nama_perusahaan); ?></h2>
        <p><?php echo htmlspecialchars($alamat_perusahaan); ?></p>
        <h3>INVOICE SERVIS</h3>
    </div>
    
    <table class="info-table">
        <tr>
            <td width="15%"><strong>No. Service</strong></td>
            <td width="2%">:</td>
            <td width="33%"><?php echo $data['no_service']; ?></td>
            <td width="15%"><strong>Status</strong></td>
            <td width="2%">:</td>
            <td width="33%"><?php echo strtoupper($data['status_servis']); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>:</td>
            <td><?php echo $data['tanggal_format']; ?></td>
            <td><strong>Grup Pelanggan</strong></td>
            <td>:</td>
            <td><?php echo $data['grup_pelanggan'] ?: '-'; ?></td>
        </tr>
        <tr>
            <td><strong>Jam</strong></td>
            <td>:</td>
            <td><?php echo $data['jam']; ?></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    
    <h4>Data Pelanggan</h4>
    <table class="info-table">
        <tr>
            <td width="15%"><strong>Nama</strong></td>
            <td width="2%">:</td>
            <td><?php echo $data['namapelanggan'] ?: $data['pemilik']; ?></td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>:</td>
            <td><?php echo $data['alamat_pelanggan']; ?></td>
        </tr>
        <tr>
            <td><strong>Telepon</strong></td>
            <td>:</td>
            <td><?php echo $data['telephone']; ?></td>
        </tr>
    </table>
    
    <h4>Data Kendaraan</h4>
    <table class="info-table">
        <tr>
            <td width="15%"><strong>No. Polisi</strong></td>
            <td width="2%">:</td>
            <td width="33%"><?php echo $data['no_polisi']; ?></td>
            <td width="15%"><strong>Merek</strong></td>
            <td width="2%">:</td>
            <td width="33%"><?php echo $data['merek']; ?></td>
        </tr>
        <tr>
            <td><strong>Jenis</strong></td>
            <td>:</td>
            <td><?php echo $data['jenis']; ?></td>
            <td><strong>Tipe</strong></td>
            <td>:</td>
            <td><?php echo $data['tipe']; ?></td>
        </tr>
        <tr>
            <td><strong>Warna</strong></td>
            <td>:</td>
            <td><?php echo $data['warna']; ?></td>
            <td><strong>KM Saat Ini</strong></td>
            <td>:</td>
            <td><?php echo number_format($data['km_skr'], 0, ',', '.'); ?> km</td>
        </tr>
    </table>
    
    <h4>Detail Jasa Service</h4>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="55%">Nama Jasa</th>
                <th width="15%">Waktu (Menit)</th>
                <th width="12%">Harga</th>
                <th width="8%">Diskon</th>
                <th width="15%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $subtotal_jasa = 0;
            while($jasa = mysqli_fetch_assoc($result_jasa)): 
                $subtotal_jasa += $jasa['total'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $jasa['nama_item']; ?></td>
                <td class="text-center"><?php echo $jasa['waktu']; ?> mnt</td>
                <td class="text-right">Rp <?php echo number_format($jasa['harga'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?php echo number_format($jasa['diskon'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?php echo number_format($jasa['total'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="5" class="text-right"><strong>Subtotal Jasa</strong></td>
                <td class="text-right"><strong>Rp <?php echo number_format($subtotal_jasa, 0, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
    </table>
    
    <h4>Detail Barang/Sparepart</h4>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Nama Barang</th>
                <th width="8%">Qty</th>
                <th width="10%">Satuan</th>
                <th width="12%">Harga</th>
                <th width="8%">Diskon</th>
                <th width="12%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $subtotal_barang = 0;
            while($barang = mysqli_fetch_assoc($result_barang)): 
                $subtotal_barang += $barang['total'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $barang['nama_item']; ?></td>
                <td class="text-center"><?php echo $barang['qty']; ?></td>
                <td class="text-center"><?php echo $barang['satuan']; ?></td>
                <td class="text-right">Rp <?php echo number_format($barang['harga'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?php echo number_format($barang['diskon'], 0, ',', '.'); ?></td>
                <td class="text-right">Rp <?php echo number_format($barang['total'], 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="6" class="text-right"><strong>Subtotal Barang</strong></td>
                <td class="text-right"><strong>Rp <?php echo number_format($subtotal_barang, 0, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
    </table>
    
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td width="60%"></td>
            <td width="40%">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>Total</strong></td>
                        <td class="text-right">Rp <?php echo number_format($data['subtotal'] ?: 0, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Diskon</strong></td>
                        <td class="text-right">Rp <?php echo number_format($data['diskon_nom'] ?: 0, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>PPN (<?php echo $data['ppn_persen'] ?: 0; ?>%)</strong></td>
                        <td class="text-right">Rp <?php echo number_format($data['ppn_nom'] ?: 0, 0, ',', '.'); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL AKHIR</strong></td>
                        <td class="text-right"><strong>Rp <?php echo number_format($data['total_akhir'] ?: 0, 0, ',', '.'); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Bayar</strong></td>
                        <td class="text-right">Rp <?php echo number_format($data['bayar'] ?: 0, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kembali</strong></td>
                        <td class="text-right">Rp <?php echo number_format($data['kembali'] ?: 0, 0, ',', '.'); ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <p style="margin-top: 30px; font-size: 10px; font-style: italic;">
        * Invoice ini digenerate otomatis oleh sistem<br>
        * Terima kasih atas kepercayaan Anda menggunakan layanan kami
    </p>
</body>
</html>
<?php
$html = ob_get_clean();

// Render PDF beneran pakai Dompdf (sama seperti semua nota lain di app ini),
// bukan wkhtmltopdf.exe Windows yang gak ada di hosting produksi.
require_once("dompdf/autoload.inc.php");
use Dompdf\Dompdf;
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('Invoice-' . $no_service . '.pdf', array("Attachment" => 0));
?>
