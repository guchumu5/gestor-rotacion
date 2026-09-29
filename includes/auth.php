<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return !empty($_SESSION['authenticated']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function attempt_login(string $password, string $expected): bool
{
    if (hash_equals($expected, $password)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        return true;
    }
    return false;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}
