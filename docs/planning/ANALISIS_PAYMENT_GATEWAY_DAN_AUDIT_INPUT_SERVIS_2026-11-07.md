# Audit Input Modul Servis + Analisa Payment Gateway (2026-11-07)

Status: audit statis + uji handler asli lewat CLI (session buatan, DB lokal, data uji `ZZTEST-*` sudah dihapus). Uji klik di browser tidak dilakukan karena sesi browser belum login; handler yang sama (halaman asli, POST asli) dijalankan lewat PHP CLI dan hasilnya dicek di DB.

### Hasil uji handler (CLI, DB lokal)
| Input | Hasil sebelum perbaikan | Hasil sesudah |
|-------|------------------------|---------------|
| Tambah keluhan 300 karakter (reguler, jemput, garansi) | Hilang diam-diam (>255) | Tersimpan 255 karakter |
| Keluhan/WO "tidak selesai" + keterangan 300 karakter dan tanda petik (`O'Neil`) | `catatan` tidak tersimpan (kolom tidak ada); setelah kolom ada, tanda petik menjadi `\'` (escape ganda) | `keterangan_tidak_selesai` 255 karakter, `catatan` berisi teks bersih |
| Simpan header servis (`btnsimpan`) tanpa field `keluhan` | **`keterangan` ditimpa kosong** ("ket awal" menjadi "") | `keterangan` tetap, KM tersimpan |
| Part pelanggan (nama ber-apostrof, qty 2, Rp 25.000) | Lulus | (tidak berubah) total 50.000, keterangan tersimpan |

## 1. Audit input servis: hasil

### Sudah diperbaiki (lokal, belum commit)
| # | Temuan | Dampak | Perbaikan |
|---|--------|--------|-----------|
| 1 | Kolom `tblservice.catatan` tidak ada di skema, tetapi 8 query di `app/_handler_status_keluhan_wo.php` (baris 107, 120, 180, 187, 230, 243, 328, 343) dan `app/servis-input-reguler-jemput.php:1908` membaca/menulis kolom itu. `mysqli_report` OFF, jadi gagal diam-diam. | Saat keluhan/WO ditandai "tidak selesai", alasan tidak masuk ke catatan servis. Layar tetap menampilkan "berhasil". Alasan masih tersimpan di `tbservis_keluhan_status.keterangan_tidak_selesai`. | Migrasi baru `db/migrations/2026-11-07_run_tblservice_catatan.php [up|down]` (reversible, idempotent). Sudah dijalankan di DB lokal. **Produksi belum.** |
| 2 | Keluhan tambahan (`txtkeluhan`) tanpa batas panjang. Kolom `tbservis_keluhan_status.keluhan` varchar(255), `INSERT` tidak dicek. | Keluhan >255 karakter ditolak MySQL strict mode dan hilang diam-diam. | Potong 255 karakter di `servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`; `maxlength=255` di `_template/panel-kiri-kasir.php`. |
| 3 | `keterangan_keluhan` dan `keterangan_wo` ("tidak selesai") ke kolom varchar(255) tanpa batas. | Sama dengan #2. | Potong 255 di `_handler_status_keluhan_wo.php`. |
| 4 | `servis-input-reguler.php` `btnsimpan` (update) menimpa `tblservice.keterangan` dengan kosong karena form reguler tidak mengirim `keluhan`. **Terbukti lewat uji CLI.** | Keluhan awal servis hilang setiap kali header disimpan. | `keterangan` hanya diupdate jika `keluhan` dikirim (di-escape, dipotong 100). `INSERT` baru juga di-escape dan dipotong 100 (sebelumnya rawan injection). |
| 5 | Catatan "tidak selesai" memakai teks yang sudah di-escape lalu di-escape lagi. **Terbukti.** | Catatan menyimpan `\'` dan backslash. | Pakai teks mentah di `_handler_status_keluhan_wo.php` (keluhan dan WO). |

| 6 | `btnreject_wo_item` menulis catatan penolakan ke `tblservice.keterangan` varchar(100) (gagal jika panjang) dan escape ganda. **Terbukti.** | Catatan tolak item hilang atau berisi `\'`. | Ditulis ke kolom `catatan` dengan teks mentah. Catatan ini hanya tampil di tab riwayat servis sebelumnya, bukan di header servis. |
| 7 | `btntolakitem` escape ganda pada `keterangan_tolak`. **Terbukti.** | Catatan berisi `\'`. | Teks mentah. |
| 8 | Edit item/paket (6 file `servis_edit_*`) menerima qty negatif dan potongan 150%. **Terbukti** (qty -3, potongan 150 menghasilkan total 15.000). | Total servis ngawur. | Clamp: qty ≥ 1, potongan 0-100, harga ≥ 0. Diuji ulang: qty 1, potongan 100, total 0. |

### Lulus tanpa perubahan
- Batal servis (`servis-cancel-proses.php`): status jadi `cancel`, alasan dengan tanda petik tersimpan, log batal tercatat.

### Belum diuji
- Kirim invoice WA (`servis-send-invoice-wa.php`): sengaja tidak dijalankan karena mengirim pesan nyata ke pihak luar.
- Uji klik browser sungguhan.
- Banyak `mysqli_query` penyimpan (INSERT keluhan, INSERT part pelanggan baris 1276) tidak dicek hasilnya, sehingga kegagalan tidak pernah sampai ke pengguna. Pola yang sama dengan bug 30 September.
- Item "part pelanggan" disimpan dengan `nobaris = 0` tetap. Penghapusan memakai `id` (aman), tetapi urutan baris tidak unik.
- `tbservis_part_customer` tidak ada di skema (tidak dipakai: part pelanggan disimpan di `tblservis_barang.keterangan`).

### Yang sudah benar
- Pembayaran servis memvalidasi `bayar >= total bersih - DP` di server.
- Kepala mekanik, admin, dan mekanik wajib dan persen harus 100%.
- Bayar tidak bisa jika masih ada keluhan berstatus diproses/belum selesai.
- Upload bukti pembayaran dibatasi ekstensi dan 2 MB.
- Hapus item barang diblokir jika servis sudah `bayar`/`selesai`.

## 2. Payment gateway: analisa

### Kondisi sekarang
- Pembayaran dicatat manual: `bayar_tunai`, `bayar_transfer`, `bayar_qris`, `ref_transfer` (teks bebas), `bukti_pembayaran` (upload foto). Metode campuran didukung. Tidak ada verifikasi otomatis bahwa uang masuk.
- Satu klik Bayar menjalankan sekaligus: potong stok (ledger Moving Average, atomik), komisi, statistik pelanggan, dan penutupan servis. Logika ini tertanam langsung di tiga halaman besar (reguler 180 KB, jemput 167 KB, garansi 78 KB).
- Belum ada kode integrasi gateway apa pun.

### Yang berubah jika gateway dipasang
1. **Status "menunggu pembayaran".** Saat ini bayar langsung final. Dengan gateway, ada jeda antara pelanggan membayar dan konfirmasi (webhook). Dibutuhkan status baru dan tabel `payment_transaction` (order_id, cabang, jumlah, status, payload mentah, idempotency key).
2. **Logika finalisasi harus dipisah ke satu fungsi.** Webhook harus memanggil proses yang sama dengan tombol Bayar (stok, komisi, statistik). Ini pekerjaan terbesar: refactor tiga halaman besar. Tanpa refactor, ada risiko dua jalur berbeda (kasir klik vs webhook) yang tidak konsisten.
3. **Order ID harus memakai `no_service` + `kd_cabang`.** `no_service` tidak unik lintas cabang (30 ribu lebih duplikat tercatat).
4. **Endpoint webhook publik dengan HTTPS.** Produksi di shared hosting bisa menerima callback. Laragon lokal tidak bisa (perlu tunnel untuk uji). Verifikasi tanda tangan wajib, proses harus idempotent (webhook bisa terkirim ganda).
5. **Kunci API lewat environment variable**, bukan di repo (aturan project).
6. **Rekonsiliasi.** Dana QRIS/VA masuk ke rekening penyedia lalu settlement ke bank (biasanya H+1 atau lebih). Perlu laporan selisih terhadap Setoran/Closing Kasir. Alur kas fisik di Keuangan Kasir tidak berlaku untuk dana ini.
7. **Kasus tepi:** pembayaran kedaluwarsa, bayar kurang/lebih, batal servis setelah dibayar (refund), DP servis, bayar campuran tunai + QRIS, garansi (tanpa bayar), retur.

### Opsi, dari ringan ke berat
| Opsi | Isi | Usaha (perkiraan kasar) | Catatan |
|------|-----|------------------------|---------|
| A. Status quo + rapikan | QRIS statis toko, kasir cek mutasi/aplikasi lalu isi referensi dan bukti. Wajibkan `ref_transfer` untuk metode non-tunai, cegah kembalian pada transfer/QRIS. | Kecil | Tidak ada risiko integrasi. Tetap manual. |
| B. QRIS dinamis, konfirmasi kasir | Sistem membuat QR dengan nominal tepat, kasir melihat status lalu menekan Bayar. Tanpa webhook, hanya polling status. | Sedang | Tetap butuh akun penyedia, tapi finalisasi tetap lewat tombol yang sudah ada. |
| C. Otomatis penuh (webhook) | Webhook menyelesaikan servis. Mencakup poin 1-7 di atas. | Besar (refactor finalisasi + tabel + rekonsiliasi + uji sandbox) | Paling nyaman bagi pelanggan, paling berisiko. |

Penyedia umum di Indonesia untuk dibandingkan: Midtrans, Xendit, DOKU, Duitku, iPaymu. Biaya (MDR QRIS, biaya VA, settlement) berubah-ubah dan harus dikonfirmasi langsung dengan penyedia. Angka di dokumen ini sengaja tidak dicantumkan.

### Rekomendasi
Mulai dari Opsi A sekarang (murah, langsung mengurangi salah catat). Lanjut ke Opsi B di satu cabang pilot setelah:
1. fungsi finalisasi pembayaran dipisah dari tiga halaman besar,
2. data admin per cabang lengkap (CIKDITIRO masih kosong),
3. keputusan akun merchant: satu untuk semua cabang atau satu per cabang (menentukan rekonsiliasi),
4. mode Moving Average Live sudah stabil di cabang itu, supaya dua perubahan besar tidak bertabrakan.
Opsi C hanya jika volume non-tunai tinggi dan Opsi B terbukti stabil.

## 3. Sisa pekerjaan audit
- Uji klik browser sungguhan (login oleh Rafi) untuk memastikan UI mengirim field yang sama dengan uji CLI.
- Uji handler yang belum tercakup (lihat daftar "Belum diuji" di atas).
- Jalankan migrasi `2026-11-07_run_tblservice_catatan.php` di produksi bersama migrasi lain yang tertunda.

## 4. Uji browser (login admin lokal, CIKDITIRO) - lulus
Keluhan 300 karakter (input terpotong 255, DB 255); status keluhan "tidak selesai" via modal AJAX (keterangan 255); tolak item penawaran (catatan bersih); Simpan header (KM tersimpan, `keterangan` utuh); part pelanggan (qty 2, total 50.000, apostrof utuh); batal servis (status `cancel`, log tercatat). Perbaikan tambahan: `app/_ajax/update_status_keluhan.php` dipotong 255. Invoice WA sengaja tidak diuji (mengirim pesan nyata). Data uji dihapus.

## 5. Invoice WA
Temuan: `servis-send-invoice-wa.php` mencari servis hanya dengan `no_service` (tidak unik lintas cabang), sehingga dapat mengirim invoice ke pelanggan cabang lain. Diperbaiki dengan filter `kd_cabang` sesi. Diuji (CLI, sebelum cURL): tanpa nomor, cabang lain ("Data service tidak ditemukan"), pelanggan tanpa telepon. Pengiriman nyata tidak diuji karena `WA_API_ENABLED` aktif di lokal dan akan mengirim pesan sungguhan.
