<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/admins.php';

require_admin();

$user = current_user();
$admins = all_admins();
$editingId = (int) ($_GET['edit'] ?? 0);
$editing = $editingId > 0 ? find_admin($editingId) : null;
$form = $editing ?? [];

$pageTitle = 'Role Management - ' . APP_NAME;
require __DIR__ . '/../frontend/layout/header.php';
?>
<?php $currentPage = 'role-management'; ?>
<main class="dashboard">
  <?php require __DIR__ . '/../frontend/components/sidebar.php'; ?>

  <section class="content">
    <header class="topbar">
      <div>
        <span class="eyebrow">Akses Pengguna</span>
        <h1>Role Management</h1>
      </div>

      <div class="user-pill">
        <span><?= h($user['name'] ?? 'Admin') ?></span>
        <strong><?= h($user['role'] ?? 'Administrator') ?></strong>
      </div>
    </header>

    <section class="workspace role-workspace">
      <form class="item-form" method="post" action="<?= h(url_path('/admin-save.php')) ?>">
        <div class="form-heading">
          <div>
            <span class="eyebrow"><?= $editing ? 'Edit Akun' : 'Tambah Akun' ?></span>
            <h2><?= $editing ? 'Ubah Pengguna' : 'Akun Baru' ?></h2>
          </div>
          <a class="ghost-link" href="<?= h(url_path('/role-management.php')) ?>">Reset</a>
        </div>

        <input type="hidden" name="mode" value="<?= $editing ? 'update' : 'create' ?>">
        <input type="hidden" name="id" value="<?= h($form['id'] ?? '') ?>">

        <div class="form-section">
          <h3>Identitas Login</h3>
          <div class="form-grid">
            <label>Nama Lengkap <input name="name" type="text" value="<?= h($form['name'] ?? '') ?>" required></label>
            <label>Username <input name="username" type="text" value="<?= h($form['username'] ?? '') ?>" required></label>
            <label>Email <input name="email" type="email" value="<?= h($form['email'] ?? '') ?>" required></label>
            <label>Role
              <select name="role" required>
                <?php foreach (ADMIN_ROLES as $role): ?>
                  <option value="<?= h($role) ?>" <?= ($form['role'] ?? 'Kasir') === $role ? 'selected' : '' ?>><?= h($role) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label style="grid-column: 1 / -1">Password
              <input name="password" type="password" autocomplete="new-password" placeholder="<?= $editing ? 'Kosongkan jika tidak diganti' : 'Minimal 6 karakter' ?>" <?= $editing ? '' : 'required' ?>>
            </label>
          </div>
        </div>

        <button class="primary-button" type="submit">
          <span><?= $editing ? 'Simpan Perubahan' : 'Buat Akun' ?></span>
          <svg viewBox="0 0 24 24"><path d="M17 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4ZM12 19c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3-1.3 3-3 3Zm3-10H5V5h10v4Z"/></svg>
        </button>
      </form>

      <section class="table-panel">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Dibuat</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $admin): ?>
                <tr>
                  <td><strong><?= h($admin['name']) ?></strong></td>
                  <td><?= h($admin['username']) ?></td>
                  <td><?= h($admin['email']) ?></td>
                  <td><span class="tag"><?= h($admin['role']) ?></span></td>
                  <td><?= h(date('d/m/Y', strtotime((string) $admin['created_at']))) ?></td>
                  <td>
                    <div class="row-actions">
                      <a href="<?= h(url_path('/role-management.php?edit=' . urlencode((string) $admin['id']))) ?>" title="Edit akun">
                        <svg viewBox="0 0 24 24"><path d="m14.1 5.9 4 4L8.7 19H5v-3.7l9.1-9.4Zm5.3 2.6-4-4 1.2-1.2c.8-.8 2-.8 2.8 0l1.2 1.2c.8.8.8 2 0 2.8l-1.2 1.2Z"/></svg>
                      </a>
                      <form method="post" action="<?= h(url_path('/admin-delete.php')) ?>" onsubmit="return confirm('Hapus akun <?= h($admin['username']) ?>?')">
                        <input type="hidden" name="id" value="<?= h($admin['id']) ?>">
                        <button type="submit" title="Hapus akun" <?= (int) ($user['id'] ?? 0) === (int) $admin['id'] ? 'disabled' : '' ?>>
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
<?php require __DIR__ . '/../frontend/layout/footer.php'; ?>
