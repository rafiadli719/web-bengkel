<?php
/**
 * Tab Pickup Details (Redesign)
 * Menampilkan detail penjemputan motor berdasarkan data yang diinput di Jadwal Penjemputan
 * Style: Redesign V2 (rd-card)
 */

// Pastikan variabel tersedia dengan fallback
$cust_name = $namapelanggan ?? $nama_pelanggan ?? 'Tamu';
$cust_addr = $alamatpelanggan ?? $alamat_pelanggan ?? '-';
$cust_phone = $teleponpelanggan ?? $telepon ?? '-'; 
$pickup_date = !empty($tanggal_jemput) ? date('d-m-Y', strtotime($tanggal_jemput)) : '-';
$pickup_time = !empty($jam_jemput) ? date('H:i', strtotime($jam_jemput)) . ' WIB' : '-';
$pickup_note = $keterangan_jemput ?? '-';
$pickup_status = $status_jemput ?? 'unknown';

// Mapping status jemput
$status_label = '';
$status_class = 'neutral';
switch($pickup_status) {
    case '1': $status_label = 'Terjadwal / Menunggu'; $status_class = 'warning'; break;
    case '2': $status_label = 'Sedang Dijemput'; $status_class = 'info'; break;
    case '3': $status_label = 'Selesai Jemput'; $status_class = 'success'; break;
    case '0': $status_label = 'Batal'; $status_class = 'danger'; break;
    default: $status_label = 'Pending'; $status_class = 'neutral';
}
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    <!-- Left Column: Markup Details & Customer -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Pickup Info Card -->
        <div class="rd-card primary">
            <div class="rd-card-header">
                <h5><i class="fa fa-truck"></i> Informasi Penjemputan</h5>
                <span class="rd-badge solid-<?= $status_class ?>">
                    <?= strtoupper($status_label) ?>
                </span>
            </div>
            <div class="rd-card-body" style="padding: 16px;">
                <div class="rd-info-grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="rd-info-item">
                        <span class="label">Tanggal Jemput</span>
                        <span class="value lg primary" style="font-weight: 600;">
                            <i class="fa fa-calendar-alt" style="color: #666; font-size: 14px; margin-right: 4px;"></i> 
                            <?= $pickup_date ?>
                        </span>
                    </div>
                    <div class="rd-info-item">
                        <span class="label">Jam</span>
                        <span class="value lg primary" style="font-weight: 600;">
                            <i class="fa fa-clock" style="color: #666; font-size: 14px; margin-right: 4px;"></i> 
                            <?= $pickup_time ?>
                        </span>
                    </div>
                    <div class="rd-info-item">
                        <span class="label">Kendaraan</span>
                        <span class="value" style="font-size: 16px; font-weight: 700; color: var(--rd-primary);">
                            <?= $no_polisi ?>
                            <?php if(!empty($tipe_motor)): ?>
                                <span style="font-size: 13px; color: #666; font-weight: 400; display: block;">(<?= $tipe_motor ?>)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="rd-info-item">
                        <span class="label">Catatan</span>
                        <span class="value"><?= nl2br(htmlspecialchars($pickup_note)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Info Card -->
        <div class="rd-card" style="margin-bottom: 0;">
            <div class="rd-card-header">
                <h5><i class="fa fa-user"></i> Informasi Pelanggan</h5>
            </div>
            <div class="rd-card-body" style="padding: 16px;">
                <div class="rd-info-grid" style="grid-template-columns: 1fr;">
                    <div class="rd-info-item">
                        <span class="label">Nama</span>
                        <span class="value" style="font-size: 16px; font-weight: 600;">
                            <?= htmlspecialchars($cust_name) ?>
                        </span>
                    </div>
                    <div class="rd-info-item">
                        <span class="label">Telepon</span>
                        <span class="value">
                            <?= htmlspecialchars($cust_phone) ?>
                            <?php if($cust_phone != '-'): ?>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $cust_phone)) ?>" target="_blank" class="rd-btn xs success" style="margin-left: 8px;">
                                <i class="fab fa-whatsapp"></i> WA
                            </a>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="rd-info-item">
                        <span class="label">Alamat</span>
                        <span class="value">
                            <i class="fa fa-map-marker-alt" style="color: #999; margin-right: 4px;"></i>
                            <?= htmlspecialchars($cust_addr) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Location & Photo -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Photo Card -->
        <div class="rd-card" style="height: auto;">
            <div class="rd-card-header">
                <h5><i class="fa fa-camera"></i> Foto Lokasi / Patokan</h5>
            </div>
            <div class="rd-card-body" style="padding: 16px; text-align: center;">
                <?php if(!empty($foto_patokan)): ?>
                    <!-- Fix path relative to admin folder -->
                    <?php 
                        // Normalisasi path separator
                        $raw_path = str_replace('\\', '/', $foto_patokan);
                        
                        // Cek apakah raw_path sudah mengandung ../ atau folder file_upload
                        // Skenario 1: Path di DB = file_upload/gambar.jpg (Standar)
                        // Skenario 2: Path di DB = ../file_upload/gambar.jpg (Legacy)
                        
                        $display_path = '';
                        
                        // Path fisik check (Relative to script execution location: _admincab/)
                        // Kita asumsikan folder file_upload ada di level atas (../file_upload)
                        
                        // Clean 'delete' keywords or extra slashes
                        $clean_path = ltrim($raw_path, './'); 
                        $clean_path = str_replace('../', '', $clean_path); // remove up-dir for cleaning
                        
                        // Construct potential paths
                        $path_option_1 = "../" . $clean_path; // ../file_upload/foto.jpg
                        
                        if (file_exists($path_option_1)) {
                            $display_path = $path_option_1;
                        } else {
                            // Fallback: Just try to use what's in DB directly if it looks link-like
                            $display_path = $raw_path;
                             // Jika tidak ada ../ di awal dan ada file_upload, tambahkan ../
                            if (strpos($display_path, 'file_upload') !== false && strpos($display_path, '../') === false) {
                                $display_path = "../" . $display_path;
                            }
                        }
                    ?>
                    <div style="border: 1px solid #eee; padding: 4px; border-radius: 8px; margin-bottom: 12px;">
                        <img src="<?= $display_path ?>" style="max-width: 100%; height: auto; max-height: 250px; border-radius: 4px;" alt="Foto Patokan" onerror="this.onerror=null;this.src='../assets/images/no-image.png'; this.parentNode.innerHTML+='<br><small class=\'text-danger\'>Gagal memuat: '+this.src+'</small>';">
                    </div>
                    <a href="<?= $display_path ?>" target="_blank" class="rd-btn sm outline-primary">
                        <i class="fa fa-search-plus"></i> Lihat Full Size
                    </a>
                    
                    <!-- Debug info (Hidden in production ideally, but useful now) -->
                    <div style="font-size:10px; color:#ccc; margin-top:5px; display:none;">
                        DB Value: <?= $foto_patokan ?><br>
                        Resolved: <?= $display_path ?>
                    </div>
                    
                <?php else: ?>
                    <div style="color: #999; padding: 32px; background: #f9f9f9; border-radius: 8px; border: 1px dashed #ddd;">
                        <i class="fa fa-image" style="font-size: 32px; margin-bottom: 8px; display: block;"></i>
                        Tidak ada foto patokan yang diupload.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Maps Card -->
        <div class="rd-card">
            <div class="rd-card-header">
                <h5><i class="fa fa-map-marked-alt"></i> Lokasi & Rute Penjemputan</h5>
            </div>
            <div class="rd-card-body" style="padding: 16px;">
                <?php 
                $gmaps_link = '';
                if(!empty($klat) && !empty($klong)) {
                    $gmaps_link = "https://www.google.com/maps?q={$klat},{$klong}";
                }
                ?>
                
                <?php if(!empty($klat) && !empty($klong)): ?>
                    <!-- Map Container -->
                    <div id="pickupDetailsMap" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 16px; background: #eee;"></div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div class="rd-alert info" style="margin:0; padding: 10px; text-align: center;">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Jarak Tempuh</div>
                            <strong id="displayJarak" style="font-size: 16px;">- KM</strong>
                        </div>
                        <div class="rd-alert info" style="margin:0; padding: 10px; text-align: center;">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Estimasi Waktu</div>
                            <strong id="displayWaktu" style="font-size: 16px;">- Menit</strong>
                        </div>
                    </div>

                    <a href="<?= $gmaps_link ?>" target="_blank" class="rd-btn block success lg">
                        <i class="fa fa-map-marker-alt"></i> Buka Google Maps
                    </a>
                    
                <?php else: ?>
                    <div class="rd-alert warning" style="margin: 0;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <div>Lokasi GPS belum tercatat di data pelanggan.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="text-align: right; margin-top: 8px;">
            <a href="servis-print.php?snoserv=<?= $no_service ?>" target="_blank" class="rd-btn outline-danger">
                <i class="fa fa-print"></i> Cetak SPK Jemput
            </a>
        </div>

    </div>
</div>

<!-- LEAFLET & OSRM LOGIC (Only load if coordinates exist) -->
<?php if(!empty($klat) && !empty($klong)): ?>
    <!-- Leaflet CSS & JS (Load via CDN if local not available, or use local fallback logic) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="assets/js/osrm-route-calculator.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Init map after delay to ensure container is rendered and has size
        initPickupMap();
        
        // Listen for tab switching to Detail Jemput
        const tabBtn = document.querySelector('button[data-target="pickup-details"]');
        if(tabBtn) {
            tabBtn.addEventListener('click', function() {
                // Give time for tab to become visible then Force Resize
                setTimeout(function() {
                    if (typeof OSRMCalculator !== 'undefined' && OSRMCalculator.resizeMap) {
                        OSRMCalculator.resizeMap();
                    } else {
                        // Fallback logic if needed, or re-init (though re-init might be blocked by _leaflet_id check)
                        initPickupMap();
                    }
                }, 300);
            });
        }
    });

    async function initPickupMap() {
        var mapContainer = document.getElementById('pickupDetailsMap');
        if(!mapContainer) return;

        // Customer Coords
        var custLat = <?= $klat ?>;
        var custLng = <?= $klong ?>;
        
        // Branch Coords (Fallback to Jakarta if empty/null)
        var branchLat = <?= !empty($lat_cabang) ? floatval($lat_cabang) : -6.2088 ?>;
        var branchLng = <?= !empty($long_cabang) ? floatval($long_cabang) : 106.8456 ?>;
        var branchName = "<?= !empty($nama_cabang) ? addslashes($nama_cabang) : 'Bengkel' ?>";

        try {
            // Check if map is already initialized
            if (mapContainer._leaflet_id) {
                 // Map already exists, just resize it
                 if (typeof OSRMCalculator !== 'undefined' && OSRMCalculator.resizeMap) {
                    OSRMCalculator.resizeMap();
                 }
                 return;
            }

            // Initialize Map using OSRMCalculator helper if available, or manual
            if (typeof OSRMCalculator !== 'undefined') {
                const routeData = await OSRMCalculator.displayRouteOnMap(
                    'pickupDetailsMap',
                    branchLat, branchLng,
                    custLat, custLng,
                    branchName,
                    'Lokasi Pelanggan'
                );
                
                // Update display
                document.getElementById('displayJarak').innerText = OSRMCalculator.formatDistance(routeData.distance);
                document.getElementById('displayWaktu').innerText = OSRMCalculator.formatDuration(routeData.duration);
            } else {
                // Fallback basic map without route if OSRM script fails
                var map = L.map('pickupDetailsMap').setView([custLat, custLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: 'OpenStreetMap'
                }).addTo(map);
                L.marker([custLat, custLng]).addTo(map).bindPopup("Lokasi Pelanggan").openPopup();
                
                // Force resize
                setTimeout(() => { map.invalidateSize(); }, 300);
            }
        } catch (e) {
            console.error("Map Preview Error:", e);
            mapContainer.innerHTML = '<div class="alert alert-danger">Gagal memuat peta: ' + e.message + '</div>';
        }
    }
    </script>
<?php endif; ?>
