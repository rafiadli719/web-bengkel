<div class="row">
    <div class="col-xs-8 col-sm-3">
        <label>Masukkan Kata Kunci :</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <input type="text" id="txtkey" name="txtkey" 
                value="<?php echo $txtkey; ?>" class="form-control" />    
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-3">
        <label>Cari Data Pada :</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <select class="form-control" name="cbocari" id="cbocari" >
                <option value="">- Seluruh Kolom Field -</option>
                <?php
                    $q = mysqli_query($koneksi,"select id, cari FROM tbcari where tipe='11' order by id asc");
                    while ($row1 = mysqli_fetch_array($q)){
                        $k_id           = $row1['id'];
                        $k_opis         = $row1['cari'];
                ?>
                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $txtcari){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-3">
        <label>Urut Berdasarkan :</label>
        <div class="row">
            <div class="col-xs-8 col-sm-12">    
                <select class="form-control" name="cbourut" id="cbourut" >
                <?php
                    $q = mysqli_query($koneksi,"select id, urut FROM tburut where tipe='11' order by id asc");
                    while ($row1 = mysqli_fetch_array($q)){
                        $k_id           = $row1['id'];
                        $k_opis         = $row1['urut'];
                ?>
                <option value='<?php echo $k_id; ?>' <?php if ($k_id == $txturut){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                <?php
                    }
                ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col-xs-8 col-sm-3">
        <label>&nbsp;</label>
        <div class="row">
            <div class="col-xs-8 col-sm-6">    
                <button class="btn <?php echo $tipebtn1; ?> btn-block" type="submit" 
                id="btnasc" name="btnasc">
                <i class="ace-icon fa fa-arrow-up icon-on-right"></i> Ascending
                </button>
            </div>
            <div class="col-xs-8 col-sm-6">    
                <button class="btn <?php echo $tipebtn2; ?> btn-block" type="submit" 
                id="btndesc" name="btndesc">
                <i class="ace-icon fa fa-arrow-down icon-on-right"></i> Descending
                </button>
            </div>
        </div>
    </div>
</div>
