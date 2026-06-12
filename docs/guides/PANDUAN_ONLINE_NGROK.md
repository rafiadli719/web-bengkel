# Panduan Meng-Online-kan Project dengan Ngrok

Dokumen ini menjelaskan cara menggunakan **Ngrok** untuk membuat project web lokal (XAMPP) anda bisa diakses dari internet (HP, Laptop lain, atau Client).

## 1. Apa itu Ngrok?
Ngrok adalah tool yang membuat "terowongan" (tunnel) aman dari internet publik ke komputer lokal anda. Ini berguna untuk demo web tanpa hosting.

## 2. Persiapan Awal
Pastikan:
1. **XAMPP** sudah berjalan (Apache & MySQL Start).
2. Project anda bisa dibuka di browser lokal (misal: `http://localhost/web-bengkel/aplikasi/aplikasi/`).
3. File `ngrok.exe` sudah ada di folder project (sudah di-download sebelumnya).

## 3. Setup Pertama Kali (Hanya Sekali)
Jika anda memindahkan folder project atau mengganti komputer, lakukan ini lagi:

1. Daftar akun di [dashboard.ngrok.com](https://dashboard.ngrok.com/signup).
2. Login dan ambil **Authtoken** anda di menu "Your Authtoken".
3. Buka Terminal/Command Prompt di folder project.
4. Jalankan perintah untuk menyimpan token:
   ```powershell
   .\ngrok config add-authtoken <TOKEN_ANDA>
   ```
   *(Ganti `<TOKEN_ANDA>` dengan kode panjang dari dashboard ngrok)*

## 4. Cara Menjalankan (Setiap Kali Mau Online)
Setiap kali anda ingin website bisa diakses orang lain:

1. Buka folder `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi` di File Explorer.
2. Klik kanan di ruang kosong -> Pilih **"Open in Terminal"** (atau Shift + Klik Kanan -> "Open PowerShell window here").
3. Ketik perintah berikut dan tekan Enter:
   ```powershell
   .\ngrok http 80
   ```
   *(Angka `80` adalah port Apache XAMPP, jika anda ubah port XAMPP, sesuaikan angkanya)*

## 5. Mengakses Web
Setelah perintah dijalankan:
1. Akan muncul tampilan status Ngrok di terminal (Status: `online`).
2. Cari baris **Forwarding**. Anda akan melihat alamat seperti:
   `https://xxxx-xxxx.ngrok-free.app -> http://localhost:80`
3. Copy alamat `https://xxxx-xxxx.ngrok-free.app` tersebut.
4. Tambahkan folder project anda dibelakangnya.
   
   **Contoh URL Lengkap:**
   ```
   https://xxxx-xxxx.ngrok-free.app/web-bengkel/aplikasi/aplikasi/
   ```

## 6. Catatan Penting
*   **Terminal Jangan Ditutup**: Selama terminal Ngrok terbuka, web bisa diakses. Jika ditutup, web offline.
*   **URL Berubah**: Karena menggunakan versi gratis, setiap kali anda mematikan dan menyalakan Ngrok, URL depan (`https://xxxx...`) akan berubah. Pastikan selalu share link terbaru.
*   **Session Expired**: Link versi gratis biasanya berlaku selama 2 jam atau sampai sesi habis, cukup restart ngrok jika mati.

## 7. Troubleshooting
*   **Command not found**: Pastikan anda mengetik `.\ngrok` (dengan titik dan backslash) di PowerShell, bukan cuma `ngrok`.
*   **ERR_NGROK_4018**: Artinya Token salah atau belum dimasukkan. Ulangi langkah Setup Token.
*   **XAMPP Error**: Pastikan di localhost biasa web anda jalan normal dulu.
