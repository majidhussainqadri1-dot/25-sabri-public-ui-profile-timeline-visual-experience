<?php
declare(strict_types=1);

require __DIR__ . '/review39-53-timeline-bootstrap.php';

use Sabri\PublicExperience\Normalized_Timeline_Item;

// ISO timestamps are allowed to carry fractional seconds; earlier updates
// within one second must not be accepted after UTC-second serialization.
$invalid_pairs = [
    ['2026-10-12T12:30:00.900Z', '2026-10-12T12:30:00.100Z'],
    ['2026-10-12T12:30:00.900Z', '2026-10-12T17:30:00.100+05:00'],
    ['2026-10-12T23:59:59.999Z', '2026-10-12T23:59:59.001Z'],
];
foreach ($invalid_pairs as $i => [$published, $updated]) {
    $rejected = false;
    try {
        r3953_item('precision', (string) ($i + 1), [
            'published_at' => $published,
            'updated_at' => $updated,
        ]);
    } catch (InvalidArgumentException $exception) {
        $rejected = str_contains($exception->getMessage(), 'cannot precede');
    }
    r3953_assert($rejected,
        'Timeline must reject a truly earlier subsecond update even if canonical UTC-second strings tie: ' . $i);
}

$valid = r3953_item('precision', '4', [
    'published_at' => '2026-10-12T17:30:00.100+05:00',
    'updated_at' => '2026-10-12T12:30:00.900Z',
]);
r3953_assert($valid instanceof Normalized_Timeline_Item
    && $valid->get('published_at') === '2026-10-12T12:30:00Z'
    && $valid->get('updated_at') === '2026-10-12T12:30:00Z',
    'Valid cross-offset subsecond update must survive while public UTC-seconds wire contract stays unchanged.');

$validSeconds = r3953_item('precision', '5', [
    'published_at' => '2026-10-12 12:30:00',
    'updated_at' => '2026-10-12T12:30:00Z',
]);
r3953_assert($validSeconds->get('published_at') === '2026-10-12T12:30:00Z',
    'Legacy SQL UTC publication dates remain accepted at equal instants.');

echo "PASS: File25 timeline fractional-second chronology/offset regression\n";
