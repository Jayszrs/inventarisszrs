<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function read_json_file(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    $data = json_decode($content ?: '[]', true);

    return is_array($data) ? $data : [];
}

function write_json_file(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

function redirect_to(string $path): never
{
    header('Location: ' . url_path($path));
    exit;
}

function app_base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $directory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($directory === '' || $directory === '.') {
        return '';
    }

    return $directory;
}

function url_path(string $path = ''): string
{
    if (preg_match('/^https?:\/\//', $path) === 1) {
        return $path;
    }

    $base = app_base_path();
    $path = '/' . ltrim($path, '/');

    return $base . $path;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function pull_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function number_value(mixed $value): int
{
    return max(0, (int) $value);
}

function rupiah(mixed $value): string
{
    return 'Rp ' . number_format((int) $value, 0, ',', '.');
}
