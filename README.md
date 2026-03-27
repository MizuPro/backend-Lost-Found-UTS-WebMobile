# CommuterLink Nusantara — Lost & Found API

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892bf.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**CommuterLink Nusantara Lost & Found API** adalah RESTful API yang dirancang untuk mengelola sistem penemuan barang tertinggal dan laporan kehilangan di lingkungan transportasi publik KRL. Sistem ini memfasilitasi pencatatan barang, pelaporan kehilangan, hingga proses pencocokan dan penyerahan barang secara digital.

---

## 🚀 Fitur Utama

- **Autentikasi JWT**: Keamanan akses menggunakan JSON Web Token (JWT).
- **Role-Based Access Control (RBAC)**:
  - **Petugas**: Manajemen penuh data barang temuan, laporan kehilangan, dan proses pencocokan.
  - **Pelapor**: Membuat laporan kehilangan, melihat daftar barang temuan (informasi terbatas), dan mengelola profil sendiri.
- **Matching Engine**: Membantu petugas mencocokkan laporan kehilangan dengan barang yang ditemukan.
- **Upload Foto**: Dukungan unggah foto barang temuan untuk verifikasi lebih akurat.
- **Soft Delete**: Pengarsipan barang yang sudah selesai tanpa menghapus data secara permanen dari database.

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP (Native dengan Arsitektur MVC)
- **Database**: MySQL / MariaDB
- **Autentikasi**: Firebase JWT PHP
- **Web Server**: Laragon / Apache / Nginx

---

## 📁 Struktur Proyek

```text
backend-Lost-Found/
├── config/             # Konfigurasi Database & JWT
├── controllers/        # Logika Bisnis (MVC)
├── helpers/            # Fungsi utilitas (Response, Auth, dsb)
├── middleware/         # Proteksi Route (Auth & Role)
├── models/             # Interaksi Database
├── routes/             # Definisi Endpoint API
├── storage/            # Tempat penyimpanan upload foto
├── database.sql        # Skema Database
├── API_DOCUMENTATION_versi2.md # Dokumentasi OpenAPI 3.0
└── index.php           # Entry Point & Router
```

---

## ⚙️ Instalasi & Persiapan

### Prasyarat
- PHP >= 7.4
- MySQL / MariaDB
- Composer

### Langkah-langkah
1. **Clone Repositori**:
   ```bash
   git clone https://github.com/username/backend-Lost-Found.git
   cd backend-Lost-Found
   ```

2. **Install Dependensi**:
   ```bash
   composer install
   ```

3. **Setup Database**:
   - Buat database baru bernama `lost_found_db`.
   - Import file `database.sql` ke dalam database tersebut.

4. **Konfigurasi**:
   - Sesuaikan kredensial database di dalam folder `config/`.

5. **Jalankan Server**:
   Jika menggunakan PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
   Atau arahkan folder root ke server Laragon/XAMPP Anda.

---

## 📖 Dokumentasi API

Dokumentasi lengkap mengenai endpoint, parameter, dan contoh response dapat dilihat pada file:
👉 **[API_DOCUMENTATION_versi2.md](./API_DOCUMENTATION_versi2.md)**

### Endpoint Populer:
- `POST /api/auth/login` - Login pengguna
- `GET /api/found-items` - Daftar barang temuan
- `POST /api/lost-reports` - Buat laporan kehilangan
- `POST /api/matches` - Proses pencocokan (Petugas)

---

## 👥 Kontributor
- **Kelompok x** - Project Studi Kasus CommuterLink Nusantara
- **Universitas Bunda Mulia**

---

## 📄 Lisensi
Distributed under the MIT License.
