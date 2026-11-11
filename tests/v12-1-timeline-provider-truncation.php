<?php
declare(strict_types=1);

/**
 * V12.1: a failed native batch may neither leak rows nor manufacture a
 * 500-item public truncation signal. Uses the real Timeline_Service.
 */
require __DIR__ . '/review39-53-timeline-bootstrap.php';

use Sabri\PublicExperience\Timeline_Registry;
use Sabri\PublicExperience\Timeline_Service;

$rows = [];
for ($i = 1; $i <= 499; ++$i) {
    $rows[] = r3953_item('failed-owner', (string) $i);
}
$rows[] = 'malformed private/native final row';
$registry = new Timeline_Registry();
$registry->register(new R3953_Timeline_Provider('failed-owner', 'read-only', $rows));
$failed = (new Timeline_Service($registry))->get_for_author(7, ['sort' => 'oldest']);
r3953_assert($failed['items'] === [],
    'Atomic failure must discard the full 500-candidate native batch.');
r3953_assert($failed['provider_errors'] === ['failed-owner'],
    'Native owner failure must remain auditable but private error details redacted.');
r3953_assert($failed['truncated'] === false,
    'Failed 500th candidate must not create phantom public truncation.');
r3953_assert($failed['has_more'] === false,
    'Invalid batch must not claim a later public page.');

$registry2 = new Timeline_Registry();
$registry2->register(new R3953_Timeline_Provider('trusted-owner', 'read-only', [
    r3953_item('trusted-owner', 'valid-1'),
]));
$registry2->register(new R3953_Timeline_Provider('failed-owner', 'read-only', $rows));
$partial = (new Timeline_Service($registry2))->get_for_author(7, ['sort' => 'oldest']);
r3953_assert(count($partial['items']) === 1
    && ($partial['items'][0]['title'] ?? '') === 'Public item valid-1',
    'Approved public content from healthy provider survives independent failed source.');
r3953_assert($partial['provider_errors'] === ['failed-owner'] && $partial['truncated'] === false,
    'Partial native outage cannot invent a 500-row bound while one record remains available.');

$valid = [];
for ($i = 1; $i <= 500; ++$i) {
    $valid[] = r3953_item('capped-owner', (string) $i);
}
$registry3 = new Timeline_Registry();
$registry3->register(new R3953_Timeline_Provider('capped-owner', 'read-only', $valid));
$bounded = (new Timeline_Service($registry3))->get_for_author(7, ['sort' => 'oldest']);
r3953_assert(count($bounded['items']) === 20
    && $bounded['has_more'] === true && $bounded['truncated'] === true
    && $bounded['provider_errors'] === [],
    'Entirely valid 500-item source must retain honest truncation and pagination.');

$registry4 = new Timeline_Registry();
$registry4->register(new R3953_Timeline_Provider('short-owner', 'read-only', [
    r3953_item('short-owner', '1'), r3953_item('short-owner', '2'),
]));
$short = (new Timeline_Service($registry4))->get_for_author(7, ['sort' => 'oldest']);
r3953_assert(count($short['items']) === 2 && $short['truncated'] === false,
    'Short approved native source must not be flagged as capped.');

echo "PASS V12.1 Timeline: atomic provider batch and accurate 500-record truncation\n";
