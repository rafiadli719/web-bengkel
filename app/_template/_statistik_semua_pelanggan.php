<?php
/**
 * Template: Semua Pelanggan
 * Menampilkan semua data statistik pelanggan
 */

// Query untuk semua pelanggan
$query_semua = "SELECT * FROM view_statistik_pelanggan ORDER BY total_nominal DESC";
$result_semua = mysqli_query($koneksi, $query_semua);
?>

<div class="widget-box">
    <div class="widget-header widget-header-flat">
        <h4 class="widget-title">
            <i class="ace-icon fa fa-users"></i>
            Semua Pelanggan
        </h4>
        <div class="widget-toolbar">
            <input type="text" id="search_pelanggan" class="form-control input-sm" placeholder="Cari pelanggan..." style="width: 200px;">
        </div>
    </div>
    
    <div class="widget-body">
        <div class="widget-main no-padding">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="tabel_semua_pelanggan">
                    <thead>
                        <tr>
                            <th class="center" width="50">No</th>
                            <th>No. Pelanggan</th>
                            <th>Nama Pelanggan</th>
                            <th class="center">Total Transaksi</th>
                            <th class="center">Total Nominal</th>
                            <th class="center">Rata-rata</th>
                            <th class="center">Status Member</th>
                            <th class="center">Terakhir Datang</th>
                            <th class="center">Lama Tidak Datang</th>
                            <th class="center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($row = mysqli_fetch_array($result_semua)): 
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
                            
                            // Tentukan status pelanggan
                            $status_class = 'success';
                            $status_text = 'Aktif';
                            if($row['lama_tidak_datang'] > 60) {
                                $status_class = 'danger';
                                $status_text = 'Urgent';
                            } elseif($row['lama_tidak_datang'] > 30) {
                                $status_class = 'warning';
                                $status_text = 'Perlu Follow-up';
                            }
                        ?>
                        <tr>
                            <td class="center"><?php echo $no++; ?></td>
                            <td><?php echo $row['no_pelanggan']; ?></td>
                            <td>
                                <strong><?php echo $row['nama_pelanggan']; ?></strong>
                                <?php if($row['lama_tidak_datang'] > 30): ?>
                                <br><small class="text-<?php echo $status_class; ?>">
                                    <i class="fa fa-exclamation-triangle"></i> <?php echo $status_text; ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <span class="badge badge-info"><?php echo $row['total_transaksi']; ?>x</span>
                            </td>
                            <td class="text-right">
                                <strong>Rp <?php echo number_format($row['total_nominal'], 0, ',', '.'); ?></strong>
                            </td>
                            <td class="text-right">
                                Rp <?php echo number_format($row['rata_rata_transaksi'], 0, ',', '.'); ?>
                            </td>
                            <td class="center">
                                <span class="label label-<?php echo $badge_class; ?>">
                                    <?php echo $icon . ' ' . $row['status_member']; ?>
                                </span>
                            </td>
                            <td class="center">
                                <?php echo date('d/m/Y', strtotime($row['tanggal_terakhir'])); ?>
                                <br><small class="text-muted"><?php echo $row['lama_tidak_datang']; ?> hari lalu</small>
                            </td>
                            <td class="center">
                                <?php if($row['lama_tidak_datang'] > 30): ?>
                                    <span class="label label-<?php echo $status_class; ?>">
                                        <?php echo $row['lama_tidak_datang']; ?> hari
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fa fa-check"></i> Aktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-info" 
                                            onclick="lihatDetail('<?php echo $row['no_pelanggan']; ?>')"
                                            title="Lihat Detail">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <?php if($row['lama_tidak_datang'] > 30): ?>
                                    <a href="statistik_pelanggan_send_wa.php?nopelanggan=<?php echo $row['no_pelanggan']; ?>" 
                                       class="btn btn-xs btn-success" 
                                       target="_blank"
                                       title="Kirim WhatsApp">
                                        <i class="fa fa-whatsapp"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Lihat detail function (tidak perlu jQuery)
function lihatDetail(nopelanggan) {
    // Redirect ke halaman detail pelanggan
    window.location.href = 'detail_pelanggan.php?nopelanggan=' + nopelanggan;
}

// Search function (akan dijalankan setelah jQuery loaded)
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        $('#search_pelanggan').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#tabel_semua_pelanggan tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
}
</script>
