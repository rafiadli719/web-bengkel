<?php
// Template untuk input keluhan service jemput
?>

<table class="table table-bordered">
    <tr>
        <td width="60%">
            <label>Keluhan :</label>
            <div class="row">
                <div class="col-xs-8 col-sm-10">
                    <input type="text" class="form-control input-sm" 
                    id="txtkeluhan" name="txtkeluhan" 
                    placeholder="Masukkan keluhan atau klik tombol cari untuk memilih dari master data" 
                    autocomplete="off" />
                </div>
                <div class="col-xs-4 col-sm-2">
                    <button type="button" class="btn btn-info btn-sm" onclick="showModalSearchKeluhan()">
                        <i class="ace-icon fa fa-search"></i> Cari
                    </button>
                </div>
            </div>
        </td>
        <td width="20%">
            <label>&nbsp;</label><br>
            <button class="btn btn-primary btn-sm btn-block" type="submit" 
            id="btnaddkeluhan" name="btnaddkeluhan">
                + Keluhan
            </button>
        </td>
        <td width="20%">
            <label>Pengerjaan :</label>
            <div class="row">
                <div class="col-xs-8 col-sm-8">
                    <input type="text" class="form-control input-sm" 
                    id="txtitempengerjaan" name="txtitempengerjaan" 
                    placeholder="Item pengerjaan" autocomplete="off" />
                </div>
                <div class="col-xs-4 col-sm-4">
                    <button class="btn btn-success btn-sm" type="submit" 
                    id="btnaddpengerjaan" name="btnaddpengerjaan">
                        + Kerja
                    </button>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <label>Pilih Mekanik :</label>
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
        <td>&nbsp;</td>
    </tr>
</table>

<!-- Daftar Keluhan yang sudah ditambahkan -->
<table class="table table-bordered table-striped">
    <thead>
        <tr class="info">
            <th width="5%" class="center">No</th>
            <th width="40%">Keluhan</th>
            <th width="20%">Status</th>
            <th width="30%">Keterangan</th>
            <th width="5%" class="center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $no = 0;
            $sql = mysqli_query($koneksi,"SELECT 
                                            id, keluhan, status_pengerjaan, 
                                            keterangan_tidak_selesai
                                            FROM tbservis_keluhan_status
                                            WHERE no_service='$no_service'
                                            ORDER BY id ASC");
            while ($tampil = mysqli_fetch_array($sql)) {
                $no++;
                $status_color = '';
                $status_text = '';
                
                switch($tampil['status_pengerjaan']) {
                    case 'datang':
                        $status_color = 'label-warning';
                        $status_text = 'Datang';
                        break;
                    case 'diproses':
                        $status_color = 'label-info';
                        $status_text = 'Di Proses';
                        break;
                    case 'selesai':
                        $status_color = 'label-success';
                        $status_text = 'Selesai';
                        break;
                    case 'tidak_selesai':
                        $status_color = 'label-danger';
                        $status_text = 'Tidak Selesai';
                        break;
                }
        ?>
        <tr>
            <td class="center"><?php echo $no; ?></td>
            <td><?php echo htmlspecialchars($tampil['keluhan']); ?></td>
            <td>
                <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                <br><br>
                <!-- Form untuk update status -->
                <form method="post" style="display:inline;">
                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
                    <input type="hidden" name="txtcariwo" value="<?php echo $txtcariwo; ?>"/>
                    <input type="hidden" name="txtcarisrv" value="<?php echo $txtcarisrv; ?>"/>
                    <input type="hidden" name="txtcaribrg" value="<?php echo $txtcaribrg; ?>"/>
                    <input type="hidden" name="keluhan_id" value="<?php echo $tampil['id']; ?>"/>
                    <select name="status_keluhan" class="form-control input-xs" style="width:100%; margin-bottom:5px;">
                        <option value="datang" <?php echo ($tampil['status_pengerjaan']=='datang')?'selected':''; ?>>Datang</option>
                        <option value="diproses" <?php echo ($tampil['status_pengerjaan']=='diproses')?'selected':''; ?>>Di Proses</option>
                        <option value="selesai" <?php echo ($tampil['status_pengerjaan']=='selesai')?'selected':''; ?>>Selesai</option>
                        <option value="tidak_selesai" <?php echo ($tampil['status_pengerjaan']=='tidak_selesai')?'selected':''; ?>>Tidak Selesai</option>
                    </select>
                    <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-xs btn-primary">Update</button>
                </form>
            </td>
            <td>
                <?php if($tampil['status_pengerjaan'] == 'tidak_selesai') { ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
                        <input type="hidden" name="txtcariwo" value="<?php echo $txtcariwo; ?>"/>
                        <input type="hidden" name="txtcarisrv" value="<?php echo $txtcarisrv; ?>"/>
                        <input type="hidden" name="txtcaribrg" value="<?php echo $txtcaribrg; ?>"/>
                        <input type="hidden" name="keluhan_id" value="<?php echo $tampil['id']; ?>"/>
                        <input type="hidden" name="status_keluhan" value="tidak_selesai"/>
                        <textarea name="keterangan_keluhan" class="form-control input-xs" rows="2" 
                                  placeholder="Masukkan keterangan..."><?php echo htmlspecialchars($tampil['keterangan_tidak_selesai']); ?></textarea>
                        <br>
                        <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-xs btn-warning">Simpan Keterangan</button>
                    </form>
                <?php } else { ?>
                    <?php echo htmlspecialchars($tampil['keterangan_tidak_selesai']); ?>
                <?php } ?>
            </td>
            <td class="center">
                <a class="red" data-rel="tooltip" title="Delete" 
                href="keluhan-hapus.php?keluhan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                onclick="return confirm('Keluhan akan dihapus. Lanjutkan?')">
                <i class="ace-icon fa fa-trash-o bigger-130"></i>
                </a>
            </td>
        </tr>
        <?php
            }
            if($no == 0) {
        ?>
        <tr>
            <td colspan="5" class="center"><em>Belum ada keluhan yang ditambahkan</em></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<!-- Daftar Pengerjaan -->
<h5 class="header green smaller">Daftar Pengerjaan</h5>
<table class="table table-bordered table-striped">
    <thead>
        <tr class="info">
            <th width="5%" class="center">No</th>
            <th width="50%">Item Pengerjaan</th>
            <th width="35%">Mekanik</th>
            <th width="10%" class="center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $no = 0;
            $sql = mysqli_query($koneksi,"SELECT 
                                            p.id, p.item_pengerjaan, m.nama as nama_mekanik
                                            FROM tbservis_pengerjaan p
                                            LEFT JOIN tblmekanik m ON p.kd_mekanik = m.nomekanik
                                            WHERE p.no_service='$no_service'
                                            ORDER BY p.id ASC");
            while ($tampil = mysqli_fetch_array($sql)) {
                $no++;
        ?>
        <tr>
            <td class="center"><?php echo $no; ?></td>
            <td><?php echo htmlspecialchars($tampil['item_pengerjaan']); ?></td>
            <td><?php echo htmlspecialchars($tampil['nama_mekanik']); ?></td>
            <td class="center">
                <a class="red" data-rel="tooltip" title="Delete" 
                href="pengerjaan-hapus.php?pengerjaan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                onclick="return confirm('Pengerjaan akan dihapus. Lanjutkan?')">
                <i class="ace-icon fa fa-trash-o bigger-130"></i>
                </a>
            </td>
        </tr>
        <?php
            }
            if($no == 0) {
        ?>
        <tr>
            <td colspan="4" class="center"><em>Belum ada pengerjaan yang ditambahkan</em></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<script type="text/javascript">
function showModalSearchKeluhan() {
    // Implementasi modal search keluhan
    if(typeof $('#modal-search-keluhan').modal === 'function') {
        $('#modal-search-keluhan').modal('show');
    } else {
        alert('Modal search keluhan belum tersedia');
    }
}
</script>