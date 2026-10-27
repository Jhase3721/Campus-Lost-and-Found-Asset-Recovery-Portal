<?php
declare(strict_types=1);

function reportTextSimilarity(?string $first, ?string $second): int
{
    $normalize = static function (string $value): string {
        $value = strtolower($value);
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);

        return trim(is_string($normalized) ? $normalized : $value);
    };

    $firstNormalized = $normalize($first ?? '');
    $secondNormalized = $normalize($second ?? '');

    if ($firstNormalized === '' || $secondNormalized === '') {
        return 0;
    }

    if ($firstNormalized === $secondNormalized) {
        return 100;
    }

    $firstTokens = array_unique(explode(' ', $firstNormalized));
    $secondTokens = array_unique(explode(' ', $secondNormalized));
    $sharedTokens = count(array_intersect($firstTokens, $secondTokens));
    $tokenCount = count($firstTokens) + count($secondTokens);

    return $tokenCount === 0 ? 0 : (int) round(200 * $sharedTokens / $tokenCount);
}

function reportDateSimilarity(string $first, string $second): int
{
    $firstDate = DateTimeImmutable::createFromFormat('!Y-m-d', $first);
    $secondDate = DateTimeImmutable::createFromFormat('!Y-m-d', $second);

    if ($firstDate === false || $secondDate === false) {
        return 0;
    }

    $daysApart = $firstDate->diff($secondDate)->days;

    return $daysApart >= 30 ? 0 : (int) round(100 * (30 - $daysApart) / 30);
}

function findPossibleReportMatches(array $reports, int $minimumScore = 35, int $limit = 20): array
{
    $lostReports = [];
    $foundReports = [];

    foreach ($reports as $report) {
        if (!in_array(($report['status'] ?? ''), ['reported', 'found'], true)) {
            continue;
        }

        if (($report['report_type'] ?? '') === 'lost') {
            $lostReports[] = $report;
        } elseif (($report['report_type'] ?? '') === 'found') {
            $foundReports[] = $report;
        }
    }

    $matches = [];
    $weights = [
        'name' => 30,
        'color' => 20,
        'description' => 20,
        'location' => 15,
        'date' => 15,
    ];

    foreach ($lostReports as $lost) {
        foreach ($foundReports as $found) {
            $scores = [
                'name' => reportTextSimilarity((string) $lost['item_name'], (string) $found['item_name']),
                'description' => reportTextSimilarity((string) $lost['description'], (string) $found['description']),
                'location' => reportTextSimilarity((string) $lost['location'], (string) $found['location']),
                'date' => reportDateSimilarity((string) $lost['event_date'], (string) $found['event_date']),
            ];
            $availableWeight = array_sum($weights) - $weights['color'];
            $weightedScore = $scores['name'] * $weights['name']
                + $scores['description'] * $weights['description']
                + $scores['location'] * $weights['location']
                + $scores['date'] * $weights['date'];

            if (
                trim((string) ($lost['color'] ?? '')) !== ''
                && trim((string) ($found['color'] ?? '')) !== ''
            ) {
                $scores['color'] = reportTextSimilarity((string) $lost['color'], (string) $found['color']);
                $weightedScore += $scores['color'] * $weights['color'];
                $availableWeight += $weights['color'];
            }

            $score = (int) round($weightedScore / $availableWeight);
            if ($score < $minimumScore) {
                continue;
            }

            $reasons = [];
            foreach ($scores as $field => $fieldScore) {
                if ($fieldScore >= 50) {
                    $reasons[] = [
                        'field' => $field,
                        'score' => $fieldScore,
                    ];
                }
            }

            $matches[] = [
                'lost' => $lost,
                'found' => $found,
                'score' => $score,
                'reasons' => $reasons,
            ];
        }
    }

    usort($matches, static function (array $first, array $second): int {
        $scoreOrder = $second['score'] <=> $first['score'];
        if ($scoreOrder !== 0) {
            return $scoreOrder;
        }

        return (int) $second['lost']['id'] <=> (int) $first['lost']['id'];
    });

    return array_slice($matches, 0, max(0, $limit));
}
