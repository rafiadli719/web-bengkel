                                            <div class="row">
                                                <div class="col-xs-6">

                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-7 control-label no-padding-right" for="form-field-1"> Jml. Pesanan </label>
                                                        <div class="col-sm-5">
                                                            <input type="text" class="form-control" value="<?php echo $total_qty; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                                        <div class="col-sm-10">
                                                            <textarea class="form-control" id="txtnote" name="txtnote" rows="4" disabled><?php echo $note; ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>  
                                                <div class="col-xs-8 col-sm-6">
                                                    <button class="btn btn-primary btn-block" type="submit" 
                                                    id="btnsimpan" name="btnsimpan">
                                                        Proses
                                                    </button>                                                
                                                </div>
                                                <div class="col-xs-8 col-sm-6">
                                                    <a href="pembelian_cab_add.php" 
                                                    onclick="return confirm('Proses Penerimaan Ke Pembelian akan dibatalkan. Lanjutkan?')">                                                                    
                                                        <button class="btn btn-primary btn-block" type="button">
                                                            Batal
                                                        </button>
                                                    </a>                                                
                                                </div>
                                                                                              
                                            </div>