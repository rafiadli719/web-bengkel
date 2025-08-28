                                            <div class="row">
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Cara Bayar </label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $carabayar; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-7 control-label no-padding-right" for="form-field-1"> Jml. Jual </label>
                                                        <div class="col-sm-5">
                                                            <input type="text" class="form-control" value="<?php echo $total_qty_jual; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Syarat </label>
                                                        <div class="col-sm-4">
                                                            <input type="text" class="form-control" 
                                                            id="txtsyarat" name="txtsyarat" value="<?php echo $syarat; ?> Hari" disabled />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-7 control-label no-padding-right" for="form-field-1"> Jml. Pesanan diterima </label>
                                                        <div class="col-sm-5">
                                                            <input type="text" class="form-control" value="<?php echo $total_qty_order; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>                                                
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Tanggal JT </label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" 
                                                            id="txtjt" name="txtjt" disabled value="<?php echo $tanggal_jt; ?>" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-7 control-label no-padding-right" for="form-field-1"> Total Retur </label>
                                                        <div class="col-sm-5">
                                                            <input type="text" class="form-control" value="<?php echo $total_qty_retur; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>                                                
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                                        <div class="col-sm-10">
                                                            <textarea class="form-control" id="txtnote" name="txtnote" rows="2" disabled><?php echo $note; ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>                                                                                                
                                            </div>