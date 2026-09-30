<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$clients = clients_all($pdo, false);
$products = products_all($pdo, false);

$filterClient = get_string('client_id');
$filterProduct = get_string('product_id');
$filterFrom = get_string('from');
$filterTo = get_string('to');

$sql = 'SELECT m.id, m.client_id, m.quantity, m.price, m.note, m.movement_date, m.created_at,
               c.name AS client_name, p.name AS product_name
        FROM movements m
        INNER JOIN clients c ON c.id = m.client_id
        INNER JOIN products p ON p.id = m.product_id
        WHERE 1=1';
$params = [];

if ($filterClient !== '' && ctype_digit($filterClient)) {
    $sql .= ' AND m.client_id = ?';
    $params[] = (int) $filterClient;
}
if ($filterProduct !== '' && ctype_digit($filterProduct)) {
    $sql .= ' AND m.product_id = ?';
    $params[] = (int) $filterProduct;
}
if ($filterFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) {
    $sql .= ' AND m.movement_date >= ?';
    $params[] = $filterFrom;
}
if ($filterTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo)) {
    $sql .= ' AND m.movement_date <= ?';
    $params[] = $filterTo;
}

$sql .= ' ORDER BY m.movement_date DESC, m.created_at DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

foreach ($rows as &$row) {
    $priceRaw = $row['price'];
    $row['unit_price'] = $priceRaw === null ? null : (float) $priceRaw;
}
unset($row);

$pageTitle = 'Historial · Gestor de rotación';
$activeNav = 'historial';
require __DIR__ . '/includes/header.php';
?>
<div class="page">
  <div class="page-head">
    <p class="eyebrow mb-1" style="color:var(--red)">Pedidos</p>
    <h1 class="page-title">Historial</h1>
    <p class="page-sub">Filtra por cliente, producto o fechas. Unidades siempre visibles.</p>
  </div>

  <form method="get" class="panel">
    <div class="filter-grid">
      <div>
        <label class="form-label" for="client_id">Cliente</label>
        <select class="form-select" name="client_id" id="client_id">
          <option value="">Todos</option>
          <?php foreach ($clients as $client): ?>
            <option value="<?= (int) $client['id'] ?>" <?= $filterClient === (string) $client['id'] ? 'selected' : '' ?>>
              <?= e($client['name']) ?><?= !(int) $client['active'] ? ' (arch.)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" for="product_id">Producto</label>
        <select class="form-select" name="product_id" id="product_id">
          <option value="">Todos</option>
          <?php foreach ($products as $product): ?>
            <option value="<?= (int) $product['id'] ?>" <?= $filterProduct === (string) $product['id'] ? 'selected' : '' ?>>
              <?= e($product['name']) ?><?= !(int) $product['active'] ? ' (arch.)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label" for="from">Desde</label>
        <input class="form-control" type="date" name="from" id="from" value="<?= e($filterFrom) ?>">
      </div>
      <div>
        <label class="form-label" for="to">Hasta</label>
        <input class="form-control" type="date" name="to" id="to" value="<?= e($filterTo) ?>">
      </div>
    </div>
    <div class="action-row mt-3">
      <button class="btn btn-brand" type="submit" style="min-height:48px;border-radius:14px;font-weight:700;flex:1">Filtrar</button>
      <a class="btn btn-outline-secondary" href="historial.php" style="min-height:48px;border-radius:14px;display:grid;place-items:center">Limpiar</a>
    </div>
  </form>

  <?php if (!$rows): ?>
    <div class="panel empty-state">
      <strong>Sin movimientos</strong>
      <p class="mb-3">No hay pedidos con estos filtros, o aún no has registrado ninguno.</p>
      <a class="btn btn-brand btn-lg-touch" href="movimiento.php">Registrar</a>
    </div>
  <?php else: ?>
    <div class="table-card">
      <?php foreach ($rows as $row): ?>
        <a class="mv-card text-decoration-none text-dark" href="movimiento.php?id=<?= (int) $row['id'] ?>">
          <div class="mv-card-top">
            <strong><?= e($row['client_name']) ?></strong>
            <strong><?= e(qty_es($row['quantity'])) ?> uds</strong>
          </div>
          <div class="list-meta">
            <?= e($row['movement_date']) ?> · <?= e($row['product_name']) ?>
            <span data-price-col> · <?= price_span($row['unit_price']) ?></span>
            <?php if ($row['note']): ?> · <?= e($row['note']) ?><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="panel table-desktop">
      <div class="table-responsive">
        <table class="table table-mobile align-middle mb-0">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Producto</th>
              <th class="text-end">Cant.</th>
              <th class="text-end" data-price-col>P. ud</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= e($row['movement_date']) ?></td>
                <td><a class="client-link" href="<?= e(client_url((int) $row['client_id'])) ?>&amp;back=historial"><?= e($row['client_name']) ?></a></td>
                <td><?= e($row['product_name']) ?></td>
                <td class="text-end"><?= e(qty_es($row['quantity'])) ?></td>
                <td class="text-end" data-price-col><?= price_span($row['unit_price']) ?></td>
                <td class="text-end"><a href="movimiento.php?id=<?= (int) $row['id'] ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
