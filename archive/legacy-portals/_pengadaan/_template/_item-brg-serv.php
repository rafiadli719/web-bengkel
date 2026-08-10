<div class="row">
    <div class="col-sm-12">
        
        <div class="widget-box">
            <div class="widget-header">
                <h4 class="widget-title">Barang</h4>
            </div>
            <div class="widget-body">
                <div class="widget-main no-padding">
                    <form class="form-horizontal" action="cari_pelanggan_jl.php" method="post" role="form">
                    <input type="hidden" name="txtnopol" class="form-control" 
                    value="<?php echo $kode_pelanggan; ?>"/>
                        <div class="row">
                            <div class="col-xs-12 col-sm-4">
                                <div class="input-group">
                                    <input type="text" id="txtcaribrg" name="txtcaribrg" 
                                    class="form-control search-query" 
                                    placeholder="Kode/Nama Item.." 
                                    value="<?php echo $txtcaribrg; ?>" />
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-purple btn-sm">
                                            <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-4">
                                <input type="text" id="txtnamabrg" name="txtnamabrg" 
                                class="col-xs-10 col-sm-12" 
                                value="<?php echo $txtnamaitem; ?>" disabled />
                            </div>
                    </form>      
                    <form action="" method="post">
                        <input type="hidden" name="txtkdbarang" 
                        class="form-control" value="<?php echo $txtcaribrg; ?>"/>
                                                    
                        <div class="col-xs-12 col-sm-2">
                            <div class="form-group">
                                <label class="col-sm-6 control-label no-padding-right" for="form-field-1"> Jumlah : </label>
                                <div class="col-sm-6">
                                    <input type="text" id="txtqty" name="txtqty" 
                                    class="col-xs-10 col-sm-12" value="0" autocomplete="off" />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-2">
                            <div class="form-group">
                                <label class="col-sm-6 control-label no-padding-right" for="form-field-1"> Potongan : </label>
                                <div class="col-sm-6">
                                    <input type="text" id="txtpot" name="txtpot" 
                                    class="col-xs-10 col-sm-12" value="0" autocomplete="off" />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-1">
                                <button type="submit" 
                                class="btn btn-sm btn-primary" id="btnadd" name="btnadd">+</button>
                            </div> 
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</div>