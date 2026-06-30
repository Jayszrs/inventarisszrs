<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';

if (is_logged_in()) {
    redirect_to('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (login_user($identifier, $password)) {
        flash('Login berhasil. Selamat datang.');
        redirect_to('/dashboard.php');
    }

    flash('Username/email atau password salah.', 'error');
    redirect_to('/login.php');
}

$pageTitle = 'Login Admin - ' . APP_NAME;
require __DIR__ . '/../frontend/layout/header.php';
?>
<main class="login-shell">
  <aside class="login-visual">
    <div class="visual-bg-shapes">
      <div class="bg-circle bg-circle-1"></div>
      <div class="bg-circle bg-circle-2"></div>
      <div class="bg-circle bg-circle-3"></div>
    </div>

    <div class="brand-mark">
      <span class="brand-icon">
        <svg viewBox="0 0 24 24" fill="none"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2Zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8Z" fill="currentColor" opacity=".3"/><path d="M12 6c-1.1 0-2 .3-2.8.8l5.6 5.6c.5-.8.8-1.7.8-2.8 0-2-1.6-3.6-3.6-3.6Z" fill="currentColor"/></svg>
      </span>
      <strong>FAZMA<span>stone</span></strong>
    </div>

    <div class="login-copy">
      <p class="copy-subtitle">Dashboard Inventaris</p>
      <h1>Fazma Stone</h1>
      <span class="divider-line"></span>
      <p class="subtitle">Kelola stok batu alam, harga pokok, margin jual, diskon barang, dan data suplyer dalam satu sistem operasional.</p>
    </div>

    <div class="visual-features">
      <div class="feature-item">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24"><path d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4Zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8Z"/></svg>
        </div>
        <div>
          <strong>Aman & Terpercaya</strong>
          <small>Data inventaris terjaga</small>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-icon">
          <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2Zm-5 14H7v-2h7v2Zm3-4H7v-2h10v2Zm0-4H7V7h10v2Z"/></svg>
        </div>
        <div>
          <strong>Laporan Real-time</strong>
          <small>Pantau stok langsung</small>
        </div>
      </div>
    </div>

    <small class="copyright">&copy; 2026 Fazma Stone Inventory System</small>
  </aside>

  <section class="login-panel">
    <a class="back-link" href="<?= h(url_path('/index.php')) ?>" aria-label="Kembali ke beranda">
      <svg viewBox="0 0 24 24"><path d="M19 11H7.8l4.6-4.6L11 5 4 12l7 7 1.4-1.4L7.8 13H19v-2Z"/></svg>
      Kembali ke beranda
    </a>

    <div class="login-card">
      <div class="section-kicker"><span></span> AREA TERBATAS</div>
      <h2>Login Admin</h2>
      <p class="panel-text">Masuk untuk mengelola inventaris dan dashboard operasional.</p>

      <form class="login-form" method="post" action="<?= h(url_path('/login.php')) ?>">
        <label>
          ID / USERNAME / EMAIL
          <span class="input-wrap">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4Z"/></svg>
            <input name="identifier" autocomplete="username" placeholder="admin atau email@fazmastone.com" required>
          </span>
        </label>

        <label>
          PASSWORD
          <span class="input-wrap">
            <svg viewBox="0 0 24 24"><path d="M17 9h1c1.1 0 2 .9 2 2v9H4v-9c0-1.1.9-2 2-2h1V7c0-2.8 2.2-5 5-5s5 2.2 5 5v2Zm-8 0h6V7c0-1.7-1.3-3-3-3S9 5.3 9 7v2Z"/></svg>
            <input name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>
          </span>
        </label>

        <div class="forgot-row">
          <button type="button">Lupa password?</button>
        </div>

        <button class="primary-button" type="submit">
          <span>Masuk ke Dashboard</span>
          <svg viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8-8-8Z"/></svg>
        </button>

        <div class="default-account">
          <span>Default admin</span>
          <strong>admin</strong>
          <small>Password: admin123</small>
        </div>
        <p class="form-note">Akun baru hanya dapat dibuat oleh admin melalui Role Management.</p>
      </form>
    </div>
  </section>
</main>
<div class="portal-loader" id="portalLoader" aria-hidden="true">
  <div class="loader-card">
    <span class="loader-ring"></span>
    <strong>Masuk Portal</strong>
    <small>Memeriksa ID dan password</small>
  </div>
</div>
<?php require __DIR__ . '/../frontend/components/flash.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.login-form');
  const loader = document.getElementById('portalLoader');

  if (!form || !loader) return;

  form.addEventListener('submit', () => {
    loader.classList.add('active');
  });
});
</script>
<?php require __DIR__ . '/../frontend/layout/footer.php'; ?>
