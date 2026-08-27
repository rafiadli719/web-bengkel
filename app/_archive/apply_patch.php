<?php
/**
 * Apply patch to cabang_edit.php
 */

$file = 'cabang_edit.php';
echo "Reading $file...\n";
$content = file_get_contents($file);

if ($content === false) {
    die("❌ ERROR: Cannot read $file\n");
}

// Create backup
file_put_contents($file . '.prepatch', $content);
echo "✅ Backup created: {$file}.prepatch\n";

// PATCH 1: Add fields to SELECT query using regex
$pattern1 = '/(SELECT\s+nama_cabang,\s+tipe_cabang)/s';
$replacement1 = 'SELECT
                                        nama_cabang,
                                        tipe_cabang,
                                        alamat_cabang,
                                        google_maps_cabang,
                                        lat_cabang,
                                        long_cabang';
$content = preg_replace($pattern1, $replacement1, $content, 1);
echo "✅ PATCH 1: SELECT query updated\n";

// PATCH 2: Add variable assignments after $tipe_cabang
$pattern2 = '/(\$tipe_cabang=\$tm_cari\[\'tipe_cabang\'\];)/';
$replacement2 = '$1
	$alamat_cabang=$tm_cari[\'alamat_cabang\'] ?? \'\';
	$google_maps_cabang=$tm_cari[\'google_maps_cabang\'] ?? \'\';
	$lat_cabang=$tm_cari[\'lat_cabang\'] ?? \'\';
	$long_cabang=$tm_cari[\'long_cabang\'] ?? \'\';';
$content = preg_replace($pattern2, $replacement2, $content, 1);
echo "✅ PATCH 2: Variable assignments added\n";

// PATCH 3: Add form fields before "clearfix form-actions"
$pattern3 = '/(<\/div>\s*<\/div>\s*<div class="clearfix form-actions">)/s';
$replacement3 = '</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right"> Alamat Cabang </label>
								<div class="col-sm-9">
									<textarea id="txtalamat" name="txtalamat" class="col-xs-10 col-sm-6" rows="3" placeholder="Alamat lengkap cabang..."><?php echo isset($alamat_cabang) ? htmlspecialchars($alamat_cabang) : \'\'; ?></textarea>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right"> Google Maps Link </label>
								<div class="col-sm-9">
									<input type="url" id="txtgooglemaps" name="txtgooglemaps" class="col-xs-10 col-sm-6" value="<?php echo isset($google_maps_cabang) ? htmlspecialchars($google_maps_cabang) : \'\'; ?>" placeholder="https://www.google.com/maps/@-6.123456,106.123456..." autocomplete="off" />
									<br><small class="text-muted"><i class="fa fa-info-circle"></i> Paste link Google Maps cabang, koordinat akan otomatis ter-extract</small>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right"> Latitude </label>
								<div class="col-sm-9">
									<input type="text" id="txtlat" name="txtlat" class="col-xs-10 col-sm-6" value="<?php echo isset($lat_cabang) ? htmlspecialchars($lat_cabang) : \'\'; ?>" placeholder="-6.123456" autocomplete="off" readonly style="background-color: #f5f5f5;" />
									<br><small class="text-muted"><i class="fa fa-check"></i> Auto-filled dari Google Maps link</small>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right"> Longitude </label>
								<div class="col-sm-9">
									<input type="text" id="txtlong" name="txtlong" class="col-xs-10 col-sm-6" value="<?php echo isset($long_cabang) ? htmlspecialchars($long_cabang) : \'\'; ?>" placeholder="106.123456" autocomplete="off" readonly style="background-color: #f5f5f5;" />
									<br><small class="text-muted"><i class="fa fa-check"></i> Auto-filled dari Google Maps link</small>
								</div>
							</div>

							$1';
$content = preg_replace($pattern3, $replacement3, $content, 1);
echo "✅ PATCH 3: Form inputs added\n";

// PATCH 4: Add JavaScript before closing });
$pattern4 = '/(\s+\}\);\s+<\/script>)/s';
$replacement4 = '

		// ========================================
		// Auto-extract GPS coordinates from Google Maps URL
		// ========================================
		$(\'#txtgooglemaps\').on(\'blur\', function() {
			var mapsUrl = $(this).val().trim();
			if (mapsUrl) {
				// Try pattern 1: @lat,lng (https://www.google.com/maps/@-6.123,106.123,17z)
				var match = mapsUrl.match(/@(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);

				if (!match) {
					// Try pattern 2: q=lat,lng (https://www.google.com/maps?q=-6.123,106.123)
					match = mapsUrl.match(/q=(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
				}

				if (!match) {
					// Try pattern 3: ll=lat,lng
					match = mapsUrl.match(/ll=(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
				}

				if (match) {
					$(\'#txtlat\').val(match[1]);
					$(\'#txtlong\').val(match[2]);

					// Show success message
					alert(\'✅ Koordinat berhasil di-extract:\\n\\nLatitude: \' + match[1] + \'\\nLongitude: \' + match[2]);
				} else {
					// Show error if no match
					if (mapsUrl.includes(\'google.com/maps\')) {
						alert(\'⚠️ Format Google Maps URL tidak dikenali.\\n\\nPastikan URL mengandung koordinat seperti:\\n@-6.123456,106.123456\');
					}
				}
			}
		});
$1';
$content = preg_replace($pattern4, $replacement4, $content, 1);
echo "✅ PATCH 4: JavaScript added\n";

// Save patched file
if (file_put_contents($file, $content) !== false) {
    echo "\n";
    echo "========================================\n";
    echo "✅ SUCCESS: $file has been patched!\n";
    echo "========================================\n";
    echo "Backup: {$file}.prepatch\n";
    echo "\nNext steps:\n";
    echo "1. Test file cabang_edit.php\n";
    echo "2. Import database: UPDATE_DATABASE_JARAK_OTOMATIS.sql\n";
    echo "3. Edit data cabang untuk input koordinat\n";
} else {
    echo "❌ ERROR: Cannot write to $file\n";
}
?>
