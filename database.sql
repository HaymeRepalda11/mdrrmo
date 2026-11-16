-- =====================================================================
-- MDRRMO Los Baños – database schema (MySQL / MariaDB, XAMPP)
-- Import in phpMyAdmin:  Import -> choose this file -> Go
-- =====================================================================
CREATE DATABASE IF NOT EXISTS `mdrrmo_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mdrrmo_db`;

-- ---------- Accounts ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120)  NOT NULL,
  `email`         VARCHAR(190)  NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL,
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
  `phone`         VARCHAR(20)   NOT NULL DEFAULT '',
  `barangay`      VARCHAR(60)   NOT NULL DEFAULT '',
  `birthday`      DATE          NULL,
  `sex`           VARCHAR(20)   NOT NULL DEFAULT '',
  `blood`         VARCHAR(5)    NOT NULL DEFAULT '',
  `address`       VARCHAR(255)  NOT NULL DEFAULT '',
  `ec_name`       VARCHAR(120)  NOT NULL DEFAULT '',
  `ec_phone`      VARCHAR(20)   NOT NULL DEFAULT '',
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` DATETIME      NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_barangay` (`barangay`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Forgot-password codes (stored hashed, 10-minute life) ----------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `code_hash`  VARCHAR(255)  NOT NULL,
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME      NOT NULL,
  `used_at`    DATETIME      NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resets_user` (`user_id`),
  CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Brute-force protection for login / reset ----------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(190) NOT NULL,
  `ip`         VARCHAR(45)  NOT NULL,
  `success`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_email` (`email`, `created_at`),
  KEY `idx_attempts_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Contact Us messages ----------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NULL,
  `name`       VARCHAR(120)  NOT NULL,
  `email`      VARCHAR(190)  NOT NULL,
  `phone`      VARCHAR(20)   NOT NULL DEFAULT '',
  `topic`      VARCHAR(80)   NOT NULL,
  `message`    TEXT          NOT NULL,
  `status`     ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  `ip`         VARCHAR(45)   NOT NULL DEFAULT '',
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_status` (`status`, `created_at`),
  KEY `idx_msg_ip` (`ip`, `created_at`),
  CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Go-bag checklist (per user) ----------
CREATE TABLE IF NOT EXISTS `gobag_progress` (
  `user_id`    INT UNSIGNED NOT NULL,
  `items`      VARCHAR(500) NOT NULL DEFAULT '[]',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_gobag_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Editable page content (saved by the admin "Edit Page" tools) ----------
CREATE TABLE IF NOT EXISTS `page_content` (
  `page`       VARCHAR(60)  NOT NULL,            -- home, about, services, news, contact, safety-tips
  `region`     VARCHAR(30)  NOT NULL,            -- .topbar, main, .site-footer
  `html`       MEDIUMTEXT   NOT NULL,
  `updated_by` INT UNSIGNED NULL,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`page`, `region`),
  CONSTRAINT `fk_content_user` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Previous versions of each region (so a bad edit can be restored from phpMyAdmin)
CREATE TABLE IF NOT EXISTS `page_content_history` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page`       VARCHAR(60)  NOT NULL,
  `region`     VARCHAR(30)  NOT NULL,
  `html`       MEDIUMTEXT   NOT NULL,
  `saved_by`   INT UNSIGNED NULL,
  `saved_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hist_page` (`page`, `region`, `saved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
