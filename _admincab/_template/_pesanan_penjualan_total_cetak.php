                                                        <div class="row">
                                                            <table width="100%">
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Sub Total :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($total_jual,0)?>" 
                                                                            readonly="true" />
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
                                                                            value="<?php echo number_format($diskon,0)?>%" 
                                                                            readonly="true" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpotfaktur_nom" name="txtpotfaktur_nom" 
                                                                            value="<?php echo number_format($total_diskon,0)?>" 
                                                                            readonly="true" />
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
                                                                            value="<?php echo number_format($pajak,0)?>%" 
                                                                            readonly="true" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpajak_nom1" name="txtpajak_nom1" readonly="true" 
                                                                            value="<?php echo number_format($total_pajak,0)?>" 
                                                                            readonly="true" />
                                                                        
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
                                                                            value="<?php echo number_format($netto,0)?>" 
                                                                            readonly="true" />

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
                                                                            value="<?php echo $dp; ?>" disabled />
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
                                                                            value="<?php echo number_format($kekurangan,0)?>" disabled />

                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                                                                                                                                                                                                         
                                                            </table>														                                                            
														</div>                                                                                                                