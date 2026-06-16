<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
        
            <div class="row">
                <div class="col-xs-8 col-sm-6">
                    <label>No. Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                            value="<?php echo $no_service; ?>" readonly="true" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-8 col-sm-6">
                    <label>Jenis Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                            value="SERVICE GARANSI" readonly="true" style="background-color: #f8f9fa; font-weight: bold; color: #28a745;" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>                                                                                                                
            <!-- Section Penanggung Jawab Servis dengan Persentase -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header blue smaller">
                        <i class="ace-icon fa fa-user-md"></i>
                        Penanggung Jawab Servis
                    </h4>
                </div>
                
                <!-- Kepala Mekanik 1 dengan Persentase -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 1 <span style="color:red;">*</span>:</label>
                    <select class="form-control" name="cbokepala1" id="cbokepala1" onchange="validateMekanikKepala(); autoFillKepalaPercentage()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            $sql="SELECT nomekanik, nama FROM tblmekanik 
                                  WHERE nama<>'-' AND keahlian='1' 
                                  ORDER BY nama ASC";
                            $sql_row=mysqli_query($koneksi,$sql);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>">
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala1" id="txtpersen_kepala1" 
                               value="0" min="0" max="100" 
                               onchange="calculatePercentageKepala()" onkeyup="calculatePercentageKepala()">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Kepala Mekanik 2 dengan Persentase -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 2 (Opsional):</label>
                    <select class="form-control" name="cbokepala2" id="cbokepala2" onchange="validateMekanikKepala(); autoFillKepalaPercentage()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            $sql="SELECT nomekanik, nama FROM tblmekanik 
                                  WHERE nama<>'-' AND keahlian='1' 
                                  ORDER BY nama ASC";
                            $sql_row=mysqli_query($koneksi,$sql);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>">
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala2" id="txtpersen_kepala2" 
                               value="0" min="0" max="100" 
                               onchange="calculatePercentageKepala()" onkeyup="calculatePercentageKepala()">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Status Persentase Kepala Mekanik -->
                <div class="col-xs-12 col-sm-12">
                    <div class="space space-2"></div>
                    <div id="persentaseStatusKepala" class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        <strong>Total % Supervisi: <span id="totalPersenKepala">0</span>%</strong>
                        <span id="persenMessageKepala"> - Boleh kurang dari 100%</span>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Km -->
            <div class="row">
                <div class="col-xs-8 col-sm-6">
                    <label>Km Sekarang :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                            id="txtkm_skr" name="txtkm_skr" 
                            value="<?php echo $km_skr; ?>" 
                            autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-8 col-sm-6">
                    <label for="id-date-picker-1">Km Berikut :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                            id="txtkm_next" name="txtkm_next" 
                            value="<?php echo $km_berikut; ?>" autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="space space-8"></div>
                
                <!-- Section Admin/Kasir -->
                <div class="row">
                    <div class="col-xs-12 col-sm-12">
                        <h4 class="header green smaller">
                            <i class="ace-icon fa fa-user"></i>
                            Admin/Kasir
                        </h4>
                        <p><small><em>Minimal 1 admin/kasir harus diisi. Total persentase harus 100%</em></small></p>
                    </div>
                    
                    <!-- Admin/Kasir 1 -->
                    <div class="col-xs-8 col-sm-8">
                        <label>Admin/Kasir 1 <span style="color:red;">*</span>:</label>
                        <select class="form-control" name="cbomekanik1" id="cbomekanik1" onchange="validateMekanikGaransi(); autoFillMekanikPercentage()">
                            <option value="">- Pilih Admin/Kasir -</option>
                            <?php
                                $sql="SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' AND keahlian='2' ORDER BY nama ASC";
                                $sql_row=mysqli_query($koneksi,$sql);
                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo $sql_res["nomekanik"]; ?>" <?php echo ($mekanik1==$sql_res["nomekanik"])?'selected':''; ?>>
                                <?php echo $sql_res["nama"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-4 col-sm-4">
                        <label>% Kerja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="txtpersen_kerja1" id="txtpersen_kerja1" 
                                   value="<?php echo $persen1; ?>" min="0" max="100" 
                                   onchange="calculatePercentageGaransi()" onkeyup="calculatePercentageGaransi()">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    
                    <!-- Admin/Kasir 2 -->
                    <div class="col-xs-8 col-sm-8">
                        <label>Admin/Kasir 2 (Opsional):</label>
                        <select class="form-control" name="cbomekanik2" id="cbomekanik2" onchange="validateMekanikGaransi(); autoFillMekanikPercentage()">
                            <option value="">- Pilih Admin/Kasir -</option>
                            <?php
                                $sql="SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' AND keahlian='2' ORDER BY nama ASC";
                                $sql_row=mysqli_query($koneksi,$sql);
                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo $sql_res["nomekanik"]; ?>" <?php echo ($mekanik2==$sql_res["nomekanik"])?'selected':''; ?>>
                                <?php echo $sql_res["nama"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-4 col-sm-4">
                        <label>% Kerja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="txtpersen_kerja2" id="txtpersen_kerja2" 
                                   value="<?php echo $persen2; ?>" min="0" max="100" 
                                   onchange="calculatePercentageGaransi()" onkeyup="calculatePercentageGaransi()">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    
                    <!-- Mekanik 3 -->
                    <div class="col-xs-8 col-sm-8">
                        <label>Mekanik 3 (Opsional):</label>
                        <select class="form-control" name="cbomekanik3" id="cbomekanik3" onchange="validateMekanikGaransi(); autoFillMekanikPercentage()">
                            <option value="">- Pilih Mekanik -</option>
                            <?php
                                $sql="SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' AND keahlian='3' ORDER BY nama ASC";
                                $sql_row=mysqli_query($koneksi,$sql);
                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo $sql_res["nomekanik"]; ?>" <?php echo ($mekanik3==$sql_res["nomekanik"])?'selected':''; ?>>
                                <?php echo $sql_res["nama"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-4 col-sm-4">
                        <label>% Kerja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="txtpersen_kerja3" id="txtpersen_kerja3" 
                                   value="<?php echo $persen3; ?>" min="0" max="100" 
                                   onchange="calculatePercentageGaransi()" onkeyup="calculatePercentageGaransi()">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    
                    <!-- Mekanik 4 -->
                    <div class="col-xs-8 col-sm-8">
                        <label>Mekanik 4 (Opsional):</label>
                        <select class="form-control" name="cbomekanik4" id="cbomekanik4" onchange="validateMekanikGaransi(); autoFillMekanikPercentage()">
                            <option value="">- Pilih Mekanik -</option>
                            <?php
                                $sql="SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' AND keahlian='3' ORDER BY nama ASC";
                                $sql_row=mysqli_query($koneksi,$sql);
                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo $sql_res["nomekanik"]; ?>" <?php echo ($mekanik4==$sql_res["nomekanik"])?'selected':''; ?>>
                                <?php echo $sql_res["nama"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-4 col-sm-4">
                        <label>% Kerja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="txtpersen_kerja4" id="txtpersen_kerja4" 
                                   value="<?php echo $persen4; ?>" min="0" max="100" 
                                   onchange="calculatePercentageGaransi()" onkeyup="calculatePercentageGaransi()">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    
                    <!-- Status Persentase Admin/Kasir -->
                    <div class="col-xs-12 col-sm-12">
                        <div class="space space-2"></div>
                        <div id="persentaseStatusGaransi" class="alert alert-warning">
                            <i class="ace-icon fa fa-exclamation-triangle"></i>
                            <strong>Total % Admin/Kasir: <span id="totalPersenMekanik">0</span>%</strong>
                            <span id="persenMessageGaransi"> - Harus tepat 100%!</span>
                        </div>
                    </div>
                </div>
                <div class="space space-8"></div>
                
                <!-- Section Mekanik Pengerjaan -->
                <div class="row">
                    <div class="col-xs-12 col-sm-12">
                        <h4 class="header purple smaller">
                            <i class="ace-icon fa fa-wrench"></i>
                            Mekanik Pengerjaan
                        </h4>
                        <p><small><em>Opsional - Untuk mekanik yang terlibat dalam pengerjaan</em></small></p>
                    </div>
                    
                    <!-- Mekanik 3 -->
                    <div class="col-xs-8 col-sm-8">
                        <label>Mekanik 3 (Opsional):</label>
                        <select class="form-control" name="cbomekanik3" id="cbomekanik3" onchange="validateMekanikGaransi(); autoFillMekanikPercentage()">
                            <option value="">- Pilih Mekanik -</option>
                            <?php
                                $sql="SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' AND keahlian='3' ORDER BY nama ASC";
                                $sql_row=mysqli_query($koneksi,$sql);
                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo $sql_res["nomekanik"]; ?>" <?php echo ($mekanik3==$sql_res["nomekanik"])?'selected':''; ?>>
                                <?php echo $sql_res["nama"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-4 col-sm-4">
                        <label>% Kerja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="txtpersen_kerja3" id="txtpersen_kerja3" 
                                   value="<?php echo $persen3; ?>" min="0" max="100" 
                                   onchange="calculatePercentageGaransi()" onkeyup="calculatePercentageGaransi()">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                    
                    <!-- Mekanik 4 -->
                    <div class="col-xs-8 col-sm-8">
                        <label>Mekanik 4 (Opsional):</label>
                        <select class="form-control" name="cbomekanik4" id="cbomekanik4" onchange="validateMekanikGaransi(); autoFillMekanikPercentage()">
                            <option value="">- Pilih Mekanik -</option>
                            <?php
                                $sql="SELECT nomekanik, nama FROM tblmekanik WHERE nama<>'-' AND keahlian='3' ORDER BY nama ASC";
                                $sql_row=mysqli_query($koneksi,$sql);
                                while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo $sql_res["nomekanik"]; ?>" <?php echo ($mekanik4==$sql_res["nomekanik"])?'selected':''; ?>>
                                <?php echo $sql_res["nama"]; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-4 col-sm-4">
                        <label>% Kerja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="txtpersen_kerja4" id="txtpersen_kerja4" 
                                   value="<?php echo $persen4; ?>" min="0" max="100" 
                                   onchange="calculatePercentageGaransi()" onkeyup="calculatePercentageGaransi()">
                            <span class="input-group-addon">%</span>
                        </div>
                    </div>
                </div>
                <div class="space space-8"></div>
                                                                                                                 
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header orange smaller">
                        <i class="ace-icon fa fa-exclamation-triangle"></i>
                        Keluhan Pelanggan
                    </h4>
                </div>
                <div class="col-xs-8 col-sm-8">
                    <label>Keluhan:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="txtkeluhan" name="txtkeluhan" 
                               placeholder="Ketik keluhan atau pilih dari master..." autocomplete="off" maxlength="255" />
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-info" onclick="openModalSearchKeluhan()">
                                <i class="fa fa-search"></i> Cari
                            </button>
                        </span>
                    </div>
                    <small class="help-block">
                        <i class="fa fa-info-circle"></i> 
                        Ketik manual atau klik "Cari" untuk memilih dari master keluhan
                    </small>
                </div>
                <div class="col-xs-8 col-sm-4">
                    <label>&nbsp;</label>
                    <button class="btn btn-sm btn-primary btn-block" type="submit" id="btnaddkeluhan" name="btnaddkeluhan">
                        <i class="ace-icon fa fa-plus"></i> Tambah Keluhan
                    </button>
                </div>

                <div class="col-xs-8 col-sm-12">
                    <?php if (!empty($cari_keluhan)) { ?>
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        Menampilkan hasil pencarian untuk: "<strong><?php echo htmlspecialchars($cari_keluhan); ?></strong>"
                    </div>
                    <?php } ?>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="info">
                                <th width="5%" class="center">No</th>
                                <th width="35%">Keluhan Pelanggan</th>
                                <th width="20%">Status Pengerjaan</th>
                                <th width="15%">Progress</th>
                                <th width="15%">Estimasi</th>
                                <th width="10%" class="center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $no = 0;
                                $cari_keluhan = isset($_GET['cari_keluhan']) ? $_GET['cari_keluhan'] : '';
                                
                                if (!empty($cari_keluhan)) {
                                    $sql = mysqli_query($koneksi,"SELECT 
                                                                    id, keluhan, status_pengerjaan, 
                                                                    keterangan_tidak_selesai 
                                                                    FROM tbservis_keluhan_status 
                                                                    WHERE 
                                                                    no_service='$no_service' AND 
                                                                    keluhan LIKE '%$cari_keluhan%'
                                                                    ORDER BY id ASC");
                                } else {
                                    $sql = mysqli_query($koneksi,"SELECT 
                                                                    id, keluhan, status_pengerjaan, 
                                                                    keterangan_tidak_selesai 
                                                                    FROM tbservis_keluhan_status 
                                                                    WHERE 
                                                                    no_service='$no_service'
                                                                    ORDER BY id ASC");
                                }
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
                                <td class="center"><?php echo $no ?></td>
                                <td><?php echo $tampil['keluhan']?></td>
                                <td>
                                    <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                                    <br><br>
                                    <!-- Form untuk update status -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
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
                                            <input type="hidden" name="keluhan_id" value="<?php echo $tampil['id']; ?>"/>
                                            <input type="hidden" name="status_keluhan" value="tidak_selesai"/>
                                            <textarea name="keterangan_keluhan" class="form-control input-xs" rows="2" 
                                                      placeholder="Contoh: perlu sparepart khusus"><?php echo $tampil['keterangan_tidak_selesai']; ?></textarea>
                                            <br>
                                            <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-xs btn-warning">Simpan Keterangan</button>
                                        </form>
                                    <?php } else { ?>
                                        <?php echo $tampil['keterangan_tidak_selesai']; ?>
                                    <?php } ?>
                                </td>
                                <td class="center">
                                    <a class="red" data-rel="tooltip" title="Delete" 
                                    href="keluhan-hapus.php?kid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                    onclick="return confirm('Keluhan akan dihapus. Lanjutkan?')">
                                    <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                    </a>
                                </td>														
                            </tr>
                        <?php
                            }
                            if($no == 0) {
                                if (!empty($cari_keluhan)) {
                        ?>
                        <tr>
                            <td colspan="5" class="center"><em>Tidak ada keluhan yang sesuai dengan pencarian "<?php echo htmlspecialchars($cari_keluhan); ?>"</em></td>
                        </tr>
                        <?php 
                                } else {
                        ?>
                        <tr>
                            <td colspan="5" class="center"><em>Belum ada keluhan yang diinput</em></td>
                        </tr>
                        <?php 
                                }
                            } ?>
                        </tbody>
                    </table>
                </div>


                <!-- Riwayat Service Kendaraan -->
                <div class="col-xs-12 col-sm-12" style="margin-top: 20px;">
                    <h4 class="header purple smaller">
                        <i class="ace-icon fa fa-history"></i>
                        Riwayat Service Kendaraan
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr class="info">
                                    <th width="15%" class="center">No. Service</th>
                                    <th width="15%" class="center">Tanggal</th>
                                    <th width="15%" class="center">Total</th>
                                    <th width="55%">Keluhan Sebelumnya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $sql_history = mysqli_query($koneksi,"SELECT 
                                                                        no_service, tanggal_trx, status, total_grand 
                                                                        FROM view_service 
                                                                        WHERE no_polisi='$no_polisi' AND 
                                                                        status='4' AND
                                                                        no_service != '$no_service'
                                                                        ORDER BY tanggal_trx DESC
                                                                        LIMIT 5");
                                    if(mysqli_num_rows($sql_history) > 0) {
                                        while ($history = mysqli_fetch_array($sql_history)) {
                                            $no_service_history = $history['no_service'];
                                ?>
                                <tr>
                                    <td class="center">
                                        <strong><?php echo $history['no_service']; ?></strong>
                                    </td>
                                    <td class="center">
                                        <?php echo date('d/m/Y', strtotime($history['tanggal_trx'])); ?>
                                    </td>
                                    <td class="center">
                                        <span class="label label-success">
                                            Rp <?php echo number_format($history['total_grand'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            $sql_keluhan_history = mysqli_query($koneksi,"SELECT keluhan 
                                                                                        FROM tbservis_keluhan_status 
                                                                                        WHERE no_service='$no_service_history' 
                                                                                        LIMIT 3");
                                            $keluhan_list = [];
                                            while ($keluhan_history = mysqli_fetch_array($sql_keluhan_history)) {
                                                $keluhan_list[] = $keluhan_history['keluhan'];
                                            }
                                            
                                            if(!empty($keluhan_list)) {
                                                echo "• " . implode("<br>• ", $keluhan_list);
                                                if(mysqli_num_rows($sql_keluhan_history) > 3) {
                                                    echo "<br><small class='text-muted'>...dan lainnya</small>";
                                                }
                                            } else {
                                                echo "<em class='text-muted'>Tidak ada keluhan tercatat</em>";
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php 
                                        }
                                    } else {
                                ?>
                                <tr>
                                    <td colspan="4" class="center">
                                        <em class="text-muted">Tidak ada riwayat service sebelumnya</em>
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
// Function untuk modal search keluhan
function openModalSearchKeluhan() {
    if(typeof $('#modal-search-keluhan') !== 'undefined') {
        $('#modal-search-keluhan').modal('show');
    } else {
        alert('Modal search keluhan tidak tersedia');
    }
}

// Function untuk memilih keluhan dari modal
function selectKeluhan(keluhan) {
    document.getElementById('txtkeluhan').value = keluhan;
    $('#modal-search-keluhan').modal('hide');
}

// Function untuk kepala mekanik
function calculatePercentageKepala() {
    var persen_kepala1 = parseInt(document.getElementById('txtpersen_kepala1').value) || 0;
    var persen_kepala2 = parseInt(document.getElementById('txtpersen_kepala2').value) || 0;
    
    var total = persen_kepala1 + persen_kepala2;
    
    document.getElementById('totalPersenKepala').innerHTML = total;
    
    var statusDiv = document.getElementById('persentaseStatusMekanik');
    var messageSpan = document.getElementById('persenMessageMekanik');
    
    if (total <= 100) {
        statusDiv.className = 'alert alert-info';
        messageSpan.innerHTML = ' - Total supervisi: ' + total + '%';
        messageSpan.style.color = 'blue';
    } else {
        statusDiv.className = 'alert alert-warning';
        messageSpan.innerHTML = ' - Total melebihi 100%!';
        messageSpan.style.color = 'orange';
    }
}

function validateMekanikKepala() {
    var kepala1 = document.getElementById('cbokepala1').value;
    var kepala2 = document.getElementById('cbokepala2').value;
    
    // Auto set persentase jika hanya satu kepala mekanik
    if (kepala1 && !kepala2) {
        if (document.getElementById('txtpersen_kepala1').value == '' || document.getElementById('txtpersen_kepala1').value == '0') {
            document.getElementById('txtpersen_kepala1').value = '100';
        }
    }
    
    // If only kepala2 is selected, auto-fill 100%
    else if (!kepala1 && kepala2) {
        document.getElementById('txtpersen_kepala1').value = '';
        document.getElementById('txtpersen_kepala2').value = '100';
    }
    // If both are selected, split 50-50
    else if (kepala1 && kepala2) {
        document.getElementById('txtpersen_kepala1').value = '50';
        document.getElementById('txtpersen_kepala2').value = '50';
    }
    // If none selected, clear percentages
    else {
        document.getElementById('txtpersen_kepala1').value = '';
        document.getElementById('txtpersen_kepala2').value = '';
    }
    
    calculatePercentageKepala();
}

// Validation functions like jemput template
function validateMekanikGaransi(number) {
    var mekanik = document.getElementById('cbomekanik' + number).value;
    var persen = document.getElementById('txtpersen_kerja' + number);
    
    if (mekanik == '') {
        persen.value = '0';
    } else if (persen.value == '0' || persen.value == '') {
        // Auto set 100% jika mekanik pertama dan belum ada yang diisi
        if (number == 1) {
            var total_existing = 0;
            for(var i = 2; i <= 4; i++) {
                total_existing += parseInt(document.getElementById('txtpersen_kerja' + i).value) || 0;
            }
            if (total_existing == 0) {
                persen.value = '100';
            }
        }
    }
    calculatePercentageGaransi();
}

function calculatePercentageGaransi() {
    var persen1 = parseInt(document.getElementById('txtpersen_kerja1').value) || 0;
    var persen2 = parseInt(document.getElementById('txtpersen_kerja2').value) || 0;
    var persen3 = parseInt(document.getElementById('txtpersen_kerja3').value) || 0;
    var persen4 = parseInt(document.getElementById('txtpersen_kerja4').value) || 0;
    
    var total = persen1 + persen2 + persen3 + persen4;
    
    document.getElementById('totalPersenMekanik').innerHTML = total;
    
    var statusDiv = document.getElementById('persentaseStatusGaransi');
    var messageSpan = document.getElementById('persenMessageGaransi');
    
    if (total == 100) {
        statusDiv.className = 'alert alert-success';
        messageSpan.innerHTML = ' - Persentase sudah benar!';
        messageSpan.style.color = 'green';
    } else if (total > 100) {
        statusDiv.className = 'alert alert-danger';
        messageSpan.innerHTML = ' - Persentase melebihi 100%!';
        messageSpan.style.color = 'red';
    } else if (total > 0) {
        statusDiv.className = 'alert alert-warning';
        messageSpan.innerHTML = ' - Persentase kurang dari 100%!';
        messageSpan.style.color = 'orange';
    } else {
        statusDiv.className = 'alert alert-warning';
        messageSpan.innerHTML = ' - Belum ada persentase yang diisi!';
        messageSpan.style.color = 'gray';
    }
}

function autoFillKepalaPercentage() {
    var kepala1 = document.getElementById('cbokepala1').value;
    var kepala2 = document.getElementById('cbokepala2').value;
    
    // If only kepala1 is selected, auto-fill 100%
    if (kepala1 && !kepala2) {
        document.getElementById('txtpersen_kepala1').value = '100';
        document.getElementById('txtpersen_kepala2').value = '';
    }
    // If only kepala2 is selected, auto-fill 100%
    else if (!kepala1 && kepala2) {
        document.getElementById('txtpersen_kepala1').value = '';
        document.getElementById('txtpersen_kepala2').value = '100';
    }
    // If both are selected, split 50-50
    else if (kepala1 && kepala2) {
        document.getElementById('txtpersen_kepala1').value = '50';
        document.getElementById('txtpersen_kepala2').value = '50';
    }
    // If none selected, clear percentages
    else {
        document.getElementById('txtpersen_kepala1').value = '';
        document.getElementById('txtpersen_kepala2').value = '';
    }
    
    calculatePercentageKepala();
}

function autoFillMekanikPercentage() {
    var mekanik1 = document.getElementById('cbomekanik1').value;
    var mekanik2 = document.getElementById('cbomekanik2').value;
    var mekanik3 = document.getElementById('cbomekanik3').value;
    var mekanik4 = document.getElementById('cbomekanik4').value;
    
    var selectedCount = 0;
    if (mekanik1) selectedCount++;
    if (mekanik2) selectedCount++;
    if (mekanik3) selectedCount++;
    if (mekanik4) selectedCount++;
    
    // Clear all percentages first
    document.getElementById('txtpersen_kerja1').value = '';
    document.getElementById('txtpersen_kerja2').value = '';
    document.getElementById('txtpersen_kerja3').value = '';
    document.getElementById('txtpersen_kerja4').value = '';
    
    if (selectedCount === 1) {
        // Auto-fill 100% for the selected one
        if (mekanik1) document.getElementById('txtpersen_kerja1').value = '100';
        else if (mekanik2) document.getElementById('txtpersen_kerja2').value = '100';
        else if (mekanik3) document.getElementById('txtpersen_kerja3').value = '100';
        else if (mekanik4) document.getElementById('txtpersen_kerja4').value = '100';
    }
    else if (selectedCount === 2) {
        // Split 50-50
        var percentage = '50';
        if (mekanik1) document.getElementById('txtpersen_kerja1').value = percentage;
        if (mekanik2) document.getElementById('txtpersen_kerja2').value = percentage;
        if (mekanik3) document.getElementById('txtpersen_kerja3').value = percentage;
        if (mekanik4) document.getElementById('txtpersen_kerja4').value = percentage;
    }
    else if (selectedCount === 3) {
        // Split 33.33-33.33-33.33
        var percentage = '33';
        var lastPercentage = '34'; // To make total 100%
        var count = 0;
        if (mekanik1) { count++; document.getElementById('txtpersen_kerja1').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik2) { count++; document.getElementById('txtpersen_kerja2').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik3) { count++; document.getElementById('txtpersen_kerja3').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik4) { count++; document.getElementById('txtpersen_kerja4').value = (count === 3) ? lastPercentage : percentage; }
    }
    else if (selectedCount === 4) {
        // Split 25-25-25-25
        document.getElementById('txtpersen_kerja1').value = '25';
        document.getElementById('txtpersen_kerja2').value = '25';
        document.getElementById('txtpersen_kerja3').value = '25';
        document.getElementById('txtpersen_kerja4').value = '25';
    }
    
    calculatePercentageGaransi();
}

// Auto calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculatePercentageKepala();
    calculatePercentageGaransi();
    
    // Event listeners
    $('#txtpersen_kepala1, #txtpersen_kepala2').on('input keyup', function() {
        calculatePercentageKepala();
    });
    
    $('#txtpersen_kerja1, #txtpersen_kerja2, #txtpersen_kerja3, #txtpersen_kerja4').on('input keyup', function() {
        calculatePercentage();
    });
    
    // Add validation to form submit
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var submitButton = e.submitter || document.activeElement;
            
            // Only validate for main save buttons
            if (submitButton && (submitButton.name == 'btnsimpan' || submitButton.id == 'btnsimpan')) {
                if (!validateBeforeSubmitGaransi()) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
</script>