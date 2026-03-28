-- ============================================================
-- AMANI ESCROW PLATFORM v2.0 - Complete Database Schema
-- Africa's Premier Escrow Service
-- ============================================================
--
-- ┌─────────────────────────────────────────────────────────┐
-- │  HOW TO IMPORT                                          │
-- ├─────────────────────────────────────────────────────────┤
-- │                                                         │
-- │  METHOD A — cPanel phpMyAdmin (RECOMMENDED):            │
-- │    1. cPanel → MySQL Databases → create your database   │
-- │    2. cPanel → phpMyAdmin → select your database        │
-- │    3. Import tab → choose this file → Go               │
-- │    This file works as-is: it only creates tables and    │
-- │    inserts data — NO "CREATE DATABASE" or "USE" lines   │
-- │    that would conflict with your cPanel database name.  │
-- │                                                         │
-- │  METHOD B — CLI (optional):                             │
-- │    mysql -u USERNAME -p DATABASE_NAME < database.sql    │
-- │                                                         │
-- │  DEFAULT ADMIN LOGIN (change password immediately!):    │
-- │    Email:    admin@amaniescrow.com                      │
-- │    Password: Admin@123                                  │
-- │                                                         │
-- └─────────────────────────────────────────────────────────┘
--
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `role` ENUM('user','agent','admin','superadmin') NOT NULL DEFAULT 'user',
    `status` ENUM('active','suspended','banned','pending') NOT NULL DEFAULT 'pending',
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `kyc_status` ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
    `trust_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `total_transactions` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_volume` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `preferred_currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `timezone` VARCHAR(50) DEFAULT 'Africa/Nairobi',
    `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `two_factor_secret` VARCHAR(255) DEFAULT NULL,
    `email_verification_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_token` VARCHAR(255) DEFAULT NULL,
    `password_reset_expires` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `business_name` VARCHAR(255) DEFAULT NULL,
    `business_type` VARCHAR(100) DEFAULT NULL,
    `business_registration` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Kenya',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ROLES & PERMISSIONS
-- ============================================================
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(150) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ESCROWS
-- ============================================================
CREATE TABLE `escrows` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `escrow_id` VARCHAR(20) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `category` ENUM('car','property','freelance','marketplace','import_export','electronics','digital_services','other') NOT NULL DEFAULT 'other',
    `buyer_id` INT UNSIGNED NOT NULL,
    `seller_id` INT UNSIGNED DEFAULT NULL,
    `agent_id` INT UNSIGNED DEFAULT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `escrow_fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `fee_paid_by` ENUM('buyer','seller','split') NOT NULL DEFAULT 'buyer',
    `total_amount` DECIMAL(15,2) NOT NULL,
    `status` ENUM('draft','pending','funded','in_progress','delivered','completed','disputed','cancelled','refunded','expired') NOT NULL DEFAULT 'draft',
    `inspection_period_days` INT UNSIGNED NOT NULL DEFAULT 3,
    `delivery_deadline` DATE DEFAULT NULL,
    `terms` TEXT DEFAULT NULL,
    `is_milestone` TINYINT(1) NOT NULL DEFAULT 0,
    `invitation_token` VARCHAR(255) DEFAULT NULL,
    `invitation_email` VARCHAR(255) DEFAULT NULL,
    `contract_hash` VARCHAR(64) DEFAULT NULL,
    `funded_at` DATETIME DEFAULT NULL,
    `delivered_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `cancelled_at` DATETIME DEFAULT NULL,
    `auto_complete_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`),
    INDEX `idx_escrows_status` (`status`),
    INDEX `idx_escrows_buyer` (`buyer_id`),
    INDEX `idx_escrows_seller` (`seller_id`),
    INDEX `idx_escrows_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `escrow_milestones` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `escrow_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `order_num` INT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('pending','funded','in_progress','delivered','completed','disputed') NOT NULL DEFAULT 'pending',
    `deadline` DATE DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`escrow_id`) REFERENCES `escrows`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRANSACTIONS
-- ============================================================
CREATE TABLE `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `transaction_ref` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `escrow_id` INT UNSIGNED DEFAULT NULL,
    `type` ENUM('escrow_fund','escrow_release','escrow_refund','withdrawal','deposit','fee','adjustment') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(15,2) NOT NULL,
    `status` ENUM('pending','processing','completed','failed','reversed') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `payment_reference` VARCHAR(255) DEFAULT NULL,
    `gateway` VARCHAR(50) DEFAULT NULL,
    `gateway_reference` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`escrow_id`) REFERENCES `escrows`(`id`),
    INDEX `idx_transactions_user` (`user_id`),
    INDEX `idx_transactions_status` (`status`),
    INDEX `idx_transactions_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- WALLETS
-- ============================================================
CREATE TABLE `wallets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `escrow_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_earned` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_withdrawn` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_currency` (`user_id`, `currency`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- WITHDRAWALS
-- ============================================================
CREATE TABLE `withdrawals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `method` VARCHAR(50) NOT NULL,
    `account_details` JSON NOT NULL,
    `status` ENUM('pending','processing','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT DEFAULT NULL,
    `processed_by` INT UNSIGNED DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_withdrawals_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DISPUTES
-- ============================================================
CREATE TABLE `disputes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `dispute_id` VARCHAR(20) NOT NULL UNIQUE,
    `escrow_id` INT UNSIGNED NOT NULL,
    `raised_by` INT UNSIGNED NOT NULL,
    `against_user` INT UNSIGNED NOT NULL,
    `reason` ENUM('item_not_received','item_not_as_described','service_incomplete','quality_issue','fraud','communication','deadline_missed','other') NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('open','under_review','evidence_requested','resolved','closed') NOT NULL DEFAULT 'open',
    `resolution` ENUM('buyer_refund','seller_release','partial_refund','cancelled') DEFAULT NULL,
    `resolution_amount` DECIMAL(15,2) DEFAULT NULL,
    `resolution_notes` TEXT DEFAULT NULL,
    `resolved_by` INT UNSIGNED DEFAULT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `deadline` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`escrow_id`) REFERENCES `escrows`(`id`),
    FOREIGN KEY (`raised_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`against_user`) REFERENCES `users`(`id`),
    INDEX `idx_disputes_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dispute_evidence` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `dispute_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('text','image','document','link') NOT NULL,
    `content` TEXT NOT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`dispute_id`) REFERENCES `disputes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MESSAGES
-- ============================================================
CREATE TABLE `conversations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `escrow_id` INT UNSIGNED DEFAULT NULL,
    `dispute_id` INT UNSIGNED DEFAULT NULL,
    `type` ENUM('escrow','dispute','direct','support') NOT NULL DEFAULT 'direct',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`escrow_id`) REFERENCES `escrows`(`id`),
    FOREIGN KEY (`dispute_id`) REFERENCES `disputes`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversation_participants` (
    `conversation_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `last_read_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`conversation_id`, `user_id`),
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('text','file','system') NOT NULL DEFAULT 'text',
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_name` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`),
    INDEX `idx_messages_conversation` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'bell',
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user` (`user_id`),
    INDEX `idx_notifications_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KYC DOCUMENTS
-- ============================================================
CREATE TABLE `kyc_documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `document_type` ENUM('national_id','passport','drivers_license','selfie','proof_of_address','business_registration') NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT DEFAULT NULL,
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    INDEX `idx_kyc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ESCROW ATTACHMENTS
-- ============================================================
CREATE TABLE `escrow_attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `escrow_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL,
    `type` ENUM('contract','invoice','delivery_proof','receipt','other') NOT NULL DEFAULT 'other',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`escrow_id`) REFERENCES `escrows`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PAYMENT GATEWAYS
-- ============================================================
CREATE TABLE `payment_gateways` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `environment` ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
    `config` JSON NOT NULL,
    `supported_currencies` JSON DEFAULT NULL,
    `min_amount` DECIMAL(15,2) DEFAULT NULL,
    `max_amount` DECIMAL(15,2) DEFAULT NULL,
    `fee_type` ENUM('fixed','percentage','mixed') NOT NULL DEFAULT 'percentage',
    `fee_value` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `fee_fixed` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================
CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('text','number','boolean','json','color','image','textarea','file') NOT NULL DEFAULT 'text',
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `label` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_key` (`setting_key`),
    INDEX `idx_settings_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SITE MEDIA / IMAGES (Admin manageable)
-- ============================================================
CREATE TABLE `site_media` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `media_key` VARCHAR(100) NOT NULL UNIQUE,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `alt_text` VARCHAR(255) DEFAULT NULL,
    `section` VARCHAR(50) NOT NULL DEFAULT 'general',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_media_key` (`media_key`),
    INDEX `idx_media_section` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ACTIVITY LOGS
-- ============================================================
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_activity_user` (`user_id`),
    INDEX `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT LOGS
-- ============================================================
CREATE TABLE `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `record_id` INT UNSIGNED DEFAULT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CURRENCIES
-- ============================================================
CREATE TABLE `currencies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(3) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `symbol` VARCHAR(10) NOT NULL,
    `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ESCROW CONTRACTS
-- ============================================================
CREATE TABLE `escrow_contracts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `escrow_id` INT UNSIGNED NOT NULL,
    `content` LONGTEXT NOT NULL,
    `buyer_signed` TINYINT(1) NOT NULL DEFAULT 0,
    `seller_signed` TINYINT(1) NOT NULL DEFAULT 0,
    `buyer_signed_at` DATETIME DEFAULT NULL,
    `seller_signed_at` DATETIME DEFAULT NULL,
    `hash` VARCHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`escrow_id`) REFERENCES `escrows`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

INSERT INTO `roles` (`name`, `display_name`, `description`, `is_system`) VALUES
('superadmin', 'Super Administrator', 'Full system access', 1),
('admin', 'Administrator', 'Administrative access', 1),
('agent', 'Escrow Agent', 'Manage assigned escrows', 1),
('user', 'User', 'Standard user', 1);

INSERT INTO `currencies` (`code`, `name`, `symbol`, `exchange_rate`, `is_active`, `is_default`) VALUES
('KES', 'Kenyan Shilling', 'KSh', 1.000000, 1, 1),
('USD', 'US Dollar', '$', 0.006897, 1, 0),
('NGN', 'Nigerian Naira', '₦', 10.758621, 1, 0),
('GHS', 'Ghanaian Cedi', 'GH₵', 0.085517, 1, 0),
('ZAR', 'South African Rand', 'R', 0.124483, 1, 0),
('TZS', 'Tanzanian Shilling', 'TSh', 17.241379, 1, 0),
('UGX', 'Ugandan Shilling', 'USh', 25.517241, 1, 0);

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `label`, `description`, `is_public`) VALUES
('platform_name', 'Amani Escrow', 'text', 'general', 'Platform Name', 'Name displayed across the platform', 1),
('platform_tagline', 'Africa''s Most Trusted Escrow Platform', 'text', 'general', 'Platform Tagline', 'Short tagline', 1),
('company_email', 'support@amaniescrow.com', 'text', 'general', 'Support Email', 'Primary support email', 1),
('company_phone', '+254 700 000 000', 'text', 'general', 'Support Phone', 'Primary phone', 1),
('company_address', 'Nairobi, Kenya', 'text', 'general', 'Company Address', 'Physical address', 1),
('site_logo', '', 'image', 'branding', 'Site Logo', 'Main logo used across the entire platform', 1),
('site_logo_light', '', 'image', 'branding', 'Site Logo (Light)', 'Light version for dark backgrounds', 1),
('favicon', '', 'image', 'branding', 'Favicon', 'Browser tab icon (.ico or .png)', 1),
('pwa_icon_192', '', 'image', 'branding', 'PWA Icon 192x192', 'PWA install icon (192x192 PNG)', 1),
('pwa_icon_512', '', 'image', 'branding', 'PWA Icon 512x512', 'PWA splash icon (512x512 PNG)', 1),
('primary_color', '#C8F545', 'color', 'branding', 'Primary/Accent Color', 'Main accent color (lime green)', 1),
('bg_dark', '#0B0F19', 'color', 'branding', 'Dark Background', 'Main dark background color', 1),
('bg_card', '#131825', 'color', 'branding', 'Card Background', 'Card/surface background', 1),
('escrow_fee_percentage', '2.5', 'number', 'fees', 'Escrow Fee (%)', 'Platform fee percentage', 0),
('min_escrow_amount', '500', 'number', 'fees', 'Minimum Escrow (KES)', 'Minimum transaction', 0),
('max_escrow_amount', '10000000', 'number', 'fees', 'Maximum Escrow (KES)', 'Maximum transaction', 0),
('withdrawal_fee', '50', 'number', 'fees', 'Withdrawal Fee (KES)', 'Fixed withdrawal fee', 0),
('min_withdrawal', '1000', 'number', 'fees', 'Minimum Withdrawal', 'Min withdrawal amount', 0),
('default_inspection_days', '3', 'number', 'escrow', 'Default Inspection Days', 'Default inspection period', 0),
('auto_complete_days', '14', 'number', 'escrow', 'Auto-Complete Days', 'Days before auto-complete', 0),
('dispute_deadline_days', '7', 'number', 'escrow', 'Dispute Deadline Days', 'Days for evidence', 0),
('maintenance_mode', '0', 'boolean', 'system', 'Maintenance Mode', 'Enable maintenance', 0),
('registration_enabled', '1', 'boolean', 'system', 'Registration Enabled', 'Allow new registrations', 0),
('kyc_required', '1', 'boolean', 'system', 'KYC Required', 'Require KYC for large txns', 0),
('kyc_threshold', '50000', 'number', 'system', 'KYC Threshold (KES)', 'Amount triggering KYC', 0),
('pwa_enabled', '1', 'boolean', 'system', 'PWA Enabled', 'Enable Progressive Web App', 1),
('pwa_name', 'Amani Escrow', 'text', 'pwa', 'PWA App Name', 'Name shown on install prompt', 1),
('pwa_short_name', 'Amani', 'text', 'pwa', 'PWA Short Name', 'Short name for homescreen', 1),
('pwa_theme_color', '#0B0F19', 'color', 'pwa', 'PWA Theme Color', 'Status bar color on mobile', 1),
('pwa_bg_color', '#0B0F19', 'color', 'pwa', 'PWA Background Color', 'Splash screen background', 1),
('homepage_hero_title', 'Secure Escrow Services for Online Transactions', 'text', 'homepage', 'Hero Title', 'Main homepage heading', 1),
('homepage_hero_subtitle', 'Our Escrow system ensures secure online transactions, protecting buyers and sellers from fraud and payment disputes.', 'textarea', 'homepage', 'Hero Subtitle', 'Homepage subtitle', 1),
('homepage_hero_btn1_text', 'Get Started Now', 'text', 'homepage', 'Hero Button 1 Text', 'Primary CTA button', 1),
('homepage_hero_btn2_text', 'Discover More', 'text', 'homepage', 'Hero Button 2 Text', 'Secondary CTA button', 1),
('homepage_about_title', 'Trust your transactions with us - Safe, Secure, and Efficient!', 'text', 'homepage', 'About Section Title', 'About section heading', 1),
('homepage_about_text', 'Amani Escrow delivers secure escrow solutions built to protect your transactions and strengthen trust in every deal.', 'textarea', 'homepage', 'About Section Text', 'About section body', 1),
('homepage_stats_transactions', '50K+', 'text', 'homepage', 'Stats: Transactions', 'Transactions stat', 1),
('homepage_stats_users', '168K+', 'text', 'homepage', 'Stats: Users', 'Users stat', 1),
('homepage_stats_countries', '8+', 'text', 'homepage', 'Stats: Countries', 'Countries stat', 1),
('terms_of_service', '', 'textarea', 'legal', 'Terms of Service', 'Platform terms', 1),
('privacy_policy', '', 'textarea', 'legal', 'Privacy Policy', 'Privacy policy', 1);

-- Site media (admin-manageable images)
INSERT INTO `site_media` (`media_key`, `file_path`, `title`, `alt_text`, `section`, `sort_order`) VALUES
('hero_image', '', 'Hero Section Image', 'Escrow platform hero', 'homepage', 1),
('hero_mockup', '', 'Hero Mockup/Phone Image', 'App mockup', 'homepage', 2),
('about_image', '', 'About Section Image', 'About us', 'homepage', 3),
('feature_image_1', '', 'Feature Image 1', 'Feature 1', 'features', 1),
('feature_image_2', '', 'Feature Image 2', 'Feature 2', 'features', 2),
('feature_image_3', '', 'Feature Image 3', 'Feature 3', 'features', 3),
('cta_image', '', 'CTA Section Image', 'Call to action', 'homepage', 4),
('trust_badge_1', '', 'Trust Badge 1', 'Trust badge', 'trust', 1),
('trust_badge_2', '', 'Trust Badge 2', 'Trust badge', 'trust', 2),
('trust_badge_3', '', 'Trust Badge 3', 'Trust badge', 'trust', 3),
('og_image', '', 'Social Share Image', 'Open Graph image', 'seo', 1);

-- Payment Gateways
INSERT INTO `payment_gateways` (`name`, `display_name`, `description`, `is_active`, `environment`, `config`, `supported_currencies`, `fee_type`, `fee_value`, `fee_fixed`, `sort_order`) VALUES
('mpesa', 'M-Pesa', 'Mobile money via M-Pesa', 1, 'sandbox', '{"consumer_key":"","consumer_secret":"","shortcode":"","paybill":"","passkey":"","callback_url":"","c2b_enabled":true,"b2c_enabled":true}', '["KES"]', 'mixed', 1.5000, 10.00, 1),
('stripe', 'Stripe', 'Credit/debit card payments', 0, 'sandbox', '{"public_key":"","secret_key":"","webhook_secret":""}', '["USD","KES","NGN","GHS","ZAR"]', 'percentage', 2.9000, 0.00, 2),
('paypal', 'PayPal', 'PayPal payments', 0, 'sandbox', '{"client_id":"","client_secret":"","webhook_id":""}', '["USD"]', 'percentage', 3.5000, 0.00, 3),
('bank_transfer', 'Bank Transfer', 'Direct bank transfer', 1, 'live', '{"bank_name":"","account_name":"","account_number":"","branch":"","swift_code":""}', '["KES","USD","NGN"]', 'fixed', 0.0000, 100.00, 4),
('crypto', 'Cryptocurrency', 'BTC, ETH, USDT', 0, 'sandbox', '{"btc_address":"","eth_address":"","usdt_address":"","network":"","api_key":"","api_secret":""}', '["BTC","ETH","USDT"]', 'percentage', 1.0000, 0.00, 5);

-- ⚠️  Default admin: admin@amaniescrow.com / Admin@123 — CHANGE PASSWORD IMMEDIATELY
INSERT INTO `users` (`uuid`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `status`, `email_verified`, `kyc_status`, `trust_score`) VALUES
(UUID(), 'System', 'Admin', 'admin@amaniescrow.com', '+254700000000', '$2y$12$LJ3m4sFQDFCqFJl1BXVBb.D4GIvBKg8aS1jLvLN8bRqZjPx1o.8Zy', 'superadmin', 'active', 1, 'approved', 100.00);

INSERT INTO `wallets` (`user_id`, `currency`) VALUES (1, 'KES');

-- ============================================================
-- IMPORT COMPLETE
-- ============================================================
