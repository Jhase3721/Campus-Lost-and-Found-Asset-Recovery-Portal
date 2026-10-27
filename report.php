<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
requireLogin();

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
    'report_type' => '',
    'item_name' => '',
    'category' => '',
    'color' => '',
    'location' => '',
    'date' => '',
    'description' => '',
];
$errors = [];
$formError = '';
$submitted = ($_SESSION['report_created'] ?? false) === true;
unset($_SESSION['report_created']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $default) {
        $postedValue = $_POST[$field] ?? '';
        $values[$field] = is_string($postedValue) ? trim($postedValue) : '';
    }

    if (!verifyCsrfToken()) {
        $formError = 'Your form expired. Refresh the page and submit it again.';
    }

    if (!in_array($values['report_type'], ['lost', 'found'], true)) {
        $errors['report_type'] = 'Choose whether the item was lost or found.';
    }

    if ($values['item_name'] === '' || strlen($values['item_name']) > 120) {
        $errors['item_name'] = 'Enter an item name (up to 120 characters).';
    }

    if (!in_array($values['category'], $categories, true)) {
        $errors['category'] = 'Choose a category.';
    }

    if ($values['color'] !== '' && strlen($values['color']) > 60) {
        $errors['color'] = 'Keep the color description to 60 characters or fewer.';
    }

    if (!in_array($values['location'], $locations, true)) {
        $errors['location'] = 'Choose the campus location.';
    }

    $reportDate = DateTimeImmutable::createFromFormat('!Y-m-d', $values['date']);
    if (
        $reportDate === false
        || $reportDate->format('Y-m-d') !== $values['date']
        || $reportDate > new DateTimeImmutable('today')
    ) {
        $errors['date'] = 'Enter a valid date that is not in the future.';
    }

    if ($values['description'] === '' || strlen($values['description']) > 1500) {
        $errors['description'] = 'Add a description (up to 1,500 characters).';
    }

    if ($errors === [] && $formError === '') {
        try {
            $statement = db()->prepare(
                'INSERT INTO reports
                    (user_id, report_type, item_name, category, color, location, event_date, description)
                 VALUES
                    (:user_id, :report_type, :item_name, :category, :color, :location, :event_date, :description)'
            );
            $statement->execute([
                'user_id' => currentUser()['id'],
                'report_type' => $values['report_type'],
                'item_name' => $values['item_name'],
                'category' => $values['category'],
                'color' => $values['color'] !== '' ? $values['color'] : null,
                'location' => $values['location'],
                'event_date' => $values['date'],
                'description' => $values['description'],
            ]);
            $_SESSION['report_created'] = true;
            header('Location: report.php');
            exit;
        } catch (PDOException $exception) {
            error_log('Campus portal could not save a user report.');
            $formError = 'Your report could not be saved right now. Please try again.';
        }
    }
}

function fieldError(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<div class="invalid-feedback d-block">' . escape($errors[$field]) . '</div>'
        : '';
}

function fieldClass(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Report a lost or found item with Campus-Lost-and-Found-Asset-Recovery-Portal.">
    <title>Campus-Lost-and-Found-Asset-Recovery-Portal | Report an item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="brand-mark" aria-hidden="true">C</span>
                <span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <?php if (currentUser()['role'] === 'admin'): ?>
                    <a class="small text-decoration-none" href="admin.php">Admin reports</a>
                <?php endif; ?>
                <span class="navbar-text d-none d-md-inline"><?= escape(currentUser()['email']) ?></span>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <header class="page-heading mb-4">
                    <p class="eyebrow mb-2">CAMPUS LOST &amp; FOUND</p>
                    <h1 class="display-6 fw-bold mb-2">Report an item</h1>
                    <p class="text-secondary mb-0">Share a few details so your campus community can help.</p>
                </header>

                <?php if ($submitted): ?>
                    <section class="alert alert-success border-0 shadow-sm mb-4" aria-live="polite">
                        <h2 class="h5 alert-heading">Your report has been submitted</h2>
                        <p class="mb-0">The campus team can now review your report.</p>
                    </section>
                <?php endif; ?>

                <?php if ($formError !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= escape($formError) ?></div>
                <?php endif; ?>

                <div class="row g-4">
                    <section class="col-lg-8" aria-labelledby="form-title">
                        <div class="card form-card border-0 shadow-sm">
                            <div class="card-body p-4 p-md-5">
                                <h2 id="form-title" class="h4 fw-bold mb-4">Item details</h2>
                                <form method="post" action="report.php" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                    <fieldset class="mb-4">
                                        <legend class="form-label fw-semibold">What are you reporting?</legend>
                                        <div class="d-flex gap-3">
                                            <?php foreach (['lost' => 'I lost an item', 'found' => 'I found an item'] as $type => $label): ?>
                                                <div class="form-check choice-card flex-fill">
                                                    <input class="form-check-input" type="radio" name="report_type"
                                                        id="type-<?= escape($type) ?>" value="<?= escape($type) ?>"
                                                        <?= $values['report_type'] === $type ? 'checked' : '' ?> required>
                                                    <label class="form-check-label fw-medium" for="type-<?= escape($type) ?>">
                                                        <?= escape($label) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?= fieldError($errors, 'report_type') ?>
                                    </fieldset>

                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label fw-semibold" for="item_name">Item name</label>
                                            <input class="form-control<?= fieldClass($errors, 'item_name') ?>" id="item_name"
                                                name="item_name" maxlength="120" value="<?= escape($values['item_name']) ?>"
                                                placeholder="e.g. Blue water bottle" required>
                                            <?= fieldError($errors, 'item_name') ?>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-semibold" for="category">Category</label>
                                            <select class="form-select<?= fieldClass($errors, 'category') ?>" id="category" name="category" required>
                                                <option value="">Choose one</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= escape($category) ?>" <?= $values['category'] === $category ? 'selected' : '' ?>>
                                                        <?= escape($category) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?= fieldError($errors, 'category') ?>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="color">Color <span class="text-secondary fw-normal">(optional)</span></label>
                                            <input class="form-control<?= fieldClass($errors, 'color') ?>" id="color" name="color"
                                                maxlength="60" value="<?= escape($values['color']) ?>" placeholder="e.g. Navy blue">
                                            <?= fieldError($errors, 'color') ?>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="location">Campus location</label>
                                            <select class="form-select<?= fieldClass($errors, 'location') ?>" id="location" name="location" required>
                                                <option value="">Choose a location</option>
                                                <?php foreach ($locations as $location): ?>
                                                    <option value="<?= escape($location) ?>" <?= $values['location'] === $location ? 'selected' : '' ?>>
                                                        <?= escape($location) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?= fieldError($errors, 'location') ?>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="date">Date lost or found</label>
                                            <input class="form-control<?= fieldClass($errors, 'date') ?>" type="date" id="date"
                                                name="date" max="<?= escape((new DateTimeImmutable('today'))->format('Y-m-d')) ?>"
                                                value="<?= escape($values['date']) ?>" required>
                                            <?= fieldError($errors, 'date') ?>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" for="description">Description</label>
                                            <textarea class="form-control<?= fieldClass($errors, 'description') ?>" id="description"
                                                name="description" rows="4" maxlength="1500" required
                                                placeholder="Add details that could help identify the item."><?= escape($values['description']) ?></textarea>
                                            <?= fieldError($errors, 'description') ?>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mt-4">
                                        <button class="btn btn-primary px-4" type="submit">Submit report</button>
                                        <span class="small text-secondary">Your email is shared with campus administrators for report follow-up.</span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <aside class="col-lg-4">
                        <div class="help-card h-100">
                            <div class="help-icon" aria-hidden="true">?</div>
                            <h2 class="h5 fw-bold mt-3">A few helpful tips</h2>
                            <ul class="help-list">
                                <li>Use a clear, common name for the item.</li>
                                <li>Choose the closest campus location and date.</li>
                                <li>Keep identifying details specific but safe to share.</li>
                            </ul>
                            <div class="privacy-note">
                                <strong>Your safety matters.</strong>
                                Avoid including passwords, student ID numbers, or other sensitive information in the description.
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-top bg-white py-3">
        <div class="container small text-secondary"><span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span> · A safer way to reunite people with their belongings.</div>
    </footer>
</body>
</html>
