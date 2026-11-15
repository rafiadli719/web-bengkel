# Hasil Uji Setelah Reload Adiwerna (PESALAKAN) — 2026-11-15

Lingkup: lokal, login akun test `e2e_test_cikditiro`. Hanya Adiwerna yang sudah dimuat dari `fitmotor_center`.

## Yang diuji
| Uji | Hasil |
|---|---|
| Smoke GET 434 halaman `app/*.php` (tanpa save/proses) | 387 OK 200, 35 redirect sah, `403.php`/`404.php`/`master_item.php` (sengaja 404), 0 fatal di halaman utama |
| 4 halaman `paket_add_*_cari` (500) | Fragmen modal butuh parameter, bukan akibat reload. Cek terpisah |
| 5 halaman `servis-*-cari` timeout 40 dtk tanpa param | Dengan param normal 0,5–3,7 dtk, 200 OK. Artefak uji |
| `modal-search-keluhan.php` fatal `$koneksi null` | Fragmen include dibuka langsung, bukan alur nyata |
| Laporan 1–14 Nov: penjualan 2, pembelian 21, servis 114 | Cocok DB (servis 118 − 4 belum bayar = 114) |

## Temuan
1. **Laporan tidak filter cabang** (`lap_servis`, `lap_penjualan`, `lap_pembelian`, dll. pakai view tanpa `kd_cabang`). Tutup sebelum sesi Pacul, kalau tidak laporan campur cabang.
2. **15 baris detail yatim**: `SV24000004849` (10 barang) dan `SV19000003064` (5 jasa) detail ada di PESALAKAN, header hanya ada di PCL (nomor Access bentrok lintas cabang). Verifikasi ulang saat sesi Pacul.
3. **15 item pada barang servis tanpa master `tblitem`** (mis. `95014-71102`, `MASD`). Cek ulang setelah master digabung.
4. **1.799 servis bayar Adiwerna `total_akhir = 0`** (1.727 punya barang/jasa; 2023–2026 ±180–210/tahun). Sama dengan sumber center (`TotalAkhir=0`), bukan salah muat. Efek: nominal laporan dan tier member rendah. Butuh keputusan Rafi: biarkan, atau hitung dari SubTotal.
5. **Halaman lambat dengan data baru**: `servis-carinopol` 11,6 dtk, `barang_kartu_stok` 8,4 dtk, `barang*` 6–9 dtk, `servis-garansi` 11 dtk. Perlu profil query/index.

## Belum diuji
- Alur tulis penuh di browser setelah reload (servis baru → bayar, pembelian, penjualan, retur, PO/DO, kas). E2E sebelum reload lulus (2026-11-01/02/14), belum diulang di data baru.
- Cabang selain Adiwerna (belum dimuat).
- Konflik master (menunggu keputusan Rafi) dan Fase K.
