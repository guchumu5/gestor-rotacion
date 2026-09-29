<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$stats = compute_dashboard_stats($pdo);
$pageTitle = 'Resumen · Gestor de rotación';
$activeNav = 'resumen';

function delta_class(?float $pct): string
{
    if ($pct === null) {
        return 'delta-flat';
    }
    if ($pct > 0.5) {
        return 'delta-up';
    }
    if ($pct < -0.5) {
        return 'delta-down';
    }
    return 'delta-flat';
}

require __DIR__ . '/includes/header.php';
?>
<div class="page">
  <div class="page-head d-flex justify-content-between align-items-start gap-2">
    <div>
      <p class="eyebrow mb-1" style="color:var(--red)">Hoy</p>
      <h1 class="page-title">Resumen</h1>
      <p class="page-sub">Medias, mejores clientes y stock orientativo.</p>
    </div>
    <a class="btn btn-brand btn-lg-touch px-3" href="movimiento.php" style="min-height:48px;padding-top:.65rem;padding-bottom:.65rem">+ Añadir</a>
  </div>

  <?php if ($stats['empty']): ?>
    <div class="panel empty-state">
      <strong>Todavía no hay pedidos</strong>
      <p class="mb-3">Registra el primero y aquí verás medias mensuales, clientes fuertes y mix de producto.</p>
      <a class="btn btn-brand btn-lg-touch" href="movimiento.php">Registrar primer movimiento</a>
    </div>
  <?php else: ?>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-label">Media mensual €</div>
        <div class="stat-value"><?= e(money_es($stats['monthly_avg_amount'])) ?></div>
        <div class="stat-hint"><?= (int) $stats['month_count'] ?> mes(es) con datos</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Media mensual uds</div>
        <div class="stat-value"><?= e(qty_es($stats['monthly_avg_quantity'])) ?></div>
        <div class="stat-hint">Pista de stock</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Media semanal €</div>
        <div class="stat-value"><?= e(money_es($stats['weekly_avg_amount'])) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Media semanal uds</div>
        <div class="stat-value"><?= e(qty_es($stats['weekly_avg_quantity'])) ?></div>
      </div>
      <div class="stat-card wide">
        <div class="stat-label">Stock orientativo al mes</div>
        <div class="stat-value"><?= e(qty_es($stats['stock_hint'])) ?> uds</div>
        <div class="stat-hint">Basado en la media de cantidad por mes. Ajusta con tu margen de seguridad.</div>
      </div>
    </div>

    <div class="panel">
      <h2>Este mes vs anterior</h2>
      <div class="compare-grid mb-2">
        <div class="compare-box">
          <div class="stat-label">Este mes</div>
          <strong><?= e(money_es($stats['month_compare']['this']['amount'])) ?></strong>
          <span class="stat-hint"><?= e(qty_es($stats['month_compare']['this']['quantity'])) ?> uds · <?= (int) $stats['month_compare']['this']['count'] ?> pedidos</span>
        </div>
        <div class="compare-box" style="background:#f4f4f4">
          <div class="stat-label">Mes anterior</div>
          <strong style="color:var(--ink)"><?= e(money_es($stats['month_compare']['prev']['amount'])) ?></strong>
          <span class="stat-hint"><?= e(qty_es($stats['month_compare']['prev']['quantity'])) ?> uds · <?= (int) $stats['month_compare']['prev']['count'] ?> pedidos</span>
        </div>
      </div>
      <p class="mb-0">
        Dinero:
        <span class="<?= e(delta_class($stats['month_amount_delta'])) ?>"><?= e(format_delta($stats['month_amount_delta'])) ?></span>
        · Cantidad:
        <span class="<?= e(delta_class($stats['month_qty_delta'])) ?>"><?= e(format_delta($stats['month_qty_delta'])) ?></span>
      </p>
    </div>

    <div class="panel">
      <h2>Últimos 30 días</h2>
      <div class="stat-grid">
        <div>
          <div class="stat-label">Cobrado</div>
          <div class="stat-value" style="font-size:1.2rem"><?= e(money_es($stats['last30']['amount'])) ?></div>
          <div class="stat-hint <?= e(delta_class($stats['amount_delta_pct'])) ?>">vs 30 anteriores: <?= e(format_delta($stats['amount_delta_pct'])) ?></div>
        </div>
        <div>
          <div class="stat-label">Cantidad</div>
          <div class="stat-value" style="font-size:1.2rem"><?= e(qty_es($stats['last30']['quantity'])) ?></div>
          <div class="stat-hint <?= e(delta_class($stats['quantity_delta_pct'])) ?>">vs 30 anteriores: <?= e(format_delta($stats['quantity_delta_pct'])) ?></div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Mejores clientes · dinero</h2>
      <?php if (!$stats['best_by_amount']): ?>
        <p class="text-muted mb-0">Sin datos todavía.</p>
      <?php else: ?>
        <?php foreach ($stats['best_by_amount'] as $row): ?>
          <div class="list-row">
            <div class="list-main">
              <p class="list-title"><?= e($row['name']) ?></p>
              <p class="list-meta"><?= (int) $row['count'] ?> pedidos</p>
            </div>
            <div class="list-value"><?= e(money_es($row['amount'])) ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h2>Mejores clientes · cantidad</h2>
      <?php foreach ($stats['best_by_quantity'] as $row): ?>
        <div class="list-row">
          <div class="list-main">
            <p class="list-title"><?= e($row['name']) ?></p>
            <p class="list-meta"><?= (int) $row['count'] ?> pedidos</p>
          </div>
          <div class="list-value"><?= e(qty_es($row['quantity'])) ?> uds</div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <h2>Compra típica por cliente</h2>
      <?php foreach ($stats['client_averages'] as $row): ?>
        <div class="list-row">
          <div class="list-main">
            <p class="list-title"><?= e($row['name']) ?></p>
            <p class="list-meta">Ticket medio / cantidad media</p>
          </div>
          <div class="list-value">
            <?= e(money_es($row['avg_amount'])) ?><br>
            <span class="list-meta"><?= e(qty_es($row['avg_quantity'])) ?> uds</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <h2>Mix de producto</h2>
      <?php foreach ($stats['product_mix'] as $row): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between gap-2">
            <strong><?= e($row['name']) ?></strong>
            <span><?= e(qty_es($row['quantity'])) ?> uds · <?= number_format($row['share'] * 100, 0) ?>%</span>
          </div>
          <div class="mix-bar" aria-hidden="true">
            <div class="mix-fill" style="width:<?= max(0, min(100, (int) round($row['share'] * 100))) ?>%"></div>
          </div>
          <div class="stat-hint"><?= e(money_es($row['amount'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="mb-0">Últimos movimientos</h2>
        <a href="historial.php">Ver todos</a>
      </div>
      <?php foreach ($stats['recent'] as $mv): ?>
        <div class="list-row">
          <div class="list-main">
            <p class="list-title"><?= e($mv['client_name']) ?> · <?= e($mv['product_name']) ?></p>
            <p class="list-meta"><?= e($mv['movement_date']) ?> · <?= e(qty_es($mv['quantity'])) ?> uds</p>
          </div>
          <div class="list-value"><?= e(money_es($mv['amount'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
