<?php
declare(strict_types=1);
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Gestor de rotación';
?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#c41230">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Rotación">
  <meta name="application-name" content="Gestor de rotación">
  <title><?= e($pageTitle) ?></title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
  <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg">
  <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
