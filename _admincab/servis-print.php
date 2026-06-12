<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];
        $kd_cabang=$_SESSION['_cabang'];
		include "../config/koneksi.php";

		// Check database connection
		if(!$koneksi) {
			die("Database connection failed: " . mysqli_connect_error());
		}

		$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_user, password, user_akses, foto_user
                                        FROM tbuser WHERE id='$id_user'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$_nama=$tm_cari['nama_user'];

    // ------- Data Cabang ----------
		$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_cabang, tipe_cabang, alamat_cabang
                                        FROM tbcabang
                                        WHERE kode_cabang='$kd_cabang'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang=$tm_cari['nama_cabang'] ?? 'BENGKEL MOTOR';
        $alamat_cabang=$tm_cari['alamat_cabang'] ?? '';
        $kota_cabang=''; // Column doesn't exist
        $telp_cabang=''; // Column doesn't exist
    // --------------------

        $no_service = $_GET['snoserv'] ?? '';

        if(empty($no_service)) {
            echo"<script>window.alert('Nomor servis tidak ditemukan!');
            window.close();</script>";
            exit;
        }

        // Get service data
        $cari_service = mysqli_query($koneksi,"SELECT
                                        s.*,
                                        DATE_FORMAT(s.tanggal,'%d/%m/%Y') AS tanggal_format,
                                        p.namapelanggan,
                                        p.alamat as alamat_pelanggan,
                                        p.telephone,
                                        pg.grup as grup_pelanggan,
                                        k.pemilik,
                                        k.jenis,
                                        k.tipe,
                                        k.warna,
                                        k.no_rangka,
                                        k.no_mesin,
                                        pm.merek
                                        FROM tblservice s
                                        LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
                                        LEFT JOIN tblpelanggangrup pg ON p.kgrup = pg.kgrup
                                        LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
                                        LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                                        WHERE s.no_service='$no_service'");

        if(mysqli_num_rows($cari_service) == 0) {
            echo"<script>window.alert('Data servis tidak ditemukan!');
            window.close();</script>";
            exit;
        }

        $data_service = mysqli_fetch_array($cari_service);
        $tanggal = $data_service['tanggal_format'];
        $jam = $data_service['jam'];
        $no_polisi = $data_service['no_polisi'];
        $namapelanggan = $data_service['namapelanggan'] ?: $data_service['pemilik'];
        $alamat_pelanggan = $data_service['alamat_pelanggan'];
        $telephone = $data_service['telephone'];
        $grup_pelanggan = $data_service['grup_pelanggan'] ?: '-';
        $jenis = $data_service['jenis'];
        $tipe = $data_service['tipe'];
        $merek = $data_service['merek'];
        $warna = $data_service['warna'];
        $no_rangka = $data_service['no_rangka'];
        $no_mesin = $data_service['no_mesin'];
        $km_skr = $data_service['km_skr'];
        $km_berikut = $data_service['km_berikut'];
        $status_servis = $data_service['status_servis'];
        $keterangan = $data_service['keterangan'];

        // Get totals from service header
        $subtotal_jasa = $data_service['subtotal_jasa'] ?? 0;
        $subtotal_item = $data_service['subtotal_item'] ?? 0;
        $subtotal = $data_service['subtotal'] ?? 0;
        $diskon_persen = $data_service['diskon_persen'] ?? 0;
        $diskon_nom = $data_service['diskon_nom'] ?? 0;
        $total_diskon = $data_service['total_diskon'] ?? 0;
        $ppn_persen = $data_service['ppn_persen'] ?? 0;
        $ppn_nom = $data_service['ppn_nom'] ?? 0;
        $total_pajak = $data_service['total_pajak'] ?? 0;
        $total_akhir = $data_service['total_akhir'] ?? 0;
        $total_grand = $data_service['total_grand'] ?? 0;
        $bayar = $data_service['bayar'] ?? 0;
        $kembali = $data_service['kembali'] ?? 0;
        $jenis_bayar = $data_service['jenis_bayar'] ?? 0;
        $metode_pembayaran = $data_service['metode_pembayaran'] ?? 'Tunai';
        $bukti_pembayaran = $data_service['bukti_pembayaran'] ?? '';

        // Get mekanik data
        $mekanik1 = $data_service['mekanik1'];
        $mekanik2 = $data_service['mekanik2'];
        $mekanik3 = $data_service['mekanik3'];
        $mekanik4 = $data_service['mekanik4'];
        $kepala_mekanik1 = $data_service['kepala_mekanik1'];
        $kepala_mekanik2 = $data_service['kepala_mekanik2'];

        // Get service detail - jasa
        $cari_jasa = mysqli_query($koneksi,"SELECT
                                    sj.*,
                                    COALESCE(wh.nama_wo, ij.namaitem, 'Item Tidak Diketahui') as nama_item
                                    FROM tblservis_jasa sj
                                    LEFT JOIN tbworkorderheader wh ON sj.no_item = wh.kode_wo
                                    LEFT JOIN tblitem_jasa ij ON sj.no_item = ij.noitem
                                    WHERE sj.no_service='$no_service'
                                    ORDER BY sj.nobaris");

        if(!$cari_jasa) {
            die("Error query jasa: " . mysqli_error($koneksi));
        }

        // Get service detail - barang
        $cari_barang = mysqli_query($koneksi,"SELECT
                                    sb.*,
                                    COALESCE(vi.namaitem, 'Item Tidak Diketahui') as nama_item,
                                    COALESCE(vi.satuan, 'PCS') as satuan
                                    FROM tblservis_barang sb
                                    LEFT JOIN view_cari_item vi ON sb.no_item = vi.noitem
                                    WHERE sb.no_service='$no_service'
                                    ORDER BY sb.nobaris");

        if(!$cari_barang) {
            die("Error query barang: " . mysqli_error($koneksi));
        }
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Invoice Servis - <?php echo $no_service; ?></title>

		<meta name="description" content="Invoice Servis" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<style>
			@media print {
				.no-print { display: none !important; }
				body { background: white; }
                @page { margin: 0.5cm; }
			}

			body {
				background: white;
				font-family: Arial, sans-serif;
				font-size: 11px;
                padding: 10px;
			}

			.invoice-header {
				text-align: center;
				border-bottom: 3px solid #333;
				margin-bottom: 15px;
				padding-bottom: 10px;
			}

            .invoice-header h3 {
                margin: 5px 0;
                font-size: 18px;
                font-weight: bold;
            }

            .invoice-header p {
                margin: 2px 0;
                font-size: 11px;
            }

			.info-section {
				margin-bottom: 10px;
			}

			.info-table {
				width: 100%;
				margin-bottom: 10px;
			}

			.info-table td {
				padding: 3px 5px;
				vertical-align: top;
			}

            .info-table .label-col {
                width: 120px;
                font-weight: bold;
            }

            .info-table .separator-col {
                width: 10px;
            }

			.detail-table {
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 10px;
			}

			.detail-table th,
			.detail-table td {
				padding: 5px;
				border: 1px solid #333;
			}

			.detail-table th {
				background-color: #f0f0f0;
				font-weight: bold;
                text-align: center;
			}

            .detail-table td.text-right {
                text-align: right;
            }

            .detail-table td.text-center {
                text-align: center;
            }

			.total-section {
				width: 100%;
				margin-top: 10px;
			}

			.total-section table {
				width: 100%;
			}

			.total-section td {
				padding: 3px 5px;
			}

            .total-row {
                font-weight: bold;
                font-size: 13px;
            }

            .border-top {
                border-top: 2px solid #333;
            }

            .footer-note {
                margin-top: 20px;
                font-size: 10px;
                font-style: italic;
            }

            .signature-section {
                margin-top: 30px;
                width: 100%;
            }

            .signature-box {
                text-align: center;
                display: inline-block;
                width: 30%;
            }

            .signature-line {
                border-top: 1px solid #333;
                margin-top: 50px;
                padding-top: 5px;
            }
		</style>
	</head>

	<body>
		<div class="container-fluid">
			<!-- Print Button -->
			<div class="no-print" style="margin-bottom: 10px;">
				<button onclick="printService()" class="btn btn-primary btn-sm">
					<i class="fa fa-print"></i> Print
				</button>
				<a href="servis-reguler-struk.php?snoserv=<?php echo $no_service; ?>&mode=download" target="_blank" class="btn btn-danger btn-sm">
					<i class="fa fa-file-pdf-o"></i> Download PDF
				</a>
				<button onclick="kirimWhatsApp()" class="btn btn-success btn-sm">
					<i class="fa fa-whatsapp"></i> Kirim ke WhatsApp
				</button>
				<button onclick="window.close()" class="btn btn-default btn-sm">
					<i class="fa fa-times"></i> Close
				</button>
			</div>
			<script>
			function kirimWhatsApp() {
				// Confirm dulu
				if(!confirm('Kirim invoice ini ke WhatsApp pelanggan?')) {
					return;
				}
				
				// Show loading
				var btn = event.target;
				var originalText = btn.innerHTML;
				btn.disabled = true;
				btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
				
				// Kirim via AJAX
				fetch('servis-send-invoice-wa.php?no_service=<?php echo $no_service; ?>')
					.then(response => response.json())
					.then(data => {
						btn.disabled = false;
						btn.innerHTML = originalText;
						
						if(data.success) {
							alert('Invoice berhasil dikirim ke WhatsApp!\n\nNomor: ' + data.phone + '\nStatus: ' + data.status);
						} else {
							alert('Gagal mengirim invoice!\n\nError: ' + data.message);
						}
					})
					.catch(error => {
						btn.disabled = false;
						btn.innerHTML = originalText;
						alert('Terjadi kesalahan!\n\n' + error);
					});
			}
			</script>

			<!-- Invoice Preview (Iframe) -->
            <div style="border: 1px solid #ccc; padding: 0;">
                <iframe id="invoiceFrame" src="servis-reguler-struk.php?snoserv=<?php echo $no_service; ?>&mode=view" style="width: 100%; height: 800px; border: none;"></iframe>
            </div>

			<script>
            // Update the print function to print the iframe content
            function printService() {
                var iframe = document.getElementById('invoiceFrame');
                var iframeWindow = iframe.contentWindow || iframe.contentDocument;
                
                if (iframeWindow.document.queryCommandSupported('print')) {
                    iframeWindow.document.execCommand('print', false, null);
                } else {
                    iframeWindow.focus();
                    iframeWindow.print();
                }
            }
            
            // Override the default print button onclick if needed, 
            // but since we updated the print button in the header (previous step logic check needed?)
            // Let's make sure the button up top calls this printService() if it's not already doing so.
            // Wait, the button up top currently does `onclick="window.print()"`. We need to change that.
            </script>
		</div>

		<!-- Auto print script -->
		<script>
			// Auto print when page loads (optional)
			// window.onload = function() { window.print(); }
		</script>
	</body>
</html>

<?php
	}
?>
