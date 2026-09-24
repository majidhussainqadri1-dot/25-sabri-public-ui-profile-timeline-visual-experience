<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-native-bootstrap.php';
global $r3953_decisions;
$cases = [
    7 => r3953_valid_decision('a'),
    8 => ['state' => 'verified', 'verified' => 'true', 'verified_until' => '2099-12-31', 'fingerprint' => str_repeat('b', 64)],
    9 => ['state' => 'Verified', 'verified' => true, 'verified_until' => '2099-12-31', 'fingerprint' => str_repeat('c', 64)],
    10 => ['state' => 'verified', 'verified' => true, 'verified_until' => '2099-12-31', 'fingerprint' => strtoupper(str_repeat('d', 64))],
    11 => ['state' => 'verified', 'verified' => true, 'verified_until' => '2099-12-31 extra', 'fingerprint' => str_repeat('e', 64)],
];
$r3953_decisions = $cases;
$native = new \Sabri\PublicExperience\Native_Integration();
r3953_native_assert(($native->doctor_verification_decision(7)['verified'] ?? false) === true, 'Exact File 09 decision should pass.');
foreach ([8,9,10,11] as $id) {
    r3953_native_assert($native->doctor_verification_decision($id) === [], 'Malformed File 09 decision representation must fail closed for ID ' . $id);
}
echo "PASS: Review 46 exact File 09 decision representation\n";
