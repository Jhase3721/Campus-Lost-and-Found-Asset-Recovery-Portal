<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

try {
    db()->exec(
        'CREATE TABLE IF NOT EXISTS campus_assets (
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
            CONSTRAINT campus_assets_creator_fk
                FOREIGN KEY (created_by) REFERENCES users (id)
        ) ENGINE=InnoDB'
    );

    $reportColumn = db()->prepare(
        'SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = \'reports\'
           AND COLUMN_NAME = \'campus_asset_id\''
    );
    $reportColumn->execute();
    if ($reportColumn->fetchColumn() === false) {
        db()->exec(
            'ALTER TABLE reports
             ADD COLUMN campus_asset_id BIGINT UNSIGNED NULL,
             ADD KEY reports_campus_asset (campus_asset_id),
             ADD CONSTRAINT reports_campus_asset_fk
                 FOREIGN KEY (campus_asset_id) REFERENCES campus_assets (id) ON DELETE SET NULL'
        );
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS handover_codes (
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
            CONSTRAINT handover_codes_creator_fk
                FOREIGN KEY (created_by) REFERENCES users (id)
        ) ENGINE=InnoDB'
    );

    fwrite(STDOUT, "Asset QR and handover tables are ready.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Asset and handover migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
