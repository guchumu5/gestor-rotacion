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
      <p class="page-sub">Unidades por producto, stock orientativo y mejores clientes.</p>
    </div>
    <a class="btn btn-brand btn-lg-touch px-3" href="movimiento.php" style="min-height:48px;padding-top:.65rem;padding-bottom:.65rem">+ Añadir</a>
  </div>

  <?php if ($stats['empty']): ?>
    <div class="panel empty-state">
      <strong>Todavía no hay pedidos</strong>
      <p class="mb-3">Registra el primero y aquí verás medias por producto, clientes fuertes y stock orientativo.</p>
      <a class="btn btn-brand btn-lg-touch" href="movimiento.php">Registrar primer movimiento</a>
    </div>
  <?php else: ?>

    <div class="panel">
      <h2>Por producto</h2>
      <p class="page-sub mb-3">Solo unidades: medias, stock orientativo y mezcla.</p>
      <?php foreach ($stats['product_stats'] as $prod): ?>
        <div class="product-block">
          <div class="product-block-head">
            <strong><?= e($prod['name']) ?></strong>
            <span class="stat-hint"><?= number_format($prod['share'] * 100, 0) ?>% del total uds</span>
          </div>
          <div class="product-stats-grid">
            <div>
              <div class="stat-label">Este mes</div>
              <div class="stat-value sm"><?= e(qty_es($prod['this_month_qty'])) ?> uds</div>
            </div>
            <div>
              <div class="stat-label">Media mes</div>
              <div class="stat-value sm"><?= e(qty_es($prod['monthly_avg_quantity'])) ?> uds</div>
            </div>
            <div>
              <div class="stat-label">Media sem.</div>
              <div class="stat-value sm"><?= e(qty_es($prod['weekly_avg_quantity'])) ?> uds</div>
            </div>
            <div>
              <div class="stat-label">Stock orientativo</div>
              <div class="stat-value sm"><?= e(qty_es($prod['stock_hint'])) ?> uds</div>
              <div class="stat-hint">Media mensual de unidades</div>
            </div>
          </div>
          <div class="mix-bar" aria-hidden="true">
            <div class="mix-fill" style="width:<?= max(0, min(100, (int) round($prod['share'] * 100))) ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <h2>Este mes vs anterior <span class="stat-hint">(unidades)</span></h2>
      <div class="compare-grid mb-2">
        <div class="compare-box">
          <div class="stat-label">Este mes</div>
          <strong><?= e(qty_es($stats['month_compare']['this']['quantity'])) ?> uds</strong>
          <span class="stat-hint"><?= (int) $stats['month_compare']['this']['count'] ?> pedidos</span>
        </div>
        <div class="compare-box" style="background:#f4f4f4">
          <div class="stat-label">Mes anterior</div>
          <strong style="color:var(--ink)"><?= e(qty_es($stats['month_compare']['prev']['quantity'])) ?> uds</strong>
          <span class="stat-hint"><?= (int) $stats['month_compare']['prev']['count'] ?> pedidos</span>
        </div>
      </div>
      <p class="mb-0">
        Variación unidades:
        <span class="<?= e(delta_class($stats['month_qty_delta'])) ?>"><?= e(format_delta($stats['month_qty_delta'])) ?></span>
      </p>
    </div>

    <div class="panel">
      <h2>Últimos 30 días</h2>
      <div class="stat-grid">
        <div>
          <div class="stat-label">Unidades</div>
          <div class="stat-value" style="font-size:1.2rem"><?= e(qty_es($stats['last30']['quantity'])) ?></div>
          <div class="stat-hint <?= e(delta_class($stats['quantity_delta_pct'])) ?>">vs 30 anteriores: <?= e(format_delta($stats['quantity_delta_pct'])) ?></div>
        </div>
        <div>
          <div class="stat-label">Pedidos</div>
          <div class="stat-value" style="font-size:1.2rem"><?= (int) $stats['last30']['count'] ?></div>
          <div class="stat-hint">En los últimos 30 días</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Mejores clientes · unidades</h2>
      <?php foreach ($stats['best_by_quantity'] as $row): ?>
        <a class="list-row list-row-link" href="<?= e(client_url((int) $row['id'])) ?>&amp;back=resumen">
          <div class="list-main">
            <p class="list-title"><?= e($row['name']) ?></p>
            <p class="list-meta">
              <?= (int) $row['count'] ?> pedidos
              <?php if ($row['by_product']): ?>
                ·
                <?php
                  $bits = [];
                  foreach ($row['by_product'] as $bp) {
                      $bits[] = $bp['name'] . ' ' . qty_es($bp['quantity']);
                  }
                  echo e(implode(' · ', $bits));
                ?>
              <?php endif; ?>
            </p>
          </div>
          <div class="list-value"><?= e(qty_es($row['quantity'])) ?> uds</div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <h2>Compra típica por cliente</h2>
      <?php foreach ($stats['client_averages'] as $row): ?>
        <a class="list-row list-row-link" href="<?= e(client_url((int) $row['id'])) ?>&amp;back=resumen">
          <div class="list-main">
            <p class="list-title"><?= e($row['name']) ?></p>
            <p class="list-meta">Cantidad media por pedido</p>
          </div>
          <div class="list-value"><?= e(qty_es($row['avg_quantity'])) ?> uds</div>
        </a>
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
            <p class="list-title">
              <a class="client-link" href="<?= e(client_url((int) $mv['client_id'])) ?>&amp;back=resumen"><?= e($mv['client_name']) ?></a>
              · <?= e($mv['product_name']) ?>
            </p>
            <p class="list-meta">
              <?= e($mv['movement_date']) ?>
              <span data-price-col> · <?= price_span($mv['unit_price']) ?></span>
            </p>
          </div>
          <div class="list-value"><?= e(qty_es($mv['quantity'])) ?> uds</div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
