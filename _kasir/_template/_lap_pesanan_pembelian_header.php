<div class="row">
    <div class="col-xs-8 col-sm-2">
        <label>Dari Tanggal :</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <div class="input-group">
                    <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
                    value="<?php echo $tgl_pilih_dari; ?>" data-date-format="dd/mm/yyyy" />
                    <span class="input-group-addon">
                        <i class="fa fa-calendar bigger-110"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-2">
        <label>Sampai Tanggal :</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <div class="input-group">
                    <input class="form-control date-picker" id="id-date-picker-2" name="id-date-picker-2" type="text" autocomplete="off" 
                    value="<?php echo $tgl_pilih_sampai; ?>" data-date-format="dd/mm/yyyy" />
                    <span class="input-group-addon">
                        <i class="fa fa-calendar bigger-110"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-4">
        <label>Supplier :</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <select class="form-control" name="cbosupplier" id="cbosupplier" >
                <option value="">- Semua Supplier -</option>
                <?php
                    $q = mysqli_query($koneksi,"select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
                    while ($row1 = mysqli_fetch_array($q)){
                        $k_id           = $row1['nosupplier'];
                        $k_opis         = $row1['namasupplier'];
                ?>
                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbo_supplier){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-2">
        <label>&nbsp;</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <button class="btn btn-primary btn-block" type="submit" 
                id="btnrst" name="btnrst">
                Tampilkan
                </button>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-1">
        <label>&nbsp;</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <button class="btn btn-primary btn-block" type="submit" 
                id="btnpdf" name="btnpdf">
                Cetak
                </button>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-1">
        <label>&nbsp;</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <button class="btn btn-primary btn-block" type="submit" 
                id="btnxl" name="btnxl">
                Export
                </button>
            </div>
        </div>
    </div>    
</div>
