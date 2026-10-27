<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
requireAdmin();

$categories = [
    'Electronics',
    'Books and stationery',
    'Clothing and accessories',
    'Keys and cards',
    'Bags',
    'Other',
];
$locations = [
    'Library',
    'Classroom',
    'Cafeteria',
    'Gym or sports area',
    'Auditorium',
    'Campus grounds',
    'Other',
];
$values = [
    'asset_tag' => '',
    'item_name' => '',
    'category' => '',
    'location' => '',
];
$errors = [];
$notice = is_string($_SESSION['asset_notice'] ?? null) ? $_SESSION['asset_notice'] : '';
unset($_SESSION['asset_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $default) {
        $posted = $_POST[$field] ?? '';
        $values[$field] = is_string($posted) ? trim($posted) : '';
    }

    if (!verifyCsrfToken()) {
        $notice = 'Your form expired. Refresh the page and try again.';
    } else {
        if ($values['asset_tag'] === '' || strlen($values['asset_tag']) > 60) {
            $errors['asset_tag'] = 'Enter an asset tag (up to 60 characters).';
        }
        if ($values['item_name'] === '' || strlen($values['item_name']) > 120) {
            $errors['item_name'] = 'Enter an asset name (up to 120 characters).';
        }
        if (!in_array($values['category'], $categories, true)) {
            $errors['category'] = 'Choose a category.';
        }
        if (!in_array($values['location'], $locations, true)) {
            $errors['location'] = 'Choose the campus location.';
        }

        if ($errors === []) {
            try {
                $statement = db()->prepare(
                    'INSERT INTO campus_assets
                        (asset_tag, item_name, category, location, qr_token, created_by)
                     VALUES
                        (:asset_tag, :item_name, :category, :location, :qr_token, :created_by)'
                );
                $statement->execute([
                    'asset_tag' => $values['asset_tag'],
                    'item_name' => $values['item_name'],
                    'category' => $values['category'],
                    'location' => $values['location'],
                    'qr_token' => bin2hex(random_bytes(32)),
                    'created_by' => currentUser()['id'],
                ]);
                $_SESSION['asset_notice'] = 'Campus asset registered. Print its QR label and attach it to the item.';
                header('Location: assets.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors['asset_tag'] = 'That asset tag is already registered.';
                } else {
                    error_log('Campus portal could not register an asset.');
                    $notice = 'The asset could not be registered. Please try again.';
                }
            }
        }
    }
}

$assets = [];
try {
    $assets = db()->query(
        'SELECT campus_assets.id, campus_assets.asset_tag, campus_assets.item_name,
                campus_assets.category, campus_assets.location, campus_assets.qr_token,
                campus_assets.created_at, users.email AS creator_email
         FROM campus_assets
         INNER JOIN users ON users.id = campus_assets.created_by
         ORDER BY campus_assets.created_at DESC, campus_assets.id DESC'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log('Campus portal could not load registered assets.');
    http_response_code(500);
    exit('Registered assets could not be loaded. Please try again later.');
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registered assets | Campus Lost and Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar bg-white border-bottom">
        <div class="container d-flex flex-wrap gap-3 justify-content-between">
            <a class="navbar-brand fw-bold" href="index.php"><span class="brand-mark" aria-hidden="true">C</span><span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span></a>
            <div class="d-flex align-items-center gap-3">
                <a class="small text-decoration-none" href="admin.php">Admin reports</a>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </nav>
    <main class="container py-5">
        <header class="page-heading mb-4">
            <p class="eyebrow mb-2">06 / ACCOUNT</p>
            <h1 class="display-6 fw-bold mb-2">Registered campus assets</h1>
            <p class="text-secondary mb-0">Register a school asset and create a scannable QR label that links to its campus record.</p>
        </header>
        <?php if ($notice !== ''): ?>
            <div class="alert alert-info" role="status"><?= escape($notice) ?></div>
        <?php endif; ?>
        <div class="row g-4 align-items-start">
            <section class="col-lg-4" aria-labelledby="register-asset-heading">
                <div class="card asset-register-card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 id="register-asset-heading" class="h5 fw-bold mb-3">Register an asset</h2>
                        <form method="post" action="assets.php" class="vstack gap-3">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                            <div>
                                <label for="asset_tag" class="form-label fw-semibold">Asset tag / ID</label>
                                <input class="form-control<?= isset($errors['asset_tag']) ? ' is-invalid' : '' ?>" id="asset_tag"
                                    name="asset_tag" maxlength="60" value="<?= escape($values['asset_tag']) ?>"
                                    placeholder="e.g. LIB-0001" required>
                                <?php if (isset($errors['asset_tag'])): ?><div class="invalid-feedback d-block"><?= escape($errors['asset_tag']) ?></div><?php endif; ?>
                            </div>
                            <div>
                                <label for="item_name" class="form-label fw-semibold">Asset name</label>
                                <input class="form-control<?= isset($errors['item_name']) ? ' is-invalid' : '' ?>" id="item_name"
                                    name="item_name" maxlength="120" value="<?= escape($values['item_name']) ?>"
                                    placeholder="e.g. Projector" required>
                                <?php if (isset($errors['item_name'])): ?><div class="invalid-feedback d-block"><?= escape($errors['item_name']) ?></div><?php endif; ?>
                            </div>
                            <div>
                                <label for="category" class="form-label fw-semibold">Category</label>
                                <select class="form-select<?= isset($errors['category']) ? ' is-invalid' : '' ?>" id="category" name="category" required>
                                    <option value="">Choose one</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= escape($category) ?>" <?= $values['category'] === $category ? 'selected' : '' ?>><?= escape($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['category'])): ?><div class="invalid-feedback d-block"><?= escape($errors['category']) ?></div><?php endif; ?>
                            </div>
                            <div>
                                <label for="location" class="form-label fw-semibold">Campus location</label>
                                <select class="form-select<?= isset($errors['location']) ? ' is-invalid' : '' ?>" id="location" name="location" required>
                                    <option value="">Choose a location</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?= escape($location) ?>" <?= $values['location'] === $location ? 'selected' : '' ?>><?= escape($location) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['location'])): ?><div class="invalid-feedback d-block"><?= escape($errors['location']) ?></div><?php endif; ?>
                            </div>
                            <button class="btn btn-primary" type="submit">Register asset</button>
                        </form>
                    </div>
                </div>
            </section>
            <section class="col-lg-8" aria-labelledby="asset-list-heading">
                <div class="asset-list-heading">
                    <div>
                        <p class="eyebrow mb-2">ASSET DIRECTORY</p>
                        <h2 id="asset-list-heading" class="h4 fw-bold mb-0">QR labels</h2>
                    </div>
                    <span class="matches-count"><?= count($assets) ?> <?= count($assets) === 1 ? 'asset' : 'assets' ?></span>
                </div>
                <?php if ($assets === []): ?>
                    <div class="asset-empty-state">
                        <h3 class="h6 fw-bold mb-1">No assets registered yet</h3>
                        <p class="small text-secondary mb-0">Register an asset to create its QR label.</p>
                    </div>
                <?php else: ?>
                    <div class="asset-label-list">
                        <?php foreach ($assets as $asset): ?>
                            <article class="asset-label-card">
                                <div class="asset-label-details">
                                    <span class="asset-label-tag"><?= escape($asset['asset_tag']) ?></span>
                                    <h3 class="h6 fw-bold mb-1"><?= escape($asset['item_name']) ?></h3>
                                    <p class="small text-secondary mb-0"><?= escape($asset['category']) ?> · <?= escape($asset['location']) ?></p>
                                    <a class="asset-record-link" href="asset.php?code=<?= escape($asset['qr_token']) ?>">Open asset record</a>
                                </div>
                                <div class="asset-qr-wrap">
                                    <img class="asset-qr-image" src="asset_qr.php?id=<?= escape((string) $asset['id']) ?>"
                                        alt="QR code for <?= escape($asset['asset_tag']) ?>" width="180" height="180">
                                    <button class="btn btn-outline-secondary btn-sm mt-2" type="button" onclick="window.print()">Print label</button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
