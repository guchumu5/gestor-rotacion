<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';

if (request_method() === 'POST') {
    csrf_verify();
    $password = post_string('password');
    if (attempt_login($password, (string) $config['app_password'])) {
        redirect('index.php');
    }
    $error = 'Contraseña incorrecta.';
}
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
  <title>Entrar · Gestor de rotación</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="login-body">
  <div class="login-shell">
    <div class="login-mark" aria-hidden="true">R</div>
    <div>
      <p class="eyebrow text-danger mb-1" style="color:var(--red)!important">Productor</p>
      <h1>Gestor de rotación</h1>
      <p class="lead">Quién compró, cuándo y cuánto — para pedir con cabeza.</p>
    </div>

    <div class="login-card">
      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post" autocomplete="current-password">
        <?= csrf_field() ?>
        <label class="form-label" for="password">Contraseña</label>
        <input
          class="form-control form-control-lg mb-3"
          type="password"
          id="password"
          name="password"
          inputmode="text"
          autofocus
          required
          placeholder="Escribe la contraseña"
        >
        <button class="btn btn-brand btn-lg-touch w-100" type="submit">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
