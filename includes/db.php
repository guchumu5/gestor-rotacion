<?php
declare(strict_types=1);

/**
 * @param array{host?:mixed,port?:mixed,name?:mixed,user?:mixed,pass?:mixed,charset?:mixed} $db
 */
function db_connect(array $db): PDO
{
    if (!class_exists('PDO')) {
        app_error_page(
            'Falta la extensión PDO',
            'PHP no tiene la extensión <code>PDO</code>. En Plesk/Apache activa <code>pdo_mysql</code> para esta versión de PHP.'
        );
    }

    $required = ['host', 'port', 'name', 'user', 'pass', 'charset'];
    foreach ($required as $key) {
        if (!array_key_exists($key, $db)) {
            app_error_page(
                'config.php incompleto',
                'Falta la clave <code>db.' . e($key) . '</code>. Copia de nuevo <code>config.example.php</code> a <code>config.php</code> y ajusta los valores.'
            );
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        (string) $db['host'],
        (int) $db['port'],
        (string) $db['name'],
        (string) $db['charset']
    );

    try {
        $pdo = new PDO($dsn, (string) $db['user'], (string) $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        app_error_page(
            'No se pudo conectar a MySQL',
            'Revisa host, nombre de base de datos, usuario y contraseña en <code>config.php</code>.',
            $e
        );
        // app_error_page() termina el script; esto solo satisface al analizador.
        throw $e;
    }

    db_require_schema($pdo);

    return $pdo;
}

function db_require_schema(PDO $pdo): void
{
    $needed = ['clients', 'products', 'movements'];
    $missing = [];

    try {
        foreach ($needed as $table) {
            // SHOW TABLES LIKE es seguro: nombres fijos del esquema de la app.
            $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
            if ($stmt === false || $stmt->fetchColumn() === false) {
                $missing[] = $table;
            }
        }
    } catch (Throwable $e) {
        app_error_page(
            'No se pudo comprobar el esquema',
            'La conexión a MySQL funcionó, pero no se pudieron listar las tablas. Comprueba permisos del usuario e importa <code>schema.sql</code>.',
            $e
        );
    }

    if ($missing !== []) {
        $safe = array_map(static fn(string $t): string => e($t), $missing);
        app_error_page(
            'Faltan tablas en MySQL',
            'La base de datos conecta, pero no están las tablas necesarias (<code>'
            . implode('</code>, <code>', $safe)
            . '</code>). Importa <code>schema.sql</code> en la misma base configurada en <code>config.php</code>.'
        );
    }
}

function clients_all(PDO $pdo, bool $onlyActive = false): array
{
    $sql = 'SELECT id, name, active, created_at FROM clients';
    if ($onlyActive) {
        $sql .= ' WHERE active = 1';
    }
    $sql .= ' ORDER BY name ASC';
    return $pdo->query($sql)->fetchAll();
}

function products_all(PDO $pdo, bool $onlyActive = false): array
{
    $sql = 'SELECT id, name, active, created_at FROM products';
    if ($onlyActive) {
        $sql .= ' WHERE active = 1';
    }
    $sql .= ' ORDER BY name ASC';
    return $pdo->query($sql)->fetchAll();
}

function client_movement_count(PDO $pdo, int $id): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM movements WHERE client_id = ?');
    $stmt->execute([$id]);
    return (int) $stmt->fetchColumn();
}

function product_movement_count(PDO $pdo, int $id): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM movements WHERE product_id = ?');
    $stmt->execute([$id]);
    return (int) $stmt->fetchColumn();
}

function find_or_create_client(PDO $pdo, string $name): int
{
    $name = trim($name);
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        $pdo->prepare('UPDATE clients SET active = 1 WHERE id = ?')->execute([(int) $id]);
        return (int) $id;
    }
    $ins = $pdo->prepare('INSERT INTO clients (name, active) VALUES (?, 1)');
    $ins->execute([$name]);
    return (int) $pdo->lastInsertId();
}
