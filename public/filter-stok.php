<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';

require_login();

$user = current_user();
$rows = all_barang();

$filters = [
    'search' => $_GET['search'] ?? '',
    'kelompok' => $_GET['kelompok'] ?? '',
    'supplyer' => $_GET['supplyer'] ?? '',
    'stok' => $_GET['stok'] ?? '',
    'sort' => $_GET['sort'] ?? 'nama-asc',
];

$items = filter_barang($rows, $filters);
$kelompokOptions = unique_options($rows, 'kelompok');
$supplyerOptions = unique_options($rows, 'supplyer');

// Summary counts
$totalBarang = count($rows);
$stokAman = count(array_filter($rows, fn($i) => $i['stok'] > 10));
$stokTipis = count(array_filter($rows, fn($i) => $i['stok'] > 0 && $i['stok'] <= 10));
$stokHabis = count(array_filter($rows, fn($i) => $i['stok'] === 0));

$pageTitle = 'Filter Stok - ' . APP_NAME;
require __DIR__ . '/../frontend/layout/header.php';
?>
<?php $currentPage = 'filter-stok'; ?>
<main class="dashboard">
  <?php require __DIR__ . '/../frontend/components/sidebar.php'; ?>

  <section class="content">
    <header class="topbar">
      <div>
        <span class="eyebrow">Pemantauan Stok</span>
        <h1>Filter Stok Barang</h1>
      </div>
      <div class="user-pill">
        <span><?= h($user['name'] ?? 'Admin') ?></span>
        <strong><?= h($user['role'] ?? 'Administrator') ?></strong>
      </div>
    </header>

    <!-- Summary Cards -->
    <section class="summary-grid">
      <div class="stat-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24"><path d="M3 7 12 2l9 5v10l-9 5-9-5V7Zm9 3.2L17.8 7 12 3.8 6.2 7 12 10.2Zm-7 5.6 6 3.4v-7.3L5 8.5v7.3Zm8 3.4 6-3.4V8.5l-6 3.4v7.3Z"/></svg>
        </div>
        <span class="card-label">Total Barang</span>
        <strong class="card-value"><?= number_format($totalBarang, 0, ',', '.') ?></strong>
        <small class="card-sub">Jenis barang terdaftar</small>
      </div>
      <div class="stat-card">
        <div class="card-icon">
          <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <span class="card-label">Stok Aman</span>
        <strong class="card-value green"><?= number_format($stokAman, 0, ',', '.') ?></strong>
        <small class="card-sub">Stok > 10 unit</small>
      </div>
      <div class="stat-card">
        <div class="card-icon amber">
          <svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
        </div>
        <span class="card-label">Stok Tipis</span>
        <strong class="card-value amber"><?= number_format($stokTipis, 0, ',', '.') ?></strong>
        <small class="card-sub">Perlu restock segera</small>
      </div>
      <div class="stat-card">
        <div class="card-icon red">
          <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        </div>
        <span class="card-label">Stok Habis</span>
        <strong class="card-value red"><?= number_format($stokHabis, 0, ',', '.') ?></strong>
        <small class="card-sub">Tidak tersedia</small>
      </div>
    </section>

    <section class="workspace full-width">
      <section class="table-panel">
        <form class="filter-bar" id="filter" method="get" action="<?= h(url_path('/filter-stok.php')) ?>">
          <input name="search" placeholder="🔍 Cari no, nama, kelompok, atau suplyer..." value="<?= h($filters['search']) ?>">
          <select name="kelompok" onchange="this.form.submit()">
            <option value="">Semua Kelompok</option>
            <?php foreach ($kelompokOptions as $option): ?>
              <option value="<?= h($option) ?>" <?= $filters['kelompok'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="supplyer" onchange="this.form.submit()">
            <option value="">Semua Suplyer</option>
            <?php foreach ($supplyerOptions as $option): ?>
              <option value="<?= h($option) ?>" <?= $filters['supplyer'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="stok" onchange="this.form.submit()">
            <option value="">Semua Stok</option>
            <option value="available" <?= $filters['stok'] === 'available' ? 'selected' : '' ?>>Tersedia</option>
            <option value="low" <?= $filters['stok'] === 'low' ? 'selected' : '' ?>>Stok Tipis</option>
            <option value="empty" <?= $filters['stok'] === 'empty' ? 'selected' : '' ?>>Kosong</option>
          </select>
          <select name="sort" onchange="this.form.submit()">
            <option value="nama-asc" <?= $filters['sort'] === 'nama-asc' ? 'selected' : '' ?>>Nama A-Z</option>
            <option value="stok-desc" <?= $filters['sort'] === 'stok-desc' ? 'selected' : '' ?>>Stok Terbanyak</option>
            <option value="stok-asc" <?= $filters['sort'] === 'stok-asc' ? 'selected' : '' ?>>Stok Tersedikit</option>
            <option value="harga-desc" <?= $filters['sort'] === 'harga-desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
            <option value="harga-asc" <?= $filters['sort'] === 'harga-asc' ? 'selected' : '' ?>>Harga Terendah</option>
          </select>
          <button type="submit" class="filter-button">FILTER</button>
        </form>

        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>No Barang</th>
                <th>Nama Barang</th>
                <th>Kelompok</th>
                <th>Stok</th>
                <th>Harga Pokok</th>
                <th>Margin</th>
                <th>Jual Reguler</th>
                <th>Suplyer</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: 48px; color: var(--gray-400);">
                    <svg style="width:40px;height:40px;opacity:0.3;margin-bottom:8px;" viewBox="0 0 24 24"><path d="M3 7 12 2l9 5v10l-9 5-9-5V7Z"/></svg>
                    <div>Data tidak ditemukan.</div>
                  </td>
                </tr>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td><strong><?= h($item['idbarang']) ?></strong></td>
                  <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <div style="width:36px; height:36px; border-radius:8px; overflow:hidden; background:var(--gray-50); flex-shrink:0; border: 1px solid var(--green-100);">
                        <?php if(!empty($item['gambar'])): ?>
                          <img src="<?= h(url_path('/' . $item['gambar'])) ?>" alt="<?= h($item['nama']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                          <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--gray-300);">
                            <svg style="width:16px; height:16px;" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm0 16H5V5h14v14Zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71Z"/></svg>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <strong style="color:var(--ink); display:block; font-size:14px;"><?= h($item['nama']) ?></strong>
                        <small style="color:var(--gray-400); font-size:12px;"><?= h($item['satuan']) ?> - <?= h($item['perdus']) ?> perdus <?php if(!empty($item['ukuran'])) echo '- ' . h($item['ukuran']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge"><?= h($item['kelompok']) ?></span></td>
                  <td>
                    <?php if ($item['stok'] === 0): ?>
                      <span class="stock-badge habis"><span class="dot"></span> 0 Habis</span>
                    <?php elseif ($item['stok'] < 10): ?>
                      <span class="stock-badge tipis"><span class="dot"></span> <?= h($item['stok']) ?> Tipis</span>
                    <?php else: ?>
                      <span class="stock-badge aman"><span class="dot"></span> <?= h($item['stok']) ?> Aman</span>
                    <?php endif; ?>
                  </td>
                  <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                  <td><?= h($item['margin1']) ?>%<br><small style="color:var(--gray-400)">Rp <?= number_format($item['harga1'], 0, ',', '.') ?></small></td>
                  <td>
                    <strong>Rp <?= number_format($item['jual'], 0, ',', '.') ?></strong>
                    <?php if ($item['batas_diskon'] > 0): ?>
                      <div style="margin-top:4px;">
                        <span style="color:var(--red-600); font-size:12px; display:block;">Diskon <?= h($item['batas_diskon']) ?>% (-Rp <?= number_format($item['jumlah_diskon'], 0, ',', '.') ?>)</span>
                        <strong style="color:var(--green-700); font-size:13px;">Rp <?= number_format($item['jual'] - $item['jumlah_diskon'], 0, ',', '.') ?></strong>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td><?= h($item['supplyer']) ?></td>
                  <td>
                    <div class="action-buttons">
                      <a href="<?= h(url_path('/dashboard.php?edit=' . urlencode($item['idbarang']))) ?>" class="btn-icon" title="Edit">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                      </a>
                      <form method="post" action="<?= h(url_path('/barang-delete.php')) ?>" onsubmit="return confirm('Hapus barang ini?');" style="display:inline;">
                        <input type="hidden" name="idbarang" value="<?= h($item['idbarang']) ?>">
                        <button type="submit" class="btn-icon danger" title="Hapus">
                          <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </section>
</main>
<?php require __DIR__ . '/../frontend/components/flash.php'; ?>
</body>
</html>
