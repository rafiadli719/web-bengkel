                                                        <div class="row">
                                                            <table width="100%">
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Sub Total :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($tot,0)?>" 
                                                                            readonly="true" />
                                                                            
                                                                            <input type="hidden" id="txttotal" name="txttotal" 
                                                                            class="form-control" value="<?php echo $tot; ?>"/>
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
                                                                            value="<?php echo number_format($tot,0)?>" 
                                                                            readonly="true" />
                                                                            
                                                                            <input type="hidden" id="txtnet" name="txtnet" 
                                                                            class="form-control" value="<?php echo number_format($tot,0)?>"/>
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Jumlah Bayar :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtdp" name="txtdp" 
                                                                            value="<?php echo $tot; ?>" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>       
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Sisa Bayar :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtkekurangan1" name="txtkekurangan1" 
                                                                            value="<?php echo number_format($tot,0)?>" disabled />

                                                                            <input type="hidden" id="txtkekurangan" name="txtkekurangan" 
                                                                            class="form-control" value="<?php echo $tot; ?>"/>  
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                                                                                                                                                                                                         
                                                            </table>														                                                            
														</div>                                                                                                                