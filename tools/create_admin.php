<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

$email = getenv('CAMPUS_ADMIN_EMAIL');
$password = getenv('CAMPUS_ADMIN_PASSWORD');

if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Set CAMPUS_ADMIN_EMAIL to a valid email address before running this script.\n");
    exit(1);
}

if (!is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "Set CAMPUS_ADMIN_PASSWORD to a password with at least 12 characters.\n");
    exit(1);
}

try {
    $statement = db()->prepare(
        'INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, \'admin\')'
    );
    $statement->execute([
        'email' => strtolower($email),
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "An account with that email already exists. Admin roles must be granted by a database administrator.\n");
        exit(1);
    }

    throw $exception;
}

fwrite(STDOUT, "Administrator account created for {$email}.\n");
