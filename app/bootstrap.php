<?php
declare(strict_types=1);

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('campus_portal_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('CAMPUS_DB_HOST') ?: '127.0.0.1';
    $name = getenv('CAMPUS_DB_NAME') ?: 'campus_lost_found';
    $user = getenv('CAMPUS_DB_USER') ?: 'root';
    $password = getenv('CAMPUS_DB_PASSWORD');
    $password = $password === false ? '' : $password;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
    $connection = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';

    return is_string($submittedToken)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $submittedToken);
}

function currentUser(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function requireLogin(): void
{
    if (currentUser() === null) {
        header('Location: login.php?next=report.php');
        exit;
    }
}

function requireAdmin(): void
{
    $user = currentUser();
    if ($user === null) {
        header('Location: login.php?next=admin.php');
        exit;
    }

    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
    unset($_SESSION['csrf_token']);
}

function safeNextPage(mixed $next): ?string
{
    if (!is_string($next) || !in_array($next, ['report.php', 'admin.php'], true)) {
        return null;
    }

    return $next;
}

function databaseUnavailable(): never
{
    error_log('Campus portal could not connect to its MySQL database.');
    http_response_code(503);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Database setup required</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/styles.css" rel="stylesheet">
    </head>
    <body>
        <main class="container py-5">
            <section class="alert alert-warning border-0 shadow-sm">
                <h1 class="h4">Database setup required</h1>
                <p class="mb-0">The portal cannot connect to MySQL. Check the database settings and import <code>database/schema.sql</code>, then try again.</p>
            </section>
        </main>
    </body>
    </html>
    <?php
    exit;
}

try {
    db();
} catch (PDOException $exception) {
    databaseUnavailable();
}
