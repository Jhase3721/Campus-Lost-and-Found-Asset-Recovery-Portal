<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/matching.php';
require __DIR__ . '/app/recovery.php';
require __DIR__ . '/app/insights.php';
require __DIR__ . '/app/handover.php';
requireAdmin();

$recoveryNotice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $connection = db();
    $actionInput = $_POST['action'] ?? 'advance';
    $action = is_string($actionInput) ? $actionInput : '';
    $reportIdInput = $_POST['report_id'] ?? '';
    $requestedStatus = $_POST['next_status'] ?? '';

    try {
        if (!verifyCsrfToken()) {
            throw new RuntimeException('Your form expired. Refresh the page and try again.');
        }

        if (
            !is_string($reportIdInput)
            || filter_var($reportIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false
            || !is_string($requestedStatus)
            || !in_array($action, ['advance', 'issue_handover'], true)
        ) {
            throw new RuntimeException('That recovery update was invalid. Please try again.');
        }

        $connection->beginTransaction();
        $currentReportStatement = $connection->prepare(
            'SELECT status FROM reports WHERE id = :id FOR UPDATE'
        );
        $currentReportStatement->execute(['id' => $reportIdInput]);
        $currentStatus = $currentReportStatement->fetchColumn();

        if (!is_string($currentStatus)) {
            throw new RuntimeException('That report could not be found.');
        }

        if ($action === 'issue_handover') {
            if ($currentStatus !== 'verified') {
                throw new RuntimeException('A pickup code can only be issued after a claim is verified.');
            }
            $code = createHandoverCode();
            $expiresAt = storeHandoverCode($connection, (int) $reportIdInput, (int) currentUser()['id'], $code);
            $_SESSION['issued_handover'] = [
                'report_id' => (int) $reportIdInput,
                'code' => $code,
                'expires_at' => $expiresAt,
            ];
        } else {
            $nextStatus = nextRecoveryStatus($currentStatus);
            if ($nextStatus === null || $nextStatus !== $requestedStatus || $nextStatus === 'returned') {
                throw new RuntimeException('This report has already changed. Refresh the page before updating it.');
            }

            $updateStatement = $connection->prepare(
                'UPDATE reports SET status = :status WHERE id = :id'
            );
            $updateStatement->execute([
                'status' => $nextStatus,
                'id' => $reportIdInput,
            ]);
            $activityStatement = $connection->prepare(
                'INSERT INTO recovery_activity (report_id, status, actor_user_id, details)
                 VALUES (:report_id, :status, :actor_user_id, :details)'
            );
            $activityStatement->execute([
                'report_id' => $reportIdInput,
                'status' => $nextStatus,
                'actor_user_id' => currentUser()['id'],
                'details' => recoveryStatusDescription($nextStatus),
            ]);
            if ($nextStatus === 'verified') {
                $code = createHandoverCode();
                $expiresAt = storeHandoverCode($connection, (int) $reportIdInput, (int) currentUser()['id'], $code);
                $_SESSION['issued_handover'] = [
                    'report_id' => (int) $reportIdInput,
                    'code' => $code,
                    'expires_at' => $expiresAt,
                ];
            }
        }
        $connection->commit();
        $_SESSION['recovery_notice'] = $action === 'issue_handover'
            ? 'A new one-time pickup code was issued. Share it privately with the approved claimant.'
            : recoveryStatusLabel($nextStatus) . ' status recorded in the recovery history.';
    } catch (RuntimeException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        unset($_SESSION['issued_handover']);
        $_SESSION['recovery_notice'] = $exception->getMessage();
    } catch (PDOException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        unset($_SESSION['issued_handover']);
        error_log('Campus portal could not update a report recovery status.');
        $_SESSION['recovery_notice'] = 'The recovery update could not be saved. Please try again.';
    }

    header('Location: admin.php');
    exit;
}

$recoveryNotice = is_string($_SESSION['recovery_notice'] ?? null) ? $_SESSION['recovery_notice'] : '';
unset($_SESSION['recovery_notice']);
$issuedHandover = is_array($_SESSION['issued_handover'] ?? null) ? $_SESSION['issued_handover'] : null;
unset($_SESSION['issued_handover']);
$reportActivity = [];
$activeHandoverReports = [];
try {
    $statement = db()->query(
        'SELECT reports.id, reports.report_type, reports.item_name, reports.category, reports.color,
                reports.location, reports.event_date, reports.description, reports.status,
                reports.campus_asset_id, campus_assets.asset_tag,
                reports.created_at, users.email
         FROM reports
         INNER JOIN users ON users.id = reports.user_id
         LEFT JOIN campus_assets ON campus_assets.id = reports.campus_asset_id
         ORDER BY reports.created_at DESC, reports.id DESC'
    );
    $reports = $statement->fetchAll();
    $possibleMatches = findPossibleReportMatches($reports);
    $campusInsights = buildCampusLocationInsights($reports);

    if ($reports !== []) {
        $reportIds = array_column($reports, 'id');
        $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
        $activityStatement = db()->prepare(
            "SELECT recovery_activity.report_id, recovery_activity.status, recovery_activity.details,
                    recovery_activity.created_at, users.email AS actor_email
             FROM recovery_activity
             LEFT JOIN users ON users.id = recovery_activity.actor_user_id
             WHERE recovery_activity.report_id IN ($placeholders)
             ORDER BY recovery_activity.created_at DESC, recovery_activity.id DESC"
        );
        $activityStatement->execute($reportIds);
        foreach ($activityStatement->fetchAll() as $activity) {
            $reportActivity[(int) $activity['report_id']][] = $activity;
        }
        $handoverStatement = db()->query(
            'SELECT report_id FROM handover_codes
             WHERE used_at IS NULL AND expires_at > CURRENT_TIMESTAMP AND attempts < 5'
        );
        foreach ($handoverStatement->fetchAll() as $handoverReport) {
            $activeHandoverReports[(int) $handoverReport['report_id']] = true;
        }
    }
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
                <a class="small text-decoration-none" href="assets.php">Campus assets</a>
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
        <?php if ($recoveryNotice !== ''): ?>
            <div class="alert alert-info" role="status"><?= escape($recoveryNotice) ?></div>
        <?php endif; ?>
        <?php if ($issuedHandover !== null): ?>
            <section class="alert alert-warning handover-issued-alert" role="status">
                <h2 class="h6 fw-bold">One-time pickup code · Report #<?= escape((string) $issuedHandover['report_id']) ?></h2>
                <p class="mb-1">Share this code privately with the approved claimant. It can be used once, expires after 24 hours, and is shown only once here.</p>
                <code class="handover-issued-code"><?= escape($issuedHandover['code']) ?></code>
                <p class="small mb-0 mt-1">Expires <?= escape($issuedHandover['expires_at']) ?></p>
                <a class="small" href="handover.php">Open the claimant pickup confirmation page</a>
            </section>
        <?php endif; ?>
        <section class="campus-insights-section mb-4" aria-labelledby="campus-insights-heading">
            <header class="campus-insights-header">
                <div>
                    <p class="eyebrow mb-2">05 / IMPROVE</p>
                    <h2 id="campus-insights-heading" class="h4 fw-bold mb-1">Campus insights</h2>
                    <p class="text-secondary mb-0">See where campus members report lost and found items most often.</p>
                </div>
                <span class="matches-count"><?= $campusInsights['total_reports'] ?> <?= $campusInsights['total_reports'] === 1 ? 'report' : 'reports' ?></span>
            </header>
            <div class="campus-insights-summary">
                <div class="insight-stat">
                    <span class="insight-stat-label">Lost reports</span>
                    <strong><?= $campusInsights['lost_reports'] ?></strong>
                </div>
                <div class="insight-stat">
                    <span class="insight-stat-label">Found reports</span>
                    <strong><?= $campusInsights['found_reports'] ?></strong>
                </div>
                <div class="insight-stat insight-stat-highlight">
                    <span class="insight-stat-label">Most lost reports</span>
                    <?php if ($campusInsights['top_lost_location'] !== null): ?>
                        <strong><?= escape($campusInsights['top_lost_location']['location']) ?></strong>
                        <small><?= $campusInsights['top_lost_location']['lost'] ?> lost <?= $campusInsights['top_lost_location']['lost'] === 1 ? 'report' : 'reports' ?></small>
                    <?php else: ?>
                        <strong>Not available yet</strong>
                        <small>Lost reports will appear here.</small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($campusInsights['locations'] === []): ?>
                <div class="campus-insights-empty">
                    <h3 class="h6 fw-bold mb-1">No location data yet</h3>
                    <p class="small text-secondary mb-0">Location charts will appear after reports are submitted.</p>
                </div>
            <?php else: ?>
                <div class="campus-insights-chart">
                    <div class="campus-chart-legend" aria-label="Chart legend">
                        <span><i class="legend-dot legend-dot-lost" aria-hidden="true"></i>Lost</span>
                        <span><i class="legend-dot legend-dot-found" aria-hidden="true"></i>Found</span>
                    </div>
                    <ol class="location-chart-list" aria-label="Report totals by campus location, ranked by lost reports">
                        <?php foreach ($campusInsights['locations'] as $location): ?>
                            <li class="location-chart-row">
                                <span class="location-chart-name"><?= escape($location['location']) ?></span>
                                <span class="location-chart-track" role="img" aria-label="<?= escape($location['location']) ?>: <?= $location['lost'] ?> lost and <?= $location['found'] ?> found">
                                    <span class="location-chart-bar" style="width: <?= $location['bar_width'] ?>%">
                                        <?php if ($location['lost'] > 0): ?>
                                            <span class="location-chart-segment location-chart-segment-lost" style="width: <?= $location['lost_width'] ?>%"></span>
                                        <?php endif; ?>
                                        <?php if ($location['found'] > 0): ?>
                                            <span class="location-chart-segment location-chart-segment-found" style="width: <?= 100 - $location['lost_width'] ?>%"></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <span class="location-chart-count"><?= $location['total'] ?> <small><?= $location['total'] === 1 ? 'report' : 'reports' ?></small></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <p class="campus-chart-caption">Bars compare total reports against the busiest location. Locations are ordered by lost report count.</p>
                </div>
            <?php endif; ?>
        </section>
        <section class="possible-matches-section mb-4" aria-labelledby="matches-heading">
            <div class="possible-matches-heading">
                <div>
                    <p class="eyebrow mb-2">SMART ITEM MATCHING</p>
                    <h2 id="matches-heading" class="h4 fw-bold mb-1">Possible matches</h2>
                    <p class="text-secondary mb-0">Open lost and found reports ranked by item name, color, description, place, and date. Scores of 35% or higher are shown, up to 20 pairs; color is skipped when either report leaves it blank.</p>
                </div>
                <span class="matches-count"><?= count($possibleMatches) ?> <?= count($possibleMatches) === 1 ? 'match' : 'matches' ?></span>
            </div>
            <?php if ($possibleMatches === []): ?>
                <div class="matches-empty">
                    <span class="matches-empty-icon" aria-hidden="true">⌕</span>
                    <div>
                        <h3 class="h6 fw-bold mb-1">No strong matches yet</h3>
                        <p class="small text-secondary mb-0">As more open lost and found reports are submitted, promising pairs will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="possible-matches-list">
                    <?php foreach ($possibleMatches as $match): ?>
                        <?php
                        $fieldLabels = [
                            'name' => 'Name',
                            'color' => 'Color',
                            'description' => 'Description',
                            'location' => 'Place',
                            'date' => 'Date',
                        ];
                        ?>
                        <article class="possible-match-card">
                            <header class="possible-match-card-header">
                                <div>
                                    <span class="match-kicker">POSSIBLE PAIR</span>
                                    <h3 class="h6 fw-bold mb-0"><?= escape($match['lost']['item_name']) ?> <span aria-hidden="true">↔</span> <?= escape($match['found']['item_name']) ?></h3>
                                </div>
                                <div class="match-score" aria-label="Similarity score <?= $match['score'] ?> percent">
                                    <strong><?= $match['score'] ?>%</strong>
                                    <span>similarity</span>
                                </div>
                            </header>
                            <div class="possible-match-reports">
                                <?php foreach (['lost' => $match['lost'], 'found' => $match['found']] as $type => $report): ?>
                                    <div class="match-report match-report-<?= escape($type) ?>">
                                        <span class="badge rounded-pill <?= $type === 'lost' ? 'text-bg-warning' : 'text-bg-info' ?>"><?= escape(ucfirst($type)) ?></span>
                                        <p class="match-report-details">
                                            <strong><?= escape($report['item_name']) ?></strong>
                                            <span><?= escape($report['color'] ?: 'Color not specified') ?> · <?= escape($report['location']) ?></span>
                                            <span><?= escape($report['event_date']) ?> · <?= escape($report['email']) ?></span>
                                        </p>
                                        <p class="match-report-description"><?= escape($report['description']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <footer class="match-reasons">
                                <span class="match-reasons-label">Similar details:</span>
                                <?php if ($match['reasons'] === []): ?>
                                    <span class="match-reason">Several details contribute to this score</span>
                                <?php else: ?>
                                    <?php foreach ($match['reasons'] as $reason): ?>
                                        <span class="match-reason"><?= escape($fieldLabels[$reason['field']]) ?> <?= $reason['score'] ?>%</span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
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
                                <th scope="col">Recovery history</th>
                                <th scope="col">Description</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><strong><?= escape($report['item_name']) ?></strong><span class="table-subtext"><?= escape($report['category']) ?><?= $report['color'] !== null && $report['color'] !== '' ? ' · ' . escape($report['color']) : '' ?><?= $report['asset_tag'] !== null ? ' · Asset ' . escape($report['asset_tag']) : '' ?></span></td>
                                        <td><span class="badge rounded-pill <?= $report['report_type'] === 'lost' ? 'text-bg-warning' : 'text-bg-info' ?>"><?= escape(ucfirst($report['report_type'])) ?></span></td>
                                        <td><?= escape($report['email']) ?><span class="table-subtext">Reported <?= escape($report['created_at']) ?></span></td>
                                        <td><?= escape($report['location']) ?><span class="table-subtext"><?= escape($report['event_date']) ?></span></td>
                                        <td>
                                            <?php
                                            $statusIndex = array_search($report['status'], recoveryStatuses(), true);
                                            $statusIndex = $statusIndex === false ? 0 : $statusIndex;
                                            $nextStatus = nextRecoveryStatus($report['status']);
                                            ?>
                                            <span class="recovery-status-badge"><?= escape(recoveryStatusLabel($report['status'])) ?></span>
                                            <ol class="recovery-progress recovery-progress-compact" aria-label="Recovery progress">
                                                <?php foreach (recoveryStatuses() as $index => $status): ?>
                                                    <li class="<?= $index <= $statusIndex ? 'is-complete' : '' ?> <?= $index === $statusIndex ? 'is-current' : '' ?>">
                                                        <span class="recovery-progress-marker"><?= $index < $statusIndex ? '✓' : (string) ($index + 1) ?></span>
                                                        <span><?= escape(recoveryStatusLabel($status)) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ol>
                                            <?php if ($report['status'] === 'verified'): ?>
                                                <form method="post" action="admin.php" class="recovery-update-form">
                                                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="issue_handover">
                                                    <input type="hidden" name="report_id" value="<?= escape((string) $report['id']) ?>">
                                                    <input type="hidden" name="next_status" value="">
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                                        <?= isset($activeHandoverReports[(int) $report['id']]) ? 'Replace pickup code' : 'Issue pickup code' ?>
                                                    </button>
                                                </form>
                                            <?php elseif ($nextStatus !== null && $nextStatus !== 'returned'): ?>
                                                <form method="post" action="admin.php" class="recovery-update-form">
                                                    <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="advance">
                                                    <input type="hidden" name="report_id" value="<?= escape((string) $report['id']) ?>">
                                                    <input type="hidden" name="next_status" value="<?= escape($nextStatus) ?>">
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">Mark as <?= escape(recoveryStatusLabel($nextStatus)) ?></button>
                                                </form>
                                            <?php else: ?>
                                                <span class="recovery-complete-label">Journey complete</span>
                                            <?php endif; ?>
                                            <?php if ($report['status'] === 'verified'): ?>
                                                <p class="recovery-code-hint">The claimant confirms pickup at <a href="handover.php">handover.php</a>.</p>
                                            <?php endif; ?>
                                            <details class="recovery-activity-details">
                                                <summary>Activity record (<?= count($reportActivity[(int) $report['id']] ?? []) ?>)</summary>
                                                <ol class="recovery-activity-list">
                                                    <?php foreach ($reportActivity[(int) $report['id']] ?? [] as $activity): ?>
                                                        <li>
                                                            <span class="recovery-activity-marker" aria-hidden="true"></span>
                                                            <div>
                                                                <strong><?= escape(recoveryStatusLabel($activity['status'])) ?></strong>
                                                                <p><?= escape($activity['details'] ?: recoveryStatusDescription($activity['status'])) ?></p>
                                                                <time datetime="<?= escape(date(DATE_ATOM, strtotime($activity['created_at']))) ?>"><?= escape($activity['created_at']) ?></time>
                                                                <span class="recovery-actor"><?= escape($activity['actor_email'] ?? 'Portal') ?></span>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ol>
                                            </details>
                                        </td>
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
