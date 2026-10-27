<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$error = '';
$next = safeNextPage($_GET['next'] ?? null);
$assetCodeInput = $_GET['asset_code'] ?? '';
$assetCode = is_string($assetCodeInput) && preg_match('/^[a-f0-9]{64}$/', $assetCodeInput)
    ? $assetCodeInput
    : '';
$testAdminEmail = 'admin@gmail.com';
$testAdminEnabled = getenv('CAMPUS_ENABLE_TEST_ADMIN') === '1'
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (currentUser() !== null) {
    $user = currentUser();
    header('Location: ' . (($user['role'] ?? '') === 'admin' ? 'admin.php' : 'report.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next = safeNextPage($_POST['next'] ?? null);
    $postedAssetCode = $_POST['asset_code'] ?? '';
    $assetCode = is_string($postedAssetCode) && preg_match('/^[a-f0-9]{64}$/', $postedAssetCode)
        ? $postedAssetCode
        : '';
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
            if ($destination === 'report.php' && $assetCode !== '') {
                $destination .= '?asset_code=' . rawurlencode($assetCode);
            }
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
<body class="sign-in-page">
    <nav class="navbar sign-in-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><span class="brand-mark" aria-hidden="true">C</span><span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span></a>
            <a class="sign-in-home-link" href="index.php">Back to home <span aria-hidden="true">&rarr;</span></a>
        </div>
    </nav>
    <main class="container sign-in-layout">
        <div class="sign-in-grid">
            <section class="sign-in-welcome" aria-labelledby="welcome-heading">
                <span class="sign-in-pill"><span aria-hidden="true"></span> CAMPUS LOST &amp; FOUND</span>
                <h1 id="welcome-heading">A little help can bring it <span>back home.</span></h1>
                <p class="sign-in-welcome-copy">Sign in to keep track of reports, follow up on lost belongings, and help campus items find their way back.</p>
                <div class="sign-in-steps" aria-label="How recovery works">
                    <div class="sign-in-step">
                        <span class="sign-in-step-number">01</span>
                        <span><strong>Share the details</strong><small>Report what was lost or found.</small></span>
                    </div>
                    <div class="sign-in-step">
                        <span class="sign-in-step-number">02</span>
                        <span><strong>Review possible matches</strong><small>Stay in the loop as reports are reviewed.</small></span>
                    </div>
                    <div class="sign-in-step">
                        <span class="sign-in-step-number">03</span>
                        <span><strong>Reconnect belongings</strong><small>Help items return to their owners.</small></span>
                    </div>
                </div>
                <div class="sign-in-trust"><span aria-hidden="true">✓</span> A shared place for a more helpful campus community</div>
                <span class="sign-in-orbit sign-in-orbit-one" aria-hidden="true"></span>
                <span class="sign-in-orbit sign-in-orbit-two" aria-hidden="true"></span>
            </section>

            <section class="auth-card card border-0" aria-labelledby="sign-in-heading">
                <div class="card-body">
                    <div class="sign-in-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3.5 19 6v5.5c0 4.2-2.8 7.5-7 9-4.2-1.5-7-4.8-7-9V6l7-2.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="eyebrow mb-2">WELCOME BACK</p>
                    <h2 id="sign-in-heading" class="sign-in-heading">Sign in to your account</h2>
                    <p class="sign-in-subtitle">Use your email address to access reports and recovery tools.</p>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
                    <?php endif; ?>
                    <form method="post" action="login.php" class="vstack gap-3">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                        <input type="hidden" name="next" value="<?= escape($next ?? '') ?>">
                        <input type="hidden" name="asset_code" value="<?= escape($assetCode) ?>">
                        <div>
                            <label for="email" class="form-label fw-semibold">Email address</label>
                            <input class="form-control" type="email" id="email" name="email" maxlength="254"
                                value="<?= escape(isset($email) ? $email : '') ?>" autocomplete="email" placeholder="you@example.com" required>
                        </div>
                        <div>
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input class="form-control" type="password" id="password" name="password"
                                autocomplete="current-password" placeholder="Enter your password" required>
                        </div>
                        <button class="btn btn-primary w-100 sign-in-submit" type="submit">Sign in <span aria-hidden="true">&rarr;</span></button>
                    </form>
                    <p class="sign-in-register">New here? <a href="register.php">Create an account</a></p>
                    <div class="sign-in-secure"><span aria-hidden="true">●</span> Your account details are kept secure</div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
