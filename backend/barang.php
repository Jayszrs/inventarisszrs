<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';

function normalize_barang(array $item): array
{
    $harga = number_value($item['harga'] ?? 0);
    $margin = number_value($item['margin1'] ?? 0);
    $hargaMargin = (int) round(($harga * $margin) / 100);
    $jual = $harga + $hargaMargin;
    
    // Diskon dari 5% sampai 95%
    $batas_diskon = number_value($item['batas_diskon'] ?? 0);
    if ($batas_diskon > 95) $batas_diskon = 95;
    
    $jumlah_diskon = (int) round(($jual * $batas_diskon) / 100);

    return [
        'idbarang' => strtoupper(substr(trim((string) ($item['idbarang'] ?? '')), 0, 15)),
        'nama' => substr(trim((string) ($item['nama'] ?? '')), 0, 40),
        'kelompok' => strtoupper(substr(trim((string) ($item['kelompok'] ?? '')), 0, 10)),
        'ukuran' => substr(trim((string) ($item['ukuran'] ?? '')), 0, 20),
        'stok' => number_value($item['stok'] ?? 0),
        'harga' => $harga,
        'satuan' => substr(trim((string) ($item['satuan'] ?? '')), 0, 5),
        'perdus' => number_value($item['perdus'] ?? 0),
        'margin1' => $margin,
        'harga1' => $hargaMargin,
        'jual' => $jual,
        'supplyer' => strtoupper(substr(trim((string) ($item['supplyer'] ?? '')), 0, 5)),
        'batas_diskon' => $batas_diskon,
        'jumlah_diskon' => $jumlah_diskon,
        'gambar' => trim((string) ($item['gambar'] ?? '')),
    ];
}

function all_barang(): array
{
    $statement = db()->query('
        SELECT idbarang, nama, kelompok, ukuran, stok, harga, satuan, perdus, margin1, harga1, jual,
               supplyer, batas_diskon, jumlah_diskon, gambar
        FROM barang
        ORDER BY nama ASC
    ');

    return array_map('normalize_barang', $statement->fetchAll());
}

function validate_barang(array $payload, array $rows, ?string $currentId = null): array
{
    $item = normalize_barang($payload);
    $errors = [];

    if ($item['idbarang'] === '') {
        $errors[] = 'No Barang wajib diisi.';
    }

    if ($item['nama'] === '') {
        $errors[] = 'Nama Barang wajib diisi.';
    }

    if ($item['kelompok'] === '') {
        $errors[] = 'Kelompok wajib diisi.';
    }

    if ($item['satuan'] === '') {
        $errors[] = 'Satuan wajib diisi.';
    }

    if ($item['supplyer'] === '') {
        $errors[] = 'Suplyer wajib diisi.';
    }

    foreach ($rows as $row) {
        $sameId = strtoupper((string) ($row['idbarang'] ?? '')) === $item['idbarang'];
        $isCurrent = $currentId !== null && strtoupper($currentId) === $item['idbarang'];

        if ($sameId && !$isCurrent) {
            $errors[] = 'No Barang sudah dipakai.';
            break;
        }
    }

    return [$errors, $item];
}

function create_barang(array $item): void
{
    $statement = db()->prepare('
        INSERT INTO barang (
            idbarang, nama, kelompok, ukuran, stok, harga, satuan, perdus, margin1,
            supplyer, batas_diskon, jumlah_diskon, gambar
        ) VALUES (
            :idbarang, :nama, :kelompok, :ukuran, :stok, :harga, :satuan, :perdus, :margin1,
            :supplyer, :batas_diskon, :jumlah_diskon, :gambar
        )
    ');

    $statement->execute(bind_barang($item));
}

function update_barang(string $idbarang, array $item): bool
{
    if (!barang_exists($idbarang)) {
        return false;
    }

    $statement = db()->prepare('
        UPDATE barang
        SET nama = :nama,
            kelompok = :kelompok,
            ukuran = :ukuran,
            stok = :stok,
            harga = :harga,
            satuan = :satuan,
            perdus = :perdus,
            margin1 = :margin1,
            supplyer = :supplyer,
            batas_diskon = :batas_diskon,
            jumlah_diskon = :jumlah_diskon,
            gambar = :gambar
        WHERE idbarang = :idbarang
    ');

    $statement->execute(bind_barang(['idbarang' => $idbarang] + $item));

    return true;
}

function delete_barang(string $idbarang): bool
{
    $statement = db()->prepare('DELETE FROM barang WHERE idbarang = :idbarang');
    $statement->execute(['idbarang' => $idbarang]);

    return $statement->rowCount() > 0;
}

function barang_exists(string $idbarang): bool
{
    $statement = db()->prepare('SELECT COUNT(*) FROM barang WHERE idbarang = :idbarang');
    $statement->execute(['idbarang' => $idbarang]);

    return (int) $statement->fetchColumn() > 0;
}

function bind_barang(array $item): array
{
    return [
        'idbarang' => $item['idbarang'],
        'nama' => $item['nama'],
        'kelompok' => $item['kelompok'],
        'ukuran' => $item['ukuran'],
        'stok' => $item['stok'],
        'harga' => $item['harga'],
        'satuan' => $item['satuan'],
        'perdus' => $item['perdus'],
        'margin1' => $item['margin1'],
        'supplyer' => $item['supplyer'],
        'batas_diskon' => $item['batas_diskon'],
        'jumlah_diskon' => $item['jumlah_diskon'],
        'gambar' => $item['gambar'],
    ];
}

function generate_new_idbarang(): string
{
    $statement = db()->query('SELECT idbarang FROM barang ORDER BY idbarang DESC LIMIT 1');
    $lastId = $statement->fetchColumn();
    if (!$lastId) {
        return 'BRG-001';
    }
    // Asumsi format 'BRG-001'
    if (preg_match('/^BRG-(\d+)$/i', $lastId, $matches)) {
        $nextNumber = (int) $matches[1] + 1;
        return 'BRG-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
    return 'BRG-' . strtoupper(substr(uniqid(), -4));
}

function generate_new_supplyer(): string
{
    $statement = db()->query('SELECT supplyer FROM barang ORDER BY supplyer DESC LIMIT 1');
    $lastId = $statement->fetchColumn();
    if (!$lastId) {
        return 'SUP01';
    }
    // Asumsi format 'SUP01'
    if (preg_match('/^SUP(\d+)$/i', $lastId, $matches)) {
        $nextNumber = (int) $matches[1] + 1;
        return 'SUP' . str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
    }
    return 'SUP' . strtoupper(substr(uniqid(), -2));
}

function filter_barang(array $rows, array $filters): array
{
    $search = strtolower(trim((string) ($filters['search'] ?? '')));
    $kelompok = strtoupper(trim((string) ($filters['kelompok'] ?? '')));
    $supplyer = strtoupper(trim((string) ($filters['supplyer'] ?? '')));
    $stok = trim((string) ($filters['stok'] ?? ''));
    $sort = trim((string) ($filters['sort'] ?? 'nama-asc'));

    $results = array_values(array_filter($rows, function (array $item) use ($search, $kelompok, $supplyer, $stok): bool {
        if ($search !== '') {
            $haystack = strtolower(implode(' ', [$item['idbarang'], $item['nama'], $item['kelompok'], $item['supplyer']]));
            if (!str_contains($haystack, $search)) {
                return false;
            }
        }

        if ($kelompok !== '' && $item['kelompok'] !== $kelompok) {
            return false;
        }

        if ($supplyer !== '' && $item['supplyer'] !== $supplyer) {
            return false;
        }

        if ($stok === 'available' && $item['stok'] <= 0) {
            return false;
        }

        if ($stok === 'low' && !($item['stok'] > 0 && $item['stok'] <= 10)) {
            return false;
        }

        if ($stok === 'empty' && $item['stok'] !== 0) {
            return false;
        }

        return true;
    }));

    usort($results, function (array $a, array $b) use ($sort): int {
        return match ($sort) {
            'stok-asc' => $a['stok'] <=> $b['stok'],
            'stok-desc' => $b['stok'] <=> $a['stok'],
            'harga-asc' => $a['harga'] <=> $b['harga'],
            'harga-desc' => $b['harga'] <=> $a['harga'],
            default => strcasecmp($a['nama'], $b['nama']),
        };
    });

    return $results;
}

function barang_summary(array $rows): array
{
    return [
        'totalBarang' => count($rows),
        'totalStok' => array_sum(array_column($rows, 'stok')),
        'nilaiModal' => array_reduce($rows, fn (int $sum, array $item): int => $sum + ($item['stok'] * $item['harga']), 0),
        'stokTipis' => count(array_filter($rows, fn (array $item): bool => $item['stok'] > 0 && $item['stok'] <= 10)),
    ];
}

function unique_options(array $rows, string $key): array
{
    $values = array_values(array_unique(array_map(fn (array $item): string => (string) $item[$key], $rows)));
    sort($values);

    return $values;
}
