<?php
/**
 * Copia este archivo a config.php y ajusta los valores.
 * config.php no se sube a git (está en .gitignore).
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'gestor_rotacion',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    // Contraseña compartida del productor (sesión PHP real).
    'app_password' => 'hola',
];
