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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#c41230">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Rotación">
  <title><?= e($pageTitle) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="app-body">
  <div class="app-shell">
    <header class="app-topbar">
      <div class="topbar-brand">
        <span class="brand-mark" aria-hidden="true">R</span>
        <div>
          <p class="eyebrow mb-0">Productor</p>
          <h1 class="topbar-title">Gestor de rotación</h1>
        </div>
      </div>
      <a class="btn btn-outline-light btn-sm logout-btn" href="logout.php">Salir</a>
    </header>

    <main class="app-main">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info')) ?> alert-dismissible fade show" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
      <?php endif; ?>
