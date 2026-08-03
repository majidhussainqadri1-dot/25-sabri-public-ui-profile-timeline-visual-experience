<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-section-bootstrap.php';
$registry = new \Sabri\PublicExperience\Section_Registry();
$registry->register(new R3953_Section_Provider('experimental-provider', 'experimental'));
$registry->register(new R3953_Section_Provider('accepted-provider', 'read-only'));
$service = new \Sabri\PublicExperience\Section_Service($registry);
$result = $service->get_public_section(7, ['class' => 'doctor'], 'knowledge');
r3953_section_assert(count($result['items']) === 1 && ($result['items'][0]['title'] ?? '') === 'accepted-provider', 'Experimental section providers must not render publicly.');
$health = $service->public_health();
r3953_section_assert(($health['sections']['knowledge']['enabled'] ?? -1) === 1, 'Public health enabled count must use the public maturity gate.');
echo "PASS: Review 40 profile-section provider maturity gate\n";
