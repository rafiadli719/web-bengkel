						<div class="row">
							<div class="col-xs-12 col-sm-9">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">	
                                        
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-4">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> No. Polisi :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $kode_pelanggan; ?>" disabled />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> Pemilik :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $pemilik; ?>" disabled />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> Jenis :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $jenis; ?>" disabled />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-4">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> Merk/Tipe :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $merek; ?>" disabled />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="form-field-1"> Warna :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $warna; ?>" disabled />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-4">
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> No. Rangka :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $no_rangka; ?>" disabled />
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="col-sm-4 control-label no-padding-right" for="txtnopesanan"> No. Mesin :</label>									
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" value="<?php echo $no_mesin; ?>" disabled />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                        </div>
                                    </div>
                                </div>
                            </div>

							<div class="col-xs-12 col-sm-3">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">	
                                        
                                            <div class="row">
                                                <div class="col-xs-12 col-sm-12">
                                                    <table id="dynamic-table" class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Tanggal</th>
                                                                <th>Kilometer</th>
                                                                <th>Keterangan</th>                                                                
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        
                                                        <?php 
                                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                                        DATE_FORMAT(tanggal,'%d-%m-%Y') AS tgl_service 
                                                                                        FROM 
                                                                                        tblservice 
                                                                                        WHERE 
                                                                                        no_polisi='$kode_pelanggan'");
                                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                        ?>
                                                        
                                                            <tr>
                                                                <td><?php echo $tampil['tgl_service']?></td>														
                                                                <td></td>														
                                                                <td></td>														                                                        
                                                            </tr>

                                                        <?php
                                                                }
                                                        ?>      
                                                  
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>