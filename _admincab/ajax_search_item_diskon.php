<?php
/**
 * AJAX Handler: Search Item untuk Pengaturan Diskon Member
 * File: ajax_search_item_diskon.php
 */

session_start();
if(empty($_SESSION['_iduser'])){
    echo '<div class="alert alert-danger">Session expired</div>';
    exit;
}

include "../config/koneksi.php";

$search = isset($_POST['search']) ? mysqli_real_escape_string($koneksi, trim($_POST['search'])) : '';

if(strlen($search) < 2) {
    echo '<div class="alert alert-warning">Masukkan minimal 2 karakter</div>';
    exit;
}

// Get filter tipe (barang/jasa)
$filter_tipe = isset($_POST['filter_tipe']) ? mysqli_real_escape_string($koneksi, $_POST['filter_tipe']) : 'semua';

// Build tipe condition
$tipe_condition = '';
if($filter_tipe == 'barang') {
    $tipe_condition = "AND (i.jenis_jasa = 0 OR i.jenis_jasa IS NULL)";
} elseif($filter_tipe == 'jasa') {
    $tipe_condition = "AND i.jenis_jasa > 0";
}

// Search items (barang dan jasa)
$sql = "SELECT i.noitem, i.namaitem, i.jenis, i.hargajual, i.jenis_jasa,
               i.exclude_diskon_member as item_exclude,
               h.exclude_diskon_member as jenis_exclude,
               CASE WHEN i.jenis_jasa > 0 THEN 'JASA' ELSE 'BARANG' END as tipe_item
        FROM tblitem i
        LEFT JOIN tbhargajual h ON i.jenis = h.jenis
        WHERE (i.noitem LIKE '%$search%' OR i.namaitem LIKE '%$search%')
        AND i.statusitem = '1'
        $tipe_condition
        ORDER BY i.namaitem ASC
        LIMIT 50";

$result = mysqli_query($koneksi, $sql);

if(!$result) {
    echo '<div class="alert alert-danger">Error: ' . mysqli_error($koneksi) . '</div>';
    exit;
}

if(mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-info">Tidak ada item yang ditemukan dengan kata kunci "<strong>' . htmlspecialchars($search) . '</strong>"</div>';
    exit;
}
?>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th width="12%">Kode Item</th>
            <th width="28%">Nama Item</th>
            <th width="8%">Tipe</th>
            <th width="10%">Jenis</th>
            <th width="12%">Harga</th>
            <th width="30%">Status Diskon Member</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): 
            // Determine effective exclude status
            // NULL = ikut jenis, 0 = force dapat diskon, 1 = force tidak dapat diskon
            $item_exclude = $row['item_exclude'];
            $jenis_exclude = $row['jenis_exclude'] ?? 0;
            
            if($item_exclude === null) {
                // Ikut setting jenis
                $effective_exclude = $jenis_exclude;
                $status_source = 'jenis';
            } else {
                // Override per item
                $effective_exclude = $item_exclude;
                $status_source = 'item';
            }
        ?>
        <tr>
            <td><code><?php echo htmlspecialchars($row['noitem']); ?></code></td>
            <td><?php echo htmlspecialchars($row['namaitem']); ?></td>
            <td class="text-center">
                <?php if($row['tipe_item'] == 'JASA'): ?>
                    <span class="label label-info arrowed-right arrowed-in">JASA</span>
                <?php else: ?>
                    <span class="label label-inverse arrowed">BARANG</span>
                <?php endif; ?>
            </td>
            <td>
                <span class="label label-<?php echo $jenis_exclude ? 'warning' : 'default'; ?>">
                    <?php echo htmlspecialchars($row['jenis']); ?>
                </span>
            </td>
            <td class="text-right">
                Rp <?php echo number_format($row['hargajual'], 0, ',', '.'); ?>
            </td>
            <td>
                <?php if($effective_exclude): ?>
                    <span class="label label-warning">
                        <i class="fa fa-ban"></i> No Discount
                        <?php if($status_source == 'jenis'): ?>
                            <small>(dari jenis)</small>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <span class="label label-success">
                        <i class="fa fa-check"></i> Dapat Diskon
                        <?php if($status_source == 'jenis'): ?>
                            <small>(dari jenis)</small>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                
                <div class="pull-right">
                    <?php if($item_exclude === null): ?>
                        <!-- Compact Button Group - More reliable than dropdown -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-xs btn-white btn-success" 
                                    onclick='updateStatusItem("<?php echo addslashes($row['noitem']); ?>", 0, false)'
                                    title="Dapatkan Diskon (Hijau)">
                                <b>Y</b>
                            </button>
                            <button type="button" class="btn btn-xs btn-white btn-danger" 
                                    onclick='updateStatusItem("<?php echo addslashes($row['noitem']); ?>", 1, false)'
                                    title="Tidak Dapat Diskon (Merah)">
                                <b>N</b>
                            </button>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn btn-xs btn-info" 
                                onclick='updateStatusItem("<?php echo addslashes($row['noitem']); ?>", "NULL", true)'
                                title="Reset ke pengaturan kategori">
                            <i class="fa fa-undo"></i> Reset
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<p class="text-muted"><small>Menampilkan maksimal 50 hasil pencarian</small></p>
