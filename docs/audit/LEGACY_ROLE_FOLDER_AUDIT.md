# Audit Folder Role Legacy

## Ringkasan

Folder role lama yang ditemukan:

- `_admin`
- `_hrd`
- `_kasir`
- `_pengadaan`
- `_managemen`
- `_booking`

## Hasil Cek Keterkaitan

Audit awal menunjukkan bahwa alur aktif aplikasi sekarang sudah terpusat di:

- `login.php`
- `cek_login.php`
- `panel/` -> junction ke `/_admincab/`
- `/_admincab/` sebagai implementation layer utama

Pengecekan referensi dari root aktif dan `_admincab` tidak menunjukkan dependency runtime langsung ke folder role lama di atas.

Temuan penting:

- login aktif sudah diarahkan ke `/panel/`
- `/_admincab/menu_dashboard.php` sudah memakai RBAC dinamis
- root dan `_admincab` tidak lagi memakai redirect aktif ke `_hrd`, `_kasir`, `_pengadaan`, `_managemen`, `_admin`, atau `_booking`

## Kesimpulan Sementara

Folder role lama sangat mungkin merupakan modul legacy atau snapshot arsitektur lama, bukan jalur utama aplikasi saat ini.

Namun folder-folder tersebut **belum dipindahkan/dihapus** pada tahap ini karena:

- ukurannya masih besar
- kemungkinan masih ada deep link manual yang pernah dipakai user lama
- perlu audit lanjutan per folder untuk memastikan tidak ada fitur unik yang belum terserap ke `_admincab`

## Rekomendasi Tahap Berikutnya

1. Audit per folder legacy: bandingkan file unik terhadap `_admincab`.
2. Jika tidak dipakai, pindahkan folder-role lama ke arsip terpisah.
3. Sisakan stub `index.php` redirect ke `/panel/` untuk menjaga kompatibilitas URL lama.
4. Setelah masa observasi aman, baru hapus arsip yang benar-benar tidak dibutuhkan.
