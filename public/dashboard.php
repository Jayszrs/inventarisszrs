<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';

require_login();

$user = current_user();
$rows = all_barang();
$editingId = (string) ($_GET['edit'] ?? '');
$editing = null;

foreach ($rows as $row) {
    if ($row['idbarang'] === $editingId) {
        $editing = $row;
        break;
    }
}

$filters = [
    'search' => $_GET['search'] ?? '',
    'kelompok' => $_GET['kelompok'] ?? '',
    'supplyer' => $_GET['supplyer'] ?? '',
    'stok' => $_GET['stok'] ?? '',
    'sort' => $_GET['sort'] ?? 'nama-asc',
];

$items = filter_barang($rows, $filters);
$summary = barang_summary($rows);
$kelompokOptions = unique_options($rows, 'kelompok');
$supplyerOptions = unique_options($rows, 'supplyer');

$pageTitle = 'Dashboard - ' . APP_NAME;
require __DIR__ . '/../frontend/layout/header.php';
?>
<main class="dashboard">
  <aside class="sidebar">
    <div class="brand-mark compact">
      <span class="brand-roof"></span>
      <strong>FAZMA<span>stone</span></strong>
    </div>

    <nav>
      <a class="active" href="<?= h(url_path('/dashboard.php')) ?>">
        <svg viewBox="0 0 24 24"><path d="M3 7 12 2l9 5v10l-9 5-9-5V7Zm9 3.2L17.8 7 12 3.8 6.2 7 12 10.2Zm-7 5.6 6 3.4v-7.3L5 8.5v7.3Zm8 3.4 6-3.4V8.5l-6 3.4v7.3Z"/></svg>
        Data Barang
      </a>
      <a href="#filter">
        <svg viewBox="0 0 24 24"><path d="M3 5h18v2H3V5Zm4 6h10v2H7v-2Zm3 6h4v2h-4v-2Z"/></svg>
        Filter Stok
      </a>
      <a href="#laporan">
        <svg viewBox="0 0 24 24"><path d="M5 3h11l3 3v15H5V3Zm10 1.5V7h2.5L15 4.5ZM8 11h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg>
        Ringkasan
      </a>
    </nav>

    <form method="post" action="<?= h(url_path('/logout.php')) ?>" class="logout-form">
      <button class="logout-button" type="submit">
        <svg viewBox="0 0 24 24"><path d="M10 3h10v18H10v-2h8V5h-8V3Zm1.4 11.6L14 12l-2.6-2.6L12.8 8l5 5-5 5-1.4-1.4ZM4 11h10v2H4v-2Z"/></svg>
        Log Out
      </button>
    </form>
  </aside>

  <section class="content">
    <header class="topbar">
      <div>
        <span class="eyebrow">Dashboard Inventaris</span>
        <h1>Kelola Barang Fazma Stone</h1>
      </div>

      <div class="user-pill">
        <span><?= h($user['name'] ?? 'Admin') ?></span>
        <strong><?= h($user['role'] ?? 'Administrator') ?></strong>
      </div>
    </header>

    <section class="summary-grid" id="laporan">
      <article>
        <span>Total Barang</span>
        <strong><?= number_format($summary['totalBarang'], 0, ',', '.') ?></strong>
      </article>
      <article>
        <span>Jumlah Stok</span>
        <strong><?= number_format($summary['totalStok'], 0, ',', '.') ?></strong>
      </article>
      <article>
        <span>Nilai Modal</span>
        <strong><?= rupiah($summary['nilaiModal']) ?></strong>
      </article>
      <article>
        <span>Stok Tipis</span>
        <strong><?= number_format($summary['stokTipis'], 0, ',', '.') ?></strong>
      </article>
    </section>

    <section class="workspace">
      <form class="item-form" method="post" action="<?= h(url_path('/barang-save.php')) ?>" enctype="multipart/form-data">
        <div class="form-heading">
          <div>
            <span class="eyebrow"><?= $editing ? 'Edit Data' : 'Input Barang' ?></span>
            <h2><?= $editing ? 'Ubah Barang' : 'Tambah Barang' ?></h2>
          </div>
          <a class="ghost-link" href="<?= h(url_path('/dashboard.php')) ?>">Reset</a>
        </div>

        <input type="hidden" name="mode" value="<?= $editing ? 'update' : 'create' ?>">
        
        <?php $item = $editing ?? []; ?>

        <div class="form-section">
          <h3>Gambar Produk</h3>
          <div class="image-upload-area" id="imageUploadArea">
            <?php if (!empty($item['gambar'])): ?>
              <img src="<?= h(url_path('/' . $item['gambar'])) ?>" alt="Preview" class="image-preview active" id="imagePreview">
            <?php else: ?>
              <img src="" alt="Preview" class="image-preview" id="imagePreview">
            <?php endif; ?>
            <div class="upload-placeholder" <?= !empty($item['gambar']) ? 'style="display:none;"' : '' ?>>
              <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm0 16H5V5h14v14Zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71Z"/></svg>
              <span>Klik atau Drag & Drop Gambar<br><small>Maksimal 2MB (JPG, PNG, WEBP)</small></span>
            </div>
            <input type="file" name="gambar" id="gambarInput" accept="image/jpeg, image/png, image/webp, image/gif" class="hidden-input">
          </div>
        </div>

        <div class="form-section">
          <h3>Identitas Barang</h3>
          <div class="form-grid">
            <label>No Barang <input name="idbarang" type="text" value="<?= h($item['idbarang'] ?? '') ?>" placeholder="BRG-004" <?= $editing ? 'readonly' : '' ?> required></label>
            <label>Nama Barang <input name="nama" type="text" value="<?= h($item['nama'] ?? '') ?>" placeholder="Nama batu alam" required></label>
            <label>Kelompok <input name="kelompok" type="text" value="<?= h($item['kelompok'] ?? '') ?>" placeholder="MRM" required></label>
            <label>Satuan <input name="satuan" type="text" value="<?= h($item['satuan'] ?? '') ?>" placeholder="m2" required></label>
            <label>Suplyer <input name="supplyer" type="text" value="<?= h($item['supplyer'] ?? '') ?>" placeholder="SUP01" required></label>
          </div>
        </div>

        <div class="form-section">
          <h3>Stok & Harga Pokok</h3>
          <div class="form-grid">
            <label>Jumlah Stok <input name="stok" type="number" value="<?= h($item['stok'] ?? '0') ?>" required></label>
            <label>Jumlah Perdus <input name="perdus" type="number" value="<?= h($item['perdus'] ?? '0') ?>" required></label>
            <label>Harga Pokok (Rp) <input name="harga" id="inputHarga" type="number" value="<?= h($item['harga'] ?? '0') ?>" required></label>
          </div>
        </div>

        <div class="form-section">
          <h3>Margin & Jual Reguler</h3>
          <div class="form-grid align-center">
            <label>Margin % <input name="margin1" id="inputMargin" type="number" value="<?= h($item['margin1'] ?? '0') ?>" required></label>
            <div class="live-preview-box">
              <span>Harga Jual Reguler</span>
              <strong id="previewJual">Rp 0</strong>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Diskon Penjualan</h3>
          <input type="hidden" name="batas_diskon" id="inputDiskon" value="<?= h($item['batas_diskon'] ?? '0') ?>">
          <div class="discount-buttons" id="discountButtons">
            <button type="button" class="discount-btn" data-val="0">0%</button>
            <?php for ($d = 5; $d <= 95; $d += 5): ?>
              <button type="button" class="discount-btn" data-val="<?= $d ?>"><?= $d ?>%</button>
            <?php endfor; ?>
          </div>
          <div class="discount-preview-row">
            <div class="live-preview-box danger">
              <span>Potongan Diskon</span>
              <strong id="previewPotongan">Rp 0</strong>
            </div>
            <div class="live-preview-box success">
              <span>Harga Setelah Diskon</span>
              <strong id="previewHargaDiskon">Rp 0</strong>
            </div>
          </div>
        </div>

        <button class="primary-button" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah Barang' ?></button>
      </form>

      <section class="table-panel">
        <form class="filter-bar" id="filter" method="get" action="<?= h(url_path('/dashboard.php')) ?>">
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

          <button class="filter-button" type="submit">Filter</button>
        </form>

        <div class="table-wrap">
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
              <?php if (!$items): ?>
                <tr><td colspan="9" class="empty-row">Belum ada data yang cocok dengan filter.</td></tr>
              <?php endif; ?>

              <?php foreach ($items as $item): ?>
                <?php
                  $status = $item['stok'] === 0 ? 'Kosong' : ($item['stok'] <= 10 ? 'Tipis' : 'Aman');
                  $statusClass = strtolower($status);
                ?>
                <tr>
                  <td><strong><?= h($item['idbarang']) ?></strong></td>
                  <td>
                    <div class="item-name-col">
                      <?php if (!empty($item['gambar'])): ?>
                        <img src="<?= h(url_path('/' . $item['gambar'])) ?>" alt="Gambar" class="table-thumb">
                      <?php else: ?>
                        <div class="table-thumb placeholder"></div>
                      <?php endif; ?>
                      <div>
                        <div class="item-name"><?= h($item['nama']) ?></div>
                        <small><?= h($item['satuan']) ?> · <?= h($item['perdus']) ?> perdus</small>
                      </div>
                    </div>
                  </td>
                  <td><span class="tag"><?= h($item['kelompok']) ?></span></td>
                  <td><span class="stock <?= h($statusClass) ?>"><?= number_format($item['stok'], 0, ',', '.') ?> <?= h($status) ?></span></td>
                  <td><?= rupiah($item['harga']) ?></td>
                  <td><?= h($item['margin1']) ?>%<small><?= rupiah($item['harga1']) ?></small></td>
                  <td>
                    <strong><?= rupiah($item['jual']) ?></strong>
                    <?php if ($item['batas_diskon'] > 0): ?>
                      <small class="discount-label">Diskon <?= h($item['batas_diskon']) ?>% (-<?= rupiah($item['jumlah_diskon']) ?>)</small>
                      <strong class="text-success"><?= rupiah($item['jual'] - $item['jumlah_diskon']) ?></strong>
                    <?php endif; ?>
                  </td>
                  <td><?= h($item['supplyer']) ?></td>
                  <td>
                    <div class="row-actions">
                      <a href="<?= h(url_path('/dashboard.php?edit=' . urlencode($item['idbarang']))) ?>" title="Edit barang">
                        <svg viewBox="0 0 24 24"><path d="m14.1 5.9 4 4L8.7 19H5v-3.7l9.1-9.4Zm5.3 2.6-4-4 1.2-1.2c.8-.8 2-.8 2.8 0l1.2 1.2c.8.8.8 2 0 2.8l-1.2 1.2Z"/></svg>
                      </a>
                      <form method="post" action="<?= h(url_path('/barang-delete.php')) ?>" onsubmit="return confirm('Hapus barang <?= h($item['nama']) ?>?')">
                        <input type="hidden" name="idbarang" value="<?= h($item['idbarang']) ?>">
                        <button type="submit" title="Hapus barang">
                          <svg viewBox="0 0 24 24"><path d="M7 21c-1.1 0-2-.9-2-2V7h14v12c0 1.1-.9 2-2 2H7ZM9 4h6l1 1h4v2H4V5h4l1-1Z"/></svg>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
  const inputHarga = document.getElementById('inputHarga');
  const inputMargin = document.getElementById('inputMargin');
  const previewJual = document.getElementById('previewJual');
  const inputDiskon = document.getElementById('inputDiskon');
  const discountButtons = document.querySelectorAll('.discount-btn');
  const previewPotongan = document.getElementById('previewPotongan');
  const previewHargaDiskon = document.getElementById('previewHargaDiskon');
  
  const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
  };

  const calculate = () => {
    const harga = parseInt(inputHarga.value) || 0;
    const margin = parseInt(inputMargin.value) || 0;
    const diskonPersen = parseInt(inputDiskon.value) || 0;
    
    const hargaMargin = Math.round(harga * margin / 100);
    const hargaJual = harga + hargaMargin;
    
    const potonganDiskon = Math.round(hargaJual * diskonPersen / 100);
    const hargaSetelahDiskon = hargaJual - potonganDiskon;
    
    previewJual.textContent = formatRupiah(hargaJual);
    previewPotongan.textContent = formatRupiah(potonganDiskon);
    previewHargaDiskon.textContent = formatRupiah(hargaSetelahDiskon);
    
    // Update active button
    discountButtons.forEach(btn => {
      if(parseInt(btn.dataset.val) === diskonPersen) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  };

  inputHarga.addEventListener('input', calculate);
  inputMargin.addEventListener('input', calculate);
  
  discountButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      inputDiskon.value = btn.dataset.val;
      calculate();
    });
  });
  
  calculate();

  // Image upload preview
  const uploadArea = document.getElementById('imageUploadArea');
  const fileInput = document.getElementById('gambarInput');
  const imagePreview = document.getElementById('imagePreview');
  const placeholder = uploadArea.querySelector('.upload-placeholder');

  uploadArea.addEventListener('click', () => fileInput.click());
  
  uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
  });
  
  uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
  });
  
  uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
      handleFile(fileInput.files[0]);
    }
  });

  fileInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
      handleFile(this.files[0]);
    }
  });

  function handleFile(file) {
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.src = e.target.result;
      imagePreview.classList.add('active');
      placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php require __DIR__ . '/../frontend/layout/footer.php'; ?>
