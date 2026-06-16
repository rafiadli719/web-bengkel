<div class="row">
    <div class="col-xs-12 col-sm-4">
        <div class="form-group">
            <label class="col-sm-5 control-label no-padding-right" for="txtnojl"> No. Pembelian :</label>									
            <div class="col-sm-7">
                <input type="text" id="txtnobl" name="txtnobl" 
                class="form-control" 
                value="<?php echo $LastID; ?>" readonly="true" />
            </div>
        </div> 
    </div>
    <div class="col-xs-12 col-sm-8">
        <div class="form-group">
        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Tanggal : </label>									
        <div class="col-sm-4">
                <div class="input-group">
                    <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" 
                    value="<?php echo $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" />
                    <span class="input-group-addon">
                        <i class="fa fa-calendar bigger-110"></i>
                    </span>
                </div>
        </div>
    </div>
    </div>																	    
</div>
<div class="row">
    <div class="col-xs-12 col-sm-4">
        <div class="form-group">
            <label class="col-sm-5 control-label no-padding-right" for="txtnopesanan"> No. Pesanan : </label>									
            <div class="col-sm-7">
                <input type="text" id="txtnopesanan" name="txtnopesanan" 
                class="form-control" 
                value="<?php echo $nopesanan; ?>" readonly="true" />
            </div>
        </div> 
    </div>
    <div class="col-xs-12 col-sm-8">
        <div class="form-group">
        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Tanggal Pesan : </label>									
        <div class="col-sm-4">
                <div class="input-group">
                    <input class="form-control date-picker" 
                    type="text" readonly="true" 
                    value="<?php echo $tgl_pesan; ?>" data-date-format="dd/mm/yyyy" />
                    <span class="input-group-addon">
                        <i class="fa fa-calendar bigger-110"></i>
                    </span>
                </div>
        </div>
        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Dari Supplier : </label>									
        <div class="col-sm-4">
                <select class="form-control" name="cbocabang" id="cbocabang" disabled >
                <option value="">- Pilih Cabang -</option>
                <?php
                    $q = mysqli_query($koneksi,"select 
                                                kode_cabang, nama_cabang 
                                                FROM 
                                                tbcabang");
                    while ($row1 = mysqli_fetch_array($q)){
                        $k_id           = $row1['kode_cabang'];
                        $k_opis         = $row1['nama_cabang'];
                ?>
                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $drcabang){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                <?php
                    }
                ?>
                </select>
        </div>
    </div>
    </div>																	    
</div>
