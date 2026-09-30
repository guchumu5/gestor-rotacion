<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Página de error visible (evita pantalla blanca 500 con display_errors=Off).
 * Registra el detalle técnico en el log de PHP.
 */
function app_error_page(string $title, string $message, ?Throwable $exception = null, int $status = 500): void
{
    // Evitar bucles si header()/echo disparan warnings convertidos en excepciones.
    restore_error_handler();
    restore_exception_handler();

    if ($exception !== null) {
        error_log('[gestor-rotacion] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    } else {
        error_log('[gestor-rotacion] ' . $title . ' — ' . strip_tags($message));
    }

    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
    }

    $detail = $exception !== null ? $exception->getMessage() : '';

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . '</title></head>';
    echo '<body style="font-family:system-ui,sans-serif;padding:2rem;max-width:40rem;margin:auto;line-height:1.45;color:#111">';
    echo '<h1 style="font-size:1.5rem;margin:0 0 .75rem">' . e($title) . '</h1>';
    echo '<p style="margin:0 0 1rem">' . $message . '</p>';
    if ($detail !== '') {
        echo '<p style="color:#666;font-size:.95rem;word-break:break-word"><strong>Detalle:</strong> ' . e($detail) . '</p>';
    }
    echo '<p style="color:#666;font-size:.9rem;margin-top:1.5rem">Si acabas de instalar la app: revisa <code>config.php</code> e importa <code>schema.sql</code> en MySQL.</p>';
    echo '</body></html>';
    exit;
}

/**
 * iOS standalone: un 302 tras POST (o a veces GET) abre Safari y sale del webview.
 * Detectamos el flag de formulario o la cookie que pone el JS en modo standalone.
 */
function wants_standalone_nav(): bool
{
    if (isset($_POST['standalone']) && (string) $_POST['standalone'] === '1') {
        return true;
    }
    if (isset($_COOKIE['gr_standalone']) && (string) $_COOKIE['gr_standalone'] === '1') {
        return true;
    }
    return false;
}

/**
 * Redirección que no rompe el modo standalone de iOS (sin header Location).
 */
function standalone_redirect_page(string $path): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    $href = e($path);
    $jsPath = json_encode($path, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    echo '<!DOCTYPE html><html lang="es"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
    echo '<title>Redirigiendo…</title>';
    echo '<script>location.replace(' . $jsPath . ');</script>';
    echo '</head><body style="font-family:system-ui,sans-serif;padding:2rem;text-align:center">';
    echo '<p><a href="' . $href . '">Continuar</a></p>';
    echo '</body></html>';
    exit;
}

function redirect(string $path): void
{
    if (wants_standalone_nav()) {
        standalone_redirect_page($path);
    }
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

/** Formatea dinero o "—" si no hay precio / total. */
function money_or_dash(float|int|string|null $amount): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }
    return money_es($amount);
}

/** Precio unitario para listados: "—" si NULL. */
function unit_price_es(float|int|string|null $price): string
{
    if ($price === null || $price === '') {
        return '—';
    }
    return money_es($price);
}

/**
 * Precio unitario en HTML: ocultable con el ojo (data-price).
 * Sin precio → "—"; con precio → valor + máscara "•••" (CSS/JS).
 */
function price_span(float|int|string|null $price): string
{
    if ($price === null || $price === '') {
        return '<span class="text-muted">—</span>';
    }
    $text = e(money_es($price));
    return '<span class="price-wrap" data-price>'
        . '<span class="price-value">' . $text . '</span>'
        . '<span class="price-mask" aria-hidden="true">•••</span>'
        . '</span>';
}

/**
 * Total de línea = qty * precio unitario.
 * Devuelve null si no hay precio (no tratar como 0 €).
 */
function line_total(float|int|string $quantity, float|int|string|null $unitPrice): ?float
{
    if ($unitPrice === null || $unitPrice === '') {
        return null;
    }
    return (float) $quantity * (float) $unitPrice;
}

function qty_es(float|int|string $qty): string
{
    $n = (float) $qty;
    if (abs($n - round($n)) < 0.001) {
        return (string) (int) round($n);
    }
    return number_format($n, 2, ',', '.');
}

function days_label(int $days): string
{
    if ($days === 1) {
        return '1 día';
    }
    return $days . ' días';
}

function client_url(int $clientId): string
{
    return 'cliente.php?id=' . $clientId;
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
