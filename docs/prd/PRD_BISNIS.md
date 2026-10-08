# Dokumen Kebutuhan Sistem — FIT MOTOR (Versi Bisnis)

> **Untuk siapa:** pemilik, kepala cabang, manajer, staf CS/kasir/gudang/keuangan, dan siapa pun yang tidak berlatar belakang IT.
> **Isi:** apa saja yang bisa dikerjakan sistem ini, siapa memakainya, bagaimana alurnya, dan apa yang masih perlu dibereskan.
> **Versi teknis:** [PRD_TEKNIS.md](./PRD_TEKNIS.md) · **Gambar hubungan data:** [ERD.md](./ERD.md) · [LRS.md](./LRS.md)
> **Tanggal:** 25 September 2026

---

## 1. Gambaran Singkat

FIT MOTOR punya **1 kantor pusat/gudang dan 4 cabang bengkel** (Adiwerna/Pesalakan, Pacul, Cikditiro, dan Trayeman yang berstatus mitra). Dulu setiap cabang mencatat di aplikasi **Microsoft Access** sendiri-sendiri, sehingga angka sulit disatukan dan sulit dicek pemilik.

**Sistem Web Bengkel** adalah aplikasi berbasis web yang menyatukan semuanya dalam satu tempat. Cukup buka browser, login, pilih cabang. Dengan sistem ini:

- Pelanggan yang datang **tercatat rapi** — siapa, motor apa, servis apa saja sebelumnya.
- Antrian bengkel, kerja mekanik, dan pembayaran **terpantau langsung**.
- Stok sparepart **berkurang dan bertambah otomatis** saat dipakai, dijual, atau dibeli.
- Uang harian kasir **tercatat sampai disetor ke bank**, lengkap dengan bukti dan jejak siapa yang mengubah.
- Pemilik bisa melihat laporan seluruh cabang tanpa menunggu rekap manual.

**Besarnya data yang sudah ada di sistem saat ini:** lebih dari **100 ribu transaksi servis**, sekitar **37 ribu pelanggan dan motor**, sekitar **5.900 jenis barang dan jasa**, serta **lebih dari 300 ribu catatan keluar-masuk stok**. Sebagian besar dipindahkan dari Access lama.

---

## 2. Masalah yang Diselesaikan

| Dulu (Access per cabang) | Sekarang (Web Bengkel) |
|---|---|
| Data tiap cabang terpisah, pemilik harus minta rekap | Semua cabang dalam satu sistem, laporan bisa dibuka kapan saja |
| Pelanggan yang sama bisa tercatat dobel | Ada pengecekan pelanggan dobel dan penggabungan yang harus disetujui atasan |
| Pemakaian sparepart di servis tidak masuk kartu stok | Stok otomatis berkurang saat dipakai servis |
| Diskon dan potongan bisa diberikan tanpa jejak | Diskon besar butuh persetujuan atasan, semua tercatat |
| Uang kasir sulit ditelusuri | Alur kas awal → closing → setoran → bank tercatat setiap langkah |
| Komisi mekanik dihitung ulang tiap direvisi | Komisi disimpan permanen saat servis selesai/dibayar |
| Komplain pelanggan ditangani lewat chat, mudah terlupa | Ada modul komplain dengan penanggung jawab dan alur yang jelas |

---

## 3. Siapa Saja yang Memakai

| Peran | Tugas utama di sistem |
|---|---|
| **Administrator** | Mengatur user, hak akses, data master, memantau sinkronisasi data |
| **Customer Service (CS)** | Menerima pelanggan, mencari data motor, membuat servis, mengurus antrian, menerima komplain |
| **Kasir** | Buka kas, terima pembayaran servis & penjualan, tutup kas (closing), setor |
| **Mekanik** | Mengerjakan servis, mencatat temuan pada motor |
| **Kepala Mekanik** | Mengatur jadwal & pembagian kerja mekanik, memutuskan apakah komplain pengerjaan ulang diterima |
| **Pengadaan (Purchasing)** | Memesan barang ke supplier, memantau stok minimum, mengurus barang datang |
| **Keuangan** | Menerima setoran cabang, memeriksa selisih, menyetor ke bank |
| **Kepala Cabang** | Menyetujui hal penting di cabang (mis. pengerjaan ulang komplain) |
| **Manager / Manajemen** | Memantau laporan dan menangani eskalasi |
| **CRM** | Memantau pelanggan setia, mengingatkan pelanggan lama |
| **HRD** | Data pegawai dan absensi |

Setiap orang hanya melihat **menu yang sesuai perannya**. Kasir tidak akan melihat menu pembelian, misalnya. Pengaturan ini dilakukan admin lewat menu *Master Posisi*.

---

## 4. Modul-Modul dan Manfaatnya

### 4.1 Servis (jantung sistem)
**Untuk apa:** mencatat seluruh perjalanan motor pelanggan di bengkel, dari datang sampai selesai dibayar.

**Alur sehari-hari:**
1. **Pelanggan datang.** CS mencari data lewat **plat nomor** atau nama. Sistem langsung menampilkan siapa pemiliknya, servis terakhir, dan **kategori member** (Bronze/Silver/Gold/Platinum). Kalau baru, CS mendaftarkan pelanggan dan motornya.
2. **Catat keluhan** pelanggan dan buat servis. Bisa juga diatur **jemput-antar** motor (tarif dihitung dari jarak; motor jalan atau mogok tarifnya berbeda).
3. **Nomor antrian** diberikan. Antrian punya status: *menunggu → diproses → selesai* (atau *batal*), dengan tingkat prioritas *normal / urgent / VIP*.
4. **Mekanik mengerjakan.** Kepala mekanik membagi kerja. Kalau mekanik menemukan masalah lain, ia mencatat sebagai **temuan**.
5. **Penawaran tambahan.** Dari temuan, sistem menyarankan sparepart/jasa. CS menawarkan ke pelanggan; pelanggan **setuju atau menolak** (alasan penolakan dicatat: tidak mau, stok kosong, harga tidak cocok, dll.).
6. **Pembayaran.** Kasir memproses bayar — bisa **tunai, transfer, QRIS, atau campuran**. Nota dicetak. Stok otomatis berkurang.
7. **Setelah bayar**, sistem otomatis memperbarui **riwayat kunjungan, total belanja, dan kategori member** pelanggan, serta menyimpan **komisi** mekanik, kepala mekanik, dan admin.

**Fitur pendukung:**
- **Servis Garansi** — pengerjaan ulang dalam masa garansi. Lama garansi berbeda menurut kategori member.
- **DP (uang muka)** — pelanggan bisa bayar sebagian di awal, nanti dipotong saat bayar akhir.
- **Batal servis** — alasan wajib dicatat (permintaan pelanggan, stok kosong, tidak ada mekanik, pelanggan tidak datang, lainnya) dan pengembalian DP dicatat.
- **Retur servis** — bila ada jasa/barang yang dikembalikan.
- **Diskon** — diskon manual di luar batas wajar **harus disetujui supervisor**.
- **Promo periode** — lihat bagian 4.6.

### 4.2 Pembelian & Pengadaan Barang
**Untuk apa:** memastikan barang selalu tersedia tanpa menumpuk, dan setiap pembelian ada jejaknya.

**Alur:**
1. **Dashboard stok min/max** memberi tahu barang mana yang hampir habis berdasarkan penjualan 12 minggu terakhir. Sistem bisa **membuat draf permintaan otomatis**.
2. **Permintaan Pembelian (PR)** dibuat dan disetujui.
3. Jika perlu, **minta penawaran** beberapa supplier dan dibandingkan.
4. **Pesanan ke supplier (PO)** dibuat. **Persetujuan bertingkat** — makin besar nilainya, makin tinggi atasan yang harus menyetujui. Tidak bisa dilewati.
5. **Barang datang (Delivery Order)** — dicek jumlahnya dan dilacak statusnya.
6. **Faktur pembelian** dicatat dan barang dicek kualitasnya (lulus / gagal / sebagian). Stok bertambah.
7. **Hutang supplier** tercatat dan dilunasi lewat menu pembayaran hutang. **Retur** ke supplier juga bisa dicatat.
8. **Alarm harga beli**: kalau harga beli naik/turun melebihi batas yang ditetapkan, sistem memberi tanda supaya harga jual dievaluasi.

### 4.3 Penjualan
**Untuk apa:** menjual sparepart/barang langsung tanpa servis (kasir toko).
- Buat pesanan penjualan → jadi transaksi penjualan → cetak struk. Stok otomatis berkurang.
- **Piutang** pelanggan bisa dicatat dan dibayar bertahap.
- **Retur & refund** pelanggan dicatat.
- Ada opsi **membuat servis dari penjualan** (barang yang dibeli langsung dipasang di bengkel) dengan pengecekan agar tidak dobel.

### 4.4 Antar Cabang
**Untuk apa:** memindahkan barang antar cabang atau dari gudang pusat ke cabang, dengan catatan jelas siapa kirim dan siapa terima.

Ada tiga cara:
- **Cabang sendiri (internal) — seperti jual-beli antar cabang.** Cabang membuat pesanan → pusat memproses & mengirim → cabang menerima → cabang **wajib menginput faktur pembelian** agar stok di sistem benar-benar bertambah. *(Jika langkah terakhir ini lupa, barang sudah ada tetapi stok di sistem belum bertambah.)*
- **Pengadaan barang.** Cabang membuat permintaan (atau pusat mengirim duluan) → pusat konfirmasi kirim → cabang konfirmasi terima. Selesai, tidak perlu faktur terpisah.
- **Cabang mitra (Trayeman)** memakai jalur eksternal, termasuk unggah data lewat Excel. Harga dan diskon khusus mitra diatur di pengaturan antar cabang.

### 4.5 Stok
**Untuk apa:** tahu persis sisa barang di tiap cabang.
- **Stok akhir** dan **kartu stok** (riwayat masuk-keluar per barang) menampilkan asal perubahan: stok awal, pembelian, penjualan, servis, penyesuaian.
- **Penyesuaian manual** untuk koreksi (barang rusak, selisih hasil hitung fisik) — tercatat sebagai stok masuk atau keluar manual, sehingga bisa diaudit.

### 4.6 Member, Promo & Hubungan Pelanggan (CRM)
**Kategori member otomatis** berdasarkan total belanja atau jumlah kunjungan:

| Kategori | Total belanja | Jumlah kunjungan | Diskon jasa | Diskon barang |
|---|---|---|---|---|
| Bronze | s.d. Rp 1,99 juta | 0–4 kali | — | — |
| Silver | Rp 2 juta – 4,99 juta | 5–9 kali | 10% | 7% |
| Gold | Rp 5 juta – 9,99 juta | 10–19 kali | 15% | 11% |
| Platinum | ≥ Rp 10 juta | ≥ 20 kali | 20% | 14% |

- Pelanggan otomatis naik kategori setelah pembayaran; kategori **tidak turun** akibat penyalinan data lama.
- **Promo periode**: admin membuat promo (potongan persen atau nominal) untuk jasa, barang, atau paket tertentu, dengan **masa berlaku, cabang berlaku, dan syarat** (mis. minimal belanja, kategori member tertentu, jumlah kunjungan). Bisa diatur boleh **digabung** dengan diskon member atau tidak. Setiap pemakaian tercatat.
- **Statistik pelanggan**: siapa pelanggan terbaik, siapa yang sudah lama tidak datang, perkiraan kunjungan berikutnya, dan daftar untuk **dihubungi lewat WhatsApp**.
- **Pelanggan dobel**: sistem membantu mendeteksi; penggabungan **harus disetujui atasan** dan ada catatan sebelum-sesudah.
- **Pindah kepemilikan motor** sedang disiapkan tetapi **menunya sengaja dimatikan sementara** sampai migrasi data selesai.

### 4.7 Keuangan Kasir & Setoran
**Untuk apa:** menjaga uang harian cabang dari pagi sampai masuk rekening bank.

**Urutan kerja setiap hari:**
1. **Kas Awal** — kasir menghitung uang laci pagi hari (rincian per pecahan). Ada batas minimum kas awal per cabang.
2. **Sepanjang hari** — semua **pemasukan** dan **pengeluaran** dicatat dengan akun yang jelas (biaya listrik, transport, pembelian barang, dll.). Total penjualan dan servis ikut terhitung.
3. **Kas Akhir & Closing** — kasir menutup hari. Sistem menghitung selisih antara uang yang seharusnya ada dan yang benar-benar ada.
4. **Revisi** — jika ada salah catat setelah closing, kasir **mengajukan revisi** dan admin menyetujui/menolak. Membuka kembali transaksi juga perlu konfirmasi.
5. **Serah terima** — bila kasir bergantian shift, uang diserahterimakan dan tercatat.
6. **Setoran ke Staf Keuangan** — uang dibawa kurir; status: *sedang dibawa → diterima → validasi OK / SELISIH / dikembalikan ke CS*.
7. **Setoran ke Bank** — tunai atau transfer, lengkap dengan bukti. Sistem dapat **membaca bukti transfer** untuk membantu pencocokan nominal.
8. **Keuangan pusat** memantau semua cabang, termasuk **pinjam-meminjam antar cabang** dan setoran yang perlu diverifikasi.

Semua perubahan penting **tercatat siapa dan kapan** (jejak audit).

### 4.8 Penanganan Komplain Pelanggan
**Untuk apa:** setiap keluhan pelanggan ditangani sampai tuntas oleh orang yang tepat.

| Jenis komplain | Ditangani oleh |
|---|---|
| **Rework** (hasil servis bermasalah) | Kepala Mekanik memberi usulan; **Kepala Cabang** menyetujui |
| Harga, Sikap, Antrian, Fasilitas, Lainnya | Kepala Cabang |

**Alur:**
1. **CS mencatat komplain** dan memilih **servis asli** yang dikomplain (harus motor yang sama).
2. Untuk **rework**, Kepala Mekanik mengusulkan **terima** atau **tolak**.
3. **Kepala Cabang** memutuskan *setuju* atau *minta revisi*. Jika disetujui, sistem **langsung membuat servis garansi** (prioritas urgent) sehingga pelanggan tidak perlu antre ulang dari awal.
4. Hal yang tidak selesai di cabang **dieskalasi ke manajemen**.
5. **Dashboard** menampilkan ringkasan komplain dan bisa **diekspor**. Status yang dipakai: *Open, Diajukan, Dijadwalkan, Dikerjakan, Selesai, Ditutup-Ditolak, No-show (pelanggan tidak datang), Eskalasi Manajemen*.

> **Catatan penting:** modul ini sudah jadi dan berjalan, tetapi **persetujuan komplain baru bisa dipakai penuh di Pusat**. Untuk 4 cabang lain, akun **Kepala Cabang** dan **Kepala Mekanik** belum dibuat karena menunggu data nama staf dari pemilik (lihat bagian 8).

### 4.9 Laporan
Tersedia laporan untuk pembelian, penjualan, servis, retur, hutang dan piutang, antar cabang, kas masuk/keluar, stok, pembatalan servis, kunjungan pelanggan, serta **profit dan insentif**. Sebagian besar bisa dilihat di layar, **dicetak, atau diunduh sebagai PDF dan Excel**.

### 4.10 Data Master (daftar acuan)
Data dasar yang dipakai semua modul: **barang & jasa** (beserta kategori, satuan, rak, margin harga jual), **paket servis (work order)**, **keluhan dan temuan umum**, **pelanggan & kendaraan**, **supplier**, **cabang**, **mekanik & levelnya**, **sales**, **wilayah**, **akun kas & biaya**, **user & hak akses**, serta **pegawai**.

### 4.11 HRD & Absensi
Data pegawai, jadwal kerja, dan absensi (dengan pengecekan lokasi kerja). *Cakupan yang aktif dipakai perlu dikonfirmasi lagi.*

### 4.12 Penyalinan Data dari Access
Selama masa peralihan, data dari Access cabang masih **disalin ke sistem web** secara berkala. Ada layar khusus untuk melihat apakah proses salin berhasil dan baris mana yang gagal. Rencananya, setelah semua cabang beralih sepenuhnya, **penyalinan ini dihentikan** dan Access tidak dipakai lagi.

---

## 5. Aturan Bisnis Penting (ringkas)

1. **Satu servis = satu nomor per cabang.** Nomor servis yang sama bisa ada di cabang berbeda; sistem selalu membedakan berdasar cabang.
2. **Status servis:** datang → diproses → selesai → **bayar**. Hanya yang berstatus *bayar* yang dihitung sebagai kunjungan dan penghasil komisi.
3. **Diskon** di luar aturan member/promo wajib disetujui supervisor.
4. **Pesanan pembelian bertingkat:** nilai besar → persetujuan atasan lebih tinggi. Tidak boleh dilewati.
5. **Member hanya naik**, tidak turun otomatis.
6. **Servis dan penjualan adalah transaksi terpisah** (sama seperti di Access), digabung hanya di laporan.
7. **Uang kasir:** setiap tahap (closing, setoran, validasi, bank) harus lewat urutan yang ada; perubahan setelah closing butuh persetujuan.
8. **Stok cabang tujuan (jalur antar cabang internal)** baru bertambah setelah faktur pembelian diinput.
9. **Perubahan struktur data** harus bisa dibatalkan (reversible) dan dijalankan dengan cara yang aman.
10. **Harga pokok (HPP) memakai metode FIFO** (barang yang masuk lebih dulu dianggap keluar lebih dulu) — *penerapannya belum lengkap* (lihat bagian 8).

---

## 6. Status Kesiapan per Modul

Penilaian mengikuti dashboard progres: **Siap pakai** = sudah lolos uji lapangan; **Tahap uji coba** = berjalan, masih diuji di bengkel; **Pengerjaan** = belum selesai.

| Modul | Status |
|---|---|
| Cari pelanggan/nopol, booking servis, nomor antrian | 🟢 Siap pakai |
| Pencarian sparepart & cek stok | 🟢 Siap pakai |
| Antrian & monitor mekanik, work order, pembayaran servis, garansi, batal servis | 🟡 Uji coba |
| Pembelian (PR → PO → DO → Faktur → Hutang) | 🟢 Berjalan (beberapa bug kritis sudah diperbaiki Juli 2026) |
| Penjualan / kasir toko, retur, piutang | 🟡 Uji coba |
| Antar cabang | 🟡 Uji coba |
| Stok & kartu stok | 🟢 Berjalan |
| Promo periode | 🟢 Selesai & teruji |
| Member & statistik pelanggan | 🟢 Berjalan |
| Keuangan kasir & setoran (5 cabang) | 🟢 Lolos uji 5 cabang, sistem kasir lama sudah diblokir |
| Komplain (termasuk rework → garansi) | 🟢 Selesai; 🟡 menunggu akun 4 cabang |
| Laporan | 🟡 Uji coba |
| Penyalinan data Access | 🟢 Berjalan (masa peralihan) |
| HRD & absensi | ⚪ Perlu konfirmasi |

---

## 7. Ukuran Keberhasilan

Indikator yang dapat dipantau dari data sistem:

| Yang diukur | Contoh manfaat |
|---|---|
| Jumlah servis per cabang per hari & waktu tunggu antrian | Mengatur jumlah mekanik |
| Persentase penawaran part yang disetujui pelanggan | Menilai kualitas rekomendasi mekanik |
| Persentase servis dibatalkan & alasannya | Mengurangi kehilangan pendapatan |
| Jumlah komplain per jenis, dan berapa yang jadi pengerjaan ulang | Menjaga kualitas kerja |
| Selisih kas closing & setoran | Mengontrol kebocoran uang |
| Stok di bawah minimum & barang lambat laku | Mengurangi kehabisan dan penumpukan barang |
| Jumlah pelanggan per kategori member dan yang lama tidak datang | Menjalankan program pelanggan setia |

---

## 8. Pekerjaan yang Belum Selesai & Risiko

### Butuh keputusan/data dari pemilik
1. **Akun Kepala Cabang dan Kepala Mekanik untuk 4 cabang** (Pacul, Pesalakan, Trayeman, Cikditiro). Tanpa akun ini, persetujuan komplain **tidak bisa berjalan** di cabang-cabang tersebut. *Sistem tidak boleh membuat akun sendiri — perlu nama staf dan kode karyawan asli.*
2. **Tiga hal di modul komplain yang sengaja ditunda** karena aturannya belum jelas: unggah data komplain sekaligus banyak, pencegahan input komplain dobel (dalam 60 detik), dan batas berapa hari pelanggan dianggap "tidak datang" (usulan 7 hari, belum bisa diatur).
3. **Metode HPP FIFO** dan penghitungan **laba rugi, insentif, dan bagi hasil**: datanya sudah ada tetapi belum jadi laporan/aturan hitung di sistem web.
4. **Pindah kepemilikan motor**: menu disembunyikan; menunggu migrasi Access selesai.
5. **Fitur baru "Checklist Pengecekan Mekanik"** — rancangannya sudah ditulis (19 Sep 2026), belum dikerjakan.

### Risiko yang perlu diketahui
| Risiko | Penjelasan | Yang perlu dilakukan |
|---|---|---|
| Sebagian data tidak dijaga ketat oleh database | Banyak hubungan antar data (mis. servis ↔ pelanggan) hanya dijaga oleh program, bukan oleh database. Bila ada salah input/salah ubah, bisa muncul data yatim | Bertahap tambahkan pengaman data |
| Nomor servis bisa sama di cabang berbeda | Membuat laporan lintas cabang mudah keliru bila tidak hati-hati | Sudah ditangani; 3 laporan risiko kecil tersisa |
| Kata sandi dan kunci sistem | Ada kata sandi database bawaan yang masih tertulis di sebagian file lama. Repositori pernah diakses peserta PKL | Dibersihkan bertahap; jangan bagikan file konfigurasi |
| Dua sistem hak akses hidup bersamaan | Sistem lama dan baru berdampingan sehingga hak akses bisa tidak seragam | Diseragamkan ke sistem baru |
| Login lama `masterkey` masih ada | Menunggu koordinasi eksternal untuk dimatikan | Dijadwalkan |
| Puluhan halaman lama belum terhubung ke menu | Bisa membingungkan dan membuka celah | Audit menu sedang berjalan (5 dari 8 tahap selesai) |
| Sistem tidak memakai Docker | Sistem produksi berjalan di hosting biasa | Tidak diubah (hanya proyek baru) |

---

## 9. Rencana Berikutnya (usulan urutan)

1. **Lengkapi akun Kepala Cabang & Kepala Mekanik** di 4 cabang → komplain berjalan penuh.
2. **Selesaikan audit menu** (stok & laporan) dan rapikan halaman lama.
3. **Uji lapangan** modul yang masih berstatus kuning sampai lolos (antrian, pembayaran servis, garansi, penjualan, antar cabang, laporan) → naik menjadi hijau.
4. **Tutup peralihan Access** (cutover sekali jalan) lalu hentikan penyalinan data.
5. **Pengaman data & kata sandi** (hapus kata sandi bawaan, seragamkan hak akses).
6. **HPP FIFO, laba rugi, insentif** menjadi laporan resmi.
7. **Checklist Pengecekan Mekanik**, lalu **Pindah Kepemilikan Motor**.

---

## 10. Istilah Sederhana

| Istilah | Arti |
|---|---|
| **Nopol** | Nomor polisi (plat) kendaraan |
| **PR** | Permintaan pembelian internal (belum memesan ke supplier) |
| **RFQ** | Permintaan penawaran harga ke beberapa supplier |
| **PO** | Pesanan resmi ke supplier |
| **DO** | Surat jalan / tanda barang datang |
| **QC** | Pengecekan kualitas barang saat datang |
| **Work Order (WO)** | Paket pekerjaan servis (jasa + sparepart) |
| **Temuan** | Masalah yang ditemukan mekanik saat mengerjakan motor |
| **Rework** | Pengerjaan ulang karena hasil servis dikomplain |
| **Closing** | Penutupan kas harian kasir |
| **Setoran** | Menyerahkan uang dari cabang ke keuangan/bank |
| **HPP** | Harga pokok penjualan (modal barang) |
| **FIFO** | Barang yang masuk pertama dianggap keluar pertama |
| **Cutover** | Hari perpindahan penuh dari sistem lama ke sistem baru |
| **Eskalasi** | Menaikkan penanganan ke atasan yang lebih tinggi |
| **Member** | Tingkat kesetiaan pelanggan (Bronze, Silver, Gold, Platinum) |
