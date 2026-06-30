CREATE DATABASE IF NOT EXISTS inventaris_fazma
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventaris_fazma;

DROP TABLE IF EXISTS b_keluar;
DROP TABLE IF EXISTS barang;
DROP TABLE IF EXISTS admins;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'Administrator',
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE barang (
  idbarang VARCHAR(15) NOT NULL,
  nama VARCHAR(40) NOT NULL,
  kelompok VARCHAR(50) NOT NULL,
  ukuran VARCHAR(20) NOT NULL DEFAULT '',
  stok INT UNSIGNED NOT NULL DEFAULT 0,
  harga INT UNSIGNED NOT NULL DEFAULT 0,
  satuan VARCHAR(5) NOT NULL,
  perdus INT UNSIGNED NOT NULL DEFAULT 0,
  margin1 INT UNSIGNED NOT NULL DEFAULT 0,
  harga1 INT UNSIGNED AS (ROUND((harga * margin1) / 100)) STORED,
  jual INT UNSIGNED AS (harga + ROUND((harga * margin1) / 100)) STORED,
  supplyer VARCHAR(5) NOT NULL,
  batas_diskon INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_diskon INT UNSIGNED NOT NULL DEFAULT 0,
  gambar VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idbarang),
  INDEX idx_barang_nama (nama),
  INDEX idx_barang_kelompok (kelompok),
  INDEX idx_barang_supplyer (supplyer),
  INDEX idx_barang_stok (stok),
  INDEX idx_barang_harga (harga)
) ENGINE=InnoDB;

CREATE TABLE b_keluar (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  no_faktur CHAR(10) NOT NULL,
  tgl_faktur DATE NOT NULL,
  kode_brg VARCHAR(15) NOT NULL,
  jumlah INT UNSIGNED NOT NULL DEFAULT 0,
  harga_beli INT UNSIGNED NOT NULL DEFAULT 0,
  harga_jual INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_b_keluar_faktur (no_faktur),
  INDEX idx_b_keluar_tanggal (tgl_faktur),
  INDEX idx_b_keluar_barang (kode_brg),
  CONSTRAINT fk_b_keluar_barang FOREIGN KEY (kode_brg) REFERENCES barang(idbarang)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_b_keluar_admin FOREIGN KEY (created_by) REFERENCES admins(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO admins (username, email, password_hash, role, name) VALUES
('admin', 'admin@fazmastone.com', '$2y$12$qITfesC84vyAYISzTlMKbOOsigkUdzQ.RYTmz1rqUZ9OXQWAk3GOW', 'Administrator', 'Admin Fazma Stone');

INSERT INTO barang (
  idbarang,
  nama,
  kelompok,
  stok,
  harga,
  satuan,
  perdus,
  margin1,
  supplyer,
  batas_diskon,
  jumlah_diskon
) VALUES
('BRG-001', 'Marmer Green Jade', 'Batu Alam', 48, 185000, 'm2', 4, 25, 'SUP01', 12, 15000),
('BRG-002', 'Granit Black Galaxy', 'Batu Alam', 19, 245000, 'm2', 3, 22, 'SUP02', 8, 10000),
('BRG-003', 'Travertine Classic', 'Batu Alam', 7, 165000, 'm2', 5, 18, 'SUP01', 5, 7500),
('BAT01', 'Andesit Bakar RTM', 'Batu Alam', 30, 95000, 'm2', 4, 20, 'SUP03', 0, 0),
('BUK01', 'Buku Tulis', 'Buku', 100, 4000, 'pcs', 1, 25, 'SUP04', 0, 0),
('ATK01', 'Pensil 2B', 'ATK', 80, 1500, 'pcs', 1, 35, 'SUP04', 0, 0);

CREATE OR REPLACE VIEW v_barang_ringkasan AS
SELECT
  COUNT(*) AS total_barang,
  COALESCE(SUM(stok), 0) AS total_stok,
  COALESCE(SUM(stok * harga), 0) AS nilai_modal,
  COALESCE(SUM(CASE WHEN stok > 0 AND stok <= 10 THEN 1 ELSE 0 END), 0) AS stok_tipis
FROM barang;

CREATE OR REPLACE VIEW v_penjualan_ringkasan AS
SELECT
  COUNT(DISTINCT no_faktur) AS total_faktur,
  COALESCE(SUM(jumlah), 0) AS total_item,
  COALESCE(SUM(total), 0) AS total_penjualan,
  COALESCE(SUM(harga_beli * jumlah), 0) AS total_modal
FROM b_keluar;

CREATE OR REPLACE VIEW v_barang_detail AS
SELECT
  idbarang,
  nama,
  kelompok,
  stok,
  harga,
  satuan,
  perdus,
  margin1,
  harga1,
  jual,
  supplyer,
  batas_diskon,
  jumlah_diskon,
  created_at,
  updated_at
FROM barang;
