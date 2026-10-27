<?php
declare(strict_types=1);

function createHandoverCode(): string
{
    return strtoupper(bin2hex(random_bytes(6)));
}

function storeHandoverCode(PDO $connection, int $reportId, int $adminId, string $code): string
{
    $expiresAt = (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s');
    $statement = $connection->prepare(
        'INSERT INTO handover_codes
            (report_id, code_hash, expires_at, created_by, attempts, used_at)
         VALUES
            (:report_id, :code_hash, :expires_at, :created_by, 0, NULL)
         ON DUPLICATE KEY UPDATE
            code_hash = VALUES(code_hash),
            expires_at = VALUES(expires_at),
            created_by = VALUES(created_by),
            attempts = 0,
            used_at = NULL,
            created_at = CURRENT_TIMESTAMP'
    );
    $statement->execute([
        'report_id' => $reportId,
        'code_hash' => hash('sha256', $code),
        'expires_at' => $expiresAt,
        'created_by' => $adminId,
    ]);

    return $expiresAt;
}
