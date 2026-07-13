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
$delimiter = ';';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM for Excel compatibility
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

$writeRow = static fn (array $row): bool|int => fputcsv($out, $row, $delimiter);

// Tell Excel to use semicolon, which fits Indonesian regional settings better.
fprintf($out, "sep=%s\r\n", $delimiter);

$grandTotal = 0;
$totalModal = 0;
$totalItem = 0;
$fakturNumbers = [];

foreach ($sales as $sale) {
    $total = (int) $sale['total'];
    $modal = (int) $sale['harga_beli'] * (int) $sale['jumlah'];
    $grandTotal += $total;
    $totalModal += $modal;
    $totalItem += (int) $sale['jumlah'];
    $fakturNumbers[(string) $sale['no_faktur']] = true;
}

$writeRow(['LAPORAN PENJUALAN FAZMA STONE']);
$writeRow(['Dicetak', date('d/m/Y H:i:s')]);
$writeRow(['Filter Pencarian', $filters['search'] !== '' ? $filters['search'] : 'Semua']);
$writeRow(['Filter Tanggal', $filters['tanggal'] !== '' ? date('d/m/Y', strtotime((string) $filters['tanggal'])) : 'Semua']);
$writeRow(['Filter Kelompok', $filters['kelompok'] !== '' ? $filters['kelompok'] : 'Semua']);
$writeRow([]);

$writeRow(['RINGKASAN']);
$writeRow(['Total Faktur', count($fakturNumbers)]);
$writeRow(['Barang Terjual', $totalItem]);
$writeRow(['Total Penjualan (Rp)', $grandTotal]);
$writeRow(['Total Modal (Rp)', $totalModal]);
$writeRow(['Keuntungan (Rp)', $grandTotal - $totalModal]);
$writeRow([]);

$writeRow(['DETAIL PENJUALAN']);
$writeRow([
    'No',
    'No Faktur',
    'Tanggal',
    'Kode Barang',
    'Nama Barang',
    'Kelompok',
    'Jumlah',
    'Satuan',
    'Harga Beli (Rp)',
    'Harga Jual (Rp)',
    'Total (Rp)',
    'Modal (Rp)',
    'Keuntungan (Rp)',
]);

if (!$sales) {
    $writeRow(['Tidak ada data penjualan untuk filter ini.']);
}

foreach ($sales as $index => $sale) {
    $total = (int) $sale['total'];
    $modal = (int) $sale['harga_beli'] * (int) $sale['jumlah'];

    $writeRow([
        $index + 1,
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
        $modal,
        $total - $modal,
    ]);
}

fclose($out);
exit;
