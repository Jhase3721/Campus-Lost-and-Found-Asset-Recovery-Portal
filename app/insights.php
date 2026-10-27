<?php
declare(strict_types=1);

function buildCampusLocationInsights(array $reports): array
{
    $locations = [];

    foreach ($reports as $report) {
        $location = trim((string) ($report['location'] ?? ''));
        $reportType = $report['report_type'] ?? '';

        if ($location === '' || !in_array($reportType, ['lost', 'found'], true)) {
            continue;
        }

        if (!isset($locations[$location])) {
            $locations[$location] = [
                'location' => $location,
                'lost' => 0,
                'found' => 0,
                'total' => 0,
            ];
        }

        $locations[$location][$reportType]++;
        $locations[$location]['total']++;
    }

    $locations = array_values($locations);
    usort($locations, static function (array $first, array $second): int {
        $lostOrder = $second['lost'] <=> $first['lost'];
        if ($lostOrder !== 0) {
            return $lostOrder;
        }

        $totalOrder = $second['total'] <=> $first['total'];
        if ($totalOrder !== 0) {
            return $totalOrder;
        }

        return strcasecmp($first['location'], $second['location']);
    });

    $maximumTotal = 0;
    foreach ($locations as $location) {
        $maximumTotal = max($maximumTotal, $location['total']);
    }

    foreach ($locations as &$location) {
        $location['bar_width'] = $maximumTotal === 0
            ? 0
            : (int) round($location['total'] * 100 / $maximumTotal);
        $location['lost_width'] = $location['total'] === 0
            ? 0
            : (int) round($location['lost'] * 100 / $location['total']);
    }
    unset($location);

    $lostCount = array_sum(array_column($locations, 'lost'));
    $foundCount = array_sum(array_column($locations, 'found'));
    $topLostLocation = null;
    foreach ($locations as $location) {
        if ($location['lost'] > 0) {
            $topLostLocation = $location;
            break;
        }
    }

    return [
        'locations' => $locations,
        'total_reports' => $lostCount + $foundCount,
        'lost_reports' => $lostCount,
        'found_reports' => $foundCount,
        'top_lost_location' => $topLostLocation,
    ];
}
