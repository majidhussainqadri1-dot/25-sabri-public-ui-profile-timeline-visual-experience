<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
require_once __DIR__ . '/../includes/class-section-service.php';

use Sabri\PublicExperience\Section_Service;

$method = new ReflectionMethod(Section_Service::class, 'profile_cache_digest');
$method->setAccessible(true);

$profile_a = [
    'class' => 'doctor',
    'display_name' => 'Doctor One',
    'contacts' => ['phone' => '+923001234567', 'whatsapp' => '+923001234567'],
    'clinic' => ['city' => 'Gujrat', 'country' => 'Pakistan'],
    'professional' => ['specialization' => 'Classical Homeopathy'],
];
$profile_same = [
    'professional' => ['specialization' => 'Classical Homeopathy'],
    'clinic' => ['country' => 'Pakistan', 'city' => 'Gujrat'],
    'contacts' => ['whatsapp' => '+923001234567', 'phone' => '+923001234567'],
    'display_name' => 'Doctor One',
    'class' => 'doctor',
];
$profile_changed = $profile_a;
$profile_changed['contacts']['phone'] = '+923009999999';

$digest_a = (string) $method->invoke(null, $profile_a);
$digest_same = (string) $method->invoke(null, $profile_same);
$digest_changed = (string) $method->invoke(null, $profile_changed);

foreach ([$digest_a, $digest_same, $digest_changed] as $digest) {
    if (preg_match('/^[a-f0-9]{64}$/', $digest) !== 1) {
        fwrite(STDERR, "Section cache digest is not a SHA-256 identity.\n");
        exit(1);
    }
}
if (! hash_equals($digest_a, $digest_same)) {
    fwrite(STDERR, "Equivalent associative profile projections produced different cache identities.\n");
    exit(1);
}
if (hash_equals($digest_a, $digest_changed)) {
    fwrite(STDERR, "Materially changed public profile projection reused the previous cache identity.\n");
    exit(1);
}

$deep = ['root' => []];
$cursor =& $deep['root'];
for ($i = 0; $i < 20; $i++) {
    $cursor['level_' . $i] = [];
    $cursor =& $cursor['level_' . $i];
}
$deep_digest = (string) $method->invoke(null, $deep);
if (preg_match('/^[a-f0-9]{64}$/', $deep_digest) !== 1) {
    fwrite(STDERR, "Bounded deep projection did not produce a valid cache identity.\n");
    exit(1);
}

echo "Review 23 section cache binding checks passed.\n";
