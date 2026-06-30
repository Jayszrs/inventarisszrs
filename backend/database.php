<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensure_database_schema($pdo);

    return $pdo;
}

function ensure_database_schema(PDO $pdo): void
{
    static $done = false;

    if ($done) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(120) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(40) NOT NULL DEFAULT 'Administrator',
            name VARCHAR(120) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS barang (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS b_keluar (
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
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    try {
        $pdo->exec('ALTER TABLE barang MODIFY kelompok VARCHAR(50) NOT NULL');
    } catch (PDOException) {
        // Existing databases may already use the desired column definition.
    }

    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($adminCount === 0) {
        $statement = $pdo->prepare('
            INSERT INTO admins (username, email, password_hash, role, name)
            VALUES (:username, :email, :password_hash, :role, :name)
        ');
        $statement->execute([
            'username' => 'admin',
            'email' => 'admin@fazmastone.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'Administrator',
            'name' => 'Admin Fazma Stone',
        ]);
    }

    $done = true;
}
