<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';

const ADMIN_ROLES = ['Administrator', 'Manager', 'Kasir'];

function all_admins(): array
{
    $statement = db()->query('
        SELECT id, username, email, role, name, created_at, updated_at
        FROM admins
        ORDER BY role ASC, name ASC
    ');

    return $statement->fetchAll();
}

function find_admin(int $id): ?array
{
    $statement = db()->prepare('
        SELECT id, username, email, role, name
        FROM admins
        WHERE id = :id
        LIMIT 1
    ');
    $statement->execute(['id' => $id]);
    $admin = $statement->fetch();

    return $admin ?: null;
}

function validate_admin_payload(array $payload, ?int $currentId = null): array
{
    $admin = [
        'username' => strtolower(substr(trim((string) ($payload['username'] ?? '')), 0, 50)),
        'email' => strtolower(substr(trim((string) ($payload['email'] ?? '')), 0, 120)),
        'role' => trim((string) ($payload['role'] ?? 'Kasir')),
        'name' => substr(trim((string) ($payload['name'] ?? '')), 0, 120),
        'password' => (string) ($payload['password'] ?? ''),
    ];
    $errors = [];

    if ($admin['username'] === '') {
        $errors[] = 'Username wajib diisi.';
    }

    if ($admin['email'] === '' || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if ($admin['name'] === '') {
        $errors[] = 'Nama wajib diisi.';
    }

    if (!in_array($admin['role'], ADMIN_ROLES, true)) {
        $errors[] = 'Role tidak valid.';
    }

    if ($currentId === null && strlen($admin['password']) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if ($admin['password'] !== '' && strlen($admin['password']) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    $sql = '
        SELECT id
        FROM admins
        WHERE (LOWER(username) = :username OR LOWER(email) = :email)
    ';
    $params = [
        'username' => $admin['username'],
        'email' => $admin['email'],
    ];

    if ($currentId !== null) {
        $sql .= ' AND id <> :current_id';
        $params['current_id'] = $currentId;
    }

    $sql .= ' LIMIT 1';
    $statement = db()->prepare($sql);
    $statement->execute($params);

    if ($statement->fetch()) {
        $errors[] = 'Username atau email sudah dipakai.';
    }

    return [$errors, $admin];
}

function create_admin(array $admin): void
{
    $statement = db()->prepare('
        INSERT INTO admins (username, email, password_hash, role, name)
        VALUES (:username, :email, :password_hash, :role, :name)
    ');
    $statement->execute([
        'username' => $admin['username'],
        'email' => $admin['email'],
        'password_hash' => password_hash($admin['password'], PASSWORD_DEFAULT),
        'role' => $admin['role'],
        'name' => $admin['name'],
    ]);
}

function update_admin(int $id, array $admin): bool
{
    if (!find_admin($id)) {
        return false;
    }

    $params = [
        'id' => $id,
        'username' => $admin['username'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'name' => $admin['name'],
    ];

    $passwordSql = '';
    if ($admin['password'] !== '') {
        $passwordSql = ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($admin['password'], PASSWORD_DEFAULT);
    }

    $statement = db()->prepare("
        UPDATE admins
        SET username = :username,
            email = :email,
            role = :role,
            name = :name
            {$passwordSql}
        WHERE id = :id
    ");
    $statement->execute($params);

    return true;
}

function delete_admin(int $id): bool
{
    $statement = db()->prepare('DELETE FROM admins WHERE id = :id');
    $statement->execute(['id' => $id]);

    return $statement->rowCount() > 0;
}
