<?php
/**
 * Template: List Temuan & Penawaran Part per Servis
 * Digunakan untuk menampilkan daftar temuan dan penawaran part yang sudah dibuat
 */
?>

<style>
.temuan-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
    overflow: hidden;
}
.temuan-card-header {
    padding: 10px 15px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
}
.temuan-card-body {
    padding: 15px;
}
.temuan-urgent-kritis {
    border-left: 4px solid #d9534f;
}
.temuan-urgent-tinggi {
    border-left: 4px solid #f0ad4e;
}
.temuan-urgent-sedang {
    border-left: 4px solid #5bc0de;
}
.temuan-urgent-rendah {
    border-left: 4px solid #5cb85c;
}
.penawaran-item {
    background-color: #f9f9f9;
    padding: 8px;
    margin: 5px 0;
    border-radius: 3px;
}
.penawaran-pending {
    border-left: 3px solid #f0ad4e;
}
.penawaran-disetujui {
    border-left: 3px solid #5cb85c;
}
.penawaran-ditolak {
    border-left: 3px solid #d9534f;
}
</style>

<!-- ============================================ -->
<!-- SECTION: Pending Items dari Work Order -->
<!-- ============================================ -->
<?php
// Query pending items dari Work Order
$sql_pending = "SELECT 
                    pi.*,
                    sw.kode_wo,
                    wh.nama_wo
                FROM tbservis_pending_items pi
                LEFT JOIN tbservis_workorder sw ON pi.wo_id = sw.id
                LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                WHERE pi.no_service = '$no_service'
                AND pi.status_approval = 'pending'
                ORDER BY pi.created_at DESC";
$result_pending = mysqli_query($koneksi, $sql_pending);
$total_pending = $result_pending ? mysqli_num_rows($result_pending) : 0;

if($total_pending > 0) {
?>
<div class="widget-box widget-color-orange">
    <div class="widget-header widget-header-small">
        <h5 class="widget-title">
            <i class="ace-icon fa fa-clock-o"></i>
            Item Pending dari Work Order
        </h5>
        <div class="widget-toolbar">
            <span class="badge badge-warning"><?php echo $total_pending; ?> Item Pending</span>
            <a href="#" data-action="collapse">
                <i class="ace-icon fa fa-chevron-up"></i>
            </a>
        </div>
    </div>
    <div class="widget-body">
        <div class="widget-main">
            <div class="alert alert-info">
                <i class="ace-icon fa fa-info-circle"></i>
                <strong>Perhatian:</strong> Item berikut dari Work Order memerlukan persetujuan sebelum masuk ke daftar barang/jasa servis.
            </div>
            <div class="clearfix" style="margin-bottom:10px;">
                <div class="pull-left">
                    <button type="button" id="btnApproveSelectedWO" class="btn btn-success btn-xs" onclick="submitBulkApprove()" disabled>
                        <i class="ace-icon fa fa-check"></i> Setujui yang Dipilih
                    </button>
                    <button type="button" id="btnRejectSelectedWO" class="btn btn-danger btn-xs" onclick="openBulkRejectModal()" disabled>
                        <i class="ace-icon fa fa-times"></i> Tolak yang Dipilih
                    </button>
                </div>
            </div>
            
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr class="warning">
                        <th width="3%" class="text-center"><input type="checkbox" id="wo_select_all" onclick="toggleSelectAllWO(this)"></th>
                        <th width="5%">#</th>
                        <th width="15%">Work Order</th>
                        <th width="10%">Tipe</th>
                        <th width="15%">Kode</th>
                        <th>Nama Item</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Harga</th>
                        <th width="12%" class="text-right">Total</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no_pending = 0;
                    while($pending = mysqli_fetch_array($result_pending)) {
                        $no_pending++;
                        $tipe_badge = ($pending['tipe'] == 'barang') ? 
                            '<span class="label label-info"><i class="fa fa-cube"></i> Barang</span>' : 
                            '<span class="label label-purple"><i class="fa fa-wrench"></i> Jasa</span>';
                        $wo_display = $pending['nama_wo'] ? $pending['nama_wo'] : $pending['kode_wo'];
                    ?>
                    <tr>
                        <td class="text-center"><input type="checkbox" class="wo-select" value="<?php echo $pending['id']; ?>" onclick="updateBulkButtons()"></td>
                        <td class="text-center"><?php echo $no_pending; ?></td>
                        <td>
                            <small class="text-muted"><?php echo htmlspecialchars($pending['kode_wo']); ?></small><br>
                            <strong><?php echo htmlspecialchars($wo_display); ?></strong>
                        </td>
                        <td class="text-center"><?php echo $tipe_badge; ?></td>
                        <td><code><?php echo htmlspecialchars($pending['kode_item']); ?></code></td>
                        <td><?php echo htmlspecialchars($pending['nama_item']); ?></td>
                        <td class="text-center"><?php echo $pending['quantity']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($pending['harga_satuan'], 0, ',', '.'); ?></td>
                        <td class="text-right"><strong>Rp <?php echo number_format($pending['total'], 0, ',', '.'); ?></strong></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                                    <input type="hidden" name="pending_item_id" value="<?php echo $pending['id']; ?>">
                                    <button type="submit" name="btnapprove_wo_item" class="btn btn-success btn-xs" 
                                            onclick="return confirm('Setujui item ini?\n\nItem akan ditambahkan ke <?php echo ($pending['tipe'] == 'barang') ? 'Item Barang' : 'Item Jasa'; ?> servis.');"
                                            title="Setujui">
                                        <i class="ace-icon fa fa-check"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-xs" 
                                        onclick="showRejectModal(<?php echo $pending['id']; ?>, '<?php echo addslashes($pending['nama_item']); ?>', '<?php echo $pending['tipe']; ?>')"
                                        title="Tolak">
                                    <i class="ace-icon fa fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="warning">
                        <td colspan="7" class="text-right"><strong>Total Pending:</strong></td>
                        <td class="text-right">
                            <?php 
                            // Reset pointer and calculate total
                            mysqli_data_seek($result_pending, 0);
                            $grand_total_pending = 0;
                            while($p = mysqli_fetch_array($result_pending)) {
                                $grand_total_pending += $p['total'];
                            }
                            ?>
                            <strong class="orange">Rp <?php echo number_format($grand_total_pending, 0, ',', '.'); ?></strong>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <form id="bulkWoForm" method="POST" style="display:none;">
                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                <input type="hidden" name="btnapprove_wo_bulk" value="1">
            </form>
            <div class="modal fade" id="modalRejectWoBulk" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="bulkRejectForm" method="POST">
                            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                            <input type="hidden" name="btnreject_wo_bulk" value="1">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title"><i class="fa fa-times-circle red"></i> Tolak Item Terpilih</h4>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <strong>Jumlah item terpilih:</strong> <span id="wo_bulk_count">0</span>
                                </div>
                                <div class="form-group">
                                    <label>Alasan Penolakan <span class="red">*</span></label>
                                    <select name="alasan_reject" class="form-control" required>
                                        <option value="">-- Pilih Alasan --</option>
                                        <option value="customer_tidak_mau">Pelanggan tidak setuju</option>
                                        <option value="stok_cabang_kosong">Stok barang di bengkel tidak ada</option>
                                        <option value="stok_supplier_kosong">Stok barang di supplier tidak ada</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Keterangan Tambahan</label>
                                    <textarea name="keterangan_reject" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                                </div>
                                <div id="bulkRejectSelectedHolder"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> Tolak Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <script>
            function getSelectedWoIds(){
                var ids=[];var boxes=document.querySelectorAll('.wo-select');
                boxes.forEach(function(b){if(b.checked){ids.push(b.value)}});
                return ids;
            }
            function toggleSelectAllWO(src){
                var boxes=document.querySelectorAll('.wo-select');
                boxes.forEach(function(b){b.checked=src.checked});
                updateBulkButtons();
            }
            function updateBulkButtons(){
                var any=getSelectedWoIds().length>0;
                var btnA=document.getElementById('btnApproveSelectedWO');
                var btnR=document.getElementById('btnRejectSelectedWO');
                if(btnA) btnA.disabled=!any; if(btnR) btnR.disabled=!any;
                var selAll=document.getElementById('wo_select_all');
                if(selAll){
                    var boxes=document.querySelectorAll('.wo-select');
                    var allChecked=true;var anyChecked=false;
                    boxes.forEach(function(b){allChecked=allChecked&&b.checked; anyChecked=anyChecked||b.checked;});
                    selAll.indeterminate = anyChecked && !allChecked;
                    selAll.checked = allChecked && anyChecked;
                }
            }
            function submitBulkApprove(){
                var ids=getSelectedWoIds();
                if(ids.length===0){alert('Pilih minimal satu item.');return;}
                if(!confirm('Setujui '+ids.length+' item terpilih? Item akan ditambahkan ke daftar barang/jasa servis.')) return;
                var form=document.getElementById('bulkWoForm');
                while(form.firstChild){if(form.firstChild.name&&form.firstChild.name==='selected_item_ids[]'){form.removeChild(form.firstChild);}else{break;}}
                ids.forEach(function(id){
                    var i=document.createElement('input');i.type='hidden';i.name='selected_item_ids[]';i.value=id;form.appendChild(i);
                });
                form.submit();
            }
            function openBulkRejectModal(){
                var ids=getSelectedWoIds();
                if(ids.length===0){alert('Pilih minimal satu item.');return;}
                document.getElementById('wo_bulk_count').textContent=ids.length;
                var holder=document.getElementById('bulkRejectSelectedHolder');
                holder.innerHTML='';
                ids.forEach(function(id){
                    var i=document.createElement('input');i.type='hidden';i.name='selected_item_ids[]';i.value=id;holder.appendChild(i);
                });
                $('#modalRejectWoBulk').modal('show');
            }
            document.addEventListener('change',function(e){if(e.target&&e.target.classList.contains('wo-select')){updateBulkButtons();}});
            </script>
        </div>
    </div>
</div>

<!-- Modal Reject Item -->
<div class="modal fade" id="modalRejectItem" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                <input type="hidden" name="pending_item_id" id="reject_item_id" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-times-circle red"></i> Tolak Item</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Item:</strong> <span id="reject_item_name"></span> (<span id="reject_item_tipe"></span>)
                    </div>
                    <div class="form-group">
                        <label>Alasan Penolakan <span class="red">*</span></label>
                        <select name="alasan_reject" class="form-control" required>
                            <option value="">-- Pilih Alasan --</option>
                            <option value="customer_tidak_mau">Pelanggan tidak setuju</option>
                            <option value="stok_cabang_kosong">Stok barang di bengkel tidak ada</option>
                            <option value="stok_supplier_kosong">Stok barang di supplier tidak ada</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan Tambahan</label>
                        <textarea name="keterangan_reject" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="btnreject_wo_item" class="btn btn-danger">
                        <i class="fa fa-times"></i> Tolak Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(itemId, itemName, itemTipe) {
    document.getElementById('reject_item_id').value = itemId;
    document.getElementById('reject_item_name').textContent = itemName;
    document.getElementById('reject_item_tipe').textContent = (itemTipe == 'barang') ? 'Barang' : 'Jasa';
    $('#modalRejectItem').modal('show');
}
</script>

<div class="space-14"></div>
<?php } // end if total_pending > 0 ?>

<?php
$sql_wo_history = "SELECT 
                        pi.*, 
                        sw.kode_wo, 
                        wh.nama_wo 
                    FROM tbservis_pending_items pi 
                    LEFT JOIN tbservis_workorder sw ON pi.wo_id = sw.id 
                    LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo 
                    WHERE pi.no_service = '$no_service' 
                      AND pi.status_approval IN ('disetujui','ditolak') 
                    ORDER BY pi.created_at DESC";
$result_wo_history = mysqli_query($koneksi, $sql_wo_history);
$total_wo_history = $result_wo_history ? mysqli_num_rows($result_wo_history) : 0;

if($total_wo_history > 0) {
?>
<div class="widget-box widget-color-grey">
    <div class="widget-header widget-header-small">
        <h5 class="widget-title">
            <i class="ace-icon fa fa-history"></i>
            Riwayat Item Work Order (Disetujui/Ditolak)
        </h5>
        <div class="widget-toolbar">
            <span class="badge badge-grey"><?php echo $total_wo_history; ?> Item</span>
            <a href="#" data-action="collapse">
                <i class="ace-icon fa fa-chevron-up"></i>
            </a>
        </div>
    </div>
    <div class="widget-body">
        <div class="widget-main">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr class="active">
                        <th width="5%">#</th>
                        <th width="15%">Work Order</th>
                        <th width="10%">Tipe</th>
                        <th width="15%">Kode</th>
                        <th>Nama Item</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Harga</th>
                        <th width="12%" class="text-right">Total</th>
                        <th width="10%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no_h = 0; 
                    while($h = mysqli_fetch_array($result_wo_history)) { 
                        $no_h++; 
                        $tipe_badge = ($h['tipe'] == 'barang') ? 
                            '<span class="label label-info"><i class="fa fa-cube"></i> Barang</span>' : 
                            '<span class="label label-purple"><i class="fa fa-wrench"></i> Jasa</span>'; 
                        $wo_display = $h['nama_wo'] ? $h['nama_wo'] : $h['kode_wo']; 
                        $status_class = ($h['status_approval'] == 'disetujui') ? 'label-success' : 'label-danger'; 
                        $status_text = ($h['status_approval'] == 'disetujui') ? 'Disetujui' : 'Ditolak'; 
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no_h; ?></td>
                        <td>
                            <small class="text-muted"><?php echo htmlspecialchars($h['kode_wo']); ?></small><br>
                            <strong><?php echo htmlspecialchars($wo_display); ?></strong>
                        </td>
                        <td class="text-center"><?php echo $tipe_badge; ?></td>
                        <td><code><?php echo htmlspecialchars($h['kode_item']); ?></code></td>
                        <td><?php echo htmlspecialchars($h['nama_item']); ?></td>
                        <td class="text-center"><?php echo (int)$h['quantity']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($h['harga_satuan'], 0, ',', '.'); ?></td>
                        <td class="text-right"><strong>Rp <?php echo number_format($h['total'], 0, ',', '.'); ?></strong></td>
                        <td class="text-center">
                            <span class="label <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
 </div>

<div class="space-14"></div>
<?php } ?>

<!-- ============================================ -->
<!-- SECTION: Daftar Temuan & Penawaran Part -->
<!-- ============================================ -->
<div class="widget-box widget-color-blue2">
    <div class="widget-header widget-header-small">
        <h5 class="widget-title">
            <i class="ace-icon fa fa-list-alt"></i>
            Daftar Temuan & Penawaran Part
        </h5>
        <div class="widget-toolbar">
            <span class="badge badge-info">
                <?php
                $count_temuan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_temuan WHERE no_service='$no_service'");
                $total_temuan = mysqli_fetch_array($count_temuan)['total'];
                echo $total_temuan;
                ?> Temuan
            </span>
            <a href="#" data-action="collapse">
                <i class="ace-icon fa fa-chevron-down"></i>
            </a>
        </div>
    </div>

    <div class="widget-body">
        <div class="widget-main">

            <?php
            // Query temuan dengan penawaran part
            $sql_temuan = "SELECT
                            t.*,
                            k.keluhan,
                            COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display,
                            mt.kategori as kategori_temuan,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id) as total_penawaran,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='pending') as pending_count,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='disetujui') as approved_count,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='ditolak') as rejected_count
                          FROM tbservis_temuan t
                          LEFT JOIN tbservis_keluhan_status k ON t.keluhan_id = k.id
                          LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                          WHERE t.no_service = '$no_service'
                          ORDER BY t.created_at DESC";

            $result_temuan = mysqli_query($koneksi, $sql_temuan);
            $no_temuan = 0;

            if(mysqli_num_rows($result_temuan) > 0) {
                while($temuan = mysqli_fetch_array($result_temuan)) {
                    $no_temuan++;

                    // Determine urgensi class
                    $urgensi_class = 'temuan-urgent-' . $temuan['tingkat_urgensi'];
                    $urgensi_icon = match($temuan['tingkat_urgensi']) {
                        'tinggi' => '🟠',
                        'sedang' => '🟡',
                        'rendah' => '🟢',
                        default => '⚪'
                    };

                    // Status badge
                    $status_badge_class = match($temuan['status_temuan']) {
                        'ditemukan' => 'label-info',
                        'ditawarkan' => 'label-warning',
                        'disetujui' => 'label-primary',
                        'ditolak' => 'label-danger',
                        'selesai' => 'label-success',
                        default => 'label-default'
                    };

                    $status_text = match($temuan['status_temuan']) {
                        'ditemukan' => 'Ditemukan',
                        'ditawarkan' => 'Ditawarkan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'selesai' => 'Selesai',
                        default => $temuan['status_temuan']
                    };
            ?>

            <!-- Temuan Card -->
            <div class="temuan-card <?php echo $urgensi_class; ?>">
                <div class="temuan-card-header" style="background-color: #f5f5f5;">
                    <div class="row">
                        <div class="col-xs-8">
                            <?php echo $urgensi_icon; ?>
                            <strong>#<?php echo $no_temuan; ?>: <?php echo htmlspecialchars($temuan['nama_temuan_display']); ?></strong>
                            <?php if($temuan['kategori_temuan']) { ?>
                                <span class="label label-default"><?php echo $temuan['kategori_temuan']; ?></span>
                            <?php } ?>
                            <span class="label <?php echo $status_badge_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                        <div class="col-xs-4 text-right">
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($temuan['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="temuan-card-body">
                    <div class="row">
                        <!-- Kolom Kiri: Info Temuan -->
                        <div class="col-xs-12 col-sm-6">
                            <table class="table table-condensed table-borderless">
                                <tr>
                                    <td width="35%"><i class="ace-icon fa fa-exclamation-circle"></i> Keluhan:</td>
                                    <td><strong><?php echo htmlspecialchars($temuan['keluhan']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td><i class="ace-icon fa fa-wrench"></i> Jenis Perbaikan:</td>
                                    <td>
                                        <?php
                                        if($temuan['jenis_perbaikan'] == 'setting') {
                                            echo '⚙️ Hanya Setting/Servis';
                                        } else {
                                            echo '🛒 Penggantian Part';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php if($temuan['mekanik_name']) { ?>
                                <tr>
                                    <td><i class="ace-icon fa fa-user"></i> Ditemukan oleh:</td>
                                    <td><?php echo htmlspecialchars($temuan['mekanik_name']); ?></td>
                                </tr>
                                <?php } ?>
                                <?php if($temuan['estimasi_biaya'] > 0) { ?>
                                <tr>
                                    <td><i class="ace-icon fa fa-money"></i> Estimasi Biaya:</td>
                                    <td><strong>Rp <?php echo number_format($temuan['estimasi_biaya'], 0, ',', '.'); ?></strong></td>
                                </tr>
                                <?php } ?>
                            </table>

                            <?php if($temuan['deskripsi_temuan']) { ?>
                            <div class="alert alert-info" style="margin-top: 10px;">
                                <i class="ace-icon fa fa-info-circle"></i>
                                <strong>Deskripsi:</strong><br>
                                <?php echo nl2br(htmlspecialchars($temuan['deskripsi_temuan'])); ?>
                            </div>
                            <?php } ?>

                            <?php if($temuan['foto_temuan']) { ?>
                            <div style="margin-top: 10px;">
                                <a href="../<?php echo $temuan['foto_temuan']; ?>" target="_blank" class="btn btn-xs btn-info">
                                    <i class="ace-icon fa fa-camera"></i> Lihat Foto Temuan
                                </a>
                            </div>
                            <?php } ?>
                        </div>

                        <!-- Kolom Kanan: Penawaran Part -->
                        <div class="col-xs-12 col-sm-6">
                            <h6 class="header smaller blue">
                                <i class="ace-icon fa fa-shopping-cart"></i>
                                Penawaran Part (<?php echo $temuan['total_penawaran']; ?>)
                            </h6>

                            <?php
                            // Query penawaran part untuk temuan ini
                            $sql_penawaran = "SELECT * FROM tbservis_penawaran_part
                                             WHERE temuan_id = '{$temuan['id']}'
                                             ORDER BY suggestion_priority ASC, created_at ASC";
                            $result_penawaran = mysqli_query($koneksi, $sql_penawaran);

                            if(mysqli_num_rows($result_penawaran) > 0) {
                                while($penawaran = mysqli_fetch_array($result_penawaran)) {
                                    $penawaran_class = 'penawaran-' . $penawaran['status_penawaran'];
                                    $status_icon = match($penawaran['status_penawaran']) {
                                        'pending' => '⏳',
                                        'disetujui' => '✅',
                                        'ditolak' => '❌',
                                        default => '❓'
                                    };
                            ?>

                            <div class="penawaran-item <?php echo $penawaran_class; ?>">
                                <div class="row">
                                    <div class="col-xs-8">
                                        <?php echo $status_icon; ?>
                                        <strong><?php echo htmlspecialchars($penawaran['nama_barang']); ?></strong>
                                        <?php if($penawaran['suggestion_priority'] == 1) { ?>
                                            <span class="badge badge-success">REKOMENDASI</span>
                                        <?php } ?>
                                        <br>
                                        <small>
                                            Kode: <?php echo $penawaran['kode_barang']; ?> |
                                            Qty: <?php echo $penawaran['quantity']; ?> |
                                            @ Rp <?php echo number_format($penawaran['harga_satuan'], 0, ',', '.'); ?>
                                        </small>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <strong class="blue">
                                            Rp <?php echo number_format($penawaran['total_harga'], 0, ',', '.'); ?>
                                        </strong>
                                        <br>
                                        <?php if($penawaran['status_penawaran'] == 'pending') { ?>
                                        <div class="btn-group btn-group-xs">
                                            <button type="button" class="btn btn-success"
                                                    onclick="approvePenawaran(<?php echo $penawaran['id']; ?>)"
                                                    title="Setujui">
                                                <i class="ace-icon fa fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger"
                                                    onclick="rejectPenawaran(<?php echo $penawaran['id']; ?>)"
                                                    title="Tolak">
                                                <i class="ace-icon fa fa-times"></i>
                                            </button>
                                        </div>
                                        <?php } else { ?>
                                        <?php 
                                            $status_badge_class = match($penawaran['status_penawaran']) {
                                                'pending' => 'label-warning',
                                                'disetujui' => 'label-success',
                                                'ditolak' => 'label-danger',
                                                default => 'label-default'
                                            };
                                        ?>
                                        <span class="label label-sm <?php echo $status_badge_class; ?>">
                                            <?php echo strtoupper($penawaran['status_penawaran']); ?>
                                        </span>
                                        <?php } ?>
                                    </div>
                                </div>

                                <?php if($penawaran['status_penawaran'] == 'ditolak' && $penawaran['alasan_tolak']) { ?>
                                <div class="alert alert-danger alert-sm" style="margin-top: 5px; margin-bottom: 0;">
                                    <small>
                                        <strong>Alasan ditolak:</strong>
                                        <?php
                                        $alasan_text = match($penawaran['alasan_tolak']) {
                                            'customer_tidak_mau' => 'Customer tidak mau',
                                            'stok_bengkel_kosong' => 'Stok bengkel kosong',
                                            'stok_supplier_kosong' => 'Stok supplier kosong',
                                            'harga_tidak_cocok' => 'Harga tidak cocok',
                                            'lainnya' => 'Lainnya',
                                            default => $penawaran['alasan_tolak']
                                        };
                                        echo $alasan_text;
                                        ?>
                                        <?php if($penawaran['keterangan_tolak']) { ?>
                                            - <?php echo htmlspecialchars($penawaran['keterangan_tolak']); ?>
                                        <?php } ?>
                                    </small>
                                </div>
                                <?php } ?>
                            </div>

                            <?php
                                } // end while penawaran
                            } else {
                                if($temuan['jenis_perbaikan'] == 'penggantian_part') {
                            ?>
                                <div class="alert alert-warning">
                                    <i class="ace-icon fa fa-info-circle"></i>
                                    Belum ada part yang ditawarkan untuk temuan ini.
                                </div>
                            <?php
                                } else {
                            ?>
                                <div class="alert alert-success">
                                    <i class="ace-icon fa fa-check"></i>
                                    Temuan ini hanya perlu setting/servis, tidak perlu penggantian part.
                                </div>
                            <?php
                                }
                            }
                            ?>

                            <!-- Summary -->
                            <?php if($temuan['total_penawaran'] > 0) { ?>
                            <div class="well well-sm" style="margin-top: 10px; margin-bottom: 0;">
                                <div class="row">
                                    <div class="col-xs-6 text-center">
                                        <small>Pending</small><br>
                                        <strong class="orange"><?php echo $temuan['pending_count']; ?></strong>
                                    </div>
                                    <div class="col-xs-6 text-center">
                                        <small>Disetujui</small><br>
                                        <strong class="green"><?php echo $temuan['approved_count']; ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row" style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 10px;">
                        <div class="col-xs-12 text-right">
                            <?php if($temuan['jenis_perbaikan'] == 'penggantian_part') { ?>
                            <button type="button" class="btn btn-xs btn-primary"
                                    onclick="addMoreParts(<?php echo $temuan['id']; ?>)">
                                <i class="ace-icon fa fa-plus"></i> Tambah Part Lain
                            </button>
                            <?php } ?>

                            <button type="button" class="btn btn-xs btn-info"
                                    onclick="editTemuan(<?php echo $temuan['id']; ?>)">
                                <i class="ace-icon fa fa-edit"></i> Edit Temuan
                            </button>

                            <button type="button" class="btn btn-xs btn-danger"
                                    onclick="deleteTemuan(<?php echo $temuan['id']; ?>)">
                                <i class="ace-icon fa fa-trash-o"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                } // end while temuan
            } else {
            ?>

            <!-- Empty State -->
            <div class="alert alert-warning text-center">
                <i class="ace-icon fa fa-info-circle fa-3x"></i>
                <h5>Belum Ada Temuan</h5>
                <p>Belum ada temuan hasil pengecekan untuk servis ini. Silakan input temuan di form di atas.</p>
            </div>

            <?php
            }
            ?>

        </div>
    </div>
</div>

<?php
// ========================================
// SECTION: PENAWARAN UMUM (tanpa temuan)
// ========================================
$sql_penawaran_umum = "SELECT * FROM tbservis_penawaran_part 
                       WHERE no_service = '$no_service' 
                       AND (temuan_id IS NULL OR temuan_id = 0)
                       ORDER BY created_at DESC";
$result_penawaran_umum = mysqli_query($koneksi, $sql_penawaran_umum);
$total_penawaran_umum = $result_penawaran_umum ? mysqli_num_rows($result_penawaran_umum) : 0;

if($total_penawaran_umum > 0) {
?>
<div class="space-14"></div>

<div class="widget-box widget-color-purple">
    <div class="widget-header widget-header-small">
        <h5 class="widget-title">
            <i class="ace-icon fa fa-shopping-basket"></i>
            Penawaran Part Umum (Tanpa Temuan Terkait)
        </h5>
        <div class="widget-toolbar">
            <span class="badge badge-purple"><?php echo $total_penawaran_umum; ?> Item</span>
            <a href="#" data-action="collapse">
                <i class="ace-icon fa fa-chevron-up"></i>
            </a>
        </div>
    </div>
    <div class="widget-body">
        <div class="widget-main">
            <div class="alert alert-info">
                <i class="ace-icon fa fa-info-circle"></i>
                Item berikut ditambahkan langsung tanpa dikaitkan ke temuan tertentu.
            </div>
            
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr class="info">
                        <th width="5%">#</th>
                        <th width="15%">Kode Part</th>
                        <th>Nama Part</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Harga</th>
                        <th width="12%" class="text-right">Total</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no_umum = 0;
                    $grand_total_umum = 0;
                    while($penawaran = mysqli_fetch_array($result_penawaran_umum)) {
                        $no_umum++;
                        $grand_total_umum += $penawaran['total_harga'];
                        
                        $status_class = match($penawaran['status_penawaran']) {
                            'pending' => 'label-warning',
                            'disetujui' => 'label-success',
                            'ditolak' => 'label-danger',
                            default => 'label-default'
                        };
                        $status_icon = match($penawaran['status_penawaran']) {
                            'pending' => '⏳',
                            'disetujui' => '✅',
                            'ditolak' => '❌',
                            default => '❓'
                        };
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no_umum; ?></td>
                        <td><code><?php echo htmlspecialchars($penawaran['kode_barang']); ?></code></td>
                        <td><?php echo htmlspecialchars($penawaran['nama_barang']); ?></td>
                        <td class="text-center"><?php echo $penawaran['quantity']; ?></td>
                        <td class="text-right">Rp <?php echo number_format($penawaran['harga_satuan'], 0, ',', '.'); ?></td>
                        <td class="text-right"><strong>Rp <?php echo number_format($penawaran['total_harga'], 0, ',', '.'); ?></strong></td>
                        <td class="text-center">
                            <span class="label <?php echo $status_class; ?>">
                                <?php echo $status_icon . ' ' . ucfirst($penawaran['status_penawaran']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if($penawaran['status_penawaran'] == 'pending') { ?>
                            <div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-success"
                                        onclick="approvePenawaran(<?php echo $penawaran['id']; ?>)"
                                        title="Setujui">
                                    <i class="ace-icon fa fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-danger"
                                        onclick="rejectPenawaran(<?php echo $penawaran['id']; ?>)"
                                        title="Tolak">
                                    <i class="ace-icon fa fa-times"></i>
                                </button>
                            </div>
                            <?php } else { ?>
                            <span class="text-muted">-</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="info">
                        <td colspan="5" class="text-right"><strong>Total Penawaran Umum:</strong></td>
                        <td class="text-right"><strong class="purple">Rp <?php echo number_format($grand_total_umum, 0, ',', '.'); ?></strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php } // end if penawaran umum ?>

<script>
function approvePenawaran(penawaranId) {
    if(confirm('Setujui penawaran part ini?\n\nPart akan ditambahkan ke daftar item barang servis.')) {
        window.location = 'servis-penawaran-approve.php?id=' + penawaranId + '&snoserv=<?php echo $no_service; ?>';
    }
}

function rejectPenawaran(penawaranId) {
    // Show modal untuk pilih alasan tolak
    // TODO: Implementasi modal
    var alasan = prompt('Alasan penolakan:\n\n1. Customer tidak mau\n2. Stok kosong\n3. Harga tidak cocok\n4. Lainnya\n\nKetik nomor (1-4):');

    if(alasan) {
        var alasanMap = {
            '1': 'customer_tidak_mau',
            '2': 'stok_bengkel_kosong',
            '3': 'harga_tidak_cocok',
            '4': 'lainnya'
        };

        var alasanCode = alasanMap[alasan] || 'lainnya';
        var keterangan = prompt('Keterangan tambahan (optional):');

        window.location = 'servis-penawaran-reject.php?id=' + penawaranId +
                         '&alasan=' + alasanCode +
                         '&ket=' + encodeURIComponent(keterangan || '') +
                         '&snoserv=<?php echo $no_service; ?>';
    }
}

function addMoreParts(temuanId) {
    // Set temuan ID in the modal
    $('#modal_add_part_temuan_id').val(temuanId);
    
    // Load temuan info
    $.ajax({
        url: 'servis-input-reguler.php?action=get_temuan_info&id=' + temuanId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#modal_add_part_temuan_name').text(response.data.nama_temuan);
            }
        }
    });
    
    // Clear form
    $('#formAddPartToTemuan')[0].reset();
    $('#add_part_kode').val('');
    $('#add_part_nama').val('');
    $('#add_part_harga').val('');
    $('#add_part_qty').val(1);
    $('#add_part_total_display').val('Rp 0');
    
    // Show modal
    $('#modalAddPartToTemuan').modal('show');
}

function editTemuan(temuanId) {
    // Load temuan data via AJAX
    $.ajax({
        url: 'servis-input-reguler.php?action=get_temuan_info&id=' + temuanId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var data = response.data;
                $('#edit_temuan_id').val(data.id);
                if ($('#edit_kode_temuan').length) {
                    $('#edit_kode_temuan').val(data.kode_temuan || '');
                }
                if ($('#edit_nama_temuan_display').length) {
                    $('#edit_nama_temuan_display').val(data.nama_temuan || '');
                }
                if ($('#edit_temuan_nama').length) {
                    $('#edit_temuan_nama').val(data.nama_temuan || '');
                }
                $('#edit_temuan_deskripsi').val(data.deskripsi_temuan || '');
                $('#edit_temuan_jenis').val(data.jenis_perbaikan);
                $('#edit_temuan_urgensi').val(data.tingkat_urgensi);
                $('#edit_temuan_estimasi').val(data.estimasi_biaya || 0);
                $('#edit_temuan_status').val(data.status_temuan);
                
                $('#modalEditTemuan').modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error loading temuan data');
        }
    });
}

function deleteTemuan(temuanId) {
    if(confirm('Hapus temuan ini?\n\nSemua penawaran part yang terkait juga akan dihapus.')) {
        window.location = 'servis-temuan-delete.php?id=' + temuanId + '&snoserv=<?php echo $no_service; ?>';
    }
}

// Calculate total for add part modal
function calcAddPartTotal() {
    var h = parseInt($('#add_part_harga').val() || 0);
    var q = parseInt($('#add_part_qty').val() || 0);
    $('#add_part_total_display').val('Rp ' + (h * q).toLocaleString('id-ID'));
}

$(document).ready(function() {
    $('#add_part_harga, #add_part_qty').on('input change', calcAddPartTotal);
});
</script>

<!-- ========================================== -->
<!-- MODAL: Tambah Part ke Temuan -->
<!-- ========================================== -->
<div class="modal fade" id="modalAddPartToTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formAddPartToTemuan" method="POST" action="servis-input-reguler.php">
                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                <input type="hidden" name="temuan_id" id="modal_add_part_temuan_id" value="">
                <input type="hidden" name="btnaddpenawaran" value="1">
                
                <div class="modal-header bg-primary">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-plus-circle white"></i>
                        Tambah Part untuk Temuan
                    </h4>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-exclamation-circle"></i>
                        <strong>Temuan:</strong> <span id="modal_add_part_temuan_name">-</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Kode Part <span class="red">*</span></label>
                                <input type="text" class="form-control" id="add_part_kode" name="kode_barang" 
                                       placeholder="Kode part" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Nama Part <span class="red">*</span></label>
                                <input type="text" class="form-control" id="add_part_nama" name="nama_barang" 
                                       placeholder="Nama part" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Harga Satuan <span class="red">*</span></label>
                                <input type="number" class="form-control text-right" id="add_part_harga" 
                                       name="harga_satuan" placeholder="0" min="0" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Quantity <span class="red">*</span></label>
                                <input type="number" class="form-control text-center" id="add_part_qty" 
                                       name="quantity" value="1" min="1" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Total</label>
                                <input type="text" class="form-control text-right" id="add_part_total_display" 
                                       value="Rp 0" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-warning btn-sm" 
                                onclick="$('#modalInputCustom').modal('show'); $('#modalAddPartToTemuan').modal('hide');">
                            <i class="ace-icon fa fa-plus-circle"></i> Input Barang Custom
                        </button>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ace-icon fa fa-save"></i> Simpan Penawaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Edit Temuan -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formEditTemuan" method="POST" action="servis-input-reguler.php">
                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                <input type="hidden" name="temuan_id" id="edit_temuan_id" value="">
                <input type="hidden" name="btnedittemuan" value="1">
                <input type="hidden" name="kode_temuan" id="edit_kode_temuan" value="">
                
                <div class="modal-header bg-info">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-edit white"></i>
                        Edit Temuan
                    </h4>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Temuan <span class="red">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="edit_nama_temuan_display" 
                                           placeholder="Pilih temuan dari master..." readonly>
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-primary" onclick="$('#modalSearchTemuan').modal('show');">
                                            <i class="ace-icon fa fa-search"></i> Pilih
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Deskripsi Detail</label>
                                <textarea class="form-control" id="edit_temuan_deskripsi" name="deskripsi_temuan" 
                                          rows="3" placeholder="Jelaskan detail kondisi/kerusakan yang ditemukan..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Jenis Perbaikan <span class="red">*</span></label>
                                <select class="form-control" id="edit_temuan_jenis" name="jenis_perbaikan" required>
                                    <option value="setting">Hanya Setting/Servis Saja</option>
                                    <option value="penggantian_part">Penggantian Part</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Tingkat Urgensi <span class="red">*</span></label>
                                <select class="form-control" id="edit_temuan_urgensi" name="tingkat_urgensi" required>
                                    <option value="rendah">🟢 Rendah</option>
                                    <option value="sedang">🟡 Sedang (Perlu segera)</option>
                                    <option value="tinggi">🟠 Tinggi</option>
                                    <option value="kritis">🔴 Kritis</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Estimasi Biaya</label>
                                <input type="number" class="form-control text-right" id="edit_temuan_estimasi" 
                                       name="estimasi_biaya" placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Status Temuan</label>
                                <select class="form-control" id="edit_temuan_status" name="status_temuan">
                                    <option value="ditemukan">Ditemukan</option>
                                    <option value="ditawarkan">Ditawarkan</option>
                                    <option value="disetujui">Disetujui</option>
                                    <option value="ditolak">Ditolak</option>
                                    <option value="selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="ace-icon fa fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

