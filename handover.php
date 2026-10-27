<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/recovery.php';

$error = '';
$success = false;
$reportId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportIdInput = $_POST['report_id'] ?? '';
    $codeInput = $_POST['code'] ?? '';
    $reportId = is_string($reportIdInput) ? trim($reportIdInput) : '';
    $code = is_string($codeInput) ? strtoupper(trim($codeInput)) : '';

    if (!verifyCsrfToken()) {
        $error = 'Your form expired. Refresh the page and try again.';
    } elseif (
        filter_var($reportId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
        || !preg_match('/^[A-F0-9]{12}$/', $code)
    ) {
        $error = 'That report number or pickup code is invalid.';
    } else {
        $connection = db();
        try {
            $connection->beginTransaction();
            $statement = $connection->prepare(
                'SELECT reports.status, handover_codes.code_hash, handover_codes.expires_at,
                        handover_codes.attempts, handover_codes.used_at
                 FROM reports
                 LEFT JOIN handover_codes ON handover_codes.report_id = reports.id
                 WHERE reports.id = :report_id
                 FOR UPDATE'
            );
            $statement->execute(['report_id' => $reportId]);
            $handover = $statement->fetch();
            $validCode = $handover !== false
                && $handover['status'] === 'verified'
                && is_string($handover['code_hash'])
                && $handover['used_at'] === null
                && (int) $handover['attempts'] < 5
                && strtotime((string) $handover['expires_at']) > time()
                && hash_equals($handover['code_hash'], hash('sha256', $code));

            if (!$validCode) {
                if (
                    $handover !== false
                    && $handover['status'] === 'verified'
                    && $handover['used_at'] === null
                    && (int) $handover['attempts'] < 5
                ) {
                    $attempt = $connection->prepare(
                        'UPDATE handover_codes
                         SET attempts = attempts + 1
                         WHERE report_id = :report_id AND attempts < 5'
                    );
                    $attempt->execute(['report_id' => $reportId]);
                }
                $connection->commit();
                $error = 'That report number or pickup code is invalid, expired, or already used.';
            } else {
                $update = $connection->prepare(
                    'UPDATE reports SET status = \'returned\' WHERE id = :report_id AND status = \'verified\''
                );
                $update->execute(['report_id' => $reportId]);
                if ($update->rowCount() !== 1) {
                    throw new RuntimeException('This pickup has already been confirmed.');
                }
                $markUsed = $connection->prepare(
                    'UPDATE handover_codes SET used_at = CURRENT_TIMESTAMP WHERE report_id = :report_id'
                );
                $markUsed->execute(['report_id' => $reportId]);
                $activity = $connection->prepare(
                    'INSERT INTO recovery_activity (report_id, status, actor_user_id, details)
                     VALUES (:report_id, \'returned\', NULL, :details)'
                );
                $activity->execute([
                    'report_id' => $reportId,
                    'details' => recoveryStatusDescription('returned') . ' Confirmed with a one-time pickup code.',
                ]);
                $connection->commit();
                $success = true;
            }
        } catch (RuntimeException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Campus portal could not confirm a handover.');
            $error = 'The pickup could not be confirmed right now. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm pickup | Campus Lost and Found</title>
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
        <section class="handover-card card border-0 shadow-sm mx-auto">
            <div class="card-body p-4 p-md-5">
                <?php if ($success): ?>
                    <p class="eyebrow mb-2">PICKUP CONFIRMED</p>
                    <h1 class="h3 fw-bold">The item has been returned</h1>
                    <p class="text-secondary mb-0">The one-time pickup code has been used, and the recovery record is complete.</p>
                <?php else: ?>
                    <p class="eyebrow mb-2">APPROVED PICKUP</p>
                    <h1 class="h3 fw-bold mb-2">Confirm item pickup</h1>
                    <p class="text-secondary mb-4">Enter the report number and one-time code provided by the campus administrator.</p>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
                    <?php endif; ?>
                    <form method="post" action="handover.php" class="vstack gap-3">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                        <div>
                            <label for="report_id" class="form-label fw-semibold">Report number</label>
                            <input class="form-control" type="number" id="report_id" name="report_id" min="1"
                                value="<?= escape($reportId) ?>" required>
                        </div>
                        <div>
                            <label for="code" class="form-label fw-semibold">One-time pickup code</label>
                            <input class="form-control text-uppercase" type="text" id="code" name="code"
                                minlength="12" maxlength="12" pattern="[A-Fa-f0-9]{12}" autocomplete="one-time-code" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Confirm pickup</button>
                    </form>
                <?php endif; ?>
                <p class="small text-secondary mt-4 mb-0"><a href="index.php">Return to the portal</a></p>
            </div>
        </section>
    </main>
</body>
</html>
