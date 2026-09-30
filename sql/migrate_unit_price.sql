-- Migración: de "importe total" (amount) a "precio unitario" opcional (price).
--
-- La app también aplica esto sola al conectar (includes/db.php → db_migrate_unit_price).
-- Este fichero sirve para migrar a mano o documentar el mismo cambio.
--
-- Supuesto: no se convierte el antiguo importe TOTAL a precio unitario;
-- a partir de ahora el valor almacenado se interpreta como precio unitario.
-- Vacío → NULL.
--
-- Uso típico (si no confías en la auto-migración):
--   mysql -u USUARIO -p NOMBRE_BD < sql/migrate_unit_price.sql

-- Si la columna aún se llama amount (instalación previa):
ALTER TABLE movements
  CHANGE COLUMN amount price DECIMAL(12, 2) NULL
  COMMENT 'Precio unitario (€); NULL si no se indica. Total línea = quantity * price';

-- Si ya se llama price y solo falta permitir NULL, ejecuta en su lugar:
-- ALTER TABLE movements
--   MODIFY COLUMN price DECIMAL(12, 2) NULL
--   COMMENT 'Precio unitario (€); NULL si no se indica. Total línea = quantity * price';
