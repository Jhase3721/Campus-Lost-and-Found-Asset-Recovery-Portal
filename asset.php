<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$codeInput = $_GET['code'] ?? '';
$code = is_string($codeInput) && preg_match('/^[a-f0-9]{64}$/', $codeInput)
    ? $codeInput
    : '';
$asset = false;

if ($code !== '') {
    try {
        $statement = db()->prepare(
            'SELECT id, asset_tag, item_name, category, location
             FROM campus_assets
             WHERE qr_token = :qr_token
             LIMIT 1'
        );
        $statement->execute(['qr_token' => $code]);
        $asset = $statement->fetch();
    } catch (PDOException $exception) {
        error_log('Campus portal could not load an asset QR record.');
        http_response_code(500);
        exit('The asset record could not be loaded. Please try again later.');
    }
}

if ($asset === false) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $asset !== false ? escape($asset['item_name']) . ' | Campus asset' : 'Asset not found' ?></title>
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
        <section class="asset-public-card card border-0 shadow-sm mx-auto">
            <div class="card-body p-4 p-md-5">
                <?php if ($asset === false): ?>
                    <p class="eyebrow mb-2">CAMPUS ASSET</p>
                    <h1 class="h3 fw-bold">Asset not found</h1>
                    <p class="text-secondary mb-0">This QR label may be invalid or no longer active.</p>
                <?php else: ?>
                    <p class="eyebrow mb-2">REGISTERED CAMPUS ASSET</p>
                    <h1 class="h3 fw-bold mb-1"><?= escape($asset['item_name']) ?></h1>
                    <p class="asset-public-tag"><?= escape($asset['asset_tag']) ?></p>
                    <dl class="asset-public-details">
                        <div><dt>Category</dt><dd><?= escape($asset['category']) ?></dd></div>
                        <div><dt>Registered location</dt><dd><?= escape($asset['location']) ?></dd></div>
                    </dl>
                    <p class="text-secondary small">If this asset is lost or found, sign in and submit a report to help campus staff track it.</p>
                    <?php if (currentUser() !== null): ?>
                        <a class="btn btn-primary w-100" href="report.php?asset_code=<?= escape($code) ?>">Report this asset</a>
                    <?php else: ?>
                        <a class="btn btn-primary w-100" href="login.php?next=report.php&amp;asset_code=<?= escape($code) ?>">Sign in to report this asset</a>
                    <?php endif; ?>
                <?php endif; ?>
                <p class="small text-secondary mt-4 mb-0"><a href="index.php">Return to the portal</a></p>
            </div>
        </section>
    </main>
</body>
</html>
