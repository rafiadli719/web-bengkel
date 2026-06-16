<!-- Tab Detail Service -->
<div class="detail-content">
    <div class="row">
        <div class="col-xs-12">
            <div class="padding-18">
                
                <!-- Service Information -->
                <div class="form-section">
                    <h5 class="blue">
                        <i class="ace-icon fa fa-info-circle"></i>
                        Informasi Service
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> No. Service :</label>									
                                <div class="col-sm-8">
                                    <input type="text" id="txtnoservice" name="txtnoservice" class="form-control" 
                                    value="<?php echo $no_service; ?>" readonly="true" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Tanggal :</label>									
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
                                        value="<?php echo $tanggal; ?>" data-date-format="dd/mm/yyyy" />
                                        <span class="input-group-addon">
                                            <i class="fa fa-calendar bigger-110"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Jam :</label>									
                                <div class="col-sm-8">
                                    <input type="time" class="form-control" name="jam_service" id="jam_service" 
                                    value="<?php echo $jam; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> User :</label>									
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" 
                                    value="<?php echo $_nama; ?>" disabled />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Status :</label>									
                                <div class="col-sm-9">
                                    <select class="form-control" name="status_servis" id="status_servis">
                                        <option value="datang" <?php echo ($status_servis == 'datang') ? 'selected' : ''; ?>>Datang</option>
                                        <option value="proses" <?php echo ($status_servis == 'proses') ? 'selected' : ''; ?>>Proses</option>
                                        <option value="selesai" <?php echo ($status_servis == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                        <option value="batal" <?php echo ($status_servis == 'batal') ? 'selected' : ''; ?>>Batal</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="form-section">
                    <h5 class="green">
                        <i class="ace-icon fa fa-user"></i>
                        Informasi Pelanggan
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Kode Pelanggan :</label>									
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="kode_pelanggan" id="kode_pelanggan" 
                                        value="<?php echo $kode_pelanggan; ?>" placeholder="Kode pelanggan..." />
                                        <span class="input-group-btn">
                                            <button class="btn btn-info" type="button" onclick="cariPelanggan()">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Nama Pelanggan :</label>									
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="namapelanggan" id="namapelanggan" 
                                    value="<?php echo $namapelanggan; ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> No. Polisi :</label>									
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="no_polisi" id="no_polisi" 
                                        value="<?php echo $no_polisi; ?>" placeholder="Nomor polisi..." />
                                        <span class="input-group-btn">
                                            <button class="btn btn-info" type="button" onclick="cariKendaraan()">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="form-section">
                    <h5 class="orange">
                        <i class="ace-icon fa fa-motorcycle"></i>
                        Informasi Kendaraan
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Pemilik :</label>									
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="pemilik" id="pemilik" 
                                    value="<?php echo $pemilik; ?>" readonly />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Jenis :</label>									
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="jenis" id="jenis" 
                                    value="<?php echo $jenis; ?>" readonly />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Merek :</label>									
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" name="merek" id="merek" 
                                    value="<?php echo $merek; ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Warna :</label>									
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="warna" id="warna" 
                                    value="<?php echo $warna; ?>" readonly />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> No. Rangka :</label>									
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="no_rangka" id="no_rangka" 
                                    value="<?php echo $no_rangka; ?>" readonly />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> No. Mesin :</label>									
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="no_mesin" id="no_mesin" 
                                    value="<?php echo $no_mesin; ?>" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> KM Sekarang :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="km_skr" id="km_skr" 
                                    value="<?php echo $km_skr; ?>" placeholder="Kilometer saat ini" />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> KM Berikut :</label>									
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="km_berikut" id="km_berikut" 
                                    value="<?php echo $km_berikut; ?>" placeholder="Kilometer berikutnya" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <h5 class="purple">
                        <i class="ace-icon fa fa-sticky-note"></i>
                        Informasi Tambahan
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="col-sm-2 control-label no-padding-right"> Catatan :</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="catatan" name="catatan" rows="2" 
                                    placeholder="Catatan tambahan..."><?php echo $catatan ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
