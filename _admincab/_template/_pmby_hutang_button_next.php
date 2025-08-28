<div class="col-xs-12 col-sm-12">
    <div class="widget-box">
        <div class="widget-body">
            <div class="widget-main">
            
                                            <div class="row">
                                                <div class="col-xs-8 col-sm-3">
                                                    <button class="btn btn-primary btn-block" type="submit" 
                                                    id="btnsimpan" name="btnsimpan">
                                                        Simpan 
                                                    </button>                                                
                                                </div>
                                                <div class="col-xs-8 col-sm-3">
                                                    <a href="pmby_hutang_batal.php?nobyr=<?php echo $nobyr; ?>" 
                                                    onclick="return confirm('Inputan Pembayaran Hutang akan dibatalkan. Lanjutkan?')">                                                                    
                                                        <button class="btn btn-primary btn-block" type="button">
                                                            Batal
                                                        </button>
                                                    </a>                                                
                                                </div>
                                                <div class="col-xs-8 col-sm-3">
                                                    <button class="btn disabled btn-primary btn-block" type="button" 
                                                    id="btncetak" name="btncetak">
                                                        Cetak
                                                    </button>                                                
                                                </div>
                                                <div class="col-xs-8 col-sm-3">
                                                    <a href="pmby_hutang.php">
                                                    <button class="btn btn-primary btn-block" type="button">
                                                        Tutup
                                                    </button>  
                                                    </a>
                                                </div>                                                
                                            </div>
            
            </div>
        </div>
    </div>
</div>