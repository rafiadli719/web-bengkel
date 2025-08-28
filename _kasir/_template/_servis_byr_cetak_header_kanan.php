
											<div class="widget-box">
												<div class="widget-body">
													<div class="widget-main">
                                                    
                                                        <div class="row">
															<div class="col-xs-8 col-sm-6">
                                                                <label>No. Service :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" class="form-control" 
                                                                        value="<?php echo $no_service; ?>" readonly="true" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="space space-8"></div>                                                                                                                
                                                        <div class="row">
															<div class="col-xs-8 col-sm-6">
                                                                <label>Km Sekarang :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" class="form-control" 
                                                                        id="txtkm_skr" name="txtkm_skr" 
                                                                        value="<?php echo $km_skr; ?>" 
                                                                        autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-6">
                                                                <label for="id-date-picker-1">Km Berikut :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" class="form-control" 
                                                                        id="txtkm_next" name="txtkm_next" 
                                                                        value="<?php echo $km_berikut; ?>" autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="space space-8"></div>                                                                                                                 
															<div class="col-xs-8 col-sm-12">
                                                                <h4 class="header green">Keluhan</h4>
                                                            </div>
															<div class="col-xs-8 col-sm-12">
                                                                <textarea class="col-xs-10 col-sm-12" id="txtkeluhan" name="txtkeluhan" rows="2" placeholder="Isikan Keluhan.."></textarea>
                                                            </div>
															<div class="col-xs-8 col-sm-12">
                                                                <button class="btn btn-primary btn-block" type="submit" 
                                                                id="btnaddkeluhan" name="btnaddkeluhan">
                                                                    + Keluhan
                                                                </button>
                                                            </div>
															<div class="col-xs-8 col-sm-12">
                                                                <table class="table table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <td class="center" width="5%">No</td>
                                                                            <td width="95%">Keluhan</td>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                            $no = 0 ;
                                                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                                                            keluhan, id 
                                                                                                            FROM 
                                                                                                            tbservis_keluhan 
                                                                                                            WHERE 
                                                                                                            no_service='$no_service'");
                                                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                                                $no++;
                                                                        ?>
                                                                        <tr>
                                                                            <td class="center"><?php echo $no ?></td>
                                                                            <td>
                                                                                <?php echo $tampil['keluhan']?>&nbsp;
                                                                                <a class="red" data-rel="tooltip" title="Delete" 
                                                                                href="keluhan-hapus.php?sid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                                                                onclick="return confirm('Item Keluhan akan dihapus. Lanjutkan?')">
                                                                                <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                                                                </a>
                                                                            </td>														
                                                                        </tr>
                                                                    <?php
                                                                        }
                                                                    ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>

															<div class="col-xs-8 col-sm-12">
                                                                <h4 class="header green">Item Pengerjaan</h4>
                                                            </div>
															<div class="col-xs-8 col-sm-7">
                                                                <label>Item Pengerjaan :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <textarea class="col-xs-10 col-sm-12" id="txtitempengerjaan" name="txtitempengerjaan" rows="2"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>                                                            
															<div class="col-xs-8 col-sm-5">
                                                                <label>Mekanik :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <select class="form-control" name="cbomekanik" id="cbomekanik" >
                                                                        <option value="">- Pilih Mekanik -</option>
                                                                        <?php
                                                                            $sql="select 
                                                                                    nomekanik, nama 
                                                                                    FROM tblmekanik 
                                                                                    where nama<>'-' 
                                                                                    order by nama asc";
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
                                                                </div>
                                                            </div>                                                            
															<div class="col-xs-8 col-sm-12">
                                                                <button class="btn btn-primary btn-block" type="submit" 
                                                                id="btnaddpengerjaan" name="btnaddpengerjaan">
                                                                    + Item Pengerjaan
                                                                </button>
                                                            </div>
															<div class="col-xs-8 col-sm-12">
                                                                <table class="table table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <td class="center" width="5%"></td>
                                                                            <td width="60%">Item Pengerjaan</td>
                                                                            <td width="35%">Mekanik</td>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                            $no = 0 ;
                                                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                                                            item_pengerjaan, kd_mekanik, id 
                                                                                                            FROM 
                                                                                                            tbservis_pengerjaan 
                                                                                                            WHERE 
                                                                                                            no_service='$no_service'");
                                                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                                                $no++;
                                                                                $kd_mekanik=$tampil['kd_mekanik'];
                                                                                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                                                nama 
                                                                                                                FROM tblmekanik 
                                                                                                                WHERE nomekanik='$kd_mekanik'");			
                                                                                $tm_cari=mysqli_fetch_array($cari_kd);
                                                                                $nama_mekanik=$tm_cari['nama'];				        
                                                                        ?>
                                                                        <tr>
                                                                            <td class="center">
                                                                                <a class="red" data-rel="tooltip" title="Delete" 
                                                                                href="pengerjaan-hapus.php?sid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                                                                onclick="return confirm('Item Keluhan akan dihapus. Lanjutkan?')">
                                                                                <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                                                                </a>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $tampil['item_pengerjaan']?>
                                                                            </td>														
                                                                            <td>
                                                                                <?php echo $nama_mekanik; ?>
                                                                            </td>														
                                                                        </tr>
                                                                    <?php
                                                                        }
                                                                    ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>

															<div class="col-xs-8 col-sm-12">
                                                                <table class="table table-bordered">
                                                                    <thead
                                                                        <tr>
                                                                            <td colspan="3" bgcolor="gainsboro" align="center"><b>History Service</b></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td width="10%">No. Service</td>
                                                                            <td class="center" width="10%">Tanggal</td>
                                                                            <td width="80%">Keluhan</td>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                                                        no_service, tanggal_trx, status, total_grand 
                                                                                                        FROM view_service 
                                                                                                        WHERE no_polisi='$no_polisi' and 
                                                                                                        status='4' 
                                                                                                        order by tanggal desc");
                                                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                                                $status=$tampil['status'];
                                                                                $no_service=$tampil['no_service'];

                                                                                //if($status=='4') {
                                                                                    $ket_status="Selesai";
                                                                                //}
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo $tampil['no_service']?></td>														
                                                                            <td class="center"><?php echo $tampil['tanggal_trx']?></td>														                                                                
                                                                            <td>
                                                                                <table width="100%">
                                                                                    <?php 
                                                                                        $no1 = 0 ;
                                                                                        $sql1 = mysqli_query($koneksi,"SELECT 
                                                                                                                        keluhan 
                                                                                                                        FROM tbservis_keluhan 
                                                                                                                        WHERE no_service='$no_service'");
                                                                                        while ($tampil1 = mysqli_fetch_array($sql1)) {
                                                                                            $no1++;
                                                                                    ?> 
                                                                                    <tr valign="top">
                                                                                        <td width="5%"><?php echo $no1; ?></td>
                                                                                        <td width="95%"><?php echo $tampil1['keluhan']; ?></td>
                                                                                    </tr>
                                                                                    <?php 
                                                                                        }
                                                                                    ?>
                                                                                </table>
                                                                            </td>		
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