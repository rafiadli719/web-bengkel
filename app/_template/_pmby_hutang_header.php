							<div class="col-xs-12 col-sm-12">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">	
                                            <br>
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-5">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> No. Bayar :</label>									
                                                        <div class="col-sm-7">
                                                            <input type="text" id="txtnobyr" name="txtnobyr" class="form-control" 
                                                            value="<?php echo $LastID; ?>" />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Tanggal :</label>									
                                                        <div class="col-sm-7">
                                                            <div class="input-group">
                                                                <input class="form-control date-picker" 
                                                                id="id-date-picker-1" name="id-date-picker-1" 
                                                                type="text" autocomplete="off" 
                                                                value="<?php echo $tgl_pilih; ?>" 
                                                                data-date-format="dd/mm/yyyy" />
                                                                <span class="input-group-addon">
                                                                    <i class="fa fa-calendar bigger-110"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-2">

                                                </div>
                                                <div class="col-xs-12 col-sm-5">
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtuser"> User :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" 
                                                            value="<?php echo $_nama; ?>" disabled />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="cbotipe_supplier"> Tipe Supplier :</label>
                                                        <div class="col-sm-8">
                                                            <select class="col-xs-10 col-sm-12" name="cbotipe_supplier" id="cbotipe_supplier">
                                                                <option value="" <?php if (($flt_tipe_sup ?? '') === '') echo 'selected'; ?>>- Semua Tipe -</option>
                                                                <option value="eksternal" <?php if (($flt_tipe_sup ?? '') === 'eksternal') echo 'selected'; ?>>Eksternal</option>
                                                                <option value="antarcabang" <?php if (($flt_tipe_sup ?? '') === 'antarcabang') echo 'selected'; ?>>Antar Cabang</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtjt_dari"> Jatuh Tempo :</label>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <input class="form-control date-picker" id="txtjt_dari" name="txtjt_dari" type="text" autocomplete="off" placeholder="Dari (dd/mm/yyyy)" value="<?php echo htmlspecialchars($flt_jt_dari ?? ''); ?>" data-date-format="dd/mm/yyyy" />
                                                                <span class="input-group-addon">s/d</span>
                                                                <input class="form-control date-picker" id="txtjt_sampai" name="txtjt_sampai" type="text" autocomplete="off" placeholder="Sampai (dd/mm/yyyy)" value="<?php echo htmlspecialchars($flt_jt_sampai ?? ''); ?>" data-date-format="dd/mm/yyyy" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtuser"> Supplier :</label>									
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <?php
                                                                $hutang_kode_cabang = array();
                                                                $q_cb = mysqli_query($koneksi,"select kode_cabang FROM tbcabang");
                                                                while ($r_cb = mysqli_fetch_array($q_cb)) { $hutang_kode_cabang[] = $r_cb['kode_cabang']; }
                                                                ?>
                                                                <select class="col-xs-10 col-sm-12" name="cbosupplier" id="cbosupplier" required >
                                                                <option value="">- Pilih -</option>
                                                                <?php
                                                                    $q = mysqli_query($koneksi,"select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
                                                                    while ($row1 = mysqli_fetch_array($q)){
                                                                        $k_id           = $row1['nosupplier'];
                                                                        $k_opis         = $row1['namasupplier'];
                                                                ?>
                                                                <option value='<?php echo $k_id; ?>' data-tipe='<?php echo in_array($k_id, $hutang_kode_cabang, true) ? 'antarcabang' : 'eksternal'; ?>' <?php if ($k_id == $no_supplier){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                                                <?php
                                                                    }
                                                                ?>
                                                                </select>
                                                                <div class="input-group-btn">
                                                                    <button type="submit" class="btn btn-default no-border btn-sm" 
                                                                    id="btncari_pesanan" name="btncari_pesanan">
                                                                    <i class="ace-icon fa fa-check icon-on-right bigger-110"></i>
                                                                    </button>
                                                                </div>
                                                            </div>                                                                    
                                                        </div>                                                        

                                                    </div>
                                                </div>
                                            </div>
										</div>
									</div>
								</div>	
							</div>
<script>
(function() {
    var tipe = document.getElementById('cbotipe_supplier');
    var sup = document.getElementById('cbosupplier');
    if (!tipe || !sup) { return; }
    function terapkan() {
        var t = tipe.value;
        for (var i = 0; i < sup.options.length; i++) {
            var o = sup.options[i];
            var tp = o.getAttribute('data-tipe');
            var tampil = (t === '' || tp === null || tp === t);
            o.hidden = !tampil;
            o.disabled = !tampil;
        }
        var cur = sup.options[sup.selectedIndex];
        if (cur && cur.disabled) { sup.value = ''; }
    }
    tipe.addEventListener('change', terapkan);
    terapkan();
})();
</script>
