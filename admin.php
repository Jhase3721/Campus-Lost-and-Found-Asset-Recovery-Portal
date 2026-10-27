<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
requireAdmin();

try {
    $statement = db()->query(
        'SELECT reports.id, reports.report_type, reports.item_name, reports.category, reports.color,
                reports.location, reports.event_date, reports.description, reports.status,
                reports.created_at, users.email
         FROM reports
         INNER JOIN users ON users.id = reports.user_id
         ORDER BY reports.created_at DESC, reports.id DESC'
    );
    $reports = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Campus portal could not load reports for the administrator.');
    http_response_code(500);
    exit('Reports could not be loaded. Please try again later.');
}
$admin = currentUser();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin reports | Campus-Lost-and-Found-Asset-Recovery-Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar bg-white border-bottom">
        <div class="container d-flex flex-wrap gap-3 justify-content-between">
            <a class="navbar-brand fw-bold" href="index.php"><span class="brand-mark" aria-hidden="true">C</span><span class="brand-name">Campus-Lost-and-Found-Asset-Recovery-Portal</span></a>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-secondary d-none d-sm-inline"><?= escape($admin['email']) ?> · Admin</span>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </nav>
    <main class="container py-5">
        <header class="page-heading mb-4">
            <p class="eyebrow mb-2">ADMINISTRATION</p>
            <h1 class="display-6 fw-bold mb-2">User reports</h1>
            <p class="text-secondary mb-0">Review lost and found items submitted by campus users.</p>
        </header>
        <section class="card admin-table-card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if ($reports === []): ?>
                    <div class="empty-state text-center p-5">
                        <h2 class="h5 fw-bold">No reports yet</h2>
                        <p class="text-secondary mb-0">Submitted reports will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr>
                                <th scope="col">Item</th>
                                <th scope="col">Type</th>
                                <th scope="col">Submitted by</th>
                                <th scope="col">Location / date</th>
                                <th scope="col">Status</th>
                                <th scope="col">Description</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><strong><?= escape($report['item_name']) ?></strong><span class="table-subtext"><?= escape($report['category']) ?><?= $report['color'] !== null && $report['color'] !== '' ? ' · ' . escape($report['color']) : '' ?></span></td>
                                        <td><span class="badge rounded-pill <?= $report['report_type'] === 'lost' ? 'text-bg-warning' : 'text-bg-info' ?>"><?= escape(ucfirst($report['report_type'])) ?></span></td>
                                        <td><?= escape($report['email']) ?><span class="table-subtext">Reported <?= escape($report['created_at']) ?></span></td>
                                        <td><?= escape($report['location']) ?><span class="table-subtext"><?= escape($report['event_date']) ?></span></td>
                                        <td><span class="badge rounded-pill text-bg-secondary"><?= escape(ucfirst($report['status'])) ?></span></td>
                                        <td class="report-description"><?= escape($report['description']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
