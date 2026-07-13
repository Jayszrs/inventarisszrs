<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/penjualan.php';
require_once __DIR__ . '/../backend/receipt_pdf.php';

require_login();

$noFaktur = normalize_faktur((string) ($_GET['no_faktur'] ?? ''));

if ($noFaktur === '') {
    flash('No faktur struk tidak valid.', 'error');
    redirect_to('/penjualan.php');
}

$sales = penjualan_by_faktur($noFaktur);

if (!$sales) {
    flash('Data struk tidak ditemukan.', 'error');
    redirect_to('/penjualan.php');
}

$pdf = generate_struk_pdf($sales, current_user() ?? []);
$filename = 'struk-penjualan-' . $noFaktur . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdf;
exit;
