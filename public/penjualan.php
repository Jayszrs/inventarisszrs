<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/barang.php';
require_once __DIR__ . '/../backend/penjualan.php';

require_login();

$user = current_user();
$barangRows = all_barang();
$categories = category_options_for_sales($barangRows);
$filters = [
    'search' => $_GET['search'] ?? '',
    'tanggal' => $_GET['tanggal'] ?? '',
    'kelompok' => $_GET['kelompok'] ?? '',
];
$sales = all_penjualan($filters);
$summary = penjualan_summary($sales);
$kelompokOptions = unique_options($barangRows, 'kelompok');
$nextFaktur = generate_no_faktur(date('Y-m-d'));
$productPayload = array_map(static fn (array $item): array => [
    'kode' => $item['idbarang'],
    'nama' => $item['nama'],
    'kelompok' => $item['kelompok'],
    'stok' => $item['stok'],
    'satuan' => $item['satuan'],
    'harga_beli' => $item['harga'],
    'harga_jual' => $item['jual'],
], $barangRows);

$pageTitle = 'Penjualan - ' . APP_NAME;
require __DIR__ . '/../frontend/layout/header.php';
?>
<?php $currentPage = 'penjualan'; ?>
<main class="dashboard">
  <?php require __DIR__ . '/../frontend/components/sidebar.php'; ?>

  <section class="content">
    <header class="topbar">
      <div>
        <span class="eyebrow">Barang Keluar</span>
        <h1>Penjualan Multi Kategori</h1>
      </div>

      <div class="user-pill">
        <span><?= h($user['name'] ?? 'Admin') ?></span>
        <strong><?= h($user['role'] ?? 'Administrator') ?></strong>
      </div>
    </header>

    <section class="summary-grid">
      <article>
        <span>Total Faktur</span>
        <strong><?= number_format($summary['totalFaktur'], 0, ',', '.') ?></strong>
      </article>
      <article>
        <span>Barang Terjual</span>
        <strong><?= number_format($summary['totalItem'], 0, ',', '.') ?></strong>
      </article>
      <article>
        <span>Total Penjualan</span>
        <strong><?= rupiah($summary['totalPenjualan']) ?></strong>
      </article>
      <article>
        <span>Total Modal</span>
        <strong><?= rupiah($summary['totalModal']) ?></strong>
      </article>
    </section>

    <section class="workspace sales-workspace">
      <form class="item-form sales-form" method="post" action="<?= h(url_path('/penjualan-save.php')) ?>">
        <div class="form-heading">
          <div>
            <span class="eyebrow">Input Faktur</span>
            <h2>Buat Penjualan</h2>
          </div>
        </div>

        <div class="form-grid">
          <label>No Faktur <input name="no_faktur" id="noFaktur" type="text" maxlength="10" value="<?= h($nextFaktur) ?>" required></label>
          <label>Tanggal Faktur <input name="tgl_faktur" id="tglFaktur" type="date" value="<?= h(date('Y-m-d')) ?>" required></label>
        </div>

        <div class="category-toolbar" id="categoryToolbar">
          <button type="button" class="category-btn active" data-category="">Semua</button>
          <?php foreach ($categories as $category): ?>
            <button type="button" class="category-btn" data-category="<?= h($category) ?>"><?= h($category) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="sale-lines" id="saleLines"></div>

        <button class="secondary-button" id="addSaleLine" type="button">
          <svg viewBox="0 0 24 24"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2h6Z"/></svg>
          Tambah Barang
        </button>

        <div class="sale-total-box">
          <span>Jumlah Total</span>
          <strong id="saleGrandTotal">Rp 0</strong>
        </div>

        <button class="primary-button" type="submit">
          <span>Simpan Penjualan</span>
          <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4ZM12 19c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3-1.3 3-3 3Zm3-10H5V5h10v4Z"/></svg>
        </button>
      </form>

      <section class="table-panel">
        <form class="filter-bar sales-filter" method="get" action="<?= h(url_path('/penjualan.php')) ?>">
          <input name="search" placeholder="Cari faktur, kode, atau nama barang" value="<?= h($filters['search']) ?>">
          <input name="tanggal" type="date" value="<?= h($filters['tanggal']) ?>">
          <select name="kelompok" onchange="this.form.submit()">
            <option value="">Semua Kelompok</option>
            <?php foreach ($kelompokOptions as $option): ?>
              <option value="<?= h($option) ?>" <?= $filters['kelompok'] === $option ? 'selected' : '' ?>><?= h($option) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="filter-button" type="submit">Filter</button>
        </form>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>No Faktur</th>
                <th>Tanggal</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$sales): ?>
                <tr><td colspan="8" class="empty-row">Belum ada data penjualan.</td></tr>
              <?php endif; ?>

              <?php foreach ($sales as $sale): ?>
                <tr>
                  <td><strong><?= h($sale['no_faktur']) ?></strong></td>
                  <td><?= h(date('d/m/Y', strtotime((string) $sale['tgl_faktur']))) ?></td>
                  <td><?= h($sale['kode_brg']) ?></td>
                  <td>
                    <div class="item-name"><?= h($sale['nama']) ?></div>
                    <small><?= h($sale['kelompok']) ?></small>
                  </td>
                  <td><?= number_format((int) $sale['jumlah'], 0, ',', '.') ?> <?= h($sale['satuan']) ?></td>
                  <td><?= rupiah($sale['harga_beli']) ?></td>
                  <td><?= rupiah($sale['harga_jual']) ?></td>
                  <td><strong><?= rupiah($sale['total']) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="7">Jumlah Total</th>
                <th><?= rupiah($summary['totalPenjualan']) ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>
    </section>
  </section>
</main>
<?php require __DIR__ . '/../frontend/components/flash.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const products = <?= json_encode($productPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const saleLines = document.getElementById('saleLines');
  const addSaleLine = document.getElementById('addSaleLine');
  const categoryButtons = document.querySelectorAll('.category-btn');
  const saleGrandTotal = document.getElementById('saleGrandTotal');
  let activeCategory = '';

  const formatRupiah = (number) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(number || 0);

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[char]));

  const categoryMatches = (item, category) => {
    if (!category) return true;
    const group = item.kelompok.toLowerCase();
    const selected = category.toLowerCase();

    if (selected === 'batu alam') {
      return ['batu', 'andesit', 'palimanan', 'marmer', 'granit', 'travertine'].some((keyword) => group.includes(keyword));
    }

    if (selected === 'atk') {
      return ['atk', 'buku', 'pensil', 'pulpen', 'kertas'].some((keyword) => group.includes(keyword) || item.nama.toLowerCase().includes(keyword));
    }

    if (selected === 'buku') {
      return group.includes('buku') || item.nama.toLowerCase().includes('buku');
    }

    return group === selected;
  };

  const optionMarkup = (selectedValue = '') => {
    const filtered = products.filter((item) => categoryMatches(item, activeCategory));
    const source = filtered.length ? filtered : products;

    return '<option value="">Pilih barang</option>' + source.map((item) => {
      const selected = item.kode === selectedValue ? ' selected' : '';
      return `<option value="${escapeHtml(item.kode)}"${selected}>${escapeHtml(item.kode)} - ${escapeHtml(item.nama)} (${escapeHtml(item.stok)} ${escapeHtml(item.satuan)})</option>`;
    }).join('');
  };

  const buildLine = () => {
    const line = document.createElement('div');
    line.className = 'sale-line';
    line.innerHTML = `
      <label>Barang
        <select name="kode_brg[]" class="sale-product" required>${optionMarkup()}</select>
      </label>
      <label>Jumlah
        <input name="jumlah[]" class="sale-qty" type="number" min="1" value="1" required>
      </label>
      <div class="mini-total">
        <span>Harga Jual</span>
        <strong class="line-price">Rp 0</strong>
      </div>
      <div class="mini-total">
        <span>Total</span>
        <strong class="line-total">Rp 0</strong>
      </div>
      <button class="icon-button remove-line" type="button" title="Hapus barang">
        <svg viewBox="0 0 24 24"><path d="M7 21c-1.1 0-2-.9-2-2V7h14v12c0 1.1-.9 2-2 2H7ZM9 4h6l1 1h4v2H4V5h4l1-1Z"/></svg>
      </button>
    `;
    saleLines.appendChild(line);
    refreshLine(line);
  };

  const findProduct = (kode) => products.find((item) => item.kode === kode);

  const refreshLine = (line) => {
    const select = line.querySelector('.sale-product');
    const qtyInput = line.querySelector('.sale-qty');
    const product = findProduct(select.value);
    const qty = parseInt(qtyInput.value, 10) || 0;
    const price = product ? parseInt(product.harga_jual, 10) : 0;
    const maxStock = product ? parseInt(product.stok, 10) : 0;

    qtyInput.max = maxStock || '';
    if (maxStock > 0 && qty > maxStock) {
      qtyInput.value = maxStock;
    }

    line.querySelector('.line-price').textContent = formatRupiah(price);
    line.querySelector('.line-total').textContent = formatRupiah(price * (parseInt(qtyInput.value, 10) || 0));
    refreshGrandTotal();
  };

  const refreshGrandTotal = () => {
    let total = 0;
    saleLines.querySelectorAll('.sale-line').forEach((line) => {
      const product = findProduct(line.querySelector('.sale-product').value);
      const qty = parseInt(line.querySelector('.sale-qty').value, 10) || 0;
      total += product ? parseInt(product.harga_jual, 10) * qty : 0;
    });
    saleGrandTotal.textContent = formatRupiah(total);
  };

  saleLines.addEventListener('change', (event) => {
    const line = event.target.closest('.sale-line');
    if (line) refreshLine(line);
  });

  saleLines.addEventListener('input', (event) => {
    const line = event.target.closest('.sale-line');
    if (line) refreshLine(line);
  });

  saleLines.addEventListener('click', (event) => {
    const button = event.target.closest('.remove-line');
    if (!button) return;
    button.closest('.sale-line').remove();
    if (!saleLines.children.length) buildLine();
    refreshGrandTotal();
  });

  categoryButtons.forEach((button) => {
    button.addEventListener('click', () => {
      categoryButtons.forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      activeCategory = button.dataset.category || '';

      saleLines.querySelectorAll('.sale-product').forEach((select) => {
        const selected = select.value;
        select.innerHTML = optionMarkup(selected);
      });
    });
  });

  addSaleLine.addEventListener('click', buildLine);
  buildLine();
});
</script>

<?php require __DIR__ . '/../frontend/layout/footer.php'; ?>
