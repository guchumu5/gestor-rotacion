<?php
declare(strict_types=1);
/** @var string $activeNav */
$activeNav = $activeNav ?? '';
$navItems = [
    'resumen' => ['href' => 'index.php', 'label' => 'Resumen', 'icon' => '◉'],
    'registrar' => ['href' => 'movimiento.php', 'label' => 'Añadir', 'icon' => '+'],
    'historial' => ['href' => 'historial.php', 'label' => 'Historial', 'icon' => '☰'],
    'clientes' => ['href' => 'clientes.php', 'label' => 'Clientes', 'icon' => '☺'],
    'productos' => ['href' => 'productos.php', 'label' => 'Productos', 'icon' => '◆'],
];
?>
    </main>

    <nav class="bottom-nav" aria-label="Navegación principal">
      <?php foreach ($navItems as $key => $item): ?>
        <a class="nav-item<?= $activeNav === $key ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>">
          <span class="nav-icon" aria-hidden="true"><?= e($item['icon']) ?></span>
          <span class="nav-label"><?= e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
