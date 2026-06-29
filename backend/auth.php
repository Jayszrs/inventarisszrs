<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect_to('/login.php');
    }
}

function login_user(string $identifier, string $password): bool
{
    $key = strtolower(trim($identifier));
    $statement = db()->prepare('
        SELECT id, username, email, password_hash, role, name
        FROM admins
        WHERE LOWER(username) = :username OR LOWER(email) = :email
        LIMIT 1
    ');
    $statement->execute([
        'username' => $key,
        'email' => $key,
    ]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => $user['id'] ?? '',
        'username' => $user['username'] ?? '',
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? 'Administrator',
        'name' => $user['name'] ?? 'Admin',
    ];

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
