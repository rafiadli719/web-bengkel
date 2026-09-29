# Data dummy E2E lapangan 2026-09-28

Semua data uji ditandai `[TEST-E2E]` (nama) supaya gampang dibersihkan.
Diupdate tiap sesi tes; hapus baris setelah datanya dibersihkan dari DB.

| Tabel | Kunci | Keterangan |
|---|---|---|
| tblpelanggan | CST2609280001 | [TEST-E2E] Budi Uji, WA 081234500001 |
| tblkendaraan | G 9001 TST | milik CST2609280001, HONDA ADV-150 |
| tblservice | SV26000103578 | servis reguler dummy, status 'datang', G 9001 TST |
| tblpenjualan_header | JL26000000002 | transaksi POS dummy (cabang PST), note [TEST-E2E], qty 2 x 20W-40MATIC — stok PST sudah dipotong 2, kembalikan saldo saat hapus |
