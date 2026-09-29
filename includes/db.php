<?php
declare(strict_types=1);

/**
 * @param array{host:string,port:int,name:string,user:string,pass:string,charset:string} $db
 */
function db_connect(array $db): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        (int) $db['port'],
        $db['name'],
        $db['charset']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Base de datos</title></head><body style="font-family:system-ui;padding:2rem;max-width:40rem;margin:auto">';
        echo '<h1>No se pudo conectar a MySQL</h1>';
        echo '<p>Revisa <code>config.php</code> e importa <code>schema.sql</code>.</p>';
        echo '<p style="color:#666">' . e($e->getMessage()) . '</p></body></html>';
        exit;
    }

    return $pdo;
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
