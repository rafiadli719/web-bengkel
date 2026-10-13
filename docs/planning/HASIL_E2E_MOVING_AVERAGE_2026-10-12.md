# Hasil E2E Browser Moving Average vs PRD — 2026-10-12

**Cakupan:** uji end-to-end lewat sesi browser (login, form, dan endpoint halaman asli) di DB lokal, cabang PST dan CIKDITIRO mode `live`, validasi ke `docs/PRD_Moving_Average_Fit_Motor_Webbase_v1.md`.
**Data uji:** item `ZZMA01`/`ZZMA02`, pelanggan `E2EMA1`, servis `SV26E2EMA0001`, 2 akun uji. **Semua sudah dihapus**; `inv_*` kembali kosong seperti baseline (ledger 0, balance 0, mode semua `off`).
**Status review:** belum ada review Sonnet/Opus untuk perubahan kode hari ini. Belum di-commit.

## 1. Hasil uji vs PRD

| # | Skenario (rujukan PRD) | Hasil | Bukti |
|---|---|---|---|
| 1 | Opening stock lewat UI: validasi lalu muat, total nilai awal | LULUS | 2 baris, Rp 1.392.000; PST 100 @10.000, CIKDITIRO 40 @9.800 |
| 2 | Pembelian 50 @12.000 di atas 100 @10.000 (Contoh 1, AC-01, Test 2) | LULUS | average 10.666,67, qty 150, nilai 1.600.000 |
| 3 | Penjualan 30 (Contoh 2, AC-02, Test 3) | LULUS | average tetap 10.666,67, qty 120, HPP snapshot 10.666,67/unit |
| 4 | Penjualan 150 > stok (Contoh 3, AC-03, Test 19) | LULUS | "Stok Barang tidak mencukupi!"; tidak ada baris tersimpan |
| 5 | Retur penjualan 10 (Contoh 4, AC-08, Test 5) | LULUS | harga masuk = HPP asal, average tetap 10.666,67, qty 130 |
| 6 | Retur pembelian 10 (BR-02, Test 4) | LULUS | average tetap, nilai turun qty x average |
| 7 | Transfer PST ke CIKDITIRO 50 (Contoh 5, AC-07) | LULUS sebagian | average tujuan 10.281,48 sesuai rumus; **barang dalam perjalanan tidak tercatat** (lihat F-7) |
| 8 | Koreksi harga: beli 100 @10.000, jual 60, koreksi ke 10.500 (Contoh 6, AC-05, Test 25) | LULUS | average 10.500, nilai +20.000 (40 x 500), PPV 30.000 (60 x 500), HPP jual lama tetap 10.000 |
| 9 | Maker-checker (AC-10, Test 28) | LULUS | maker ADM tidak bisa menyetujui; average baru berubah hanya setelah KEU setuju; audit lengkap |
| 10 | Alasan wajib (Test 21) | LULUS | ajuan tanpa alasan ditolak |
| 11 | Stock adjustment -3 / +5 (Test 23) | LULUS | ledger `ADJ_OUT`/`ADJ_IN` via approval |
| 12 | Periode closed (EC-10, Test 27) | LULUS | penjualan ditolak atomik; adjustment tetap diterapkan |
| 13 | Servis bayar (Test 8) | LULUS setelah perbaikan F-4 | ledger `SERVIS`, snapshot `harga_pokok` di `tblservis_barang`, tidak potong ganda saat bayar ulang |
| 14 | Harga negatif (V-05) | LULUS | "Biaya tidak boleh negatif", rollback total |
| 15 | Qty negatif (V-06) | LULUS di ledger | "Qty harus lebih dari 0", rollback total (keranjang masih menerimanya, F-9) |
| 16 | Harga nol (EC-03) | GAGAL lalu diperbaiki (F-2) | sebelum fix average 10.500 turun ke 9.333,33 |
| 17 | Edit transaksi posted (AC-04, Test 22) | GAGAL lalu diperbaiki (F-1) | sebelum fix harga baris faktur posted berubah ke 99.999 |
| 18 | Terima transfer melebihi kirim | GAGAL lalu diperbaiki (F-3) | sebelum fix 9 diterima padahal 5 dikirim |
| 19 | Laporan: valuasi, kartu stok, HPP, profit penjualan | LULUS | profit membaca snapshot: HPP 320.000 dan 600.000, tidak berubah setelah koreksi |
| 20 | `inv_harian.php`, `inv_precutover.php` | LULUS dengan catatan | lihat F-8 dan F-6 |

Tes CLI: `run_inventory_tests` 40, `run_shadow_adapter_tests` 47, `run_correction_tests` 50, `run_journal_tests` 26, semua lulus (journal sempat 4 gagal saat data uji masih di DB; lulus setelah dibersihkan).

**Catatan ke PRD:**
- Contoh 5 PRD menulis average tujuan Rp10.148,15. Hitungan yang benar `(40 x 9.800 + 50 x 10.666,67) / 90 = 10.281,48`; sistem benar, angka di PRD keliru.
- Penjualan 30 menghasilkan nilai ledger Rp320.000,10 (30 x 10.666,67), bukan Rp320.000. Akibat average dibulatkan 2 desimal (D-08); selisih Rp0,10 per transaksi, saldo nilai bergeser sejalan.
- Adjustment (+) dengan biaya = average menggeser average Rp0,01 (10.666,67 jadi 10.666,66). Efek pembulatan yang sama.

## 2. Temuan dan status

| ID | Temuan | Tingkat | Status |
|---|---|---|---|
| F-1 | `UPDATE` di 11 halaman edit item (pembelian, penjualan, pesanan x3, stok masuk/keluar, tarik pesanan, servis x3) tanpa guard status. Guard `status_trx='0'` hanya ada di `SELECT` tampilan, jadi baris faktur posted bisa ditulis ulang lewat URL (klaim F3-07 di runbook belum benar) | Tinggi | DIPERBAIKI |
| F-2 | Barang masuk harga Rp0 diterima dan menurunkan average (EC-03, V-05) | Tinggi | DIPERBAIKI di `InventoryService::postSekali` untuk `PEMBELIAN`, `DO_TERIMA`, `STOK_MASUK`; opsi `allow_zero_cost` untuk kasus sah |
| F-3 | `pengadaan_antarcab_terima.php` menerima qty melebihi kirim dan memakai `no_item` dari POST, bisa membuat stok fiktif | Tinggi | DIPERBAIKI: item dan batas qty dari detail kiriman |
| F-4 | `servis-reguler-byr.php`: servis tanpa baris jasa, `SUM(waktu)` NULL jadi `total_waktu=''`, `UPDATE` gagal di MySQL strict; status tetap `datang` padahal stok terpotong dan muncul alert sukses | Tinggi | DIPERBAIKI (cast NULL ke 0) |
| F-5 | Status periode (BR-08) tidak punya UI, hanya API | Sedang | DIPERBAIKI: kontrol Periode di Pengaturan (open/closed; locked hanya IT) |
| F-6 | `inv_precutover.php` menandai GAGAL bila periode belum diset, padahal servis menganggapnya open | Rendah | DIPERBAIKI |
| F-7 | **Barang dalam perjalanan tidak pernah tercatat.** `shipTransfer/receiveTransfer/returnTransfer` ada di `InventoryCorrection`, tetapi halaman kirim/terima hanya memakai `TRANSFER_OUT/TRANSFER_IN` biasa. `inv_transit` selalu kosong: AC-06, BR-13, BR-15, V-14, Test 32-35 tidak berjalan; laporan transit kosong | Tinggi | **PLANNING** (bagian 3.1) |
| F-8 | **Ledger tidak diproyeksikan ke `tbstok` untuk `OPENING`, `ADJ`, `SCRAP`.** Pembaca lama (`view_stok_master`, kartu stok lama, MinMax, `stok-akhir.php`) tidak melihatnya. Selisih persis sama dengan opening + adjustment (PST ZZMA01: 104, CIKDITIRO: 40). `inv_harian` dan `inv_precutover` melaporkannya sebagai selisih rekonsiliasi | Tinggi | **PLANNING** (bagian 3.2) |
| F-9 | Keranjang penjualan/pembelian menerima qty 0 atau negatif (total negatif); baru ditolak saat simpan oleh ledger | Rendah | Backlog |
| F-10 | 13 endpoint hapus item tanpa guard login | Sedang | DIPERBAIKI: guard sesi di semua `*_hapus_item` |
| F-11 | Ajuan koreksi qty 0 diterima, baru gagal saat disetujui ("Penerapan gagal") | Rendah | Backlog |
| F-12 | V-13 notifikasi Finance untuk entri PPV belum ada | Rendah | Backlog |

Berkas yang diubah hari ini: `app/lib/InventoryService.php`, `app/_inventory/admin.php`, `app/pengadaan_antarcab_terima.php`, `app/servis-reguler-byr.php`, 11 `*_edit_item.php`, 13 `*_hapus_item.php`, `scripts/inv_precutover.php`.

## 3. Planning untuk temuan skala besar

### 3.1 F-7 Barang dalam perjalanan (BR-13/14/15, AC-06/07, V-14)

Skala sedang (1-2 hari), perlu keputusan perilaku sebelum kode.

| Langkah | Isi | Ukuran |
|---|---|---|
| T1 | Varian dalam-transaksi: `shipTransferTx` / `receiveTransferTx` / `returnTransferTx` yang memakai transaksi pemanggil (`manage_tx=false`), karena halaman sudah memegang `begin_transaction` | S |
| T2 | `pengadaan_antarcab_proses.php` dan `_push.php`: ganti `inv_live_post_out(TRANSFER_OUT)` dengan ship (catat `inv_transit` pada biaya asal) untuk cabang live | S |
| T3 | `pengadaan_antarcab_terima.php`: ganti `inv_live_post_in` dengan receive (biaya = biaya kirim, tutup transit). Qty terima kurang dari kirim: sisa kembali ke asal (BR-15) | M |
| T4 | Jalur batal/gagal kirim: tombol "Kembalikan ke asal" untuk pesanan berstatus `dikirim` yang tidak jadi diterima | M |
| T5 | Transfer yang dikirim sebelum cabang live (tanpa baris transit): terima memakai jalur lama (fallback `inv_hpp_asal`) | S |
| T6 | Uji: CLI (kirim/terima/kembali/parsial/ganda), E2E browser dua akun, laporan transit dan aging, `inv_harian` bagian transit | M |

**Keputusan yang dibutuhkan (Rafi/BA):**
1. Qty terima kurang dari kirim: sisa otomatis kembali ke asal, atau tetap di transit sampai diputuskan? Usulan: tetap in_transit dan alert aging, penyelesaian lewat tombol kembalikan.
2. Siapa boleh membatalkan transit (pengirim, Kepala Cabang asal, atau Finance)?

### 3.2 F-8 Proyeksi `OPENING`/`ADJ`/`SCRAP` ke `tbstok`

Skala sedang, berisiko ke pembaca lama; butuh keputusan.

| Opsi | Isi | Konsekuensi |
|---|---|---|
| A (rekomendasi) | Cabang live: tiap `OPENING`/`ADJ_*`/`SCRAP_*`/revaluasi qty menulis baris `tbstok` (tipe 1 untuk opening, 5 masuk, 6 keluar) di transaksi yang sama; rekonsiliasi memakai baris ini | Pembaca lama konsisten; `tbstok` historis negatif tetap, jadi saldo `view_stok_master` mulai dari angka lama + opening. Butuh keputusan D-10 |
| B | Biarkan `tbstok` historis, alihkan pembaca lama (`view_stok_master`, kartu stok, MinMax, stok akhir) ke `inv_balance` untuk cabang live | Konsisten tanpa menulis `tbstok`; mengubah banyak pembaca dan laporan |
| C | Tetap seperti sekarang, dokumentasikan bahwa laporan lama bukan sumber stok untuk cabang live | Murah, tetapi dua angka stok akan terlihat berbeda di layar |

Langkah bila A: T1 proyeksi di `InventoryCorrection` dan `opening_stock.php` (S), T2 sesuaikan `reconcile` agar baseline per tipe (S), T3 uji dan E2E (S). Keputusan yang dibutuhkan: A, B, atau C, dan nasib saldo negatif historis `tbstok` (D-10).

### 3.3 Urutan usulan
1. Putuskan 3.1 dan 3.2 (Rafi), lalu kerjakan keduanya sebelum cabang pilot `live`.
2. Backlog kecil F-9, F-11, F-12 dapat digabung dalam satu sesi.
3. Setelah itu E2E klik penuh di cabang pilot untuk alur yang memanggil WhatsApp/Accurate (tidak dilakukan di lokal).
