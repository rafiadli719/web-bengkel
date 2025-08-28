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
                                                        <label class="col-sm-3 control-label no-padding-right" for="txtuser"> Supplier :</label>									
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <select class="col-xs-10 col-sm-12" name="cbosupplier" id="cbosupplier" required >
                                                                <option value="">- Pilih -</option>
                                                                <?php
                                                                    $q = mysqli_query($koneksi,"select nosupplier, namasupplier FROM tblsupplier order by namasupplier asc");
                                                                    while ($row1 = mysqli_fetch_array($q)){
                                                                        $k_id           = $row1['nosupplier'];
                                                                        $k_opis         = $row1['namasupplier'];
                                                                ?>
                                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $no_supplier){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
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