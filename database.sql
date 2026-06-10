-- =====================================================================
-- Car Import Management System ("carimport")
-- Database schema + seed data
-- MySQL / XAMPP — import via phpMyAdmin or:  mysql -u root < database.sql
-- Base currency: IQD. All *_base amounts are stored in IQD.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `carimport`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `carimport`;

-- ---------------------------------------------------------------------
-- Roles
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_role` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name`  VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Users  (language: en | ar | ku)
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(150) NOT NULL,
  `role_id`       INT UNSIGNED NOT NULL,
  `language`      VARCHAR(5) NOT NULL DEFAULT 'en',
  `status`        TINYINT(1) NOT NULL DEFAULT 1,          -- 1 active, 0 inactive
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`    DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `fk_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pages (drives the dynamic sidebar; names in 3 languages)
-- parent_id NULL = top-level item; page_url NULL = group header (treeview)
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_pages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_en`    VARCHAR(100) NOT NULL,
  `name_ar`    VARCHAR(100) NOT NULL,
  `name_ku`    VARCHAR(100) NOT NULL,
  `page_url`   VARCHAR(150) NULL DEFAULT NULL,
  `parent_id`  INT UNSIGNED NULL DEFAULT NULL,
  `icon`       VARCHAR(80) NOT NULL DEFAULT 'far fa-circle',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_pages_parent` (`parent_id`),
  CONSTRAINT `fk_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `tbl_pages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Role <-> Page permissions
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_role_pages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`    INT UNSIGNED NOT NULL,
  `page_id`    INT UNSIGNED NOT NULL,
  `can_view`   TINYINT(1) NOT NULL DEFAULT 0,
  `can_add`    TINYINT(1) NOT NULL DEFAULT 0,
  `can_edit`   TINYINT(1) NOT NULL DEFAULT 0,
  `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_page` (`role_id`, `page_id`),
  KEY `fk_rp_page` (`page_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_role` (`id`),
  CONSTRAINT `fk_rp_page` FOREIGN KEY (`page_id`) REFERENCES `tbl_pages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- System settings (key/value)
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_settings` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Currencies
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_currency` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`   VARCHAR(10) NOT NULL,
  `name`   VARCHAR(50) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_currency_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Cars (the core product)
-- status pipeline: purchased > paid > shipped > in_transit > at_port >
--                  cleared > in_showroom > sold
-- purchase_price is also mirrored as the first cost line in tbl_car_costs
-- so the landed cost is always SUM(tbl_car_costs.amount_base).
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_cars` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lot_number`          VARCHAR(50) NULL,
  `vin`                 VARCHAR(50) NULL,
  `make`                VARCHAR(80) NOT NULL,
  `model`               VARCHAR(80) NOT NULL,
  `year`                SMALLINT UNSIGNED NULL,
  `color`               VARCHAR(50) NULL,
  `purchase_price`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,   -- native currency
  `purchase_currency`   VARCHAR(10) NOT NULL DEFAULT 'USD',
  `purchase_rate`       DECIMAL(15,6) NOT NULL DEFAULT 1.000000, -- rate locked at purchase
  `purchase_price_base` DECIMAL(15,2) NOT NULL DEFAULT 0.00,   -- in IQD
  `purchase_date`       DATE NULL,
  `supplier`            VARCHAR(150) NULL,                     -- e.g. Copart
  `status`              ENUM('purchased','paid','shipped','in_transit','at_port',
                             'cleared','in_showroom','sold') NOT NULL DEFAULT 'purchased',
  `notes`               TEXT NULL,
  `created_by`          INT UNSIGNED NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`          DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cars_status` (`status`),
  KEY `idx_cars_vin` (`vin`),
  KEY `fk_cars_user` (`created_by`),
  CONSTRAINT `fk_cars_user` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Cost types
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_cost_types` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Shipments (containers) — one invoice split across cars
-- allocation_method: count | value | manual
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_shipments` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_ref`      VARCHAR(80) NOT NULL,
  `container_number`  VARCHAR(80) NULL,
  `total_amount`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency`          VARCHAR(10) NOT NULL DEFAULT 'USD',
  `exchange_rate`     DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `allocation_method` ENUM('count','value','manual') NOT NULL DEFAULT 'count',
  `ship_date`         DATE NULL,
  `arrival_date`      DATE NULL,
  `status`            VARCHAR(30) NOT NULL DEFAULT 'open',
  `notes`             TEXT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`        DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Cost lines attached to a car (the landed-cost ledger)
-- amount_base = amount * exchange_rate, frozen at transaction time
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_car_costs` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_id`         INT UNSIGNED NOT NULL,
  `cost_type_id`   INT UNSIGNED NOT NULL,
  `description`    VARCHAR(255) NULL,
  `amount`         DECIMAL(15,2) NOT NULL,                  -- native currency
  `currency`       VARCHAR(10) NOT NULL DEFAULT 'IQD',
  `exchange_rate`  DECIMAL(15,6) NOT NULL DEFAULT 1.000000, -- locked rate
  `amount_base`    DECIMAL(15,2) NOT NULL,                  -- in IQD, never recomputed
  `invoice_number` VARCHAR(80) NULL,
  `attachment`     VARCHAR(255) NULL,
  `cost_date`      DATE NULL,
  `shipment_id`    INT UNSIGNED NULL DEFAULT NULL,          -- set when allocated from a shipment
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`     DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_costs_car` (`car_id`),
  KEY `fk_costs_type` (`cost_type_id`),
  KEY `fk_costs_shipment` (`shipment_id`),
  CONSTRAINT `fk_costs_car` FOREIGN KEY (`car_id`) REFERENCES `tbl_cars` (`id`),
  CONSTRAINT `fk_costs_type` FOREIGN KEY (`cost_type_id`) REFERENCES `tbl_cost_types` (`id`),
  CONSTRAINT `fk_costs_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `tbl_shipments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Cars inside a shipment + their allocated share (in base currency)
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_shipment_cars` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `shipment_id`           INT UNSIGNED NOT NULL,
  `car_id`                INT UNSIGNED NOT NULL,
  `allocated_amount_base` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shipment_car` (`shipment_id`, `car_id`),
  KEY `fk_sc_car` (`car_id`),
  CONSTRAINT `fk_sc_shipment` FOREIGN KEY (`shipment_id`) REFERENCES `tbl_shipments` (`id`),
  CONSTRAINT `fk_sc_car` FOREIGN KEY (`car_id`) REFERENCES `tbl_cars` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Customers
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_customers` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(50) NULL,
  `address`    VARCHAR(255) NULL,
  `notes`      TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Sales (one open sale per car; status: open | paid | cancelled)
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_sales` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_id`          INT UNSIGNED NOT NULL,
  `customer_id`     INT UNSIGNED NOT NULL,
  `sale_price`      DECIMAL(15,2) NOT NULL,
  `currency`        VARCHAR(10) NOT NULL DEFAULT 'IQD',
  `exchange_rate`   DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `sale_price_base` DECIMAL(15,2) NOT NULL,
  `sale_date`       DATE NOT NULL,
  `status`          ENUM('open','paid','cancelled') NOT NULL DEFAULT 'open',
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sales_car` (`car_id`),
  KEY `fk_sales_customer` (`customer_id`),
  CONSTRAINT `fk_sales_car` FOREIGN KEY (`car_id`) REFERENCES `tbl_cars` (`id`),
  CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Payments / installments against a sale
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_payments` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id`       INT UNSIGNED NOT NULL,
  `amount`        DECIMAL(15,2) NOT NULL,
  `currency`      VARCHAR(10) NOT NULL DEFAULT 'IQD',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `amount_base`   DECIMAL(15,2) NOT NULL,
  `payment_date`  DATE NOT NULL,
  `method`        VARCHAR(50) NULL,                 -- cash, transfer, ...
  `notes`         VARCHAR(255) NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`    DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payments_sale` (`sale_id`),
  CONSTRAINT `fk_payments_sale` FOREIGN KEY (`sale_id`) REFERENCES `tbl_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- General spending NOT tied to a specific car
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_spending` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `description`   VARCHAR(255) NOT NULL,
  `category`      VARCHAR(100) NULL,
  `amount`        DECIMAL(15,2) NOT NULL,
  `currency`      VARCHAR(10) NOT NULL DEFAULT 'IQD',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `amount_base`   DECIMAL(15,2) NOT NULL,
  `spend_date`    DATE NOT NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`    DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Audit log
-- ---------------------------------------------------------------------
CREATE TABLE `tbl_audit_log` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NULL,
  `action`     VARCHAR(50) NOT NULL,            -- create | update | delete | login | ...
  `table_name` VARCHAR(80) NOT NULL,
  `record_id`  BIGINT UNSIGNED NULL,
  `details`    TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_table` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- =====================================================================

INSERT INTO `tbl_role` (`id`, `role_name`) VALUES (1, 'Administrator');

-- admin / admin123  (change the password after first login!)
INSERT INTO `tbl_users` (`id`, `username`, `password_hash`, `full_name`, `role_id`, `language`, `status`) VALUES
(1, 'admin', '$2y$12$Uxlp.H8cmK/znNPQ8vOHBuQ0JHJCTMND7HK1EDGtI3xXfk6uROsPy', 'System Administrator', 1, 'en', 1);

INSERT INTO `tbl_currency` (`id`, `code`, `name`, `symbol`) VALUES
(1, 'USD', 'US Dollar', '$'),
(2, 'IQD', 'Iraqi Dinar', 'IQD');

INSERT INTO `tbl_cost_types` (`id`, `name`) VALUES
(1, 'Purchase Price'),
(2, 'Buyer Fee'),
(3, 'US Transport'),
(4, 'Ocean Freight'),
(5, 'Customs/Tax'),
(6, 'Clearance'),
(7, 'Repair'),
(8, 'Spare Parts'),
(9, 'Other');

INSERT INTO `tbl_settings` (`setting_key`, `setting_value`) VALUES
('system_name', 'Car Import System'),
('logo_path', 'dist/img/AdminLTELogo.png'),
('theme', 'peshang'),
('default_language', 'en'),
('default_exchange_rate', '1450');   -- USD -> IQD default suggestion (editable)

-- Sidebar pages (name_en / name_ar / name_ku Badini)
INSERT INTO `tbl_pages` (`id`, `name_en`, `name_ar`, `name_ku`, `page_url`, `parent_id`, `icon`, `sort_order`) VALUES
(1,  'Dashboard',       'لوحة التحكم',     'داشبۆرد',          'dashboard.php',        NULL, 'fas fa-tachometer-alt', 1),
(2,  'Cars',            'السيارات',        'ترومبێل',           'cars.php',             NULL, 'fas fa-car',            2),
(3,  'Shipments',       'الشحنات',         'بارکرن',            'shipments.php',        NULL, 'fas fa-ship',           3),
(4,  'Customers',       'العملاء',         'موشتەری',           'customers.php',        NULL, 'fas fa-users',          4),
(5,  'Sales',           'المبيعات',        'فرۆتن',             'sales.php',            NULL, 'fas fa-handshake',      5),
(6,  'Payments',        'الدفعات',         'پارەدان',           'payments.php',         NULL, 'fas fa-money-bill-wave',6),
(7,  'Spending',        'المصروفات',       'خەرجی',             'spending.php',         NULL, 'fas fa-wallet',         7),
(8,  'Invoices',        'الفواتير',        'پسوولە',            'invoices.php',         NULL, 'fas fa-file-invoice',   8),
(9,  'Currencies',      'العملات',         'دراڤ',              'currency.php',         NULL, 'fas fa-coins',          9),
(10, 'Reports',         'التقارير',        'ڕاپۆرت',            NULL,                   NULL, 'fas fa-chart-bar',      10),
(11, 'Profit Report',   'تقرير الأرباح',   'ڕاپۆرتا قازانجێ',   'report_profit.php',    10,   'fas fa-chart-line',     1),
(12, 'Monthly Report',  'التقرير الشهري',  'ڕاپۆرتا هەیڤانە',   'report_monthly.php',   10,   'fas fa-calendar-alt',   2),
(13, 'Administration',  'الإدارة',         'بەڕێڤەبرن',         NULL,                   NULL, 'fas fa-cogs',           11),
(14, 'Users',           'المستخدمون',      'بکارهێنەر',         'users.php',            13,   'fas fa-user',           1),
(15, 'Roles',           'الأدوار',         'ڕۆل',               'roles.php',            13,   'fas fa-user-tag',       2),
(16, 'Permissions',     'الصلاحيات',       'دەستوور',           'role_permissions.php', 13,   'fas fa-key',            3),
(17, 'Settings',        'الإعدادات',       'ڕێکخستن',           'settings.php',         13,   'fas fa-sliders-h',      4),
(18, 'Audit Log',       'سجل التدقيق',     'تۆمارا چاڤدێریێ',   'audit_log.php',        13,   'fas fa-history',        5);

-- Admin role gets full access to every page
INSERT INTO `tbl_role_pages` (`role_id`, `page_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT 1, id, 1, 1, 1, 1 FROM `tbl_pages`;
