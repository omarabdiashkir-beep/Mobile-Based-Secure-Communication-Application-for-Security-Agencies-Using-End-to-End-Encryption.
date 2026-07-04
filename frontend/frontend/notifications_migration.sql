-- ─────────────────────────────────────────────────────────────
--  Notifications — run this SQL on the live database once
-- ─────────────────────────────────────────────────────────────

-- Drop old table if it had a different schema, then recreate.
-- Skip the DROP if you already have notification data you want to keep.
-- DROP TABLE IF EXISTS notifications;

ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS sender_id  INT UNSIGNED NULL          AFTER user_id,
  ADD COLUMN IF NOT EXISTS action_url VARCHAR(500)  NULL         AFTER data,
  MODIFY COLUMN data      TEXT NULL,
  MODIFY COLUMN is_read   TINYINT(1) NOT NULL DEFAULT 0;

-- Index for fast per-user unread lookups
ALTER TABLE notifications
  ADD INDEX IF NOT EXISTS idx_user_read (user_id, is_read, created_at);

-- ── If the notifications table doesn't exist yet, create it fresh ──
-- CREATE TABLE IF NOT EXISTS notifications (
--   id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   user_id    INT UNSIGNED NOT NULL,
--   sender_id  INT UNSIGNED NULL,
--   type       VARCHAR(50)  NOT NULL DEFAULT 'general',
--   title      VARCHAR(255) NOT NULL,
--   body       TEXT         NOT NULL,
--   data       TEXT         NULL,
--   action_url VARCHAR(500) NULL,
--   is_read    TINYINT(1)   NOT NULL DEFAULT 0,
--   read_at    DATETIME     NULL,
--   created_at DATETIME     NOT NULL,
--   INDEX idx_user_read (user_id, is_read, created_at)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
