
											<div class="widget-box">
												<div class="widget-body">
													<div class="widget-main">
                                                    
                                                        <div class="row">
															<div class="col-xs-8 col-sm-6">
                                                                <label>No. Service :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" class="form-control" 
                                                                        id="txtnoserv" name="txtnoserv" 
                                                                        value="<?php echo $LastID; ?>" autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-6">
                                                                <label for="id-date-picker-1">Tanggal :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <div class="input-group">
                                                                            <input class="form-control date-picker" 
                                                                            id="id-date-picker-1" name="id-date-picker-1" 
                                                                            type="text" data-date-format="dd-mm-yyyy" />
                                                                            <span class="input-group-addon">
                                                                                <i class="fa fa-calendar bigger-110"></i>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr />
                                                        <div class="row">
															<div class="col-xs-8 col-sm-6">
                                                                <label>Km Sekarang :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" class="form-control" 
                                                                        id="txtkm_skr" name="txtkm_skr" 
                                                                        value="<?php echo $txtkm_skr; ?>" 
                                                                        required autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-6">
                                                                <label for="id-date-picker-1">Km Berikut :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" class="form-control" 
                                                                        id="txtkm_next" name="txtkm_next" 
                                                                        readonly="true" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="space space-8"></div>                                                                                                                
														<label>Status :</label>
                                                        <div class="row">
															<div class="col-xs-8 col-sm-6">
                                                                <select class="form-control" id="cbostatus" name="cbostatus">
                                                                    <option value="Belum Selesai">Belum Selesai</option>
                                                                    <option value="Selesai">Selesai</option>
                                                                </select>
                                                            </div>
														</div>
                                                        <div class="space space-8"></div>                                                                                                                
														<label>Keterangan/Keluhan :</label>
                                                        <div class="row">
															<div class="col-xs-8 col-sm-12">
                                                                <textarea class="form-control" id="txtnote" name="txtnote" rows="3"></textarea>
															</div>
														</div>

                                                        <hr />

                                                        <div class="row">
															<div class="col-xs-8 col-sm-7">
                                                                <select class="form-control" name="cbomekanik1" id="cbomekanik1" required >
                                                                <option value="">- Mekanik 1 -</option>
                                                                <?php
                                                                $q = mysqli_query($koneksi,"select nomekanik, nama FROM tblmekanik where nama<>'-' order by nama asc");
                                                                while ($row1 = mysqli_fetch_array($q)){
                                                                    $k_id           = $row1['nomekanik'];
                                                                    $k_opis         = $row1['nama'];
                                                                ?>
                                                                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $cbo_mekanik1){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                                                <?php
                                                                    }
                                                                ?>
                                                                </select>
															</div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Biaya</label>                                                            
                                                            </div>
															<div class="col-xs-8 col-sm-3">
                                                                <input type="text" class="form-control" 
                                                                id="txtbiaya1" name="txtbiaya1" 
                                                                autocomplete="off" />
															</div>                                                            
														</div>                                                        

                                                        <div class="row">
															<div class="col-xs-8 col-sm-7">
                                                                <select class="form-control" name="cbomekanik2" id="cbomekanik2" >
                                                                <option value="">- Mekanik 2 -</option>
                                                                <?php
                                                                    $sql="select nomekanik, nama FROM tblmekanik where nama<>'-' order by nama asc";
                                                                    $sql_row=mysqli_query($koneksi,$sql);
                                                                    while($sql_res=mysqli_fetch_assoc($sql_row))	
                                                                    {
                                                                ?>
                                                                <option value="<?php echo $sql_res["nomekanik"]; ?>"><?php echo $sql_res["nama"]; ?></option>
                                                                <?php
                                                                    }
                                                                ?>
                                                                </select>
															</div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Biaya</label>                                                            
                                                            </div>
															<div class="col-xs-8 col-sm-3">
                                                                <input type="text" class="form-control" 
                                                                id="txtbiaya2" name="txtbiaya2" 
                                                                autocomplete="off" />
															</div>                                                            
														</div>                                                        

                                                        <div class="row">
															<div class="col-xs-8 col-sm-7">
                                                                <select class="form-control" name="cbomekanik3" id="cbomekanik3" >
                                                                <option value="">- Mekanik 3 -</option>
                                                                <?php
                                                                    $sql="select nomekanik, nama FROM tblmekanik where nama<>'-' order by nama asc";
                                                                    $sql_row=mysqli_query($koneksi,$sql);
                                                                    while($sql_res=mysqli_fetch_assoc($sql_row))	
                                                                    {
                                                                ?>
                                                                <option value="<?php echo $sql_res["nomekanik"]; ?>"><?php echo $sql_res["nama"]; ?></option>
                                                                <?php
                                                                    }
                                                                ?>
                                                                </select>
															</div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Biaya</label>                                                            
                                                            </div>
															<div class="col-xs-8 col-sm-3">
                                                                <input type="text" class="form-control" 
                                                                id="txtbiaya3" name="txtbiaya3" 
                                                                autocomplete="off" />
															</div>                                                            
														</div>                                                        

                                                        <div class="row">
															<div class="col-xs-8 col-sm-7">
                                                                <select class="form-control" name="cbomekanik4" id="cbomekanik4" >
                                                                <option value="">- Mekanik 4 -</option>
                                                                <?php
                                                                    $sql="select nomekanik, nama FROM tblmekanik where nama<>'-' order by nama asc";
                                                                    $sql_row=mysqli_query($koneksi,$sql);
                                                                    while($sql_res=mysqli_fetch_assoc($sql_row))	
                                                                    {
                                                                ?>
                                                                <option value="<?php echo $sql_res["nomekanik"]; ?>"><?php echo $sql_res["nama"]; ?></option>
                                                                <?php
                                                                    }
                                                                ?>
                                                                </select>
															</div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Biaya</label>                                                            
                                                            </div>
															<div class="col-xs-8 col-sm-3">
                                                                <input type="text" class="form-control" 
                                                                id="txtbiaya4" name="txtbiaya4" 
                                                                autocomplete="off" />
															</div>                                                            
														</div>                                                        
                                                        <hr />
                                                        <div class="row">
															<div class="col-xs-8 col-sm-9">
                                                                <label>Total jasa biaya mekanik :</label>                                                            
															</div>
															<div class="col-xs-8 col-sm-3">
                                                                <input type="text" class="form-control" 
                                                                id="txtbiayamekanik" name="txtbiayamekanik" 
                                                                autocomplete="off" />
															</div>                                                            
														</div>                                                                                                                
                                                        
                                                        
													</div>
												</div>
											</div>        


											<div class="widget-box">
												<div class="widget-body">
													<div class="widget-main">                                
                                                    
                                                        <div class="row">
                                                            <table width="100%">
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Sub Total :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($total_keseluruhan,0)?>" 
                                                                            readonly="true" />
                                                                            
                                                                            <input type="hidden" id="txttotal" name="txttotal" 
                                                                            class="form-control" value="<?php echo $total_keseluruhan; ?>"/>
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Potongan Faktur :</label>
                                                                    </td>
                                                                    <td width="20%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpotfaktur_persen" name="txtpotfaktur_persen" 
                                                                            value="0" autocomplete="off" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpotfaktur_nom" name="txtpotfaktur_nom" 
                                                                            value="0" autocomplete="off" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Pajak :</label>
                                                                    </td>
                                                                    <td width="20%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpajak_persen" name="txtpajak_persen" 
                                                                            value="0" autocomplete="off" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpajak_nom1" name="txtpajak_nom1" readonly="true" 
                                                                            value="0" autocomplete="off" />
                                                                            
                                                                            <input type="hidden" id="txtpajak_nom" name="txtpajak_nom" 
                                                                            class="form-control" value="0"/>
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Total Netto :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtnet1" name="txtnet1" 
                                                                            value="<?php echo number_format($total_keseluruhan,0)?>" 
                                                                            readonly="true" />
                                                                            
                                                                            <input type="hidden" id="txtnet" name="txtnet" 
                                                                            class="form-control" value="<?php echo number_format($total_keseluruhan,0)?>"/>
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Bayar :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtdp" name="txtdp" 
                                                                            value="<?php echo $total_keseluruhan; ?>" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                                                                                
                                                            </table>															                                                            
														</div>                                                                                                                

													</div>
												</div>
											</div>        