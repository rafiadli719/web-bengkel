# Planning: Revisi Modul Laporan Masalah + Deteksi Duplikat Pelanggan

**Tanggal:** 2026-07-10
**Latar belakang:** Modul laporan masalah (`issue_add.php`) awal dibangun generik untuk 6 kategori (`data_pelanggan`, `data_kendaraan`, `komisi`, `stok`, `sistem`, `lainnya`), dengan alur khusus merge pelanggan untuk kategori `data_pelanggan`. Setelah dites pakai data riil, ketahuan alur ini gak efektif dari dua sisi:

- **CS**: deteksi dobel cuma manual pas kebetulan search. Dari 37.673 data pelanggan, cakupan sangat rendah, dan CS gak punya alat verifikasi kemiripan.
- **Supervisor**: approval cuma lihat 2 data mentah tanpa skor kemiripan, gampang salah gabung. Konfirmasi approve cuma sekali klik (pernah ke-trigger gak sengaja lewat automation browser).

**Keputusan arah baru:** pisahkan dua hal yang tadinya digabung satu modul.

## 1. Laporan Masalah → dipersempit ke kasus transaksional

Tetap pakai `issue_add.php` + alur approval, tapi kategori dibatasi ke kasus **input-error spesifik satu transaksi** yang butuh audit trail + approval sebelum diubah:

- Salah persentase komisi mekanik/kepala mekanik di input servis
- Salah input nominal/jasa/barang lain yang sudah terlanjur tersimpan (kasus serupa, per-transaksi)

**Yang dihapus dari kategori:** `data_pelanggan` (merge pelanggan) — dipindah ke tool admin (lihat bagian 2). Kategori `data_kendaraan`, `stok`, `sistem` perlu ditinjau juga: kalau isinya "data massal yang butuh deteksi sistematik" (misal data kendaraan tanpa pemilik jelas), pola yang sama berlaku — keluarkan dari laporan tiket, masuk tool admin.

### Perubahan teknis
- `app/issue_add.php`:
  - `$kategori_opsi` hapus `data_pelanggan` (baris 35)
  - Hapus blok submit-merge terkait kategori `data_pelanggan` (baris ~76-93)
  - Hapus blok render "Merge Data Pelanggan" di detail tiket (baris ~396+)
  - Ganti kategori jadi lebih spesifik ke kasus transaksional, contoh: `komisi_mekanik`, `nominal_transaksi`, `lainnya`
- `app/menu_config.php`: tetap ada menu "Laporan Masalah", tidak berubah
- `customer_merge_log`, `customer_alias`, `customer_merge_approve.php`: **tidak dihapus** — dipakai ulang sebagai backend eksekusi merge yang dipicu dari tool admin baru (bagian 2), bukan dari tiket.

## 2. Tool Admin: Deteksi & Perbaikan Data Pelanggan Duplikat

Halaman baru khusus admin/tim data, bukan modul tiket. Alurnya proaktif, bukan reaktif nunggu laporan.

### Alur
1. Admin buka halaman "Deteksi Data Pelanggan Bermasalah"
2. Sistem jalankan query kemiripan atas seluruh `tblpelanggan` (bukan per-satu manual):
   - Nama mirip (soundex/levenshtein atau minimal `LIKE` normalisasi spasi+huruf besar/kecil)
   - No HP sama persis
   - Alamat sama + kota sama
   - Nopol kendaraan sama tapi nopelanggan beda (join `tblkendaraan`)
3. Hasil ditampilkan berkelompok (cluster kandidat dobel) dengan skor kemiripan, bukan daftar flat
4. Admin review tiap cluster, pilih record master vs record yang dilebur
5. Eksekusi merge pakai backend yang sudah ada (`customer_merge_log` → `customer_merge_approve.php` punya fungsi eksekusi, tinggal dipanggil langsung dari sini tanpa lewat tiket dan tanpa approval-ganda karena admin yang eksekusi sendiri = satu titik tanggung jawab)
6. Snapshot JSON + `customer_alias` tetap jalan buat audit trail dan basis rollback manual

### File baru (usulan)
- `app/admin_deteksi_pelanggan_dobel.php` — halaman list cluster kandidat + tombol merge
- Reuse fungsi eksekusi dari `customer_merge_approve.php` (extract jadi fungsi kalau perlu dipanggil dari 2 tempat)

### Guardrail yang perlu ditambah saat build (dari temuan sebelumnya)
- Re-type/konfirmasi eksplisit nopelanggan target sebelum eksekusi merge (cegah kejadian ke-klik gak sengaja)
- Lock/status check sederhana biar gak ada 2 proses eksekusi bertabrakan di record yang sama
- Sinkron status membership/tier pelanggan pas merge (saat ini belum auto-sync)

## Urutan Kerja

1. **Trim `issue_add.php`**: hapus opsi & alur kategori `data_pelanggan`, ganti ke kategori transaksional (komisi dulu, karena itu contoh konkret dari user)
2. **Bangun tool deteksi admin**: query cluster kemiripan dulu (read-only), validasi hasil pakai data riil sebelum bikin tombol eksekusi
3. **Sambungkan eksekusi merge** dari tool admin ke backend existing, tambah guardrail re-type konfirmasi
4. **Review kategori lain** (`data_kendaraan`, `stok`, `sistem`) — putuskan tiap kategori masuk "kasus transaksional" (tetap di tiket) atau "data massal" (pindah ke tool admin), satu-satu

## Belum diputuskan / perlu keputusan user
- Kategori komisi: apakah butuh approval supervisor sebelum angka diubah, atau CS/mekanik boleh ubah langsung dengan catatan log?
- Threshold skor kemiripan buat cluster duplikat pelanggan — mulai dari mana (nama+HP exact match dulu, atau langsung pakai fuzzy match)?
- `data_kendaraan`, `stok`, `sistem` — perlu direview satu-satu, belum dianalisis di sesi ini.
