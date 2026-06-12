# 📊 DIAGRAM ALUR: SISTEM KATEGORI MEMBER

## 🔄 Alur Sistem Baru (Otomatis)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    KASIR INPUT SERVIS                               │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. Pilih Pelanggan: Budi Santoso (AD 1234 AB)                    │
│                                                                     │
│  2. Sistem otomatis tampilkan:                                     │
│     ┌──────────────────────────────────────────┐                  │
│     │ 🥈 Status Member: Silver                 │                  │
│     │ Diskon: 5%                               │                  │
│     │ Total Transaksi: 15x                     │                  │
│     │ Total Nominal: Rp 3.500.000              │                  │
│     └──────────────────────────────────────────┘                  │
│                                                                     │
│  3. Input Jasa & Barang:                                           │
│     - Ganti oli: Rp 50.000                                         │
│     - Tune up: Rp 100.000                                          │
│     - Sparepart: Rp 200.000                                        │
│                                                                     │
│  4. Sistem otomatis hitung:                                        │
│     ┌──────────────────────────────────────────┐                  │
│     │ Subtotal:        Rp 350.000              │                  │
│     │ Diskon (5%):     Rp  17.500              │                  │
│     │ Total Bayar:     Rp 332.500              │                  │
│     └──────────────────────────────────────────┘                  │
│                                                                     │
│  5. Kasir klik: [💰 BAYAR]                                         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                    DATABASE: tblservice                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  UPDATE tblservice                                                  │
│  SET status_servis = 'bayar',                                       │
│      total_akhir = 332500,                                          │
│      diskon = 17500                                                 │
│  WHERE no_service = 'SV25000000123'                                 │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│              ⚡ TRIGGER: trg_after_service_bayar                    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. Hitung total transaksi pelanggan:                              │
│     SELECT COUNT(*) FROM tblservice                                │
│     WHERE no_pelanggan = 'AD 1234 AB'                              │
│     AND status_servis = 'bayar'                                    │
│     → Hasil: 16x (15 + 1 transaksi baru)                           │
│                                                                     │
│  2. Hitung total nominal:                                          │
│     SELECT SUM(total_akhir) FROM tblservice                        │
│     WHERE no_pelanggan = 'AD 1234 AB'                              │
│     AND status_servis = 'bayar'                                    │
│     → Hasil: Rp 3.832.500 (3.500.000 + 332.500)                    │
│                                                                     │
│  3. Tentukan status member dari master:                            │
│     SELECT status_member, diskon_persen                            │
│     FROM tbmaster_kategori_member                                  │
│     WHERE 3832500 >= min_nominal                                   │
│     AND (max_nominal IS NULL OR 3832500 <= max_nominal)            │
│     → Hasil: Silver (Rp 2jt - 5jt), Diskon 5%                      │
│                                                                     │
│  4. Update statistik_pelanggan:                                    │
│     INSERT INTO statistik_pelanggan (...) VALUES (...)             │
│     ON DUPLICATE KEY UPDATE                                        │
│         total_transaksi = 16,                                      │
│         total_nominal = 3832500,                                   │
│         status_member = 'Silver',                                  │
│         diskon_persen = 5.00,                                      │
│         ...                                                        │
│                                                                     │
│  5. Sync kgrup di tblpelanggan (backward compatibility):           │
│     UPDATE tblpelanggan                                            │
│     SET kgrup = '003'  -- mapping Silver                           │
│     WHERE nopelanggan = 'AD 1234 AB'                               │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                         ✅ SELESAI!                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ✓ Transaksi tersimpan                                             │
│  ✓ Statistik pelanggan terupdate                                   │
│  ✓ Status member tetap Silver (masih di range Rp 2-5 juta)        │
│  ✓ Diskon tetap 5%                                                 │
│  ✓ Kasir tidak perlu lakukan apapun lagi                           │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📈 Alur Upgrade Status Member

```
┌─────────────────────────────────────────────────────────────────────┐
│  SKENARIO: Pelanggan Silver Upgrade ke Gold                        │
└─────────────────────────────────────────────────────────────────────┘

Status Awal:
┌──────────────────────────────────────┐
│ Pelanggan: Budi Santoso              │
│ Status: 🥈 Silver                    │
│ Total Nominal: Rp 4.800.000          │
│ Diskon: 5%                           │
│                                      │
│ Progress ke Gold:                    │
│ ████████████████░░ 96%               │
│ Kurang Rp 200.000 lagi!              │
└──────────────────────────────────────┘

Transaksi Baru: Rp 500.000
                ↓
┌──────────────────────────────────────┐
│ Total Nominal Baru:                  │
│ Rp 4.800.000 + Rp 500.000            │
│ = Rp 5.300.000                       │
│                                      │
│ ✅ Melewati threshold Gold!          │
│    (>= Rp 5.000.000)                 │
└──────────────────────────────────────┘
                ↓
┌──────────────────────────────────────┐
│ ⚡ TRIGGER OTOMATIS:                 │
│                                      │
│ 1. Hitung total: Rp 5.300.000        │
│ 2. Cek master kategori member        │
│ 3. Status member: Silver → Gold      │
│ 4. Diskon: 5% → 10%                  │
│ 5. Update statistik_pelanggan        │
└──────────────────────────────────────┘
                ↓
Status Baru:
┌──────────────────────────────────────┐
│ Pelanggan: Budi Santoso              │
│ Status: 🥇 Gold ← NAIK!              │
│ Total Nominal: Rp 5.300.000          │
│ Diskon: 10% ← NAIK!                  │
│                                      │
│ Progress ke Platinum:                │
│ ██████░░░░░░░░░░░░░░ 30%             │
│ Kurang Rp 4.700.000 lagi!            │
│                                      │
│ 🎁 Benefit Baru:                     │
│ • Diskon 10% (naik dari 5%)          │
│ • Garansi 30 hari                    │
│ • Gratis cuci motor 2x/bulan         │
│ • Gratis jemput-antar (5km)          │
│ • Diskon 5% sparepart                │
└──────────────────────────────────────┘
```

---

## 🎯 Threshold Status Member

```
┌─────────────────────────────────────────────────────────────────────┐
│                    KATEGORI MEMBER & THRESHOLD                      │
└─────────────────────────────────────────────────────────────────────┘

🥉 BRONZE
├─ Range: Rp 0 - Rp 1.999.999
├─ Diskon: 0%
└─ Benefit:
   • Akses layanan standar
   • Garansi service 7 hari
   • Reminder service via WhatsApp

                    ↓ Transaksi Rp 2.000.000

🥈 SILVER
├─ Range: Rp 2.000.000 - Rp 4.999.999
├─ Diskon: 5%
└─ Benefit:
   • Diskon 5% untuk semua service
   • Garansi service 14 hari
   • Prioritas antrian
   • Reminder service via WhatsApp
   • Gratis cuci motor 1x per bulan

                    ↓ Transaksi Rp 5.000.000

🥇 GOLD
├─ Range: Rp 5.000.000 - Rp 9.999.999
├─ Diskon: 10%
└─ Benefit:
   • Diskon 10% untuk semua service
   • Garansi service 30 hari
   • Prioritas antrian tinggi
   • Reminder service via WhatsApp
   • Gratis cuci motor 2x per bulan
   • Gratis jemput-antar motor (radius 5km)
   • Diskon 5% untuk pembelian sparepart

                    ↓ Transaksi Rp 10.000.000

💎 PLATINUM
├─ Range: >= Rp 10.000.000
├─ Diskon: 15%
└─ Benefit:
   • Diskon 15% untuk semua service
   • Garansi service 60 hari
   • Prioritas antrian VIP
   • Reminder service via WhatsApp
   • Gratis cuci motor unlimited
   • Gratis jemput-antar motor (radius 10km)
   • Diskon 10% untuk pembelian sparepart
   • Gratis service berkala (oli + filter) 1x per tahun
   • Customer service dedicated
```

---

## 🔀 Perbandingan: Sistem Lama vs Baru

```
┌─────────────────────────────────────────────────────────────────────┐
│                         SISTEM LAMA                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Kasir input servis                                                │
│         ↓                                                           │
│  Kasir klik BAYAR                                                  │
│         ↓                                                           │
│  Data tersimpan di tblservice                                      │
│         ↓                                                           │
│  ❌ Kasir harus ingat update kgrup pelanggan manual                │
│         ↓                                                           │
│  ❌ Buka halaman pelanggan                                         │
│         ↓                                                           │
│  ❌ Edit pelanggan                                                 │
│         ↓                                                           │
│  ❌ Ubah kgrup dari '001' ke '002' (jika layak upgrade)            │
│         ↓                                                           │
│  ❌ Simpan                                                         │
│         ↓                                                           │
│  ⚠️  MASALAH:                                                       │
│      - Kasir sering lupa                                           │
│      - Tidak ada aturan jelas kapan upgrade                        │
│      - Data tidak akurat                                           │
│      - Diskon tidak konsisten (Gold 5%, Silver 10%)                │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

                              VS

┌─────────────────────────────────────────────────────────────────────┐
│                         SISTEM BARU                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Kasir input servis                                                │
│         ↓                                                           │
│  Kasir klik BAYAR                                                  │
│         ↓                                                           │
│  Data tersimpan di tblservice                                      │
│         ↓                                                           │
│  ⚡ TRIGGER OTOMATIS JALAN                                          │
│         ↓                                                           │
│  ✅ Hitung total transaksi & nominal                               │
│         ↓                                                           │
│  ✅ Tentukan status member berdasarkan threshold                   │
│         ↓                                                           │
│  ✅ Set diskon sesuai status member                                │
│         ↓                                                           │
│  ✅ Update statistik_pelanggan                                     │
│         ↓                                                           │
│  ✅ SELESAI!                                                       │
│         ↓                                                           │
│  🎉 KEUNTUNGAN:                                                     │
│      - Kasir tidak perlu lakukan apapun                            │
│      - Update otomatis 100% akurat                                 │
│      - Aturan upgrade jelas (threshold)                            │
│      - Diskon konsisten (Bronze 0% < Silver 5% < Gold 10%)         │
│      - Real-time tracking                                          │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📊 Struktur Database

```
┌─────────────────────────────────────────────────────────────────────┐
│                    TABEL & RELASI                                   │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────┐
│   tblpelanggan           │
├──────────────────────────┤
│ • nopelanggan (PK)       │
│ • namapelanggan          │
│ • alamat                 │
│ • telephone              │
│ • kgrup (sync dari stat) │◄────┐
└──────────────────────────┘     │
            │                    │
            │ 1:N                │ sync
            ↓                    │
┌──────────────────────────┐     │
│   tblservice             │     │
├──────────────────────────┤     │
│ • no_service (PK)        │     │
│ • no_pelanggan (FK)      │     │
│ • tanggal                │     │
│ • total_akhir            │     │
│ • status_servis          │     │
│ • diskon                 │     │
└──────────────────────────┘     │
            │                    │
            │ TRIGGER            │
            │ (on UPDATE)        │
            ↓                    │
┌──────────────────────────┐     │
│ statistik_pelanggan      │     │
├──────────────────────────┤     │
│ • id_statistik (PK)      │     │
│ • no_pelanggan (FK)      │─────┘
│ • total_transaksi        │
│ • total_nominal          │
│ • status_member          │◄────┐
│ • diskon_persen          │     │
│ • tanggal_terakhir       │     │
│ • ...                    │     │
└──────────────────────────┘     │
            │                    │
            │ N:1                │
            ↓                    │
┌──────────────────────────┐     │
│ tbmaster_kategori_member │     │
├──────────────────────────┤     │
│ • id (PK)                │     │
│ • status_member (UK)     │─────┘
│ • min_nominal            │
│ • max_nominal            │
│ • diskon_persen          │
│ • benefit                │
│ • urutan                 │
└──────────────────────────┘

LEGEND:
PK = Primary Key
FK = Foreign Key
UK = Unique Key
1:N = One to Many
N:1 = Many to One
```

---

## 🎬 Timeline Contoh Kasus

```
┌─────────────────────────────────────────────────────────────────────┐
│  TIMELINE: Perjalanan Pelanggan dari Bronze ke Platinum            │
└─────────────────────────────────────────────────────────────────────┘

Hari 1 (1 Jan 2025)
├─ Transaksi: Rp 500.000
├─ Total: Rp 500.000
└─ Status: 🥉 Bronze (0% diskon)

Hari 30 (30 Jan 2025)
├─ Transaksi: Rp 800.000
├─ Total: Rp 1.300.000
└─ Status: 🥉 Bronze (0% diskon)

Hari 60 (1 Mar 2025)
├─ Transaksi: Rp 1.200.000
├─ Total: Rp 2.500.000
└─ Status: 🥈 Silver (5% diskon) ← NAIK!

Hari 90 (30 Mar 2025)
├─ Transaksi: Rp 900.000
├─ Diskon 5%: Rp 45.000
├─ Bayar: Rp 855.000
├─ Total: Rp 3.400.000
└─ Status: 🥈 Silver (5% diskon)

Hari 120 (29 Apr 2025)
├─ Transaksi: Rp 2.000.000
├─ Diskon 5%: Rp 100.000
├─ Bayar: Rp 1.900.000
├─ Total: Rp 5.400.000
└─ Status: 🥇 Gold (10% diskon) ← NAIK!

Hari 150 (29 Mei 2025)
├─ Transaksi: Rp 1.500.000
├─ Diskon 10%: Rp 150.000
├─ Bayar: Rp 1.350.000
├─ Total: Rp 6.900.000
└─ Status: 🥇 Gold (10% diskon)

Hari 180 (28 Jun 2025)
├─ Transaksi: Rp 3.500.000
├─ Diskon 10%: Rp 350.000
├─ Bayar: Rp 3.150.000
├─ Total: Rp 10.400.000
└─ Status: 💎 Platinum (15% diskon) ← NAIK!

Hari 210 (28 Jul 2025)
├─ Transaksi: Rp 2.000.000
├─ Diskon 15%: Rp 300.000
├─ Bayar: Rp 1.700.000
├─ Total: Rp 12.400.000
└─ Status: 💎 Platinum (15% diskon)

┌──────────────────────────────────────┐
│ SUMMARY (210 hari):                  │
├──────────────────────────────────────┤
│ Total Transaksi: 8x                  │
│ Total Nominal: Rp 12.400.000         │
│ Total Diskon: Rp 945.000             │
│ Status Akhir: 💎 Platinum            │
│ Diskon Saat Ini: 15%                 │
└──────────────────────────────────────┘
```

---

**Semua proses di atas berjalan OTOMATIS tanpa campur tangan kasir!** ✨
