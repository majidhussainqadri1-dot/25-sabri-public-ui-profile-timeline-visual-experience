<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-native-bootstrap.php';
$base = r3953_assertions(1);
$base['institutional_account'] = true;
$base['account_class'] = 'founder';
$base['membership_type'] = '';
SMC_Contracts::$assertions[1] = $base;
r3953_native_assert((new \Sabri\PublicExperience\Native_Integration())->public_visibility(1) === 'public', 'Eligible Founder should remain public.');
$blocked = $base; $blocked['suspended'] = true; SMC_Contracts::$assertions[1] = $blocked;
r3953_native_assert((new \Sabri\PublicExperience\Native_Integration())->public_visibility(1) === 'private', 'Suspended Founder must fail closed.');
$blocked = $base; $blocked['status'] = 'rejected'; SMC_Contracts::$assertions[1] = $blocked;
r3953_native_assert((new \Sabri\PublicExperience\Native_Integration())->public_visibility(1) === 'private', 'Hard-blocked Founder status must fail closed.');
$blocked = $base; $blocked['public_profile_allowed'] = false; SMC_Contracts::$assertions[1] = $blocked;
r3953_native_assert((new \Sabri\PublicExperience\Native_Integration())->public_visibility(1) === 'private', 'Explicit public-profile denial must apply to Founder.');
SMC_Contracts::$assertions[1] = [];
r3953_native_assert((new \Sabri\PublicExperience\Native_Integration())->public_visibility(1) === 'private', 'Missing Founder assertions must fail closed.');
echo "PASS: Review 44 Founder hard-block public visibility\n";
