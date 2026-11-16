# 📚 PerpusApp — Sistem Informasi Manajemen Perpustakaan

**PerpusApp** adalah aplikasi web manajemen perpustakaan berbasis **Laravel** dengan arsitektur **MVC (Model-View-Controller)**, **Eloquent ORM**, serta kontrol akses berbasis peran (**Role-Based Access Control**). Aplikasi ini mengelola koleksi buku, kategori, data anggota, sirkulasi peminjaman dan pengembalian buku, hingga pelaporan PDF.

---

## 👥 Tim Pengembang (Kelas 5D)
* **Nurul Annissa Azzahra**
* **Muhammad Syifaaur Rahman**
* **Muhammad Luthfi Ramadhan**

---

## 🚀 Fitur & Hak Akses (Role)

### 1. Admin
* Login & Logout (Protected Routes dengan Middleware)
* **Dashboard Statistik:** Menampilkan total judul buku, total stok buku tersedia, jumlah anggota, jumlah buku sedang dipinjam (beserta rincian popup stok), dan total denda.
* **User Management:** Melihat, menambah, mengubah, menghapus akun pengguna, serta mengatur role (`admin`, `petugas`, `anggota`).
* **Category Management:** CRUD penuh kategori buku (tambah, lihat, edit, hapus).
* **Book Management:** CRUD penuh koleksi buku, detail buku, upload sampul buku, serta sinkronisasi stok otomatis.
* **Member Management:** CRUD penuh data anggota beserta detail profil dan riwayat peminjamannya.
* **Borrowing Management:** Mencatat transaksi peminjaman, pengembalian, denda keterlambatan/buku hilang, filter transaksi, dan cetak laporan PDF.

### 2. Petugas
* Login & Logout + Dashboard Statistik Perpustakaan.
* **Category Management:** Melihat daftar kategori buku (*Read-Only*).
* **Book Management:** CRUD penuh koleksi buku, detail buku, dan manajemen stok.
* **Member Management:** CRUD penuh data anggota dan detail riwayat peminjaman anggota.
* **Borrowing Management:** Mengelola transaksi peminjaman dan pengembalian buku serta cetak laporan sirkulasi PDF.

### 3. Anggota
* Login & Logout + Dashboard ringkasan pinjaman aktif.
* **Katalog Buku:** Menelusuri daftar buku lengkap dengan pencarian *real-time* (AJAX) dan filter kategori, serta peminjaman mandiri.
* **Riwayat Saya:** Melihat riwayat transaksi peminjaman pribadi dan melakukan pengembalian buku mandiri.

---

## 🛠️ Teknologi yang Digunakan
* **Backend:** PHP 8.4, Laravel Framework
* **Database:** MySQL (Eloquent ORM & Migrations)
* **Frontend:** Blade Templating, Bootstrap 5, Mazer Admin Template, DataTables, AJAX
* **Library Tambahan:** `barryvdh/laravel-dompdf` (Ekspor Laporan Sirkulasi PDF)

---

## 🗄️ Struktur Tabel Database
1. `users` — Menyimpan data akun pengguna dengan kolom `role` (`admin`, `petugas`, `anggota`).
2. `categories` — Menyimpan kategori buku (`hasMany` ke `books`).
3. `books` — Menyimpan data koleksi buku (`belongsTo` `categories`, memuat `total_stock`, `available_stock`, dan `cover_image`).
4. `borrowings` — Menyimpan transaksi sirkulasi peminjaman dan pengembalian (`belongsTo` `users` sebagai peminjam, `users` sebagai petugas/admin, dan `books`).

---

## ⚙️ Cara Instalasi & Menjalankan Proyek

1. **Clone repository ini:**
   ```bash
   git clone [https://github.com/annissazh/perpustakaan-app.git](https://github.com/annissazh/perpustakaan-app.git)
   cd perpustakaan-app