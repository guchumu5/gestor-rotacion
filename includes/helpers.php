<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function money_es(float|int|string $amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' €';
}

function qty_es(float|int|string $qty): string
{
    $n = (float) $qty;
    if (abs($n - round($n)) < 0.001) {
        return (string) (int) round($n);
    }
    return number_format($n, 2, ',', '.');
}

function today_iso(): string
{
    return date('Y-m-d');
}

function parse_decimal(string $value): ?float
{
    $normalized = str_replace(',', '.', trim($value));
    if ($normalized === '' || !is_numeric($normalized)) {
        return null;
    }
    return (float) $normalized;
}

function pct_delta(float $current, float $previous): ?float
{
    if ($previous == 0.0) {
        return $current == 0.0 ? 0.0 : null;
    }
    return (($current - $previous) / $previous) * 100.0;
}

function format_delta(?float $pct): string
{
    if ($pct === null) {
        return '—';
    }
    $sign = $pct > 0 ? '+' : '';
    return $sign . number_format($pct, 0, ',', '.') . '%';
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function get_string(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}
