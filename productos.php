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
                $pdo->prepare('INSERT INTO products (name, active) VALUES (?, 1)')->execute([$name]);
                flash_set('success', 'Producto creado.');
                redirect('productos.php');
            } catch (PDOException $e) {
                $error = 'Ese nombre ya existe.';
            }
        }
    } elseif ($action === 'rename' && $id > 0) {
        if ($name === '') {
            $error = 'El nombre no puede estar vacío.';
        } else {
            try {
                $pdo->prepare('UPDATE products SET name = ? WHERE id = ?')->execute([$name, $id]);
                flash_set('success', 'Producto actualizado.');
                redirect('productos.php');
            } catch (PDOException $e) {
                $error = 'Ese nombre ya existe.';
            }
        }
    } elseif ($action === 'deactivate' && $id > 0) {
        $count = product_movement_count($pdo, $id);
        if ($count > 0) {
            $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([$id]);
            flash_set('success', "Producto archivado (tiene $count movimiento(s); se conserva el historial).");
        } else {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash_set('success', 'Producto eliminado.');
        }
        redirect('productos.php');
    } elseif ($action === 'activate' && $id > 0) {
        $pdo->prepare('UPDATE products SET active = 1 WHERE id = ?')->execute([$id]);
        flash_set('success', 'Producto reactivado.');
        redirect('productos.php');
    }
}

$products = products_all($pdo, false);
$pageTitle = 'Productos · Gestor de rotación';
$activeNav = 'productos';
require __DIR__ . '/includes/header.php';
?>
<div class="page">
  <div class="page-head">
    <p class="eyebrow mb-1" style="color:var(--red)">Catálogo</p>
    <h1 class="page-title">Productos</h1>
    <p class="page-sub">Rojo, Ches, Win… renombra o añade los tuyos.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="panel form-panel">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label class="form-label" for="name">Nuevo producto</label>
    <div class="d-flex gap-2">
      <input class="form-control" type="text" name="name" id="name" placeholder="Nombre" required autocomplete="off">
      <button class="btn btn-brand" type="submit" style="min-width:96px;border-radius:14px;font-weight:700">Crear</button>
    </div>
  </form>

  <?php if (!$products): ?>
    <div class="panel empty-state">
      <strong>Sin productos</strong>
      <p class="mb-0">Importa el schema o crea el primero aquí.</p>
    </div>
  <?php else: ?>
    <?php foreach ($products as $product): ?>
      <?php $count = product_movement_count($pdo, (int) $product['id']); ?>
      <div class="panel">
        <form method="post" class="mb-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
          <label class="form-label">Nombre <?= !(int) $product['active'] ? '<span class="badge text-bg-secondary">Archivado</span>' : '' ?></label>
          <div class="d-flex gap-2">
            <input class="form-control" type="text" name="name" value="<?= e($product['name']) ?>" required>
            <button class="btn btn-outline-brand" type="submit" style="border-radius:14px;font-weight:700">Guardar</button>
          </div>
          <div class="form-text mt-1"><?= $count ?> movimiento(s)</div>
        </form>
        <div class="action-row">
          <?php if ((int) $product['active']): ?>
            <form method="post" onsubmit="return confirm(<?= $count > 0 ? "'Este producto tiene historial. Se archivará y no se borrarán los pedidos. ¿Continuar?'" : "'¿Eliminar este producto?'" ?>);">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="deactivate">
              <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
              <button class="btn btn-outline-danger btn-sm" type="submit"><?= $count > 0 ? 'Archivar' : 'Eliminar' ?></button>
            </form>
          <?php else: ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="activate">
              <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
              <button class="btn btn-outline-brand btn-sm" type="submit">Reactivar</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
