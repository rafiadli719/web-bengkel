<?php
/**
 * Template: Pelanggan Perlu Follow-up
 * Menampilkan pelanggan yang lama tidak datang (> 30 hari)
 */

// Query untuk pelanggan perlu follow-up
$query_followup = "SELECT * FROM view_pelanggan_follow_up ORDER BY hari_tidak_datang DESC";
$result_followup = mysqli_query($koneksi, $query_followup);
?>

<div class="widget-box">
    <div class="widget-header widget-header-flat widget-header-large">
        <h4 class="widget-title">
            <i class="ace-icon fa fa-bell red"></i>
            Pelanggan Perlu Follow-up
        </h4>
        <div class="widget-toolbar">
            <span class="badge badge-warning">
                <?php echo mysqli_num_rows($result_followup); ?> pelanggan
            </span>
        </div>
    </div>
    
    <div class="widget-body">
        <div class="widget-main no-padding">
            
            <?php if(mysqli_num_rows($result_followup) == 0): ?>
            
            <div class="alert alert-success" style="margin: 20px;">
                <i class="ace-icon fa fa-check"></i>
                <strong>Bagus!</strong> Tidak ada pelanggan yang perlu di-follow-up saat ini.
                Semua pelanggan aktif dan rutin datang.
            </div>
            
            <?php else: ?>
            
            <div class="alert alert-warning" style="margin: 20px;">
                <i class="ace-icon fa fa-exclamation-triangle"></i>
                <strong>Perhatian!</strong> Ada <?php echo mysqli_num_rows($result_followup); ?> pelanggan yang sudah lama tidak datang.
                Segera lakukan follow-up untuk meningkatkan customer retention.
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="center" width="50">No</th>
                            <th>No. Pelanggan</th>
                            <th>Nama Pelanggan</th>
                            <th>Telepon</th>
                            <th class="center">Total Transaksi</th>
                            <th class="center">Terakhir Datang</th>
                            <th class="center">Lama Tidak Datang</th>
                            <th class="center">Prioritas</th>
                            <th class="center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($row = mysqli_fetch_array($result_followup)): 
                            // Tentukan prioritas
                            $prioritas_class = 'warning';
                            $prioritas_text = 'Normal';
                            $prioritas_icon = 'fa-clock-o';
                            
                            if($row['hari_tidak_datang'] > 90) {
                                $prioritas_class = 'danger';
                                $prioritas_text = 'Urgent';
                                $prioritas_icon = 'fa-exclamation-circle';
                            } elseif($row['hari_tidak_datang'] > 60) {
                                $prioritas_class = 'warning';
                                $prioritas_text = 'Tinggi';
                                $prioritas_icon = 'fa-exclamation-triangle';
                            }
                        ?>
                        <tr>
                            <td class="center"><?php echo $no++; ?></td>
                            <td><?php echo $row['nopelanggan']; ?></td>
                            <td>
                                <strong><?php echo $row['namapelanggan']; ?></strong>
                                <br><small class="text-muted"><?php echo $row['alamat']; ?></small>
                            </td>
                            <td>
                                <?php if(!empty($row['telephone'])): ?>
                                    <i class="fa fa-phone"></i> <?php echo $row['telephone']; ?>
                                <?php elseif(!empty($row['notlp'])): ?>
                                    <i class="fa fa-phone"></i> <?php echo $row['notlp']; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <span class="badge badge-info">-</span>
                            </td>
                            <td class="center">
                                <?php echo date('d/m/Y', strtotime($row['tanggal_terakhir_transaksi'])); ?>
                            </td>
                            <td class="center">
                                <span class="label label-<?php echo $prioritas_class; ?>" style="font-size: 13px;">
                                    <?php echo $row['hari_tidak_datang']; ?> hari
                                </span>
                            </td>
                            <td class="center">
                                <span class="label label-<?php echo $prioritas_class; ?>">
                                    <i class="fa <?php echo $prioritas_icon; ?>"></i>
                                    <?php echo $prioritas_text; ?>
                                </span>
                            </td>
                            <td class="center">
                                <div class="btn-group">
                                    <a href="statistik_pelanggan_send_wa.php?nopelanggan=<?php echo $row['nopelanggan']; ?>" 
                                       class="btn btn-xs btn-success" 
                                       target="_blank"
                                       title="Kirim WhatsApp Reminder">
                                        <i class="fa fa-whatsapp"></i> Kirim WA
                                    </a>
                                    <button type="button" class="btn btn-xs btn-info" 
                                            onclick="lihatRiwayat('<?php echo $row['nopelanggan']; ?>')"
                                            title="Lihat Riwayat">
                                        <i class="fa fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="widget-toolbox padding-12 clearfix">
                <div class="pull-left">
                    <strong>Tips Follow-up:</strong>
                    <ul class="list-unstyled" style="margin-top: 10px;">
                        <li><i class="fa fa-check text-success"></i> Kirim pesan WhatsApp reminder yang personal</li>
                        <li><i class="fa fa-check text-success"></i> Tawarkan promo atau diskon khusus untuk pelanggan lama</li>
                        <li><i class="fa fa-check text-success"></i> Tanyakan kondisi motor dan tawarkan free check-up</li>
                    </ul>
                </div>
                <div class="pull-right">
                    <button type="button" class="btn btn-success" onclick="kirimBroadcast()">
                        <i class="fa fa-whatsapp"></i>
                        Kirim Broadcast Semua
                    </button>
                </div>
            </div>
            
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script>
function lihatRiwayat(nopelanggan) {
    // Redirect ke halaman detail pelanggan
    window.location.href = 'detail_pelanggan.php?nopelanggan=' + nopelanggan;
}

function kirimBroadcast() {
    if(confirm('Kirim pesan WhatsApp ke semua pelanggan yang perlu follow-up?')) {
        window.open('broadcast_followup.php', '_blank');
    }
}
</script>
