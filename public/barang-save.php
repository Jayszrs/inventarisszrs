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

$existingItem = null;
if ($mode === 'update') {
    foreach ($rows as $row) {
        if ($row['idbarang'] === $idbarang) {
            $existingItem = $row;
            break;
        }
    }
}

// Handle file upload
$gambar = $existingItem ? $existingItem['gambar'] : '';
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['gambar']['tmp_name'];
    $fileName = $_FILES['gambar']['name'];
    $fileSize = $_FILES['gambar']['size'];
    $fileType = $_FILES['gambar']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedfileExtensions = ['jpg', 'gif', 'png', 'webp', 'jpeg'];
    if (in_array($fileExtension, $allowedfileExtensions)) {
        if ($fileSize < 2000000) { // Max 2MB
            $uploadFileDir = __DIR__ . '/assets/uploads/barang/';
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $gambar = 'assets/uploads/barang/' . $newFileName;
                // Option: Delete old image if exists
                if ($existingItem && $existingItem['gambar'] && file_exists(__DIR__ . '/' . $existingItem['gambar'])) {
                    @unlink(__DIR__ . '/' . $existingItem['gambar']);
                }
            } else {
                flash('Terjadi kesalahan saat mengunggah gambar.', 'error');
                redirect_to('/dashboard.php' . ($mode === 'update' ? '?edit=' . urlencode($idbarang) : ''));
            }
        } else {
            flash('Ukuran gambar maksimal 2MB.', 'error');
            redirect_to('/dashboard.php' . ($mode === 'update' ? '?edit=' . urlencode($idbarang) : ''));
        }
    } else {
        flash('Format gambar hanya boleh JPG, JPEG, PNG, WEBP, atau GIF.', 'error');
        redirect_to('/dashboard.php' . ($mode === 'update' ? '?edit=' . urlencode($idbarang) : ''));
    }
}

$_POST['gambar'] = $gambar;

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
