-- Gestor de rotación — esquema y datos iniciales
-- Compatible con MySQL 5.7+ / 8.x / MariaDB
--
-- movements.price = precio UNITARIO (€). NULL si no se indica.
-- Total de línea = quantity * price (solo cuando price no es NULL).
-- Instalaciones ya existentes: la app migra sola al conectar (amount→price);
-- también puedes ejecutar sql/migrate_unit_price.sql a mano.

CREATE DATABASE IF NOT EXISTS gestor_rotacion
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestor_rotacion;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clients_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(12, 2) NOT NULL,
  price DECIMAL(12, 2) NULL COMMENT 'Precio unitario (€); NULL si no se indica. Total línea = quantity * price',
  note VARCHAR(500) NULL,
  movement_date DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_movements_date (movement_date),
  KEY idx_movements_client (client_id),
  KEY idx_movements_product (product_id),
  CONSTRAINT fk_movements_client
    FOREIGN KEY (client_id) REFERENCES clients (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_movements_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO clients (name, active) VALUES
  ('SanPablo', 1),
  ('Mecanico', 1),
  ('Fr', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO products (name, active) VALUES
  ('Rojo', 1),
  ('Ches', 1),
  ('Win', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
