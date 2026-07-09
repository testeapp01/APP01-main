-- Migration: create integracoes table
-- Compatible with MySQL
CREATE TABLE IF NOT EXISTS `integracoes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(191) NOT NULL,
  `tipo` VARCHAR(60) DEFAULT NULL,
  `config` JSON DEFAULT NULL,
  `status` VARCHAR(30) DEFAULT 'ativo',
  `ultimo_sincronismo` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
