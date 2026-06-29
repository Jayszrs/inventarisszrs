<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/dashboard.php');
}

$idbarang = strtoupper(trim((string) ($_POST['idbarang'] ?? '')));

if (!delete_barang($idbarang)) {
    flash('Barang tidak ditemukan.', 'error');
    redirect_to('/dashboard.php');
}

flash('Barang berhasil dihapus.');
redirect_to('/dashboard.php');
