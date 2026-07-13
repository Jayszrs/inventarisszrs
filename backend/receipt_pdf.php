<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function generate_struk_pdf(array $sales, array $user = []): string
{
    if (!$sales) {
        throw new RuntimeException('Data struk tidak ditemukan.');
    }

    $pageWidth = 595.28;
    $pageHeight = 841.89;
    $objects = [];

    $reserveObject = static function () use (&$objects): int {
        $id = count($objects) + 1;
        $objects[$id] = '';
        return $id;
    };

    $addObject = static function (string $body) use (&$objects): int {
        $id = count($objects) + 1;
        $objects[$id] = $body;
        return $id;
    };

    $setObject = static function (int $id, string $body) use (&$objects): void {
        $objects[$id] = $body;
    };

    $pagesId = $reserveObject();
    $fontRegularId = $addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
    $fontBoldId = $addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
    $logo = receipt_pdf_logo(__DIR__ . '/../public/assets/img/logo-fazmastone.png');
    $logoId = null;

    if ($logo) {
        $logoId = $addObject(
            "<< /Type /XObject /Subtype /Image /Width {$logo['width']} /Height {$logo['height']} " .
            "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /{$logo['filter']} /Length " . strlen($logo['data']) . " >>\n" .
            "stream\n{$logo['data']}\nendstream"
        );
    }

    $noFaktur = (string) $sales[0]['no_faktur'];
    $tanggal = date('d/m/Y', strtotime((string) $sales[0]['tgl_faktur']));
    $adminName = (string) ($sales[0]['admin_name'] ?: ($user['name'] ?? 'Admin'));
    $createdAt = date('d/m/Y H:i', strtotime((string) ($sales[0]['created_at'] ?? 'now')));
    $totalPenjualan = array_sum(array_map(static fn (array $sale): int => (int) $sale['total'], $sales));
    $totalModal = array_sum(array_map(static fn (array $sale): int => (int) $sale['harga_beli'] * (int) $sale['jumlah'], $sales));
    $totalItem = array_sum(array_map(static fn (array $sale): int => (int) $sale['jumlah'], $sales));
    $chunks = array_chunk($sales, 12);
    $pageIds = [];

    foreach ($chunks as $pageIndex => $chunk) {
        $isFirstPage = $pageIndex === 0;
        $isLastPage = $pageIndex === count($chunks) - 1;
        $content = receipt_pdf_page_content(
            $chunk,
            [
                'no_faktur' => $noFaktur,
                'tanggal' => $tanggal,
                'admin' => $adminName,
                'created_at' => $createdAt,
                'total_penjualan' => $totalPenjualan,
                'total_modal' => $totalModal,
                'total_item' => $totalItem,
                'profit' => $totalPenjualan - $totalModal,
                'page' => $pageIndex + 1,
                'pages' => count($chunks),
            ],
            $pageWidth,
            $pageHeight,
            $logoId !== null,
            $isFirstPage,
            $isLastPage
        );

        $contentId = $addObject("<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream");
        $xObjects = $logoId !== null ? " /XObject << /Logo {$logoId} 0 R >>" : '';
        $pageId = $addObject(
            "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] " .
            "/Resources << /Font << /F1 {$fontRegularId} 0 R /F2 {$fontBoldId} 0 R >>{$xObjects} >> " .
            "/Contents {$contentId} 0 R >>"
        );
        $pageIds[] = $pageId;
    }

    $kids = implode(' ', array_map(static fn (int $id): string => "{$id} 0 R", $pageIds));
    $setObject($pagesId, "<< /Type /Pages /Kids [{$kids}] /Count " . count($pageIds) . ' >>');
    $catalogId = $addObject("<< /Type /Catalog /Pages {$pagesId} 0 R >>");

    return receipt_pdf_build($objects, $catalogId);
}

function receipt_pdf_page_content(
    array $sales,
    array $meta,
    float $pageWidth,
    float $pageHeight,
    bool $hasLogo,
    bool $isFirstPage,
    bool $isLastPage
): string {
    $content = '';

    $text = static function (
        float $x,
        float $yTop,
        string $value,
        int $size = 10,
        string $font = 'F1',
        array $color = [0.13, 0.16, 0.14]
    ) use (&$content, $pageHeight): void {
        $y = $pageHeight - $yTop;
        $content .= sprintf(
            "BT /%s %d Tf %.3F %.3F %.3F rg %.2F %.2F Td %s Tj ET\n",
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            receipt_pdf_string($value)
        );
    };

    $line = static function (float $x1, float $y1Top, float $x2, float $y2Top, array $color = [0.74, 0.86, 0.78]) use (&$content, $pageHeight): void {
        $content .= sprintf(
            "q %.3F %.3F %.3F RG 1 w %.2F %.2F m %.2F %.2F l S Q\n",
            $color[0],
            $color[1],
            $color[2],
            $x1,
            $pageHeight - $y1Top,
            $x2,
            $pageHeight - $y2Top
        );
    };

    $rect = static function (float $x, float $yTop, float $w, float $h, array $color, bool $stroke = false) use (&$content, $pageHeight): void {
        $operator = $stroke ? 'S' : 'f';
        $paint = $stroke ? 'RG' : 'rg';
        $content .= sprintf(
            "q %.3F %.3F %.3F %s %.2F %.2F %.2F %.2F re %s Q\n",
            $color[0],
            $color[1],
            $color[2],
            $paint,
            $x,
            $pageHeight - $yTop - $h,
            $w,
            $h,
            $operator
        );
    };

    $rect(0, 0, $pageWidth, 94, [0.91, 0.98, 0.94]);
    $rect(0, 0, 9, $pageHeight, [0.06, 0.45, 0.22]);
    $line(36, 94, $pageWidth - 36, 94, [0.08, 0.55, 0.27]);

    if ($hasLogo) {
        $content .= "q 92 0 0 54 42 758 cm /Logo Do Q\n";
    } else {
        $rect(42, 28, 58, 42, [0.06, 0.45, 0.22]);
        $text(50, 53, 'FAZMA', 16, 'F2', [1, 1, 1]);
    }

    $text(170, 34, 'FAZMA STONE', 20, 'F2', [0.05, 0.36, 0.18]);
    $text(170, 54, 'Produsen dan Supplier Batu Alam', 10, 'F2', [0.15, 0.45, 0.25]);
    $text(170, 70, 'Jl. Raya Fazma Stone | Telp. 0812-0000-0000', 9, 'F1', [0.34, 0.41, 0.37]);
    $text(170, 84, 'Email: admin@fazmastone.com', 9, 'F1', [0.34, 0.41, 0.37]);

    $text(410, 42, 'STRUK PENJUALAN', 17, 'F2', [0.05, 0.36, 0.18]);
    $text(410, 61, (string) $meta['no_faktur'], 12, 'F2', [0.13, 0.16, 0.14]);
    $text(410, 78, 'Halaman ' . $meta['page'] . '/' . $meta['pages'], 9, 'F1', [0.34, 0.41, 0.37]);

    $top = $isFirstPage ? 118 : 112;

    if ($isFirstPage) {
        $rect(36, 112, 250, 72, [0.96, 0.99, 0.97]);
        $rect(310, 112, 249, 72, [0.96, 0.99, 0.97]);
        $rect(36, 112, 250, 72, [0.74, 0.86, 0.78], true);
        $rect(310, 112, 249, 72, [0.74, 0.86, 0.78], true);
        $text(50, 134, 'Tanggal Faktur', 9, 'F2', [0.34, 0.41, 0.37]);
        $text(50, 153, (string) $meta['tanggal'], 13, 'F2', [0.05, 0.36, 0.18]);
        $text(50, 174, 'Dicetak: ' . (string) $meta['created_at'], 9, 'F1', [0.34, 0.41, 0.37]);
        $text(324, 134, 'Admin', 9, 'F2', [0.34, 0.41, 0.37]);
        $text(324, 153, (string) $meta['admin'], 13, 'F2', [0.05, 0.36, 0.18]);
        $text(324, 174, 'Barang terjual: ' . number_format((int) $meta['total_item'], 0, ',', '.') . ' item', 9, 'F1', [0.34, 0.41, 0.37]);
        $top = 210;
    }

    $rect(36, $top, 523, 28, [0.06, 0.45, 0.22]);
    $text(48, $top + 18, 'No', 9, 'F2', [1, 1, 1]);
    $text(78, $top + 18, 'Kode', 9, 'F2', [1, 1, 1]);
    $text(140, $top + 18, 'Nama Barang', 9, 'F2', [1, 1, 1]);
    $text(315, $top + 18, 'Qty', 9, 'F2', [1, 1, 1]);
    $text(372, $top + 18, 'Harga', 9, 'F2', [1, 1, 1]);
    $text(480, $top + 18, 'Total', 9, 'F2', [1, 1, 1]);

    $rowTop = $top + 28;
    foreach ($sales as $index => $sale) {
        $fill = $index % 2 === 0 ? [0.99, 1, 0.99] : [0.95, 0.99, 0.96];
        $rect(36, $rowTop, 523, 34, $fill);
        $text(48, $rowTop + 21, (string) (((int) $meta['page'] - 1) * 12 + $index + 1), 9);
        $text(78, $rowTop + 21, (string) $sale['kode_brg'], 9, 'F2');
        $text(140, $rowTop + 15, receipt_pdf_fit((string) $sale['nama'], 31), 9, 'F2');
        $text(140, $rowTop + 29, receipt_pdf_fit((string) $sale['kelompok'], 31), 8, 'F1', [0.34, 0.41, 0.37]);
        $text(315, $rowTop + 21, number_format((int) $sale['jumlah'], 0, ',', '.') . ' ' . (string) $sale['satuan'], 9);
        $text(372, $rowTop + 21, rupiah($sale['harga_jual']), 9);
        $text(480, $rowTop + 21, rupiah($sale['total']), 9, 'F2');
        $line(36, $rowTop + 34, 559, $rowTop + 34, [0.86, 0.93, 0.88]);
        $rowTop += 34;
    }

    if ($isLastPage) {
        $summaryTop = max($rowTop + 22, 642);
        $rect(325, $summaryTop, 234, 104, [0.91, 0.98, 0.94]);
        $rect(325, $summaryTop, 234, 104, [0.08, 0.55, 0.27], true);
        $text(342, $summaryTop + 23, 'Total Penjualan', 10, 'F2', [0.34, 0.41, 0.37]);
        $text(458, $summaryTop + 23, rupiah($meta['total_penjualan']), 11, 'F2', [0.05, 0.36, 0.18]);
        $text(342, $summaryTop + 47, 'Total Modal', 10, 'F1', [0.34, 0.41, 0.37]);
        $text(458, $summaryTop + 47, rupiah($meta['total_modal']), 10, 'F1');
        $text(342, $summaryTop + 71, 'Keuntungan', 10, 'F1', [0.34, 0.41, 0.37]);
        $text(458, $summaryTop + 71, rupiah($meta['profit']), 10, 'F1');
        $line(342, $summaryTop + 84, 542, $summaryTop + 84, [0.08, 0.55, 0.27]);
        $text(342, $summaryTop + 98, 'Terima kasih atas pembelian Anda.', 9, 'F2', [0.05, 0.36, 0.18]);
    }

    $line(36, 792, 559, 792, [0.74, 0.86, 0.78]);
    $text(36, 812, 'Struk ini dibuat otomatis oleh Fazma Stone Inventory.', 8, 'F1', [0.34, 0.41, 0.37]);
    $text(430, 812, date('d/m/Y H:i:s'), 8, 'F1', [0.34, 0.41, 0.37]);

    return $content;
}

function receipt_pdf_build(array $objects, int $catalogId): string
{
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];

    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($id = 1; $id <= count($objects); $id++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogId} 0 R >>\n";
    $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

    return $pdf;
}

function receipt_pdf_string(string $value): string
{
    $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

    if ($encoded === false) {
        $encoded = preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
    }

    return '(' . strtr($encoded, [
        '\\' => '\\\\',
        '(' => '\\(',
        ')' => '\\)',
    ]) . ')';
}

function receipt_pdf_fit(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    if (strlen($value) <= $maxLength) {
        return $value;
    }

    return substr($value, 0, max(0, $maxLength - 3)) . '...';
}

function receipt_pdf_logo(string $path): ?array
{
    if (!file_exists($path)) {
        return null;
    }

    $info = getimagesize($path);
    if (!$info) {
        return null;
    }

    [$width, $height, $type] = $info;

    if ($type === IMAGETYPE_JPEG) {
        return [
            'data' => (string) file_get_contents($path),
            'width' => $width,
            'height' => $height,
            'filter' => 'DCTDecode',
        ];
    }

    if ($type !== IMAGETYPE_PNG) {
        return null;
    }

    if (!extension_loaded('gd')) {
        return receipt_pdf_png_logo($path);
    }

    $source = imagecreatefrompng($path);
    if (!$source) {
        return null;
    }

    $canvas = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

    ob_start();
    imagejpeg($canvas, null, 92);
    $data = (string) ob_get_clean();

    imagedestroy($source);
    imagedestroy($canvas);

    return [
        'data' => $data,
        'width' => $width,
        'height' => $height,
        'filter' => 'DCTDecode',
    ];
}

function receipt_pdf_png_logo(string $path): ?array
{
    $png = file_get_contents($path);
    if ($png === false || substr($png, 0, 8) !== "\x89PNG\r\n\x1A\n") {
        return null;
    }

    $offset = 8;
    $width = 0;
    $height = 0;
    $bitDepth = 0;
    $colorType = 0;
    $compression = 0;
    $filter = 0;
    $interlace = 0;
    $idat = '';
    $length = strlen($png);

    while ($offset + 8 <= $length) {
        $chunkLength = unpack('N', substr($png, $offset, 4))[1];
        $type = substr($png, $offset + 4, 4);
        $data = substr($png, $offset + 8, $chunkLength);
        $offset += 12 + $chunkLength;

        if ($type === 'IHDR') {
            $header = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', $data);
            $width = (int) $header['width'];
            $height = (int) $header['height'];
            $bitDepth = (int) $header['bitDepth'];
            $colorType = (int) $header['colorType'];
            $compression = (int) $header['compression'];
            $filter = (int) $header['filter'];
            $interlace = (int) $header['interlace'];
            continue;
        }

        if ($type === 'IDAT') {
            $idat .= $data;
            continue;
        }

        if ($type === 'IEND') {
            break;
        }
    }

    if ($width <= 0 || $height <= 0 || $bitDepth !== 8 || $compression !== 0 || $filter !== 0 || $interlace !== 0) {
        return null;
    }

    if (!in_array($colorType, [2, 6], true)) {
        return null;
    }

    $raw = zlib_decode($idat);
    if ($raw === false) {
        return null;
    }

    $bytesPerPixel = $colorType === 6 ? 4 : 3;
    $stride = $width * $bytesPerPixel;
    $position = 0;
    $previous = str_repeat("\0", $stride);
    $rgb = '';

    for ($row = 0; $row < $height; $row++) {
        $filterType = ord($raw[$position]);
        $position++;
        $scanline = substr($raw, $position, $stride);
        $position += $stride;
        $line = '';

        for ($i = 0; $i < $stride; $i++) {
            $value = ord($scanline[$i]);
            $left = $i >= $bytesPerPixel ? ord($line[$i - $bytesPerPixel]) : 0;
            $up = ord($previous[$i]);
            $upLeft = $i >= $bytesPerPixel ? ord($previous[$i - $bytesPerPixel]) : 0;

            $predictor = match ($filterType) {
                1 => $left,
                2 => $up,
                3 => intdiv($left + $up, 2),
                4 => receipt_pdf_paeth($left, $up, $upLeft),
                default => 0,
            };

            $line .= chr(($value + $predictor) & 0xFF);
        }

        if ($colorType === 2) {
            $rgb .= $line;
        } else {
            for ($i = 0; $i < $stride; $i += 4) {
                $alpha = ord($line[$i + 3]);

                if ($alpha === 255) {
                    $rgb .= $line[$i] . $line[$i + 1] . $line[$i + 2];
                    continue;
                }

                $red = receipt_pdf_composite_channel(ord($line[$i]), $alpha);
                $green = receipt_pdf_composite_channel(ord($line[$i + 1]), $alpha);
                $blue = receipt_pdf_composite_channel(ord($line[$i + 2]), $alpha);
                $rgb .= chr($red) . chr($green) . chr($blue);
            }
        }

        $previous = $line;
    }

    return [
        'data' => gzcompress($rgb, 9),
        'width' => $width,
        'height' => $height,
        'filter' => 'FlateDecode',
    ];
}

function receipt_pdf_composite_channel(int $channel, int $alpha): int
{
    return (int) round(($channel * $alpha + 255 * (255 - $alpha)) / 255);
}

function receipt_pdf_paeth(int $left, int $up, int $upLeft): int
{
    $estimate = $left + $up - $upLeft;
    $leftDistance = abs($estimate - $left);
    $upDistance = abs($estimate - $up);
    $upLeftDistance = abs($estimate - $upLeft);

    if ($leftDistance <= $upDistance && $leftDistance <= $upLeftDistance) {
        return $left;
    }

    if ($upDistance <= $upLeftDistance) {
        return $up;
    }

    return $upLeft;
}
