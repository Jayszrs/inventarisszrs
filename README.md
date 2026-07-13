# Fazma Stone Inventory

Aplikasi inventaris Fazma Stone berbasis PHP, HTML, CSS, dan MySQL/XAMPP. Sistem ini dipakai untuk mengelola data barang, stok, penjualan barang keluar, laporan ringkasan, export CSV, dan manajemen akun admin.

## Fitur

- Login dan logout admin.
- Dashboard CRUD barang sesuai field inventaris: kode barang, nama, kelompok, ukuran, stok, harga, satuan, isi per dus, margin, harga jual, supplier, diskon, dan gambar.
- Nomor barang dan supplier dibuat otomatis dan ditampilkan sebagai field `readonly`.
- Filter stok dan pencarian data barang.
- Penjualan multi kategori untuk mencatat barang keluar.
- Nomor faktur dibuat otomatis dengan format `FKyymmddNN`, ditampilkan `readonly`, dan tetap di-generate dari backend agar tidak bisa diubah manual.
- Validasi stok saat penjualan, pengurangan stok otomatis, serta penyimpanan harga beli, harga jual, dan total per item.
- Ringkasan inventaris dan laporan penjualan: total barang, jumlah stok, nilai modal, stok tipis, pendapatan, profit, faktur, grafik harian/bulanan/tahunan, dan penjualan terbaru.
- Export laporan penjualan ke CSV dengan filter yang sama seperti halaman penjualan.
- Role management untuk tambah, edit, dan hapus akun admin.

## Database XAMPP

1. Buka XAMPP Control Panel.
2. Start `Apache` dan `MySQL`.
3. Buka phpMyAdmin.
4. Import file:

```text
database/inventaris_fazma.sql
```

Query tersebut akan membuat database `inventaris_fazma`, tabel `admins`, `barang`, `b_keluar`, view ringkasan barang, view ringkasan penjualan, dan data awal.

Konfigurasi koneksi ada di:

```text
backend/config.php
```

Default XAMPP yang dipakai:

```text
Host: 127.0.0.1
Port: 3306
Database: inventaris_fazma
User: root
Password: kosong
```

## Jalankan

### Lewat XAMPP Apache

Pastikan folder project berada di:

```text
C:\xampp\htdocs\inventarisszrs
```

Buka:

```text
http://localhost/inventarisszrs/
```

### Lewat PHP Built-in Server

Jalankan dari folder project:

```bash
php -S localhost:3000 -t public
```

Buka:

```text
http://localhost:3000
```

Jika command `php` tidak terbaca dari PATH Windows, pakai binary XAMPP:

```bash
C:\xampp\php\php.exe -S localhost:3000 -t public
```

## Akun Demo

```text
Username: admin
Password: admin123
```

## Struktur

```text
backend/
  admins.php       CRUD admin
  auth.php         Login, sesi admin, logout
  barang.php       CRUD barang, filter, summary, hitung harga jual
  config.php       Konfigurasi path dan session
  database.php     Koneksi PDO MySQL dan bootstrap tabel
  helpers.php      Helper umum
  penjualan.php    Generate faktur, validasi, simpan penjualan, laporan revenue
  data/            Backup data JSON lama

database/
  inventaris_fazma.sql

frontend/
  components/      Sidebar dan flash message
  layout/          Header dan footer HTML

public/
  index.php        Entry point
  login.php        Halaman login admin
  dashboard.php    Dashboard CRUD barang
  filter-stok.php  Filter dan daftar stok
  penjualan.php    Input penjualan multi kategori
  penjualan-save.php
  ringkasan.php    Dashboard laporan inventaris dan penjualan
  export-csv.php   Export laporan penjualan CSV
  role-management.php
  admin-save.php
  admin-delete.php
  barang-save.php
  barang-delete.php
  logout.php
  assets/          CSS, logo, dan upload gambar barang
```

## Catatan Nomor Faktur

Nomor faktur tidak diambil dari input user saat disimpan. Halaman hanya menampilkan preview nomor faktur berikutnya, sedangkan `backend/penjualan.php` selalu membuat ulang nomor faktur berdasarkan tanggal faktur ketika form diproses.
