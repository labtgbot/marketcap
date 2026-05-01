-- Migration: 0005_ton_ecosystem_curation
-- Purpose: Store curated TON ecosystem categories, assets, lists, and revision history.

CREATE TABLE IF NOT EXISTS `ton_asset_categories` (
  `id` VARCHAR(80) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(80) NULL,
  `tag` VARCHAR(80) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ton_asset_categories_tag` (`tag`),
  KEY `idx_ton_asset_categories_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ton_assets` (
  `id` VARCHAR(120) NOT NULL,
  `coin_id` VARCHAR(160) NULL,
  `name` VARCHAR(191) NOT NULL,
  `symbol` VARCHAR(40) NOT NULL,
  `category_id` VARCHAR(80) NOT NULL,
  `verification_state` ENUM('verified', 'curated', 'unverified') NOT NULL DEFAULT 'unverified',
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `priority` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `contract_addresses_json` JSON NULL,
  `aliases_json` JSON NULL,
  `route_json` JSON NULL,
  `links_json` JSON NULL,
  `created_by_admin_user_id` BIGINT UNSIGNED NULL,
  `updated_by_admin_user_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_ton_assets_coin_id` (`coin_id`),
  KEY `idx_ton_assets_category` (`category_id`, `verification_state`, `priority`),
  KEY `idx_ton_assets_featured` (`featured`, `visible`, `priority`),
  KEY `idx_ton_assets_verification` (`verification_state`, `visible`, `priority`),
  CONSTRAINT `fk_ton_assets_category_id` FOREIGN KEY (`category_id`) REFERENCES `ton_asset_categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ton_assets_created_by` FOREIGN KEY (`created_by_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ton_assets_updated_by` FOREIGN KEY (`updated_by_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ton_asset_tags` (
  `asset_id` VARCHAR(120) NOT NULL,
  `tag` VARCHAR(80) NOT NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`asset_id`, `tag`),
  KEY `idx_ton_asset_tags_tag` (`tag`, `asset_id`),
  CONSTRAINT `fk_ton_asset_tags_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `ton_assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ton_ecosystem_lists` (
  `id` VARCHAR(80) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_ton_ecosystem_lists_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ton_ecosystem_list_assets` (
  `list_id` VARCHAR(80) NOT NULL,
  `asset_id` VARCHAR(120) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1000,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`list_id`, `asset_id`),
  KEY `idx_ton_ecosystem_list_assets_asset` (`asset_id`, `list_id`),
  KEY `idx_ton_ecosystem_list_assets_order` (`list_id`, `sort_order`),
  CONSTRAINT `fk_ton_ecosystem_list_assets_list_id` FOREIGN KEY (`list_id`) REFERENCES `ton_ecosystem_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ton_ecosystem_list_assets_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `ton_assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ton_curation_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_admin_user_id` BIGINT UNSIGNED NULL,
  `source` ENUM('json_store', 'admin_ui', 'migration', 'api') NOT NULL DEFAULT 'api',
  `asset_id` VARCHAR(120) NULL,
  `revision_hash` CHAR(64) NOT NULL,
  `payload_json` JSON NOT NULL,
  `request_id` VARCHAR(128) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_ton_curation_revisions_asset` (`asset_id`, `created_at`),
  KEY `idx_ton_curation_revisions_actor` (`actor_admin_user_id`, `created_at`),
  UNIQUE KEY `uniq_ton_curation_revisions_hash` (`revision_hash`),
  CONSTRAINT `fk_ton_curation_revisions_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `ton_assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ton_curation_revisions_actor` FOREIGN KEY (`actor_admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
