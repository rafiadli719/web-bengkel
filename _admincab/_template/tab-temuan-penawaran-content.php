<?php ?>

<div class="row">
  <div class="col-xs-12">
    <?php include "_template/_servis_input_temuan.php"; ?>
  </div>
</div>

<div class="space-8"></div>

<div class="row">
  <div class="col-xs-12">
    <div class="widget-box widget-color-green2">
      <div class="widget-header widget-header-small">
        <h5 class="widget-title">
          <i class="ace-icon fa fa-shopping-cart"></i>
          Tambah Penawaran Part
        </h5>
        <div class="widget-toolbar">
          <div class="btn-group">
            <button class="btn btn-white btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
              <i class="fa fa-download"></i> Download <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
              <li class="dropdown-header"><i class="fa fa-file-excel-o"></i> Format Excel (.xls)</li>
              <li>
                <a target="_blank" href="temuan-penawaran-export.php?format=excel&no_service=<?php echo $no_service; ?>">
                  <i class="fa fa-file-excel-o text-success"></i> Download Excel
                </a>
              </li>
              <li role="separator" class="divider"></li>
              <li class="dropdown-header"><i class="fa fa-file-pdf-o"></i> Format PDF (.pdf)</li>
              <li>
                <a target="_blank" href="temuan-penawaran-export.php?format=pdf&no_service=<?php echo $no_service; ?>">
                  <i class="fa fa-file-pdf-o text-danger"></i> Download PDF
                </a>
              </li>
            </ul>
          </div>
          <a href="#" data-action="collapse">
            <i class="ace-icon fa fa-chevron-down"></i>
          </a>
        </div>
      </div>
      <div class="widget-body">
        <div class="widget-main">
          <form id="formTambahPenawaran" method="POST">
            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
            <input type="hidden" name="btnaddpenawaran" value="1">

            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Temuan Terkait</label>
                  <select name="temuan_id" class="form-control input-sm">
                    <option value="">- Pilih Temuan -</option>
                    <?php
                    $q_t = mysqli_query($koneksi, "SELECT id, COALESCE(temuan_custom, (SELECT nama_temuan FROM tbmaster_temuan WHERE kode_temuan = tbservis_temuan.kode_temuan)) AS nama FROM tbservis_temuan WHERE no_service='{$no_service}' ORDER BY id DESC");
                    if ($q_t) {
                      while($t = mysqli_fetch_array($q_t)){
                        echo "<option value='".$t['id']."'>".htmlspecialchars($t['nama'])."</option>";
                      }
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-sm-6">
                <label>&nbsp;</label>
                <div>
                  <button type="button" class="btn btn-primary btn-sm" onclick="$('#modalFastMovesV2').modal('show');">
                    <i class="ace-icon fa fa-bolt"></i> Pilih Part dari Fast Moves
                  </button>
                  <button type="button" class="btn btn-warning btn-sm" onclick="$('#modalInputCustom').modal('show');">
                    <i class="ace-icon fa fa-plus-circle"></i> Input Barang Custom
                  </button>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Kode Part</label>
                  <input type="text" class="form-control input-sm" id="kode_barang_penawaran" name="kode_barang" placeholder="Kode part" required>
                </div>
              </div>
              <div class="col-sm-8">
                <div class="form-group">
                  <label>Nama Part</label>
                  <input type="text" class="form-control input-sm" id="nama_barang_penawaran" name="nama_barang" placeholder="Nama part" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Harga Satuan</label>
                  <input type="number" class="form-control input-sm text-right" id="harga_satuan_penawaran" name="harga_satuan" placeholder="0" min="0" required>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Quantity</label>
                  <input type="number" class="form-control input-sm text-center" id="quantity_penawaran" name="quantity" value="1" min="1" required>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="form-group">
                  <label>Total</label>
                  <input type="text" class="form-control input-sm text-right" id="total_penawaran_display" value="Rp 0" readonly>
                </div>
              </div>
            </div>

            <div class="text-right">
              <button type="submit" class="btn btn-success btn-sm" name="btnaddpenawaran">
                <i class="ace-icon fa fa-save"></i> Simpan Penawaran
              </button>
              <button type="reset" class="btn btn-default btn-sm">
                <i class="ace-icon fa fa-undo"></i> Reset
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="space-14"></div>

<div class="row">
  <div class="col-xs-12">
    <?php include "_template/_servis_list_temuan_penawaran.php"; ?>
  </div>
</div>

<script type="text/javascript">
// Global function for total calculation
function hitungTotalPenawaran() {
  var h = parseInt(document.getElementById('harga_satuan_penawaran').value || 0);
  var q = parseInt(document.getElementById('quantity_penawaran').value || 0);
  var t = document.getElementById('total_penawaran_display');
  if(t) {
    var total = h * q;
    t.value = 'Rp ' + total.toLocaleString('id-ID');
  }
}

// Alias for backward compatibility
var recalc = hitungTotalPenawaran;

document.addEventListener('DOMContentLoaded', function(){
  var h = document.getElementById('harga_satuan_penawaran');
  var q = document.getElementById('quantity_penawaran');
  if(h) {
    h.addEventListener('input', hitungTotalPenawaran);
    h.addEventListener('change', hitungTotalPenawaran);
  }
  if(q) {
    q.addEventListener('input', hitungTotalPenawaran);
    q.addEventListener('change', hitungTotalPenawaran);
  }
  
  // Initial calculation
  hitungTotalPenawaran();
  
  var f = document.getElementById('form-input-temuan');
  if(f){
    f.addEventListener('submit', function(){
      if(!f.querySelector('input[name="btnaddtemuan"]')){
        var x = document.createElement('input');
        x.type = 'hidden';
        x.name = 'btnaddtemuan';
        x.value = '1';
        f.appendChild(x);
      }
    });
  }
});
</script>

