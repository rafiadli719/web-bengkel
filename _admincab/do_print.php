<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = isset($_SESSION['_cabang']) ? $_SESSION['_cabang'] : '';
include "../config/koneksi.php";

$no_do = isset($_GET['no_do']) ? trim($_GET['no_do']) : '';
if($no_do === ''){
    echo '<script>alert("No. DO tidak ditemukan"); window.close();</script>';
    exit;
}

// Get company/branch info
$qcab = mysqli_query($koneksi, "SELECT * FROM tblcabang WHERE kd_cabang='".mysqli_real_escape_string($koneksi,$kd_cabang)."'");
$cabang = mysqli_fetch_assoc($qcab);

// Load DO Header
$qh = mysqli_query($koneksi, "SELECT doh.*, s.namasupplier, s.alamat as alamat_supplier, s.kota as kota_supplier, s.telepon as telepon_supplier
                              FROM tbldelivery_order_header doh
                              LEFT JOIN tblsupplier s ON s.nosupplier=doh.no_supplier
                              WHERE doh.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'");
$h = mysqli_fetch_assoc($qh);
if(!$h){
    echo '<script>alert("DO tidak ditemukan"); window.close();</script>';
    exit;
}

// Load DO Detail
$qd = mysqli_query($koneksi, "SELECT d.*, i.namaitem, i.satuan
                              FROM tbldelivery_order_detail d
                              LEFT JOIN tblitem i ON i.noitem=d.no_item
                              WHERE d.no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                              ORDER BY d.nobaris, d.id ASC");
$items = [];
while($r = mysqli_fetch_assoc($qd)){ $items[] = $r; }

// Load tracking
$qt = mysqli_query($koneksi, "SELECT * FROM tbldo_tracking
                              WHERE no_do='".mysqli_real_escape_string($koneksi,$no_do)."'
                              ORDER BY tanggal_update ASC, id ASC");
$tracking = [];
while($r = mysqli_fetch_assoc($qt)){ $tracking[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Print DO - <?php echo htmlspecialchars($h['no_do']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .container { max-width: 210mm; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 11px; color: #666; }
        .doc-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; text-transform: uppercase; }
        .info-section { margin-bottom: 20px; }
        .info-row { display: flex; margin-bottom: 5px; }
        .info-label { width: 150px; font-weight: bold; }
        .info-value { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .signature-section { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 30%; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 60px; padding-top: 5px; }
        .tracking-section { margin-top: 20px; page-break-inside: avoid; }
        .tracking-item { padding: 10px; border: 1px solid #ddd; margin-bottom: 10px; background: #f9f9f9; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        .btn-print { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; margin-bottom: 10px; }
        .btn-print:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom:10px;">
        <button class="btn-print" onclick="window.print()">🖨️ Print</button>
        <button class="btn-print" style="background:#6c757d;" onclick="window.close()">❌ Close</button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo htmlspecialchars($cabang ? $cabang['nama_cabang'] : 'PT. FITMOTOR'); ?></h1>
            <p><?php echo htmlspecialchars($cabang ? $cabang['alamat'] : ''); ?></p>
            <p>Telp: <?php echo htmlspecialchars($cabang ? $cabang['telepon'] : ''); ?></p>
        </div>

        <!-- Document Title -->
        <div class="doc-title">DELIVERY ORDER (SURAT JALAN)</div>

        <!-- DO Info -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">No. DO:</div>
                <div class="info-value"><strong><?php echo htmlspecialchars($h['no_do']); ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">No. PO:</div>
                <div class="info-value"><?php echo htmlspecialchars($h['no_po']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal DO:</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($h['tanggal_do'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value"><strong><?php echo strtoupper($h['status_do']); ?></strong></div>
            </div>
        </div>

        <!-- Supplier Info -->
        <div class="info-section">
            <h3 style="margin-bottom:10px; border-bottom:1px solid #333;">Informasi Supplier</h3>
            <div class="info-row">
                <div class="info-label">Nama Supplier:</div>
                <div class="info-value"><strong><?php echo htmlspecialchars($h['namasupplier']); ?></strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Alamat:</div>
                <div class="info-value"><?php echo htmlspecialchars($h['alamat_supplier']); ?>, <?php echo htmlspecialchars($h['kota_supplier']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Telepon:</div>
                <div class="info-value"><?php echo htmlspecialchars($h['telepon_supplier']); ?></div>
            </div>
        </div>

        <!-- Shipping Info -->
        <div class="info-section">
            <h3 style="margin-bottom:10px; border-bottom:1px solid #333;">Informasi Pengiriman</h3>
            <?php if($h['tanggal_kirim']){ ?>
            <div class="info-row">
                <div class="info-label">Tanggal Kirim:</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($h['tanggal_kirim'])); ?></div>
            </div>
            <?php } ?>
            <?php if($h['tanggal_estimasi_tiba']){ ?>
            <div class="info-row">
                <div class="info-label">Estimasi Tiba:</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($h['tanggal_estimasi_tiba'])); ?></div>
            </div>
            <?php } ?>
            <?php if($h['kurir']){ ?>
            <div class="info-row">
                <div class="info-label">Kurir:</div>
                <div class="info-value"><?php echo htmlspecialchars($h['kurir']); ?></div>
            </div>
            <?php } ?>
            <?php if($h['no_surat_jalan']){ ?>
            <div class="info-row">
                <div class="info-label">No. Surat Jalan:</div>
                <div class="info-value"><?php echo htmlspecialchars($h['no_surat_jalan']); ?></div>
            </div>
            <?php } ?>
            <div class="info-row">
                <div class="info-label">Alamat Kirim:</div>
                <div class="info-value"><?php echo htmlspecialchars($h['alamat_kirim']); ?></div>
            </div>
        </div>

        <!-- Items Table -->
        <h3 style="margin-top:20px; margin-bottom:10px; border-bottom:1px solid #333;">Daftar Barang</h3>
        <table>
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:15%">Kode Item</th>
                    <th>Nama Item</th>
                    <th class="text-right" style="width:10%">Qty PO</th>
                    <th class="text-right" style="width:10%">Qty Kirim</th>
                    <th class="text-right" style="width:10%">Qty Terima</th>
                    <th class="text-center" style="width:8%">Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i=0;
                $total_po = 0;
                $total_kirim = 0;
                $total_terima = 0;
                foreach($items as $item){
                    $i++;
                    $total_po += (int)$item['qty_po'];
                    $total_kirim += (int)$item['qty_kirim'];
                    $total_terima += (int)$item['qty_terima'];
                ?>
                <tr>
                    <td class="text-center"><?php echo $i; ?></td>
                    <td><?php echo htmlspecialchars($item['no_item']); ?></td>
                    <td><?php echo htmlspecialchars($item['namaitem']); ?></td>
                    <td class="text-right"><?php echo number_format((int)$item['qty_po'], 0, ',', '.'); ?></td>
                    <td class="text-right"><strong><?php echo number_format((int)$item['qty_kirim'], 0, ',', '.'); ?></strong></td>
                    <td class="text-right"><?php echo number_format((int)$item['qty_terima'], 0, ',', '.'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['satuan']); ?></td>
                </tr>
                <?php } ?>
                <tr style="font-weight:bold; background-color:#e9ecef;">
                    <td colspan="3" class="text-right">TOTAL:</td>
                    <td class="text-right"><?php echo number_format($total_po, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($total_kirim, 0, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($total_terima, 0, ',', '.'); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Tracking History -->
        <?php if(count($tracking) > 0){ ?>
        <div class="tracking-section">
            <h3 style="margin-bottom:10px; border-bottom:1px solid #333;">Riwayat Tracking</h3>
            <?php foreach($tracking as $t){ ?>
            <div class="tracking-item">
                <div style="font-weight:bold; margin-bottom:5px;">
                    <?php echo strtoupper($t['status']); ?>
                    <span style="float:right; font-weight:normal; font-size:11px;">
                        <?php echo date('d/m/Y H:i', strtotime($t['tanggal_update'])); ?>
                    </span>
                </div>
                <?php if($t['lokasi']){ ?>
                <div style="margin-bottom:3px;"><strong>Lokasi:</strong> <?php echo htmlspecialchars($t['lokasi']); ?></div>
                <?php } ?>
                <?php if($t['keterangan']){ ?>
                <div><?php echo htmlspecialchars($t['keterangan']); ?></div>
                <?php } ?>
                <div style="font-size:10px; color:#666; margin-top:3px;">
                    oleh: <?php echo htmlspecialchars($t['updated_by']); ?>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php } ?>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div>Dikirim Oleh,</div>
                <div class="signature-line"><?php echo htmlspecialchars($h['kurir'] ?: '(.................)'); ?></div>
            </div>
            <div class="signature-box">
                <div>Diterima Oleh,</div>
                <div class="signature-line">(.................)</div>
            </div>
            <div class="signature-box">
                <div>Mengetahui,</div>
                <div class="signature-line">(.................)</div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top:30px; font-size:10px; color:#666; text-align:center;">
            <p>Dokumen ini dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Halaman ini adalah salinan yang sah dari sistem <?php echo htmlspecialchars($cabang ? $cabang['nama_cabang'] : 'FITMOTOR'); ?></p>
        </div>
    </div>
</body>
</html>
