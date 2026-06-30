<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';

require_login();

$user = current_user();
$rows = all_barang();
$summary = barang_summary($rows);

$pageTitle = 'Ringkasan Laporan - ' . APP_NAME;
require __DIR__ . '/../frontend/layout/header.php';
?>
<?php $currentPage = 'ringkasan'; ?>
<main class="dashboard">
  <?php require __DIR__ . '/../frontend/components/sidebar.php'; ?>

  <section class="content">
    <header class="topbar">
      <div>
        <span class="eyebrow">Laporan Keuangan & Stok</span>
        <h1>Ringkasan Inventaris</h1>
      </div>
      <div class="user-pill">
        <span><?= h($user['name'] ?? 'Admin') ?></span>
        <strong><?= h($user['role'] ?? 'Administrator') ?></strong>
      </div>
    </header>

    <section class="workspace full-width" style="display:flex; align-items:flex-start; padding: 24px;">
      <section class="summary-grid large" id="laporan" style="width: 100%; max-width: 1200px; margin: 0 auto; gap: 24px;">
        <article style="padding: 32px;">
          <span style="font-size: 16px;">Total Barang</span>
          <strong style="font-size: 36px; margin-top: 12px;"><?= number_format($summary['totalBarang'], 0, ',', '.') ?></strong>
        </article>
        <article style="padding: 32px;">
          <span style="font-size: 16px;">Jumlah Stok Keseluruhan</span>
          <strong style="font-size: 36px; margin-top: 12px;"><?= number_format($summary['totalStok'], 0, ',', '.') ?></strong>
        </article>
        <article style="padding: 32px;">
          <span style="font-size: 16px;">Total Nilai Modal</span>
          <strong style="font-size: 36px; margin-top: 12px; color: var(--green-600);"><?= rupiah($summary['nilaiModal']) ?></strong>
        </article>
        <article style="padding: 32px;">
          <span style="font-size: 16px;">Barang Stok Tipis</span>
          <strong style="font-size: 36px; margin-top: 12px; color: var(--red-600);"><?= number_format($summary['stokTipis'], 0, ',', '.') ?></strong>
        </article>
      </section>
    </section>
  </section>
</main>
</body>
</html>
