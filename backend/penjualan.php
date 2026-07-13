<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/barang.php';

function generate_no_faktur(?string $date = null): string
{
    $date = $date ?: date('Y-m-d');
    $prefix = 'FK' . date('ymd', strtotime($date));

    $statement = db()->prepare('
        SELECT no_faktur
        FROM b_keluar
        WHERE no_faktur LIKE :prefix
        ORDER BY no_faktur DESC
        LIMIT 1
    ');
    $statement->execute(['prefix' => $prefix . '%']);
    $last = (string) ($statement->fetchColumn() ?: '');
    $next = 1;

    if (preg_match('/^' . preg_quote($prefix, '/') . '(\d{2})$/', $last, $matches) === 1) {
        $next = ((int) $matches[1]) + 1;
    }

    return $prefix . str_pad((string) ($next % 100), 2, '0', STR_PAD_LEFT);
}

function normalize_faktur(string $value): string
{
    return strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $value), 0, 10));
}

function all_penjualan(array $filters = []): array
{
    $where = [];
    $params = [];

    $search = trim((string) ($filters['search'] ?? ''));
    $tanggal = trim((string) ($filters['tanggal'] ?? ''));
    $kelompok = trim((string) ($filters['kelompok'] ?? ''));

    if ($search !== '') {
        $where[] = '(k.no_faktur LIKE :search OR k.kode_brg LIKE :search OR b.nama LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    if ($tanggal !== '') {
        $where[] = 'k.tgl_faktur = :tanggal';
        $params['tanggal'] = $tanggal;
    }

    if ($kelompok !== '') {
        $where[] = 'b.kelompok = :kelompok';
        $params['kelompok'] = $kelompok;
    }

    $sql = '
        SELECT k.id, k.no_faktur, k.tgl_faktur, k.kode_brg, k.jumlah, k.harga_beli,
               k.harga_jual, k.total, b.nama, b.satuan, b.kelompok
        FROM b_keluar k
        INNER JOIN barang b ON b.idbarang = k.kode_brg
    ';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY k.tgl_faktur DESC, k.no_faktur DESC, k.id DESC';

    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function penjualan_summary(array $rows): array
{
    $faktur = array_unique(array_map(fn (array $row): string => (string) $row['no_faktur'], $rows));

    return [
        'totalFaktur' => count($faktur),
        'totalItem' => array_sum(array_column($rows, 'jumlah')),
        'totalPenjualan' => array_sum(array_column($rows, 'total')),
        'totalModal' => array_reduce($rows, fn (int $sum, array $row): int => $sum + ((int) $row['harga_beli'] * (int) $row['jumlah']), 0),
    ];
}

function penjualan_by_faktur(string $noFaktur): array
{
    $statement = db()->prepare('
        SELECT k.id, k.no_faktur, k.tgl_faktur, k.kode_brg, k.jumlah, k.harga_beli,
               k.harga_jual, k.total, k.created_at, b.nama, b.satuan, b.kelompok,
               a.name AS admin_name
        FROM b_keluar k
        INNER JOIN barang b ON b.idbarang = k.kode_brg
        LEFT JOIN admins a ON a.id = k.created_by
        WHERE k.no_faktur = :no_faktur
        ORDER BY k.id ASC
    ');
    $statement->execute(['no_faktur' => normalize_faktur($noFaktur)]);

    return $statement->fetchAll();
}

function validate_penjualan(array $payload): array
{
    $tglFaktur = trim((string) ($payload['tgl_faktur'] ?? date('Y-m-d')));
    $noFaktur = '';
    $kodeBarang = $payload['kode_brg'] ?? [];
    $jumlah = $payload['jumlah'] ?? [];
    $items = [];
    $errors = [];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglFaktur)) {
        $errors[] = 'Tanggal faktur tidak valid.';
    }

    $noFaktur = generate_no_faktur($errors ? date('Y-m-d') : $tglFaktur);

    if (strlen($noFaktur) !== 10) {
        $errors[] = 'No faktur harus 10 karakter.';
    }

    if (!is_array($kodeBarang) || !is_array($jumlah)) {
        $errors[] = 'Item penjualan tidak valid.';
        return [$errors, ['no_faktur' => $noFaktur, 'tgl_faktur' => $tglFaktur, 'items' => []]];
    }

    foreach ($kodeBarang as $index => $kode) {
        $kode = strtoupper(trim((string) $kode));
        $qty = number_value($jumlah[$index] ?? 0);

        if ($kode === '' && $qty === 0) {
            continue;
        }

        if ($kode === '') {
            $errors[] = 'Kode barang wajib dipilih.';
            continue;
        }

        if ($qty <= 0) {
            $errors[] = 'Jumlah barang harus lebih dari 0.';
            continue;
        }

        $items[$kode] = ($items[$kode] ?? 0) + $qty;
    }

    if (!$items) {
        $errors[] = 'Minimal pilih satu barang untuk dijual.';
    }

    $statement = db()->prepare('SELECT COUNT(*) FROM b_keluar WHERE no_faktur = :no_faktur');
    $statement->execute(['no_faktur' => $noFaktur]);

    if ((int) $statement->fetchColumn() > 0) {
        $errors[] = 'No faktur sudah pernah dipakai.';
    }

    return [
        $errors,
        [
            'no_faktur' => $noFaktur,
            'tgl_faktur' => $tglFaktur,
            'items' => $items,
        ],
    ];
}

function create_penjualan(array $sale, ?int $createdBy = null): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $select = $pdo->prepare('
            SELECT idbarang, nama, stok, harga, jual
            FROM barang
            WHERE idbarang = :idbarang
            FOR UPDATE
        ');
        $insert = $pdo->prepare('
            INSERT INTO b_keluar (
                no_faktur, tgl_faktur, kode_brg, jumlah, harga_beli, harga_jual, total, created_by
            ) VALUES (
                :no_faktur, :tgl_faktur, :kode_brg, :jumlah, :harga_beli, :harga_jual, :total, :created_by
            )
        ');
        $update = $pdo->prepare('
            UPDATE barang
            SET stok = stok - :jumlah
            WHERE idbarang = :idbarang
        ');

        foreach ($sale['items'] as $kode => $qty) {
            $select->execute(['idbarang' => $kode]);
            $barang = $select->fetch();

            if (!$barang) {
                throw new RuntimeException('Barang ' . $kode . ' tidak ditemukan.');
            }

            if ((int) $barang['stok'] < $qty) {
                throw new RuntimeException('Stok ' . $barang['nama'] . ' tidak cukup.');
            }

            $hargaBeli = (int) $barang['harga'];
            $hargaJual = (int) $barang['jual'];
            $total = $hargaJual * $qty;

            $insert->execute([
                'no_faktur' => $sale['no_faktur'],
                'tgl_faktur' => $sale['tgl_faktur'],
                'kode_brg' => $kode,
                'jumlah' => $qty,
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'total' => $total,
                'created_by' => $createdBy,
            ]);

            $update->execute([
                'jumlah' => $qty,
                'idbarang' => $kode,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function category_options_for_sales(array $barang): array
{
    $presets = ['Batu Alam', 'ATK', 'Buku', 'Material'];
    $actual = unique_options($barang, 'kelompok');

    return array_values(array_unique(array_merge($presets, $actual)));
}

// ── Revenue & Profit Dashboard Functions ─────────────────

function revenue_today(): array
{
    $stmt = db()->prepare('
        SELECT COALESCE(SUM(total), 0) AS revenue,
               COALESCE(SUM(total) - SUM(harga_beli * jumlah), 0) AS profit,
               COALESCE(SUM(jumlah), 0) AS items,
               COUNT(DISTINCT no_faktur) AS faktur
        FROM b_keluar
        WHERE tgl_faktur = CURDATE()
    ');
    $stmt->execute();
    return $stmt->fetch() ?: ['revenue' => 0, 'profit' => 0, 'items' => 0, 'faktur' => 0];
}

function revenue_this_month(): array
{
    $stmt = db()->prepare('
        SELECT COALESCE(SUM(total), 0) AS revenue,
               COALESCE(SUM(total) - SUM(harga_beli * jumlah), 0) AS profit,
               COALESCE(SUM(jumlah), 0) AS items,
               COUNT(DISTINCT no_faktur) AS faktur
        FROM b_keluar
        WHERE YEAR(tgl_faktur) = YEAR(CURDATE()) AND MONTH(tgl_faktur) = MONTH(CURDATE())
    ');
    $stmt->execute();
    return $stmt->fetch() ?: ['revenue' => 0, 'profit' => 0, 'items' => 0, 'faktur' => 0];
}

function revenue_this_year(): array
{
    $stmt = db()->prepare('
        SELECT COALESCE(SUM(total), 0) AS revenue,
               COALESCE(SUM(total) - SUM(harga_beli * jumlah), 0) AS profit,
               COALESCE(SUM(jumlah), 0) AS items,
               COUNT(DISTINCT no_faktur) AS faktur
        FROM b_keluar
        WHERE YEAR(tgl_faktur) = YEAR(CURDATE())
    ');
    $stmt->execute();
    return $stmt->fetch() ?: ['revenue' => 0, 'profit' => 0, 'items' => 0, 'faktur' => 0];
}

function daily_revenue_chart(int $days = 7): array
{
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(tgl_faktur, '%Y-%m-%d') AS label,
               COALESCE(SUM(total), 0) AS revenue,
               COALESCE(SUM(total) - SUM(harga_beli * jumlah), 0) AS profit
        FROM b_keluar
        WHERE tgl_faktur >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        GROUP BY tgl_faktur
        ORDER BY tgl_faktur ASC
    ");
    $stmt->execute(['days' => $days]);
    $rows = $stmt->fetchAll();

    // Fill missing days
    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $found = false;
        foreach ($rows as $row) {
            if ($row['label'] === $date) {
                $result[] = [
                    'label' => date('d M', strtotime($date)),
                    'revenue' => (int) $row['revenue'],
                    'profit' => (int) $row['profit'],
                ];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $result[] = [
                'label' => date('d M', strtotime($date)),
                'revenue' => 0,
                'profit' => 0,
            ];
        }
    }

    return $result;
}

function monthly_revenue_chart(int $months = 12): array
{
    $stmt = db()->prepare("
        SELECT DATE_FORMAT(tgl_faktur, '%Y-%m') AS label,
               COALESCE(SUM(total), 0) AS revenue,
               COALESCE(SUM(total) - SUM(harga_beli * jumlah), 0) AS profit
        FROM b_keluar
        WHERE tgl_faktur >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
        GROUP BY DATE_FORMAT(tgl_faktur, '%Y-%m')
        ORDER BY label ASC
    ");
    $stmt->execute(['months' => $months]);
    $rows = $stmt->fetchAll();

    $result = [];
    $monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    for ($i = $months - 1; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-{$i} months"));
        $month = (int) date('n', strtotime("-{$i} months"));
        $found = false;
        foreach ($rows as $row) {
            if ($row['label'] === $ym) {
                $result[] = [
                    'label' => $monthNames[$month - 1] . ' ' . date('y', strtotime("-{$i} months")),
                    'revenue' => (int) $row['revenue'],
                    'profit' => (int) $row['profit'],
                ];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $result[] = [
                'label' => $monthNames[$month - 1] . ' ' . date('y', strtotime("-{$i} months")),
                'revenue' => 0,
                'profit' => 0,
            ];
        }
    }

    return $result;
}

function yearly_revenue_chart(): array
{
    $stmt = db()->prepare("
        SELECT YEAR(tgl_faktur) AS label,
               COALESCE(SUM(total), 0) AS revenue,
               COALESCE(SUM(total) - SUM(harga_beli * jumlah), 0) AS profit
        FROM b_keluar
        GROUP BY YEAR(tgl_faktur)
        ORDER BY label ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        $currentYear = (int) date('Y');
        return [['label' => (string) $currentYear, 'revenue' => 0, 'profit' => 0]];
    }

    return array_map(fn($r) => [
        'label' => (string) $r['label'],
        'revenue' => (int) $r['revenue'],
        'profit' => (int) $r['profit'],
    ], $rows);
}

function recent_sales(int $limit = 10): array
{
    $stmt = db()->prepare("
        SELECT k.no_faktur, k.tgl_faktur, k.kode_brg, k.jumlah, k.harga_beli,
               k.harga_jual, k.total, b.nama, b.satuan, b.kelompok
        FROM b_keluar k
        INNER JOIN barang b ON b.idbarang = k.kode_brg
        ORDER BY k.tgl_faktur DESC, k.id DESC
        LIMIT :lmt
    ");
    $stmt->bindValue('lmt', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
