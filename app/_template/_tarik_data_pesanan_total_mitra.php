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
                                                                            id="txtsubtotal1" name="txtsubtotal1" readonly="true" />
                                                                            
                                                                            <input type="hidden" id="txtsubtotal" name="txtsubtotal" 
                                                                            class="form-control" value="<?php echo $total_jual; ?>"/>                                                                            
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
                                                                            value="<?php echo $diskon; ?>" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpotfaktur_nom" name="txtpotfaktur_nom" 
                                                                            value="<?php echo $total_diskon; ?>" />
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
                                                                            value="<?php echo $pajak; ?>" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            id="txtpajak_nom" name="txtpajak_nom" readonly="true" 
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
                                                                            id="txtnet" name="txtnet" 
                                                                            value="<?php echo $netto; ?>" 
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
                                                                            value="<?php echo $dp; ?>" />
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
                                                                            id="txtkekurangan" name="txtkekurangan" 
                                                                            value="<?php echo $kekurangan; ?>" disabled />

                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                                                                                                                                                                                                         
                                                            </table>														                                                            
														</div>                                                                                                                