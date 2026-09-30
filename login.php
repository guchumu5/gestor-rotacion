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
    $ok = attempt_login($password, (string) $config['app_password']);

    $wantsJson = post_string('ajax') === '1'
        || (
            isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        if ($ok) {
            echo json_encode(['ok' => true, 'redirect' => 'index.php'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Contraseña incorrecta.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($ok) {
        redirect('index.php');
    }
    $error = 'Contraseña incorrecta.';
}

$pageTitle = 'Entrar · Gestor de rotación';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php require __DIR__ . '/includes/head_meta.php'; ?>
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
      <div class="alert alert-danger py-2" id="login-error"<?= $error === '' ? ' hidden' : '' ?>><?= e($error !== '' ? $error : '') ?></div>
      <form method="post" id="login-form" autocomplete="current-password">
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
  <script src="assets/js/app.js"></script>
</body>
</html>
