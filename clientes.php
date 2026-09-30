<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$error = '';

if (request_method() === 'POST') {
    csrf_verify();
    $action = post_string('action');
    $id = (int) ($_POST['id'] ?? 0);
    $name = post_string('name');

    if ($action === 'create') {
        if ($name === '') {
            $error = 'El nombre no puede estar vacío.';
        } else {
            try {
                $pdo->prepare('INSERT INTO clients (name, active) VALUES (?, 1)')->execute([$name]);
                flash_set('success', 'Cliente creado.');
                redirect('clientes.php');
            } catch (PDOException $e) {
                $error = 'Ese nombre ya existe.';
            }
        }
    } elseif ($action === 'rename' && $id > 0) {
        if ($name === '') {
            $error = 'El nombre no puede estar vacío.';
        } else {
            try {
                $pdo->prepare('UPDATE clients SET name = ? WHERE id = ?')->execute([$name, $id]);
                flash_set('success', 'Cliente renombrado.');
                redirect('clientes.php');
            } catch (PDOException $e) {
                $error = 'Ese nombre ya existe.';
            }
        }
    } elseif ($action === 'deactivate' && $id > 0) {
        $count = client_movement_count($pdo, $id);
        if ($count > 0) {
            $pdo->prepare('UPDATE clients SET active = 0 WHERE id = ?')->execute([$id]);
            flash_set('success', "Cliente archivado (tiene $count movimiento(s); no se borra el historial).");
        } else {
            $pdo->prepare('DELETE FROM clients WHERE id = ?')->execute([$id]);
            flash_set('success', 'Cliente eliminado.');
        }
        redirect('clientes.php');
    } elseif ($action === 'activate' && $id > 0) {
        $pdo->prepare('UPDATE clients SET active = 1 WHERE id = ?')->execute([$id]);
        flash_set('success', 'Cliente reactivado.');
        redirect('clientes.php');
    }
}

$clients = clients_all($pdo, false);
$pageTitle = 'Clientes · Gestor de rotación';
$activeNav = 'clientes';
require __DIR__ . '/includes/header.php';
?>
<div class="page">
  <div class="page-head">
    <p class="eyebrow mb-1" style="color:var(--red)">Fichas</p>
    <h1 class="page-title">Clientes</h1>
    <p class="page-sub">Solo nombre. Si tienen historial, se archivan en lugar de borrar.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="panel form-panel">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label class="form-label" for="name">Nuevo cliente</label>
    <div class="d-flex gap-2">
      <input class="form-control" type="text" name="name" id="name" placeholder="Nombre" required autocomplete="off">
      <button class="btn btn-brand" type="submit" style="min-width:96px;border-radius:14px;font-weight:700">Crear</button>
    </div>
  </form>

  <?php if (!$clients): ?>
    <div class="panel empty-state">
      <strong>Sin clientes</strong>
      <p class="mb-0">Crea el primero o regístralo al añadir un pedido.</p>
    </div>
  <?php else: ?>
    <?php foreach ($clients as $client): ?>
      <?php $count = client_movement_count($pdo, (int) $client['id']); ?>
      <div class="panel">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div>
            <a class="client-link client-link-lg" href="<?= e(client_url((int) $client['id'])) ?>&amp;back=clientes"><?= e($client['name']) ?></a>
            <?= !(int) $client['active'] ? '<span class="badge text-bg-secondary ms-1">Archivado</span>' : '' ?>
            <div class="form-text mt-1"><?= $count ?> movimiento(s) · toca el nombre para ver historial y huecos</div>
          </div>
          <a class="btn btn-outline-brand btn-sm" href="<?= e(client_url((int) $client['id'])) ?>&amp;back=clientes" style="border-radius:12px;font-weight:700;white-space:nowrap">Ver</a>
        </div>
        <form method="post" class="mb-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
          <label class="form-label">Renombrar</label>
          <div class="d-flex gap-2">
            <input class="form-control" type="text" name="name" value="<?= e($client['name']) ?>" required>
            <button class="btn btn-outline-brand" type="submit" style="border-radius:14px;font-weight:700">Guardar</button>
          </div>
        </form>
        <div class="action-row">
          <?php if ((int) $client['active']): ?>
            <form method="post" onsubmit="return confirm(<?= $count > 0 ? "'Este cliente tiene historial. Se archivará (soft-delete) y no se borrarán los pedidos. ¿Continuar?'" : "'¿Eliminar este cliente?'" ?>);">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="deactivate">
              <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
              <button class="btn btn-outline-danger btn-sm" type="submit"><?= $count > 0 ? 'Archivar' : 'Eliminar' ?></button>
            </form>
          <?php else: ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="activate">
              <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
              <button class="btn btn-outline-brand btn-sm" type="submit">Reactivar</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
