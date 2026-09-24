<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-timeline-bootstrap.php';
$registry = new \Sabri\PublicExperience\Timeline_Registry();
$registry->register(new R3953_Timeline_Provider('detected-provider', 'detected', [r3953_item('detected-provider', '1')]));
$registry->register(new R3953_Timeline_Provider('degraded-provider', 'degraded', [r3953_item('degraded-provider', '2')]));
$registry->register(new R3953_Timeline_Provider('accepted-provider', 'read-only', [r3953_item('accepted-provider', '3')]));
$result = (new \Sabri\PublicExperience\Timeline_Service($registry))->get_for_author(7);
r3953_assert(count($result['items']) === 1 && ($result['items'][0]['title'] ?? '') === 'Public item 3', 'Only public-usable provider maturity may render.');
echo "PASS: Review 39 timeline provider maturity gate\n";
