-- ============================================================
-- Diwan International Pvt Ltd — Stock System (v2)
-- 3-level barcode hierarchy: CARTON -> BOX -> PCS
-- Stock hamesha PIECES mein track hota hai (current_stock_pcs)
-- ============================================================

CREATE DATABASE IF NOT EXISTS stock_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stock_system;

-- -------------------------------------------
-- Product Master
-- item_code = product master code (e.g. 45125)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  item_code         VARCHAR(100) NOT NULL,
  item_name         VARCHAR(255) NOT NULL DEFAULT '',
  pcs_per_box       INT DEFAULT 0,
  boxes_per_ctn     INT DEFAULT 0,
  pcs_per_ctn       INT DEFAULT 0,
  current_stock_pcs INT DEFAULT 0,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_item_code (item_code(100))
) ENGINE=InnoDB;

-- -------------------------------------------
-- Barcode Master (nayi table)
-- Har barcode ka ek level + pcs_qty hota hai.
-- parent_barcode = kis unit ke andar hai (CARTON ka parent empty).
-- is_consumed = 1 => stock out ho chuka hai.
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS barcodes (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  barcode        VARCHAR(100) NOT NULL,
  level          ENUM('CARTON','BOX','PCS') NOT NULL,
  item_code      VARCHAR(100) NOT NULL,
  parent_barcode VARCHAR(100) DEFAULT NULL,
  pcs_qty        INT NOT NULL DEFAULT 1,
  is_consumed    TINYINT DEFAULT 0,
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_barcode (barcode(100)),
  KEY idx_bc_item (item_code(100)),
  KEY idx_bc_parent (parent_barcode(100)),
  CONSTRAINT fk_bc_product FOREIGN KEY (item_code) REFERENCES products (item_code)
) ENGINE=InnoDB;

-- -------------------------------------------
-- Stock In (hamesha pieces mein)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS stock_in (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  barcode    VARCHAR(100) NOT NULL,
  level      ENUM('CARTON','BOX','PCS') NOT NULL,
  item_code  VARCHAR(100) NOT NULL,
  item_name  VARCHAR(255) DEFAULT '',
  pcs_qty    INT NOT NULL,
  source     VARCHAR(20) DEFAULT 'manual',
  remark     VARCHAR(255) DEFAULT '',
  entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_in_barcode (barcode(100)),
  KEY idx_in_item (item_code(100)),
  KEY idx_in_date (entry_date)
) ENGINE=InnoDB;

-- -------------------------------------------
-- Stock Out (hamesha pieces mein)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS stock_out (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  barcode    VARCHAR(100) NOT NULL,
  level      ENUM('CARTON','BOX','PCS') NOT NULL,
  item_code  VARCHAR(100) NOT NULL,
  item_name  VARCHAR(255) DEFAULT '',
  pcs_qty    INT NOT NULL,
  source     VARCHAR(20) DEFAULT 'manual',
  remark     VARCHAR(255) DEFAULT '',
  entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_out_barcode (barcode(100)),
  KEY idx_out_item (item_code(100)),
  KEY idx_out_date (entry_date)
) ENGINE=InnoDB;