<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$errors = [];
$email = '';

if (currentUser() !== null) {
    $user = currentUser();
    header('Location: ' . (($user['role'] ?? '') === 'admin' ? 'admin.php' : 'report.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedEmail = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['password_confirmation'] ?? '';
    $email = is_string($postedEmail) ? strtolower(trim($postedEmail)) : '';

    if (!verifyCsrfToken()) {
        $errors[] = 'Your registration form expired. Refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!is_string($password) || strlen($password) < 12) {
        $errors[] = 'Choose a password with at least 12 characters.';
    } elseif (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
        $errors[] = 'The password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            $statement = db()->prepare(
                'INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, \'user\')'
            );
            $statement->execute([
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            loginUser([
                'id' => (int) db()->lastInsertId(),
                'email' => $email,
                'role' => 'user',
            ]);
            header('Location: report.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'An account with that email already exists. Try signing in instead.';
            } else {
                throw $exception;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Campus-Lost-and-Found-Asset-Recovery-Portal</title>
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
                <p class="eyebrow mb-2">JOIN YOUR CAMPUS</p>
                <h1 class="h3 fw-bold mb-2">Create your account</h1>
                <p class="text-secondary mb-4">Register with your email to submit and track lost-and-found reports.</p>
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" action="register.php" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <div>
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input class="form-control" type="email" id="email" name="email" maxlength="254"
                            value="<?= escape($email) ?>" autocomplete="email" required>
                    </div>
                    <div>
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input class="form-control" type="password" id="password" name="password"
                            minlength="12" autocomplete="new-password" required>
                        <div class="form-text">Use at least 12 characters.</div>
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm password</label>
                        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation"
                            minlength="12" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-primary w-100 mt-2" type="submit">Create account</button>
                </form>
                <p class="text-secondary small mt-4 mb-0">Already registered? <a href="login.php">Sign in</a></p>
            </div>
        </section>
    </main>
</body>
</html>
