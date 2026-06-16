<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    $format = $_GET['format'] ?? 'excel';
    $search = $_GET['search'] ?? '';
    
    // Set headers untuk download
    if($format == 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="work_order_export_' . date('Y-m-d') . '.xls"');
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="work_order_export_' . date('Y-m-d') . '.csv"');
    }
    
    // Start output
    if($format == 'excel') {
        echo '<table border="1">';
        echo '<tr style="background-color: #cccccc; font-weight: bold;">';
        echo '<td>No</td>';
        echo '<td>Kode Work Order</td>';
        echo '<td>Nama Work Order</td>';
        echo '<td>Keterangan</td>';
        echo '<td>Total Waktu (Menit)</td>';
        echo '<td>Total Harga</td>';
        echo '<td>Jumlah Detail Jasa</td>';
        echo '<td>Jumlah Detail Barang</td>';
        echo '<td>Detail Jasa</td>';
        echo '<td>Detail Barang</td>';
        echo '</tr>';
    } else {
        echo "No,Kode Work Order,Nama Work Order,Keterangan,Total Waktu (Menit),Total Harga,Jumlah Detail Jasa,Jumlah Detail Barang,Detail Jasa,Detail Barang\n";
    }
    
    // Query work order
    if($search != '') {
        $sql = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader 
                                      WHERE (kode_wo LIKE '%$search%' OR nama_wo LIKE '%$search%') 
                                      AND status='0'
                                      ORDER BY kode_wo ASC");
    } else {
        $sql = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader 
                                      WHERE status='0'
                                      ORDER BY kode_wo ASC");
    }
    
    $no = 0;
    while ($tampil = mysqli_fetch_array($sql)) {
        $no++;
        
        // Get detail jasa
        $jasa_query = mysqli_query($koneksi,"SELECT d.kode_barang, w.nama_wo, d.total 
                                           FROM tbworkorderdetail d
                                           LEFT JOIN tbworkorderheader w ON d.kode_barang = w.kode_wo
                                           WHERE d.kode_wo='{$tampil['kode_wo']}' AND d.tipe='1'
                                           ORDER BY d.id ASC");
        
        $detail_jasa = [];
        $count_jasa = 0;
        while($jasa = mysqli_fetch_array($jasa_query)) {
            $count_jasa++;
            $detail_jasa[] = $jasa['kode_barang'] . ' - ' . $jasa['nama_wo'] . ' (Rp ' . number_format($jasa['total'], 0, ',', '.') . ')';
        }
        
        // Get detail barang
        $barang_query = mysqli_query($koneksi,"SELECT d.kode_barang, d.jumlah, v.namaitem, d.total 
                                             FROM tbworkorderdetail d
                                             LEFT JOIN view_cari_item v ON d.kode_barang = v.noitem
                                             WHERE d.kode_wo='{$tampil['kode_wo']}' AND d.tipe='2'
                                             ORDER BY d.id ASC");
        
        $detail_barang = [];
        $count_barang = 0;
        while($barang = mysqli_fetch_array($barang_query)) {
            $count_barang++;
            $detail_barang[] = $barang['kode_barang'] . ' - ' . $barang['namaitem'] . ' (' . $barang['jumlah'] . ' pcs @ Rp ' . number_format($barang['total'], 0, ',', '.') . ')';
        }
        
        $jasa_text = implode('; ', $detail_jasa);
        $barang_text = implode('; ', $detail_barang);
        
        if($format == 'excel') {
            echo '<tr>';
            echo '<td>' . $no . '</td>';
            echo '<td>' . $tampil['kode_wo'] . '</td>';
            echo '<td>' . htmlspecialchars($tampil['nama_wo']) . '</td>';
            echo '<td>' . htmlspecialchars($tampil['keterangan']) . '</td>';
            echo '<td>' . $tampil['waktu'] . '</td>';
            echo '<td>' . number_format($tampil['harga'], 0, ',', '.') . '</td>';
            echo '<td>' . $count_jasa . '</td>';
            echo '<td>' . $count_barang . '</td>';
            echo '<td>' . htmlspecialchars($jasa_text) . '</td>';
            echo '<td>' . htmlspecialchars($barang_text) . '</td>';
            echo '</tr>';
        } else {
            echo '"' . $no . '",';
            echo '"' . $tampil['kode_wo'] . '",';
            echo '"' . str_replace('"', '""', $tampil['nama_wo']) . '",';
            echo '"' . str_replace('"', '""', $tampil['keterangan']) . '",';
            echo '"' . $tampil['waktu'] . '",';
            echo '"' . $tampil['harga'] . '",';
            echo '"' . $count_jasa . '",';
            echo '"' . $count_barang . '",';
            echo '"' . str_replace('"', '""', $jasa_text) . '",';
            echo '"' . str_replace('"', '""', $barang_text) . '"';
            echo "\n";
        }
    }
    
    if($format == 'excel') {
        echo '</table>';
    }
}
?>