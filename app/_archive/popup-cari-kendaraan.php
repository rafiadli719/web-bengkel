<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

$search_query = "";
$result_message = "Masukkan kata kunci pencarian";

if(isset($_POST['btncari'])) {
    $search_query = mysqli_real_escape_string($koneksi, $_POST['txtsearch']);
    
    if(!empty($search_query)) {
        // Count results
        $count_sql = "SELECT COUNT(*) as total FROM view_pelanggan_kendaraan 
                     WHERE (nopolisi LIKE '%$search_query%') OR 
                           (pemilik LIKE '%$search_query%') OR 
                           (telephone LIKE '%$search_query%')";
        $count_result = mysqli_query($koneksi, $count_sql);
        $count_data = mysqli_fetch_assoc($count_result);
        $total_found = $count_data['total'];
        
        $result_message = "Ditemukan $total_found kendaraan";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Kendaraan</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/ace.min.css">
    
    <style>
        body {
            padding: 15px;
            background: #f5f5f5;
        }
        
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .results-container {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
        }
        
        .vehicle-row {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .vehicle-row:hover {
            background-color: #f8f9fa;
        }
        
        .vehicle-row:last-child {
            border-bottom: none;
        }
        
        .vehicle-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn-select {
            float: right;
            margin-top: 5px;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 1px 3px;
            border-radius: 2px;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="search-container">
        <h4><i class="fa fa-search"></i> Cari Kendaraan</h4>
        <form method="post" action="">
            <div class="input-group">
                <input type="text" 
                       class="form-control" 
                       name="txtsearch" 
                       id="txtsearch"
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       placeholder="Masukkan No. Polisi, Nama Pemilik, atau No. Telepon..."
                       autocomplete="off">
                <span class="input-group-btn">
                    <button type="submit" 
                            name="btncari" 
                            class="btn btn-primary">
                        <i class="fa fa-search"></i> Cari
                    </button>
                </span>
            </div>
        </form>
        
        <div class="result-info" style="margin-top: 10px;">
            <small class="text-muted"><?php echo $result_message; ?></small>
        </div>
    </div>

    <div class="results-container">
        <?php if(isset($_POST['btncari']) && !empty($search_query)): ?>
            <?php
            $sql = "SELECT nopolisi, pemilik, tipe, jenis, warna, merek, telephone, alamat
                    FROM view_pelanggan_kendaraan 
                    WHERE (nopolisi LIKE '%$search_query%') OR 
                          (pemilik LIKE '%$search_query%') OR 
                          (telephone LIKE '%$search_query%')
                    ORDER BY pemilik ASC
                    LIMIT 50";
            
            $result = mysqli_query($koneksi, $sql);
            
            if(mysqli_num_rows($result) > 0):
                while($data = mysqli_fetch_assoc($result)):
                    // Highlight search terms
                    $nopol_highlighted = str_ireplace($search_query, '<span class="highlight">'.$search_query.'</span>', htmlspecialchars($data['nopolisi']));
                    $owner_highlighted = str_ireplace($search_query, '<span class="highlight">'.$search_query.'</span>', htmlspecialchars($data['pemilik']));
                    $phone_highlighted = str_ireplace($search_query, '<span class="highlight">'.$search_query.'</span>', htmlspecialchars($data['telephone']));
            ?>
                <div class="vehicle-row" onclick="selectVehicle('<?php echo htmlspecialchars($data['nopolisi']); ?>', '<?php echo htmlspecialchars($data['pemilik']); ?>')">
                    <div class="row">
                        <div class="col-md-8">
                            <strong><?php echo $nopol_highlighted; ?></strong>
                            <div><?php echo $owner_highlighted; ?></div>
                            <div class="vehicle-info">
                                <i class="fa fa-motorcycle"></i> <?php echo htmlspecialchars($data['merek']); ?> - <?php echo htmlspecialchars($data['tipe']); ?> 
                                <span class="text-muted">(<?php echo htmlspecialchars($data['jenis']); ?>, <?php echo htmlspecialchars($data['warna']); ?>)</span>
                                <?php if(!empty($data['telephone'])): ?>
                                    <br><i class="fa fa-phone"></i> <?php echo $phone_highlighted; ?>
                                <?php endif; ?>
                                <?php if(!empty($data['alamat'])): ?>
                                    <br><i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($data['alamat']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="button" 
                                    class="btn btn-success btn-sm btn-select"
                                    onclick="selectVehicle('<?php echo htmlspecialchars($data['nopolisi']); ?>', '<?php echo htmlspecialchars($data['pemilik']); ?>')">
                                <i class="fa fa-check"></i> Pilih
                            </button>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="no-results">
                    <i class="fa fa-search fa-3x"></i>
                    <h4>Tidak ada kendaraan ditemukan</h4>
                    <p>Coba gunakan kata kunci yang berbeda atau periksa ejaan.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fa fa-info-circle fa-2x"></i>
                <h4>Silakan masukkan kata kunci pencarian</h4>
                <p>Anda dapat mencari berdasarkan No. Polisi, Nama Pemilik, atau No. Telepon.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Auto-focus on search input
            $('#txtsearch').focus();
            
            // Search on Enter key
            $('#txtsearch').on('keypress', function(e) {
                if (e.which === 13) {
                    $('button[name="btncari"]').click();
                }
            });
        });
        
        function selectVehicle(nopol, owner) {
            // Check if parent window has the setKendaraan function
            if (window.opener && window.opener.setKendaraan) {
                window.opener.setKendaraan(nopol);
                
                // Also try to trigger customer data fetch if the function exists
                if (window.opener.fetchCustomerData) {
                    window.opener.fetchCustomerData(nopol);
                }
                
                // Show success message briefly before closing
                showSuccessMessage(nopol, owner);
                
                setTimeout(function() {
                    window.close();
                }, 1000);
            } else {
                alert('Error: Tidak dapat mengirim data ke form utama.');
            }
        }
        
        function showSuccessMessage(nopol, owner) {
            var message = '<div class="alert alert-success" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">' +
                         '<i class="fa fa-check-circle"></i> Kendaraan ' + nopol + ' (' + owner + ') telah dipilih!' +
                         '</div>';
            
            $('body').append(message);
            
            setTimeout(function() {
                $('.alert-success').fadeOut();
            }, 800);
        }
        
        // Close popup when Escape key is pressed
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                window.close();
            }
        });
    </script>
</body>
</html>