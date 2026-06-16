<!-- Tab Progress Mekanik -->
<div class="progress-content">
    <div class="row">
        <div class="col-xs-12">
            <div class="padding-18">
                
                <!-- Progress Overview -->
                <div class="form-section">
                    <h5 class="red">
                        <i class="ace-icon fa fa-users"></i>
                        Progress Pengerjaan Mekanik
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Total Progress</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-primary" id="totalProgress">0%</h2>
                                            <small>Rata-rata Progress</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Mekanik Aktif</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-success" id="mekanikAktif">0</h2>
                                            <small>Sedang Bekerja</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Estimasi Selesai</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <h2 class="text-warning" id="estimasiSelesai">-</h2>
                                            <small>Jam</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Status</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="text-center">
                                            <span class="label label-info" id="statusPengerjaan">Menunggu</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual Mechanic Progress -->
                <div class="form-section">
                    <h5 class="blue">
                        <i class="ace-icon fa fa-user"></i>
                        Progress Per Mekanik
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <!-- Kepala Mekanik Progress -->
                    <div class="progress-section">
                        <h6 class="blue">Kepala Mekanik</h6>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Kepala Mekanik 1:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_kepala1" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_kepala1" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-primary" id="bar_kepala1" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Kepala Mekanik 2:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_kepala2" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_kepala2" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-primary" id="bar_kepala2" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Progress -->
                    <div class="progress-section">
                        <h6 class="orange">Admin</h6>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Admin 1:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_admin1" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_admin1" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-warning" id="bar_admin1" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Admin 2:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_admin2" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_admin2" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-warning" id="bar_admin2" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mekanik Progress -->
                    <div class="progress-section">
                        <h6 class="purple">Mekanik</h6>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Mekanik 1:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_mekanik1" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_mekanik1" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-success" id="bar_mekanik1" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Mekanik 2:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_mekanik2" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_mekanik2" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-success" id="bar_mekanik2" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Mekanik 3:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_mekanik3" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_mekanik3" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-success" id="bar_mekanik3" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Mekanik 4:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="nama_mekanik4" readonly />
                                            <span class="input-group-addon">
                                                <input type="number" class="form-control" id="progress_mekanik4" 
                                                min="0" max="100" value="0" style="width: 60px;" />
                                                <span>%</span>
                                            </span>
                                        </div>
                                        <div class="progress" style="margin-top: 5px;">
                                            <div class="progress-bar progress-bar-success" id="bar_mekanik4" 
                                            role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Actions -->
                <div class="form-section">
                    <h5 class="green">
                        <i class="ace-icon fa fa-cogs"></i>
                        Aksi Progress
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Status Pengerjaan:</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="status_pengerjaan">
                                        <option value="belum_mulai">Belum Mulai</option>
                                        <option value="bekerja">Sedang Bekerja</option>
                                        <option value="istirahat">Istirahat</option>
                                        <option value="selesai">Selesai</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Catatan Progress:</label>
                                <div class="col-sm-8">
                                    <textarea class="form-control" id="catatan_progress" rows="2" 
                                    placeholder="Catatan progress pengerjaan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 text-center">
                            <button type="button" class="btn btn-success" onclick="updateProgress()">
                                <i class="fa fa-save"></i> Update Progress
                            </button>
                            <button type="button" class="btn btn-info" onclick="refreshProgress()">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Progress History -->
                <div class="form-section">
                    <h5 class="purple">
                        <i class="ace-icon fa fa-history"></i>
                        Riwayat Progress
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Mekanik</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody id="progressHistory">
                                <!-- Progress history will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Global variables for progress management
var progressData = {};

// Function to update progress
function updateProgress() {
    var status = document.getElementById('status_pengerjaan').value;
    var catatan = document.getElementById('catatan_progress').value;
    
    // Collect progress data from all mechanics
    var progressUpdates = [];
    
    // Kepala Mekanik
    if(document.getElementById('nama_kepala1').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_kepala1').value,
            progress: document.getElementById('progress_kepala1').value,
            status: status,
            catatan: catatan
        });
    }
    
    if(document.getElementById('nama_kepala2').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_kepala2').value,
            progress: document.getElementById('progress_kepala2').value,
            status: status,
            catatan: catatan
        });
    }
    
    // Admin
    if(document.getElementById('nama_admin1').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_admin1').value,
            progress: document.getElementById('progress_admin1').value,
            status: status,
            catatan: catatan
        });
    }
    
    if(document.getElementById('nama_admin2').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_admin2').value,
            progress: document.getElementById('progress_admin2').value,
            status: status,
            catatan: catatan
        });
    }
    
    // Mekanik
    if(document.getElementById('nama_mekanik1').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_mekanik1').value,
            progress: document.getElementById('progress_mekanik1').value,
            status: status,
            catatan: catatan
        });
    }
    
    if(document.getElementById('nama_mekanik2').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_mekanik2').value,
            progress: document.getElementById('progress_mekanik2').value,
            status: status,
            catatan: catatan
        });
    }
    
    if(document.getElementById('nama_mekanik3').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_mekanik3').value,
            progress: document.getElementById('progress_mekanik3').value,
            status: status,
            catatan: catatan
        });
    }
    
    if(document.getElementById('nama_mekanik4').value) {
        progressUpdates.push({
            id_mekanik: document.getElementById('nama_mekanik4').value,
            progress: document.getElementById('progress_mekanik4').value,
            status: status,
            catatan: catatan
        });
    }
    
    // Send progress updates via AJAX
    $.ajax({
        url: '_ajax/ajax-update-progress-mekanik.php',
        type: 'POST',
        data: {
            no_service: '<?php echo $no_service; ?>',
            progress_updates: JSON.stringify(progressUpdates)
        },
        success: function(response) {
            var data = JSON.parse(response);
            if(data.success) {
                alert('Progress berhasil diupdate');
                refreshProgress();
            } else {
                alert('Gagal update progress: ' + data.message);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat update progress');
        }
    });
}

// Function to refresh progress
function refreshProgress() {
    // Load current progress data
    $.ajax({
        url: '_ajax/ajax-get-progress.php',
        type: 'POST',
        data: {
            no_service: '<?php echo $no_service; ?>'
        },
        success: function(response) {
            var data = JSON.parse(response);
            if(data.success) {
                updateProgressDisplay(data.progress);
                updateProgressHistory(data.history);
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat memuat progress');
        }
    });
}

// Function to update progress display
function updateProgressDisplay(progressData) {
    // Update progress bars and inputs
    Object.keys(progressData).forEach(function(mekanikId) {
        var progress = progressData[mekanikId];
        var progressInput = document.getElementById('progress_' + mekanikId);
        var progressBar = document.getElementById('bar_' + mekanikId);
        
        if(progressInput && progressBar) {
            progressInput.value = progress.persen_kerja;
            progressBar.style.width = progress.persen_kerja + '%';
            progressBar.textContent = progress.persen_kerja + '%';
        }
    });
    
    // Calculate total progress
    var totalProgress = 0;
    var activeCount = 0;
    Object.keys(progressData).forEach(function(mekanikId) {
        if(progressData[mekanikId].status_kerja === 'bekerja') {
            totalProgress += parseInt(progressData[mekanikId].persen_kerja);
            activeCount++;
        }
    });
    
    var avgProgress = activeCount > 0 ? Math.round(totalProgress / activeCount) : 0;
    document.getElementById('totalProgress').textContent = avgProgress + '%';
    document.getElementById('mekanikAktif').textContent = activeCount;
}

// Function to update progress history
function updateProgressHistory(history) {
    var tbody = document.getElementById('progressHistory');
    tbody.innerHTML = '';
    
    history.forEach(function(entry) {
        var row = tbody.insertRow();
        row.innerHTML = `
            <td>${entry.waktu}</td>
            <td>${entry.nama_mekanik}</td>
            <td>${entry.progress}%</td>
            <td><span class="label label-${getStatusColor(entry.status)}">${entry.status}</span></td>
            <td>${entry.catatan || '-'}</td>
        `;
    });
}

// Function to get status color
function getStatusColor(status) {
    switch(status) {
        case 'belum_mulai': return 'default';
        case 'bekerja': return 'success';
        case 'istirahat': return 'warning';
        case 'selesai': return 'info';
        default: return 'default';
    }
}

// Function to populate mechanic names from work order
function populateMechanicNames() {
    // This will be called when the page loads to populate mechanic names
    // from the work order tab selections
    var kepala1 = document.getElementById('kepala_mekanik1');
    var kepala2 = document.getElementById('kepala_mekanik2');
    var admin1 = document.getElementById('admin1');
    var admin2 = document.getElementById('admin2');
    var mekanik1 = document.getElementById('mekanik1');
    var mekanik2 = document.getElementById('mekanik2');
    var mekanik3 = document.getElementById('mekanik3');
    var mekanik4 = document.getElementById('mekanik4');
    
    if(kepala1 && kepala1.value) {
        document.getElementById('nama_kepala1').value = kepala1.options[kepala1.selectedIndex].text;
    }
    if(kepala2 && kepala2.value) {
        document.getElementById('nama_kepala2').value = kepala2.options[kepala2.selectedIndex].text;
    }
    if(admin1 && admin1.value) {
        document.getElementById('nama_admin1').value = admin1.options[admin1.selectedIndex].text;
    }
    if(admin2 && admin2.value) {
        document.getElementById('nama_admin2').value = admin2.options[admin2.selectedIndex].text;
    }
    if(mekanik1 && mekanik1.value) {
        document.getElementById('nama_mekanik1').value = mekanik1.options[mekanik1.selectedIndex].text;
    }
    if(mekanik2 && mekanik2.value) {
        document.getElementById('nama_mekanik2').value = mekanik2.options[mekanik2.selectedIndex].text;
    }
    if(mekanik3 && mekanik3.value) {
        document.getElementById('nama_mekanik3').value = mekanik3.options[mekanik3.selectedIndex].text;
    }
    if(mekanik4 && mekanik4.value) {
        document.getElementById('nama_mekanik4').value = mekanik4.options[mekanik4.selectedIndex].text;
    }
}

// Event listeners for progress inputs
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for progress inputs
    var progressInputs = ['kepala1', 'kepala2', 'admin1', 'admin2', 'mekanik1', 'mekanik2', 'mekanik3', 'mekanik4'];
    
    progressInputs.forEach(function(mekanik) {
        var input = document.getElementById('progress_' + mekanik);
        var bar = document.getElementById('bar_' + mekanik);
        
        if(input && bar) {
            input.addEventListener('input', function() {
                var value = this.value;
                bar.style.width = value + '%';
                bar.textContent = value + '%';
            });
        }
    });
    
    // Initial load
    refreshProgress();
});
</script>
