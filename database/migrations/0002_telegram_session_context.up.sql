-- Migration: 0002_telegram_session_context
-- Purpose: Store signed Telegram launch chat context for validated sessions.

ALTER TABLE `user_sessions`
  ADD COLUMN `telegram_chat_type` VARCHAR(32) NULL AFTER `chat_instance_hash`,
  ADD KEY `idx_user_sessions_telegram_chat_type` (`telegram_chat_type`, `last_seen_at`);
