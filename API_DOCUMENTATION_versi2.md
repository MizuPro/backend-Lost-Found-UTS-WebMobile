# 📖 Dokumentasi API CommuterLink Nusantara Lost & Found (Versi Mudah Dibaca)

Selamat datang di Dokumentasi API untuk sistem *Lost & Found CommuterLink Nusantara*. Dokumen ini disusun khusus agar sangat mudah dibaca tanpa perlu melihat code OpenAPI yang panjang.

## ℹ️ Informasi Umum
- **Base URL API :** `http://localhost/backend-Lost-Found`
- **Format Response :** Selalu `JSON`
- **Autentikasi :** JWT Bearer Token. Hampir semua endpoint ditandai dengan 🔒 wajib mengirimkan header: \`Authorization: Bearer <token_anda>\`
- **Aturan Role (Hak Akses):** Terdapat dua role utama.
  - **Petugas 👮‍♂️ :** Bisa mengubah segala data (Admin dari sistem).
  - **Pelapor 🙎‍♂️ :** Penumpang yang lapor / ngecek barangnya. Hanya mendapatkan info terbatas.

---

## 🟢 1. Autentikasi User (Auth)

### Register Akun Baru
- **Method:** `POST`
- **Endpoint:** `/api/auth/register`
- **Deskripsi:** Mendaftar akun baru ke aplikasi.
- **Parameter yang dikirim:**
  - `name` (Wajib)
  - `email` (Wajib, format email)
  - `password` (Wajib, minimal 8 karakter)
  - `role` (Wajib, diisi: `petugas` atau `pelapor`)

### Login (Masuk)
- **Method:** `POST`
- **Endpoint:** `/api/auth/login`
- **Deskripsi:** Menghasilkan Token JWT. Simpan token ini untuk mengakses halaman lain!
- **Parameter yang dikirim:**
  - `email` (Wajib)
  - `password` (Wajib)

### 🔒 Logout (Keluar)
- **Method:** `POST`
- **Endpoint:** `/api/auth/logout`
- **Deskripsi:** Logout aplikasi. *Ingat: Token di frontend / HP juga harus dihapus manual.*

### 🔒 Profil Saya
- **Method:** `GET`
- **Endpoint:** `/api/auth/me`
- **Deskripsi:** Mengecek pengguna yang saat ini sedang login beserta rolenya.

### 🔒 Update Profil
- **Method:** `PUT`
- **Endpoint:** `/api/auth/profile`
- **Deskripsi:** Mengubah nama atau alamat email. Jika sukses, email / nama baru langsung aktif.
- **Parameter yang dikirim:**
  - `name` (Wajib)
  - `email` (Wajib)

### 🔒 Ganti Password
- **Method:** `PUT`
- **Endpoint:** `/api/auth/change-password`
- **Deskripsi:** Proses ubah password. Setelah sukses, token akan hangus dan pengguna wajib login ulang.
- **Parameter yang dikirim:**
  - `current_password` (Wajib)
  - `new_password` (Wajib)

---

## 👜 2. Kelola Barang Temuan (Found Items)
*Bagian ini adalah daftar barang yang ditemukan kececer stasiun/kereta.*

### 🔒 Daftar Semua Barang Temuan
- **Method:** `GET`
- **Endpoint:** `/api/found-items`
- **Deskripsi:** Menghasilkan seluruh list barang. 
  - *Petugas:* Melihat detail foto, lokasi rinci, dan catatan rahasia.
  - *Pelapor:* Hanya bisa melihat ID, nama barang, waktu ditemukan, dan status. (Mencegah pencurian barang dari foto aplikasi).
- **Filter Pencarian (Boleh ditambahkan di ujung URL):** `?status=...` , `?search=...` , `?lokasi=...` (khusus petugas).

### 🔒 Daftar Barang Temuan (Status Selesai)
- **Method:** `GET`
- **Endpoint:** `/api/found-items/selesai`
- **Deskripsi:** List barang yang perkaranya sudah rampung (sudah dikembalikan ke pemiliknya).

### 🔒 Daftar Barang Temuan (Masih Diproses)
- **Method:** `GET`
- **Endpoint:** `/api/found-items/ongoing`
- **Deskripsi:** List barang yang belum bertuan / proses cocok / dll.

### 🔒 Tambah Barang Temuan Baru *(Hanya Petugas)*
- **Method:** `POST`
- **Endpoint:** `/api/found-items`
- **Deskripsi:** Menginput barang yang baru ditemukan petugas. Bisa menggunakan `multipart/form-data` jika mau upload gambar.
- **Parameter yang dikirim:**
  - `nama_barang` (Wajib)
  - `lokasi` (Wajib)
  - `waktu_temuan` (Wajib, Harus Format: `YYYY-MM-DD HH:MM:SS`)
  - `deskripsi` (Boleh kosong)
  - `foto` (Boleh kosong, Maksimal 5 MB)

### 🔒 Cek Detail Satu Barang Temuan
- **Method:** `GET`
- **Endpoint:** `/api/found-items/{id}`
- **Deskripsi:** Menampilkan secara mendalam terkait barang spesifik. Data dibatasi untuk role pelapor layaknya melihat list utamanya.

### 🔒 Edit Barang Temuan *(Hanya Petugas)*
- **Method:** `PUT`
- **Endpoint:** `/api/found-items/{id}`
- **Deskripsi:** Merubah informasi barang. Jika mengupload foto baru, secara sistem foto lama akan terhapus otomatis.

### 🔒 Selesaikan / Arsipkan Sistem Secara Manual *(Hanya Petugas)*
- **Method:** `PATCH`
- **Endpoint:** `/api/found-items/{id}/archive`
- **Deskripsi:** Barang ditutup / diselesaikan perkaranya (tanpa hapus data / cuma di soft-delete).
- **Catatan Tambahan (Opsional di-Post):** `catatan_selesai`

---

## 🔍 3. Laporan Kehilangan (Lost Reports)
*Bagian keluh-kesah penumpang yang merasa kehilangan barang.*

### 🔒 Daftar Laporan Hilang
- **Method:** `GET`
- **Endpoint:** `/api/lost-reports`
- **Deskripsi:**
  - *Petugas:* Melihat semua antrian laporan kehilangan dari semua penumpang.
  - *Pelapor:* Berjalan secara otomatis hanya nampilin laporan milik diri sendiri.

### 🔒 Buat Laporan Kehilangan
- **Method:** `POST`
- **Endpoint:** `/api/lost-reports`
- **Deskripsi:** Penumpang mengabarkan bahwa barangnya hilang ke sistem. User ID akan diikat otomatis ke sistem berdasarkan token login.
- **Parameter yang dikirim:**
  - `nama_barang` (Wajib)
  - `lokasi` (Wajib)
  - `waktu_hilang` (Wajib, Format: `YYYY-MM-DD HH:MM:SS`)
  - `deskripsi` (Boleh kosong, isi sejelas-jelasnya biar cepat ketemu)

### 🔒 Cek Spesifik Detail Laporan Hilang
- **Method:** `GET`
- **Endpoint:** `/api/lost-reports/{id}`
- **Deskripsi:** Menarik detil informasi lengkap untuk sebuah laporan kehilangan. (Pelapor dilarang kepo ngeliat laporan milik penumpang ID lain).

### 🔒 Edit Detail Laporan Hilang
- **Method:** `PUT`
- **Endpoint:** `/api/lost-reports/{id}`
- **Deskripsi:**
  - *Petugas:* Bisa melancarkan pengubahan status kapan saja dsb.
  - *Pelapor:* Hanya boleh mengedit jika barang masih berstatus `menunggu`, dan pelapor bisa mengubah paksa status laporannya jadi `ditutup`.

### 🔒 Hapus Laporan Selamanya *(Hanya Petugas)*
- **Method:** `DELETE`
- **Endpoint:** `/api/lost-reports/{id}`
- **Deskripsi:** Menghilangkan laporan ke format Hard Delete (Menghilang seutuhnya dari Database). Akan ditolak jika pas barangnya masih status proses cocok.

---

## 🤝 4. Pencocokan & Penyerahan Barang (Matches)
*Secara keseluruhan seluruh fitur disini **KHUSUS PETUGAS**, kecuali pelapor yang mau ngintip detail proses di menu `GET /{id}`.*

### 🔒 Lihat Seluruh List Hubungan / Pencocokan
- **Method:** `GET`
- **Endpoint:** `/api/matches`
- **Deskripsi:** Melihat semua barang yang mulai dikawinkan dengan laporan kehilangan.

### 🔒 Menjodohkan Barang (Match Baru)
- **Method:** `POST`
- **Endpoint:** `/api/matches`
- **Deskripsi:** Melakukan proses menyandingkan laporan yang pas dengan barang temuan di gudang. Status otomatis mendarat menjadi `pending`.
- **Parameter yang dikirim:**
  - `barang_temuan_id` (Wajib)
  - `laporan_id` (Wajib)

### 🔒 Detail Status Pencocokan Tersebut
- **Method:** `GET`
- **Endpoint:** `/api/matches/{id}`
- **Deskripsi:** Melihat seberapa jauh status kecocokkan. *(Pelapor akan di-allow akses hanya jika laporannya ada disitu).*

### 🔒 Validasi bahwa Benda itu Valid
- **Method:** `PUT`
- **Endpoint:** `/api/matches/{id}/verify`
- **Deskripsi:** Petugas memverifikasi kecocokan barang (misal: "Oh iya difoto benar KTP si bapak itu"). Status disuntik jadi `diverifikasi`.
- **Parameter yang dikirim (Opsional):** `catatan`

### ⚠️ Endpoint Lama Serah Terima (Deprecated)
- **Method:** `PUT`
- **Endpoint:** `/api/matches/{id}/handover`
- **Deskripsi:** Endpoint ini **sudah tidak dipakai** dan akan mengembalikan status `410 Gone`.
- **Migrasi:** Gunakan endpoint baru `PUT /api/pickup-schedules/{id}/complete`.

### 🔒 Batal & Putuskan Hubungannya
- **Method:** `PUT`
- **Endpoint:** `/api/matches/{id}/cancel`
- **Deskripsi:** Barang dan Laporan digagalkan pencocokannya. Barang dilempar lagi ke status pencarian aktif awal seperti sedia kala.
- **Parameter yang dikirim (Opsional):** `catatan`

---

## 📅 5. Penjadwalan Pengambilan (Pickup Schedules)
*Modul ini terikat ke `match_id`. Alurnya: pelapor ajukan jadwal, petugas review, lalu petugas menyelesaikan serah-terima lewat endpoint complete.*

### Status Jadwal yang Digunakan
- `menunggu_persetujuan` → pengajuan baru dari pelapor
- `disetujui` → disetujui petugas
- `ditolak` → ditolak petugas
- `dibatalkan` → dibatalkan pelapor (hanya saat masih menunggu)
- `selesai` → serah terima sudah dicatat selesai oleh petugas

### 🔒 Daftar Jadwal Pengambilan
- **Method:** `GET`
- **Endpoint:** `/api/pickup-schedules`
- **Akses:** Petugas & Pelapor
- **Deskripsi:**
  - *Petugas:* Melihat semua jadwal.
  - *Pelapor:* Hanya melihat jadwal miliknya.
- **Filter Opsional:** `?status=...` dan `?match_id=...`

### 🔒 Detail Jadwal Pengambilan
- **Method:** `GET`
- **Endpoint:** `/api/pickup-schedules/{id}`
- **Akses:** Petugas & Pelapor
- **Deskripsi:** Menampilkan detail satu jadwal. Pelapor hanya boleh mengakses jadwal miliknya.

### 🔒 Ajukan Jadwal Pengambilan *(Pelapor)*
- **Method:** `POST`
- **Endpoint:** `/api/pickup-schedules`
- **Deskripsi:** Pelapor mengajukan jadwal untuk `match` yang sudah `diverifikasi`.
- **Parameter yang dikirim:**
  - `match_id` (Wajib)
  - `waktu_jadwal` (Wajib, format `YYYY-MM-DD HH:MM:SS`)
  - `lokasi_pengambilan` (Wajib)
  - `catatan` (Opsional)
- **Catatan aturan:** Satu `match_id` hanya boleh punya satu jadwal aktif (`menunggu_persetujuan` atau `disetujui`).

### 🔒 Review Pengajuan Jadwal *(Petugas)*
- **Method:** `PUT`
- **Endpoint:** `/api/pickup-schedules/{id}/review`
- **Deskripsi:** Petugas menyetujui atau menolak pengajuan jadwal.
- **Parameter yang dikirim:**
  - `action` (Wajib, isi: `disetujui` atau `ditolak`)
  - `catatan` (Opsional)

### 🔒 Ubah Jadwal Aktif *(Petugas)*
- **Method:** `PUT`
- **Endpoint:** `/api/pickup-schedules/{id}/reschedule`
- **Deskripsi:** Petugas mengubah waktu/lokasi untuk jadwal aktif.
- **Parameter yang dikirim:**
  - `waktu_jadwal` (Wajib, format `YYYY-MM-DD HH:MM:SS`)
  - `lokasi_pengambilan` (Wajib)
  - `catatan` (Opsional)

### 🔒 Batalkan Pengajuan Jadwal *(Pelapor)*
- **Method:** `PUT`
- **Endpoint:** `/api/pickup-schedules/{id}/cancel`
- **Deskripsi:** Pelapor membatalkan pengajuan **hanya jika status masih** `menunggu_persetujuan`.
- **Parameter yang dikirim (Opsional):** `catatan`

### 🔒 Selesaikan Pengambilan / Serah Terima *(Petugas)*
- **Method:** `PUT`
- **Endpoint:** `/api/pickup-schedules/{id}/complete`
- **Deskripsi:** Endpoint finalisasi baru pengambilan barang. Saat sukses:
  - status jadwal menjadi `selesai`
  - status pencocokan (`match`) menjadi `selesai`
  - barang temuan diarsipkan (`selesai`)
  - laporan kehilangan ditandai `selesai`
- **Parameter yang dikirim (Opsional):** `catatan`

---

## 💬 6. Integrasi Chat Firebase (Manual)
*Modul komunikasi antara Petugas dan Pelapor terkait laporan kehilangan. Chat sepenuhnya berbasis Firebase, dan backend hanya berfungsi sebagai pembuat Room ID.*

### 🔒 Dapatkan Firebase Custom Token
- **Method:** `GET`
- **Endpoint:** `/api/chat/firebase-token`
- **Akses:** Petugas & Pelapor
- **Deskripsi:** Backend memberikan token untuk digunakan frontend login ke sistem Firebase (via `signInWithCustomToken`).

### 🔒 Daftar Chat Room
- **Method:** `GET`
- **Endpoint:** `/api/chat-rooms`
- **Akses:** Petugas & Pelapor
- **Deskripsi:** Menghasilkan list room chat yang Anda ikuti.

### 🔒 Mulai Chat Baru *(Hanya Petugas)*
- **Method:** `POST`
- **Endpoint:** `/api/chat-rooms`
- **Akses:** Petugas
- **Deskripsi:** Petugas memicu pembuatan room baru berdasarkan Laporan Kehilangan milik pelapor. 
- **Parameter yang dikirim:**
  - `laporan_id` (Wajib, ID Laporan Kehilangan)

### 🔒 Selesaikan / Akhiri Sesi Chat *(Hanya Petugas)*
- **Method:** `PUT`
- **Endpoint:** `/api/chat-rooms/{id}/end`
- **Akses:** Petugas
- **Deskripsi:** Petugas secara manual mengakhiri obrolan. Setelah dieksekusi, statusnya di Database menjadi `selesai` dan tidak bisa di chat lagi.

---

### *Panduan Khusus Frontend: Implementasi Realtime Chat dengan Firebase*
Berikut adalah panduan bagi tim Frontend (Web/Mobile) untuk mengintegrasikan Chat Room ini ke sistem UI menggunakan Firebase Realtime Database.

**A. Akses Konfigurasi Firebase Project**
Gunakan kredensial berikut untuk melakukan inisialisasi awal Firebase SDK di aplikasi frontend Anda (via modul `initializeApp`):
```javascript
const FIREBASE_CONFIG = {
    apiKey: "AIzaSyDXjBuF3q4Ibihi_6dWdbUzZZejKjAsKTI",
    authDomain: "ujian-project---pemwebmob.firebaseapp.com",
    databaseURL: "https://ujian-project---pemwebmob-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "ujian-project---pemwebmob",
    storageBucket: "ujian-project---pemwebmob.firebasestorage.app",
    messagingSenderId: "542420998647",
    appId: "1:542420998647:web:fc894fd939e1e21b3cb6f2"
};
```

**B. Alur Autentikasi Chat (Custom Token)**
1. Setelah pengguna Anda Login standar melalui API (`/api/auth/login`) dan mendapatkan Bearer Token JWT, panggil endpoint `GET /api/chat/firebase-token`.
2. Extrak value `firebase_token` dari respons server.
3. Login ke SDK Firebase menggunakan token tersebut via fungsi `signInWithCustomToken(auth, firebase_token)`.

**C. Skema Penyimpanan Pesan di Database Firebase**
Setiap Room dipisahkan berdasarkan `firebase_room_id` yang Anda dapatkan dari list chat. 
Path/Reference (Ref) untuk mendengarkan pesan adalah: `chats/{firebase_room_id}`

Format data (JSON) setiap pesan yang **harus** dipush (`push()`) ke Firebase oleh Frontend saat pengguna mengirim pesan:
```json
{
  "sender_id": "3",
  "sender_name": "Admin Petugas",
  "sender_username": "petugas",
  "text": "Teks pesan yang dikirim...",
  "timestamp": 1711582200000 
}
```
> *Catatan: Waktu (`timestamp`) di frontend disarankan memakai tipe Unix Epoch-milisecond (contoh `Date.now()`). Info `sender_name` & `sender_username` bisa diambil oleh frontend dengan men-decode payload JWT (Token asli dari login atau dari return data).*
