<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-native-bootstrap.php';
global $r3953_decisions, $r3953_snapshots, $r3953_helper;
$assertions = r3953_assertions(7, 'doctor');
$assertions['minor'] = true;
$assertions['guardian_required'] = true;
SMC_Contracts::$assertions[7] = $assertions;
$r3953_decisions[7] = r3953_valid_decision('a');
$r3953_snapshots[7] = r3953_snapshot();
$r3953_helper[7] = true;
$native = new \Sabri\PublicExperience\Native_Integration();
r3953_native_assert(! $native->is_verified_doctor(7), 'Minor/guardian contradiction must block Doctor public eligibility.');
r3953_native_assert($native->is_minor(7), 'Explicit File 00 minor truth must outrank Doctor presentation class.');
echo "PASS: Review 45 minor and Doctor contradiction fails closed\n";
