CREATE DATABASE IF NOT EXISTS campus_lost_found
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campus_lost_found;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campus_assets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_tag VARCHAR(60) NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    category VARCHAR(60) NOT NULL,
    location VARCHAR(80) NOT NULL,
    qr_token CHAR(64) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY campus_assets_tag_unique (asset_tag),
    UNIQUE KEY campus_assets_qr_token_unique (qr_token),
    KEY campus_assets_created_by (created_by),
    CONSTRAINT campus_assets_creator_fk FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    report_type ENUM('lost', 'found') NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    category VARCHAR(60) NOT NULL,
    color VARCHAR(60) NULL,
    location VARCHAR(80) NOT NULL,
    event_date DATE NOT NULL,
    description VARCHAR(1500) NOT NULL,
    status ENUM('reported', 'found', 'claimed', 'verified', 'returned') NOT NULL DEFAULT 'reported',
    campus_asset_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY reports_user_created (user_id, created_at),
    KEY reports_type_location_date (report_type, location, event_date),
    KEY reports_campus_asset (campus_asset_id),
    CONSTRAINT reports_user_fk FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT reports_campus_asset_fk
        FOREIGN KEY (campus_asset_id) REFERENCES campus_assets (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recovery_activity (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    report_id BIGINT UNSIGNED NOT NULL,
    status ENUM('reported', 'found', 'claimed', 'verified', 'returned') NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    details VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY recovery_activity_report_created (report_id, created_at, id),
    CONSTRAINT recovery_activity_report_fk
        FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE,
    CONSTRAINT recovery_activity_actor_fk
        FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS handover_codes (
    report_id BIGINT UNSIGNED NOT NULL,
    code_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (report_id),
    CONSTRAINT handover_codes_report_fk
        FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE,
    CONSTRAINT handover_codes_creator_fk FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB;
