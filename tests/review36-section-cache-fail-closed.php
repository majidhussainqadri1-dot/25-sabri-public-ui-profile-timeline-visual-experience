<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/class-section-service.php';

$method = new ReflectionMethod(\Sabri\PublicExperience\Section_Service::class, 'profile_cache_digest');
$method->setAccessible(true);

$a = [
    'slug' => 'doctor-one',
    'class' => 'doctor',
    'contacts' => ['phone' => '+923001234567'],
    'professional' => ['languages' => ['Urdu', 'English']],
];
$b = [
    'professional' => ['languages' => ['Urdu', 'English']],
    'contacts' => ['phone' => '+923001234567'],
    'class' => 'doctor',
    'slug' => 'doctor-one',
];
$digest_a = $method->invoke(null, $a);
$digest_b = $method->invoke(null, $b);
if (! is_string($digest_a) || strlen($digest_a) !== 64 || $digest_a !== $digest_b) {
    fwrite(STDERR, "Review 36 failed: equivalent associative profiles did not receive one deterministic digest.\n");
    exit(1);
}

$oversized = [];
for ($i = 0; $i < 200; $i++) {
    $oversized['field-' . $i] = 'value-' . $i;
}
if ($method->invoke(null, $oversized) !== null) {
    fwrite(STDERR, "Review 36 failed: bounded/truncated profile projection remained cacheable.\n");
    exit(1);
}

$deep = ['a' => ['b' => ['c' => ['d' => ['e' => ['f' => ['g' => 'too deep']]]]]]];
if ($method->invoke(null, $deep) !== null) {
    fwrite(STDERR, "Review 36 failed: over-depth profile projection remained cacheable.\n");
    exit(1);
}

$unsupported = ['object' => new stdClass()];
if ($method->invoke(null, $unsupported) !== null) {
    fwrite(STDERR, "Review 36 failed: unsupported profile object remained cacheable.\n");
    exit(1);
}

$long = ['bio' => str_repeat('a', 1001)];
if ($method->invoke(null, $long) !== null) {
    fwrite(STDERR, "Review 36 failed: truncated profile string remained cacheable.\n");
    exit(1);
}

echo "Review 36 fail-closed public-section cache checks passed.\n";
