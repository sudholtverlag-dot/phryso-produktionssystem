-- Installationsskript für das Phryso-Produktionssystem
-- Zielsystem: MySQL 8+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','redakteur') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hefte (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heftnummer VARCHAR(100) NOT NULL,
    erstellt_am TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_hefte_heftnummer (heftnummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS beitraege (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    heft_id BIGINT UNSIGNED NOT NULL,
    autor_id BIGINT UNSIGNED NOT NULL,
    ueberschrift VARCHAR(255) NOT NULL,
    subline VARCHAR(255) NULL,
    fotograf VARCHAR(255) NULL,
    haupttext LONGTEXT NOT NULL,
    wortanzahl INT UNSIGNED NOT NULL DEFAULT 0,
    titelbild_flag TINYINT(1) NOT NULL DEFAULT 0,
    kleine_bilder_anzahl INT UNSIGNED NOT NULL DEFAULT 0,
    berechnete_seiten DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    erstellt_am TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aktualisiert_am TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_beitraege_heft
        FOREIGN KEY (heft_id) REFERENCES hefte(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_beitraege_autor
        FOREIGN KEY (autor_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_beitraege_heft_id (heft_id),
    KEY idx_beitraege_autor_id (autor_id),
    KEY idx_beitraege_erstellt_am (erstellt_am)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(2048) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_notifications_user_id (user_id),
    KEY idx_notifications_is_read (is_read),
    KEY idx_notifications_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
