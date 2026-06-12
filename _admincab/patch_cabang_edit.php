<?php
/**
 * Script untuk patch cabang_edit.php secara otomatis
 * Usage: php patch_cabang_edit.php
 */

$file = 'cabang_edit.php';
$content = file_get_contents($file);

if ($content === false) {
    die("Error: Cannot read $file\n");
}

// Backup original
file_put_contents($file . '.before_patch', $content);

// PATCH 1: Update SELECT query
$old_query = '$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_cabang,
                                        tipe_cabang
									FROM tbcabang
									WHERE kode_cabang=\'$kdcabang\'");';

$new_query = '$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_cabang,
                                        tipe_cabang,
                                        alamat_cabang,
                                        google_maps_cabang,
                                        lat_cabang,
                                        long_cabang
									FROM tbcabang
									WHERE kode_cabang=\'$kdcabang\'");';

$content = str_replace($old_query, $new_query, $content);

// PATCH 2: Update variable assignments
$old_vars = '$tm_cari=mysqli_fetch_array($cari_kd);
	$nama=$tm_cari[\'nama_cabang\'];
	$tipe_cabang=$tm_cari[\'tipe_cabang\'];';

$new_vars = '$tm_cari=mysqli_fetch_array($cari_kd);
	$nama=$tm_cari[\'nama_cabang\'];
	$tipe_cabang=$tm_cari[\'tipe_cabang\'];
	$alamat_cabang=$tm_cari[\'alamat_cabang\'] ?? \'\';
	$google_maps_cabang=$tm_cari[\'google_maps_cabang\'] ?? \'\';
	$lat_cabang=$tm_cari[\'lat_cabang\'] ?? \'\';
	$long_cabang=$tm_cari[\'long_cabang\'] ?? \'\';';

$content = str_replace($old_vars, $new_vars, $content);

// PATCH 3: Add form inputs before </div> clearfix
$old_form_end = '									</select>
								</div>
							</div>

							<div class="clearfix form-actions">';

$new_form_inputs = '									</select>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Alamat Cabang </label>
								<div class="col-sm-9">
									<textarea id="txtalamat" name="txtalamat" class="col-xs-10 col-sm-6" rows="3" placeholder="Alamat lengkap cabang..."><?php echo isset($alamat_cabang) ? $alamat_cabang : \'\'; ?></textarea>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Google Maps Link </label>
								<div class="col-sm-9">
									<input type="url" id="txtgooglemaps" name="txtgooglemaps" class="col-xs-10 col-sm-6"
									value="<?php echo isset($google_maps_cabang) ? $google_maps_cabang : \'\'; ?>" placeholder="https://www.google.com/maps/@-6.123456,106.123456..." autocomplete="off" />
									<br><small class="text-muted">Paste link Google Maps cabang, koordinat akan otomatis diextract</small>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Latitude </label>
								<div class="col-sm-9">
									<input type="text" id="txtlat" name="txtlat" class="col-xs-10 col-sm-6"
									value="<?php echo isset($lat_cabang) ? $lat_cabang : \'\'; ?>" placeholder="-6.123456" autocomplete="off" readonly />
									<br><small class="text-muted">Auto-filled dari Google Maps link</small>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label no-padding-right" for="form-field-1"> Longitude </label>
								<div class="col-sm-9">
									<input type="text" id="txtlong" name="txtlong" class="col-xs-10 col-sm-6"
									value="<?php echo isset($long_cabang) ? $long_cabang : \'\'; ?>" placeholder="106.123456" autocomplete="off" readonly />
									<br><small class="text-muted">Auto-filled dari Google Maps link</small>
								</div>
							</div>

							<div class="clearfix form-actions">';

$content = str_replace($old_form_end, $new_form_inputs, $content);

// PATCH 4: Add JavaScript before });
$old_js_end = '		});
	</script>';

$new_js_end = '		// Auto-extract GPS coordinates from Google Maps URL
		$(\'#txtgooglemaps\').on(\'blur\', function() {
			var mapsUrl = $(this).val();
			if (mapsUrl) {
				// Try pattern 1: @lat,lng
				var match = mapsUrl.match(/@(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
				if (!match) {
					// Try pattern 2: q=lat,lng
					match = mapsUrl.match(/q=(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
				}

				if (match) {
					$(\'#txtlat\').val(match[1]);
					$(\'#txtlong\').val(match[2]);
				}
			}
		});
	});
	</script>';

$content = str_replace($old_js_end, $new_js_end, $content);

// Save patched file
if (file_put_contents($file, $content) !== false) {
    echo "✅ SUCCESS: $file has been patched!\n";
    echo "Backup saved as: {$file}.before_patch\n";
} else {
    echo "❌ ERROR: Cannot write to $file\n";
}
?>
