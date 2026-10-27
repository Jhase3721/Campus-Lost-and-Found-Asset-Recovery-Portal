<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$error = '';
$next = safeNextPage($_GET['next'] ?? null);
$testAdminEmail = 'admin@gmail.com';
$testAdminPassword = 'admin123456789';
$testAdminEnabled = getenv('CAMPUS_ENABLE_TEST_ADMIN') === '1'
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (currentUser() !== null) {
    $user = currentUser();
    header('Location: ' . (($user['role'] ?? '') === 'admin' ? 'admin.php' : 'report.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next = safeNextPage($_POST['next'] ?? null);
    $emailInput = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';

    if (!verifyCsrfToken()) {
        $error = 'Your sign-in form expired. Refresh the page and try again.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !is_string($password)
        || ($email === $testAdminEmail && !$testAdminEnabled)
    ) {
        $error = 'Email or password is incorrect.';
    } else {
        $statement = db()->prepare('SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            $error = 'Email or password is incorrect.';
        } else {
            loginUser($user);
            $destination = $next ?? (($user['role'] === 'admin') ? 'admin.php' : 'report.php');
            header('Location: ' . $destination);
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Campus-Lost-and-Found-Asset-Recovery-Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><span class="brand-mark" aria-hidden="true">C</span><span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span></a>
        </div>
    </nav>
    <main class="container auth-layout py-5">
        <section class="auth-card card border-0 shadow-sm mx-auto">
            <div class="card-body p-4 p-md-5">
                <p class="eyebrow mb-2">WELCOME BACK</p>
                <h1 class="h3 fw-bold mb-2">Sign in to your account</h1>
                <p class="text-secondary mb-4">Use your email address to access reports and recovery tools.</p>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
                <?php endif; ?>
                <form method="post" action="login.php" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <input type="hidden" name="next" value="<?= escape($next ?? '') ?>">
                    <div>
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="254"
                            value="<?= escape(isset($email) ? $email : '') ?>" autocomplete="email" required>
                    </div>
                    <div>
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input class="form-control" type="password" id="password" name="password"
                            autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary w-100 mt-2" type="submit">Sign in</button>
                </form>
                <p class="text-secondary small mt-4 mb-0">New here? <a href="register.php">Create an account</a></p>
            </div>
        </section>
    </main>
</body>
</html>
