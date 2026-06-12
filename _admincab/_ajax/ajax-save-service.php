<?php
session_start();
include "../../config/koneksi.php";

header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

try {
    // Get form data
    $no_service = $_POST['no_service'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $jam = $_POST['jam'] ?? '';
    $kode_pelanggan = $_POST['kode_pelanggan'] ?? '';
    $no_polisi = $_POST['no_polisi'] ?? '';
    $status_servis = $_POST['status_servis'] ?? 'datang';
    $keluhan = $_POST['keluhan'] ?? '';
    $catatan = $_POST['catatan'] ?? '';
    
    // Antrian data
    $no_antrian = $_POST['no_antrian'] ?? '';
    $prioritas_antrian = $_POST['prioritas_antrian'] ?? 'normal';
    $estimasi_waktu = $_POST['estimasi_waktu'] ?? 0;
    $catatan_antrian = $_POST['catatan_antrian'] ?? '';
    
    // Work order data
    $no_workorder = $_POST['no_workorder'] ?? '';
    $tanggal_wo = $_POST['tanggal_wo'] ?? '';
    $estimasi_selesai = $_POST['estimasi_selesai'] ?? '';
    $prioritas_wo = $_POST['prioritas_wo'] ?? 'normal';
    $deskripsi_pekerjaan = $_POST['deskripsi_pekerjaan'] ?? '';
    
    // Mechanic assignments
    $kepala_mekanik1 = $_POST['kepala_mekanik1'] ?? '';
    $kepala_mekanik2 = $_POST['kepala_mekanik2'] ?? '';
    $admin1 = $_POST['admin1'] ?? '';
    $admin2 = $_POST['admin2'] ?? '';
    $mekanik1 = $_POST['mekanik1'] ?? '';
    $mekanik2 = $_POST['mekanik2'] ?? '';
    $mekanik3 = $_POST['mekanik3'] ?? '';
    $mekanik4 = $_POST['mekanik4'] ?? '';
    
    // Items data
    $item_barang = json_decode($_POST['item_barang'] ?? '[]', true);
    $item_jasa = json_decode($_POST['item_jasa'] ?? '[]', true);
    
    // Validate required fields
    if(empty($kode_pelanggan) || empty($no_polisi)) {
        echo json_encode(array('success' => false, 'message' => 'Data pelanggan dan kendaraan harus diisi'));
        exit;
    }
    
    // Convert date format
    $tanggal_db = date('Y-m-d', strtotime(str_replace('/', '-', $tanggal)));
    
    // Begin transaction
    mysqli_begin_transaction($koneksi);
    
    try {
        // Generate service number if not exists
        if(empty($no_service)) {
            $no_service = 'SERV' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
        
        // Save to tblservice
        $query_service = "INSERT INTO tblservice (no_service, tanggal, jam, no_pelanggan, no_polisi, status_servis, user_input, tanggal_input) 
                         VALUES ('$no_service', '$tanggal_db', '$jam', '$kode_pelanggan', '$no_polisi', '$status_servis', '$_SESSION[_iduser]', NOW())
                         ON DUPLICATE KEY UPDATE 
                         tanggal = '$tanggal_db', jam = '$jam', no_pelanggan = '$kode_pelanggan', 
                         no_polisi = '$no_polisi', status_servis = '$status_servis', user_update = '$_SESSION[_iduser]', tanggal_update = NOW()";
        
        if(!mysqli_query($koneksi, $query_service)) {
            throw new Exception('Gagal menyimpan data service: ' . mysqli_error($koneksi));
        }
        
        // Save antrian data
        if(!empty($no_antrian)) {
            $query_antrian = "INSERT INTO tb_antrian_servis (no_antrian, no_service, tanggal, jam_ambil, status_antrian, prioritas, estimasi_waktu, catatan) 
                             VALUES ('$no_antrian', '$no_service', '$tanggal_db', '$jam', 'menunggu', '$prioritas_antrian', '$estimasi_waktu', '$catatan_antrian')
                             ON DUPLICATE KEY UPDATE 
                             status_antrian = 'menunggu', prioritas = '$prioritas_antrian', 
                             estimasi_waktu = '$estimasi_waktu', catatan = '$catatan_antrian'";
            
            if(!mysqli_query($koneksi, $query_antrian)) {
                throw new Exception('Gagal menyimpan data antrian: ' . mysqli_error($koneksi));
            }
        }
        
        // Save work order data to tblservice (update existing record)
        if(!empty($no_workorder) || !empty($deskripsi_pekerjaan)) {
            $query_wo = "UPDATE tblservice SET 
                        no_workorder = '$no_workorder',
                        deskripsi_pekerjaan = '$deskripsi_pekerjaan'
                        WHERE no_service = '$no_service'";
            
            if(!mysqli_query($koneksi, $query_wo)) {
                throw new Exception('Gagal menyimpan work order: ' . mysqli_error($koneksi));
            }
        }
        
        // Save mechanic assignments
        $mechanics = array(
            array('id' => $kepala_mekanik1, 'type' => 'kepala_mekanik', 'position' => 1),
            array('id' => $kepala_mekanik2, 'type' => 'kepala_mekanik', 'position' => 2),
            array('id' => $admin1, 'type' => 'admin', 'position' => 1),
            array('id' => $admin2, 'type' => 'admin', 'position' => 2),
            array('id' => $mekanik1, 'type' => 'mekanik', 'position' => 1),
            array('id' => $mekanik2, 'type' => 'mekanik', 'position' => 2),
            array('id' => $mekanik3, 'type' => 'mekanik', 'position' => 3),
            array('id' => $mekanik4, 'type' => 'mekanik', 'position' => 4)
        );
        
        foreach($mechanics as $mechanic) {
            if(!empty($mechanic['id'])) {
                $query_mechanic = "INSERT INTO tb_progress_mekanik (no_service, no_antrian, id_mekanik, jenis_kerja, persen_kerja, status_kerja) 
                                  VALUES ('$no_service', '$no_antrian', '{$mechanic['id']}', '{$mechanic['type']}', 0, 'belum_mulai')
                                  ON DUPLICATE KEY UPDATE 
                                  jenis_kerja = '{$mechanic['type']}', updated_at = NOW()";
                
                if(!mysqli_query($koneksi, $query_mechanic)) {
                    throw new Exception('Gagal menyimpan data mekanik: ' . mysqli_error($koneksi));
                }
            }
        }
        
        // Save service detail to tblservice (update existing record)
        if(!empty($keluhan) || !empty($catatan)) {
            $query_detail = "UPDATE tblservice SET 
                            keluhan = '$keluhan', 
                            catatan = '$catatan'
                            WHERE no_service = '$no_service'";
            
            if(!mysqli_query($koneksi, $query_detail)) {
                throw new Exception('Gagal menyimpan detail service: ' . mysqli_error($koneksi));
            }
        }
        
        // Save items (barang) - store as JSON in tblservice for now
        if(!empty($item_barang)) {
            $item_barang_json = json_encode($item_barang);
            $query_items = "UPDATE tblservice SET item_barang = '$item_barang_json' WHERE no_service = '$no_service'";
            
            if(!mysqli_query($koneksi, $query_items)) {
                throw new Exception('Gagal menyimpan item barang: ' . mysqli_error($koneksi));
            }
        }
        
        // Save items (jasa) - store as JSON in tblservice for now
        if(!empty($item_jasa)) {
            $item_jasa_json = json_encode($item_jasa);
            $query_items = "UPDATE tblservice SET item_jasa = '$item_jasa_json' WHERE no_service = '$no_service'";
            
            if(!mysqli_query($koneksi, $query_items)) {
                throw new Exception('Gagal menyimpan item jasa: ' . mysqli_error($koneksi));
            }
        }
        
        // Commit transaction
        mysqli_commit($koneksi);
        
        echo json_encode(array(
            'success' => true, 
            'message' => 'Service berhasil disimpan',
            'no_service' => $no_service,
            'no_antrian' => $no_antrian
        ));
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($koneksi);
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}
?>
