# Fazma Stone Inventory

Web inventaris Fazma Stone menggunakan PHP, HTML, CSS, dan MySQL/XAMPP. Fiturnya mengikuti field pada `Input Barang.pdf`: login admin, CRUD barang, filter, ringkasan stok, perhitungan margin, jual reguler, dan logout.

## Database XAMPP

1. Buka XAMPP Control Panel.
2. Start `Apache` dan `MySQL`.
3. Buka phpMyAdmin.
4. Import file:

```text
database/inventaris_fazma.sql
```

Query tersebut akan membuat database `inventaris_fazma`, tabel `admins`, tabel `barang`, view ringkasan, dan data awal.

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

## Akun Demo

```text
Username: admin
Password: admin123
```

## Struktur

```text
backend/
  auth.php       Login, sesi admin, logout
  barang.php     CRUD MySQL, filter, summary, hitung harga jual
  config.php     Konfigurasi path dan session
  database.php   Koneksi PDO MySQL
  helpers.php    Helper umum
  data/          Backup data JSON lama

database/
  inventaris_fazma.sql

frontend/
  components/    Komponen kecil seperti flash message
  layout/        Header dan footer HTML

public/
  index.php      Entry point
  login.php      Halaman login admin
  dashboard.php  Dashboard CRUD dan filter barang
  barang-save.php
  barang-delete.php
  logout.php
  assets/css/    Styling aplikasi
```
