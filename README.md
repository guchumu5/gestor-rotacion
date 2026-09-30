# Gestor de rotación

App PHP clásica (sin React/Vite/Node) para que el productor registre **quién compró, cuándo, cuántas unidades y (opcional) precio unidad**, y vea medias **por producto**, mejores clientes e historial con huecos entre pedidos.

Contraseña de acceso: **`hola`** (sesión PHP en servidor).

## Requisitos

- PHP 8.0 o superior (extensiones: `pdo_mysql`, `session`, `mbstring`)
- MySQL 5.7+ / 8.x o MariaDB
- Navegador moderno (pensado para iPhone ~390px)

## Instalación

1. Crea la base de datos e importa el esquema:

```bash
mysql -u root -p < schema.sql
```

O desde un cliente MySQL: ejecuta el contenido de `schema.sql` (crea `gestor_rotacion`, tablas y seeds: clientes SanPablo / Mecanico / Fr y productos Rojo / Ches / Win).

2. Copia la configuración:

```bash
cp config.example.php config.php
```

Edita `config.php` con host, nombre de BD, usuario y contraseña MySQL. **No subas `config.php` a git** (está en `.gitignore`).

3. Arranca el servidor embebido de PHP desde la carpeta del proyecto:

```bash
php -S localhost:8080
```

Abre http://localhost:8080/login.php e introduce `hola`.

### Apache / hosting

Apunta el DocumentRoot a esta carpeta. No hace falta rewrite especial: las páginas son `.php` directas.

### Migración (BD ya existente)

Si la app ya estaba instalada con la columna `amount` (importe total), ejecuta **una vez**:

```bash
mysql -u USUARIO -p NOMBRE_BD < sql/migrate_unit_price.sql
```

Eso renombra `amount` → `price` (precio unitario) y permite `NULL`.  
**Supuesto:** la BD está vacía o es de prueba; no se convierte el antiguo total a precio unitario. A partir de ahora el valor se interpreta como precio por unidad.

## Uso rápido (móvil)

- **Añadir**: cliente, producto, cantidad (obligatoria), **precio unidad opcional**, nota, fecha (hoy por defecto). Vacío = sin precio (`NULL`), no se guarda como 0 €.
- **Resumen**: bloques **por producto** (uds este mes, media mensual/semanal, stock orientativo). Dinero solo cuando hay precios. Clientes tocables.
- **Cliente**: toca un nombre → historial con fecha, producto, uds, precio unidad / total, y **días desde el pedido anterior** (+ media de huecos).
- **Historial**: filtros; muestra precio unidad (o —) y total si hay precio.
- **Clientes / Productos**: renombrar; si tienen historial se **archivan** (soft-delete).

## Notas iPhone

- Meta `viewport` con `viewport-fit=cover`, `apple-mobile-web-app-capable` y `manifest.webmanifest` (`display: standalone`) para usarla como app desde el icono de inicio (sin barra de Safari).
- Añadir a inicio: Safari → Compartir → **Añadir a pantalla de inicio**. Abrir **desde el icono**, no desde una pestaña de Safari.
- La hora/batería del sistema y la barra del home indicator de iOS siguen visibles (chrome del SO); solo desaparecen las barras de Safari.
- Safe areas (`env(safe-area-inset-*)`) en cabecera y navegación inferior.
- Color corporativo rojo `#c41230` sobre blanco.

## Estructura

```
config.example.php   # plantilla de credenciales
schema.sql           # tablas + seeds (price = unitario, NULL ok)
sql/migrate_unit_price.sql
manifest.webmanifest # PWA standalone (icono de inicio)
assets/icons/        # iconos PNG/SVG
login.php
index.php            # dashboard por producto
movimiento.php       # alta / edición rápida
cliente.php          # historial de un cliente + huecos
clientes.php
productos.php
historial.php
logout.php
includes/
assets/css/app.css
assets/js/app.js
```

## Seguridad básica

- Contraseña compartida (no hay cuentas de usuario).
- Sesión PHP con regeneración de ID al login.
- Tokens CSRF en formularios POST.
- Consultas PDO preparadas.
