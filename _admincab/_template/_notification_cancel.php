<?php
// Notification for cancel service results
if(isset($_GET['success']) && $_GET['success'] == 'cancel') {
    $no_service = $_GET['no_service'] ?? '';
    $refund = $_GET['refund'] ?? '0';
    echo '<div class="alert alert-success alert-dismissible" style="margin-top: 10px;">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="fa fa-check"></i> Service Berhasil Dibatalkan!</h4>
            <strong>No Service:</strong> ' . htmlspecialchars($no_service) . '<br>
            <strong>Refund:</strong> Rp ' . htmlspecialchars($refund) . '
          </div>';
}

if(isset($_GET['error'])) {
    $error_msg = '';
    switch($_GET['error']) {
        case 'unauthorized':
            $error_msg = 'Akses tidak diizinkan. Silakan login kembali.';
            break;
        case 'invalid_service':
            $error_msg = 'Nomor service tidak valid.';
            break;
        case 'missing_reason':
            $error_msg = 'Alasan pembatalan harus diisi.';
            break;
        default:
            $error_msg = 'Terjadi kesalahan. Silakan coba lagi.';
    }
    
    echo '<div class="alert alert-danger alert-dismissible" style="margin-top: 10px;">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="fa fa-times"></i> Error!</h4>
            ' . htmlspecialchars($error_msg) . '
          </div>';
}
?>

<script>
// Auto hide notifications after 5 seconds
setTimeout(function() {
    $('.alert-dismissible').fadeOut('slow');
}, 5000);
</script>
