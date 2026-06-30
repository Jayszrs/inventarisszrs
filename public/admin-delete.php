<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/admins.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/role-management.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('Akun tidak valid.', 'error');
    redirect_to('/role-management.php');
}

if ((int) (current_user()['id'] ?? 0) === $id) {
    flash('Akun yang sedang login tidak bisa dihapus.', 'error');
    redirect_to('/role-management.php');
}

if (!delete_admin($id)) {
    flash('Akun tidak ditemukan.', 'error');
    redirect_to('/role-management.php');
}

flash('Akun berhasil dihapus.');
redirect_to('/role-management.php');
