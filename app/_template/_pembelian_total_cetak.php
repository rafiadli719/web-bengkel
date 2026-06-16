                                                        <div class="row">
                                                            <table width="100%" class="table-borderless">
                                                                <tr>
                                                                    <td width="60%" align="right" style="padding: 5px;">
                                                                    <strong>Sub Total :</strong>
                                                                    </td>
                                                                    <td colspan="2" width="40%" style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        value="<?php echo number_format($total_beli,0)?>" 
                                                                        readonly="true" style="text-align: right;" />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="right" style="padding: 5px;">
                                                                    <strong>Potongan Faktur :</strong>
                                                                    </td>
                                                                    <td width="15%" style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtpotfaktur_persen" name="txtpotfaktur_persen" 
                                                                        value="<?php echo $diskon; ?> %" readonly="true" style="text-align: right;" />
                                                                    </td>
                                                                    <td width="25%" style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtpotfaktur_nom" name="txtpotfaktur_nom" 
                                                                        value="<?php echo number_format($total_diskon,0)?>" disabled style="text-align: right;" />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="right" style="padding: 5px;">
                                                                    <strong>Pajak :</strong>
                                                                    </td>
                                                                    <td style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtpajak_persen" name="txtpajak_persen" 
                                                                        value="<?php echo $pajak; ?> %" readonly="true" style="text-align: right;" />
                                                                    </td>
                                                                    <td style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtpajak_nom1" name="txtpajak_nom1" readonly="true" 
                                                                        value="<?php echo number_format($total_pajak,0)?>" disabled style="text-align: right;" />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="right" style="padding: 5px;">
                                                                    <strong>Total Netto :</strong>
                                                                    </td>
                                                                    <td colspan="2" style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtnet1" name="txtnet1" 
                                                                        value="<?php echo number_format($netto,0)?>" 
                                                                        readonly="true" style="text-align: right; font-weight: bold;" />
                                                                    </td>
                                                                </tr>                                                                
                                                                <tr>
                                                                    <td align="right" style="padding: 5px;">
                                                                    <strong>DP/Uang Muka :</strong>
                                                                    </td>
                                                                    <td colspan="2" style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtdp" name="txtdp" 
                                                                        value="<?php echo $dp; ?>" readonly="true" style="text-align: right;" />
                                                                    </td>
                                                                </tr>       
                                                                <tr>
                                                                    <td align="right" style="padding: 5px;">
                                                                    <strong>Kekurangan :</strong>
                                                                    </td>
                                                                    <td colspan="2" style="padding: 5px;">
                                                                        <input type="text" class="form-control text-right" 
                                                                        id="txtkekurangan1" name="txtkekurangan1" 
                                                                        value="<?php echo number_format($kekurangan,0)?>" disabled style="text-align: right; font-weight: bold; color: red;" />
                                                                    </td>
                                                                </tr>                                                                                                                                                                                                                                                         
                                                            </table>															                                                            
														</div>