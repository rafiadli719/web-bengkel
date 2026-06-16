<?php
/**
 * Template: Top Pelanggan
 * Menampilkan pelanggan terbaik berdasarkan total nominal transaksi
 */

// Query untuk top pelanggan
$query_top = "SELECT * FROM view_top_pelanggan ORDER BY total_nominal DESC LIMIT 50";
$result_top = mysqli_query($koneksi, $query_top);
?>

<div class="widget-box">
    <div class="widget-header widget-header-flat widget-header-large">
        <h4 class="widget-title">
            <i class="ace-icon fa fa-star yellow"></i>
            Top 50 Pelanggan Terbaik
        </h4>
        <div class="widget-toolbar">
            <span class="badge badge-success">
                <?php echo mysqli_num_rows($result_top); ?> pelanggan
            </span>
        </div>
    </div>
    
    <div class="widget-body">
        <div class="widget-main no-padding">
            
            <div class="alert alert-info" style="margin: 20px;">
                <i class="ace-icon fa fa-trophy"></i>
                <strong>Top Pelanggan!</strong> Ini adalah pelanggan terbaik Anda berdasarkan total nilai transaksi.
                Berikan apresiasi khusus untuk mempertahankan loyalitas mereka.
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="center" width="60">Ranking</th>
                            <th>No. Pelanggan</th>
                            <th>Nama Pelanggan</th>
                            <th class="center">Total Transaksi</th>
                            <th class="center">Total Nominal</th>
                            <th class="center">Rata-rata Transaksi</th>
                            <th class="center">Status Member</th>
                            <th class="center">Pelanggan Sejak</th>
                            <th class="center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $ranking = 1;
                        while($row = mysqli_fetch_array($result_top)): 
                            // Tentukan icon ranking
                            $ranking_icon = '';
                            $ranking_class = '';
                            if($ranking == 1) {
                                $ranking_icon = '🥇';
                                $ranking_class = 'text-warning';
                            } elseif($ranking == 2) {
                                $ranking_icon = '🥈';
                                $ranking_class = 'text-info';
                            } elseif($ranking == 3) {
                                $ranking_icon = '🥉';
                                $ranking_class = 'text-danger';
                            }
                            
                            // Tentukan class badge member
                            $badge_class = 'default';
                            $icon = '🥉';
                            switch($row['status_member']) {
                                case 'Silver':
                                    $badge_class = 'info';
                                    $icon = '🥈';
                                    break;
                                case 'Gold':
                                    $badge_class = 'warning';
                                    $icon = '🥇';
                                    break;
                                case 'Platinum':
                                    $badge_class = 'success';
                                    $icon = '💎';
                                    break;
                            }
                            
                            // Hitung lama jadi pelanggan
                            $lama_pelanggan = floor((time() - strtotime($row['tanggal_pertama'])) / (60 * 60 * 24));
                            $lama_tahun = floor($lama_pelanggan / 365);
                            $lama_bulan = floor(($lama_pelanggan % 365) / 30);
                        ?>
                        <tr <?php if($ranking <= 3) echo 'style="background-color: #fffacd;"'; ?>>
                            <td class="center">
                                <h3 class="<?php echo $ranking_class; ?>" style="margin: 0;">
                                    <?php echo $ranking_icon; ?>
                                    <strong><?php echo $ranking; ?></strong>
                                </h3>
                            </td>
                            <td><?php echo $row['no_pelanggan']; ?></td>
                            <td>
                                <strong><?php echo $row['nama_pelanggan']; ?></strong>
                                <?php if($ranking <= 3): ?>
                                <br><span class="label label-warning">
                                    <i class="fa fa-star"></i> VIP Customer
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <span class="badge badge-info" style="font-size: 13px;">
                                    <?php echo $row['total_transaksi']; ?>x
                                </span>
                            </td>
                            <td class="text-right">
                                <strong style="font-size: 14px; color: #28a745;">
                                    Rp <?php echo number_format($row['total_nominal'], 0, ',', '.'); ?>
                                </strong>
                            </td>
                            <td class="text-right">
                                Rp <?php echo number_format($row['rata_rata_transaksi'], 0, ',', '.'); ?>
                            </td>
                            <td class="center">
                                <span class="label label-<?php echo $badge_class; ?>" style="font-size: 12px;">
                                    <?php echo $icon . ' ' . $row['status_member']; ?>
                                </span>
                            </td>
                            <td class="center">
                                <?php echo date('d/m/Y', strtotime($row['tanggal_pertama'])); ?>
                                <br><small class="text-muted">
                                    <?php 
                                    if($lama_tahun > 0) {
                                        echo $lama_tahun . ' tahun ';
                                    }
                                    if($lama_bulan > 0) {
                                        echo $lama_bulan . ' bulan';
                                    }
                                    ?>
                                </small>
                            </td>
                            <td class="center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-info" 
                                            onclick="lihatDetail('<?php echo $row['no_pelanggan']; ?>')"
                                            title="Lihat Detail">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <a href="statistik_pelanggan_send_wa.php?nopelanggan=<?php echo $row['no_pelanggan']; ?>&type=appreciation" 
                                       class="btn btn-xs btn-success" 
                                       target="_blank"
                                       title="Kirim Apresiasi">
                                        <i class="fa fa-heart"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                        $ranking++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="widget-toolbox padding-12 clearfix">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fa fa-lightbulb-o"></i> <strong>Tips Mempertahankan Top Pelanggan:</strong></h5>
                        <ul class="list-unstyled">
                            <li><i class="fa fa-check text-success"></i> Berikan diskon eksklusif untuk member Platinum & Gold</li>
                            <li><i class="fa fa-check text-success"></i> Prioritaskan antrian untuk pelanggan VIP</li>
                            <li><i class="fa fa-check text-success"></i> Kirim ucapan terima kasih secara berkala</li>
                            <li><i class="fa fa-check text-success"></i> Tawarkan program loyalitas dengan poin reward</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fa fa-gift"></i> <strong>Benefit Member:</strong></h5>
                        <table class="table table-condensed">
                            <tr>
                                <td>💎 <strong>Platinum</strong></td>
                                <td>Diskon 20% + Jemput Antar Gratis</td>
                            </tr>
                            <tr>
                                <td>🥇 <strong>Gold</strong></td>
                                <td>Diskon 15% + Gratis Cuci Motor</td>
                            </tr>
                            <tr>
                                <td>🥈 <strong>Silver</strong></td>
                                <td>Diskon 10% + Prioritas Antrian</td>
                            </tr>
                            <tr>
                                <td>🥉 <strong>Bronze</strong></td>
                                <td>Poin Reward Setiap Transaksi</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
function lihatDetail(nopelanggan) {
    // Redirect ke halaman detail pelanggan
    window.location.href = 'detail_pelanggan.php?nopelanggan=' + nopelanggan;
}
</script>
