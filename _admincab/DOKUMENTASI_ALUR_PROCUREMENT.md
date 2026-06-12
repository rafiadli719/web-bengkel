# DOKUMENTASI ALUR KERJA PROCUREMENT TERINTEGRASI MIN/MAX

## 📊 Overview Sistem

Sistem procurement terintegrasi dengan kalkulasi MIN/MAX stok berdasarkan data penjualan 84 hari (12 minggu).

---

## 🔄 ALUR KERJA LENGKAP

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FASE 1: ANALISIS KEBUTUHAN                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐      │
│  │ Data Penjualan   │───>│ Kalkulasi        │───>│ Klasifikasi      │      │
│  │ 84 Hari          │    │ MIN/MAX          │    │ A/B/C/D/E        │      │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘      │
│                                  │                                          │
│                                  ▼                                          │
│                    ┌──────────────────────────┐                            │
│                    │ tblitem_minmax           │                            │
│                    │ (per item per cabang)    │                            │
│                    └──────────────────────────┘                            │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FASE 2: RENCANA ORDER                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐      │
│  │ Item Stok < MIN  │───>│ Hitung Kebutuhan │───>│ Buat Rencana     │      │
│  │ (view_item_      │    │ per Cabang       │    │ Order            │      │
│  │  order_segera)   │    └──────────────────┘    └──────────────────┘      │
│  └──────────────────┘                                    │                  │
│                                                          ▼                  │
│                    ┌──────────────────────────────────────────────┐        │
│                    │ tblrencana_order_header                      │        │
│                    │ tblrencana_order_detail                      │        │
│                    │ tblrencana_order_detail_cabang               │        │
│                    └──────────────────────────────────────────────┘        │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────┐          │
│  │ SPLIT ORDER:                                                  │          │
│  │ • ORDER 1: Supplier tempo ≤ 14 hari (prioritas)              │          │
│  │ • ORDER 2: Supplier tempo > 14 hari (NSA, dll)               │          │
│  └──────────────────────────────────────────────────────────────┘          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FASE 3: TRANSFER ANTAR CABANG                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐      │
│  │ Cabang Surplus   │───>│ Saran Transfer   │───>│ Eksekusi         │      │
│  │ (Stok > MAX)     │    │ ke Cabang Defisit│    │ Transfer         │      │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘      │
│                                                          │                  │
│                                                          ▼                  │
│                    ┌──────────────────────────────────────────────┐        │
│                    │ tblrencana_transfer                          │        │
│                    │ → Update nota_antarcab                       │        │
│                    └──────────────────────────────────────────────┘        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FASE 4: PESANAN PEMBELIAN (PO)                       │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐      │
│  │ Rencana Order    │───>│ Buat PO per      │───>│ Kirim ke         │      │
│  │ Approved         │    │ Supplier         │    │ Supplier         │      │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘      │
│                                                          │                  │
│                                                          ▼                  │
│                    ┌──────────────────────────────────────────────┐        │
│                    │ tbpembelian_header (sebagai PO)              │        │
│                    │ tbpembelian_detail                           │        │
│                    │ status: 'po_sent'                            │        │
│                    └──────────────────────────────────────────────┘        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FASE 5: PENERIMAAN BARANG (DO)                       │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐      │
│  │ Barang Datang    │───>│ Buat DO dari PO  │───>│ Distribusi ke    │      │
│  │ dari Supplier    │    │ (do_from_po.php) │    │ Cabang           │      │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘      │
│                                                          │                  │
│                                                          ▼                  │
│                    ┌──────────────────────────────────────────────┐        │
│                    │ tblrealisasi_order                           │        │
│                    │ tblrealisasi_order_distribusi                │        │
│                    │ → Update tbstok per cabang                   │        │
│                    └──────────────────────────────────────────────┘        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FASE 6: PEMBAYARAN HUTANG                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐      │
│  │ Faktur Jatuh     │───>│ Pembayaran       │───>│ Update Status    │      │
│  │ Tempo            │    │ ke Supplier      │    │ Lunas            │      │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘      │
│                                                          │                  │
│                                                          ▼                  │
│                    ┌──────────────────────────────────────────────┐        │
│                    │ tbpembayaran_hutang                          │        │
│                    │ → Update status pembelian                    │        │
│                    └──────────────────────────────────────────────┘        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 📁 MAPPING FILE PHP

### Fase 1: Analisis Kebutuhan
| File | Fungsi | Status |
|------|--------|--------|
| `lib/MinMaxCalculator.php` | Class kalkulasi MIN/MAX | ✅ Sudah dibuat |
| `_ajax/ajax-minmax-calculation.php` | AJAX endpoint | ✅ Sudah dibuat |
| `procurement_dashboard.php` | Dashboard monitoring | ✅ Sudah dibuat |

### Fase 2: Rencana Order
| File | Fungsi | Status |
|------|--------|--------|
| `rencana_order.php` | Daftar rencana order | 🔄 Perlu dibuat |
| `rencana_order_add.php` | Buat rencana order baru | 🔄 Perlu dibuat |
| `rencana_order_detail.php` | Detail rencana order | 🔄 Perlu dibuat |
| `rencana_order_approve.php` | Approve rencana | 🔄 Perlu dibuat |
| `_ajax/ajax-rencana-order.php` | AJAX endpoint | 🔄 Perlu dibuat |

### Fase 3: Transfer Antar Cabang
| File | Fungsi | Status |
|------|--------|--------|
| `transfer_suggest.php` | Saran transfer | 🔄 Perlu dibuat |
| `transfer_approve.php` | Approve transfer | 🔄 Perlu dibuat |
| Menggunakan sistem nota_antarcab existing | - | ✅ Ada |

### Fase 4: Pesanan Pembelian (PO)
| File | Fungsi | Status |
|------|--------|--------|
| `pesanan_pembelian.php` | Daftar PO | ✅ Ada, perlu update |
| `pesanan_pembelian_add.php` | Buat PO manual | ✅ Ada |
| `po_from_rencana.php` | Buat PO dari rencana order | 🔄 Perlu dibuat |

### Fase 5: Penerimaan Barang (DO)
| File | Fungsi | Status |
|------|--------|--------|
| `do_list.php` | Daftar DO | ✅ Ada |
| `do_from_po.php` | Buat DO dari PO | ✅ Ada, perlu update |
| `do_receive.php` | Terima barang | ✅ Ada |
| `do_distribusi.php` | Distribusi ke cabang | 🔄 Perlu dibuat |

### Fase 6: Pembayaran Hutang
| File | Fungsi | Status |
|------|--------|--------|
| `pmby_hutang.php` | Daftar hutang | ✅ Ada |
| `pembelian.php` | Daftar pembelian | ✅ Ada |

---

## 🗄️ STRUKTUR DATABASE

### Tabel Baru (Sudah Dibuat)
```sql
-- Data penjualan untuk kalkulasi
tblpenjualan_harian
tblpenjualan_mingguan

-- MIN/MAX dinamis per cabang
tblitem_minmax

-- Rencana Order
tblrencana_order_header
tblrencana_order_detail
tblrencana_order_detail_cabang

-- Transfer
tblrencana_transfer

-- Realisasi
tblrealisasi_order
tblrealisasi_order_distribusi

-- Supplier
tblsupplier_tempo
```

### Views (Sudah Dibuat)
```sql
view_stok_cabang           -- Stok per item per cabang
view_item_order_segera     -- Item yang perlu order
view_item_minmax_summary   -- Summary MIN/MAX
view_rencana_order_per_supplier  -- Rencana per supplier
```

---

## 🎯 KLASIFIKASI ITEM

| Kategori | Interval (Hari) | Transaksi/84 hari | Deskripsi |
|----------|-----------------|-------------------|-----------|
| **A** | 1-3 hari | 22-84 transaksi | Fast Moving |
| **B** | 4-12 hari | 7-21 transaksi | Medium Moving |
| **C** | >12 hari | 4-6 transaksi | Slow Moving |
| **D** | >30 hari | 1-3 transaksi | Dead Stock |
| **E** | - | 0 transaksi | Non-Stock |

---

## 📋 PRIORITAS IMPLEMENTASI

### Sprint 1 (Hari Ini)
1. ✅ Dokumentasi alur kerja
2. 🔄 `rencana_order.php` - Halaman list
3. 🔄 `rencana_order_add.php` - Form create
4. 🔄 `_ajax/ajax-rencana-order.php` - AJAX endpoint

### Sprint 2 (Besok)
1. `rencana_order_detail.php`
2. `rencana_order_approve.php`
3. Update `pesanan_pembelian.php`

### Sprint 3 (Lanjutan)
1. `po_from_rencana.php`
2. Update `do_from_po.php`
3. `do_distribusi.php`

---

## 🔐 CATATAN KEAMANAN

1. Semua file PHP dimulai dengan session check
2. Gunakan prepared statement untuk query
3. Validasi input dari user
4. Log semua aktivitas penting

---

*Dokumentasi dibuat: 19 Desember 2024*
*Versi: 1.0*
