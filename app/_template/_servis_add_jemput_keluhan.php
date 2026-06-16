<?php
// Template untuk input keluhan service jemput - disesuaikan dengan servis reguler
?>

<table class="table table-bordered">
    <tr>
        <td width="60%">
            <label>Keluhan :</label>
            <input type="hidden" id="kode_keluhan" name="kode_keluhan" value="" />
            <input type="text" class="form-control input-sm" 
            id="txtkeluhan" name="txtkeluhan" 
            placeholder="Pilih keluhan dari master" autocomplete="off" readonly />
        </td>
        <td width="20%">
            <label>&nbsp;</label><br>
            <button type="button" class="btn btn-info btn-sm btn-block" onclick="showModalSearchKeluhan()">
                <i class="ace-icon fa fa-search"></i> Pilih Keluhan
            </button>
        </td>
        <td width="20%">
            <label>&nbsp;</label><br>
            <button class="btn btn-warning btn-sm btn-block" type="submit" 
            id="btnaddkeluhan" name="btnaddkeluhan" onclick="return validateKeluhan()">
                <i class="ace-icon fa fa-plus"></i> Tambah ke SPK
            </button>
        </td>
    </tr>
</table>

<!-- Daftar Keluhan yang sudah ditambahkan -->
<table class="table table-bordered table-striped">
    <thead>
        <tr class="info">
            <th width="5%" class="center">No</th>
            <th width="30%">Keluhan</th>
            <th width="15%">Kategori</th>
            <th width="20%">Status</th>
            <th width="25%">Keterangan</th>
            <th width="5%" class="center">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $no = 0;
            $sql = mysqli_query($koneksi,"SELECT 
                                            id, keluhan, kode_keluhan, kategori, status_pengerjaan, 
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
            <td>
                <?php echo htmlspecialchars($tampil['keluhan']); ?>
                <?php if(!empty($tampil['kode_keluhan'])): ?>
                    <br><small class="text-muted"><i class="fa fa-barcode"></i> <?php echo $tampil['kode_keluhan']; ?></small>
                <?php endif; ?>
            </td>
            <td>
                <?php if(!empty($tampil['kategori'])): ?>
                    <span class="label label-info"><?php echo htmlspecialchars($tampil['kategori']); ?></span>
                <?php else: ?>
                    <small class="text-muted">-</small>
                <?php endif; ?>
            </td>
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
                \t<i class="ace-icon fa fa-trash-o bigger-130"></i>
                </a>
            </td>
        </tr>
        <?php
            }
            if($no == 0) {
        ?>
        <tr>
            <td colspan="6" class="center"><em>Belum ada keluhan yang ditambahkan</em></td>
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

function validateKeluhan() {
    var kodeKeluhan = document.getElementById('kode_keluhan').value.trim();
    if(kodeKeluhan === '') {
        alert('Silakan pilih keluhan dari Master terlebih dahulu!');
        return false;
    }
    return true;
}
</script>