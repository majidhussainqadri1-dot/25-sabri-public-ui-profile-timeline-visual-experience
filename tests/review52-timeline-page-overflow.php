<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-timeline-bootstrap.php';
$registry = new \Sabri\PublicExperience\Timeline_Registry();
$registry->register(new R3953_Timeline_Provider('accepted-provider', 'read-only', [r3953_item('accepted-provider', '1')]));
$result = (new \Sabri\PublicExperience\Timeline_Service($registry))->get_for_author(7, ['page' => 999, 'per_page' => 20]);
r3953_assert($result['page'] === 999, 'Requested page identity must be preserved.');
r3953_assert($result['items'] === [] && $result['has_more'] === false && $result['truncated'] === true, 'Out-of-range page must be empty and explicitly truncated.');
echo "PASS: Review 52 honest timeline page overflow\n";
