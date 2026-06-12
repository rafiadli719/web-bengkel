**KEBUTUHAN PROGRAM BENGKEL**

**BENGKEL FIT MOTOR**

**KEBUTUHAN SISTEM**

**WAJIB TERSEDIA (MANDATORY)**

Bisa untuk Multi Cabang

Bisa untuk Multi User

Mudah digunakan

Database mudah diakses untuk kebutuhan pengolahan data

**PELENGKAP (SETELAH MANDATORY TERSEDIA)**

Mudah diakses dari berbagai piranti (Laptop, Smartphone)

Semua operasional bengkel bisa terakomodir (bertahap)

Memudahkan dalam pengambilan keputusan

 

**KEBUTUHAN MODUL**

**WAJIB TERSEDIA (MANDATORY)**

1.  DATA MASTER

2.  KASIR

3.  PEMBELIAN

4.  PENJUALAN

5.  SERVIS

6.  PENYESUAIAN STOK

7.  TRANSFER STOK ANTAR CABANG

8.  LAPORAN

**PELENGKAP (SETELAH MANDATORY TERSEDIA)**

1.  PERHITUNGAN INSENTIF

2.  CRM (MANAJEMEN HUBUNGAN PELANGGAN)

3.  DASHBOARD MANAJEMEN (SEMUA CABANG)

4.  DASHBOARD CABANG

5.  PENGADAAN

6.  AKUNTANSI KEUANGAN

 

**LEVEL USER**

**ADMINISTRATOR**

- AKSES SEMUA MENU

- PEMBUKAAN CABANG BARU

- HANYA MENGATUR CABANG TERKAIT

**USER (LOGIN BISA BERPINDAH2 CABANG)**

STAF CS / KASIR

KEPALA MEKANIK

PENGADAAN

STAF CRM

**MANAJEMEN**

DASHBOARD LAPORAN

- GABUNGAN SEMUA CABANG

- PER CABANG

- LAPORAN HARIAN, BULANAN, TAHUNAN

**HALAMAN LOGIN**

![](media/media/image54.png){width="2.87463801399825in" height="1.4248928258967628in"}

- untuk kolom USER agar bisa combo box ato predictive text

- tambah kolom pilihan CABANG untuk menunjukkan user sedang tugas di Cabang mana

**MENU UTAMA**

![](media/media/image48.png){width="5.905598206474191in" height="3.7225415573053366in"}

**MASTER DATA**

![](media/media/image47.png){width="5.923473315835521in" height="3.7117497812773403in"}

- MASTER BARANG

- MASTER KATEGORI BARANG (OLIGEN, ORISIN, VARIASI, AFTER MARKET, DLL)

- MASTER SATUAN BARANG (BOX, PCS, DLL)

- MASTER PABRIK BARANG (HONDA, YAMAHA, IRC, FEDERAL, DLL)

- MASTER RAK BARANG (AKSESORIS, CAIRAN, BAHAN HABIS PAKAI, DLL)

- MASTER MARGIN HARGA JUAL

- 

- MASTER STATUS HARGA (JUAL NAIK POKOK NAIK, JUAL NAIK POKOK TETAP, DLL)

- MASTER WORK ORDER / PAKET (PAKET SERVIS : JASA + BARANG)

- MASTER PELANGGAN

- MASTER KATEGORI PELANGGAN (SILVER, GOLD, DIAMOND)

- MASTER SUPPLIER (TERMASUK CABANG SBG SUPPLIER)

- MASTER CABANG (PESALAKAN, PACUL, CIKDITIRO)

- MASTER TIPE CABANG (MILIK SENDIRI, MITRA)

- MASTER MEKANIK

- MASTER LEVEL MEKANIK (PEMULA, MENENGAH, MAHIR)

- MASTER TIPE MOTOR (VARIO 110, VARIO 150, VARIO 160, BEAT 110, DLL)

- MASTER PABRIK MOTOR (HONDA, YAMAHA,SUZUKI, KAWASAKI, DLL)

- MASTER KATEGORI MOTOR (MATIC, SUPER MATIC, BEBEK, SPORT, SUPER SPORT)

- MASTER KENDARAAN

- MASTER WARNA

- MASTER DESA

- MASTER KECAMATAN

- MASTER KOTA

- MASTER PROPINSI

- MASTER AKUN BIAYA

**MASTER MARGIN HARGA JUAL**

![](media/media/image58.png){width="3.555492125984252in" height="1.8765102799650044in"}

**MASTER HARGA JUAL PLUS JASA**

![](media/media/image63.png){width="2.1284733158355205in" height="1.38251312335958in"}

**MASTER ITEM**

![](media/media/image69.png){width="6.352069116360455in" height="3.7051662292213474in"}

(versi Program Bengkel)

**Hanya Administrator yg dapat menambah / mengedit data master barang**

tujuannya : agar penulisan nama produk seragam, jadi lebih mudah terkontrol

- Fitur pencarian ditambah :

<!-- -->

- bisa berdasar grup produk (oli mesin, komstir, dll), jenis produk (orisin, imitasi, dll), tipe motor (vario 125, beat FI, dll)

- bisa cek sisa stok per cabang & total keseluruhan

- penanda berdasar sisa stok cabang terkait vs minimum & maksimum stok per cabang :

  - untuk produk^2^ yg jumlahnya ≤ minimal (cth : stok warna barisnya berubah jadi kuning)

  - untuk produk^2^ yg jumlah stok nya = 0, (cth : warna barisnya berubah jadi merah)

![](media/media/image49.png){width="5.810212160979877in" height="4.94910542432196in"}

- Kolom BARCODE tetap harus tersedia, saat ini terpakai untuk kolom KATEGORI BARANG (oli mesin, komstir, dll)

- Kolom KETERANGAN nantinya ada tersendiri (untuk info lain2 terkait barang)

- kolom APPLICABLE PART (sumber dari data TIPE MOTOR) berisi info sparepart tersebut bisa digunakan oleh motor apa saja

- jika pilih tipe item "BARANG", tambahan kolom pilihan dari MASTER HARGA JUAL PLUS JASA (PK PS PB PC)

- kolom SUPPLIER 3 untuk info supplier ke-3

- kolom STOK MAKSIMAL nanti masuk kolom tersendiri

- yg belum masuk di tampilan yaitu kolom PABRIK, AREA

![](media/media/image51.png){width="7.086111111111111in" height="2.532638888888889in"}

(versi buatan sendiri)

- Tampilan diurutkan berdasar **STATUS HARGA BARANG (NAIK/TURUN/TETAP)**,

- STATUS HARGA BARANG ditentukan dari :

1.  Harga HPP pada Master Barang VS Perhitungan Acuan HPP

- Acuan HPP = harga beli netto dari 4 pembelian terakhir ke supplier yang termahal

2.  Harga Jual pada Master Barang VS Perhitungan Harga Jual

- Variabel yg digunakan utk Perhitungan Harga Jual =

> ACUAN HPP, MASTER MARGIN HARGA JUAL, MASTER HARGA JUAL PLUS JASA

3.  Sistem bisa update per item ataupun beberapa item sekaligus (centang)

![](media/media/image52.png){width="4.53286198600175in" height="2.4294608486439193in"}

**KARTU STOK**

![](media/media/image57.png){width="4.751014873140857in" height="3.5078532370953632in"}

**HISTORY HARGA POKOK / PEMBELIAN**

![](media/media/image64.png){width="4.718171478565179in" height="2.9530402449693787in"}

**MASTER SUPPLIER**

![](media/media/image71.png){width="5.898860454943132in" height="4.191173447069116in"}

![](media/media/image61.png){width="3.7976727909011374in" height="3.771839457567804in"}

- **PENAMBAHAN / EDIT SUPPLIER HANYA BISA DILAKUKAN OLEH ADMINISTRATOR**

- TERTERA JUGA SUPPLIER NAMA CABANG

- Kolom DAFTAR PABRIK SPAREPART yg disuplai oleh supplier tersebut

- Kolom KODE POS saat ini digunakan utk kolom LAMA HARI KIRIM DARI SUPPLIER

- Kolom NEGARA saat ini digunakan utk kolom PERIODE JATUH TEMPO

**MASTER TIPE MOTOR**

![](media/media/image62.png){width="3.525333552055993in" height="2.4919860017497815in"}

**MASTER KENDARAAN**

![](media/media/image74.png){width="5.37957895888014in" height="3.8448906386701664in"}

![](media/media/image65.png){width="3.067997594050744in" height="2.8458803587051618in"}

- **JENIS MOTOR** menggunakan dropdown menu (KARBURATOR, INJEKSI, LISTRIK)

- Tahun Buat saat ini digunakan untuk Tahun Pajak

- Input **KENDARAAN** baru akan **ditolak** jika NOMOR POLISI yang diinput **sudah tercatat** di sistem

**MASTER PELANGGAN**

![](media/media/image70.png){width="5.47353346456693in" height="3.812270341207349in"}

![](media/media/image72.png){width="3.3100481189851267in" height="3.3176924759405075in"}

- Tiap pelanggan bisa memiliki beberapa motor, pengecekan 2 kolom kunci yaitu : NOMOR WHATSAPP atau NOMOR POLISI

- Input Pelanggan baru akan **ditolak** jika nomor WHATSAPP yang diinput **sudah tercatat** di sistem

- Status WA / HP sudah tidak diperlukan

- Kolom yang perlu ditambahkan : Patokan, koordinat gmaps, panggilan (mas/mba/ pak/bu), dll yg diperlukan

- Dipisahkan antara kolom patokan dengan koordinat google maps

- 

**MASTER MEKANIK**

![](media/media/image73.png){width="5.226473097112861in" height="3.701146106736658in"}

![](media/media/image68.png){width="3.0063637357830273in" height="2.5612292213473316in"}

- Input **KEPALA MEKANIK** saat servis menggunakan data **MEKANIK**

- Tingkat keahlian **minimal** untuk **KEPALA MEKANIK** yaitu mekanik dengan keahlian **MAHIR**

**MASTER SALES**

![](media/media/image56.png){width="4.7163331146106735in" height="3.2358923884514437in"}

![](media/media/image46.png){width="3.296441382327209in" height="1.5670034995625546in"}

- Digunakan untuk info dalam proses servis

- karena beberapa motor servis dibawa oleh customer get customer

- nantinya SALES tersebut akan mendapat insentif / hadiah

**MASTER USER**

![](media/media/image45.png){width="3.51040135608049in" height="2.805582895888014in"}

![](media/media/image43.png){width="3.919902668416448in" height="5.263343175853018in"}

Tampilan admin disesuaikan sesuai yg dianggap terbaik

**TRANSAKSI PEMBELIAN**\
![](media/media/image42.png){width="4.322553587051619in" height="3.543076334208224in"}

**Fitur yang dibutuhkan :**

1.  **PESANAN PEMBELIAN**

2.  **PEMBELIAN**

3.  **PEMBAYARAN HUTANG**

**PEMBELIAN**

![](media/media/image60.png){width="4.667948381452319in" height="3.4098709536307963in"}

**Pencarian** Data Pembelian bisa dilengkapi berdasar :

- Tanggal

<!-- -->

- Nama (Kode) Supplier

- Status Pembayaran

- Tipe Supplier (Eksternal / Antar Cabang)

![](media/media/image67.png){width="4.492608267716536in" height="3.210955818022747in"}

- Perlu tambahan kolom **NOMOR FAKTUR, TANGGAL FAKTUR**

- Kolom keterangan nantinya akan diisi info lainnya

**PEMBAYARAN HUTANG**

![](media/media/image66.png){width="4.478126640419948in" height="3.2892443132108484in"}

- Tampilan pemilihan data yg ditampilkan ditambah berdasar **NAMA SUPPLIER, TIPE SUPPLIER** (Eksternal / Antar Cabang), **RENTANG TANGGAL JATUH TEMPO**

<!-- -->

- Pelunasan bisa langsung beberapa faktur bersamaan yang dipilih (bisa dalam bentuk **CENTANG**)

![](media/media/image59.png){width="5.035442913385827in" height="3.3571259842519683in"}

![](media/media/image50.png){width="5.3255424321959755in" height="2.0145614610673666in"}

**LAPORAN HUTANG**

- saat ini digunakan untuk kirim ke WA manajemen

- Nantinya manajemen langsung bisa akses sendiri

- Laporan hutang detail per faktur per suplier

- Laporan hutang total per suplier

![](media/media/image53.png){width="4.512649825021872in" height="5.586143919510061in"}

![](media/media/image12.png){width="3.2032819335083116in" height="3.6775721784776905in"}

**TRANSAKSI PENJUALAN**

Terbagi menjadi 2 tipe :

1.  **Penjualan ke Pelanggan**

2.  **Penjualan Antar Cabang :**

    - **Cabang Sendiri**

    - **Cabang Mitra - Eksternal**

**PENJUALAN KE PELANGGAN**

- **BARANG DGN STATUS STOK KOSONG & HARGA NAIK TIDAK BISA DIINPUT KE TRANSAKSI**

![](media/media/image10.png){width="5.094501312335958in" height="3.654121828521435in"}

![](media/media/image2.png){width="5.1169083552056in" height="3.2529779090113737in"}

**PELUNASAN PENJUALAN**

![](media/media/image8.png){width="4.394222440944882in" height="3.2354505686789152in"}

![](media/media/image9.png){width="4.589026684164479in" height="3.0194750656167977in"}

![](media/media/image13.png){width="4.957859798775153in" height="1.7467169728783902in"}

**PENJUALAN ANTAR CABANG**

- Untuk saat ini proses pengolahan data untuk Pesanan Penjualan di Excel

- File Excel diupload ke database, lalu diproses masuk ke Pesanan Penjualan

- Dari Pesanan Penjualan data ditarik masuk ke Penjualan (penerima tidak perlu input ulang manual)

- Cabang Sendiri

  - Harga Jual = Harga Pokok

  - Tunai

  - Diskon 100%

- Cabang Mitra - Eksternal

  - Harga Jual = Harga Pokok + Margin Laba (5% \* bisa berubah)

  - Tempo (kredit) 10 Hari

- Nilai margin laba (5%) dan Tempo Kredit bisa (10 hari) bisa diubah

![](media/media/image3.png){width="3.474736439195101in" height="2.0545636482939633in"}

![](media/media/image6.png){width="2.3128226159230096in" height="1.6773173665791776in"}

![](media/media/image7.png){width="3.8130325896762907in" height="1.0313943569553805in"}

![](media/media/image5.png){width="5.514288057742782in" height="1.9919313210848644in"}

![](media/media/image44.png){width="4.7644116360454944in" height="3.128804680664917in"}

![](media/media/image18.png){width="4.787917760279965in" height="3.410288713910761in"}

![](media/media/image1.png){width="4.705924103237096in" height="3.2376760717410322in"}

**PENERIMAAN ANTAR CABANG**

- User memilih **CABANG PENGIRIM**

- Daftar **Nota Cabang yang muncul hanya nota yang belum pernah diterima**

- Data PENJUALAN ANTAR CABANG dari Cabang Pengirim masuk sebagai data PESANAN PEMBELIAN di Cabang Penerima

- Semua ketentuan (Margin Harga, Periode Kredit) Nota dari Cabang Pengirim akan masuk ke Cabang Penerima

![](media/media/image38.png){width="3.3713003062117237in" height="1.6581069553805774in"}

![](media/media/image16.png){width="4.874817366579178in" height="2.256346237970254in"}

![](media/media/image4.png){width="5.0087084426946635in" height="2.987357830271216in"}

**PENYESUAIAN STOK**

**Dibagi menjadi 2 METODE :**

1.  Hasil stok opnam dihitung **MANUAL** selisihnya, user akan input selisih sesuai status :

    a.  Item Masuk (selisih lebih)

    b.  Item Keluar (selisih kurang)

2.  Hasil hitung stok opnam secara **OTOMATIS** dihitung selisihnya oleh sistem

**PENYESUAIAN STOK MANUAL**

**ITEM MASUK**

![](media/media/image15.png){width="4.541147200349957in" height="3.3306200787401576in"}

![](media/media/image31.png){width="4.542583114610673in" height="2.8972101924759404in"}

- Report untuk Item Masuk (Selisih Lebih) belum tersedia

**PENYESUAIAN STOK MANUAL**

**ITEM KELUAR**

![](media/media/image11.png){width="4.491469816272966in" height="3.27378280839895in"}

![](media/media/image35.png){width="4.484537401574803in" height="2.8830424321959756in"}

- Report untuk Item Keluar (Selisih Kurang) belum tersedia

**PENYESUAIAN STOK OTOMATIS**

Tahapannya :

1.  User melakukan stok opnam dengan pilihan data acuan stok opnam :

    a.  Produk2 yg ada transaksi penjualan & servis pada **RENTANG TANGGAL** tertentu

    b.  Semua item yg **STOK-NYA \> 0** sesuai cabang terkait

    c.  Data yg tampil diurutkan berdasar **RAK BARANG** lalu **NAMA BARANG**

2.  Hasil stok opnam dibandingkan dengan stok sistem, lalu sistem akan langsung posting penyesuaian :

    a.  Item Masuk (selisih lebih)

    b.  Item Keluar (selisih kurang)

![](media/media/image23.png){width="3.254096675415573in" height="3.3483464566929135in"}

![](media/media/image24.png){width="7.086111111111111in" height="4.740972222222222in"}

**TRANSAKSI SERVIS**

**PEMBAGIAN TIPE SERVIS** (di awal selalu pilih diantara 2 opsi di bawah) :

1.  **SERVIS REGULER**

2.  **SERVIS GARANSI** (KOMPLAIN/REWORK)

**KETERANGAN :**

1.  **SERVIS REGULER**, alur, fitur, & kebutuhan :

    - Input pelanggan baru berdasarkan pengecekan 2 kolom kunci yaitu : **NOMOR WHATSAPP** atau **NOMOR POLISI** (untuk meminimalkan duplikasi) termasuk nomor polisi lama yg sudah tidak aktif

    - Bisa pilih **WORK ORDER (Gabungan Jasa + Barang)** untuk mempercepat & meminimalkan kesalahan input, Contoh :

      - PAKET SERVIS LENGKAP, detail jasa & barangnya :

      - Jasa : Servis Standar, Perawatan CVT, Gurah Mesin

      - Barang : Pembersih CVT (1), ICC (1), PEC (1), Carbu Cleaner (2), Grease (2)

    - **NILAI HPP MENGGUNAKAN HARGA BELI TERMAHAL DARI 4 PEMBELIAN TERAKHIR**

    - Pencatatan Perintah Kerja, Keluhan, & Catatan Terpisah

> (Ada status progress masing2 perintah kerja & keluhan)

- Barang dgn status **STOK KOSONG** & **HARGA NAIK** **tidak bisa diinput ke transaksi**

- **STATUS WAKTU TIAP PROSES** bisa tercatat (DATANG, DIPROSES, SELESAI, BAYAR/SERAH TERIMA)

![](media/media/image37.png){width="7.050550087489064in" height="3.2682370953630797in"}

- **Saat ini pecatatan Perintah Kerja, Keluhan, & Catatan masih dalam satu kolom**

(Ada status progress masing2 perintah kerja & keluhan)

**PELANGGAN BARU**

![](media/media/image33.png){width="5.567765748031496in" height="2.233872484689414in"}

**PELANGGAN LAMA**

![](media/media/image26.png){width="5.703957786526684in" height="2.9727209098862644in"}

**JEMPUT ANTAR**

![](media/media/image27.png){width="5.033113517060367in" height="2.754303368328959in"}

**PENTING !**

- User bisa input item WORK ORDER

- Item barang / jasa tidak boleh diinput 2 kali dalam 1 nomor servis

- Tambahan kolom Input **Kepala Mekanik (wajib terisi)**, Sales

- Cara input mekanik dipermudah, **mekanik wajib terisi minimal 1 nama**

- Kolom biaya di sebelah kanan seharusnya kolom **kontribusi mekanik, totalnya harus 100%**

- **Diskon jasa & barang otomatis terisi berdasar tipe pelanggan**

- **Diskon jasa & barang bisa diisi fleksibel dalam Persen ataupun Potongan Rupiah**

- **KM berikut** bertambah otomatis saat diinput **KM Sekarang**, mengacu **MASTER JADWAL PENGGANTIAN OLI**

![](media/media/image40.png){width="7.086111111111111in" height="4.714583333333334in"}

![](media/media/image22.png){width="3.4479166666666665in" height="1.7694444444444444in"}

![](media/media/image39.png){width="3.34214457567804in" height="1.9034416010498687in"}

**NOTA SERVIS**

![](media/media/image32.png){width="7.086111111111111in" height="4.661111111111111in"}

![](media/media/image30.png){width="7.086111111111111in" height="4.565277777777778in"}

![](media/media/image19.png){width="5.077718722659667in" height="3.7341437007874014in"}

**History servis pelanggan / motor terkait (saat ini)**

![](media/media/image17.png){width="3.7894083552055995in" height="2.704207130358705in"}

**Tampilan History Servis yg Dibutuhkan**

- bisa melihat **REKAP & DETAIL** data dari **PELANGGAN & MOTOR** terkait

![](media/media/image25.png){width="5.83663823272091in" height="2.5968580489938757in"}

![](media/media/image28.png){width="7.086111111111111in" height="0.25833333333333336in"}

**FORM SERVIS**

- Saat ini **Lembar** **Form Servis** di bawah digunakan oleh Admin & Kepala Mekanik

- Tujuannya

  - memudahkan dalam pencatatan

  - memudahkan dalam penawaran

  - sebagai panduan untuk upselling & cross selling

- **Jenis Sparepart** (Oli Mesin, Aki, Busi, dll) yg ditampilkan adalah **barang2 laku cepat**

- Pada Format **Form Servis Kosong**, bagian yg **putih** adalah bagian yg diisi oleh **admin** dan bagian yg **kuning** adalah yang diisi oleh **Kepala Mekanik**

- Berikut Penjelasan Alur dan Cara Pengisian Form Servis (Form Servis Terisi) :

1.  Admin mencatat profil (data pelanggan & motor), perintah kerja, dan keluhan pelanggan

2.  Kepala Mekanik melakukan pengetesan awal sesuai nomor urut servis

3.  Mekanik melakukan pengerjaan servis sesuai arahan Kepala Mekanik dan Form Servis

4.  Hasil pengecekan berupa **usulan penggantian sparepart (termasuk jasa)** dicatat pada Form Servis

5.  Termasuk juga **status kondisi** dari masing2 sparepart yg diusulkan ganti (nantinya perlu data master status kondisi per jenis sparepart)

6.  Admin menghitung **estimasi total tagihan** (jasa + barang) untuk diinfo ke pelanggan terkait

7.  Admin menghitung & konfirmasi total tagihan yg disetujui pelanggan, kemudian diteruskan ke kepala mekanik

8.  Berdasar hasil konfirmasi pelanggan, pekerjaan dieksekusi oleh Mekanik

9.  Untuk poin2 yang tidak disetujui pelanggan, akan masuk **daftar catatan**

- Admin bisa input penawaran sparepart yang nama sparepart-nya belum ada di master sparepart (ini dibutuhkan karena ada beberapa penawaran sparepart yg belum pernah disediakan bengkel)

10. 

**FORM SERVIS KOSONG**

![](media/media/image21.png){width="5.646928040244969in" height="9.776037839020123in"}

**FORM SERVIS TERISI**

![](media/media/image41.png){width="7.086111111111111in" height="9.447916666666666in"}

2.  **SERVIS GARANSI** (KOMPLAIN/REWORK)

    - Tampilan hampir sama dengan tampilan Servis Reguler

    - Status SERVIS = **KOMPLAIN**

    - Yang membedakan yaitu di awal Admin akan menarik data **NOMOR SERVIS SEBELUMNYA**

    - Data dari nomor servis sebelumnya akan diinput ke Servis Garansi seperti Nama & Nomor Kendaraan

- **Nilai Sub Total Jasa TIDAK BOLEH NOL** ( 0 )

- Checking Input & Error sama dengan Servis Reguler

**KASIR**

- Tahapan proses kasir : **Kas Awal 🡪 Transaksi 🡪 Kas Akhir (Closing)**

- **Dalam 1 hari bisa proses kasir lebih dari 1 kali** (contoh admin 1 pagi s.d siang, admin 2 siang s.d sore)

- **SETORAN RIIL =** KAS AWAL + PENJUALAN TUNAI + SERVIS + UANG MASUK -- UANG KELUAR -- PEMBELIAN TUNAI

![](media/media/image29.png){width="3.201362642169729in" height="5.32655949256343in"}![](media/media/image34.png){width="3.1697003499562553in" height="3.8229166666666665in"}

![](media/media/image20.png){width="2.1875in" height="3.6312489063867015in"} ![](media/media/image14.png){width="5.981271872265967in" height="6.5980905511811025in"}

![](media/media/image55.png){width="6.232512029746282in" height="5.329937664041995in"}

**PELENGKAP** (setelah semua poin mandatory terpenuhi)

**BOOKING SERVIS**

![](media/media/image36.png){width="7.086111111111111in" height="4.031944444444444in"}

STATUS PENGERJAAN (PROGRES) SERVIS MOTOR
