<table class="table table-bordered">
            <tr>
                <td width="70%">
                    <h4 class="header blue">Summary Total</h4>
                </td>
                <td width="30%">
                    <h4 class="header blue">Pembayaran</h4>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="table table-bordered">
                        <tr>
                            <td width="40%"><b>SubTotal Service</b></td>
                            <td width="5%" class="center"><b>:</b></td>
                            <td width="55%" class="right">
                                <?php echo number_format($total_service, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><b>SubTotal Barang</b></td>
                            <td class="center"><b>:</b></td>
                            <td class="right">
                                <?php echo number_format($total_barang, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr class="info">
                            <td><b>SUBTOTAL</b></td>
                            <td class="center"><b>:</b></td>
                            <td class="right">
                                <b><?php echo number_format($tot, 0, ',', '.'); ?></b>
                                <input type="hidden" id="txttotal" name="txttotal" value="<?php echo $tot; ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="row">
                                    <div class="col-xs-8">Diskon</div>
                                    <div class="col-xs-4">
                                        <input type="text" class="form-control input-sm text-center" 
                                        id="txtpotfaktur_persen" name="txtpotfaktur_persen" 
                                        value="0" autocomplete="off" />
                                    </div>
                                </div>
                            </td>
                            <td class="center"><b>:</b></td>
                            <td class="right">
                                <input type="text" class="form-control input-sm text-right" 
                                id="txtpotfaktur_nom" name="txtpotfaktur_nom" 
                                value="0" autocomplete="off" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="row">
                                    <div class="col-xs-8">PPN</div>
                                    <div class="col-xs-4">
                                        <input type="text" class="form-control input-sm text-center" 
                                        id="txtpajak_persen" name="txtpajak_persen" 
                                        value="0" autocomplete="off" />
                                    </div>
                                </div>
                            </td>
                            <td class="center"><b>:</b></td>
                            <td class="right">
                                <input type="text" class="form-control input-sm text-right" 
                                id="txtpajak_nom" name="txtpajak_nom" id="txtpajak_nom1"
                                value="0" readonly="true" />
                            </td>
                        </tr>
                        <tr class="warning">
                            <td><b>GRAND TOTAL</b></td>
                            <td class="center"><b>:</b></td>
                            <td class="right">
                                <input type="text" class="form-control input-sm text-right" 
                                id="txtnet" name="txtnet" 
                                value="<?php echo $net; ?>" readonly="true" 
                                style="font-weight:bold; font-size:14px;" />
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="table table-bordered">
                        <tr>
                            <td width="40%"><b>Total Bayar</b></td>
                            <td width="5%" class="center"><b>:</b></td>
                            <td width="55%">
                                <input type="text" class="form-control input-sm text-right" 
                                id="txtnet1" name="txtnet1" 
                                value="<?php echo $net; ?>" readonly="true" 
                                style="font-weight:bold;" />
                            </td>
                        </tr>
                        <tr>
                            <td><b>Bayar</b></td>
                            <td class="center"><b>:</b></td>
                            <td>
                                <input type="text" class="form-control input-sm text-right" 
                                id="txtbayar" name="txtbayar" 
                                value="<?php echo $bayar; ?>" autocomplete="off" />
                            </td>
                        </tr>
                        <tr>
                            <td><b>Kembali</b></td>
                            <td class="center"><b>:</b></td>
                            <td>
                                <input type="text" class="form-control input-sm text-right" 
                                id="txtkembali" name="txtkembali" 
                                value="<?php echo $kembalian; ?>" readonly="true" />
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <select class="form-control input-sm" name="cbojenis_bayar">
                                    <option value="1">Tunai</option>
                                    <option value="2">Transfer</option>
                                    <option value="3">Kartu Kredit</option>
                                    <option value="4">Kartu Debit</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="row">
                        <div class="col-xs-12">
                            <b>Total Waktu Pengerjaan: <?php echo $total_waktu; ?> Menit</b>
                        </div>
                    </div>
                </td>
            </tr>
        </table>