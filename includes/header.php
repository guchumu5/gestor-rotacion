<?php
declare(strict_types=1);

/** @var string $pageTitle */
/** @var string $activeNav */
$pageTitle = $pageTitle ?? 'Gestor de rotación';
$activeNav = $activeNav ?? '';
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require __DIR__ . '/head_meta.php'; ?>
  <script>
    (function () {
      try {
        if (localStorage.getItem('gr_show_prices') === '1') {
          document.documentElement.classList.add('prices-visible');
        }
      } catch (e) {}
    })();
  </script>
</head>
<body class="app-body">
  <script>
    (function () {
      var show = false;
      try { show = localStorage.getItem('gr_show_prices') === '1'; } catch (e) {}
      document.body.classList.toggle('prices-visible', show);
      document.documentElement.classList.toggle('prices-visible', show);
    })();
  </script>
  <div class="app-shell">
    <header class="app-topbar">
      <div class="topbar-brand">
        <span class="brand-mark" aria-hidden="true">R</span>
        <div>
          <p class="eyebrow mb-0">Productor</p>
          <h1 class="topbar-title">Gestor de rotación</h1>
        </div>
      </div>
      <div class="topbar-actions">
        <button
          type="button"
          class="btn btn-outline-light btn-sm price-toggle-btn"
          id="price-toggle"
          aria-pressed="false"
          aria-label="Mostrar precios"
          title="Mostrar precios"
        >
          <svg class="price-icon price-icon-slash" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
          </svg>
          <svg class="price-icon price-icon-open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
        <a class="btn btn-outline-light btn-sm logout-btn" href="logout.php">Salir</a>
      </div>
    </header>

    <main class="app-main">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php endif; ?>
