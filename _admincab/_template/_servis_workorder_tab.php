<!-- Tab Work Order -->
<div class="workorder-content">
    <div class="row">
        <div class="col-xs-12">
            <div class="padding-18">
                
                <!-- Work Order Information -->
                <div class="form-section">
                    <h5 class="blue">
                        <i class="ace-icon fa fa-clipboard"></i>
                        Detail Work Order
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> No. Work Order :</label>									
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="no_workorder" id="no_workorder" 
                                    value="<?php echo $no_workorder ?? ''; ?>" placeholder="Nomor work order..." />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Tanggal WO :</label>									
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input class="form-control date-picker" id="tanggal_wo" name="tanggal_wo" type="text" autocomplete="off" 
                                        value="<?php echo $tanggal_wo ?? date('d/m/Y'); ?>" data-date-format="dd/mm/yyyy" />
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar bigger-110"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Estimasi Selesai :</label>									
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input class="form-control date-picker" id="estimasi_selesai" name="estimasi_selesai" type="text" autocomplete="off" 
                                        value="<?php echo $estimasi_selesai ?? ''; ?>" data-date-format="dd/mm/yyyy" />
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar bigger-110"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Prioritas :</label>									
                                <div class="col-sm-9">
                                    <select class="form-control" name="prioritas_wo" id="prioritas_wo">
                                        <option value="normal" <?php echo ($prioritas_wo ?? '') == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="urgent" <?php echo ($prioritas_wo ?? '') == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                        <option value="vip" <?php echo ($prioritas_wo ?? '') == 'vip' ? 'selected' : ''; ?>>VIP</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="col-sm-2 control-label no-padding-right"> Deskripsi Pekerjaan :</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="deskripsi_pekerjaan" name="deskripsi_pekerjaan" rows="4" 
                                    placeholder="Deskripsi detail pekerjaan yang akan dilakukan..."><?php echo $deskripsi_pekerjaan ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mechanic Assignment -->
                <div class="form-section">
                    <h5 class="green">
                        <i class="ace-icon fa fa-users"></i>
                        Penugasan Mekanik
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <!-- Kepala Mekanik -->
                    <div class="row">
                        <div class="col-xs-12">
                            <h6 class="blue">Kepala Mekanik</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Kepala Mekanik 1 :</label>									
                                <div class="col-sm-8">
                                    <select class="form-control" name="kepala_mekanik1" id="kepala_mekanik1">
                                        <option value="">- Pilih Kepala Mekanik -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'kepala_mekanik' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($kepala_mekanik1 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Kepala Mekanik 2 :</label>									
                                <div class="col-sm-9">
                                    <select class="form-control" name="kepala_mekanik2" id="kepala_mekanik2">
                                        <option value="">- Pilih Kepala Mekanik -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'kepala_mekanik' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($kepala_mekanik2 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin -->
                    <div class="row">
                        <div class="col-xs-12">
                            <h6 class="orange">Admin</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Admin 1 :</label>									
                                <div class="col-sm-8">
                                    <select class="form-control" name="admin1" id="admin1">
                                        <option value="">- Pilih Admin -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'admin' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($admin1 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Admin 2 :</label>									
                                <div class="col-sm-9">
                                    <select class="form-control" name="admin2" id="admin2">
                                        <option value="">- Pilih Admin -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'admin' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($admin2 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mekanik -->
                    <div class="row">
                        <div class="col-xs-12">
                            <h6 class="purple">Mekanik</h6>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Mekanik 1 :</label>									
                                <div class="col-sm-8">
                                    <select class="form-control" name="mekanik1" id="mekanik1">
                                        <option value="">- Pilih Mekanik -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'mekanik' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($mekanik1 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Mekanik 2 :</label>									
                                <div class="col-sm-9">
                                    <select class="form-control" name="mekanik2" id="mekanik2">
                                        <option value="">- Pilih Mekanik -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'mekanik' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($mekanik2 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Mekanik 3 :</label>									
                                <div class="col-sm-8">
                                    <select class="form-control" name="mekanik3" id="mekanik3">
                                        <option value="">- Pilih Mekanik -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'mekanik' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($mekanik3 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Mekanik 4 :</label>									
                                <div class="col-sm-9">
                                    <select class="form-control" name="mekanik4" id="mekanik4">
                                        <option value="">- Pilih Mekanik -</option>
                                        <?php
                                        $query_mekanik = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE jenis_mekanik = 'mekanik' ORDER BY nama ASC");
                                        if($query_mekanik) {
                                            while($mekanik = mysqli_fetch_array($query_mekanik)) {
                                                $selected = ($mekanik4 == $mekanik['nomekanik']) ? 'selected' : '';
                                                echo "<option value='".$mekanik['nomekanik']."' $selected>".$mekanik['nama']."</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Instructions -->
                <div class="form-section">
                    <h5 class="red">
                        <i class="ace-icon fa fa-list-alt"></i>
                        Instruksi Pekerjaan
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="col-sm-2 control-label no-padding-right"> Instruksi Khusus :</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="instruksi_khusus" name="instruksi_khusus" rows="4" 
                                    placeholder="Instruksi khusus untuk mekanik..."><?php echo $instruksi_khusus ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="col-sm-2 control-label no-padding-right"> Catatan WO :</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="catatan_wo" name="catatan_wo" rows="3" 
                                    placeholder="Catatan tambahan work order..."><?php echo $catatan_wo ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
