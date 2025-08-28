<?php
// Template untuk Daftar SPK (Surat Perintah Kerja) - Keluhan dan Work Order dalam satu halaman
?>

<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
            <h4 class="header blue">
                <i class="ace-icon fa fa-list-alt"></i>
                Daftar SPK (Surat Perintah Kerja)
            </h4>
            
            <!-- Header Info -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-motorcycle"></i>
                        <strong>Nopol: <?php echo htmlspecialchars($no_polisi); ?></strong>
                        <br>Kelola keluhan dan work order dalam satu halaman SPK
                    </div>
                </div>
            </div>
            
            <!-- Form Input Section -->
            <div class="row">
                <!-- Input Keluhan & Work Order Section (KIRI) -->
                <div class="col-xs-12 col-sm-6">
                    <div class="widget-box widget-color-green">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-plus-circle"></i>
                                Input Keluhan & Work Order
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <!-- Form Input Keluhan -->
                                <h6 class="header orange smaller">
                                    <i class="ace-icon fa fa-exclamation-triangle"></i> Input Keluhan
                                </h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="70%">
                                            <label>Keluhan :</label>
                                            <input type="text" class="form-control input-sm" 
                                            id="txtkeluhan" name="txtkeluhan" 
                                            placeholder="Masukkan keluhan" autocomplete="off" />
                                        </td>
                                        <td width="30%">
                                            <label>&nbsp;</label><br>
                                            <button type="button" class="btn btn-info btn-sm btn-block" onclick="showModalSearchKeluhan()">
                                                <i class="ace-icon fa fa-search"></i> Cari
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button class="btn btn-warning btn-sm btn-block" type="submit" 
                                            id="btnaddkeluhan" name="btnaddkeluhan">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Keluhan ke SPK
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Form Input Work Order -->
                                <h6 class="header blue smaller">
                                    <i class="ace-icon fa fa-cogs"></i> Input Work Order
                                </h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="70%">
                                            <label>Kode Work Order :</label>
                                            <input type="text" class="form-control input-sm" 
                                            id="txtcariwo" name="txtcariwo" 
                                            value="<?php echo $txtcariwo; ?>" 
                                            placeholder="Masukkan kode WO" autocomplete="off" />
                                        </td>
                                        <td width="30%">
                                            <label>&nbsp;</label><br>
                                            <button class="btn btn-primary btn-sm btn-block" type="submit" 
                                            id="btncariwo" name="btncariwo">
                                                <i class="ace-icon fa fa-search"></i> Cari
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <label>Nama Work Order :</label>
                                            <input type="text" class="form-control input-sm" 
                                            value="<?php echo isset($txtnamawo) ? htmlspecialchars($txtnamawo) : ''; ?>" 
                                            readonly="true" style="background-color: #f5f5f5;" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button class="btn btn-success btn-sm btn-block" type="submit" 
                                            id="btnaddworkorder" name="btnaddworkorder">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Work Order ke SPK
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daftar SPK Section (KANAN) -->
                <div class="col-xs-12 col-sm-6">
                    <?php 
                        // Hitung total SPK
                        $no_keluhan = 0;
                        $sql_keluhan_count = mysqli_query($koneksi,"SELECT COUNT(*) as total FROM tbservis_keluhan_status WHERE no_service='$no_service'");
                        $result_keluhan = mysqli_fetch_array($sql_keluhan_count);
                        $no_keluhan = $result_keluhan['total'];
                        
                        $no_wo = 0;
                        $sql_wo_count = mysqli_query($koneksi,"SELECT COUNT(*) as total FROM tbservis_workorder WHERE no_service='$no_service'");
                        $result_wo = mysqli_fetch_array($sql_wo_count);
                        $no_wo = $result_wo['total'];
                        
                        $total_spk = $no_keluhan + $no_wo;
                    ?>
                    
                    <div class="widget-box widget-color-purple">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-list"></i>
                                Daftar SPK untuk Nopol: <?php echo htmlspecialchars($no_polisi); ?>
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <div class="alert alert-info">
                                    <div class="row">
                                        <div class="col-xs-8">
                                            <strong>Total: <?php echo $total_spk; ?> SPK</strong>
                                            <br><small>(<?php echo $no_keluhan; ?> keluhan + <?php echo $no_wo; ?> work order)</small>
                                        </div>
                                        <div class="col-xs-4 text-right">
                                            <button type="button" class="btn btn-xs btn-info" onclick="refreshSPK()" title="Refresh Daftar SPK">
                                                <i class="ace-icon fa fa-refresh"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if($total_spk > 0) { ?>
                                <ol class="spk-list" style="list-style: none; padding-left: 0; counter-reset: spk-counter;">
                                    <?php 
                                        $counter = 1;
                                        
                                        // Tampilkan keluhan
                                        $sql_keluhan_list = mysqli_query($koneksi,"SELECT keluhan FROM tbservis_keluhan_status WHERE no_service='$no_service' ORDER BY id ASC");
                                        while ($tampil = mysqli_fetch_array($sql_keluhan_list)) {
                                            echo "<li style='margin-bottom: 10px; padding: 8px; border-left: 4px solid #f39c12; background-color: #fef9e7;'>";
                                            echo "<span style='font-weight: bold; color: #333;'>";
                                            echo "<span style='display: inline-block; width: 30px; color: #f39c12; font-weight: bold;'>" . $counter . ".</span>";
                                            echo htmlspecialchars($tampil['keluhan']);
                                            echo "</span>";
                                            echo "</li>";
                                            $counter++;
                                        }
                                        
                                        // Tampilkan work order
                                        $sql_wo_list = mysqli_query($koneksi,"SELECT wh.nama_wo FROM tbservis_workorder sw LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo WHERE sw.no_service='$no_service' ORDER BY sw.id ASC");
                                        while ($tampil = mysqli_fetch_array($sql_wo_list)) {
                                            echo "<li style='margin-bottom: 10px; padding: 8px; border-left: 4px solid #3498db; background-color: #ebf3fd;'>";
                                            echo "<span style='font-weight: bold; color: #333;'>";
                                            echo "<span style='display: inline-block; width: 30px; color: #3498db; font-weight: bold;'>" . $counter . ".</span>";
                                            echo htmlspecialchars($tampil['nama_wo']);
                                            echo "</span>";
                                            echo "</li>";
                                            $counter++;
                                        }
                                    ?>
                                </ol>
                                <?php } else { ?>
                                <div class="alert alert-warning">
                                    <i class="ace-icon fa fa-info-circle"></i>
                                    Belum ada SPK untuk Nopol: <strong><?php echo htmlspecialchars($no_polisi); ?></strong>
                                    <br><small>Tambahkan keluhan atau work order di sebelah kiri</small>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pengerjaan Manual (Opsional) -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="widget-box widget-color-green">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-wrench"></i>
                                Pengerjaan Manual (Opsional)
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="40%">
                                            <label>Item Pengerjaan :</label>
                                            <input type="text" class="form-control input-sm" 
                                            id="txtitempengerjaan" name="txtitempengerjaan" 
                                            placeholder="Masukkan item pengerjaan" autocomplete="off" />
                                        </td>
                                        <td width="30%">
                                            <label>Mekanik :</label>
                                            <select name="cbomekanik" id="cbomekanik" class="form-control input-sm">
                                                <option value="">- Pilih Mekanik -</option>
                                                <?php
                                                    if(isset($koneksi)) {
                                                        try {
                                                            $sql="SELECT nomekanik, nama FROM tblmekanik 
                                                                  WHERE nama<>'-' 
                                                                  ORDER BY nama ASC";
                                                            $sql_row=mysqli_query($koneksi,$sql);
                                                            if($sql_row) {
                                                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                                                ?>
                                                <option value="<?php echo htmlspecialchars($sql_res["nomekanik"]); ?>">
                                                    <?php echo htmlspecialchars($sql_res["nama"]); ?>
                                                </option>
                                                <?php 
                                                                }
                                                            }
                                                        } catch (Exception $e) {
                                                            echo '<option value="">Error loading data</option>';
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </td>
                                        <td width="30%">
                                            <label>&nbsp;</label><br>
                                            <button class="btn btn-info btn-sm btn-block" type="submit" 
                                            id="btnaddpengerjaan" name="btnaddpengerjaan">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Pengerjaan
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Daftar Pengerjaan -->
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="success">
                                            <th width="5%" class="center">No</th>
                                            <th width="60%">Item Pengerjaan</th>
                                            <th width="25%">Mekanik</th>
                                            <th width="10%" class="center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no_pengerjaan = 0;
                                            $sql_pengerjaan = mysqli_query($koneksi,"SELECT 
                                                                p.id, p.item_pengerjaan, m.nama as nama_mekanik
                                                                FROM tbservis_pengerjaan p
                                                                LEFT JOIN tblmekanik m ON p.kd_mekanik = m.nomekanik
                                                                WHERE p.no_service='$no_service'
                                                                ORDER BY p.id ASC");
                                            while ($tampil = mysqli_fetch_array($sql_pengerjaan)) {
                                                $no_pengerjaan++;
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no_pengerjaan; ?></td>
                                            <td><?php echo htmlspecialchars($tampil['item_pengerjaan']); ?></td>
                                            <td><?php echo htmlspecialchars($tampil['nama_mekanik']); ?></td>
                                            <td class="center">
                                                <a class="red" data-rel="tooltip" title="Hapus" 
                                                href="pengerjaan-hapus.php?pengerjaan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                                onclick="return confirm('Hapus pengerjaan ini?')">
                                                <i class="ace-icon fa fa-trash-o"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        
                                        <?php if($no_pengerjaan == 0) { ?>
                                        <tr>
                                            <td colspan="4" class="center">
                                                <em>Belum ada pengerjaan manual</em>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modal Search Keluhan -->
<?php 
try {
    if(file_exists('_template/modal-search-keluhan.php')) {
        include '_template/modal-search-keluhan.php';
    }
} catch (Exception $e) {
    // Silent error handling
}
?>

<script type="text/javascript">
function showModalSearchKeluhan() {
    // Implementasi modal search keluhan
    if(typeof $('#modal-search-keluhan').modal === 'function') {
        $('#modal-search-keluhan').modal('show');
    } else {
        alert('Modal search keluhan belum tersedia');
    }
}

function selectKeluhan(keluhan) {
    // Set keluhan to the input field
    var keluhanInput = document.querySelector('input[name="txtkeluhan"]');
    if (keluhanInput) {
        keluhanInput.value = keluhan;
    }
    $('#modal-search-keluhan').modal('hide');
}

function refreshSPK() {
    // Refresh halaman untuk memperbarui daftar SPK
    window.location.reload();
}
</script>