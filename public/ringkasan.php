<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';
require_once __DIR__ . '/../backend/penjualan.php';

require_login();

$user = current_user();
$rows = all_barang();
$summary = barang_summary($rows);
$todayStats = revenue_today();
$monthStats = revenue_this_month();
$yearStats = revenue_this_year();
$chartPayload = [
    'daily' => daily_revenue_chart(7),
    'monthly' => monthly_revenue_chart(12),
    'yearly' => yearly_revenue_chart(),
];
$recentSales = recent_sales(8);
$lowStockItems = array_values(array_filter($rows, static fn (array $item): bool => $item['stok'] > 0 && $item['stok'] <= 10));
usort($lowStockItems, static fn (array $a, array $b): int => $a['stok'] <=> $b['stok']);
$lowStockItems = array_slice($lowStockItems, 0, 6);

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

    <section class="ringkasan-dashboard">
      <section class="summary-grid ringkasan-summary" id="laporan">
        <article class="stat-card">
          <span class="card-label">Total Barang</span>
          <strong class="card-value"><?= number_format($summary['totalBarang'], 0, ',', '.') ?></strong>
          <small class="card-sub">Jenis barang aktif</small>
        </article>
        <article class="stat-card">
          <span class="card-label">Jumlah Stok</span>
          <strong class="card-value"><?= number_format($summary['totalStok'], 0, ',', '.') ?></strong>
          <small class="card-sub">Total stok keseluruhan</small>
        </article>
        <article class="stat-card">
          <span class="card-label">Nilai Modal</span>
          <strong class="card-value green"><?= rupiah($summary['nilaiModal']) ?></strong>
          <small class="card-sub">Stok dikali harga pokok</small>
        </article>
        <article class="stat-card">
          <span class="card-label">Stok Tipis</span>
          <strong class="card-value red"><?= number_format($summary['stokTipis'], 0, ',', '.') ?></strong>
          <small class="card-sub">Barang perlu dipantau</small>
        </article>
      </section>

      <section class="ringkasan-section">
        <div class="section-label">Dashboard Statistik</div>
        <div class="revenue-grid">
          <article class="revenue-card">
            <div class="rc-header">
              <span class="rc-icon daily">
                <svg viewBox="0 0 24 24"><path d="M7 2h2v2h6V2h2v2h3c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2h3V2Zm13 8H4v10h16V10ZM4 8h16V6H4v2Z"/></svg>
              </span>
              <span class="rc-title">Hari Ini</span>
            </div>
            <div class="rc-amount"><?= rupiah($todayStats['revenue']) ?></div>
            <div class="rc-details">
              <span class="rc-detail"><small>Profit</small><span class="profit"><?= rupiah($todayStats['profit']) ?></span></span>
              <span class="rc-detail"><small>Faktur</small><span><?= number_format((int) $todayStats['faktur'], 0, ',', '.') ?></span></span>
              <span class="rc-detail"><small>Barang</small><span><?= number_format((int) $todayStats['items'], 0, ',', '.') ?></span></span>
            </div>
          </article>

          <article class="revenue-card">
            <div class="rc-header">
              <span class="rc-icon monthly">
                <svg viewBox="0 0 24 24"><path d="M4 19h16v2H4v-2Zm1-2V9h3v8H5Zm5 0V3h3v14h-3Zm5 0v-6h3v6h-3Z"/></svg>
              </span>
              <span class="rc-title">Bulan Ini</span>
            </div>
            <div class="rc-amount"><?= rupiah($monthStats['revenue']) ?></div>
            <div class="rc-details">
              <span class="rc-detail"><small>Profit</small><span class="profit"><?= rupiah($monthStats['profit']) ?></span></span>
              <span class="rc-detail"><small>Faktur</small><span><?= number_format((int) $monthStats['faktur'], 0, ',', '.') ?></span></span>
              <span class="rc-detail"><small>Barang</small><span><?= number_format((int) $monthStats['items'], 0, ',', '.') ?></span></span>
            </div>
          </article>

          <article class="revenue-card">
            <div class="rc-header">
              <span class="rc-icon yearly">
                <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3V3Zm2 2v14h14V5H5Zm2 10 3-4 2 2.5L15.5 9 18 12.2V17H7v-2Z"/></svg>
              </span>
              <span class="rc-title">Tahun Ini</span>
            </div>
            <div class="rc-amount"><?= rupiah($yearStats['revenue']) ?></div>
            <div class="rc-details">
              <span class="rc-detail"><small>Profit</small><span class="profit"><?= rupiah($yearStats['profit']) ?></span></span>
              <span class="rc-detail"><small>Faktur</small><span><?= number_format((int) $yearStats['faktur'], 0, ',', '.') ?></span></span>
              <span class="rc-detail"><small>Barang</small><span><?= number_format((int) $yearStats['items'], 0, ',', '.') ?></span></span>
            </div>
          </article>
        </div>
      </section>

      <section class="chart-container">
        <div class="section-title">
          <h2>Grafik Penjualan & Profit</h2>
          <a class="export-btn" href="<?= h(url_path('/export-csv.php')) ?>">
            <svg viewBox="0 0 24 24"><path d="M5 20h14v-2H5v2ZM19 9h-4V3H9v6H5l7 7 7-7Z"/></svg>
            CSV
          </a>
        </div>
        <div class="chart-tabs" role="tablist" aria-label="Periode grafik">
          <button class="chart-tab active" type="button" data-chart="daily">7 Hari</button>
          <button class="chart-tab" type="button" data-chart="monthly">12 Bulan</button>
          <button class="chart-tab" type="button" data-chart="yearly">Tahunan</button>
        </div>
        <div class="chart-legend">
          <span class="chart-legend-item"><span class="chart-legend-dot revenue"></span>Penjualan</span>
          <span class="chart-legend-item"><span class="chart-legend-dot profit"></span>Profit</span>
        </div>
        <div class="bar-chart" id="revenueChart"></div>
      </section>

      <section class="ringkasan-panels">
        <section class="table-panel recent-table">
          <div class="panel-heading">
            <h2>Penjualan Terbaru</h2>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Faktur</th>
                  <th>Tanggal</th>
                  <th>Barang</th>
                  <th>Jumlah</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$recentSales): ?>
                  <tr><td colspan="5" class="empty-row">Belum ada transaksi penjualan.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentSales as $sale): ?>
                  <tr>
                    <td><strong><?= h($sale['no_faktur']) ?></strong></td>
                    <td><?= h(date('d/m/Y', strtotime((string) $sale['tgl_faktur']))) ?></td>
                    <td>
                      <div class="item-name"><?= h($sale['nama']) ?></div>
                      <small><?= h($sale['kelompok']) ?></small>
                    </td>
                    <td><?= number_format((int) $sale['jumlah'], 0, ',', '.') ?> <?= h($sale['satuan']) ?></td>
                    <td><strong><?= rupiah($sale['total']) ?></strong></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="table-panel stock-watch-panel">
          <div class="panel-heading">
            <h2>Stok Tipis</h2>
          </div>
          <div class="stock-watch-list">
            <?php if (!$lowStockItems): ?>
              <div class="empty-row">Tidak ada stok tipis.</div>
            <?php endif; ?>
            <?php foreach ($lowStockItems as $item): ?>
              <div class="stock-watch-item">
                <div>
                  <strong><?= h($item['nama']) ?></strong>
                  <small><?= h($item['idbarang']) ?> - <?= h($item['kelompok']) ?></small>
                </div>
                <span class="stock tipis"><?= number_format((int) $item['stok'], 0, ',', '.') ?> <?= h($item['satuan']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </section>
    </section>
  </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const chartData = <?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const chart = document.getElementById('revenueChart');
  const tabs = document.querySelectorAll('.chart-tab');

  const formatCompact = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    notation: 'compact',
    maximumFractionDigits: 1
  }).format(value || 0);

  const renderChart = (key) => {
    const rows = chartData[key] || [];
    const max = Math.max(1, ...rows.flatMap((row) => [Number(row.revenue) || 0, Number(row.profit) || 0]));

    chart.innerHTML = rows.map((row) => {
      const revenue = Number(row.revenue) || 0;
      const profit = Number(row.profit) || 0;
      const revenueHeight = Math.max(3, Math.round((revenue / max) * 100));
      const profitHeight = Math.max(3, Math.round((profit / max) * 100));

      return `
        <div class="bar-group">
          <div class="bar-pair">
            <span class="bar revenue" style="height: ${revenueHeight}%">
              <span class="bar-tooltip">${formatCompact(revenue)}</span>
            </span>
            <span class="bar profit" style="height: ${profitHeight}%">
              <span class="bar-tooltip">${formatCompact(profit)}</span>
            </span>
          </div>
          <span class="bar-label">${row.label}</span>
        </div>
      `;
    }).join('');
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((item) => item.classList.remove('active'));
      tab.classList.add('active');
      renderChart(tab.dataset.chart);
    });
  });

  renderChart('daily');
});
</script>

<?php require __DIR__ . '/../frontend/layout/footer.php'; ?>
