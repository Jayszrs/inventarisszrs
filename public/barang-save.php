<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/dashboard.php');
}

$rows = all_barang();
$mode = (string) ($_POST['mode'] ?? 'create');
$idbarang = strtoupper(trim((string) ($_POST['idbarang'] ?? '')));

if ($mode === 'update') {
    [$errors, $item] = validate_barang($_POST, $rows, $idbarang);
} else {
    [$errors, $item] = validate_barang($_POST, $rows);
}

if ($errors) {
    flash(implode(' ', $errors), 'error');
    redirect_to('/dashboard.php' . ($mode === 'update' ? '?edit=' . urlencode($idbarang) : ''));
}

if ($mode === 'update') {
    if (!update_barang($idbarang, $item)) {
        flash('Barang tidak ditemukan.', 'error');
        redirect_to('/dashboard.php');
    }

    flash('Data barang berhasil diperbarui.');
    redirect_to('/dashboard.php');
}

create_barang($item);
flash('Barang baru berhasil ditambahkan.');
redirect_to('/dashboard.php');
