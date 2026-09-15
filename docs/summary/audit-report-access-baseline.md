# Audit Report yang Dipakai: MS Access vs FM Web Base

Tanggal audit: 2026-09-10
Update: 2026-09-11 — audit lanjutan khusus **report cetak transaksi harian** (bukan rekap/laporan periodik), sumber bukti dari isi `SISTEM INFORMASI BENGKEL FIT MOTOR.docx` (teks + screenshot gambar di dalamnya) dan file `.rpt`/objek Report di MS Access langsung (`FITMOTOR APP.mdb`, `Bengkel2.0.mde`, folder `D:\BENGKEL 2.0\REPORT ...`). Lihat bagian **E** di bawah untuk daftar lengkap dan detail perbandingan pixel/field.

## Aturan audit

Daftar utama hanya berisi report/halaman yang benar-benar ditemukan dan dipakai dalam operasional. Daftar kebutuhan dari dokumen sistem tidak otomatis dianggap sebagai report yang sudah ada.

- **DIPAKAI**: ditemukan di menu atau alur operasional FM Web Base.
- **DIPAKAI SEBAGAI DATA**: tersedia sebagai halaman/data, bukan report cetak khusus.
- **BELUM ADA**: belum ditemukan implementasi report khusus.
- **ACCESS TERKONFIRMASI**: objek Report Access berhasil ditemukan.
- **BELUM TERKONFIRMASI**: belum ditemukan objek Report Access yang dapat dipadankan.
- **LAYOUT BELUM DIBANDINGKAN**: belum ada screenshot/hasil cetak dari kedua sistem untuk report yang sama.
- **SESUAI STRUKTUR**: struktur sudah dibandingkan dengan contoh, tetapi belum berarti pixel-perfect.

> FM Web Base selalu berada di sebelah kiri. MS Access selalu berada di sebelah kanan.

## A. Report yang benar-benar dipakai di FM Web Base

### 1. Report harian transaksi dan operasional

| No | Report aktif FM Web Base | FM Web Base | MS Access | Status layout |
|---:|---|---|---|---|
| 1 | Laporan pembelian | `lap_pembelian.php`, export PDF/Excel | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 2 | Laporan penjualan pelanggan | `lap_penjualan.php`, export PDF/Excel | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 3 | Laporan penjualan antar cabang | `lap_antarcab.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 4 | Laporan pesanan pembelian | `lap_pesanan_pembelian.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 5 | Laporan pesanan penjualan | `lap_pesanan_penjualan.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 6 | Laporan pembayaran hutang | `lap_pmby_hutang.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 7 | Laporan pembayaran piutang | `lap_pmby_piutang.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 8 | Laporan hutang detail | `laporan_hutang_detail.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 9 | Laporan hutang per supplier | `laporan_hutang_summary.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 10 | Laporan piutang detail | `laporan_piutang_detail.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 11 | Laporan piutang per pelanggan | `laporan_piutang_summary.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 12 | Laporan servis | `lap_servis.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 13 | Rekap kunjungan pelanggan | `lap_rekap_kunjungan.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 14 | Laporan cancel service | `lap_cancel_servis.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 15 | Laporan stok masuk | `lap_stok_masuk.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 16 | Laporan stok keluar | `lap_stok_keluar.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 17 | Laporan kas masuk | `lap_kas_masuk.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |
| 18 | Laporan kas keluar | `lap_kas_keluar.php` | Belum dikonfirmasi sebagai objek Report | Layout belum dibandingkan |

### 2. Report/dokumen cetak transaksi

| No | Dokumen cetak aktif FM Web Base | FM Web Base | MS Access | Status layout |
|---:|---|---|---|---|
| 19 | Nota pembelian | `pembelian_cetak.php`, `pembelian_struk.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 20 | Nota penjualan | `penjualan_cetak.php`, `penjualan_struk.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 21 | Bukti pembayaran hutang | `pembayaran_hutang_struk.php`, `pmby_hutang_print.php` | Belum ditemukan nama objek Report | **Sesuai struktur berdasarkan contoh; pixel-perfect belum diverifikasi** |
| 22 | Bukti pembayaran piutang | `pembayaran_piutang_struk.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 23 | Nota servis | `servis-print.php`, `servis-print-pdf.php`, `servis-reguler-struk.php` | `RPT_NOTA_SERVIS` | Layout belum dibandingkan |
| 24 | Bukti pengiriman antar cabang | `pengadaan_antarcab_print.php`, `pesanan_penjualan_cab_cetak.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 25 | Bukti stok masuk | `stok_masuk_struk.php`, `stok_masuk_add_cetak.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 26 | Bukti stok keluar | `stok_keluar_struk.php`, `stok_keluar_add_cetak.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 27 | Bukti stok opname masuk | `so-item-masuk-struk.php`, `so-masuk-add-cetak.php` | Belum dikonfirmasi | Layout belum dibandingkan |
| 28 | Bukti stok opname keluar | `so-keluar-add-cetak.php` | Belum dikonfirmasi | Layout belum dibandingkan |

### 3. Report/monitoring servis yang dipakai

| No | Fitur aktif FM Web Base | FM Web Base | Kategori |
|---:|---|---|---|
| 29 | Input dan lihat servis reguler | `servis-carinopol.php`, `servis-reguler.php` | Proses operasional |
| 30 | Input dan lihat servis garansi | `servis-carinopol-garansi.php`, `servis-reguler.php?filter=garansi` | Proses operasional |
| 31 | Antrian dan progres servis | `kelola-antrian.php`, `dashboard-antrian-servis.php` | Monitoring |
| 32 | Tracking keluhan servis | `report-tracking-keluhan.php` | Monitoring |
| 33 | History kendaraan/pelanggan | `kendaraan-history.php`, alur history servis | Data history |
| 34 | Work order servis | `workorder-list.php`, `workorder-print.php` | Dokumen/proses servis |
| 35 | Retur servis | `retur_servis.php` | Proses operasional; report cetak khusus belum ditemukan |

### 4. Report kasir yang dipakai

| No | Fitur aktif FM Web Base | FM Web Base | Kategori |
|---:|---|---|---|
| 36 | Kas awal | `_keuangan/kasir/kas_awal.php` | Proses kasir |
| 37 | Transaksi kasir | `_keuangan/kasir/index_kasir.php`, `view_transaksi.php` | Monitoring transaksi |
| 38 | Kas akhir dan closing | `_keuangan/kasir/kas_akhir.php`, `closing.php` | Proses kasir |
| 39 | Setoran keuangan | `_keuangan/kasir/setoran_keuangan_cs.php`, `setoran_keuangan.php` | Proses kasir |
| 40 | Export closing/setoran | `_keuangan/kasir/export/export_pdf_closing_kasir.php`, `export_pdf_setoran.php` | Dokumen/export |

## B. Report Access yang benar-benar teridentifikasi

Objek Report Access yang berhasil ditemukan dan relevan dengan operasional:

| Report Access | Fungsi |
|---|---|
| `RPT_NOTA_SERVIS` | Nota servis |
| `RPT_SURAT_AMBILMOTOR` | Surat pengambilan kendaraan |

Report Access berikut juga ada, tetapi termasuk report info/rekap lanjutan dan belum dimasukkan sebagai report transaksi harian:

- `RPT_INFO_TUNEUP_REKAP`
- `RPT_INFO_GANTI_OLI`
- `RPT_INFO_GANTI_OLI_LAMA`
- `RPT_INFO_PAJAK_MOTOR`
- `RPT_INFO_TUNEUP_DETAIL`
- `RPT_GRATIS_CUCI_MOTOR`
- `RPT_GRATIS_CUCI_MOTOR_PERIODE`

## C. Kebutuhan yang belum boleh dianggap report aktif

Item berikut berasal dari daftar kebutuhan, tetapi belum terbukti sebagai report yang benar-benar dipakai sehari-hari di MS Access maupun FM Web Base:

1. Dashboard laporan manajemen terpadu.
2. Laporan gabungan seluruh cabang dalam satu tampilan.
3. Laporan tahunan gabungan.
4. Total stok seluruh cabang sebagai report khusus.
5. Penanda stok minimum dan maksimum sebagai report khusus.
6. Report penyesuaian stok item masuk sebagai report khusus Access.
7. Report penyesuaian stok item keluar sebagai report khusus Access.
8. Report stok opname otomatis sebagai report cetak khusus.
9. Report servis garansi/komplain sebagai report cetak khusus.
10. Surat pengambilan kendaraan di FM Web Base sebagai report khusus.
11. Report cetak penerimaan antar cabang khusus.
12. Report cetak retur servis khusus.

Sesuai arahan, report baru hanya perlu dibuat apabila ada permintaan resmi dan memang akan digunakan.

## D. Jawaban untuk audit servis

Daftar servis yang berjumlah 8 item adalah daftar kebutuhan/fungsi, bukan 8 report cetak harian.

Yang benar-benar dipakai sehari-hari di FM Web Base:

- Nota servis.
- Input dan lihat servis reguler.
- Input dan lihat servis garansi.
- Antrian/progres servis.
- Tracking keluhan.
- History kendaraan/pelanggan.
- Work order.
- Retur servis.

Dari 8 item kebutuhan servis:

- **Nota servis**: report cetak utama dan Access memiliki `RPT_NOTA_SERVIS`.
- **History, rekap, detail, dan progres**: halaman/data/monitoring, bukan semuanya report cetak.
- **Form servis kosong**: form input, bukan report.
- **Form servis terisi**: dokumen dari proses servis umum.
- **Report garansi/komplain khusus**: belum ditemukan sebagai report cetak khusus.

## Kesimpulan

FM Web Base sudah memiliki banyak report dan halaman operasional yang dipakai sehari-hari. Namun daftar kebutuhan tidak boleh diperlakukan sebagai daftar report yang sudah ada.

Report yang layout-nya baru dibandingkan berdasarkan contoh adalah **Bukti Pembayaran Hutang** dan statusnya **sesuai struktur**. Layout report lain masih perlu screenshot/hasil cetak MS Access yang sama sebelum dapat dinyatakan sesuai.

Tidak ada perubahan pada file PHP dalam audit ini.

---

## E. Update 2026-09-11 — Daftar Lengkap Report Cetak Transaksi Harian (bukan rekap)

Scope: **hanya dokumen cetak per-transaksi** (nota/bukti/struk yang keluar tiap kali 1 transaksi diproses). Report rekap/info/query (mingguan, bulanan, monitoring) sengaja **tidak dimasukkan** — ditunda untuk kebutuhan Crystal Report tahap berikutnya.

### E.1 Sumber bukti

1. **`SISTEM INFORMASI BENGKEL FIT MOTOR.docx`** — 473 baris teks (`_docx_text_audit.txt`) + 74 gambar screenshot di dalamnya (`word/media/image*.png`, diekstrak & dipetakan ke teks terdekat). Dokumen ini adalah **spek kebutuhan FM Web Base** ("KEBUTUHAN PROGRAM BENGKEL"), bukan dokumentasi Access — jadi baris "belum tersedia" di dalamnya berarti belum ada di sistem manapun saat itu.
2. **Objek Report native Access**, dicek langsung pakai `mdb-tables -t report` ke 3 file:
   - `Bengkel2.0.mde` (`C:\Program Files (x86)\MySoftBiz\Bengkel 2.0\`) → **0 objek Report, 0 tabel data bisnis** (cuma 5 tabel config/temp). Ini cangkang UI kosong, bukan sumber data aktif.
   - `FITMOTOR APP.mdb` (`D:\BENGKEL 2.0\`) → **9 objek Report**: `RPT_NOTA_SERVIS`, `RPT_SURAT_AMBILMOTOR` (transaksional), sisanya 7 report info/rekap (oli, pajak, tuneup, cuci gratis — di luar scope).
   - `FITMOTOR.mdb` → 4 objek Report, semua kategori info/rekap.
3. **File Crystal Report (`.rpt`) berdiri sendiri** di folder `D:\BENGKEL 2.0\REPORT ...` dan `D:\BENGKEL 2.0\` — ditemukan lewat `find -iname "*.rpt"` lalu difilter manual buang yang rekap/mingguan/bulanan/monitoring.
4. **Tidak ditemukan** referensi Crystal Report (string `craxdrt`/`.rpt`) di dalam `Bengkel2.0.mde` maupun `FITMOTOR APP.mdb` — artinya file `.rpt` yang ditemukan di folder REPORT **belum terbukti kepanggil dari aplikasi manapun** yang saya cek; kemungkinan dibuka manual di Crystal Reports Designer, atau dipanggil dari exe/project lain yang belum ditemukan.

### E.2 Daftar lengkap: transaksi harian, Access vs FM Web Base

| No | Transaksi | Access — objek/file ditemukan | Access — status | FM Web Base — file | Layout dibandingkan? | Hasil |
|---:|---|---|---|---|---|---|
| 1 | **Nota Servis** (Faktur Service) | `RPT_NOTA_SERVIS` (Access native, di `FITMOTOR APP.mdb`) | Terkonfirmasi objek + terkonfirmasi visual (docx image32+image30, 2 halaman) | `servis-reguler-struk.php`, `servis-print.php` | **Ya, field-by-field** | **TIDAK SESUAI** — web hilang 6 field yang ada di Access: kolom **User**, **Pemilik**, **Km**, **Km Berikut**, **Keluhan** (teks keluhan pelanggan), dan footer **Halaman X dari Y**. Tabel Jasa/Barang & footer pembayaran (Pot.Faktur/Total Akhir/Pajak/Bayar/Kembali) sudah sama. |
| 2 | **Surat Pengambilan Kendaraan** | `RPT_SURAT_AMBILMOTOR` (Access native) | Terkonfirmasi objek, belum ada screenshot di docx | — | Tidak ada file setara | **BELUM ADA** di FM Web Base. Gap murni, dari dulu belum diimplementasikan di web. |
| 3 | **Nota Antar Cabang** | `NOTA ANTAR CABANG.rpt` (Crystal, di `REPORT BENGKEL\HARIAN\` & `REPORT PENGADAAN\`) | File ditemukan + terkonfirmasi visual (docx image1) | `pengadaan_antarcab_print.php` | **Ya, field-by-field** | **STRUKTUR BEDA BY DESIGN** — Access: model "jual ke cabang lain" (kolom Bayar/Tipe/Harga Jual, ke "Pelanggan"). Web: model transfer stok internal (kolom HPP, split Req/Kirim/Terima, 3 kolom tanda-tangan Pengirim/Ekspedisi/Penerima). Web lebih lengkap — ini hasil redesain modul Pengadaan yang disengaja (lihat riwayat kerja Fase 4 Pengadaan), **bukan direkomendasikan untuk dikembalikan ke pola Access**. |
| 4 | **Bukti Pembelian Barang** | `BUKTI PEMBELIAN BARANG.rpt` (Crystal, ada 2 salinan) | File ditemukan, docx cuma nyimpen screenshot form pencarian (image60) — bukan hasil cetak | `pembelian_cetak.php`, `pembelian_struk.php` | Tidak — gak ada screenshot cetakan asli di docx | **Layout belum bisa dibandingkan.** Kedua sistem sama-sama punya fitur cetak; isi field belum diverifikasi sama. |
| 5 | **Nota/Struk Penjualan** (kasir ke pelanggan) | Tidak ditemukan objek Report Access maupun file `.rpt` Crystal khusus | **Belum terkonfirmasi** — kemungkinan dicetak langsung ke printer struk lewat kode VBA (bukan lewat objek Report bernama) | `penjualan_cetak.php`, `penjualan_struk.php` | Tidak bisa | Gak ada bukti pembanding dari sisi Access. Perlu konfirmasi langsung ke user/operator cabang gimana struk ini dicetak di lapangan. |
| 6 | **Transaksi Harian Kasir** | `TRANSAKSI HARIAN KASIR.rpt`, `TRANSAKSI HARIAN.rpt`, `TRANSAKSI HARIAN (JUAL SERVIS).rpt` (Crystal) | File ditemukan, docx gak nyimpen screenshot hasil cetaknya (cuma tabel master kode akun, image55) | `_keuangan/kasir/index_kasir.php`, `view_transaksi.php` | Tidak | **Ambigu** — nama file mengindikasikan rekap harian (daftar semua transaksi 1 hari), bukan struk per-transaksi tunggal. Kalau ini rekap, di luar scope audit ini (masuk kategori "rekap/laporan" yang ditunda). Perlu konfirmasi user. |
| 7 | **Bukti Pembayaran Hutang** | Tidak ditemukan objek Report/file `.rpt` spesifik | Belum terkonfirmasi | `pembayaran_hutang_struk.php`, `pmby_hutang_print.php` | Ya (dari audit 2026-09-10, berdasar contoh) | **Sesuai struktur** — sudah dicatat sebelumnya, pixel-perfect belum diverifikasi. |
| 8 | **Bukti Pembayaran Piutang** | Tidak ditemukan | Belum terkonfirmasi | `pembayaran_piutang_struk.php` | Tidak | Layout belum dibandingkan. |
| 9 | **Bukti Stok Masuk** (penyesuaian item masuk) | Docx eksplisit: *"Report untuk Item Masuk (Selisih Lebih) belum tersedia"* (baris 331). Screenshot terkait (image15/31/23) cuma app mobile input SO, bukan report cetak | **Terkonfirmasi tidak ada** di Access sama sekali | `stok_masuk_struk.php`, `stok_masuk_add_cetak.php` | — | **Web LEBIH MAJU dari Access** — FM Web Base sudah punya cetakan ini, Access dari dulu belum pernah punya. Bukan gap, justru pencapaian. |
| 10 | **Bukti Stok Keluar** (penyesuaian item keluar) | Docx eksplisit: *"Report untuk Item Keluar (Selisih Kurang) belum tersedia"* (baris 341) | **Terkonfirmasi tidak ada** di Access sama sekali | `stok_keluar_struk.php`, `stok_keluar_add_cetak.php` | — | **Web LEBIH MAJU dari Access**, sama seperti poin 9. |
| 11 | **Bukti Stok Opname Masuk/Keluar** | Tidak ditemukan report khusus, cuma app mobile SO | Tidak ada | `so-item-masuk-struk.php`, `so-masuk-add-cetak.php`, `so-keluar-add-cetak.php` | — | Web sudah lebih lengkap dari Access untuk bagian ini. |
| 12 | **Work Order Servis** | Tidak ada report WO terpisah — form Perintah Kerja tergabung dalam form servis (docx image21 "FORM SERVIS KOSONG", image41 "FORM SERVIS TERISI") | Tidak ada objek Report terpisah | `workorder-print.php`, `workorder-list.php` | — | Web punya cetakan WO tersendiri, Access tidak. Bukan gap. |
| 13 | **Retur Servis** | Tidak ditemukan report khusus | Belum terkonfirmasi | `retur_servis.php` | — | Report cetak retur servis khusus belum ditemukan di kedua sisi. |

### E.2b Koreksi 2026-09-11 (lanjutan) — gambar tambahan ditemukan

Sapuan kedua ke semua 74 gambar di `docs/summary/SISTEM INFORMASI BENGKEL FIT MOTOR.docx` (sebelumnya cuma sebagian yang dicek) menemukan **5 layout cetak tambahan** yang kelewat di E.2, plus 1 form kertas fisik:

| No | Dokumen | Bukti gambar | Koreksi status |
|---:|---|---|---|
| 14 | **Bukti Pembayaran Piutang** | image13 (blank template) | Sebelumnya ditulis "tidak ketemu contoh" — **KELIRU, KETEMU**. Layout: header perusahaan, No.Transaksi/Tanggal/Pelanggan/User, tabel No/No.Transaksi/Keterangan/Total, footer tanda-tangan Mengetahui/Penerima. |
| 15 | **Bukti Pembayaran Hutang** | image50 (terisi data asli) | Konfirmasi ulang dgn contoh berisi data nyata (sebelumnya cuma "sesuai struktur berdasar contoh" tanpa gambar tersimpan). Layout sama persis dengan Bukti Pembayaran Piutang (No.Trs/Tanggal/Supplier/User + tabel + TTD). |
| 16 | **Closing Kasir** | image29 | Layout: judul cabang, tanggal/jam, tabel breakdown pecahan uang (Nominal x Keping = Total Nilai) dari 100rb s.d 100, Total Uang di Kasir, Kas Awal, Setoran Real, lalu bagian "Data Sistem Aplikasi" (Data Penjualan, Data Servis, Omset, Pengeluaran, Uang Masuk, Data Setoran, Selisih Setoran). |
| 17 | **Kas Awal Kasir** | image34 | Layout sama pola breakdown pecahan uang (Nominal x Keping = Total Nilai) dgn Closing Kasir, versi lebih sederhana (cuma Total Nilai Kas Awal). |
| 18 | **Kas Masuk** | image20 | Tabel No/Keterangan/Jumlah + Total Kas Masuk. |
| 19 | **Pengeluaran Kasir** | image14 | Tabel No/Keterangan/Jumlah/Status(kode akun)/Bulan Alat/Keterangan kode akun + Total Pengeluaran. |
| 20 | **Form Servis (kertas fisik)** | image21 (blank/cetak kosong), image41 (foto form terisi tangan) | **Bukan report dari Access** — ini form kertas dicetak kosong lalu diisi manual tangan oleh CS/mekanik sebelum diinput ke sistem. Field: Nmr Register, Nopol/Thn Pajak, Tanggal, Merk/Tipe/Jenis, Mekanik yg Garap (%), Warna, KM, Nama Pemilik, Alamat, No HP/WA, Pengerjaan, Keluhan, tabel checklist sparepart per kategori servis (Servis Rutin Standar/CVT/Gurah/Jasa Lain) dgn kolom Kode/Estimasi Harga/Ya-Tdk/Keterangan. |

**Koreksi tambahan**: `image36` (kalender booking appointment) yang sempat dicek **BUKAN dari sistem Access Bengkel Fit Motor** — itu screenshot software lain ("Bee Accounting versi 2.9 Platinum", software akuntansi pihak ketiga, kemungkinan referensi UI dari BA). Tidak dimasukkan sebagai bukti Access.

**Koreksi validasi Item Masuk/Item Keluar**: dicek juga form input Access-nya langsung (image31 "Daftar Item Masuk", image35 "Daftar Item Keluar") — **toolbar form ini TIDAK punya tombol Cetak sama sekali** (bandingkan dgn form Pembayaran Hutang/Piutang yang punya tombol Cetak). Ini memperkuat, bukan membantah, kesimpulan sebelumnya: Item Masuk/Keluar memang dari dulu tidak pernah bisa dicetak di Access.

**Pengecekan field Kas Awal ke kode web**: `app/_keuangan/kasir/kas_awal.php` dikonfirmasi punya breakdown pecahan uang (`keping_closing_kasir`, tabel `detail_kas_awal`) — polanya cocok dengan layout Access (Nominal x Keping). Fitur ini juga sudah lolos UAT E2E langsung (lihat riwayat kerja Kasir 2026-09-05).

### E.2c Koreksi 2026-09-11 (sapuan ketiga) — 3 temuan besar, 1 di antaranya KOREKSI KLAIM SEBELUMNYA SALAH

Diminta user cek ulang lagi karena masih ada gambar belum diperiksa. Sapuan ketiga ke sisa ~39 gambar yang belum dicek menemukan 3 dokumen transaksi nyata, 2 di antaranya adalah **koreksi atas kesimpulan SALAH di E.2/E.2b**:

| No | Dokumen | Bukti gambar | Koreksi |
|---:|---|---|---|
| 21 | **Faktur Penjualan (Nota Penjualan)** | image2 (hasil cetak asli, No.Transaksi PJ22000001313) + image10 (form input transaksi, tombol F12 Cetak) | **KLAIM SEBELUMNYA SALAH.** Di E.2 baris "Struk Penjualan (kasir)" ditulis "Tidak ditemukan objek Report Access maupun file .rpt Crystal khusus" — INI KELIRU. Faktur Penjualan Access **ADA dan ketemu contoh cetakannya**. Layout: header perusahaan, No.Transaksi/Tanggal/Sales/Pelanggan/Alamat/User, tabel No/Kode Item/Nama Item/Jumlah/Satuan/Harga/Pot%/Total, Sub Total/Total Netto/Bayar-DP/Kembali, catatan "Barang yang telah dibeli tidak dapat dikembalikan, kecuali ada perjanjian.", tanda-tangan Mengetahui/Penerima, footer Halaman X dari Y. |
| 22 | **Surat Perintah Pengambilan Motor (Jemput Antar)** | image27 (hasil cetak asli) + tombol "CETAK JEMPUT ANTAR" terlihat di image37 | **DOKUMEN BARU, tidak tercatat sama sekali sebelumnya.** Ini surat instruksi buat mekanik/kurir menjemput motor pelanggan (bukan surat serah-terima motor selesai servis). Layout: judul "SURAT PERINTAH PENGAMBILAN MOTOR", info cetak (tanggal/jam) + kontak 3 cabang, Tanggal/Jam Ambil/No Servis, Nama/No Polisi/Merek/Tipe/Jenis, Pengerjaan, Alamat, Patokan (lokasi Maps), Keterangan. |
| 23 | **Surat Perintah Kerja (SPK)** | image40 — layar transaksi servis (SV23000000394) punya 2 tombol cetak terpisah: **"Cetak Surat Perintah Kerja"** dan **"Cetak Faktur/Nota"** (shortcut F12 = Cetak SPK, CTRL+F12 = Cetak Faktur/Nota) | **KLAIM SEBELUMNYA SALAH.** Di E.2 baris "Work Order Servis" ditulis "Tidak ada report WO terpisah — form Perintah Kerja tergabung dalam form servis" — INI KELIRU. Access **punya tombol cetak SPK terpisah dari Nota**, dipanggil langsung dari layar transaksi servis. String konfirmasi juga ketemu langsung di `Bengkel2.0.mde`: `"1;Surat Perintah Kerja;2;Faktur / Nota;"`. Belum ketemu contoh hasil cetak SPK-nya (baru ketemu tombolnya), tapi keberadaan fiturnya terkonfirmasi. |

**Dampak ke kesimpulan sebelumnya:**
- Baris "Struk Penjualan (kasir)" di E.3 yang tadinya masuk kategori "Belum bisa disimpulkan" → **naik jadi bisa dibandingkan langsung**, karena contoh Access sudah ada.
- Baris "Work Order/Perintah Kerja" yang tadinya "SISTEM BARU LEBIH LENGKAP" (Access dianggap gak punya) → **klaim ini SALAH, dicabut**. Access sudah punya sejak awal, cuma print terpisah bukan report Crystal/objek Access bernama — dipanggil langsung dari tombol form transaksi.
- **Surat Perintah Pengambilan Motor (Jemput Antar)** ditambahkan sebagai item baru — belum dicek statusnya di FM Web Base, perlu diverifikasi terpisah dari "Surat Pengambilan Kendaraan" (RPT_SURAT_AMBILMOTOR) yang beda konteks (yang itu kemungkinan surat serah-terima motor SELESAI servis, bukan jemput-antar).

**Pelajaran metodologi**: audit gambar docx sebelumnya (E.2, E.2b) berhenti terlalu dini sebelum semua 74 gambar diperiksa satu-satu, menghasilkan 2 kesimpulan salah yang keburu ditulis sebagai fakta. Sapuan ketiga ini memeriksa SEMUA sisa gambar sampai tuntas.

### E.4 Update 2026-09-15 — Tindak Lanjut Prioritas E.3

1. **No. 1 (Nota Servis) — SELESAI.** 6 field yang hilang (User, Pemilik, Km, Km Berikut, Keluhan, footer Halaman X dari Y) sudah ditambahkan ke `servis-reguler-struk.php` (commit `4af23c2`, 2026-09-12) + fix pecah halaman jadi 1 halaman (commit `2314fc1`, 2026-09-12). Status baris No.1 di tabel E.2 di atas basi — bukan "TIDAK SESUAI" lagi, sekarang SESUAI.
2. **No. 21 (Faktur Penjualan) — dibandingkan field-by-field ke `penjualan_struk.php`.** Semua field Access sudah ada (header perusahaan, No.Transaksi/Tanggal/Sales/Pelanggan/Alamat/User, Sub Total/Total Netto/DP/catatan retur, TTD Mengetahui/Penerima, footer Halaman X dari Y) **kecuali kolom Satuan di tabel item** — ini sudah **DIFIX** hari ini (kolom Satuan ditambahkan, ambil `tblitem.satuan`, colspan 8→9). Sisi lain (kolom Kembali di Access vs Kekurangan di web) BUKAN gap, beda konsep sengaja (retur/tunai vs kredit tempo) — tidak diubah.
3. **No. 22 (Surat Perintah Pengambilan Motor/Jemput Antar) — BUKAN GAP, sudah dicek.** FM Web Base sudah punya `sp-ambil-motor.php`, field-nya cocok sama layout Access (Tanggal/Jam Ambil/No Servis, Nama/No Polisi/Merek/Tipe/Jenis/Warna, Pengerjaan, Alamat, Patokan, Keterangan, TTD Petugas Jemput/Pemilik Motor). Klaim "belum dicek statusnya" di E.2c poin 218 sekarang terjawab: sudah ada, sesuai.
4. **No. 23 (SPK vs `workorder-print.php`) — masih OPEN, belum bisa dikerjakan.** Bukti cetak asli SPK dari Access belum ketemu (baru tombolnya doang, isi field belum diverifikasi). Butuh screenshot hasil cetak SPK asli dari operator cabang dulu sebelum bisa dibandingkan field-by-field — sama seperti No.4 dan No.13 di bawah.

### E.5 Update 2026-09-15 (lanjutan) — No.2 diputuskan SELESAI, tercover No.22

Rafi konfirmasi: No.2 (Surat Pengambilan Kendaraan / `RPT_SURAT_AMBILMOTOR`) dan No.22
(Surat Perintah Pengambilan Motor / Jemput Antar) **1 dokumen yang sama** di operasional
riil — bukan 2 dokumen terpisah seperti asumsi awal di E.2/E.2c. Bukti: screenshot
dokumen produksi asli (dikirim Rafi) judulnya "SURAT PERINTAH PENGAMBILAN MOTOR",
field: Tanggal, Nama (+telpon), No Polisi, Merek, Tipe, Pengerjaan (isi keluhan),
Alamat, Keterangan — semua field itu sudah ada di `sp-ambil-motor.php` (malah lebih
lengkap: ada Jam Ambil, No Servis, Telpon terpisah, Jenis, Warna, Patokan terpisah dari
Alamat, foto kondisi motor, kotak TTD Petugas Jemput/Pemilik Motor).

**Kesimpulan: No.2 SELESAI, gak perlu dokumen baru.** `sp-ambil-motor.php` sudah
superset dari field yang dipakai riil di lapangan. Dicabut dari daftar gap.

Belum ada entry khusus di checklist-projek buat dokumen ini (dicek `GET
/api/web-base/features`, gak ketemu yang cocok) — kalau Rafi mau ditrack terpisah,
tinggal minta buatkan entry baru.

Sisa item yang masih butuh input dari luar kode (bukan bisa dikerjakan sendiri):
- No. 6 (Transaksi Harian Kasir) — Rafi akan buka file `.rpt` sendiri & kirim hasilnya; dieksekusi setelah itu.
- No. 4 (Bukti Pembelian Barang), No. 13 (Bukti Retur Servis) — butuh screenshot hasil cetak asli dari operator cabang.

### E.3 Ringkasan prioritas (revisi setelah E.2c)

- **Perbaikan konkret & aman dikerjakan sekarang**: No. 1 (Nota Servis) — tambah 6 field yang hilang. Dokumen resmi yang dicetak ke pelanggan tiap hari, field keluhan/km penting buat riwayat servis.
- **Gap murni, belum pernah dikerjakan**: No. 2 (Surat Pengambilan Kendaraan) — butuh keputusan Rafi, apakah masih dipakai secara operasional sebelum dibangun. No. 22 (Surat Perintah Pengambilan Motor/Jemput Antar) — statusnya di FM Web Base belum dicek, perlu ditelusuri terpisah.
- **Perlu dibandingkan field-by-field** (bukti Access sudah lengkap, tinggal dicocokkan ke web): No. 21 (Faktur Penjualan vs `penjualan_cetak.php`/`penjualan_struk.php`), No. 23 (SPK vs `workorder-print.php`) — dulu dikira gak ada perbandingannya, sekarang buktinya sudah ada.
- **Butuh klarifikasi user dulu, bukan langsung investigasi lanjut**: No. 6 (Transaksi Harian Kasir rekap atau struk).
- **Bukan gap, jangan diubah**: No. 3, 9, 10, 11, 12, Closing Kasir, Kas Awal, Kas Masuk, Pengeluaran Kasir — web sudah sama atau lebih baik dari Access by design.
- **Belum bisa disimpulkan, butuh bukti tambahan** (screenshot hasil cetak asli dari operator cabang): No. 4 (Bukti Pembelian Barang), No. 13 (Bukti Retur Servis).
- **Klaim yang sudah TERBUKTI SALAH dan dicabut**: "Struk Penjualan tidak ketemu di Access" dan "Work Order tidak dicetak terpisah di Access" — keduanya salah, lihat E.2c.
