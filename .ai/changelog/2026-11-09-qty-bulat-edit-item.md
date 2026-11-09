## Fixed
- `servis_edit_item_byr.php`, `servis_edit_item_garansi.php`, `servis_edit_item_jemput.php`, dan insert barang di `servis-reguler-byr.php`: qty dibulatkan ke bilangan bulat (min 1) sebelum total dihitung. Kolom `tblservis_barang.quantity` bertipe `int`, jadi qty pecahan (mis. 3,5) dulu tersimpan 4 sementara `total` dihitung dari 3,5.

## Temuan
- Audit seluruh `tblservis_barang` (361.891 baris): 5 baris total tidak cocok `harga_jual*quantity*(1-potongan/100)`, semuanya cabang CDT (id 350002, 349992, 267244, 266468, 217533), selisih total sekitar Rp 21 ribu. Data lama TIDAK diubah, menunggu keputusan Rafi.
- Rumus handler sudah benar (potongan = persen). Dugaan "rumus salah" di audit 2026-11-08 keliru, akarnya qty pecahan vs kolom int.
- Hanya lint PHP, belum diuji klik/CLI.
