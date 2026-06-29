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
    <div class="brand-mark">
      <span class="brand-roof"></span>
      <strong>FAZMA<span>stone</span></strong>
    </div>

    <div class="security-badge" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.4 2.8 8.4 7 10 4.2-1.6 7-5.6 7-10V6l-7-3Zm3.2 7.5-4 4-2.1-2.1 1.1-1.1 1 1 2.9-2.9 1.1 1.1Z"/></svg>
    </div>

    <div class="login-copy">
      <p>Dashboard Inventaris</p>
      <h1>Fazma Stone</h1>
      <span></span>
      <p class="subtitle">Kelola stok batu alam, harga pokok, margin jual, diskon barang, dan data suplyer dalam satu sistem operasional.</p>
    </div>

    <div class="stone-display">
      <div class="slab slab-one"></div>
      <div class="slab slab-two"></div>
      <div class="slab slab-three"></div>
    </div>

    <small class="copyright">&copy; 2026 Fazma Stone Inventory System</small>
  </aside>

  <section class="login-panel">
    <a class="back-link" href="<?= h(url_path('/index.php')) ?>" aria-label="Kembali ke beranda">
      <svg viewBox="0 0 24 24"><path d="M19 11H7.8l4.6-4.6L11 5 4 12l7 7 1.4-1.4L7.8 13H19v-2Z"/></svg>
      Kembali ke beranda
    </a>

    <div class="section-kicker"><span></span> AREA TERBATAS</div>
    <h2>Login Admin</h2>
    <p class="panel-text">Masuk untuk mengelola inventaris dan dashboard operasional.</p>

    <form class="login-form" method="post" action="<?= h(url_path('/login.php')) ?>">
      <label>
        EMAIL / USERNAME
        <span class="input-wrap">
          <svg viewBox="0 0 24 24"><path d="M4 5h16c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V7c0-1.1.9-2 2-2Zm8 7.8L4 8v9h16V8l-8 4.8Zm0-2.3L19.2 7H4.8L12 10.5Z"/></svg>
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

      <button class="primary-button" type="submit">Masuk ke Dashboard</button>
      <div class="default-account">
        <span>Default admin</span>
        <strong>admin</strong>
        <small>Password: admin123</small>
      </div>
      <p class="form-note">Akun baru hanya dapat dibuat oleh admin melalui Role Management.</p>
    </form>
  </section>
</main>
<?php require __DIR__ . '/../frontend/components/flash.php'; ?>
<?php require __DIR__ . '/../frontend/layout/footer.php'; ?>
