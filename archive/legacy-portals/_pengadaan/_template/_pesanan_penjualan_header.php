<div class="row">
    <div class="col-xs-12 col-sm-4">
        <div class="form-group">
            <label class="col-sm-5 control-label no-padding-right" for="txtnojl"> No. Pesanan :</label>									
            <div class="col-sm-7">
                <input type="text" id="txtnojl" name="txtnojl" 
                class="form-control" 
                value="<?php echo $LastID; ?>" readonly="true" />
            </div>
        </div> 
        <div class="form-group">
            <label class="col-sm-5 control-label no-padding-right" for="form-field-1"> Tanggal :</label>									
            <div class="col-sm-7">
                <div class="input-group">
                    <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
                    value="<?php echo $tgl_pilih; ?>" data-date-format="dd/mm/yyyy" />
                    <span class="input-group-addon">
                        <i class="fa fa-calendar bigger-110"></i>
                    </span>
                </div>
            </div>
        </div>    
    </div>
    <div class="col-xs-12 col-sm-8">
        <div class="form-group">
        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Pelanggan : </label>									
        <div class="col-sm-4">
            <div class="input-group">
                <input type="text" class="form-control" id="txtkey" name="txtkey" 
                value="<?php echo $nopelanggan; ?>" placeholder="No Pelanggan/Nopol" />
                <div class="input-group-btn">
                    <button type="submit" class="btn btn-default no-border btn-sm" 
                    id="btncari_pelanggan" name="btncari_pelanggan">
                        <i class="ace-icon fa fa-search icon-on-right bigger-110"></i>
                    </button>
                </div>
            </div>                                                                    
        </div>
        <div class="col-sm-6">
            <input type="text" class="form-control" id="txtnmpelanggan" name="txtnmpelanggan" 
            value="<?php echo $nmpelanggan; ?>" readonly />
        </div>
        </div>
        
        <div class="form-group">
        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Sales : </label>									
        <div class="col-sm-4">
                <select class="form-control" name="cbosales" id="cbosales" >
                <option value="">- Pilih -</option>
                <?php
                    $q = mysqli_query($koneksi,"select nosales, namasales FROM tblsales order by namasales asc");
                    while ($row1 = mysqli_fetch_array($q)){
                        $k_id           = $row1['nosales'];
                        $k_opis         = $row1['namasales'];
                ?>
                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbosales){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                <?php
                    }
                ?>
                </select>
        </div>
    </div>
    </div>																	    
</div>

