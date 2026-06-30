<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/penjualan.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/penjualan.php');
}

[$errors, $sale] = validate_penjualan($_POST);

if ($errors) {
    flash(implode(' ', $errors), 'error');
    redirect_to('/penjualan.php');
}

try {
    create_penjualan($sale, (int) (current_user()['id'] ?? 0));
    flash('Faktur penjualan ' . $sale['no_faktur'] . ' berhasil disimpan.');
} catch (Throwable $exception) {
    flash($exception->getMessage(), 'error');
}

redirect_to('/penjualan.php');
