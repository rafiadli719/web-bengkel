# QUESTIONNAIRE - MODUL PROCUREMENT (PR-PO-DO-GR)

## 📋 TUJUAN
Dokumen ini berisi daftar pertanyaan untuk mengumpulkan requirement dan data yang diperlukan sebelum membuat modul Procurement (Purchase Request, Purchase Order, Delivery Order, Goods Receipt).

---

## 🏢 A. INFORMASI UMUM PERUSAHAAN

### A1. Struktur Organisasi
1. **Berapa jumlah cabang yang akan menggunakan sistem ini?**
   - [ ] 1 cabang
   - [ ] 2-5 cabang
   - [ ] > 5 cabang
   - Sebutkan: ________> 5_________

2. **Apa saja departemen yang terlibat dalam proses pembelian?**
   - [ ] Gudang/Warehouse
   - [ ] Produksi/Workshop
   - [ ] Penjualan/Sales
   - [ ] Administrasi
   - [ ] Lainnya: _________________

3. **Berapa jumlah user yang akan menggunakan modul ini?**
   - Requester (yang buat PR): _____ orang
   - Approver (yang approve): _____ orang
   - Purchasing (yang buat PO): _____ orang
   - Warehouse (yang terima barang): _____ orang
   - Finance (yang bayar): _____ orang

### A2. Volume Transaksi
4. **Berapa rata-rata transaksi pembelian per bulan?**
   - [ ] < 10 transaksi
   - [ ] 10-50 transaksi
   - [ ] 50-100 transaksi
   - [ ] > 100 transaksi

5. **Berapa rata-rata nilai pembelian per transaksi?**
   - [ ] < Rp 1 juta
   - [ ] Rp 1-5 juta
   - [ ] Rp 5-20 juta
   - [ ] > Rp 20 juta

6. **Berapa jumlah supplier aktif saat ini?**
   - Jumlah: _____ supplier

---

## 📝 B. PURCHASE REQUEST (PR)

### B1. Proses PR
7. **Siapa saja yang berhak membuat Purchase Request?**
   - [ ] Semua user
   - [ ] Hanya departemen tertentu
   - [ ] Hanya level jabatan tertentu
   - Sebutkan: _________________

8. **Apakah PR harus menyertakan alasan/justifikasi?**
   - [ ] Ya, wajib
   - [ ] Tidak perlu
   - [ ] Opsional

9. **Apakah ada budget limit per PR?**
   - [ ] Ya, ada limit
   - [ ] Tidak ada limit
   - Jika ya, berapa: Rp _________________

10. **Berapa lama lead time dari PR dibuat sampai barang dibutuhkan?**
    - [ ] < 3 hari (urgent)
    - [ ] 3-7 hari (normal)
    - [ ] 7-14 hari (planned)
    - [ ] > 14 hari (long term)

### B2. Approval PR
11. **Berapa level approval untuk PR?**
    - [ ] 1 level (Supervisor saja)
    - [ ] 2 level (Supervisor + Manager)
    - [ ] 3 level (Supervisor + Manager + Director)
    - [ ] Lebih dari 3 level

12. **Apakah approval berdasarkan nilai nominal?**
    - [ ] Ya
    - [ ] Tidak
    
    Jika ya, sebutkan matrix approval:
    - < Rp ________: Approval oleh _________________
    - Rp ________ - Rp ________: Approval oleh _________________
    - > Rp ________: Approval oleh _________________

13. **Apakah approval berdasarkan kategori barang?**
    - [ ] Ya
    - [ ] Tidak
    
    Jika ya, sebutkan:
    - Kategori _________________: Approval oleh _________________
    - Kategori _________________: Approval oleh _________________

14. **Berapa lama maksimal waktu approval PR?**
    - Target: _____ hari/jam
    - Jika melebihi, apakah ada eskalasi? [ ] Ya [ ] Tidak

15. **Apakah approver bisa reject PR?**
    - [ ] Ya, bisa reject
    - [ ] Tidak, hanya bisa approve atau hold
    - Jika reject, apakah bisa revisi? [ ] Ya [ ] Tidak

### B3. Notifikasi PR
16. **Apakah perlu notifikasi untuk PR?**
    - [ ] Email
    - [ ] WhatsApp
    - [ ] SMS
    - [ ] Notifikasi di sistem saja
    - [ ] Tidak perlu

17. **Siapa yang perlu dinotifikasi?**
    - [ ] Requester (saat status berubah)
    - [ ] Approver (saat ada PR baru)
    - [ ] Purchasing (saat PR approved)
    - [ ] Lainnya: _________________

---

## 🛒 C. REQUEST FOR QUOTATION (RFQ) - OPTIONAL

### C1. Kebutuhan RFQ
18. **Apakah perlu modul RFQ (Request for Quotation)?**
    - [ ] Ya, sangat perlu
    - [ ] Mungkin perlu (prioritas rendah)
    - [ ] Tidak perlu

19. **Jika ya, berapa supplier yang biasa dimintai penawaran?**
    - [ ] 2 supplier
    - [ ] 3 supplier
    - [ ] 5 supplier
    - [ ] Lebih dari 5

20. **Apakah ada kriteria pemilihan supplier selain harga?**
    - [ ] Kualitas barang
    - [ ] Lead time pengiriman
    - [ ] Payment term
    - [ ] Track record
    - [ ] Lainnya: _________________

21. **Berapa lama deadline supplier untuk submit penawaran?**
    - [ ] 1-3 hari
    - [ ] 3-7 hari
    - [ ] 7-14 hari
    - [ ] Lebih dari 14 hari

---

## 📦 D. PURCHASE ORDER (PO)

### D1. Proses PO
22. **Apakah PO harus selalu dibuat dari PR?**
    - [ ] Ya, wajib dari PR
    - [ ] Tidak, bisa langsung buat PO
    - [ ] Tergantung nilai/kategori

23. **Apakah ada PO urgent (tanpa PR)?**
    - [ ] Ya, boleh
    - [ ] Tidak boleh
    - Jika ya, siapa yang berhak: _________________

24. **Apakah PO bisa untuk multiple PR?**
    - [ ] Ya, 1 PO bisa dari beberapa PR
    - [ ] Tidak, 1 PO hanya dari 1 PR

25. **Apakah PO bisa partial (dikirim bertahap)?**
    - [ ] Ya, boleh partial delivery
    - [ ] Tidak, harus full delivery

### D2. Approval PO
26. **Berapa level approval untuk PO?**
    - [ ] 1 level
    - [ ] 2 level
    - [ ] 3 level
    - [ ] Lebih dari 3 level

27. **Apakah approval PO berbeda dengan approval PR?**
    - [ ] Ya, berbeda
    - [ ] Tidak, sama
    
    Jika berbeda, sebutkan matrix:
    - < Rp ________: Approval oleh _________________
    - Rp ________ - Rp ________: Approval oleh _________________
    - > Rp ________: Approval oleh _________________

28. **Apakah perlu approval jika harga PO berbeda dengan estimasi PR?**
    - [ ] Ya, jika selisih > _____ %
    - [ ] Tidak perlu

### D3. Supplier & Payment
29. **Berapa lama payment term yang biasa digunakan?**
    - [ ] COD (Cash on Delivery)
    - [ ] Net 7
    - [ ] Net 14
    - [ ] Net 30
    - [ ] Net 60
    - [ ] Lainnya: _________________

30. **Apakah ada down payment (DP)?**
    - [ ] Ya, ada DP
    - [ ] Tidak ada DP
    - Jika ya, berapa %: _____ %

31. **Apakah PO perlu dikirim ke supplier?**
    - [ ] Ya, via email
    - [ ] Ya, via WhatsApp
    - [ ] Ya, dicetak dan diantar
    - [ ] Tidak perlu

32. **Apakah supplier perlu konfirmasi PO?**
    - [ ] Ya, wajib konfirmasi
    - [ ] Tidak perlu
    - Jika ya, berapa lama max waktu konfirmasi: _____ hari

### D4. Format PO
33. **Informasi apa saja yang harus ada di PO?**
    - [ ] Nomor PO
    - [ ] Tanggal PO
    - [ ] Supplier
    - [ ] Item & qty
    - [ ] Harga
    - [ ] Total
    - [ ] Payment term
    - [ ] Delivery address
    - [ ] Contact person
    - [ ] Terms & conditions
    - [ ] Lainnya: _________________

34. **Apakah PO perlu tanda tangan?**
    - [ ] Ya, perlu tanda tangan
    - [ ] Tidak perlu
    - Jika ya, siapa yang tanda tangan: _________________

---

## 🚚 E. DELIVERY ORDER (DO)

### E1. Proses DO
35. **Siapa yang membuat DO?**
    - [ ] Supplier (buat dan kirim ke kita)
    - [ ] Warehouse kita (input dari surat jalan supplier)
    - [ ] Purchasing (koordinasi dengan supplier)

36. **Apakah DO harus selalu dari PO?**
    - [ ] Ya, wajib dari PO
    - [ ] Tidak, bisa tanpa PO (untuk retur, dll)

37. **Apakah 1 PO bisa jadi beberapa DO (partial delivery)?**
    - [ ] Ya, boleh partial
    - [ ] Tidak, harus 1 DO untuk 1 PO

38. **Berapa lama lead time dari PO ke DO?**
    - [ ] 1-3 hari
    - [ ] 3-7 hari
    - [ ] 7-14 hari
    - [ ] > 14 hari

### E2. Tracking DO
39. **Apakah perlu tracking pengiriman real-time?**
    - [ ] Ya, sangat perlu
    - [ ] Mungkin perlu
    - [ ] Tidak perlu

40. **Jika ya, status apa saja yang perlu di-track?**
    - [ ] Confirmed (supplier konfirmasi kirim)
    - [ ] Picked up (barang diambil kurir)
    - [ ] In transit (dalam perjalanan)
    - [ ] Arrived at gate (sampai gerbang)
    - [ ] Unloading (bongkar barang)
    - [ ] Received (diterima warehouse)
    - [ ] Lainnya: _________________

41. **Siapa yang update tracking?**
    - [ ] Supplier
    - [ ] Kurir/driver
    - [ ] Warehouse kita
    - [ ] Security gate
    - [ ] Lainnya: _________________

42. **Apakah perlu notifikasi untuk tracking?**
    - [ ] Ya, setiap update status
    - [ ] Ya, hanya status penting
    - [ ] Tidak perlu

### E3. Informasi DO
43. **Informasi apa yang perlu dicatat di DO?**
    - [ ] No DO
    - [ ] No PO
    - [ ] No Surat Jalan (dari supplier)
    - [ ] Tanggal kirim
    - [ ] Estimasi tiba
    - [ ] No kendaraan
    - [ ] Nama driver
    - [ ] Telp driver
    - [ ] Item & qty
    - [ ] Lainnya: _________________

44. **Apakah perlu foto bukti pengiriman?**
    - [ ] Ya, wajib
    - [ ] Opsional
    - [ ] Tidak perlu

---

## 📥 F. GOODS RECEIPT (GR)

### F1. Proses GR
45. **Siapa yang bertanggung jawab terima barang?**
    - [ ] Warehouse staff
    - [ ] Security
    - [ ] Purchasing
    - [ ] Lainnya: _________________

46. **Apakah GR harus selalu dari DO?**
    - [ ] Ya, wajib dari DO
    - [ ] Tidak, bisa langsung dari PO
    - [ ] Tidak, bisa tanpa DO/PO (untuk retur, dll)

47. **Apakah ada proses QC (Quality Control)?**
    - [ ] Ya, wajib QC semua barang
    - [ ] Ya, QC untuk kategori tertentu
    - [ ] Tidak ada QC
    - Jika ya, siapa yang QC: _________________

### F2. Quality Control (QC)
48. **Jika ada QC, apa yang di-check?**
    - [ ] Jumlah/qty sesuai DO
    - [ ] Kondisi fisik barang
    - [ ] Kualitas barang
    - [ ] Expired date
    - [ ] Kemasan/packaging
    - [ ] Lainnya: _________________

49. **Berapa lama waktu QC?**
    - [ ] Langsung saat terima (< 1 jam)
    - [ ] Hari yang sama
    - [ ] 1-3 hari
    - [ ] > 3 hari

50. **Jika barang tidak lolos QC, apa yang dilakukan?**
    - [ ] Reject semua
    - [ ] Terima yang bagus, reject yang rusak (partial)
    - [ ] Retur ke supplier
    - [ ] Claim ke supplier
    - [ ] Lainnya: _________________

### F3. Dokumentasi GR
51. **Dokumen apa yang perlu disimpan?**
    - [ ] Surat jalan asli
    - [ ] Foto barang
    - [ ] Berita acara serah terima
    - [ ] Hasil QC
    - [ ] Invoice
    - [ ] Lainnya: _________________

52. **Berapa lama dokumen disimpan?**
    - [ ] 1 tahun
    - [ ] 3 tahun
    - [ ] 5 tahun
    - [ ] Selamanya

### F4. Update Stock
53. **Kapan stock di-update?**
    - [ ] Langsung saat GR dibuat
    - [ ] Setelah QC passed
    - [ ] Setelah GR di-post/approve
    - [ ] Manual oleh admin

54. **Apakah ada lokasi penyimpanan (bin location)?**
    - [ ] Ya, ada
    - [ ] Tidak ada
    - Jika ya, berapa lokasi: _____ lokasi

---

## 💰 G. PAYMENT & INVOICE

### G1. Invoice
55. **Kapan invoice diterima dari supplier?**
    - [ ] Bersamaan dengan barang
    - [ ] Setelah barang diterima
    - [ ] Setelah GR di-post
    - [ ] Sesuai kesepakatan

56. **Apakah invoice harus match dengan PO & GR?**
    - [ ] Ya, wajib match (3-way matching)
    - [ ] Tidak harus match
    - [ ] Toleransi selisih: _____ %

57. **Jika ada selisih, siapa yang approve?**
    - [ ] Finance
    - [ ] Purchasing
    - [ ] Manager
    - [ ] Lainnya: _________________

### G2. Payment
58. **Kapan pembayaran dilakukan?**
    - [ ] Sesuai payment term di PO
    - [ ] Setelah invoice diterima
    - [ ] Setelah barang diterima
    - [ ] Lainnya: _________________

59. **Apakah ada prioritas pembayaran?**
    - [ ] Ya, ada prioritas
    - [ ] Tidak, FIFO (first in first out)
    - Jika ya, berdasarkan apa: _________________

60. **Metode pembayaran apa yang digunakan?**
    - [ ] Transfer bank
    - [ ] Cek/giro
    - [ ] Cash
    - [ ] Lainnya: _________________

---

## 📊 H. REPORTING & MONITORING

### H1. Dashboard
61. **Dashboard apa yang dibutuhkan?**
    - [ ] PR outstanding (pending approval)
    - [ ] PO outstanding (belum diterima)
    - [ ] DO in transit (sedang dikirim)
    - [ ] GR pending QC
    - [ ] Payment due (jatuh tempo)
    - [ ] Lainnya: _________________

62. **Siapa yang perlu akses dashboard?**
    - [ ] Management
    - [ ] Purchasing
    - [ ] Warehouse
    - [ ] Finance
    - [ ] Semua user
    - [ ] Lainnya: _________________

### H2. Reports
63. **Laporan apa yang dibutuhkan?**
    - [ ] PR status report
    - [ ] PO outstanding report
    - [ ] GR summary report
    - [ ] Supplier performance report
    - [ ] Budget vs actual report
    - [ ] Aging hutang report
    - [ ] Lainnya: _________________

64. **Seberapa sering laporan dibutuhkan?**
    - [ ] Real-time
    - [ ] Harian
    - [ ] Mingguan
    - [ ] Bulanan
    - [ ] On-demand

### H3. KPI (Key Performance Indicator)
65. **KPI apa yang perlu dimonitor?**
    - [ ] PR approval time (target: _____ hari)
    - [ ] PO to GR time (target: _____ hari)
    - [ ] GR QC pass rate (target: _____ %)
    - [ ] Supplier on-time delivery (target: _____ %)
    - [ ] Budget variance (target: _____ %)
    - [ ] Lainnya: _________________

---

## 🔐 I. SECURITY & ACCESS CONTROL

### I1. User Access
66. **Apakah setiap user punya role berbeda?**
    - [ ] Ya, ada role-based access
    - [ ] Tidak, semua user sama

67. **Jika ya, sebutkan role dan aksesnya:**
    - Role: _________ | Akses: _________________
    - Role: _________ | Akses: _________________
    - Role: _________ | Akses: _________________

68. **Apakah user bisa lihat data cabang lain?**
    - [ ] Ya, bisa lihat semua cabang
    - [ ] Tidak, hanya cabang sendiri
    - [ ] Tergantung role

### I2. Audit Trail
69. **Apakah perlu audit trail (log aktivitas)?**
    - [ ] Ya, sangat perlu
    - [ ] Mungkin perlu
    - [ ] Tidak perlu

70. **Jika ya, aktivitas apa yang perlu di-log?**
    - [ ] Create/edit/delete data
    - [ ] Approval/reject
    - [ ] Print dokumen
    - [ ] Export data
    - [ ] Login/logout
    - [ ] Semua aktivitas

---

## 🔄 J. INTEGRASI

### J1. Integrasi Internal
71. **Apakah perlu integrasi dengan modul lain?**
    - [ ] Inventory/Stock
    - [ ] Accounting/Finance
    - [ ] Budget
    - [ ] Asset Management
    - [ ] Lainnya: _________________

72. **Apakah perlu integrasi dengan sistem existing?**
    - [ ] Ya
    - [ ] Tidak
    - Jika ya, sistem apa: _________________

### J2. Integrasi External
73. **Apakah perlu integrasi dengan supplier?**
    - [ ] Ya, supplier bisa akses sistem
    - [ ] Ya, via API
    - [ ] Tidak perlu

74. **Apakah perlu integrasi dengan ekspedisi?**
    - [ ] Ya, untuk tracking
    - [ ] Tidak perlu

---

## 📱 K. TEKNOLOGI & INFRASTRUKTUR

### K1. Platform
75. **Platform apa yang digunakan?**
    - [ ] Web-based (browser)
    - [ ] Mobile app (Android/iOS)
    - [ ] Desktop app
    - [ ] Semua platform

76. **Apakah perlu akses offline?**
    - [ ] Ya, perlu offline mode
    - [ ] Tidak perlu

### K2. Notifikasi
77. **Channel notifikasi apa yang digunakan?**
    - [ ] Email
    - [ ] WhatsApp
    - [ ] SMS
    - [ ] Push notification (app)
    - [ ] In-system notification
    - [ ] Lainnya: _________________

78. **Apakah perlu reminder otomatis?**
    - [ ] Ya, untuk approval pending
    - [ ] Ya, untuk payment due
    - [ ] Ya, untuk DO delay
    - [ ] Tidak perlu

---

## 🎯 L. PRIORITAS & TIMELINE

### L1. Prioritas Modul
79. **Urutkan prioritas modul (1 = paling penting):**
    - [ ] ___ Purchase Request (PR)
    - [ ] ___ Request for Quotation (RFQ)
    - [ ] ___ Purchase Order (PO)
    - [ ] ___ Delivery Order (DO)
    - [ ] ___ Goods Receipt (GR)
    - [ ] ___ Reports & Dashboard

### L2. Timeline
80. **Kapan target modul ini harus live?**
    - [ ] < 1 bulan
    - [ ] 1-3 bulan
    - [ ] 3-6 bulan
    - [ ] > 6 bulan
    - Target tanggal: _________________

81. **Apakah ada fase implementasi?**
    - [ ] Ya, bertahap per modul
    - [ ] Ya, bertahap per cabang
    - [ ] Tidak, langsung semua
    - Jika bertahap, sebutkan: _________________

### L3. Budget
82. **Apakah ada budget limit untuk development?**
    - [ ] Ya, budget: Rp _________________
    - [ ] Tidak ada limit

---

## 📝 M. CATATAN TAMBAHAN

83. **Apakah ada proses khusus yang belum tercakup di atas?**
    
    _________________________________________________________________
    _________________________________________________________________
    _________________________________________________________________

84. **Apakah ada pain point di sistem pembelian saat ini?**
    
    _________________________________________________________________
    _________________________________________________________________
    _________________________________________________________________

85. **Fitur apa yang paling diharapkan dari modul baru?**
    
    _________________________________________________________________
    _________________________________________________________________
    _________________________________________________________________

86. **Apakah ada referensi sistem lain yang ingin ditiru?**
    
    _________________________________________________________________
    _________________________________________________________________
    _________________________________________________________________

87. **Siapa PIC (Person in Charge) untuk modul ini?**
    
    - Nama: _________________
    - Jabatan: _________________
    - Email: _________________
    - Telp: _________________

88. **Siapa yang akan menjadi key user untuk testing?**
    
    - User 1: _________ (Role: _________)
    - User 2: _________ (Role: _________)
    - User 3: _________ (Role: _________)

---

## ✅ CHECKLIST DOKUMEN PENDUKUNG

Mohon dilampirkan dokumen berikut (jika ada):

- [ ] Struktur organisasi perusahaan
- [ ] Daftar supplier aktif
- [ ] Contoh form PR yang digunakan saat ini
- [ ] Contoh PO yang digunakan saat ini
- [ ] Contoh surat jalan/DO dari supplier
- [ ] Contoh invoice dari supplier
- [ ] SOP pembelian yang berlaku saat ini
- [ ] Approval matrix yang berlaku
- [ ] Budget tahunan procurement
- [ ] Laporan pembelian 3 bulan terakhir

---

## 📞 KONTAK

**Jika ada pertanyaan terkait questionnaire ini, hubungi:**

- Email: procurement.dev@fitmotorbengkel.com
- Phone: 08123456789
- WhatsApp: 08123456789

---

**Terima kasih atas partisipasinya!**

**Mohon isi questionnaire ini dengan lengkap dan kirim kembali ke tim development.**

---

*Dokumen ini dibuat pada: 3 November 2025*
*Version: 1.0*
