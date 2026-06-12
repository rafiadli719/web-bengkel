# DOKUMENTASI VIEW DATABASE

## INFORMASI UMUM
- **File SQL**: create_all_views.sql
- **Tanggal Dibuat**: 2025-11-28
- **Database**: fitmotor_dbbengkel
- **Tujuan**: Membuat ulang semua VIEW yang hilang/rusak dari struktur tabel stand-in

## DAFTAR VIEW YANG DIBUAT

### 1. master_tarif_jemput
**Tabel Sumber**: `master_tarif_jemput_range`
**Fungsi**: Menampilkan tarif jemput motor berdasarkan jenis motor dan jarak
**Kolom**:
- id, jenis_motor, jarak_km, tarif, keterangan, created_at, updated_at

### 2. tblmekanik
**Tabel Sumber**: `tbuser_karyawan`
**Fungsi**: Menampilkan data mekanik dari karyawan dengan posisi mekanik
**Kolom**:
- nomekanik, nama, alamat, telp, keahlian, status, email, tanggal_masuk, gaji_pokok, spesialisasi, sertifikat, created_at, updated_at
**Filter**: Hanya karyawan dengan posisi MEKANIK atau KEPALA_MEKANIK

### 3. tbl_master_kepala_mekanik
**Tabel Sumber**: `tbuser_karyawan`
**Fungsi**: Menampilkan data kepala mekanik per cabang
**Kolom**:
- id, kode_cabang, nama_kepala_mekanik, nip_karyawan, no_telepon, tanggal_mulai, tanggal_selesai, status_aktif, created_by, created_at, updated_at
**Filter**: Hanya karyawan dengan posisi atau level KEPALA_MEKANIK

### 4. view_absensi
**Tabel Sumber**: `tbabsensi`
**Fungsi**: Menampilkan data absensi dengan format extended (terpisah tanggal, bulan, tahun)
**Kolom**:
- nip, tgl, tanggal, bulan, tahun, jam_masuk, jam_keluar, jam_absensi, kode_status_kehadiran, keterangan, id, kode_perusahaan, kode_lokasi

### 5. view_cari_item
**Tabel Sumber**: `tblitem`, `tblitemjenis`
**Fungsi**: View untuk pencarian item/barang dengan detail lengkap
**Kolom**:
- noitem, namaitem, nama_part_resmi, kodebarcode, merek, tipe_item, status_validasi, kategori_rak, hargapokok, hargajual, statusitem, satuan, stokmin, rakbarang, namajenis, keterangan, created_at, updated_at
**Filter**: Hanya item dengan statusitem = '1' (aktif)

### 6. view_cari_kendaraan
**Tabel Sumber**: `tblkendaraan`, `master_merek`
**Fungsi**: View untuk pencarian kendaraan dengan detail lengkap termasuk merek
**Kolom**:
- nopolisi, pemilik, alamat, kode_merek, tipe, kode_tipe, jenis, kode_jenis, tahun_buat, tahun_rakit, silinder, warna, kode_warna, no_rangka, no_mesin, note, merek

### 7. view_cari_pelanggan
**Tabel Sumber**: `tblpelanggan`, `tblpelanggangrup`
**Fungsi**: View untuk pencarian pelanggan dengan informasi grup
**Kolom**:
- nopelanggan, namapelanggan, alamat, kota, propinsi, kodepost, negara, telephone, fax, kontakperson, note, potongan, tipepot, lavelharga, kgrup, patokan, klat, klong, panggilan, grup

### 8. view_keluhan_workorder
**Tabel Sumber**: `tbmaster_keluhan_workorder`, `tbmaster_keluhan`, `tbworkorderheader`
**Fungsi**: Mapping keluhan dengan workorder yang tersedia
**Kolom**:
- id, kode_keluhan, nama_keluhan, kode_workorder, nama_wo, deskripsi_wo, harga_wo, estimasi_waktu, prioritas, status_aktif, created_at, updated_at
**Filter**: Hanya mapping dengan status_aktif = '1'

### 9. view_master_keluhan
**Tabel Sumber**: `tbmaster_keluhan`
**Fungsi**: Master data keluhan yang aktif
**Kolom**:
- id, kode_keluhan, nama_keluhan, deskripsi, kategori, status_aktif, created_at, updated_at
**Filter**: Hanya keluhan dengan status_aktif = '1'

### 10. view_pelanggan_kendaraan
**Tabel Sumber**: `tblkendaraan`, `master_merek`, `tblpelanggan`
**Fungsi**: View gabungan pelanggan dengan kendaraan yang dimiliki
**Kolom**:
- nopolisi, pemilik, alamat, kode_merek, tipe, kode_tipe, jenis, kode_jenis, tahun_buat, tahun_rakit, silinder, warna, kode_warna, no_rangka, no_mesin, note, merek, telephone

### 11. view_servis_keluhan_lengkap
**Tabel Sumber**: `tbservis_keluhan_status`, `tblservis`, `tblpelanggan`, `tblkendaraan`, `tbworkorderheader`
**Fungsi**: View lengkap untuk servis dengan detail keluhan, pelanggan, dan kendaraan
**Kolom**:
- id, no_service, keluhan, status_pengerjaan, keterangan_tidak_selesai, auto_workorder, workorder_applied, created_at, updated_at, tanggal_service, jam_service, no_pelanggan, no_polisi, status_servis, namapelanggan, hp_pelanggan, hp_pelanggan2, alamat_pelanggan, kode_merek, tipe_kendaraan, jenis_kendaraan, tahun_buat, nama_workorder, jumlah_temuan, status_badge_color
**Fitur Khusus**:
- Menghitung jumlah temuan dari `tbservis_temuan_penawaran`
- Warna badge berdasarkan status pengerjaan

### 12. view_servis_workorder_lengkap
**Tabel Sumber**: `tbservis_workorder`, `tbworkorderheader`, `tblservis`, `tblpelanggan`, `tblkendaraan`
**Fungsi**: View lengkap untuk servis dengan detail workorder
**Kolom**:
- id, no_service, kode_wo, status_pengerjaan, keterangan_tidak_selesai, created_at, updated_at, nama_wo, deskripsi_wo, harga_wo, estimasi_waktu, status_wo, tanggal_service, jam_service, no_pelanggan, no_polisi, status_servis, namapelanggan, hp_pelanggan, hp_pelanggan2, alamat_pelanggan, kode_merek, tipe_kendaraan, jenis_kendaraan, tahun_buat, jumlah_item, jumlah_jasa, jumlah_barang, status_badge_color, progress_percentage
**Fitur Khusus**:
- Menghitung jumlah item, jasa, dan barang dari `tbservis_workorder_items`
- Warna badge dan persentase progress berdasarkan status pengerjaan

### 13. view_stok
**Tabel Sumber**: `tbstok`, `tblitem`
**Fungsi**: View untuk transaksi stok (masuk/keluar)
**Kolom**:
- tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, id, tgl_trx, namaitem, kd_cabang
**Fitur Khusus**: Format tanggal DD-MM-YYYY untuk tgl_trx

### 14. view_stok_master
**Tabel Sumber**: `tbstok`
**Fungsi**: View untuk menampilkan saldo stok per cabang per item
**Kolom**:
- kd_cabang, no_item, saldo
**Fitur Khusus**: Agregasi SUM(masuk - keluar) untuk menghitung saldo

### 15. v_po_status
**Tabel Sumber**: `tblorder_header`, `tblorder_detail`, `tbldelivery_order_header`, `tbldelivery_order_detail`
**Fungsi**: Status Purchase Order dengan detail penerimaan barang
**Kolom**:
- no_order, no_pr, no_supplier, tanggal, kd_cabang, status, status_approval, approved_by, approved_date, total_items, total_qty_po, total_qty_terima, persen_terima
**Fitur Khusus**:
- Menghitung total item yang berbeda
- Menghitung total qty PO dan qty yang diterima
- Menghitung persentase penerimaan

### 16. v_pr_status
**Tabel Sumber**: BELUM ADA
**Fungsi**: Status Purchase Request (placeholder)
**Status**: VIEW KOSONG - Akan diisi ketika tabel purchase_request sudah dibuat
**Note**: Saat ini mengembalikan hasil kosong dengan WHERE 1=0

## CARA MENGGUNAKAN

### 1. Eksekusi File SQL
```bash
mysql -u root -p fitmotor_dbbengkel < create_all_views.sql
```

### 2. Atau melalui phpMyAdmin
1. Buka phpMyAdmin
2. Pilih database `fitmotor_dbbengkel`
3. Klik tab "SQL"
4. Copy-paste isi file `create_all_views.sql`
5. Klik "Go" atau "Kirim"

### 3. Verifikasi VIEW
```sql
-- Lihat semua VIEW yang ada
SHOW FULL TABLES WHERE table_type = 'VIEW';

-- Test salah satu VIEW
SELECT * FROM master_tarif_jemput LIMIT 5;
SELECT * FROM view_cari_item LIMIT 10;
```

## CATATAN PENTING

### Perubahan dari Struktur Asli:
1. **master_merek**: Diasumsikan bernama `master_merek` bukan `tbmerek`
2. **tbstok**: Tabel stok menggunakan nama `tbstok` bukan `tblstokmasuk`
3. **tbworkorderheader**: Tabel workorder menggunakan struktur lama dengan kolom:
   - `keterangan` (bukan `deskripsi_wo`)
   - `harga` (bukan `harga_wo`)
   - `waktu` (bukan `estimasi_waktu`)
   - `status` (bukan `status_wo`)

### Tabel yang Mungkin Perlu Disesuaikan:
Jika ada error saat eksekusi VIEW, periksa nama tabel berikut:
- `master_merek` → Mungkin bernama `tbmerek` atau `tblmerek`
- `tbservis_keluhan_status` → Pastikan tabel ini ada, bukan `tbservis_keluhan`
- `tbservis_workorder_items` → Pastikan tabel ini ada untuk menghitung jumlah item
- `tbservis_temuan_penawaran` → Pastikan tabel ini ada untuk menghitung temuan

### VIEW yang Perlu Update di Masa Depan:
1. **v_pr_status**: Perlu diupdate ketika tabel Purchase Request dibuat
2. **view_keluhan_workorder**: Jika struktur `tbworkorderheader` berubah
3. **view_servis_workorder_lengkap**: Jika tabel `tbservis_workorder_items` tidak ada

## TROUBLESHOOTING

### Error: Unknown table 'master_merek'
**Solusi**: Ganti `master_merek` dengan nama tabel merek yang sebenarnya
```sql
-- Cek nama tabel merek
SHOW TABLES LIKE '%merek%';
```

### Error: Unknown column
**Solusi**: Sesuaikan nama kolom dengan struktur tabel yang sebenarnya
```sql
-- Cek struktur tabel
DESC nama_tabel;
```

### Error: VIEW sudah ada
**Solusi**: File SQL sudah menyertakan DROP VIEW IF EXISTS, tapi jika masih error:
```sql
-- Hapus VIEW secara manual
DROP VIEW IF EXISTS nama_view;
```

## BACKUP

Sebelum menjalankan script, sebaiknya backup database:
```bash
mysqldump -u root -p fitmotor_dbbengkel > backup_before_views_$(date +%Y%m%d).sql
```

## KONTAK & SUPPORT
Jika ada pertanyaan atau masalah, dokumentasikan error yang muncul beserta:
1. Pesan error lengkap
2. Nama VIEW yang bermasalah
3. Struktur tabel terkait (DESC nama_tabel)
