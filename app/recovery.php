<?php
declare(strict_types=1);

function recoveryStatuses(): array
{
    return ['reported', 'found', 'claimed', 'verified', 'returned'];
}

function recoveryStatusLabel(string $status): string
{
    return match ($status) {
        'reported' => 'Reported',
        'found' => 'Found',
        'claimed' => 'Claimed',
        'verified' => 'Verified',
        'returned' => 'Returned',
        default => 'Unknown',
    };
}

function nextRecoveryStatus(string $status): ?string
{
    $statuses = recoveryStatuses();
    $index = array_search($status, $statuses, true);

    return $index === false || !isset($statuses[$index + 1])
        ? null
        : $statuses[$index + 1];
}

function recoveryStatusDescription(string $status): string
{
    return match ($status) {
        'reported' => 'The item report was submitted.',
        'found' => 'A matching item has been found.',
        'claimed' => 'A claim has been submitted.',
        'verified' => 'The claim has been verified.',
        'returned' => 'The item has been returned.',
        default => 'The recovery status was updated.',
    };
}
