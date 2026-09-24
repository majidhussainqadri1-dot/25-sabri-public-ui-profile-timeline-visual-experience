<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-native-bootstrap.php';
$bad = r3953_assertions(7); $bad['account_class'] = 'Member'; SMC_Contracts::$assertions[7] = $bad;
$bad2 = r3953_assertions(8); $bad2['status'] = ' approved'; SMC_Contracts::$assertions[8] = $bad2;
$native = new \Sabri\PublicExperience\Native_Integration();
r3953_native_assert($native->membership_assertions(7) === [], 'Case aliases in File 00 taxonomy must fail closed.');
r3953_native_assert($native->membership_assertions(8) === [], 'Whitespace aliases in File 00 taxonomy must fail closed.');
echo "PASS: Review 47 exact File 00 taxonomy fields\n";
