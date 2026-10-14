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
    <div class="col-xs-8 col-sm-3">
        <label>Acuan Daftar Item :</label>
        <select class="form-control" name="acuan" id="acuan">
            <option value="transaksi"<?php echo (isset($acuan) && $acuan==='transaksi') ? ' selected' : ''; ?>>Item bertransaksi pada rentang tanggal</option>
            <option value="stok"<?php echo (isset($acuan) && $acuan==='stok') ? ' selected' : ''; ?>>Semua item stok &gt; 0 (per tanggal sampai)</option>
        </select>
    </div>
</div>
