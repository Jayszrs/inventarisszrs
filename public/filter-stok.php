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

    <section class="workspace full-width">
      <section class="table-panel">
        <form class="filter-bar" id="filter" method="get" action="<?= h(url_path('/filter-stok.php')) ?>">
          <input name="search" placeholder="Cari no, nama, kelompok, atau suplyer" value="<?= h($filters['search']) ?>">
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
          <button type="submit" class="primary-button">FILTER</button>
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
                  <td colspan="9" style="text-align: center; padding: 32px; color: var(--gray-500);">Data tidak ditemukan.</td>
                </tr>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td><strong><?= h($item['idbarang']) ?></strong></td>
                  <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <div style="width:32px; height:32px; border-radius:4px; overflow:hidden; background:var(--gray-50); flex-shrink:0;">
                        <?php if(!empty($item['gambar'])): ?>
                          <img src="<?= h(url_path('/' . $item['gambar'])) ?>" alt="<?= h($item['nama']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                          <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--gray-300);">
                            <svg style="width:16px; height:16px;" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm0 16H5V5h14v14Zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71Z"/></svg>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div>
                        <strong style="color:var(--gray-900); display:block;"><?= h($item['nama']) ?></strong>
                        <small style="color:var(--gray-500);"><?= h($item['satuan']) ?> - <?= h($item['perdus']) ?> perdus <?php if(!empty($item['ukuran'])) echo '- ' . h($item['ukuran']); ?></small>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge"><?= h($item['kelompok']) ?></span></td>
                  <td>
                    <?php if ($item['stok'] === 0): ?>
                      <span class="text-danger">0<br><small>Habis</small></span>
                    <?php elseif ($item['stok'] < 10): ?>
                      <span class="text-warning"><?= h($item['stok']) ?><br><small>Tipis</small></span>
                    <?php else: ?>
                      <span class="text-success"><?= h($item['stok']) ?><br><small>Aman</small></span>
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
