-- Migración: de "importe total" (amount) a "precio unitario" opcional (price).
-- Ejecutar UNA vez en la base de datos de producción ya conectada.
--
-- Supuesto: la BD está vacía o con datos de prueba. No se convierte el
-- antiguo importe TOTAL a precio unitario; a partir de ahora el valor
-- almacenado se interpreta como precio unitario. Vacío → NULL.
--
-- Uso típico:
--   mysql -u USUARIO -p NOMBRE_BD < sql/migrate_unit_price.sql

-- Si la columna aún se llama amount (instalación previa):
ALTER TABLE movements
  CHANGE COLUMN amount price DECIMAL(12, 2) NULL
  COMMENT 'Precio unitario (€); NULL si no se indica. Total línea = quantity * price';

-- Si ya se llama price y solo falta permitir NULL (por si el CHANGE anterior
-- no aplica porque ya renombraste a mano), descomenta:
-- ALTER TABLE movements
--   MODIFY COLUMN price DECIMAL(12, 2) NULL
--   COMMENT 'Precio unitario (€); NULL si no se indica. Total línea = quantity * price';
