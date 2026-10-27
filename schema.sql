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
    status ENUM('open', 'claimed', 'returned') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY reports_user_created (user_id, created_at),
    KEY reports_type_location_date (report_type, location, event_date),
    CONSTRAINT reports_user_fk FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;
