# LRS — Logical Record Structure FIT MOTOR (Web Bengkel)

> Digenerate otomatis dari skema database live `fitmotor_dbbengkel` (2026-09-25). Melengkapi [ERD.md](./ERD.md).

## Notasi

```
NAMA_TABEL ( <u>PK</u> , atribut, atribut, #FK → tabel_induk.kolom )
```
- **PK** ditulis tebal-bergaris (`__kolom__`); PK komposit dipisah koma.
- `#kolom` = foreign key ke tabel induk (cabang `→ tabel.kolom`).
- Tanda `†` pada PK = *natural key logis* (tabel legacy hasil migrasi Access **tidak punya PRIMARY KEY di database**; kunci diambil dari kolom identitas transaksi). Ini catatan risiko integritas — lihat bagian Temuan.
- `(D)` = relasi terdeklarasi sebagai FOREIGN KEY di MySQL; `(L)` = relasi logis (dijaga oleh kode PHP).
- Tipe data lengkap per kolom ada di [ERD.md](./ERD.md).

**Ringkasan:** 294 tabel aktif · 66 relasi terdeklarasi · 301 relasi logis.

---

## D01 — Organisasi, User & Hak Akses

**bulan_transaksi** ( bulan, nama, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**laravel_migrations** ( **__id__**, migration, batch )  
<sub>±{active[n]['rows']} baris</sub>

**master_jabatan** ( **__id__**, #kode_jabatan → tbjabatan.kode_jabatan (L), nama_jabatan, #kode_posisi → tb_master_posisi.kode_posisi (L), urutan, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**master_perusahaan** ( **__id__**, #kode_perusahaan → tbperusahaan.kode_perusahaan (L), nama_perusahaan, nama_singkat, alamat, telepon, email, website, logo, npwp, direktur, keterangan, is_aktif, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_master_level** ( **__id__**, #kode_posisi → tb_master_posisi.kode_posisi (D), kode_level, nama_level, urutan, deskripsi, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_master_posisi** ( **__id__**, kode_posisi, nama_posisi, departemen, deskripsi, user_akses_level, permissions, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_permissions** ( **__permission_id__**, permission_code, permission_name, permission_description, module, is_active )  
<sub>±{active[n]['rows']} baris</sub>

**tb_user_activity_log** ( **__log_id__**, #user_id → tbuser.id (L), action, module, description, ip_address, user_agent, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_user_mekanik_mapping** ( **__id__**, #user_id → tbuser.id (L), mekanik_code, is_primary, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_user_roles** ( **__role_id__**, role_code, role_name, role_description, department, permissions, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbcabang** ( **__kode_cabang__**, cabang_ref_kode, #perusahaan_id → master_perusahaan.id (D), nama_cabang, entry_year, entry_month, alamat_cabang, google_maps_cabang, lat_cabang, long_cabang, #tipe_cabang → tbcabang_tipe.id (L), status )  
<sub>±{active[n]['rows']} baris</sub>

**tbcabang_tipe** ( cabang_tipe, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbcari** ( cari, tipe, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbdivisi** ( **__kode_divisi__**†, nama_divisi )  
<sub>±{active[n]['rows']} baris</sub>

**tbjabatan** ( **__kode_jabatan__**†, nama_jabatan, #kode_divisi → tbdivisi.kode_divisi (L), tjabatan )  
<sub>±{active[n]['rows']} baris</sub>

**tbl_adm** ( kode, nama, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbl_service_seq_counter** ( **__seq_key__**, seq )  
<sub>±{active[n]['rows']} baris</sub>

**tblevel** ( **__id__**, level_name )  
<sub>±{active[n]['rows']} baris</sub>

**tblservice_no_counter** ( **__tahun__**, seq )  
<sub>±{active[n]['rows']} baris</sub>

**tbperusahaan** ( **__kode_perusahaan__**†, nama_perusahaan, tlp_perusahaan, email_perusahaan, bisnis, npwp, leader, alamat, logo_perusahaan, file_org, _default, leader_id, reg, bpjs_kes, social, cur, kota, prop, district, districtsub, rt, rw, negara, kodepos, kontak, kontaktlp )  
<sub>±{active[n]['rows']} baris</sub>

**tbsetting** ( **__id__**, nama_perusahaan, alamat, notlp, fax, file_logo )  
<sub>±{active[n]['rows']} baris</sub>

**tburut** ( **__id__**, urut, tipe )  
<sub>±{active[n]['rows']} baris</sub>

**tbuser** ( **__id__**, kode_karyawan, nik, nama_user, nama_lengkap, nama_panggilan, password, foto_user, foto, user_akses, status_row, email, telp, alamat, tanggal_masuk, tanggal_keluar, spesialisasi, sertifikat, #kode_posisi → tb_master_posisi.kode_posisi (L), #kode_level → tb_master_level.kode_level (L), #kode_cabang → tbcabang.kode_cabang (L), role_name, department, is_active, created_at, updated_at, last_login, #id_karyawan → tbuser_karyawan.id (D) )  
<sub>±{active[n]['rows']} baris</sub>

**tbuser_karyawan** ( **__id__**, kode_karyawan, nik, nama_lengkap, nama_panggilan, #kode_posisi → tb_master_posisi.kode_posisi (D), #kode_level → tb_master_level.kode_level (D), #kode_jabatan → tbjabatan.kode_jabatan (L), #kode_cabang → tbcabang.kode_cabang (L), email, telp, alamat, tanggal_masuk, tanggal_keluar, spesialisasi, is_active, sertifikat, foto, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbwilayah** ( **__id__**, provinsi, kota_kabupaten, kecamatan, kelurahan_desa )  
<sub>±{active[n]['rows']} baris</sub>

**tbwork_loc** ( kode_perusahaan, **__kode_lokasi__**†, nama_lokasi, lat_pos, lon_pos )  
<sub>±{active[n]['rows']} baris</sub>

**tbwork_schedule** ( **__id_work__**, kode_perusahaan, schedule_type, deskripsi, jml_day )  
<sub>±{active[n]['rows']} baris</sub>

**tbwork_schedule_dtl** ( **__id_seq__**, id_work, jam_masuk, jam_keluar, sequence )  
<sub>±{active[n]['rows']} baris</sub>

**tokens** ( **__id__**, access_token, refresh_token, scope, created_at )  
<sub>±{active[n]['rows']} baris</sub>

---

## D02 — Master Barang, Jasa & Paket

**tbcara_bayar** ( **__id__**, cara_bayar )  
<sub>±{active[n]['rows']} baris</sub>

**tbharga_jual_rules** ( **__id__**, jenis, margin, marginplus, bulat, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbhargajual** ( jenis, margin, marginplus, bulat, **__id__**, exclude_diskon_member )  
<sub>±{active[n]['rows']} baris</sub>

**tbhjual_jasa** ( jasa, nilai, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbitem_jenis_motor** ( **__id__**, #noitem → tblitem.noitem (L), #kd_kategori_motor → tbkategori_motor.id (L), kd_jenis_motor )  
<sub>±{active[n]['rows']} baris</sub>

**tbitem_validation_log** ( **__id__**, #noitem → tblitem.noitem (L), status_lama, status_baru, keterangan, validated_by, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbjenis_bayar** ( jenis_bayar )  
<sub>±{active[n]['rows']} baris</sub>

**tbkategori_rak** ( **__kode__**, nama_kategori, deskripsi, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem** ( **__noitem__**†, kodebarcode, namaitem, #jenis → tblitemjenis.jenis (L), #satuan → tblitemsatuan.satuan (L), hargapokok, hargajual, hargajual2, hargajual3, hjqtyd2, hjqtyd3, hjqtys1, hjqtys2, totalpokok, quantity, stokmin, statusitem, #supplier → tblsupplier.nosupplier (L), #supplier2 → tblsupplier.nosupplier (L), #supplier3 → tblsupplier.nosupplier (L), statusproduk, gambar, note, rakbarang, jasawaktu, jasasatuanwaktu, jeniskomisi, komisiprosen, komisinominal, inv_idawal, inv_jmlawal, inv_hrgawal, inv_tglawal, stok_maks, #kd_pabrik → tbpabrik_barang.id (L), kd_etalase, jenis_jasa, tipe_item, merek, kode_part_resmi, nama_part_resmi, penggunaan_motor, merek_tipe, #kategori_rak → tbkategori_rak.kode (L), status_validasi, created_by, validated_by, created_at, updated_at, exclude_diskon_member )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_harga_cabang** ( **__noitem__**, **__kd_cabang__**, hargapokok, hargajual, hargajual2, hargajual3, qty_access, tanggal_sync )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_jasa** ( #noitem → tblitem.noitem (L), namaitem, waktu, harga )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_minmax** ( **__id__**, #no_item → tblitem.noitem (L), #kd_cabang → tbcabang.kode_cabang (L), w1, w2, w3, w4, w5, w6, w7, w8, w9, w10, w11, w12, max_1w, max_2w, min_stok, max_stok, rop, total_transaksi_84hari, avg_interval_hari, kategori, stok_saat_ini, lead_time_hari, #supplier1 → tblsupplier.nosupplier (L), #supplier2 → tblsupplier.nosupplier (L), min_max_manual, last_sale_date, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_spart** ( #noitem → tblitem.noitem (L), kode_tipe, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_stok** ( #noitem → tblitem.noitem (L), #kode_cabang → tbcabang.kode_cabang (L), stokmin, stok_maks, stok_awal, rakbarang )  
<sub>±{active[n]['rows']} baris</sub>

**tblitemjenis** ( jenis, namajenis, _default, **__id__**, accurate_id, status, margin_khusus, ikut_margin_jenis, include_service, keterangan )  
<sub>±{active[n]['rows']} baris</sub>

**tblitemsatuan** ( satuan, namasatuan, _default, **__id__**, accurate_id, keterangan, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbljenis** ( **__kodejenis__**, namajenis, keterangan, statusjenis )  
<sub>±{active[n]['rows']} baris</sub>

**tblnamabarang** ( **__id__**, nama_barang, sinonim_1, sinonim_2, sinonim_3, perlu_ukuran, keterangan, satuan, status )  
<sub>±{active[n]['rows']} baris</sub>

**tblsatuan** ( **__kodesatuan__**, satuan, keterangan, statussatuan )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_barang_custom** ( **__id__**, #kode_barang → tblitem.noitem (L), nama_barang, harga_jual, satuan, kategori, deskripsi, status_aktif, created_by, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_barang_fastmoves** ( **__id__**, #kode_kategori → tbmaster_kategori_fastmoves.kode_kategori (D), #kode_barang → tblitem.noitem (L), is_featured, urutan, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_barang_nonitem** ( **__id__**, kode_barang_temp, nama_barang, kategori, satuan, harga_estimasi, #supplier → tblsupplier.nosupplier (L), keterangan, status_approval, perlu_approval, kode_barang_final, user_request, user_approval, tanggal_request, tanggal_approval, alasan_reject, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_jenis_item** ( **__id__**, kode, nama_jenis, margin, status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_kategori_fastmoves** ( **__id__**, kode_kategori, nama_kategori, icon, urutan, is_active, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_kategori_item** ( **__id__**, kategori_item, keterangan, margin_sesuai_jenis, margin_kategori, status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_kategori_member** ( **__id__**, status_member, benefit, min_nominal, max_nominal, urutan, created_at, masa_garansi_hari, masa_garansi_maks_hari )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_nama_barang** ( **__id__**, nama_barang, sinonim_1, sinonim_2, sinonim_3, ukuran, nama_utama, nama_alternatif, spesifikasi_diperlukan, keterangan, status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbpabrik_barang** ( **__id__**, pabrik_barang, accurate_id, deskripsi, accurate_synced, accurate_notes, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbrakbarang** ( **__id__**, rak_barang )  
<sub>±{active[n]['rows']} baris</sub>

**tbstatus_harga** ( **__id__**, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbstatus_produk** ( **__id__**, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbtipe_item** ( **__id__**, tipe )  
<sub>±{active[n]['rows']} baris</sub>

**tbtipe_pajak** ( **__id__**, tipe_pajak )  
<sub>±{active[n]['rows']} baris</sub>

**tbtipe_potongan** ( kdtipe, tipe_pot )  
<sub>±{active[n]['rows']} baris</sub>

**tbworkorder_jenis_motor** ( **__id__**, #kode_wo → tbworkorderheader.kode_wo (L), #kd_kategori_motor → tbkategori_motor.id (L), kd_jenis_motor )  
<sub>±{active[n]['rows']} baris</sub>

**tbworkorderdetail** ( #kode_wo → tbworkorderheader.kode_wo (L), #kode_barang → tblitem.noitem (L), jumlah, satuan, diskon, status_diskon, **__id__**, tipe, harga, total, is_gratis )  
<sub>±{active[n]['rows']} baris</sub>

**tbworkorderheader** ( **__kode_wo__**†, nama_wo, keterangan, status, waktu, harga )  
<sub>±{active[n]['rows']} baris</sub>

---

## D03 — Pelanggan, Kendaraan & CRM

**customer_alias** ( **__nopelanggan_lama__**, #nopelanggan_baru → tblpelanggan.nopelanggan (L), created_at )  
<sub>±{active[n]['rows']} baris</sub>

**customer_merge_log** ( **__id__**, id_issue, #nopelanggan_source → tblpelanggan.nopelanggan (L), #nopelanggan_target → tblpelanggan.nopelanggan (L), alasan, snapshot_before_json, diajukan_oleh, disetujui_oleh, status, created_at, executed_at )  
<sub>±{active[n]['rows']} baris</sub>

**kendaraan_plat_history** ( **__id__**, #id_kendaraan → tblkendaraan.id_kendaraan (L), #nopolisi → tblkendaraan.nopolisi (L), tanggal_mulai, tanggal_akhir, is_current, alasan, diinput_oleh, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**kepemilikan_kendaraan** ( **__id__**, #id_kendaraan → tblkendaraan.id_kendaraan (L), #nopelanggan → tblpelanggan.nopelanggan (L), tanggal_mulai, tanggal_akhir, is_current, sumber, diinput_oleh, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**master_kategori_member** ( **__id_kategori__**, nama_kategori, tipe_kategori, min_value, max_value, diskon_persen, diskon_jasa, diskon_barang, benefit_text, icon, warna, urutan, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**master_kedatangan_pelanggan** ( **__id_kedatangan__**, #no_pelanggan → tblpelanggan.nopelanggan (D), #no_service → tblservice.no_service (L), kedatangan_ke, tanggal_datang, tanggal_sebelumnya, jarak_hari, total_transaksi, jumlah_item, rata2_nilai_per_item, estimasi_datang_berikut, status_garansi, keterangan, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**permintaan_pindah_kepemilikan_kendaraan** ( **__id__**, #id_kendaraan → tblkendaraan.id_kendaraan (L), #nopelanggan_lama → tblpelanggan.nopelanggan (L), #nopelanggan_baru → tblpelanggan.nopelanggan (L), nopolisi_snapshot, tanggal_efektif, alasan, catatan_internal, status, blocker_snapshot_json, dibuat_oleh, disetujui_oleh, dieksekusi_oleh, approved_at, executed_at, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**setting_highlight_member** ( **__id__**, kategori_member, background_color, text_color, border_color, border_width, is_bold, opacity, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**setting_kategori_member** ( **__id__**, tipe_kategori, is_enabled, keterangan, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**statistik_kendaraan** ( **__id_kendaraan__**, #nopelanggan_current → tblpelanggan.nopelanggan (L), total_transaksi, total_kunjungan, total_nominal, total_jasa, total_sparepart, km_terakhir, tanggal_servis_terakhir, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**statistik_pelanggan** ( **__id_statistik__**, #no_pelanggan → tblpelanggan.nopelanggan (D), total_transaksi, total_nominal, jumlah_kunjungan, kedatangan_terakhir, rata_jarak_kunjungan, rata_rata_transaksi, status_member, diskon_persen, kategori_member_kunjungan, tanggal_pertama_transaksi, tanggal_terakhir_transaksi, lama_tidak_datang, lama_menjadi_pelanggan, estimasi_datang_berikutnya, total_motor, created_at, updated_at, jumlah_cancel, cancel_rate, tanggal_cancel_terakhir, cancel_customer_request, cancel_no_stock, cancel_no_mekanik, cancel_no_show, cancel_lainnya )  
<sub>±{active[n]['rows']} baris</sub>

**tb_booking** ( tanggal, jam, nama, no_telepon, #no_polisi → tblkendaraan.nopolisi (L), status_pelanggan, **__id__**, no_booking, status_booking )  
<sub>±{active[n]['rows']} baris</sub>

**tbbagi_hasil** ( **__stts__**, **__kategori__**, persen_bagihasil, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbinsentif_jual_servis** ( **__id__**, siklus, tgl, trx, garap, stts, sts, kode, mekanik, tipe, #no_item → tblitem.noitem (L), quantity, jualnet, tothpp, laba, kategori, tanggal_data, #kd_cabang → tbcabang.kode_cabang (L), created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbjenis_motor** ( **__kd__**, jenis, keterangan, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbkategori_motor** ( **__id__**, kategori, keterangan, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbkomisijl_sales1** ( **__id__**, ket )  
<sub>±{active[n]['rows']} baris</sub>

**tbkomisijl_sales2** ( **__id__**, ket )  
<sub>±{active[n]['rows']} baris</sub>

**tblkendaraan** ( **__nopolisi__**, id_kendaraan, pemilik, alamat, #kode_merek → tbpabrik_motor.id (L), tipe, #kode_tipe → tbtipe_motor.kode_tipe (L), jenis, #kode_jenis → tbjenis_motor.kd (L), tahun_buat, tahun_rakit, silinder, warna, #kode_warna → tbwarna.id (L), no_rangka, no_mesin, note )  
<sub>±{active[n]['rows']} baris</sub>

**tblpelanggan** ( **__nopelanggan__**, namapelanggan, alamat, kota, propinsi, kodepost, negara, telephone, no_wa, domisili_cabang, fax, kontakperson, note, potongan, tipepot, lavelharga, #kgrup → tblpelanggangrup.kgrup (L), patokan, foto_tampak_rumah, link_gmaps, notlp, klat, klong, panggilan, saldoawal, pertanggal, tgllahir, id_panggilan, bl_pajak, th_pajak, #merek_id → tbpabrik_motor.id (D), #tipe_id → tbtipe_motor.kode_tipe (D), #jenis_id → tbjenis_motor.kd (D), #warna_id → tbwarna.id (D), gender, valid_tgl_lahir, informasi_sumber )  
<sub>±{active[n]['rows']} baris</sub>

**tblpelanggangrup** ( **__kgrup__**†, grup, diskon )  
<sub>±{active[n]['rows']} baris</sub>

**tblsales** ( **__nosales__**†, namasales, alamat, kota, telephone, komisijual, _default, komisi_nominal, op_pil_hitung, op_pil_sistem_komisi, status_baris )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_tipe_detail** ( **__id__**, kode_tipe, kode_brand, #id_tipe_header → tbmaster_tipe_header.id (L), nama_detail, cc, id_jenis_motor, fitur_pembeda, ciri_fisik_pembeda, tahun_awal, tahun_akhir, no_seri_mesin, id_kategori_motor, status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_tipe_header** ( **__id__**, nama_model, #id_brand → tbpabrik_motor.id (D), status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbpabrik_motor** ( **__id__**, merek, kode_brand, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbpanggilan** ( **__id__**, panggilan )  
<sub>±{active[n]['rows']} baris</sub>

**tbpersen_insentif** ( **__posisi__**, persen_barang, persen_jasa, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbtipe_motor** ( **__kode_tipe__**, tipe, kode_pabrik, kode_kategori, tahun )  
<sub>±{active[n]['rows']} baris</sub>

**tbtipemotor** ( **__tipe__**, pabrik, kategori, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbwarna** ( warna, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

---

## D04 — Servis, Antrian, Diagnosa & Garansi

**log_perubahan_tarif** ( **__id__**, tanggal, user_id, aksi, tarif_id, data_lama, data_baru, keterangan )  
<sub>±{active[n]['rows']} baris</sub>

**master_tarif_jemput_range** ( **__id__**, jenis_motor, jarak_min, jarak_max, tarif_dasar, tarif_per_km, keterangan, aktif, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**servis_komisi** ( **__id__**, #no_service → tblservice.no_service (L), #kd_cabang → tbcabang.kode_cabang (L), peran, nominal_jasa, nominal_barang, persen_terpakai, dihitung_saat, id_issue_ref, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_antrian_servis** ( **__id__**, no_antrian, #no_service → tblservice.no_service (L), tanggal, jam_ambil, jam_mulai, jam_selesai, status_antrian, prioritas, estimasi_waktu, catatan, created_at, updated_at, alasan_batal, user_batal, waktu_batal )  
<sub>±{active[n]['rows']} baris</sub>

**tb_approval_diskon** ( **__id__**, #no_service → tblservice.no_service (L), jenis, nominal_diskon, persen_diskon, alasan, status, id_user_cs, id_user_supervisor, tanggal_request, tanggal_approval, #kd_cabang → tbcabang.kode_cabang (L) )  
<sub>±{active[n]['rows']} baris</sub>

**tb_dp_servis** ( **__id__**, #no_service → tblservice.no_service (L), no_dp, tanggal_dp, jumlah_dp, status, tanggal_offset, keterangan, id_user, #kd_cabang → tbcabang.kode_cabang (L), created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_kepala_mekanik_schedule** ( **__id__**, #kode_karyawan → tbuser_karyawan.kode_karyawan (D), #kode_cabang → tbcabang.kode_cabang (L), tanggal_kerja, shift, status_kehadiran, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_log_antrian** ( **__id__**, #no_antrian → tb_antrian_servis.no_antrian (L), #no_service → tblservice.no_service (L), aktivitas, keterangan, user_id, user_nama, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_log_cancel_servis** ( **__id__**, #no_service → tblservice.no_service (L), tanggal_cancel, alasan_cancel, kategori_alasan, user_cancel, biaya_cancel, status_barang, status_pembayaran, nominal_dp, nominal_refund, catatan, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_master_garansi** ( **__id__**, kategori_member, jenis_servis, masa_garansi_hari, masa_garansi_maks, keterangan, aktif, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_mekanik_progress** ( **__id__**, #no_service → tblservice.no_service (L), #kode_karyawan → tbuser_karyawan.kode_karyawan (D), status_progress, waktu_mulai, waktu_selesai, catatan, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_progress_mekanik** ( **__id__**, #no_service → tblservice.no_service (L), #no_antrian → tb_antrian_servis.no_antrian (L), mekanik_id, nama_mekanik, jenis_mekanik, persen_kerja, status_kerja, jam_mulai, jam_selesai, catatan_kerja, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbkeluhan_proses** ( **__id__**, #kode_keluhan → tbmaster_keluhan.kode_keluhan (D), #kode_item → tblitem.noitem (L), tipe_proses, nama_proses, deskripsi, estimasi_waktu, harga_estimasi, wajib, urutan, status_aktif, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbl_kepala_mekanik_harian** ( **__id__**, #kode_cabang → tbcabang.kode_cabang (L), tanggal_kerja, kepala_mekanik_1, kepala_mekanik_2, shift_kerja, keterangan, created_by, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblkeluhan** ( **__id_keluhan__**, #kode_keluhan → tbmaster_keluhan.kode_keluhan (L), nama_keluhan, deskripsi, estimasi_waktu, status )  
<sub>±{active[n]['rows']} baris</sub>

**tblretur_servis_detail** ( #no_retur → tblretur_servis_header.noretur (L), #no_service → tblservice.no_service (L), tipe_item, #no_item → tblitem.noitem (L), quantity, harga, total, alasan_retur, user, #kd_cabang → tbcabang.kode_cabang (L), **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tblretur_servis_header** ( **__noretur__**†, #no_service → tblservice.no_service (L), tanggal, note, cara_bayar_refund, total_qty_retur, total_retur, user, **__kd_cabang__**†, status_retur, status_refund, tanggal_refund )  
<sub>±{active[n]['rows']} baris</sub>

**tblservice** ( **__no_service__**, tanggal, jam, #no_pelanggan → tblpelanggan.nopelanggan (L), #no_polisi → tblkendaraan.nopolisi (L), status, **__kd_cabang__**, #id_user → tbuser.id (L), total, diskon_persen, diskon_nom, ppn_persen, ppn_nom, total_grand, bayar, kembali, jenis_bayar, total_waktu, status_jemput, kondisi_motor, jarak_jemput, tarif_jemput, keterangan, foto_motor, km_skr, #mekanik1 → tbuser.kode_karyawan (L), mekanik2, mekanik3, mekanik4, biayaM1, biayaM2, biayaM3, biayaM4, km_berikut, subtotal_jasa, subtotal_item, subtotal, total_diskon, total_pajak, total_akhir, created_at, updated_at, status_servis, boleh_dp, keterangan_jemput, foto_patokan, #kepala_mekanik1 → tbuser.kode_karyawan (L), kepala_mekanik2, admin1, admin2, persen_kepala_mekanik1, persen_kepala_mekanik2, persen_admin1, persen_admin2, persen_mekanik1, persen_mekanik2, persen_mekanik3, persen_mekanik4, metode_pembayaran, bukti_pembayaran, tanggal_cancel, alasan_cancel, user_cancel, biaya_cancel, cancel_note, total_penawaran, total_penawaran_disetujui, jumlah_temuan, jumlah_penawaran, bayar_tunai, bayar_transfer, bayar_qris, ref_transfer, bukti_transfer, is_garansi, #ref_no_service_original → tblservice.no_service (L), tanggal_garansi_expire, mekanik_original, komisi_garansi_mode, total_retur, ref_no_penjualan_asal )  
<sub>±{active[n]['rows']} baris</sub>

**tblservice_advisor** ( **__no_service__**†, **__kd_cabang__**†, advisor )  
<sub>±{active[n]['rows']} baris</sub>

**tblservice_jemputantar** ( **__no_service__**, **__kd_cabang__**, tanggal, #no_pelanggan → tblpelanggan.nopelanggan (L), #no_polisi → tblkendaraan.nopolisi (L), jam, keterangan, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblservis_barang** ( #no_service → tblservice.no_service (L), #kd_cabang → tbcabang.kode_cabang (L), nobaris, #no_item → tblitem.noitem (L), quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, #id_promo → master_diskon_periode.id_promo (L), created_at, updated_at, **__id__**, keterangan, asal_barang )  
<sub>±{active[n]['rows']} baris</sub>

**tblservis_jasa** ( #no_service → tblservice.no_service (L), #kd_cabang → tbcabang.kode_cabang (L), nobaris, #no_item → tblitem.noitem (L), waktu, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, #id_promo → master_diskon_periode.id_promo (L), created_at, updated_at, **__id__**, qty_retur )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_keluhan** ( **__id__**, kode_keluhan, nama_keluhan, deskripsi, kategori, status_aktif, status_approval, requested_by, requested_from, approved_by, approved_at, rejection_reason, created_at, updated_at, estimasi_waktu, tingkat_prioritas, #workorder_default → tbworkorderheader.kode_wo (L) )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_keluhan_temuan** ( **__id__**, #kode_keluhan → tbmaster_keluhan.kode_keluhan (L), #kode_temuan → tbmaster_temuan.kode_temuan (L), prioritas, is_mandatory, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_keluhan_workorder** ( **__id__**, #kode_keluhan → tbmaster_keluhan.kode_keluhan (L), #kode_workorder → tbworkorderheader.kode_wo (L), prioritas, status_aktif, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_temuan** ( **__id__**, kode_temuan, nama_temuan, deskripsi, kategori, tingkat_urgensi, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmaster_temuan_barang_mapping** ( **__id__**, #kode_temuan → tbmaster_temuan.kode_temuan (L), #noitem → tblitem.noitem (L), is_primary, prioritas, qty_default, keterangan, status_aktif, created_by, created_at, updated_by, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbmekanik_level** ( **__id__**, mekanik_level )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_keluhan** ( #no_service → tblservice.no_service (L), keluhan, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_keluhan_status** ( **__id__**, #no_service → tblservice.no_service (L), keluhan, kategori, #kode_keluhan → tbmaster_keluhan.kode_keluhan (L), status_pengerjaan, auto_workorder, workorder_applied, keterangan_tidak_selesai, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_keluhan_tracking** ( **__id__**, #no_service → tblservice.no_service (L), #keluhan_id → tbservis_keluhan_status.id (D), #proses_id → tbkeluhan_proses.id (D), status_proses, mekanik_id, waktu_mulai, waktu_selesai, catatan, biaya_actual, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_penawaran_jasa** ( **__id__**, #no_service → tblservice.no_service (L), #temuan_id → tbservis_temuan.id (L), kode_jasa, nama_jasa, harga, waktu_estimasi, is_from_suggestion, mapping_id, status_penawaran, alasan_tolak, keterangan_tolak, user_penawaran, user_respon, tanggal_respon, catatan_penawaran, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_penawaran_log** ( **__id__**, #penawaran_id → tbservis_penawaran_part.id (D), #no_service → tblservice.no_service (L), action, alasan, user_action, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_penawaran_part** ( **__id__**, #no_service → tblservice.no_service (L), #temuan_id → tbservis_temuan.id (D), is_from_suggestion, suggestion_priority, #kode_barang → tblitem.noitem (L), nama_barang, quantity, harga_satuan, total_harga, stok_tersedia, estimasi_ketersediaan, status_penawaran, alasan_tolak, keterangan_tolak, catatan_penawaran, discount_persen, discount_nominal, harga_final, tanggal_penawaran, tanggal_respon, user_penawaran, user_respon, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_penawaran_rejection_summary** ( **__id__**, #kode_barang → tblitem.noitem (L), nama_barang, total_penawaran, total_diterima, total_ditolak, alasan_tolak_terbanyak, acceptance_rate, last_offered_date, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_pending_items** ( **__id__**, #no_service → tblservice.no_service (L), #wo_id → tbservis_workorder.id (L), #kode_item → tblitem.noitem (L), nama_item, tipe, quantity, harga_satuan, total, waktu, status_approval, alasan_tolak, keterangan_tolak, approved_by, approved_at, created_at, updated_at, is_gratis )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_pengerjaan** ( #no_service → tblservice.no_service (L), item_pengerjaan, kd_mekanik, status_pengerjaan, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_temuan** ( **__id__**, #no_service → tblservice.no_service (L), #keluhan_id → tbservis_keluhan_status.id (D), mekanik_id, mekanik_name, #kode_temuan → tbmaster_temuan.kode_temuan (L), temuan_custom, deskripsi_temuan, jenis_perbaikan, status_temuan, keterangan_tidak_selesai, tingkat_urgensi, estimasi_biaya, biaya_actual, foto_temuan, created_by, created_at, updated_by, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_temuan_activity_log** ( **__id__**, #no_service → tblservice.no_service (L), #temuan_id → tbservis_temuan.id (L), penawaran_id, activity_type, description, old_value, new_value, user_id, user_name, ip_address, user_agent, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbservis_workorder** ( **__id__**, #no_service → tblservice.no_service (L), #kode_wo → tbworkorderheader.kode_wo (L), status_pengerjaan, keterangan_tidak_selesai, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

---

## D05 — Promo & Diskon Periode

**master_diskon_periode** ( **__id_promo__**, nama_promo, tipe_promo, nilai_promo, tanggal_mulai, tanggal_selesai, status_aktif, stackable, boleh_gabung_diskon_member, mode_syarat, keterangan, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**master_diskon_periode_cabang** ( **__id__**, #id_promo → master_diskon_periode.id_promo (D), #kd_cabang → tbcabang.kode_cabang (L) )  
<sub>±{active[n]['rows']} baris</sub>

**master_diskon_periode_syarat** ( **__id__**, #id_promo → master_diskon_periode.id_promo (D), jenis_syarat, operator, nilai, rolling_hari )  
<sub>±{active[n]['rows']} baris</sub>

**master_diskon_periode_target** ( **__id__**, #id_promo → master_diskon_periode.id_promo (D), target_type, target_id, target_nama )  
<sub>±{active[n]['rows']} baris</sub>

**promo_usage_log** ( **__id__**, #id_promo → master_diskon_periode.id_promo (D), #no_service → tblservice.no_service (L), target_type, target_id, nilai_potongan, urutan_stacking, dipakai_oleh, tanggal_pakai )  
<sub>±{active[n]['rows']} baris</sub>

---

## D06 — Pembelian & Pengadaan (PR–RFQ–PO–DO–Invoice–Hutang)

**alarm_harga_beli** ( **__id__**, #no_item → tblitem.noitem (L), #no_transaksi_pembelian → tblpembelian_header.notransaksi (L), harga_beli_lama, harga_beli_baru, persen_selisih, arah, threshold_saat_itu, harga_jual_saat_ini, status_klasifikasi, status_review, direview_oleh, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_master_approval_pembelian** ( **__id__**, level_approval, nama_level, batas_bawah, batas_atas, #kode_posisi → tb_master_posisi.kode_posisi (L), urutan, aktif, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tb_master_threshold_harga** ( **__id__**, arah, persen_threshold, aktif, updated_by, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbbeli_peritem_history** ( **__id__**, id_tabel, tanggal, #no_item → tblitem.noitem (L), hrgnet, #no_supplier → tblsupplier.nosupplier (L), fax, #kd_cabang → tbcabang.kode_cabang (L), created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbldelivery_order_detail** ( **__id__**, #no_do → tbldelivery_order_header.no_do (D), nobaris, #no_item → tblitem.noitem (L), qty_po, qty_kirim, qty_terima, qty_reject, keterangan )  
<sub>±{active[n]['rows']} baris</sub>

**tbldelivery_order_header** ( **__no_do__**, #no_po → tblorder_header.no_order (D), #no_supplier → tblsupplier.nosupplier (L), tanggal_do, tanggal_kirim, tanggal_estimasi_tiba, tanggal_tiba, no_surat_jalan, no_kendaraan, nama_pengirim, telp_pengirim, alamat_kirim, total_qty, status_do, keterangan, #kd_cabang → tbcabang.kode_cabang (L), created_by, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbldo_tracking** ( **__id__**, #no_do → tbldelivery_order_header.no_do (D), status, keterangan, lokasi, updated_by, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblhutang_detail** ( **__no_transaksi__**†, **__nobaris__**†, #no_pembelian → tblpembelian_header.notransaksi (L), keterangan, jumlah_hutang, jumlah_bayar, status )  
<sub>±{active[n]['rows']} baris</sub>

**tblhutang_header** ( **__no_transaksi__**†, tanggal, #no_supplier → tblsupplier.nosupplier (L), note, total_bayar, user, id_tabel, **__kd_cabang__**† )  
<sub>±{active[n]['rows']} baris</sub>

**tblorder_detail** ( #no_order → tblorder_header.no_order (L), nobaris, #no_item → tblitem.noitem (L), quantity, qty_terima, harga_pokok, total, user, status_trx, #kd_cabang → tbcabang.kode_cabang (L), **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tblorder_header** ( **__no_order__**, status, tanggal, tglkirim, #no_supplier → tblsupplier.nosupplier (L), note, total_qty, total_terima, total_order, user, Id_tabel, #kd_cabang → tbcabang.kode_cabang (L), status_pesanan, tipe_trx, no_penjualan, #no_pr → tblpurchase_request_header.no_pr (L), #no_rfq → tblrfq_header.no_rfq (L), status_approval, approved_by, approved_date, rejected_by, rejected_date, reject_reason, po_type, payment_term, delivery_address, contact_person, contact_phone )  
<sub>±{active[n]['rows']} baris</sub>

**tblpembelian_detail** ( #no_transaksi → tblpembelian_header.notransaksi (L), nobaris, #no_item → tblitem.noitem (L), qty_order, quantity, qty_retur, harga_pokok, potongan, harga_sp, total, sts_order, id_inv, **__id__**, user, #kd_cabang → tbcabang.kode_cabang (L), status_trx )  
<sub>±{active[n]['rows']} baris</sub>

**tblpembelian_header** ( **__notransaksi__**†, status, carabayar, tanggal, #no_order → tblorder_header.no_order (L), tanggal_order, #no_supplier → tblsupplier.nosupplier (L), note, total_qty_order, total_qty, total_beli, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user, Id_tabel, **__kd_cabang__**†, status_lunas, lama_hari, #no_do → tbldelivery_order_header.no_do (L), #no_pr → tblpurchase_request_header.no_pr (L), status_qc, qc_by, qc_date, qc_notes )  
<sub>±{active[n]['rows']} baris</sub>

**tblpo_approval_log** ( **__id__**, #no_po → tblorder_header.no_order (D), level_approval, approver, action, notes, action_date )  
<sub>±{active[n]['rows']} baris</sub>

**tblpr_approval_log** ( **__id__**, #no_pr → tblpurchase_request_header.no_pr (D), level_approval, approver, action, notes, action_date )  
<sub>±{active[n]['rows']} baris</sub>

**tblpr_tracking** ( **__id__**, #no_pr → tblpurchase_request_header.no_pr (L), status_old, status_new, keterangan, updated_by, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblpurchase_request_detail** ( **__id__**, #no_pr → tblpurchase_request_header.no_pr (D), nobaris, #no_item → tblitem.noitem (L), nama_item, quantity, qty_approved, qty_po, satuan, harga_estimasi, total_estimasi, keterangan, status_item )  
<sub>±{active[n]['rows']} baris</sub>

**tblpurchase_request_header** ( **__no_pr__**, tanggal_pr, tanggal_butuh, requester, departemen, alasan, total_qty, total_estimasi, status_pr, approved_by, approved_date, rejected_by, rejected_date, reject_reason, #kd_cabang → tbcabang.kode_cabang (L), created_by, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblretur_pembelian_detail** ( #no_retur → tblretur_pembelian_header.noretur (L), no_pembelian, #no_item → tblitem.noitem (L), quantity, harga_pokok, potongan, total, user, #kd_cabang → tbcabang.kode_cabang (L), alasan_retur, jenis_penggantian, **__id__**, status_penggantian )  
<sub>±{active[n]['rows']} baris</sub>

**tblretur_pembelian_header** ( **__noretur__**†, #nopembelian → tblpembelian_header.notransaksi (L), tanggal, note, total_qty_retur, total_retur, diskon, total_diskon, pajak, total_pajak, total_akhir, user, **__kd_cabang__**†, status_retur )  
<sub>±{active[n]['rows']} baris</sub>

**tblrfq_detail** ( **__id__**, #no_rfq → tblrfq_header.no_rfq (D), nobaris, #no_item → tblitem.noitem (L), quantity, satuan, spesifikasi )  
<sub>±{active[n]['rows']} baris</sub>

**tblrfq_header** ( **__no_rfq__**, #no_pr → tblpurchase_request_header.no_pr (D), tanggal_rfq, tanggal_deadline, keterangan, status_rfq, winner_supplier, #kd_cabang → tbcabang.kode_cabang (L), created_by, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblrfq_supplier_response** ( **__id__**, #no_rfq → tblrfq_header.no_rfq (D), #no_supplier → tblsupplier.nosupplier (L), #no_item → tblitem.noitem (L), harga_penawaran, lead_time_days, keterangan, status_response, submitted_date )  
<sub>±{active[n]['rows']} baris</sub>

**tblsupplier** ( **__nosupplier__**†, namasupplier, tipe_pemasok, alamat, kota, propinsi, kodepost, negara, telephone, no_whatsapp, fax, namabank, noaccount, atasnama, kontakperson, email, note, saldoawal, pertanggal, jmlbayar, sisa, #kd_cabang → tbcabang.kode_cabang (L), lama_hari_kirim, jangka_waktu_kredit, accurate_id )  
<sub>±{active[n]['rows']} baris</sub>

**tblsupplier_spart** ( #nosupplier → tblsupplier.nosupplier (L), id_pabrik, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbsaldo_hutang** ( #nosupplier → tblsupplier.nosupplier (L), tanggal, saldo_awal, keterangan, saldo_bayar )  
<sub>±{active[n]['rows']} baris</sub>

---

## D07 — Penjualan, Piutang & Retur

**tbjenis_penggantian_retur** ( **__id__**, jenis_penggantian )  
<sub>±{active[n]['rows']} baris</sub>

**tblorderjual_detail** ( #no_order → tblorderjual_header.no_order (L), nobaris, #no_item → tblitem.noitem (L), quantity, qty_terima, harga_jual, potongan, harga_sp, harga_pokok, total, user, #kd_cabang → tbcabang.kode_cabang (L), status_trx, margin_jual, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tblorderjual_header** ( **__no_order__**†, status, tanggal, #no_sales → tblsales.nosales (L), #no_pelanggan → tblpelanggan.nopelanggan (L), note, total_qty, total_terima, total_jual, diskon, total_diskon, pajak, total_pajak, total_akhir, pembayaran, user, id_tabel, **__kd_cabang__**†, tipe_trx, order_ke, status_pesanan )  
<sub>±{active[n]['rows']} baris</sub>

**tblpenjualan_detail** ( #no_transaksi → tblpenjualan_header.notransaksi (L), nobaris, #no_item → tblitem.noitem (L), qty_order, quantity, qty_retur, harga_jual, potongan, harga_sp, harga_pokok, total, sts_order, **__id__**, user, #kd_cabang → tbcabang.kode_cabang (L), status_trx )  
<sub>±{active[n]['rows']} baris</sub>

**tblpenjualan_harian** ( **__id__**, #no_item → tblitem.noitem (L), #kd_cabang → tbcabang.kode_cabang (L), tanggal, qty_jual, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblpenjualan_header** ( **__notransaksi__**†, status, carabayar, tanggal, #no_order → tblorderjual_header.no_order (L), tanggal_order, #no_sales → tblsales.nosales (L), #no_pelanggan → tblpelanggan.nopelanggan (L), note, total_qty_order, total_qty, total_jual, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user, id_tabel, **__kd_cabang__**†, status_lunas, lama_hari )  
<sub>±{active[n]['rows']} baris</sub>

**tblpenjualan_mingguan** ( **__id__**, #no_item → tblitem.noitem (L), #kd_cabang → tbcabang.kode_cabang (L), tahun, minggu, qty_jual, jml_transaksi, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblpiutang_detail** ( **__no_transaksi__**†, **__nobaris__**†, #no_penjualan → tblpenjualan_header.notransaksi (L), keterangan, jumlah_piutang, jumlah_bayar, status )  
<sub>±{active[n]['rows']} baris</sub>

**tblpiutang_header** ( **__no_transaksi__**†, tanggal, #no_pelanggan → tblpelanggan.nopelanggan (L), note, total_bayar, user, id_tabel, **__kd_cabang__**† )  
<sub>±{active[n]['rows']} baris</sub>

**tblretur_penjualan_detail** ( #no_retur → tblretur_penjualan_header.noretur (L), no_pembelian, #no_item → tblitem.noitem (L), quantity, harga_pokok, potongan, total, user, #kd_cabang → tbcabang.kode_cabang (L), alasan_retur, jenis_penggantian, **__id__**, status_penggantian )  
<sub>±{active[n]['rows']} baris</sub>

**tblretur_penjualan_header** ( **__noretur__**†, #nopembelian → tblpenjualan_header.notransaksi (L), tanggal, note, cara_bayar_refund, total_qty_retur, total_retur, diskon, total_diskon, pajak, total_pajak, total_akhir, user, **__kd_cabang__**†, status_retur, status_refund, tanggal_refund )  
<sub>±{active[n]['rows']} baris</sub>

**tbsaldo_piutang** ( #nopelanggan → tblpelanggan.nopelanggan (L), tanggal, saldo_awal, keterangan, saldo_bayar )  
<sub>±{active[n]['rows']} baris</sub>

**tbso_detail** ( #no_transaksi → tbso_header.no_transaksi (L), #no_item → tblitem.noitem (L), quantity, harga, total, keterangan, user, **__id__**, #kd_cabang → tbcabang.kode_cabang (L), status_trx )  
<sub>±{active[n]['rows']} baris</sub>

**tbso_header** ( **__no_transaksi__**†, tanggal, note, user, **__kd_cabang__**† )  
<sub>±{active[n]['rows']} baris</sub>

---

## D08 — Antar Cabang

**tbl_setting_antarcabang** ( **__id__**, #kd_cabang → tbcabang.kode_cabang (L), #tipe_cabang_tujuan → tbcabang_tipe.id (L), diskon_persen, margin_persen, tempo_hari, cara_bayar, aktif, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblorder_antarcab_detail** ( **__id__**, #no_order → tblorder_antarcab_header.no_order (L), no_baris, #no_item → tblitem.noitem (L), qty_request, qty_kirim, qty_terima, harga_pokok, subtotal )  
<sub>±{active[n]['rows']} baris</sub>

**tblorder_antarcab_header** ( **__no_order__**, #kd_cabang_asal → tbcabang.kode_cabang (L), #kd_cabang_tujuan → tbcabang.kode_cabang (L), tanggal_request, tanggal_proses, tanggal_kirim, tanggal_terima, status, jenis, #no_penjualan → tblpenjualan_header.notransaksi (L), #no_pembelian → tblpembelian_header.notransaksi (L), total_item, total_qty, total_nilai, catatan, user_request, user_proses, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

---

## D09 — Stok & Inventory

**tbbrg_keluar_detail** ( #no_transaksi → tbbrg_keluar_header.no_transaksi (L), #no_item → tblitem.noitem (L), quantity, harga, total, keterangan, user, **__id__**, #kd_cabang → tbcabang.kode_cabang (L), status_trx, stok_sistem, penyesuaian )  
<sub>±{active[n]['rows']} baris</sub>

**tbbrg_keluar_header** ( **__no_transaksi__**†, tanggal, note, user, **__kd_cabang__**†, #kd_cabang_ke → tbcabang.kode_cabang (L) )  
<sub>±{active[n]['rows']} baris</sub>

**tbbrg_masuk_detail** ( #no_transaksi → tbbrg_masuk_header.no_transaksi (L), #no_item → tblitem.noitem (L), quantity, harga, total, keterangan, user, **__id__**, #kd_cabang → tbcabang.kode_cabang (L), status_trx, stok_sistem, penyesuaian )  
<sub>±{active[n]['rows']} baris</sub>

**tbbrg_masuk_header** ( **__no_transaksi__**†, tanggal, note, user, **__kd_cabang__**†, #kd_cabang_dr → tbcabang.kode_cabang (L) )  
<sub>±{active[n]['rows']} baris</sub>

**tbitem_keluar_detail** ( #no_transaksi → tbitem_keluar_header.no_transaksi (L), #no_item → tblitem.noitem (L), quantity, harga, total, keterangan, user, **__id__**, #kd_cabang → tbcabang.kode_cabang (L), status_trx, stok_sistem, penyesuaian )  
<sub>±{active[n]['rows']} baris</sub>

**tbitem_keluar_header** ( **__no_transaksi__**†, tanggal, note, user, **__kd_cabang__**† )  
<sub>±{active[n]['rows']} baris</sub>

**tbitem_masuk_detail** ( #no_transaksi → tbitem_masuk_header.no_transaksi (L), #no_item → tblitem.noitem (L), quantity, harga, total, keterangan, user, **__id__**, #kd_cabang → tbcabang.kode_cabang (L), status_trx, stok_sistem, penyesuaian )  
<sub>±{active[n]['rows']} baris</sub>

**tbitem_masuk_header** ( **__no_transaksi__**†, tanggal, note, user, **__kd_cabang__**† )  
<sub>±{active[n]['rows']} baris</sub>

**tbkoreksi_stok_detail** ( #no_transaksi → tbkoreksi_stok_header.no_transaksi (L), #no_item → tblitem.noitem (L), quantity, keterangan, user, **__id__**, #kd_cabang → tbcabang.kode_cabang (L), status_trx, stok_sistem, penyesuaian, tipe_stok )  
<sub>±{active[n]['rows']} baris</sub>

**tbkoreksi_stok_header** ( **__no_transaksi__**†, tanggal, note, user, **__kd_cabang__**† )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_koreksi_detail** ( **__no_transaksi__**, **__kd_cabang__**, **__no_baris__**, #no_item → tblitem.noitem (L), type_tr, qty, harga, hpp, total, note, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_koreksi_header** ( **__no_transaksi__**, **__kd_cabang__**, tanggal, total_qty, total, note, user_input, id_tabel, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_mutasi_detail** ( **__no_transaksi__**, **__kd_cabang__**, **__no_baris__**, #no_item → tblitem.noitem (L), type_tr, qty, harga, total, note, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblitem_mutasi_header** ( **__no_transaksi__**, **__kd_cabang__**, tanggal, total_qty, total, note, user_input, id_tabel, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbstok** ( tipe, no_transaksi, #no_item → tblitem.noitem (L), tanggal, masuk, keluar, keterangan, #kd_cabang → tbcabang.kode_cabang (L), **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

---

## D10 — Keuangan Kasir & Setoran

**audit_log_closing_kasir** ( **__id__**, #kode_karyawan → tbuser.kode_karyawan (D), action, table_name, record_id, old_data, new_data, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**closing_revision_requests_closing_kasir** ( **__id__**, kode_transaksi_lama, kode_transaksi_baru, #kode_cabang → tbcabang.cabang_ref_kode (D), #kode_pemohon → tbuser.kode_karyawan (L), alasan, status, approver_kode, approval_note, approved_at, rejected_at, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**closing_transaction_details_closing_kasir** ( **__id__**, #group_id → closing_transaction_groups_closing_kasir.id (D), #transaction_id → kasir_transactions_closing_kasir.id (L), jenis_dalam_closing, nominal, keterangan, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**closing_transaction_groups_closing_kasir** ( **__id__**, group_code, nama_cabang, kode_setoran, tanggal_closing, total_closing, total_dipinjam, total_meminjam, total_gabungan, jumlah_transaksi, status_validasi, validated_by, validated_at, catatan_validasi, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**data_penjualan_closing_kasir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), jumlah_penjualan, tanggal, waktu, #kode_karyawan → tbuser.kode_karyawan (D) )  
<sub>±{active[n]['rows']} baris</sub>

**data_servis_closing_kasir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), jumlah_servis, tanggal, waktu, #kode_karyawan → tbuser.kode_karyawan (D) )  
<sub>±{active[n]['rows']} baris</sub>

**detail_kas_akhir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), nominal, jumlah_keping )  
<sub>±{active[n]['rows']} baris</sub>

**detail_kas_awal** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), nominal, jumlah_keping )  
<sub>±{active[n]['rows']} baris</sub>

**kas_akhir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), total_nilai, tanggal, waktu, #kode_karyawan → tbuser.kode_karyawan (D) )  
<sub>±{active[n]['rows']} baris</sub>

**kas_awal** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), total_nilai, tanggal, waktu, status, #kode_karyawan → tbuser.kode_karyawan (D) )  
<sub>±{active[n]['rows']} baris</sub>

**kas_awal_config_closing_kasir** ( **__id__**, #kode_cabang → tbcabang.cabang_ref_kode (D), nama_cabang, nominal_minimum, status, created_by, updated_by, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**kasir_transactions_closing_kasir** ( **__id__**, kode_transaksi, kas_awal, kas_akhir, total_pemasukan, total_pengeluaran, total_penjualan, total_servis, status, tanggal_transaksi, tanggal_closing, jam_closing, setoran_real, omset, data_setoran, selisih_setoran, #kode_karyawan → tbuser.kode_karyawan (D), #kasir_asal → tbuser.kode_karyawan (L), #kode_cabang → tbcabang.cabang_ref_kode (D), nama_cabang, deposit_status, deposit_difference_status, jenis_closing, #closing_group_id → closing_transaction_groups_closing_kasir.id (L), is_part_of_closing, jenis_setoran_id, kode_setoran, jumlah_diterima_fisik, selisih_fisik, catatan_validasi, #rekening_tujuan_id → master_rekening_cabang_closing_kasir.id (D), validasi_at, validasi_by, bukti_transaksi, revision_parent_kode, revision_child_kode, cancelled_at, cancelled_by, cancel_reason )  
<sub>±{active[n]['rows']} baris</sub>

**keping_closing_kasir** ( **__id__**, nominal )  
<sub>±{active[n]['rows']} baris</sub>

**konfirmasi_buka_transaksi_closing_kasir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), #kode_karyawan_peminta → tbuser.kode_karyawan (L), nama_cabang, tanggal_permintaan, tanggal_diproses, status, alasan_permintaan, catatan_admin, #kode_karyawan_admin → tbuser.kode_karyawan (L), created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**master_akun_closing_kasir** ( **__id__**, kode_akun, arti, jenis_akun, kategori, require_umur_pakai, min_umur_pakai )  
<sub>±{active[n]['rows']} baris</sub>

**master_nama_transaksi_closing_kasir** ( **__id__**, nama_transaksi, #kode_akun → master_akun_closing_kasir.kode_akun (D), keterangan_default, status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**master_rekening_cabang_closing_kasir** ( **__id__**, #kode_cabang → tbcabang.cabang_ref_kode (D), nama_bank, nama_rekening, no_rekening, jenis_rekening, status, created_by, updated_by, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**pemasukan_kasir_closing_kasir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), #kode_akun → master_akun_closing_kasir.kode_akun (D), kode_pengambilan_ref, jumlah, keterangan_transaksi, tanggal, waktu, #kode_karyawan → tbuser.kode_karyawan (D), #kode_cabang → tbcabang.kode_cabang (L), nomor_transaksi_closing )  
<sub>±{active[n]['rows']} baris</sub>

**pemasukan_pusat_closing_kasir** ( **__id__**, kode_transaksi, #kode_karyawan → tbuser.kode_karyawan (D), cabang, #kode_akun → master_akun_closing_kasir.kode_akun (D), jumlah, keterangan, tanggal, tanggal_transaksi, waktu, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**pengambilan_setoran_closing_kasir** ( **__id__**, kode_pengambilan, parent_kode_pengambilan, #kode_karyawan_input → tbuser.kode_karyawan (L), #kode_cabang_keuangan → tbcabang.kode_cabang (L), nama_cabang_keuangan, nominal_diambil, nominal_sisa, id_setoran_bank, kode_setoran_bank, jenis_rekening, klasifikasi, no_rekening_peminjam, no_rekening_penerima, #kode_cabang_penerima → tbcabang.kode_cabang (L), tanggal_perencanaan_setor, status, status_setor_bank, tanggal_penyerahan_setor, tanggal_riil_setor_bank, #setoran_ke_bank_id → setoran_ke_bank_closing_kasir.id (D), verified_nominal_kas_masuk, verified_notrx_kas_masuk, verified_cabang_penerima_sudah_closing, verified_cabang_closing_at, verified_mutasi_bank, mutasi_dokumen_path, mutasi_dokumen_nama_asli, mutasi_dokumen_mime, mutasi_dokumen_tipe, mutasi_rekening_pengirim, mutasi_rekening_penerima, mutasi_nominal_terdeteksi, mutasi_confidence, mutasi_hasil_json, mutasi_verified_by, mutasi_verified_at, id_pemasukan_gudang, keterangan, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**pengambilan_setoran_edit_log_closing_kasir** ( **__id__**, #kode_pengambilan → pengambilan_setoran_closing_kasir.kode_pengambilan (D), edited_by_role, edited_by_kode_karyawan, field_changed, old_value, new_value, pemasukan_kasir_id_invalidated, alasan, dibaca, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**pengambilan_setoran_pembayaran_closing_kasir** ( **__id__**, #kode_pengambilan → pengambilan_setoran_closing_kasir.kode_pengambilan (D), #pemasukan_kasir_id → pemasukan_kasir_closing_kasir.id (D), nominal_dibayar, sumber, no_transaksi, tanggal_transfer, kode_validasi_input, #kode_karyawan_input → tbuser.kode_karyawan (L), catatan, dokumen_path, dokumen_nama_asli, dokumen_mime, dokumen_tipe, rekening_pengirim_terdeteksi, rekening_penerima_terdeteksi, nominal_terdeteksi, confidence, hasil_ekstraksi_json, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**pengeluaran_kasir_closing_kasir** ( **__id__**, #kode_transaksi → kasir_transactions_closing_kasir.kode_transaksi (L), #kode_akun → master_akun_closing_kasir.kode_akun (D), jumlah, keterangan_transaksi, tanggal, waktu, #kode_karyawan → tbuser.kode_karyawan (D), #kode_cabang → tbcabang.kode_cabang (L), umur_pakai, kategori )  
<sub>±{active[n]['rows']} baris</sub>

**pengeluaran_pusat_closing_kasir** ( **__id__**, kode_transaksi, #kode_karyawan → tbuser.kode_karyawan (D), cabang, kode_akun, jumlah, keterangan, umur_pakai, kategori, tanggal, tanggal_transaksi, waktu, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**serah_terima_kasir_closing_kasir** ( **__id__**, kode_serah_terima, #kode_karyawan_pemberi → tbuser.kode_karyawan (L), #kode_karyawan_penerima → tbuser.kode_karyawan (L), #kode_cabang → tbcabang.cabang_ref_kode (D), kode_transaksi_asal, tanggal_serah_terima, total_setoran, catatan, status, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**setoran_ke_bank_closing_kasir** ( **__id__**, kode_setoran, tanggal_setoran, metode_setoran, rekening_tujuan, total_setoran, bukti_transfer, created_at, created_by, tanggal_setor )  
<sub>±{active[n]['rows']} baris</sub>

**setoran_ke_bank_detail_closing_kasir** ( **__id__**, #setoran_ke_bank_id → setoran_ke_bank_closing_kasir.id (D), #setoran_keuangan_id → setoran_keuangan_closing_kasir.id (D) )  
<sub>±{active[n]['rows']} baris</sub>

**setoran_keuangan_closing_kasir** ( **__id__**, #kode_setoran → kasir_transactions_closing_kasir.kode_setoran (L), #kode_karyawan → tbuser.kode_karyawan (D), #kode_cabang → tbcabang.cabang_ref_kode (D), nama_cabang, tanggal_setoran, tanggal_closing, jumlah_setoran, nama_pengantar, status, created_at, updated_at, updated_by, #kasir_asal_kode → tbuser.kode_karyawan (L), kasir_asal_nama, jumlah_diterima, selisih_setoran, catatan_validasi, total_selisih, created_by, has_closing_transactions, total_closing_groups, closing_summary )  
<sub>±{active[n]['rows']} baris</sub>

**tbakun** ( no_akun, nama_akun, pos, debet, kredit, status_akun, akun_induk, no_akun_induk )  
<sub>±{active[n]['rows']} baris</sub>

**tbakun_pos** ( pos )  
<sub>±{active[n]['rows']} baris</sub>

**tbkeping** ( keping, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tblakunkas** ( kodeakun, namaakun, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tblkas_keluar_masuk** ( kode_km, no_bukti, kode_akun, tanggal, uraian, masuk, keluar, jenis, #kd_cabang → tbcabang.kode_cabang (L), user, kode_akun_biaya, tipe )  
<sub>±{active[n]['rows']} baris</sub>

---

## D11 — Komplain, Issue & Merge Data

**master_jenis_masalah** ( **__id_jenis__**, kategori, nama_masalah, skema_field, role_approval, target_eksekusi, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbl_issue** ( **__id_issue__**, tanggal_lapor, #pelapor → tbuser.id (L), #kd_cabang → tbcabang.kode_cabang (L), divisi_terkait, kategori, #id_jenis_masalah → master_jenis_masalah.id_jenis (D), prioritas, status, #pic → tbuser.id (L), deadline, deskripsi, payload_json, solusi, #ref_nopelanggan → tblpelanggan.nopelanggan (L), #ref_no_polisi → tblkendaraan.nopolisi (L), created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tbl_issue_progress_log** ( **__id__**, #id_issue → tbl_issue.id_issue (L), tanggal, oleh, catatan, status_before, status_after )  
<sub>±{active[n]['rows']} baris</sub>

**tblkomplain** ( **__id__**, no_komplain, nama_pelanggan, no_hp, #nopol → tblkendaraan.nopolisi (L), #kode_cabang → tbcabang.kode_cabang (L), #kode_kategori → tblkomplain_kategori.kode_kategori (D), channel_lapor, tanggal_lapor, detail_keluhan, no_nota_rujukan, #no_service_asli → tblservice.no_service (L), #no_service_rework → tblservice.no_service (L), #pic_kode_karyawan → tbuser.kode_karyawan (L), status, mekanik_servis_awal, #mekanik_pelaksana_kode → tbuser.kode_karyawan (L), jenis_usulan, alasan_usulan, rencana_tanggal_kedatangan, tanggal_rework_aktual, biaya_rework, jumlah_revisi, keputusan_kepala_cabang, tindak_lanjut_penanganan, tanggal_ditindaklanjuti, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblkomplain_export_log** ( **__id__**, #kode_karyawan → tbuser.kode_karyawan (L), cakupan_filter, jumlah_baris, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblkomplain_kategori** ( **__id__**, kode_kategori, nama_kategori, pic_role, jenis_penyelesaian, is_active, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**tblkomplain_log** ( **__id__**, #komplain_id → tblkomplain.id (D), aksi, status_sebelum, status_sesudah, kode_karyawan_pelaku, keterangan, created_at )  
<sub>±{active[n]['rows']} baris</sub>

---

## D12 — HRD & Absensi

**tbabsensi** ( #nip → tbpegawai.nip (L), tgl, jam_masuk, jam_keluar, kode_status_kehadiran, keterangan, kode_perusahaan, kode_lokasi, lat_masuk, long_masuk, jarak_masuk, lat_pulang, long_pulang, jarak_pulang, nip_di_finger, **__id__**, id_upload )  
<sub>±{active[n]['rows']} baris</sub>

**tbabsensi_temp** ( no_urut, id, waktu, tgl, jam )  
<sub>±{active[n]['rows']} baris</sub>

**tbabsensi_temp_rst** ( id, tgl, jam_masuk, Jam_pulang )  
<sub>±{active[n]['rows']} baris</sub>

**tbabsensi_upload** ( id_upload, tgl_upload, waktu_upload, tgl_absensi_awal, tgl_absensi_akhir, #kode_cabang → tbcabang.kode_cabang (L), **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbagama** ( agama, **__kode__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbdarah** ( kode, darah )  
<sub>±{active[n]['rows']} baris</sub>

**tbemp_education** ( **__id__**, #nip → tbpegawai.nip (L), fld1, fld2, fld3, fld4, fld5 )  
<sub>±{active[n]['rows']} baris</sub>

**tbemp_family** ( **__id__**, #nip → tbpegawai.nip (L), nama, id_jk, alamat, notlp, hubungan )  
<sub>±{active[n]['rows']} baris</sub>

**tbemp_training** ( **__id__**, #nip → tbpegawai.nip (L), fld1, fld2, fld3 )  
<sub>±{active[n]['rows']} baris</sub>

**tbemp_tunjangan** ( **__id__**, #nip → tbpegawai.nip (L), nama_tunjangan, nilai_tunjangan )  
<sub>±{active[n]['rows']} baris</sub>

**tbhari_kerja** ( **__id__**, hari_kerja )  
<sub>±{active[n]['rows']} baris</sub>

**tbhistory_divisi** ( #nip → tbpegawai.nip (L), #kode_divisi → tbdivisi.kode_divisi (L), #kode_jabatan → tbjabatan.kode_jabatan (L), tgl_start, tgl_end, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbjk** ( **__kode_jk__**, jk )  
<sub>±{active[n]['rows']} baris</sub>

**tblist_bpjs** ( **__id__**, list_bpjs )  
<sub>±{active[n]['rows']} baris</sub>

**tbpegawai** ( **__nip__**†, nama, alamat, #kode_jabatan → tbjabatan.kode_jabatan (L), notlp, kode_jk, tempat_lahir, tgl_lahir, email, kode_pendidikan, npwp, kode_status_kawin, jml_tanggungan, kode_ptkp, gaji_pokok, no_rek, nama_rek, bank, ktp, kode_agama, kode_darah, no_bpjs_tk, no_bpjs_kes, #kode_divisi → tbdivisi.kode_divisi (L), kode_status_emp, tgl_masuk, foto_pegawai, kota, prop, district, districtsub, kodepos, id_hari_kerja, jumlah_cuti, user_name, password, tgl_input, status_aktif, tlp_rumah, wage_template, id_tipepajak, lokasi, #kode_perusahaan → tbperusahaan.kode_perusahaan (L), negara )  
<sub>±{active[n]['rows']} baris</sub>

**tbpegawai_salary** ( #nip → tbpegawai.nip (L), id_salary, jumlah, **__id__** )  
<sub>±{active[n]['rows']} baris</sub>

**tbpendidikan** ( **__kode__**, pendidikan )  
<sub>±{active[n]['rows']} baris</sub>

**tbstatus_emp** ( **__kode__**, status )  
<sub>±{active[n]['rows']} baris</sub>

**tbstatus_kehadiran** ( **__id__**, status_kehadiran )  
<sub>±{active[n]['rows']} baris</sub>

**tbstatus_nikah** ( **__kode__**, status_nikah )  
<sub>±{active[n]['rows']} baris</sub>

**tbtarif_ptkp** ( golongan, kode, tarif, **__id__**, id_parent )  
<sub>±{active[n]['rows']} baris</sub>

**tbtarif_ptkph** ( **__id__**, nama, keterangan, berlaku_mulai )  
<sub>±{active[n]['rows']} baris</sub>

---

## D13 — Integrasi & Konsolidasi Access

**gabung_hpp_acuan** ( **__tahun__**, **__siklus__**, **__no_item__**, frek, qty, beli, acuan_hpp, sts, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_nopolisi_domisili** ( **__no_polisi__**, sts_pes, sts_pac, sts_cik, sts_tra, akhir, sts, domisili, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_pernah_jemputantar** ( **__no_polisi__**, jumlah, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_rekap_kedatangan** ( **__no_polisi__**, jumlah, awal, akhir, pelanggan_sejak_hari, pelanggan_sejak_bulan, lama_tdk_dtg_hari, lama_tdk_dtg_bulan, total, rata2, ganti_oli_akhir, servis_akhir, datang_berikutnya, remind_sebelumnya, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_rekap_konsumen** ( **__no_polisi__**, jumlah, awal, akhir, pelanggan_sejak_hari, pelanggan_sejak_bulan, lama_tdk_dtg_hari, lama_tdk_dtg_bulan, total, rata2, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_rekap_konsumen_wa** ( nama_pelanggan, domisili, jum_motor, tot_datang, total_nilai, rata2_nilai, awal, akhir, datang_berikutnya, lama_pelanggan, lama_tdk_datang, tiap_30hari, telephone, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_rekap_peritem_terakhir** ( **__no_item__**, **__sts__**, tggl, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_rekonsil_antarcabang** ( **__id__**, cabang, tanggal, kode_jual, nama_jual, tipe_jual, kategori_jual, no_jual, item_jual, qty_jual, hpp_jual, nilai_jual, sumber, kode_beli, nama_beli, tipe_beli, kategori_beli, no_beli, item_beli, qty_beli, hpp_beli, nilai_beli, id_beli, tanggal_data, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**gabung_statistik_item_minggu** ( **__noitem__**, **__sts__**, m1, m2, m3, m4, m5, m6, m7, m8, m9, m10, m11, m12, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

**konsolidasi_pembelian_detail** ( **__id__**, kd_cabang, no_transaksi, no_baris, no_item, qty_order, quantity, qty_retur, harga_pokok, potongan, harga_sp, total, sts_order, id_inv, source_run_id, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**konsolidasi_pembelian_header** ( **__id__**, kd_cabang, no_transaksi, status, cara_bayar, tanggal, no_order, tanggal_order, no_supplier, note, total_qty_order, total_qty, total_beli, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user_input, id_tabel, source_run_id, source_file_date, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**konsolidasi_penjualan_detail** ( **__id__**, kd_cabang, no_transaksi, no_baris, no_item, qty_order, quantity, qty_retur, harga_jual, potongan, harga_sp, harga_pokok, total, sts_order, source_run_id, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**konsolidasi_penjualan_header** ( **__id__**, kd_cabang, no_transaksi, status, cara_bayar, tanggal, no_order, tanggal_order, no_sales, no_pelanggan, note, total_qty_order, total_qty, total_jual, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user_input, id_tabel, source_run_id, source_file_date, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**konsolidasi_service** ( **__id__**, kd_cabang, no_service, tanggal, jam, no_pelanggan, no_polisi, mekanik1, mekanik2, mekanik3, mekanik4, biayam1, biayam2, biayam3, biayam4, km_sekarang, km_berikut, total_waktu, status, keterangan, subtotal_jasa, subtotal_item, subtotal, diskon, total_diskon, pajak, total_pajak, total_akhir, pembayaran, user_input, id_tabel, advisor, source_run_id, source_file_date, created_at, updated_at )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_beli_peritem** ( **__id__**, sync_run_id, kd_cabang, tanggal_data, id_tabel, tanggal, no_item, hrgnet, no_supplier, fax, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_insentif** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, siklus, tgl, trx, garap, stts, sts, kode, mekanik, tipe, no_item, quantity, jualnet, tothpp, laba, kategori, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_item** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_item, kode_barcode, nama_item, jenis, satuan, harga_pokok, harga_jual, harga_jual2, harga_jual3, quantity, stok_min, status_item, supplier1, supplier2, supplier3, status_produk, rak_barang, jasa_waktu, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_kendaraan_pelanggan** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_polisi, nama_pelanggan, merek, tipe, jenis, warna, tahun_buat, telephone, grup, alamat, note, kategori, kota, kontak_person, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_mekanik** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_mekanik, nama, alamat, kota, provinsi, no_telepon, note, keahlian, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_member_pelanggan** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_pelanggan, tipe_member, no_polisi, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_pelanggan** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_pelanggan, nama_pelanggan, alamat, kota, propinsi, kode_post, negara, telephone, fax, kontak_person, note, potongan, tipe_pot, lavel_harga, kgrup, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_pembelian** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_transaksi, no_baris, status, cara_bayar, tanggal, no_order, tanggal_order, no_supplier, note, total_qty_order, total_qty, total_beli, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user_input, id_tabel, no_item, qty_order, quantity, qty_retur, harga_pokok, potongan, harga_sp, total, sts_order, id_inv, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_penjualan** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_transaksi, no_baris, status, cara_bayar, tanggal, no_order, tanggal_order, no_sales, no_pelanggan, note, total_qty_order, total_qty, total_jual, diskon, total_diskon, pajak, total_pajak, total_akhir, total_retur, pembayaran, tanggal_jt, tanggal_lunas, jumlah_bayar, user_input, id_tabel, no_item, qty_order, quantity, qty_retur, harga_jual, potongan, harga_sp, harga_pokok, total, sts_order, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_service** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_service, tanggal, jam, no_pelanggan, no_polisi, mekanik1, mekanik2, mekanik3, mekanik4, biayam1, biayam2, biayam3, biayam4, km_sekarang, km_berikut, total_waktu, status, keterangan, subtotal_jasa, subtotal_item, subtotal, diskon, total_diskon, pajak, total_pajak, total_akhir, pembayaran, user_input, id_tabel, advisor, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_service_advisor** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_service, advisor, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_service_barang** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_service, tanggal, no_pelanggan, no_polisi, mekanik1, mekanik2, mekanik3, mekanik4, biayam1, biayam2, biayam3, biayam4, km_sekarang, km_berikut, total_waktu, status, keterangan, subtotal_jasa, subtotal_item, subtotal, diskon, total_diskon, pajak, total_pajak, total_akhir, pembayaran, user_input, id_tabel, no_baris, no_item, quantity, qty_retur, harga, potongan, total, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_service_jasa** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_service, tanggal, no_pelanggan, no_polisi, mekanik1, mekanik2, mekanik3, mekanik4, biayam1, biayam2, biayam3, biayam4, km_sekarang, km_berikut, total_waktu, status, keterangan, subtotal_jasa, subtotal_item, subtotal, diskon, total_diskon, pajak, total_pajak, total_akhir, pembayaran, user_input, id_tabel, no_baris, no_item, waktu, harga, potongan, total, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_gabung_supplier** ( **__id__**, sync_run_id, kd_cabang, sts_source, tanggal_data, no_supplier, nama_supplier, alamat, kota, propinsi, kode_post, negara, telephone, fax, nama_bank, no_account, atas_nama, kontak_person, email, note, saldo_awal, per_tanggal, jml_bayar, sisa, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**stg_access_nomor_wa** ( **__id__**, sync_run_id, kd_cabang, tanggal_data, nama_pelanggan, telephone, no_pelanggan, domisili, raw_payload )  
<sub>±{active[n]['rows']} baris</sub>

**sync_access_row_errors** ( **__id__**, sync_run_id, dataset_name, row_key, error_message, raw_payload, created_at )  
<sub>±{active[n]['rows']} baris</sub>

**sync_access_runs** ( **__id__**, source_name, dataset_key, trigger_source, source_cabang, machine_name, batch_key, request_ip, source_file, sync_mode, started_at, finished_at, last_heartbeat_at, status, total_rows, success_rows, failed_rows, merge_status, merge_processed, merge_inserted, merge_updated, merge_upserted, notes )  
<sub>±{active[n]['rows']} baris</sub>

**sync_gabung_log** ( **__id__**, dataset_key, source_table, target_table, started_at, finished_at, status, total_rows, success_rows, failed_rows, notes )  
<sub>±{active[n]['rows']} baris</sub>

**tbsiklus** ( **__kd_cabang__**, **__siklus__**, tglawal, tglakhir, synced_at )  
<sub>±{active[n]['rows']} baris</sub>

---

## Tabel Relasi Lengkap

| # | Tabel anak | Kolom FK | Tabel induk | Kolom PK induk | Jenis |
|---|---|---|---|---|---|
| 1 | alarm_harga_beli | no_item | tblitem | noitem | Logis (kode) |
| 2 | alarm_harga_beli | no_transaksi_pembelian | tblpembelian_header | notransaksi | Logis (kode) |
| 3 | audit_log_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 4 | closing_revision_requests_closing_kasir | kode_cabang | tbcabang | cabang_ref_kode | Declared FK |
| 5 | closing_revision_requests_closing_kasir | kode_pemohon | tbuser | kode_karyawan | Logis (kode) |
| 6 | closing_transaction_details_closing_kasir | group_id | closing_transaction_groups_closing_kasir | id | Declared FK |
| 7 | closing_transaction_details_closing_kasir | transaction_id | kasir_transactions_closing_kasir | id | Logis (kode) |
| 8 | customer_alias | nopelanggan_baru | tblpelanggan | nopelanggan | Logis (kode) |
| 9 | customer_merge_log | nopelanggan_source | tblpelanggan | nopelanggan | Logis (kode) |
| 10 | customer_merge_log | nopelanggan_target | tblpelanggan | nopelanggan | Logis (kode) |
| 11 | data_penjualan_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 12 | data_penjualan_closing_kasir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 13 | data_servis_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 14 | data_servis_closing_kasir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 15 | detail_kas_akhir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 16 | detail_kas_awal | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 17 | kas_akhir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 18 | kas_akhir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 19 | kas_awal | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 20 | kas_awal | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 21 | kas_awal_config_closing_kasir | kode_cabang | tbcabang | cabang_ref_kode | Declared FK |
| 22 | kasir_transactions_closing_kasir | closing_group_id | closing_transaction_groups_closing_kasir | id | Logis (kode) |
| 23 | kasir_transactions_closing_kasir | kasir_asal | tbuser | kode_karyawan | Logis (kode) |
| 24 | kasir_transactions_closing_kasir | kode_cabang | tbcabang | cabang_ref_kode | Declared FK |
| 25 | kasir_transactions_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 26 | kasir_transactions_closing_kasir | rekening_tujuan_id | master_rekening_cabang_closing_kasir | id | Declared FK |
| 27 | kendaraan_plat_history | id_kendaraan | tblkendaraan | id_kendaraan | Logis (kode) |
| 28 | kendaraan_plat_history | nopolisi | tblkendaraan | nopolisi | Logis (kode) |
| 29 | kepemilikan_kendaraan | id_kendaraan | tblkendaraan | id_kendaraan | Logis (kode) |
| 30 | kepemilikan_kendaraan | nopelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 31 | konfirmasi_buka_transaksi_closing_kasir | kode_karyawan_admin | tbuser | kode_karyawan | Logis (kode) |
| 32 | konfirmasi_buka_transaksi_closing_kasir | kode_karyawan_peminta | tbuser | kode_karyawan | Logis (kode) |
| 33 | konfirmasi_buka_transaksi_closing_kasir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 34 | master_diskon_periode_cabang | id_promo | master_diskon_periode | id_promo | Declared FK |
| 35 | master_diskon_periode_cabang | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 36 | master_diskon_periode_syarat | id_promo | master_diskon_periode | id_promo | Declared FK |
| 37 | master_diskon_periode_target | id_promo | master_diskon_periode | id_promo | Declared FK |
| 38 | master_jabatan | kode_jabatan | tbjabatan | kode_jabatan | Logis (kode) |
| 39 | master_jabatan | kode_posisi | tb_master_posisi | kode_posisi | Logis (kode) |
| 40 | master_kedatangan_pelanggan | no_pelanggan | tblpelanggan | nopelanggan | Declared FK |
| 41 | master_kedatangan_pelanggan | no_service | tblservice | no_service | Logis (kode) |
| 42 | master_nama_transaksi_closing_kasir | kode_akun | master_akun_closing_kasir | kode_akun | Declared FK |
| 43 | master_perusahaan | kode_perusahaan | tbperusahaan | kode_perusahaan | Logis (kode) |
| 44 | master_rekening_cabang_closing_kasir | kode_cabang | tbcabang | cabang_ref_kode | Declared FK |
| 45 | pemasukan_kasir_closing_kasir | kode_akun | master_akun_closing_kasir | kode_akun | Declared FK |
| 46 | pemasukan_kasir_closing_kasir | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 47 | pemasukan_kasir_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 48 | pemasukan_kasir_closing_kasir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 49 | pemasukan_pusat_closing_kasir | kode_akun | master_akun_closing_kasir | kode_akun | Declared FK |
| 50 | pemasukan_pusat_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 51 | pengambilan_setoran_closing_kasir | kode_cabang_keuangan | tbcabang | kode_cabang | Logis (kode) |
| 52 | pengambilan_setoran_closing_kasir | kode_cabang_penerima | tbcabang | kode_cabang | Logis (kode) |
| 53 | pengambilan_setoran_closing_kasir | kode_karyawan_input | tbuser | kode_karyawan | Logis (kode) |
| 54 | pengambilan_setoran_closing_kasir | setoran_ke_bank_id | setoran_ke_bank_closing_kasir | id | Declared FK |
| 55 | pengambilan_setoran_edit_log_closing_kasir | kode_pengambilan | pengambilan_setoran_closing_kasir | kode_pengambilan | Declared FK |
| 56 | pengambilan_setoran_pembayaran_closing_kasir | kode_karyawan_input | tbuser | kode_karyawan | Logis (kode) |
| 57 | pengambilan_setoran_pembayaran_closing_kasir | kode_pengambilan | pengambilan_setoran_closing_kasir | kode_pengambilan | Declared FK |
| 58 | pengambilan_setoran_pembayaran_closing_kasir | pemasukan_kasir_id | pemasukan_kasir_closing_kasir | id | Declared FK |
| 59 | pengeluaran_kasir_closing_kasir | kode_akun | master_akun_closing_kasir | kode_akun | Declared FK |
| 60 | pengeluaran_kasir_closing_kasir | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 61 | pengeluaran_kasir_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 62 | pengeluaran_kasir_closing_kasir | kode_transaksi | kasir_transactions_closing_kasir | kode_transaksi | Logis (kode) |
| 63 | pengeluaran_pusat_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 64 | permintaan_pindah_kepemilikan_kendaraan | id_kendaraan | tblkendaraan | id_kendaraan | Logis (kode) |
| 65 | permintaan_pindah_kepemilikan_kendaraan | nopelanggan_baru | tblpelanggan | nopelanggan | Logis (kode) |
| 66 | permintaan_pindah_kepemilikan_kendaraan | nopelanggan_lama | tblpelanggan | nopelanggan | Logis (kode) |
| 67 | promo_usage_log | id_promo | master_diskon_periode | id_promo | Declared FK |
| 68 | promo_usage_log | no_service | tblservice | no_service | Logis (kode) |
| 69 | serah_terima_kasir_closing_kasir | kode_cabang | tbcabang | cabang_ref_kode | Declared FK |
| 70 | serah_terima_kasir_closing_kasir | kode_karyawan_pemberi | tbuser | kode_karyawan | Logis (kode) |
| 71 | serah_terima_kasir_closing_kasir | kode_karyawan_penerima | tbuser | kode_karyawan | Logis (kode) |
| 72 | servis_komisi | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 73 | servis_komisi | no_service | tblservice | no_service | Logis (kode) |
| 74 | setoran_ke_bank_detail_closing_kasir | setoran_ke_bank_id | setoran_ke_bank_closing_kasir | id | Declared FK |
| 75 | setoran_ke_bank_detail_closing_kasir | setoran_keuangan_id | setoran_keuangan_closing_kasir | id | Declared FK |
| 76 | setoran_keuangan_closing_kasir | kasir_asal_kode | tbuser | kode_karyawan | Logis (kode) |
| 77 | setoran_keuangan_closing_kasir | kode_cabang | tbcabang | cabang_ref_kode | Declared FK |
| 78 | setoran_keuangan_closing_kasir | kode_karyawan | tbuser | kode_karyawan | Declared FK |
| 79 | setoran_keuangan_closing_kasir | kode_setoran | kasir_transactions_closing_kasir | kode_setoran | Logis (kode) |
| 80 | statistik_kendaraan | id_kendaraan | tblkendaraan | id_kendaraan | Logis (kode) |
| 81 | statistik_kendaraan | nopelanggan_current | tblpelanggan | nopelanggan | Logis (kode) |
| 82 | statistik_pelanggan | no_pelanggan | tblpelanggan | nopelanggan | Declared FK |
| 83 | tb_antrian_servis | no_service | tblservice | no_service | Logis (kode) |
| 84 | tb_approval_diskon | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 85 | tb_approval_diskon | no_service | tblservice | no_service | Logis (kode) |
| 86 | tb_booking | no_polisi | tblkendaraan | nopolisi | Logis (kode) |
| 87 | tb_dp_servis | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 88 | tb_dp_servis | no_service | tblservice | no_service | Logis (kode) |
| 89 | tb_kepala_mekanik_schedule | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 90 | tb_kepala_mekanik_schedule | kode_karyawan | tbuser_karyawan | kode_karyawan | Declared FK |
| 91 | tb_log_antrian | no_antrian | tb_antrian_servis | no_antrian | Logis (kode) |
| 92 | tb_log_antrian | no_service | tblservice | no_service | Logis (kode) |
| 93 | tb_log_cancel_servis | no_service | tblservice | no_service | Logis (kode) |
| 94 | tb_master_approval_pembelian | kode_posisi | tb_master_posisi | kode_posisi | Logis (kode) |
| 95 | tb_master_level | kode_posisi | tb_master_posisi | kode_posisi | Declared FK |
| 96 | tb_mekanik_progress | kode_karyawan | tbuser_karyawan | kode_karyawan | Declared FK |
| 97 | tb_mekanik_progress | no_service | tblservice | no_service | Logis (kode) |
| 98 | tb_progress_mekanik | no_antrian | tb_antrian_servis | no_antrian | Logis (kode) |
| 99 | tb_progress_mekanik | no_service | tblservice | no_service | Logis (kode) |
| 100 | tb_user_activity_log | user_id | tbuser | id | Logis (kode) |
| 101 | tb_user_mekanik_mapping | user_id | tbuser | id | Logis (kode) |
| 102 | tbabsensi | nip | tbpegawai | nip | Logis (kode) |
| 103 | tbabsensi_upload | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 104 | tbbeli_peritem_history | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 105 | tbbeli_peritem_history | no_item | tblitem | noitem | Logis (kode) |
| 106 | tbbeli_peritem_history | no_supplier | tblsupplier | nosupplier | Logis (kode) |
| 107 | tbbrg_keluar_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 108 | tbbrg_keluar_detail | no_item | tblitem | noitem | Logis (kode) |
| 109 | tbbrg_keluar_detail | no_transaksi | tbbrg_keluar_header | no_transaksi | Logis (kode) |
| 110 | tbbrg_keluar_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 111 | tbbrg_keluar_header | kd_cabang_ke | tbcabang | kode_cabang | Logis (kode) |
| 112 | tbbrg_masuk_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 113 | tbbrg_masuk_detail | no_item | tblitem | noitem | Logis (kode) |
| 114 | tbbrg_masuk_detail | no_transaksi | tbbrg_masuk_header | no_transaksi | Logis (kode) |
| 115 | tbbrg_masuk_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 116 | tbbrg_masuk_header | kd_cabang_dr | tbcabang | kode_cabang | Logis (kode) |
| 117 | tbcabang | perusahaan_id | master_perusahaan | id | Declared FK |
| 118 | tbcabang | tipe_cabang | tbcabang_tipe | id | Logis (kode) |
| 119 | tbemp_education | nip | tbpegawai | nip | Logis (kode) |
| 120 | tbemp_family | nip | tbpegawai | nip | Logis (kode) |
| 121 | tbemp_training | nip | tbpegawai | nip | Logis (kode) |
| 122 | tbemp_tunjangan | nip | tbpegawai | nip | Logis (kode) |
| 123 | tbhistory_divisi | kode_divisi | tbdivisi | kode_divisi | Logis (kode) |
| 124 | tbhistory_divisi | kode_jabatan | tbjabatan | kode_jabatan | Logis (kode) |
| 125 | tbhistory_divisi | nip | tbpegawai | nip | Logis (kode) |
| 126 | tbinsentif_jual_servis | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 127 | tbinsentif_jual_servis | no_item | tblitem | noitem | Logis (kode) |
| 128 | tbitem_jenis_motor | kd_kategori_motor | tbkategori_motor | id | Logis (kode) |
| 129 | tbitem_jenis_motor | noitem | tblitem | noitem | Logis (kode) |
| 130 | tbitem_keluar_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 131 | tbitem_keluar_detail | no_item | tblitem | noitem | Logis (kode) |
| 132 | tbitem_keluar_detail | no_transaksi | tbitem_keluar_header | no_transaksi | Logis (kode) |
| 133 | tbitem_keluar_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 134 | tbitem_masuk_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 135 | tbitem_masuk_detail | no_item | tblitem | noitem | Logis (kode) |
| 136 | tbitem_masuk_detail | no_transaksi | tbitem_masuk_header | no_transaksi | Logis (kode) |
| 137 | tbitem_masuk_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 138 | tbitem_validation_log | noitem | tblitem | noitem | Logis (kode) |
| 139 | tbjabatan | kode_divisi | tbdivisi | kode_divisi | Logis (kode) |
| 140 | tbkeluhan_proses | kode_item | tblitem | noitem | Logis (kode) |
| 141 | tbkeluhan_proses | kode_keluhan | tbmaster_keluhan | kode_keluhan | Declared FK |
| 142 | tbkoreksi_stok_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 143 | tbkoreksi_stok_detail | no_item | tblitem | noitem | Logis (kode) |
| 144 | tbkoreksi_stok_detail | no_transaksi | tbkoreksi_stok_header | no_transaksi | Logis (kode) |
| 145 | tbkoreksi_stok_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 146 | tbl_issue | id_jenis_masalah | master_jenis_masalah | id_jenis | Declared FK |
| 147 | tbl_issue | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 148 | tbl_issue | pelapor | tbuser | id | Logis (kode) |
| 149 | tbl_issue | pic | tbuser | id | Logis (kode) |
| 150 | tbl_issue | ref_no_polisi | tblkendaraan | nopolisi | Logis (kode) |
| 151 | tbl_issue | ref_nopelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 152 | tbl_issue_progress_log | id_issue | tbl_issue | id_issue | Logis (kode) |
| 153 | tbl_kepala_mekanik_harian | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 154 | tbl_setting_antarcabang | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 155 | tbl_setting_antarcabang | tipe_cabang_tujuan | tbcabang_tipe | id | Logis (kode) |
| 156 | tbldelivery_order_detail | no_do | tbldelivery_order_header | no_do | Declared FK |
| 157 | tbldelivery_order_detail | no_item | tblitem | noitem | Logis (kode) |
| 158 | tbldelivery_order_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 159 | tbldelivery_order_header | no_po | tblorder_header | no_order | Declared FK |
| 160 | tbldelivery_order_header | no_supplier | tblsupplier | nosupplier | Logis (kode) |
| 161 | tbldo_tracking | no_do | tbldelivery_order_header | no_do | Declared FK |
| 162 | tblhutang_detail | no_pembelian | tblpembelian_header | notransaksi | Logis (kode) |
| 163 | tblhutang_detail | no_transaksi | tblhutang_header | no_transaksi | Logis (kode) |
| 164 | tblhutang_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 165 | tblhutang_header | no_supplier | tblsupplier | nosupplier | Logis (kode) |
| 166 | tblitem | jenis | tblitemjenis | jenis | Logis (kode) |
| 167 | tblitem | kategori_rak | tbkategori_rak | kode | Logis (kode) |
| 168 | tblitem | kd_pabrik | tbpabrik_barang | id | Logis (kode) |
| 169 | tblitem | satuan | tblitemsatuan | satuan | Logis (kode) |
| 170 | tblitem | supplier | tblsupplier | nosupplier | Logis (kode) |
| 171 | tblitem | supplier2 | tblsupplier | nosupplier | Logis (kode) |
| 172 | tblitem | supplier3 | tblsupplier | nosupplier | Logis (kode) |
| 173 | tblitem_harga_cabang | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 174 | tblitem_harga_cabang | noitem | tblitem | noitem | Logis (kode) |
| 175 | tblitem_jasa | noitem | tblitem | noitem | Logis (kode) |
| 176 | tblitem_koreksi_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 177 | tblitem_koreksi_detail | no_item | tblitem | noitem | Logis (kode) |
| 178 | tblitem_koreksi_detail | no_transaksi | tblitem_koreksi_header | no_transaksi | Logis (kode) |
| 179 | tblitem_koreksi_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 180 | tblitem_minmax | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 181 | tblitem_minmax | no_item | tblitem | noitem | Logis (kode) |
| 182 | tblitem_minmax | supplier1 | tblsupplier | nosupplier | Logis (kode) |
| 183 | tblitem_minmax | supplier2 | tblsupplier | nosupplier | Logis (kode) |
| 184 | tblitem_mutasi_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 185 | tblitem_mutasi_detail | no_item | tblitem | noitem | Logis (kode) |
| 186 | tblitem_mutasi_detail | no_transaksi | tblitem_mutasi_header | no_transaksi | Logis (kode) |
| 187 | tblitem_mutasi_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 188 | tblitem_spart | noitem | tblitem | noitem | Logis (kode) |
| 189 | tblitem_stok | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 190 | tblitem_stok | noitem | tblitem | noitem | Logis (kode) |
| 191 | tblkas_keluar_masuk | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 192 | tblkeluhan | kode_keluhan | tbmaster_keluhan | kode_keluhan | Logis (kode) |
| 193 | tblkendaraan | kode_jenis | tbjenis_motor | kd | Logis (kode) |
| 194 | tblkendaraan | kode_merek | tbpabrik_motor | id | Logis (kode) |
| 195 | tblkendaraan | kode_tipe | tbtipe_motor | kode_tipe | Logis (kode) |
| 196 | tblkendaraan | kode_warna | tbwarna | id | Logis (kode) |
| 197 | tblkomplain | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 198 | tblkomplain | kode_kategori | tblkomplain_kategori | kode_kategori | Declared FK |
| 199 | tblkomplain | mekanik_pelaksana_kode | tbuser | kode_karyawan | Logis (kode) |
| 200 | tblkomplain | no_service_asli | tblservice | no_service | Logis (kode) |
| 201 | tblkomplain | no_service_rework | tblservice | no_service | Logis (kode) |
| 202 | tblkomplain | nopol | tblkendaraan | nopolisi | Logis (kode) |
| 203 | tblkomplain | pic_kode_karyawan | tbuser | kode_karyawan | Logis (kode) |
| 204 | tblkomplain_export_log | kode_karyawan | tbuser | kode_karyawan | Logis (kode) |
| 205 | tblkomplain_log | komplain_id | tblkomplain | id | Declared FK |
| 206 | tblorder_antarcab_detail | no_item | tblitem | noitem | Logis (kode) |
| 207 | tblorder_antarcab_detail | no_order | tblorder_antarcab_header | no_order | Logis (kode) |
| 208 | tblorder_antarcab_header | kd_cabang_asal | tbcabang | kode_cabang | Logis (kode) |
| 209 | tblorder_antarcab_header | kd_cabang_tujuan | tbcabang | kode_cabang | Logis (kode) |
| 210 | tblorder_antarcab_header | no_pembelian | tblpembelian_header | notransaksi | Logis (kode) |
| 211 | tblorder_antarcab_header | no_penjualan | tblpenjualan_header | notransaksi | Logis (kode) |
| 212 | tblorder_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 213 | tblorder_detail | no_item | tblitem | noitem | Logis (kode) |
| 214 | tblorder_detail | no_order | tblorder_header | no_order | Logis (kode) |
| 215 | tblorder_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 216 | tblorder_header | no_pr | tblpurchase_request_header | no_pr | Logis (kode) |
| 217 | tblorder_header | no_rfq | tblrfq_header | no_rfq | Logis (kode) |
| 218 | tblorder_header | no_supplier | tblsupplier | nosupplier | Logis (kode) |
| 219 | tblorderjual_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 220 | tblorderjual_detail | no_item | tblitem | noitem | Logis (kode) |
| 221 | tblorderjual_detail | no_order | tblorderjual_header | no_order | Logis (kode) |
| 222 | tblorderjual_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 223 | tblorderjual_header | no_order | tblorder_header | no_order | Logis (kode) |
| 224 | tblorderjual_header | no_pelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 225 | tblorderjual_header | no_sales | tblsales | nosales | Logis (kode) |
| 226 | tblpelanggan | jenis_id | tbjenis_motor | kd | Declared FK |
| 227 | tblpelanggan | kgrup | tblpelanggangrup | kgrup | Logis (kode) |
| 228 | tblpelanggan | merek_id | tbpabrik_motor | id | Declared FK |
| 229 | tblpelanggan | tipe_id | tbtipe_motor | kode_tipe | Declared FK |
| 230 | tblpelanggan | warna_id | tbwarna | id | Declared FK |
| 231 | tblpembelian_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 232 | tblpembelian_detail | no_item | tblitem | noitem | Logis (kode) |
| 233 | tblpembelian_detail | no_transaksi | tblpembelian_header | notransaksi | Logis (kode) |
| 234 | tblpembelian_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 235 | tblpembelian_header | no_do | tbldelivery_order_header | no_do | Logis (kode) |
| 236 | tblpembelian_header | no_order | tblorder_header | no_order | Logis (kode) |
| 237 | tblpembelian_header | no_pr | tblpurchase_request_header | no_pr | Logis (kode) |
| 238 | tblpembelian_header | no_supplier | tblsupplier | nosupplier | Logis (kode) |
| 239 | tblpenjualan_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 240 | tblpenjualan_detail | no_item | tblitem | noitem | Logis (kode) |
| 241 | tblpenjualan_detail | no_transaksi | tblpenjualan_header | notransaksi | Logis (kode) |
| 242 | tblpenjualan_harian | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 243 | tblpenjualan_harian | no_item | tblitem | noitem | Logis (kode) |
| 244 | tblpenjualan_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 245 | tblpenjualan_header | no_order | tblorderjual_header | no_order | Logis (kode) |
| 246 | tblpenjualan_header | no_pelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 247 | tblpenjualan_header | no_sales | tblsales | nosales | Logis (kode) |
| 248 | tblpenjualan_mingguan | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 249 | tblpenjualan_mingguan | no_item | tblitem | noitem | Logis (kode) |
| 250 | tblpiutang_detail | no_penjualan | tblpenjualan_header | notransaksi | Logis (kode) |
| 251 | tblpiutang_detail | no_transaksi | tblpiutang_header | no_transaksi | Logis (kode) |
| 252 | tblpiutang_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 253 | tblpiutang_header | no_pelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 254 | tblpo_approval_log | no_po | tblorder_header | no_order | Declared FK |
| 255 | tblpr_approval_log | no_pr | tblpurchase_request_header | no_pr | Declared FK |
| 256 | tblpr_tracking | no_pr | tblpurchase_request_header | no_pr | Logis (kode) |
| 257 | tblpurchase_request_detail | no_item | tblitem | noitem | Logis (kode) |
| 258 | tblpurchase_request_detail | no_pr | tblpurchase_request_header | no_pr | Declared FK |
| 259 | tblpurchase_request_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 260 | tblretur_pembelian_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 261 | tblretur_pembelian_detail | no_item | tblitem | noitem | Logis (kode) |
| 262 | tblretur_pembelian_detail | no_retur | tblretur_pembelian_header | noretur | Logis (kode) |
| 263 | tblretur_pembelian_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 264 | tblretur_pembelian_header | nopembelian | tblpembelian_header | notransaksi | Logis (kode) |
| 265 | tblretur_penjualan_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 266 | tblretur_penjualan_detail | no_item | tblitem | noitem | Logis (kode) |
| 267 | tblretur_penjualan_detail | no_retur | tblretur_penjualan_header | noretur | Logis (kode) |
| 268 | tblretur_penjualan_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 269 | tblretur_penjualan_header | nopembelian | tblpenjualan_header | notransaksi | Logis (kode) |
| 270 | tblretur_servis_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 271 | tblretur_servis_detail | no_item | tblitem | noitem | Logis (kode) |
| 272 | tblretur_servis_detail | no_retur | tblretur_servis_header | noretur | Logis (kode) |
| 273 | tblretur_servis_detail | no_service | tblservice | no_service | Logis (kode) |
| 274 | tblretur_servis_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 275 | tblretur_servis_header | no_service | tblservice | no_service | Logis (kode) |
| 276 | tblrfq_detail | no_item | tblitem | noitem | Logis (kode) |
| 277 | tblrfq_detail | no_rfq | tblrfq_header | no_rfq | Declared FK |
| 278 | tblrfq_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 279 | tblrfq_header | no_pr | tblpurchase_request_header | no_pr | Declared FK |
| 280 | tblrfq_supplier_response | no_item | tblitem | noitem | Logis (kode) |
| 281 | tblrfq_supplier_response | no_rfq | tblrfq_header | no_rfq | Declared FK |
| 282 | tblrfq_supplier_response | no_supplier | tblsupplier | nosupplier | Logis (kode) |
| 283 | tblservice | id_user | tbuser | id | Logis (kode) |
| 284 | tblservice | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 285 | tblservice | kepala_mekanik1 | tbuser | kode_karyawan | Logis (kode) |
| 286 | tblservice | mekanik1 | tbuser | kode_karyawan | Logis (kode) |
| 287 | tblservice | no_pelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 288 | tblservice | no_polisi | tblkendaraan | nopolisi | Logis (kode) |
| 289 | tblservice | ref_no_service_original | tblservice | no_service | Logis (kode) |
| 290 | tblservice_advisor | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 291 | tblservice_advisor | no_service | tblservice | no_service | Logis (kode) |
| 292 | tblservice_jemputantar | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 293 | tblservice_jemputantar | no_pelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 294 | tblservice_jemputantar | no_polisi | tblkendaraan | nopolisi | Logis (kode) |
| 295 | tblservice_jemputantar | no_service | tblservice | no_service | Logis (kode) |
| 296 | tblservis_barang | id_promo | master_diskon_periode | id_promo | Logis (kode) |
| 297 | tblservis_barang | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 298 | tblservis_barang | no_item | tblitem | noitem | Logis (kode) |
| 299 | tblservis_barang | no_service | tblservice | no_service | Logis (kode) |
| 300 | tblservis_jasa | id_promo | master_diskon_periode | id_promo | Logis (kode) |
| 301 | tblservis_jasa | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 302 | tblservis_jasa | no_item | tblitem | noitem | Logis (kode) |
| 303 | tblservis_jasa | no_service | tblservice | no_service | Logis (kode) |
| 304 | tblsupplier | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 305 | tblsupplier_spart | nosupplier | tblsupplier | nosupplier | Logis (kode) |
| 306 | tbmaster_barang_custom | kode_barang | tblitem | noitem | Logis (kode) |
| 307 | tbmaster_barang_fastmoves | kode_barang | tblitem | noitem | Logis (kode) |
| 308 | tbmaster_barang_fastmoves | kode_kategori | tbmaster_kategori_fastmoves | kode_kategori | Declared FK |
| 309 | tbmaster_barang_nonitem | supplier | tblsupplier | nosupplier | Logis (kode) |
| 310 | tbmaster_keluhan | workorder_default | tbworkorderheader | kode_wo | Logis (kode) |
| 311 | tbmaster_keluhan_temuan | kode_keluhan | tbmaster_keluhan | kode_keluhan | Logis (kode) |
| 312 | tbmaster_keluhan_temuan | kode_temuan | tbmaster_temuan | kode_temuan | Logis (kode) |
| 313 | tbmaster_keluhan_workorder | kode_keluhan | tbmaster_keluhan | kode_keluhan | Logis (kode) |
| 314 | tbmaster_keluhan_workorder | kode_workorder | tbworkorderheader | kode_wo | Logis (kode) |
| 315 | tbmaster_temuan_barang_mapping | kode_temuan | tbmaster_temuan | kode_temuan | Logis (kode) |
| 316 | tbmaster_temuan_barang_mapping | noitem | tblitem | noitem | Logis (kode) |
| 317 | tbmaster_tipe_detail | id_tipe_header | tbmaster_tipe_header | id | Logis (kode) |
| 318 | tbmaster_tipe_header | id_brand | tbpabrik_motor | id | Declared FK |
| 319 | tbpegawai | kode_divisi | tbdivisi | kode_divisi | Logis (kode) |
| 320 | tbpegawai | kode_jabatan | tbjabatan | kode_jabatan | Logis (kode) |
| 321 | tbpegawai | kode_perusahaan | tbperusahaan | kode_perusahaan | Logis (kode) |
| 322 | tbpegawai_salary | nip | tbpegawai | nip | Logis (kode) |
| 323 | tbsaldo_hutang | nosupplier | tblsupplier | nosupplier | Logis (kode) |
| 324 | tbsaldo_piutang | nopelanggan | tblpelanggan | nopelanggan | Logis (kode) |
| 325 | tbservis_keluhan | no_service | tblservice | no_service | Logis (kode) |
| 326 | tbservis_keluhan_status | kode_keluhan | tbmaster_keluhan | kode_keluhan | Logis (kode) |
| 327 | tbservis_keluhan_status | no_service | tblservice | no_service | Logis (kode) |
| 328 | tbservis_keluhan_tracking | keluhan_id | tbservis_keluhan_status | id | Declared FK |
| 329 | tbservis_keluhan_tracking | no_service | tblservice | no_service | Logis (kode) |
| 330 | tbservis_keluhan_tracking | proses_id | tbkeluhan_proses | id | Declared FK |
| 331 | tbservis_penawaran_jasa | no_service | tblservice | no_service | Logis (kode) |
| 332 | tbservis_penawaran_jasa | temuan_id | tbservis_temuan | id | Logis (kode) |
| 333 | tbservis_penawaran_log | no_service | tblservice | no_service | Logis (kode) |
| 334 | tbservis_penawaran_log | penawaran_id | tbservis_penawaran_part | id | Declared FK |
| 335 | tbservis_penawaran_part | kode_barang | tblitem | noitem | Logis (kode) |
| 336 | tbservis_penawaran_part | no_service | tblservice | no_service | Logis (kode) |
| 337 | tbservis_penawaran_part | temuan_id | tbservis_temuan | id | Declared FK |
| 338 | tbservis_penawaran_rejection_summary | kode_barang | tblitem | noitem | Logis (kode) |
| 339 | tbservis_pending_items | kode_item | tblitem | noitem | Logis (kode) |
| 340 | tbservis_pending_items | no_service | tblservice | no_service | Logis (kode) |
| 341 | tbservis_pending_items | wo_id | tbservis_workorder | id | Logis (kode) |
| 342 | tbservis_pengerjaan | no_service | tblservice | no_service | Logis (kode) |
| 343 | tbservis_temuan | keluhan_id | tbservis_keluhan_status | id | Declared FK |
| 344 | tbservis_temuan | kode_temuan | tbmaster_temuan | kode_temuan | Logis (kode) |
| 345 | tbservis_temuan | no_service | tblservice | no_service | Logis (kode) |
| 346 | tbservis_temuan_activity_log | no_service | tblservice | no_service | Logis (kode) |
| 347 | tbservis_temuan_activity_log | temuan_id | tbservis_temuan | id | Logis (kode) |
| 348 | tbservis_workorder | kode_wo | tbworkorderheader | kode_wo | Logis (kode) |
| 349 | tbservis_workorder | no_service | tblservice | no_service | Logis (kode) |
| 350 | tbso_detail | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 351 | tbso_detail | no_item | tblitem | noitem | Logis (kode) |
| 352 | tbso_detail | no_transaksi | tbso_header | no_transaksi | Logis (kode) |
| 353 | tbso_header | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 354 | tbstok | kd_cabang | tbcabang | kode_cabang | Logis (kode) |
| 355 | tbstok | no_item | tblitem | noitem | Logis (kode) |
| 356 | tbuser | id_karyawan | tbuser_karyawan | id | Declared FK |
| 357 | tbuser | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 358 | tbuser | kode_level | tb_master_level | kode_level | Logis (kode) |
| 359 | tbuser | kode_posisi | tb_master_posisi | kode_posisi | Logis (kode) |
| 360 | tbuser_karyawan | kode_cabang | tbcabang | kode_cabang | Logis (kode) |
| 361 | tbuser_karyawan | kode_jabatan | tbjabatan | kode_jabatan | Logis (kode) |
| 362 | tbuser_karyawan | kode_level | tb_master_level | kode_level | Declared FK |
| 363 | tbuser_karyawan | kode_posisi | tb_master_posisi | kode_posisi | Declared FK |
| 364 | tbworkorder_jenis_motor | kd_kategori_motor | tbkategori_motor | id | Logis (kode) |
| 365 | tbworkorder_jenis_motor | kode_wo | tbworkorderheader | kode_wo | Logis (kode) |
| 366 | tbworkorderdetail | kode_barang | tblitem | noitem | Logis (kode) |
| 367 | tbworkorderdetail | kode_wo | tbworkorderheader | kode_wo | Logis (kode) |

---

## Tabel Tanpa PRIMARY KEY di Database

Tabel berikut tidak punya PRIMARY KEY; kunci logis (bila ada) ditandai † di atas. Tabel tanpa kunci logis apa pun (ref/staging) bergantung pada kolom kode.

`gabung_rekap_konsumen_wa`, `tbabsensi_temp`, `tbabsensi_temp_rst`, `tbakun`, `tbakun_pos`, `tbdarah`, `tbjenis_bayar`, `tblitem_jasa`, `tblitem_stok`, `tblkas_keluar_masuk`, `tbsaldo_hutang`, `tbsaldo_piutang`, `tbtipe_potongan`


## Tabel Backup / Tidak Aktif (tidak dimodelkan)

`_backup_tbl_kepala_mekanik_harian_20251115`, `_backup_tbl_master_kepala_mekanik_20251115`, `_backup_tblmekanik_20251115`, `master_tarif_jemput_backup`, `tblitem_backup_categories`, `tblkendaraan_backup_20260622`, `tblkendaraan_backup_20260720`, `tblservice_backup`


## View Database (62)

`master_tarif_jemput`, `tbl_master_kepala_mekanik`, `tblmekanik`, `v_do_status`, `v_po_status`, `v_pr_status`, `v_promo_target_legacy`, `view_absensi`, `view_cari_item`, `view_cari_kendaraan`, `view_cari_pelanggan`, `view_do_tracking_latest`, `view_item_classified`, `view_keluhan_workorder`, `view_laporan_cancel_servis`, `view_master_kategori_member`, `view_master_keluhan`, `view_mekanik_users`, `view_pelanggan_follow_up`, `view_pelanggan_kendaraan`, `view_pemasukan_combined_closing_kasir`, `view_pemasukan_kasir_closing_kasir`, `view_pemasukan_pusat_closing_kasir`, `view_pemasukan_with_closing_closing_kasir`, `view_pembayaran_hutang`, `view_pembayaran_piutang`, `view_pembelian_detail`, `view_pembelian_header`, `view_penawaran_part_lengkap`, `view_pengeluaran_kasir_closing_kasir`, `view_pengeluaran_pusat_closing_kasir`, `view_penjualan_detail`, `view_penjualan_header`, `view_pesanan_pembelian_detail`, `view_pesanan_pembelian_header`, `view_pesanan_penjualan_h`, `view_po_with_pr`, `view_pr_complete`, `view_retur_pembelian`, `view_retur_penjualan`, `view_ringkasan_kedatangan_pelanggan`, `view_riwayat_kedatangan_detail`, `view_service`, `view_servis_keluhan_lengkap`, `view_servis_status_summary`, `view_servis_temuan_lengkap`, `view_servis_workorder_lengkap`, `view_setoran_with_closing_closing_kasir`, `view_statistik_pelanggan`, `view_stok`, `view_stok_master`, `view_suggested_parts`, `view_tarif_jemput`, `view_temuan_penawaran_summary`, `view_top_pelanggan`, `view_transaksi_ada_selisih_closing_kasir`, `view_transaksi_dikembalikan_cs_closing_kasir`, `view_transaksi_gabungan`, `view_transaksi_perlu_validasi_closing_kasir`, `view_user_details`, `view_wo_harga`, `view_workorder_complete`