-- Migration: 0003_watchlist_tombstones
-- Purpose: Preserve removal timestamps so watchlist sync can resolve stale-device conflicts.

CREATE TABLE IF NOT EXISTS `watchlist_tombstones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `watchlist_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `coin_id` VARCHAR(96) NOT NULL,
  `removed_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_watchlist_tombstones_watchlist_coin` (`watchlist_id`, `coin_id`),
  KEY `idx_watchlist_tombstones_user_coin` (`user_id`, `coin_id`, `removed_at`),
  CONSTRAINT `fk_watchlist_tombstones_watchlist_id` FOREIGN KEY (`watchlist_id`) REFERENCES `watchlists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_watchlist_tombstones_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
