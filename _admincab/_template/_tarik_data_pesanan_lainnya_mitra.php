                                            <div class="row">
                                                <div class="col-xs-6">

                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-5 control-label no-padding-right" for="form-field-1"> Jml. Pesanan </label>
                                                        <div class="col-sm-7">
                                                            <input type="text" class="form-control" value="<?php echo $total_qty_order; ?>" readonly="true" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">

                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-5 control-label no-padding-right" for="form-field-1"> Cara Bayar </label>
                                                        <div class="col-sm-7">
                                                            <select class="form-control" id="cbocarabyr" name="cbocarabyr">
																<option value="Kredit">Kredit</option>
                                                                <option value="Tunai">Tunai</option>																
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">

                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="form-group">
                                                        <label class="col-sm-5 control-label no-padding-right" for="form-field-1"> Syarat </label>
                                                        <div class="col-sm-4">
                                                            <input type="text" class="form-control" id="txtsyarat" name="txtsyarat" value="10" />
                                                        </div>
                                                        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> hari </label>                                                        
                                                    </div>
                                                </div>
                                                <div class="col-xs-12">
                                                    <div class="form-group">
                                                        <label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Keterangan </label>
                                                        <div class="col-sm-10">
                                                            <textarea class="form-control" id="txtnote" name="txtnote" rows="2"><?php echo $note; ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>                                                                                                
                                            </div>
