<?php
$currentPage = $currentPage ?? 'dashboard';
?>
<aside class="sidebar">
  <div class="brand-mark compact">
    <span class="brand-roof"></span>
    <strong>FAZMA<span>stone</span></strong>
  </div>

  <nav>
    <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= h(url_path('/dashboard.php')) ?>">
      <svg viewBox="0 0 24 24"><path d="M3 7 12 2l9 5v10l-9 5-9-5V7Zm9 3.2L17.8 7 12 3.8 6.2 7 12 10.2Zm-7 5.6 6 3.4v-7.3L5 8.5v7.3Zm8 3.4 6-3.4V8.5l-6 3.4v7.3Z"/></svg>
      Data Barang
    </a>
    <a class="<?= $currentPage === 'filter-stok' ? 'active' : '' ?>" href="<?= h(url_path('/filter-stok.php')) ?>">
      <svg viewBox="0 0 24 24"><path d="M3 5h18v2H3V5Zm4 6h10v2H7v-2Zm3 6h4v2h-4v-2Z"/></svg>
      Filter Stok
    </a>
    <a class="<?= $currentPage === 'ringkasan' ? 'active' : '' ?>" href="<?= h(url_path('/ringkasan.php')) ?>">
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
