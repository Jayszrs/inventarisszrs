<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/admins.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/role-management.php');
}

$mode = (string) ($_POST['mode'] ?? 'create');
$id = (int) ($_POST['id'] ?? 0);

[$errors, $admin] = validate_admin_payload($_POST, $mode === 'update' ? $id : null);

if ($errors) {
    flash(implode(' ', $errors), 'error');
    redirect_to('/role-management.php' . ($mode === 'update' ? '?edit=' . $id : ''));
}

if ($mode === 'update') {
    if (!update_admin($id, $admin)) {
        flash('Akun tidak ditemukan.', 'error');
        redirect_to('/role-management.php');
    }

    if ((int) (current_user()['id'] ?? 0) === $id) {
        $_SESSION['user']['username'] = $admin['username'];
        $_SESSION['user']['email'] = $admin['email'];
        $_SESSION['user']['role'] = $admin['role'];
        $_SESSION['user']['name'] = $admin['name'];
    }

    flash('Akun berhasil diperbarui.');
    redirect_to('/role-management.php');
}

create_admin($admin);
flash('Akun baru berhasil dibuat.');
redirect_to('/role-management.php');
