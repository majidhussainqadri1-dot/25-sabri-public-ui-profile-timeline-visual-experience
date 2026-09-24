<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-timeline-bootstrap.php';
$registry = new \Sabri\PublicExperience\Timeline_Registry();
$registry->register(new R3953_Timeline_Provider('atomic-provider', 'read-only', [r3953_item('atomic-provider', '1'), 'invalid-late-item']));
$result = (new \Sabri\PublicExperience\Timeline_Service($registry))->get_for_author(7);
r3953_assert($result['items'] === [], 'A malformed late item must discard the complete provider batch.');
r3953_assert($result['provider_errors'] === ['atomic-provider'], 'Atomic provider failure must be observable without leaking details.');
echo "PASS: Review 53 transactional timeline provider validation\n";
