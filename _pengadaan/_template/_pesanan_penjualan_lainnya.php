                                            <div class="row">
                                                <div class="col-xs-6">

                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-7 control-label no-padding-right" for="form-field-1"> Jml. Pesanan </label>
                                                        <div class="col-sm-5">
                                                            <input type="text" class="form-control" value="<?php echo $total_qty_order; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                                        <div class="col-sm-10">
                                                            <textarea class="form-control" id="txtnote" name="txtnote" rows="4"></textarea>
                                                        </div>
                                                    </div>
                                                </div>  

                                                <div class="col-xs-8 col-sm-3">
                                                    <button class="btn btn-primary btn-block" type="submit" 
                                                    id="btnsimpan" name="btnsimpan">
                                                        Simpan
                                                    </button>                                                
                                                </div>
                                                <div class="col-xs-8 col-sm-3">
                                                    <a href="pesanan_penjualan_batal.php?suser=<?php echo $_nama; ?>&scabang=<?php echo $kd_cabang; ?>" 
                                                    onclick="return confirm('Inputan Pesanan Penjualan akan dibatalkan. Lanjutkan?')">                                                                    
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
                                                    <a href="pesanan_penjualan.php">
                                                    <button class="btn btn-primary btn-block" type="button">
                                                        Tutup
                                                    </button>  
                                                    </a>
                                                </div>                                                
                                                                                              
                                            </div>