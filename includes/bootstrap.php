<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

set_exception_handler(static function (Throwable $e): void {
    if ($e instanceof PDOException) {
        app_error_page(
            'Error de base de datos',
            'La conexión existe, pero una consulta falló. Comprueba que importaste <code>schema.sql</code> en la base de <code>config.php</code> y que el usuario tiene permisos.',
            $e
        );
    }
    app_error_page(
        'Error en la aplicación',
        'Ha ocurrido un error inesperado. Revisa el log de PHP del hosting o contacta con quien instaló la app.',
        $e
    );
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    try {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ]);
    } catch (Throwable $e) {
        app_error_page(
            'No se pudo iniciar la sesión',
            'PHP no puede escribir sesiones. En Plesk comprueba que el directorio de sesiones es escribible.',
            $e
        );
    }
}

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/stats.php';

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    app_error_page(
        'Falta config.php',
        'Copia <code>config.example.php</code> a <code>config.php</code> y configura MySQL. Ver el README para los pasos de instalación.'
    );
}

try {
    /** @var mixed $config */
    $config = require $configPath;
} catch (Throwable $e) {
    app_error_page(
        'config.php inválido',
        'El archivo <code>config.php</code> tiene un error de sintaxis o falló al cargarse. Cópialo de nuevo desde <code>config.example.php</code>.',
        $e
    );
}

if (!is_array($config)) {
    app_error_page(
        'config.php inválido',
        'El archivo debe <code>return [...]</code> un array (como en <code>config.example.php</code>), no solo asignar variables.'
    );
}

if (!isset($config['db']) || !is_array($config['db'])) {
    app_error_page(
        'config.php incompleto',
        'Falta la clave <code>db</code>. Usa la estructura de <code>config.example.php</code>.'
    );
}

if (!isset($config['app_password']) || !is_string($config['app_password']) || $config['app_password'] === '') {
    app_error_page(
        'config.php incompleto',
        'Falta <code>app_password</code> (la contraseña de acceso a la app).'
    );
}

$pdo = db_connect($config['db']);
