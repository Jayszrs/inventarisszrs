<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/penjualan.php';

require_login();

$filters = [
    'search'   => $_GET['search']   ?? '',
    'tanggal'  => $_GET['tanggal']  ?? '',
    'kelompok' => $_GET['kelompok'] ?? '',
];

$sales = all_penjualan($filters);

$filename = 'laporan-penjualan-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM for Excel compatibility
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header row
fputcsv($out, [
    'No Faktur',
    'Tanggal',
    'Kode Barang',
    'Nama Barang',
    'Kelompok',
    'Jumlah',
    'Satuan',
    'Harga Beli',
    'Harga Jual',
    'Total',
]);

$grandTotal = 0;
$totalModal = 0;

foreach ($sales as $sale) {
    $total = (int) $sale['total'];
    $modal = (int) $sale['harga_beli'] * (int) $sale['jumlah'];
    $grandTotal += $total;
    $totalModal += $modal;

    fputcsv($out, [
        $sale['no_faktur'],
        date('d/m/Y', strtotime((string) $sale['tgl_faktur'])),
        $sale['kode_brg'],
        $sale['nama'],
        $sale['kelompok'],
        $sale['jumlah'],
        $sale['satuan'],
        $sale['harga_beli'],
        $sale['harga_jual'],
        $total,
    ]);
}

// Summary rows
fputcsv($out, []);
fputcsv($out, ['', '', '', '', '', '', '', '', 'Total Penjualan', $grandTotal]);
fputcsv($out, ['', '', '', '', '', '', '', '', 'Total Modal', $totalModal]);
fputcsv($out, ['', '', '', '', '', '', '', '', 'Keuntungan', $grandTotal - $totalModal]);

fclose($out);
exit;
