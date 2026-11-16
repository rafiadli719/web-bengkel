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

## Uji alur tulis (browser, 2026-11-16)
Login akun test, cabang PESALAKAN (cabang dipilih saat login).
| Langkah | Hasil |
|---|---|
| Cari nopol `G 4593 BN` → modal WA → preview diskon | Histori center tampil (60 kunjungan, Rp 6,56 jt, Gold, diskon 15%/11%) |
| Input servis baru `SV26000103583` (G 6736 AFF) | Header terbentuk, nomor urut lanjut dari data lama |
| Tambah barang 2×19.000 + jasa SVS001 40.000 | Baris tersimpan dengan `kd_cabang=PESALAKAN` |
| PROSES BAYAR tanpa kepala mekanik / persen 100% / admin | Ditolak dengan pesan benar |
| PROSES BAYAR lengkap (tunai 78.000) | `status_servis=bayar`, total 78.000, stok `08232-2MB-K8JN1` 65→63 (tipe 4), komisi 3 baris, statistik pelanggan naik, muncul di `lap_servis` |
| Suite CLI inventory/shadow/koreksi/jurnal | 165/165 lulus |

Temuan tambahan:
6. **Servis terbuka dibuka lintas cabang**: sesi PESALAKAN pencarian `G 4593 BN` langsung membuka `SV26000103582` milik CIKDITIRO (halaman input memuat servis hanya by `no_service`/nopol). Perlu filter `kd_cabang`.
7. **Dua jalur bayar beda kode status**: `servis-input-reguler.php` menulis `status='2'`, `servis-reguler-byr.php` menulis `status='4'`; hasil reload memakai `'4'`. Laporan memakai `status_servis`, dampak kecil (hanya `helper-functions.php` cek `'4'`). Samakan.
8. **HPP barang servis `harga_pokok=0.00`** setelah bayar (inv_mode off). Perilaku lama, perlu cutover Moving Average.
9. **CIKDITIRO tidak punya admin** (dropdown Admin kosong) → bayar servis di CIKDITIRO mustahil (sudah dicatat sebelumnya). `SV26000103582` (CIKDITIRO) tetap berstatus datang sebagai data uji.

## Uji alur tulis lanjutan (2026-11-16)
| Modul | Hasil |
|---|---|
| Penjualan manual `JL26000011244` (3×19.000, tunai) | Tersimpan, `kd_cabang=PESALAKAN`, stok tipe 3 keluar 3 (63→60). `status_lunas` ditulis `0` padahal lunas (data import `1`); tidak dipakai halaman penjualan, dampak kecil |
| Pembelian `BL26000013453` (5×16.000, tunai) | Tersimpan, nomor lanjut dari data center, stok tipe 2 masuk 5 (60→65). `status_lunas` `0`; 1 alarm harga beli sah |
| Kas masuk `KM26000000053` | **BUG**: INSERT gagal diam-diam (`no_bukti`, `keluar`, `tipe` NOT NULL tanpa default) tapi tampil "Berhasil disimpan". Diperbaiki di `kas_masuk_proses.php` & `kas_keluar_proses.php` (commit `4d0462b`), kas masuk terverifikasi tersimpan 50.000. Kas keluar baru lint, belum klik |
| Retur pembelian `RP26000000013` (2 dari BL26000013453) | Tersimpan, total 32.000, stok tipe 7 keluar 2 (65→63). Nomor baru seri `RP`, data lama seri `RB` (beda seri, tidak bentrok) |
| Retur penjualan `RJ26000000001` (1 dari JL26000011244) | Tersimpan, total 19.000, stok tipe 8 masuk 1 (63→64) |
| PO `PS26000000804` (10×16.000) | Header tersimpan `kd_cabang=PESALAKAN`, nomor lanjut tanpa bentrok. Approval, DO, terima belum diuji |
| `paket_add_*_cari` (500) | Modul paket mati: tabel `tbpaket*` tidak ada dan `view_cari_item` tanpa kolom `jenis`. Bukan akibat reload. Kandidat arsip |

Belum diuji tulis: retur servis, approval PO/DO/terima, antar cabang, kas keluar (klik), edit/hapus, cabang selain Adiwerna.

## Perbaikan dan uji lanjutan (2026-11-16, sesi lanjutan)
Perbaikan (semua diuji ulang):
| Temuan | Perbaikan | Commit |
|---|---|---|
| Laporan tanpa filter cabang | Filter `kd_cabang` sesi di 33 file lap_* (servis, penjualan, pembelian, pesanan, hutang, piutang, kas, stok + pdf/xls). `lap_servis` 114→113 | 8c4f748 |
| Servis terbuka lintas cabang | `save-no-servis-reguler.php` cek servis aktif per (nopol, kd_cabang). Servis baru `SV26000103584` terbuat, tidak lagi membuka milik CIKDITIRO | (commit fix servis aktif) |
| Dua kode status bayar | Bayar inline reguler menulis `status='4'`, 3 baris uji dinormalkan | 0ab90d1 |
| `status_lunas` 0 pada tunai | Wizard penjualan/pembelian mengisi `status_lunas`/`tanggal_lunas` (`BL26000013454` = 1). Berdampak ke laporan/pembayaran hutang-piutang | 8b621e6 |
| Kas masuk/keluar gagal diam-diam | INSERT lengkap + cek hasil | 4d0462b |

Uji tulis lulus: kas keluar (`KK26000000999`), retur servis `RS26000000003` + approve (stok tipe 9, status_refund 1), PO `PS26000000804` submit+approve (160.000), DO dari PO (stok +10, `qty_terima` 10), batal retur penjualan (stok kembali), cancel servis. Saldo stok `08232-2MB-K8JN1` konsisten di tiap langkah (akhir 76).

Catatan: query `SELECT ... WHERE no_service=` untuk membuka halaman servis masih tanpa `kd_cabang` (di luar aturan K1/K2 `audit_no_service`); `po_approval_action`/`do_from_po` memakai `no_order` saja (PK global, tunggu Fase K).

Masih belum diuji: antar cabang (butuh 2 cabang), edit/hapus item pada transaksi yang sudah tersimpan, cabang selain Adiwerna.

## Uji tambahan (2026-11-16, malam)
- Antar cabang `PA26000000004` TRAYEMAN→PESALAKAN: request (status terkirim) → kirim dari Adiwerna (stok tipe 3 keluar 3, 76→73) → terima di TRAYEMAN (tipe 2 masuk 3) → `selesai`. Lulus.
- Temuan data: `tbcabang.tipe_cabang='1'` (dianggap "pusat") di semua cabang kecuali TRAYEMAN (=2), sehingga PESALAKAN/PACUL/CIKDITIRO/PST dialihkan dan tidak bisa membuat permintaan antar cabang. Perlu keputusan Rafi (tipe cabang benar).
- Edit/hapus item draft PO lulus. Picker paket diperbaiki (25a4b0a), jasa mengembalikan 69 data.

- Picker jasa paket: 144 baris = isi `tblitem_jasa` (144). Master rak: tambah/edit/hapus lulus. Transaksi tersimpan tidak punya halaman edit item (hanya draft); pembatalan lewat retur/cancel sudah lulus.

## Belum diuji
- Alur tulis browser untuk pembelian, penjualan, retur, PO/DO, kas, antar cabang (hanya servis yang diulang; suite CLI stok lulus). E2E sebelumnya lulus (2026-11-01/02/14).
- Cabang selain Adiwerna (belum dimuat).
- Konflik master (menunggu keputusan Rafi) dan Fase K.
