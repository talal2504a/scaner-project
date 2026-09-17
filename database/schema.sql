-- ============================================================
-- Stock In/Out System — Database Schema
-- Run this in phpMyAdmin / MySQL to create tables.
-- ============================================================

CREATE DATABASE IF NOT EXISTS stock_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stock_system;

-- ---------- PRODUCTS (master list) ----------
CREATE TABLE IF NOT EXISTS products (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  serial_code   VARCHAR(100) NOT NULL,
  item_name     VARCHAR(255) DEFAULT '',
  category      VARCHAR(150) DEFAULT '',
  model         VARCHAR(150) DEFAULT '',
  poles         VARCHAR(20)  DEFAULT '',
  rating        VARCHAR(50)  DEFAULT '',
  voltage       VARCHAR(50)  DEFAULT '',
  ka            VARCHAR(50)  DEFAULT '',
  packaging     VARCHAR(100) DEFAULT '',
  notes         TEXT,
  current_stock INT DEFAULT 0,
  photo         VARCHAR(255) DEFAULT '',
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_serial (serial_code(100)),
  KEY idx_category (category),
  KEY idx_model (model)
) ENGINE=InnoDB;

-- ---------- STOCK IN records ----------
CREATE TABLE IF NOT EXISTS stock_in (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  serial_code VARCHAR(100) NOT NULL,
  item_name   VARCHAR(255) DEFAULT '',
  quantity    INT DEFAULT 0,
  photo       VARCHAR(255) DEFAULT '',
  remark      VARCHAR(255) DEFAULT '',
  source      VARCHAR(20)  DEFAULT 'manual',   -- manual | sheet
  entry_date  DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_in_serial (serial_code(100)),
  KEY idx_in_date (entry_date)
) ENGINE=InnoDB;

-- ---------- STOCK OUT records ----------
CREATE TABLE IF NOT EXISTS stock_out (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  serial_code VARCHAR(100) NOT NULL,
  item_name   VARCHAR(255) DEFAULT '',
  quantity    INT DEFAULT 0,
  photo       VARCHAR(255) DEFAULT '',
  remark      VARCHAR(255) DEFAULT '',
  source      VARCHAR(20)  DEFAULT 'manual',   -- manual | sheet | scanner
  entry_date  DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_out_serial (serial_code(100)),
  KEY idx_out_date (entry_date)
) ENGINE=InnoDB;

-- ============================================================
-- Sample master catalog (aapki sheet se pehle 3 products)
-- ============================================================
USE stock_system;
INSERT INTO products (serial_code, item_name, category, model, poles, rating, voltage, ka, packaging, notes, current_stock)
VALUES
('814009', 'NXB-63 1P C2A 6KA (180Pcs Ctn)', 'AC MCB', 'NXB-63', '1P', 'C2A', '', '6KA', '180Pcs Ctn', '', 0),
('814011', 'NXB-63 1P C4A 6KA (180Pcs Ctn)', 'AC MCB', 'NXB-63', '1P', 'C4A', '', '6KA', '180Pcs Ctn', '', 0),
('819977', 'NXBLE-63 2P C16A 30mA 6KA (54Pcs Ctn)', 'RCBO,ELCB', 'NXBLE-63', '2P', 'C16A', '', '6KA', '54Pcs Ctn', '30mA', 0)
ON DUPLICATE KEY UPDATE item_name = VALUES(item_name);