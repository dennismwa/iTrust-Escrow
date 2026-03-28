-- ============================================================
-- AMANI ESCROW — COMPLETE DATABASE MIGRATION
-- Run in cPanel → phpMyAdmin → SQL tab
-- ============================================================

-- ─── 1. USERS: notification preferences ─────────────────────
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email_notifications` TINYINT(1) NOT NULL DEFAULT 1 AFTER `two_factor_secret`,
  ADD COLUMN IF NOT EXISTS `sms_notifications` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_notifications`;

-- ─── 2. SITE MEDIA: hero background image ───────────────────
INSERT IGNORE INTO `site_media`
  (`media_key`, `file_path`, `title`, `alt_text`, `section`, `sort_order`)
VALUES
  ('hero_bg_image', '', 'Hero Background Image', 'Full background for Hero Style B', 'homepage', 4);

-- ─── 3. SETTINGS: new homepage + payment controls ───────────
-- NOTE: Column is `setting_type` NOT `type`
INSERT IGNORE INTO `settings`
  (`setting_key`, `setting_value`, `setting_type`, `category`, `label`, `description`, `is_public`)
VALUES
  ('hero_style', 'A', 'text', 'homepage', 'Hero Style (A or B)', 'A = Image right side. B = Full background image overlay. Type A or B.', 1),
  ('hero_bg_opacity', '0.4', 'text', 'homepage', 'Hero BG Opacity (0.0–1.0)', 'For Hero B only. 0.0=transparent, 1.0=dark. Recommended 0.3–0.5', 1),
  ('homepage_stats_label1', 'Transactions', 'text', 'homepage', 'Stat 1 Label', 'Caption under first stat number', 1),
  ('homepage_stats_label2', 'Users', 'text', 'homepage', 'Stat 2 Label', 'Caption under second stat number', 1),
  ('homepage_stats_label3', 'Countries', 'text', 'homepage', 'Stat 3 Label', 'Caption under third stat number', 1);

-- ─── 4. PAYMENT GATEWAYS: manual method ─────────────────────
INSERT IGNORE INTO `payment_gateways`
  (`name`, `display_name`, `description`, `is_active`, `environment`, `config`, `supported_currencies`, `fee_type`, `fee_value`, `fee_fixed`, `sort_order`)
VALUES
  ('manual', 'Manual Payment', 'Manual payment with proof upload', 1, 'live',
   '{"instructions":"Send payment and upload proof. Admin will confirm within 24 hours."}',
   '["KES","USD","NGN","GHS","ZAR"]', 'fixed', 0.0000, 0.00, 6);

-- ============================================================
-- DONE
-- ============================================================
