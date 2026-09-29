# Gestor de rotación

App PHP clásica (sin React/Vite/Node) para que el productor registre **quién compró, cuándo y cuánto**, y vea medias mensuales, mejores clientes y mix de producto.

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

## Uso rápido (móvil)

- **Añadir**: botón grande «Registrar» — elige cliente (o crea uno), producto, cantidad, importe total cobrado y fecha.
- **Resumen**: medias €/uds por mes y semana, comparación mes actual vs anterior, mejores clientes, compra típica, mix Rojo/Ches/Win, últimos movimientos.
- **Historial**: filtros por cliente, producto y rango de fechas; toca un pedido para editarlo.
- **Clientes / Productos**: renombrar; si tienen historial se **archivan** (soft-delete) para no perder datos.

## Notas iPhone

- Meta `viewport` y `apple-mobile-web-app-capable` para uso a pantalla casi completa.
- Navegación inferior fija, inputs grandes, botones de pulgar.
- Color corporativo rojo `#c41230` sobre blanco.

## Estructura

```
config.example.php   # plantilla de credenciales
schema.sql           # tablas + seeds
login.php            # acceso
index.php            # dashboard
movimiento.php       # alta / edición rápida
clientes.php
productos.php
historial.php
logout.php
includes/            # sesión, PDO, CSRF, stats, layout
assets/css/app.css
assets/js/app.js
```

## Seguridad básica

- Contraseña compartida (no hay cuentas de usuario).
- Sesión PHP con regeneración de ID al login.
- Tokens CSRF en formularios POST.
- Consultas PDO preparadas.
