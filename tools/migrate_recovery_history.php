<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/recovery.php';

try {
    $columnStatement = db()->prepare(
        'SELECT COLUMN_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = \'reports\'
           AND COLUMN_NAME = \'status\''
    );
    $columnStatement->execute();
    $columnType = $columnStatement->fetchColumn();
    if (!is_string($columnType)) {
        throw new RuntimeException('The reports.status column was not found. Import database/schema.sql first.');
    }

    foreach (recoveryStatuses() as $status) {
        if (!str_contains($columnType, "'" . $status . "'")) {
            $hasCurrentStatusSet = false;
            break;
        }
        $hasCurrentStatusSet = true;
    }

    if (!$hasCurrentStatusSet) {
        db()->exec(
            "ALTER TABLE reports
             MODIFY status ENUM('open', 'reported', 'found', 'claimed', 'verified', 'returned')
             NOT NULL DEFAULT 'reported'"
        );
        db()->exec("UPDATE reports SET status = 'reported' WHERE status = 'open'");
        db()->exec(
            "ALTER TABLE reports
             MODIFY status ENUM('reported', 'found', 'claimed', 'verified', 'returned')
             NOT NULL DEFAULT 'reported'"
        );
    }

    db()->exec(
        'CREATE TABLE IF NOT EXISTS recovery_activity (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT UNSIGNED NOT NULL,
            status ENUM(\'reported\', \'found\', \'claimed\', \'verified\', \'returned\') NOT NULL,
            actor_user_id BIGINT UNSIGNED NULL,
            details VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recovery_activity_report_created (report_id, created_at, id),
            CONSTRAINT recovery_activity_report_fk
                FOREIGN KEY (report_id) REFERENCES reports (id) ON DELETE CASCADE,
            CONSTRAINT recovery_activity_actor_fk
                FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );

    $backfill = db()->prepare(
        'INSERT INTO recovery_activity (report_id, status, actor_user_id, details, created_at)
         SELECT reports.id, reports.status, NULL, :details, reports.created_at
         FROM reports
         WHERE NOT EXISTS (
             SELECT 1 FROM recovery_activity
             WHERE recovery_activity.report_id = reports.id
         )'
    );
    $backfill->execute(['details' => 'Status imported during recovery history setup']);

    fwrite(STDOUT, "Recovery history is ready. Existing reports were preserved.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Recovery history migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
