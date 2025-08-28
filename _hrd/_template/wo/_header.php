<div class="row">
    <div class="col-xs-6">
        <div class="form-group">
            <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Kode </label>
            <div class="col-sm-9">
                <input type="text" class="col-xs-10 col-sm-6" 
                value="<?php echo $LastID; ?>" autocomplete="off" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Nama WO </label>
            <div class="col-sm-9">
                <input type="text" id="txtnamawo" name="txtnamawo" class="col-xs-10 col-sm-12" 
                value="<?php echo $txtnamawo; ?>" autocomplete="off" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-6 control-label no-padding-right" for="form-field-1"> Estimasi Waktu Pengerjaan (Menit) </label>
            <div class="col-sm-5">
                <input type="text" id="txtwaktu" name="txtwaktu" class="col-xs-10 col-sm-12" 
                value="<?php echo $txtwaktu; ?>" autocomplete="off" />
            </div>
        </div>
    </div>
    <div class="col-xs-6">
        <div class="form-group">
            <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Keterangan </label>
            <div class="col-sm-10">
                <textarea class="col-xs-10 col-sm-12" id="txtnote" name="txtnote" rows="3"><?php echo $txtketwo; ?></textarea>
            </div>
        </div>    
    </div>    
</div>    