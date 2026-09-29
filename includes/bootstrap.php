<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/stats.php';

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Configuración</title></head><body style="font-family:system-ui;padding:2rem;max-width:40rem;margin:auto">';
    echo '<h1>Falta config.php</h1>';
    echo '<p>Copia <code>config.example.php</code> a <code>config.php</code> y configura MySQL.</p>';
    echo '<p>Ver el README para los pasos de instalación.</p></body></html>';
    exit;
}

/** @var array{db: array{host:string,port:int,name:string,user:string,pass:string,charset:string}, app_password:string} $config */
$config = require $configPath;
$pdo = db_connect($config['db']);
