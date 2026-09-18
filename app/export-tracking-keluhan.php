<?php
// File: export-tracking-keluhan.php
// Export Excel untuk report-tracking-keluhan.php (kolom & filter disamain
// persis ke halaman report-nya).
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

$tgl_dari = isset($_GET['tgl_dari']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_dari']) : date('Y-m-01');
$tgl_sampai = isset($_GET['tgl_sampai']) ? mysqli_real_escape_string($koneksi, $_GET['tgl_sampai']) : date('Y-m-d');
$filter_kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$filter_prioritas = isset($_GET['prioritas']) ? mysqli_real_escape_string($koneksi, $_GET['prioritas']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$filter_cabang = isset($_GET['cabang']) ? mysqli_real_escape_string($koneksi, $_GET['cabang']) : $kd_cabang;

$where_conditions = ["DATE(s.tanggal) BETWEEN '$tgl_dari' AND '$tgl_sampai'"];
if(!empty($filter_kategori)) { $where_conditions[] = "mk.kategori = '$filter_kategori'"; }
if(!empty($filter_prioritas)) { $where_conditions[] = "mk.tingkat_prioritas = '$filter_prioritas'"; }
if(!empty($filter_status)) { $where_conditions[] = "k.status_pengerjaan = '$filter_status'"; }
if(!empty($filter_cabang)) { $where_conditions[] = "s.kd_cabang = '$filter_cabang'"; }
$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$status_label = [
    'datang' => 'Datang',
    'diproses' => 'Diproses',
    'selesai' => 'Selesai',
    'tidak_selesai' => 'Tidak Selesai',
];

$sql = mysqli_query($koneksi,"SELECT
                             s.no_service,
                             DATE(s.tanggal) as tanggal_service,
                             s.no_pelanggan,
                             p.namapelanggan,
                             k.keluhan,
                             k.status_pengerjaan,
                             mk.kode_keluhan,
                             mk.kategori,
                             mk.tingkat_prioritas,
                             mk.estimasi_waktu,
                             (SELECT COUNT(*) FROM tbservis_keluhan_tracking kt WHERE kt.keluhan_id = k.id) as total_proses,
                             (SELECT COUNT(*) FROM tbservis_keluhan_tracking kt WHERE kt.keluhan_id = k.id AND kt.status_proses = 'selesai') as proses_selesai
                             FROM tblservice s
                             JOIN tbservis_keluhan_status k ON s.no_service = k.no_service
                             LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                             LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                             $where_clause
                             ORDER BY s.tanggal DESC, s.no_service DESC");

$nama_file = "Tracking_Keluhan_" . date('Ymd_His') . ".xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$nama_file");
?>
<table border="1">
    <tr>
        <th>No</th>
        <th>No Service</th>
        <th>Tanggal</th>
        <th>Pelanggan</th>
        <th>No Pelanggan</th>
        <th>Keluhan</th>
        <th>Kode Keluhan</th>
        <th>Kategori</th>
        <th>Prioritas</th>
        <th>Status</th>
        <th>Progress</th>
        <th>Estimasi (menit)</th>
    </tr>
    <?php $no = 1; while ($data = mysqli_fetch_array($sql)): ?>
    <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo $data['no_service']; ?></td>
        <td><?php echo date('d/m/Y', strtotime($data['tanggal_service'])); ?></td>
        <td><?php echo $data['namapelanggan'] ?? ''; ?></td>
        <td><?php echo $data['no_pelanggan']; ?></td>
        <td><?php echo $data['keluhan']; ?></td>
        <td><?php echo $data['kode_keluhan'] ?? ''; ?></td>
        <td><?php echo $data['kategori'] ?? ''; ?></td>
        <td><?php echo $data['tingkat_prioritas'] ? ucfirst($data['tingkat_prioritas']) : ''; ?></td>
        <td><?php echo $status_label[$data['status_pengerjaan']] ?? $data['status_pengerjaan']; ?></td>
        <td><?php echo $data['total_proses'] > 0 ? $data['proses_selesai'] . '/' . $data['total_proses'] : 'Manual'; ?></td>
        <td><?php echo $data['estimasi_waktu'] ?? ''; ?></td>
    </tr>
    <?php endwhile; ?>
</table>
