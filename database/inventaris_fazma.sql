CREATE DATABASE IF NOT EXISTS inventaris_fazma
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventaris_fazma;

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
  kelompok CHAR(3) NOT NULL,
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
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (idbarang),
  INDEX idx_barang_nama (nama),
  INDEX idx_barang_kelompok (kelompok),
  INDEX idx_barang_supplyer (supplyer),
  INDEX idx_barang_stok (stok),
  INDEX idx_barang_harga (harga)
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
('BRG-001', 'Marmer Green Jade', 'MRM', 48, 185000, 'm2', 4, 25, 'SUP01', 12, 15000),
('BRG-002', 'Granit Black Galaxy', 'GRT', 19, 245000, 'm2', 3, 22, 'SUP02', 8, 10000),
('BRG-003', 'Travertine Classic', 'TRV', 7, 165000, 'm2', 5, 18, 'SUP01', 5, 7500);

CREATE OR REPLACE VIEW v_barang_ringkasan AS
SELECT
  COUNT(*) AS total_barang,
  COALESCE(SUM(stok), 0) AS total_stok,
  COALESCE(SUM(stok * harga), 0) AS nilai_modal,
  COALESCE(SUM(CASE WHEN stok > 0 AND stok <= 10 THEN 1 ELSE 0 END), 0) AS stok_tipis
FROM barang;

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
