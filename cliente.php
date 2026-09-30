<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$clientId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($clientId <= 0) {
    flash_set('error', 'Cliente no indicado.');
    redirect('clientes.php');
}

$stmt = $pdo->prepare('SELECT id, name, active FROM clients WHERE id = ?');
$stmt->execute([$clientId]);
$client = $stmt->fetch();
if (!$client) {
    flash_set('error', 'Cliente no encontrado.');
    redirect('clientes.php');
}

$history = compute_client_history($pdo, $clientId);
$back = get_string('back');
$backUrl = 'clientes.php';
if ($back === 'resumen') {
    $backUrl = 'index.php';
} elseif ($back === 'historial') {
    $backUrl = 'historial.php';
}

$pageTitle = $client['name'] . ' · Gestor de rotación';
$activeNav = 'clientes';
require __DIR__ . '/includes/header.php';
?>
<div class="page">
  <div class="page-head">
    <a class="back-link" href="<?= e($backUrl) ?>">← Volver</a>
    <p class="eyebrow mb-1" style="color:var(--red)">Cliente</p>
    <h1 class="page-title"><?= e($client['name']) ?></h1>
    <p class="page-sub">
      Historial de suministros
      <?= !(int) $client['active'] ? ' · <span class="badge text-bg-secondary">Archivado</span>' : '' ?>
    </p>
  </div>

  <?php if ($history['empty']): ?>
    <div class="panel empty-state">
      <strong>Sin pedidos todavía</strong>
      <p class="mb-3">Este cliente aún no tiene movimientos registrados.</p>
      <a class="btn btn-brand btn-lg-touch" href="movimiento.php">Registrar pedido</a>
    </div>
  <?php else: ?>
    <div class="panel">
      <div class="stat-grid">
        <div>
          <div class="stat-label">Pedidos</div>
          <div class="stat-value" style="font-size:1.25rem"><?= (int) $history['count'] ?></div>
        </div>
        <div>
          <div class="stat-label">Hueco medio</div>
          <div class="stat-value" style="font-size:1.25rem">
            <?php if ($history['avg_gap_days'] === null): ?>
              —
            <?php else: ?>
              <?= e(days_label((int) round($history['avg_gap_days']))) ?>
            <?php endif; ?>
          </div>
          <div class="stat-hint">
            <?php if ($history['count'] < 2): ?>
              Hace falta al menos 2 pedidos
            <?php else: ?>
              Entre pedidos (días)
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="client-history-cards">
      <?php foreach ($history['movements'] as $row): ?>
        <a class="mv-card text-decoration-none text-dark" href="movimiento.php?id=<?= (int) $row['id'] ?>">
          <div class="mv-card-top">
            <strong><?= e($row['product_name']) ?></strong>
            <strong><?= e(money_or_dash($row['line_total'])) ?></strong>
          </div>
          <div class="list-meta">
            <?= e($row['movement_date']) ?> · <?= e(qty_es($row['quantity'])) ?> uds
            · ud <?= e(unit_price_es($row['unit_price'])) ?>
            <?php if ($row['note']): ?> · <?= e($row['note']) ?><?php endif; ?>
          </div>
          <div class="gap-chip">
            <?php if ($row['gap_days'] === null): ?>
              Primer pedido · sin hueco previo
            <?php else: ?>
              <?= e(days_label((int) $row['gap_days'])) ?> desde el anterior
            <?php endif; ?>
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
              <th>Producto</th>
              <th class="text-end">Cant.</th>
              <th class="text-end">P. ud</th>
              <th class="text-end">Total</th>
              <th>Hueco</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history['movements'] as $row): ?>
              <tr>
                <td><?= e($row['movement_date']) ?></td>
                <td><?= e($row['product_name']) ?></td>
                <td class="text-end"><?= e(qty_es($row['quantity'])) ?></td>
                <td class="text-end"><?= e(unit_price_es($row['unit_price'])) ?></td>
                <td class="text-end"><?= e(money_or_dash($row['line_total'])) ?></td>
                <td>
                  <?php if ($row['gap_days'] === null): ?>
                    <span class="text-muted">Primer pedido</span>
                  <?php else: ?>
                    <?= e(days_label((int) $row['gap_days'])) ?>
                  <?php endif; ?>
                </td>
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
