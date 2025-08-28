                                                        <div class="row">
                                                            <table width="100%">
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Sub Total :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($total,0)?>" 
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
                                                                            value="<?php echo $diskon_persen;?>%" 
                                                                            readonly="true" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($diskon_nom,0)?>" 
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
                                                                            value="<?php echo $ppn_persen;?>%" 
                                                                            readonly="true" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                    <td width="30%">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($ppn_nom,0)?>" 
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
                                                                            value="<?php echo number_format($net,0)?>" 
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
                                                                            value="<?php echo number_format($bayar,0)?>" 
                                                                            readonly="true" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>       
                                                                <tr>
                                                                    <td width="50%" align="right">
                                                                    <label>Kembalian :</label>
                                                                    </td>
                                                                    <td colspan="2">
                                                                        <div class="col-xs-8 col-sm-12">
                                                                            <input type="text" class="form-control" 
                                                                            value="<?php echo number_format($kembali,0)?>" 
                                                                            readonly="true" />
                                                                        </div>                                                                    
                                                                    </td>
                                                                </tr>                                                                                                                                                                                                                                                         
                                                            </table>														                                                            
														</div>                                                                                                                