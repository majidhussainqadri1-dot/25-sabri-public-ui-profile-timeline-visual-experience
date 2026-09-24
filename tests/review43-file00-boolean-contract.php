<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-native-bootstrap.php';
$bad = r3953_assertions(7);
$bad['approved'] = 'false';
SMC_Contracts::$assertions[7] = $bad;
$native = new \Sabri\PublicExperience\Native_Integration();
r3953_native_assert($native->membership_assertions(7) === [], 'String booleans must invalidate File 00 assertions instead of casting truthy.');
echo "PASS: Review 43 strict File 00 boolean assertions\n";
