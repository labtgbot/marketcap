-- Rollback: 0002_telegram_session_context

ALTER TABLE `user_sessions`
  DROP KEY `idx_user_sessions_telegram_chat_type`,
  DROP COLUMN `telegram_chat_type`;
