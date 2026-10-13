# Gap Kebutuhan "Sistem Informasi Bengkel FIT MOTOR" vs FM Web Base

Tanggal: 2026-10-13
Sumber: `docs/summary/SISTEM INFORMASI BENGKEL FIT MOTOR.docx` dan `.md` (kebutuhan awal dari manajemen).
Status dokumen: HASIL ANALISA + PLANNING. Belum ada kode yang diubah.

## Metode dan batas keyakinan

Setiap butir kebutuhan dicocokkan ke `app/menu_config.php`, halaman terkait, dan pencarian kode.
Label yang dipakai:

- **ADA**: halaman dan logikanya ditemukan di kode.
- **SEBAGIAN**: ada, tetapi aturan atau kolom dari docx tidak lengkap.
- **BELUM**: tidak ditemukan di kode atau menu.
- **VERIFIKASI**: kode ada atau tidak terbukti, perlu dicek di halaman sebelum dikerjakan.
- **KEPUTUSAN**: konflik dengan keputusan lain, butuh keputusan Rafi.

Temuan "BELUM" berbasis pencarian kode, bukan uji browser. Sebelum dikerjakan, tiap butir dicek ulang
di halaman yang bersangkutan.

Koreksi atas analisa awal sesi ini: Upload Excel penjualan antar cabang (`penjualan_antarcab_upload.php`),
Laporan Hutang detail dan per supplier, dan penawaran barang custom ternyata SUDAH ada.

## 1. Sudah terimplementasi (tidak perlu dikerjakan)

| Butir docx | Bukti |
|---|---|
| Login memilih cabang | `index.php` (`cbocabang`) |
| Master lengkap: kategori/satuan/pabrik/rak barang, margin, status harga, work order, jasa, pelanggan, kategori pelanggan, supplier, cabang, tipe cabang, mekanik, level mekanik, tipe/pabrik/kategori motor, kendaraan, warna, propinsi/kota/kecamatan/desa, akun biaya, sales, user, Harga Jual Plus Jasa | `menu_config.php` Data Master |
| Pelanggan ditolak jika WA sudah ada, kendaraan ditolak jika nopol sudah ada | `save_pelanggan.php`, `save_kendaraan.php` |
| Kolom Panggilan, Patokan, Latitude/Longitude terpisah | `pelanggan_add.php` |
| Kolom item: barcode, keterangan, supplier 3, area, pabrik, applicable part, PK/PS/PB/PC | `barang_add.php`, `barang_edit.php` |
| Kartu stok dan history harga pokok per item | `barang_kartu_stok.php`, `barang_history_hp.php` |
| Pesanan pembelian, pembelian, pembayaran hutang, No. Faktur / Tgl Faktur | `menu_config.php`, `pembelian_add.php` |
| Pelunasan hutang banyak faktur sekaligus (centang) | `pmby_hutang.php`, `pmby_hutang_rst.php` |
| Laporan hutang detail per faktur dan total per supplier | `laporan_hutang_detail.php`, `laporan_hutang_summary.php` |
| Penjualan antar cabang: upload Excel, setting margin dan tempo, penerimaan nota belum diterima | `penjualan_antarcab_upload.php`, `setting_antarcabang.php`, `penerimaan_antarcab.php` |
| Penyesuaian stok manual masuk/keluar dan otomatis (acuan transaksi pada rentang tanggal, posting selisih) | `penyesuaian-stok-*.php`, `so-item-*.php` |
| Servis reguler dan garansi, item tidak boleh dobel, kepala mekanik wajib, persentase mekanik total 100%, diskon persen atau rupiah, diskon sesuai tier | `servis-input-reguler.php`, `servis-garansi.php` |
| Work Order/paket, master keluhan, temuan, tracking keluhan, estimasi PDF, barang custom untuk penawaran | `menu_config.php`, `servis-estimasi-pdf.php`, `master-barang-custom.php` |
| Kasir: kas awal, transaksi, closing, lebih dari satu sesi per hari | `_keuangan/kasir/*` |
| Insentif mekanik dan profit | `lap_profit_insentif.php`, `komisi_mekanik` |

## 2. Temuan: belum atau sebagian

Prioritas: **P1** aturan bisnis yang tertulis wajib tetapi belum ditegakkan, **P2** fitur mandatory yang
kurang lengkap, **P3** modul pelengkap.

### P1. Aturan bisnis belum ditegakkan

| ID | Butir docx | Kondisi | Status |
|---|---|---|---|
| G1 | Barang berstatus stok kosong **dan harga naik** tidak bisa diinput ke penjualan dan servis | Hanya `STOK KOSONG` yang diblokir (`penjualan_add_item_cari.php`, `servis-add-item-cari.php`). Blok harga naik dibuang 2026-09-29 karena kolom `status_harga_naik` tidak pernah ada. Data alarm ada di tabel `alarm_harga_beli`. | SEBAGIAN |
| G2 | Master Item (versi buatan sendiri): urut berdasar status harga naik/turun/tetap, perbandingan HPP vs acuan HPP dan harga jual vs hasil perhitungan, update per item atau massal (centang) | List utama (`barang_list_improved.php`) tidak punya kolom status harga, harga jual, acuan HPP, dan tidak ada update harga massal. Menu "Status Harga" hanya master referensi. | BELUM |
| G3 | Penanda baris stok: kuning jika stok <= minimal, merah jika stok = 0, per cabang | `barang_rst.php` menampilkan Stok Min/Max/Akhir sebagai angka tanpa pewarnaan. | BELUM |
| G4 | Cek sisa stok per cabang dan total keseluruhan | List hanya stok cabang login. | BELUM |
| G5 | Kepala Mekanik minimal level MAHIR | Tidak ada validasi level di input kepala mekanik. | BELUM |
| G6 | Sales (customer get customer) tercatat di servis dan menjadi dasar insentif | Master Sales ada (`tblsales`), tetapi tidak ada kolom Sales di form servis maupun di insentif. | BELUM |
| G7 | KM berikut terisi otomatis dari Master Jadwal Penggantian Oli | Tidak ada master jadwal oli. `txtkm_next` diisi manual. | BELUM |
| G8 | Cek duplikasi pelanggan termasuk nopol lama yang sudah tidak aktif | Cek WA dan nopol aktif ada. Nopol lama tidak ditemukan. Ada `kendaraan_pindah_tangan` yang mungkin menutup sebagian. | VERIFIKASI |
| G9 | Hanya Administrator yang boleh tambah/edit master barang dan supplier | Dibatasi lewat RBAC permission, tidak ada pemeriksaan role eksplisit. Perlu dicek matriks permission per posisi. | VERIFIKASI |

### P2. Mandatory kurang lengkap

| ID | Butir docx | Kondisi | Status |
|---|---|---|---|
| G10 | Pencarian pembelian: tanggal, supplier, status pembayaran, tipe supplier (eksternal/antar cabang) | Pencarian generik. Filter status bayar dan tipe supplier tidak ditemukan. | VERIFIKASI |
| G11 | Pembayaran hutang: filter nama supplier, tipe supplier, rentang jatuh tempo | Filter jatuh tempo dan tipe supplier tidak ditemukan. | BELUM |
| G12 | Pencarian master item berdasarkan grup, jenis, dan tipe motor | Ada filter merek, kategori, status validasi, dan applicable motors. Filter tipe motor perlu diverifikasi. | SEBAGIAN |
| G13 | Penyesuaian otomatis: acuan kedua "semua item stok > 0", urut Rak lalu Nama Barang | Hanya acuan transaksi pada rentang tanggal. Query tanpa `ORDER BY` rak dan nama. | SEBAGIAN |
| G14 | Report Item Masuk (selisih lebih) dan Item Keluar (selisih kurang) | `lap_stok_masuk.php` dan `lap_stok_keluar.php` ada. Laporan khusus selisih opname tidak ada. | SEBAGIAN |
| G15 | Master Status Kondisi per jenis sparepart, dipakai di Form Servis | Tidak ada master. Bahan sumber ada di `docs/KATEGORI, DAFTAR PENGECEKAN PART, KONDISI.xlsx`. | BELUM |
| G16 | Form Servis digital (usulan ganti part, status kondisi, daftar catatan untuk yang tidak disetujui, panduan upselling dari barang laku cepat) | Mekanisme temuan dan penawaran ada. Master `Fast Moves` ada. Lembar form terpadu dan status kondisi belum. | SEBAGIAN |
| G17 | Laporan harian, bulanan, tahunan, gabungan semua cabang dan per cabang | Laporan `lap_*` per cabang dengan rentang tanggal. Tidak ada rekap periodik gabungan semua cabang. | SEBAGIAN |
| G18 | HPP servis memakai harga beli termahal dari 4 pembelian terakhir | Tidak diimplementasi. Sistem memakai Moving Average (keputusan resmi 2026-10-05). Konflik dengan docx. | KEPUTUSAN |
| G19 | Login: kolom User combo box atau predictive text | Input teks biasa tanpa saran. | BELUM |

### P3. Modul pelengkap

| ID | Modul | Kondisi |
|---|---|---|
| G20 | Dashboard Manajemen (semua cabang) | `index.php` hanya antrian servis dan progres mekanik. Tidak ada omset, laba, hutang, piutang, atau perbandingan cabang. |
| G21 | Dashboard Cabang | Sama dengan G20, hanya operasional servis. |
| G22 | CRM | Hanya Statistik Pelanggan, Loyalty Member, dan Rekap Kunjungan. Tidak ada follow-up, pengingat servis, atau posisi Staf CRM yang terbukti. |
| G23 | Booking Servis | Tidak ada halaman maupun tabel booking. |
| G24 | Status pengerjaan (progres) servis untuk pelanggan | Hanya tracking internal (`ajax-get-detail-proses.php`). Tampilan untuk pelanggan tidak ada. |
| G25 | Akuntansi keuangan | Hanya kasir, akun kas, dan akun biaya. Jurnal otomatis nonaktif, kode akun COA (D-07) belum diisi. |
| G26 | Pembukaan cabang baru oleh Administrator | Master Cabang ada. Alur pembukaan cabang baru (seed data, user, setting) belum terbukti. |

## 3. Keputusan yang dibutuhkan dari Rafi / manajemen

1. **G18 acuan HPP.** Usulan: Moving Average tetap menjadi HPP akuntansi dan valuasi. "Harga beli termahal dari
   4 pembelian terakhir" dipakai hanya sebagai acuan usulan harga jual dan penentu status harga (G2).
   Docx perlu direvisi agar tidak ambigu.
2. **G1 definisi "harga naik".** Pilihan: (a) baris `alarm_harga_beli` yang belum direview, atau (b) harga beli
   terakhir melebihi harga pokok master sebesar threshold dari `setting-threshold-harga.php`. Perlu juga
   diputuskan apakah blok berlaku keras atau hanya peringatan dengan override approval.
3. **G6 skema insentif Sales.** Nominal, persentase, atau hadiah. Dasar hitung dari nilai servis atau jasa saja.
4. **G7 data jadwal oli.** Interval KM per jenis oli dan per kategori motor (isian dari manajemen).
5. **G15 daftar status kondisi** per jenis sparepart (baik, aus, rusak, dan seterusnya).
6. **P3 urutan pengerjaan** dan siapa pemakai: Dashboard Manajemen, CRM, Booking, Akuntansi.

## 4. Planning pengerjaan (belum dieksekusi)

Setiap fase mengikuti alur proyek: investigasi, uji E2E dengan data dummy, commit, catatan `.ai/changelog`,
update checklist-projek. Semua migrasi harus reversible.

### Fase A. Aturan bisnis penjualan dan master item (G1-G4, G9, G12)
1. Bangun fungsi bersama yang menghitung status harga item (naik/turun/tetap) dari harga pokok master,
   acuan 4 pembelian terakhir, dan margin master. Cache di tabel ringkas agar list tidak lambat.
2. Tambah kolom status harga, harga jual, dan acuan ke list master item. Urut berdasar status harga.
3. Pewarnaan baris stok (kuning, merah) dan kolom stok semua cabang (total).
4. Update harga massal dengan centang, tercatat di log harga (maker-checker bila perlu).
5. Terapkan blok harga naik di `penjualan_add_item_cari.php` dan `servis-add-item-cari.php` sesuai keputusan G1.
6. Verifikasi matriks permission untuk master barang dan supplier (G9).
- **Risiko:** performa list 5.000+ item. Verifikasi: bandingkan waktu muat sebelum dan sesudah.

### Fase B. Servis: sales, mekanik, KM berikut (G5-G8)
1. Kolom Sales di form servis (reguler, garansi, jemput) dan simpan di header servis. Migrasi kolom baru.
2. Validasi level MAHIR untuk kepala mekanik.
3. Tabel master jadwal penggantian oli dan CRUD. KM berikut terisi otomatis dari KM sekarang.
4. Telusuri cek nopol lama di input pelanggan dan servis.
- **Verifikasi:** E2E servis reguler dan garansi dengan data dummy, lalu hapus.

### Fase C. Pembelian, hutang, penyesuaian stok (G10, G11, G13, G14)
1. Filter status pembayaran, tipe supplier, dan rentang jatuh tempo di pembelian dan pembayaran hutang.
2. Acuan "semua item stok > 0" dan urutan Rak lalu Nama Barang di penyesuaian otomatis.
3. Laporan selisih opname lebih dan kurang (rentang tanggal, per cabang, ekspor).
4. Setelah mode `live` Moving Average, pembanding opname memakai saldo ledger `inv_*`.

### Fase D. Form Servis dan status kondisi (G15, G16)
1. Master status kondisi per jenis sparepart dari file xlsx (seed lewat migrasi).
2. Lembar Form Servis terpadu: profil, perintah kerja, keluhan, usulan ganti, status kondisi, estimasi,
   catatan tidak disetujui. Cetak PDF dengan dua area (admin dan kepala mekanik).
3. Daftar barang laku cepat dari Fast Moves sebagai panduan upselling.

### Fase E. Laporan dan dashboard (G17, G20, G21, G19)
1. Rekap periodik harian, bulanan, tahunan: per cabang dan gabungan semua cabang (view agregat).
2. Dashboard Manajemen dan Dashboard Cabang: omset, laba, hutang/piutang, stok kritis, antrian servis.
3. Login: predictive user (datalist).
- **Risiko:** query agregat lintas cabang berat. Pakai tabel ringkasan harian dan job di `scripts/`.

### Fase F. Pelengkap lanjut (G22-G26)
Dikerjakan hanya setelah Fase A-E dan keputusan di bagian 3: Booking servis, status progres untuk pelanggan
(tautan publik bertoken), CRM (pengingat servis dan follow-up), akuntansi (aktivasi jurnal setelah COA),
alur pembukaan cabang baru.

## 5. Urutan yang disarankan

A, lalu B, lalu C, lalu D, lalu E, lalu F. Fase A dan B langsung berdampak ke operasional harian.
G18 dan G1 perlu diputuskan lebih dulu karena menentukan rumus di Fase A.

## 6. Catatan lingkungan

Item produksi yang belum dijalankan (migrasi 2026-09-30, 2026-10-04, 2026-10-08), opening stock,
dan akun staf asli KACAB/KM tidak dibahas di sini. Lihat `CLAUDE.md` dan
`memory/project_fmwb_next_steps_2026-10-12.md`.
