# SOP Persediaan Moving Average — FIT MOTOR

Dokumen untuk staf cabang, Kepala Cabang, Finance HQ, dan IT. Sistem: menu **Persediaan (Moving Average)** di web base.
Status saat ini: ledger berjalan paralel (mode `shadow`) atau mati (`off`). Aturan di bagian 2-4 mulai mengikat per cabang saat cabang itu `live`.

## 1. Cara kerja singkat
- Harga pokok (HPP) memakai **rata-rata bergerak per lokasi**. Barang masuk mengubah average, barang keluar memakai average saat itu dan tidak mengubahnya.
- Stok tidak boleh minus. Penjualan atau servis ditolak bila stok tidak cukup.
- Tanggal transaksi = tanggal input sistem. Tidak ada tanggal mundur.
- Transaksi yang sudah tersimpan **tidak diedit dan tidak dihapus**. Kesalahan diperbaiki dengan koreksi ke depan.

## 2. SOP koreksi (pengganti edit/hapus)
Semua lewat **Persediaan, Koreksi (Maker-Checker)**. Alasan wajib (minimal 5 karakter), selalu dicatat di audit (siapa, kapan, alasan, sebelum, sesudah).

| Kesalahan | Jenis pengajuan | Yang dicatat sistem |
|---|---|---|
| Qty salah, selisih hitung, barang ditemukan | Stock Adjustment (+/-) | Barang keluar pada average, atau masuk pada average/biaya yang diisi |
| Harga faktur pembelian salah | Koreksi harga pembelian (isi no pembelian asal dan harga benar) | Sisa barang: selisih masuk ke average. Yang sudah terjual: masuk Selisih Harga Pembelian, HPP lama tidak berubah |
| Barang hilang, rusak, scrap, pemakaian internal | Barang hilang/rusak/scrap (berita acara wajib) | Keluar pada average, nilainya menjadi beban |
| Nilai persediaan perlu disesuaikan | Revaluasi (hanya Finance HQ) | Average baru, qty tetap |
| Salah tanggal | Catat di keterangan koreksi (tanggal koreksi = hari ini) | Tanggal transaksi asli tidak diubah |
| Salah lokasi | Lakukan transfer antar lokasi | Dua sisi tercatat, tidak ada edit |
| Barang cacat dari supplier | Klaim supplier | Keluar dari stok, tercatat Piutang Klaim Supplier sampai settled atau ditolak |

## 3. Maker-checker
1. **Pengaju** (ADM/staf berwenang) mengisi form dan mengirim. Stok belum berubah.
2. **Penyetuju** orang lain (Manajemen atau Finance HQ) membuka antrian, memeriksa bukti, lalu **Setujui** atau **Tolak** dengan catatan wajib.
3. Pengaju **tidak bisa** menyetujui pengajuannya sendiri. Revaluasi hanya bisa diajukan dan disetujui yang punya izin Finance HQ.
4. Setelah disetujui, koreksi langsung diterapkan. Bila penerapan gagal (mis. stok kurang), status `failed` dan alasannya tertulis, ajukan ulang.

Target waktu persetujuan: 1 hari kerja. Antrian yang menumpuk dilaporkan ke Manajemen.

## 4. Stock opname
1. Hitung fisik per lokasi pada hari yang sama, catat qty per item.
2. Ajukan selisih lewat **Koreksi** (jenis Stock Adjustment, alasan "Opname + tanggal"). Selisih bernilai di bawah ambang otomatis diterapkan; ambang default Rp0, jadi semua selisih perlu persetujuan.
3. Penyetuju mencocokkan dengan berita acara opname, lalu menyetujui.
4. Selisih besar atau berulang pada item yang sama ditinjau Manajemen.

## 5. Barang dalam perjalanan (transfer antar cabang)
- **Kirim:** stok asal turun pada average asal, barang tercatat "dalam perjalanan".
- **Terima:** stok tujuan naik pada biaya kirim, masuk ke average tujuan. Barang yang sampai rusak diproses sebagai klaim.
- **Gagal kirim/dibatalkan:** barang kembali ke asal pada biaya semula.
- Transit lebih dari 3 hari muncul merah di **Transit, PPV, Klaim, HPP**. Kepala Cabang penerima wajib menindaklanjuti.

## 6. Pembelian dengan bonus supplier
Harga efektif tiap barang = total faktur dibagi total qty (termasuk barang bonus). Input bonus sebagai baris dengan harga sesuai alokasi; total alokasi harus sama dengan total faktur.

## 7. Tugas IT harian/mingguan
- Harian: `php scripts/inv_harian.php --jurnal` (integritas, rekonsiliasi shadow, jurnal tertunda, transit lewat batas). Kode keluar 1 berarti ada yang perlu ditinjau.
- Pantau halaman **Pengaturan & Rekonsiliasi**: transaksi shadow ditolak dan selisih per cabang.
- Backup database sebelum perubahan mode atau muat opening stock.

## 8. Panduan cepat per peran
| Peran | Yang dilakukan |
|---|---|
| Kasir/Servis | Bekerja seperti biasa. Bila ditolak "stok tidak cukup", cek stok tersedia di pesan, jangan paksa tanggal/edit |
| Admin (ADM) | Mengajukan koreksi dan opname, memuat opening stock saat cutover |
| Kepala Cabang/Manajemen | Menyetujui koreksi cabangnya, menindaklanjuti transit lewat batas |
| Finance HQ | Menyetujui revaluasi, meninjau Selisih Harga Pembelian dan Piutang Klaim, mengisi kode akun COA |
| IT | Menjalankan migrasi, mengatur mode cabang, memantau skrip harian |
