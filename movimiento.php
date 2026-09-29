<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$movement = null;

if ($editId > 0) {
    $stmt = $pdo->prepare(
        'SELECT id, client_id, product_id, quantity, amount, note, movement_date
         FROM movements WHERE id = ?'
    );
    $stmt->execute([$editId]);
    $movement = $stmt->fetch();
    if (!$movement) {
        flash_set('error', 'Movimiento no encontrado.');
        redirect('historial.php');
    }
}

$clients = clients_all($pdo, true);
$products = products_all($pdo, true);
$error = '';

if (request_method() === 'POST') {
    csrf_verify();
    $action = post_string('action', 'save');

    if ($action === 'delete' && $editId > 0) {
        $pdo->prepare('DELETE FROM movements WHERE id = ?')->execute([$editId]);
        flash_set('success', 'Movimiento eliminado.');
        redirect('historial.php');
    }

    $clientMode = post_string('client_mode', 'existing');
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $newClientName = post_string('new_client_name');
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity = parse_decimal(post_string('quantity'));
    $amount = parse_decimal(post_string('amount'));
    $note = post_string('note');
    $date = post_string('movement_date', today_iso());

    if ($clientMode === 'new') {
        if ($newClientName === '') {
            $error = 'Pon un nombre al cliente nuevo.';
        } else {
            $clientId = find_or_create_client($pdo, $newClientName);
        }
    } elseif ($clientId <= 0) {
        $error = 'Elige o crea un cliente.';
    }

    if ($error === '' && $productId <= 0) {
        $error = 'Elige un producto.';
    }
    if ($error === '' && ($quantity === null || $quantity <= 0)) {
        $error = 'La cantidad tiene que ser mayor que 0.';
    }
    if ($error === '' && ($amount === null || $amount < 0)) {
        $error = 'Indica el importe total cobrado.';
    }
    if ($error === '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = 'La fecha no es válida.';
    }

    if ($error === '') {
        $noteVal = $note === '' ? null : (function_exists('mb_substr') ? mb_substr($note, 0, 500) : substr($note, 0, 500));
        if ($editId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE movements
                 SET client_id = ?, product_id = ?, quantity = ?, amount = ?, note = ?, movement_date = ?
                 WHERE id = ?'
            );
            $stmt->execute([$clientId, $productId, $quantity, $amount, $noteVal, $date, $editId]);
            flash_set('success', 'Movimiento actualizado.');
            redirect('historial.php');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO movements (client_id, product_id, quantity, amount, note, movement_date)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$clientId, $productId, $quantity, $amount, $noteVal, $date]);
        flash_set('success', 'Movimiento registrado.');
        redirect('index.php');
    }

    // Repoblar formulario tras error
    $movement = [
        'client_id' => $clientId,
        'product_id' => $productId,
        'quantity' => post_string('quantity', '1'),
        'amount' => post_string('amount'),
        'note' => $note,
        'movement_date' => $date,
    ];
    $clients = clients_all($pdo, true);
}

$isEdit = $editId > 0 && $movement;
$pageTitle = ($isEdit ? 'Editar' : 'Registrar') . ' · Gestor de rotación';
$activeNav = 'registrar';

$selectedClient = (int) ($movement['client_id'] ?? ($clients[0]['id'] ?? 0));
$selectedProduct = (int) ($movement['product_id'] ?? ($products[0]['id'] ?? 0));
$qtyValue = isset($movement['quantity']) ? (string) $movement['quantity'] : '1';
$amountValue = isset($movement['amount']) ? (string) $movement['amount'] : '';
$noteValue = (string) ($movement['note'] ?? '');
$dateValue = (string) ($movement['movement_date'] ?? today_iso());
$showNote = $noteValue !== '';
$clientMode = post_string('client_mode', 'existing') ?: 'existing';
if ($error === '' && request_method() !== 'POST') {
    $clientMode = 'existing';
}

require __DIR__ . '/includes/header.php';
?>
<div class="page">
  <div class="page-head">
    <p class="eyebrow mb-1" style="color:var(--red)"><?= $isEdit ? 'Editar' : 'Rápido' ?></p>
    <h1 class="page-title"><?= $isEdit ? 'Editar movimiento' : 'Añadir pedido' ?></h1>
    <p class="page-sub">Pocos toques. Pensado para el iPhone.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$clients && $clientMode !== 'new'): ?>
    <div class="alert alert-warning">No hay clientes activos. Crea uno abajo.</div>
  <?php endif; ?>
  <?php if (!$products): ?>
    <div class="alert alert-warning">No hay productos. Añádelos en <a href="productos.php">Productos</a>.</div>
  <?php endif; ?>

  <form method="post" class="panel form-panel" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="client_mode" id="client_mode" value="<?= e($clientMode) ?>">

    <div class="mb-3">
      <label class="form-label">Cliente</label>
      <div class="client-mode">
        <button type="button" class="btn <?= $clientMode === 'existing' ? 'btn-brand' : 'btn-outline-brand' ?>" id="mode-existing">Existente</button>
        <button type="button" class="btn <?= $clientMode === 'new' ? 'btn-brand' : 'btn-outline-brand' ?>" id="mode-new">Nuevo</button>
      </div>
      <div id="client-existing-block" <?= $clientMode === 'new' ? 'hidden' : '' ?>>
        <select class="form-select" name="client_id" id="client_id">
          <?php foreach ($clients as $client): ?>
            <option value="<?= (int) $client['id'] ?>" <?= $selectedClient === (int) $client['id'] ? 'selected' : '' ?>>
              <?= e($client['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="client-new-block" <?= $clientMode === 'new' ? '' : 'hidden' ?>>
        <input
          class="form-control"
          type="text"
          name="new_client_name"
          id="new_client_name"
          placeholder="Nombre del cliente"
          value="<?= e(post_string('new_client_name')) ?>"
          autocomplete="off"
        >
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="product_id">Producto</label>
      <div class="chip-row">
        <?php foreach ($products as $product): ?>
          <button
            type="button"
            class="chip<?= $selectedProduct === (int) $product['id'] ? ' is-active' : '' ?>"
            data-product-chip="<?= (int) $product['id'] ?>"
          ><?= e($product['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <select class="form-select" name="product_id" id="product_id" required>
        <?php foreach ($products as $product): ?>
          <option value="<?= (int) $product['id'] ?>" <?= $selectedProduct === (int) $product['id'] ? 'selected' : '' ?>>
            <?= e($product['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label" for="quantity">Cantidad</label>
      <div class="qty-stepper">
        <button type="button" class="btn" data-qty-step="-1" data-qty-target="quantity" aria-label="Menos">−</button>
        <input class="form-control text-center" type="text" inputmode="decimal" name="quantity" id="quantity" value="<?= e($qtyValue) ?>" required>
        <button type="button" class="btn" data-qty-step="1" data-qty-target="quantity" aria-label="Más">+</button>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="amount">Importe total cobrado (€)</label>
      <input
        class="form-control"
        type="text"
        inputmode="decimal"
        name="amount"
        id="amount"
        value="<?= e($amountValue) ?>"
        placeholder="Ej. 120"
        required
      >
      <div class="form-text">Precio = total pagado. La unidad se deduce con cantidad.</div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="movement_date">Fecha</label>
      <input class="form-control" type="date" name="movement_date" id="movement_date" value="<?= e($dateValue) ?>" required>
    </div>

    <div class="mb-3">
      <button type="button" class="btn btn-outline-secondary w-100" id="toggle-note" style="min-height:44px;border-radius:12px">
        <?= $showNote ? 'Ocultar nota' : 'Añadir nota (opcional)' ?>
      </button>
      <div id="note-block" class="mt-2" <?= $showNote ? '' : 'hidden' ?>>
        <textarea class="form-control" name="note" id="note" maxlength="500" placeholder="Nota breve"><?= e($noteValue) ?></textarea>
      </div>
    </div>

    <div class="cta-sticky">
      <button class="btn btn-brand btn-lg-touch w-100" type="submit">
        <?= $isEdit ? 'Guardar cambios' : 'Registrar' ?>
      </button>
    </div>
  </form>

  <?php if ($isEdit): ?>
    <form method="post" onsubmit="return confirm('¿Eliminar este movimiento?');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <button class="btn btn-outline-danger w-100" type="submit" style="min-height:48px;border-radius:14px">Eliminar movimiento</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
