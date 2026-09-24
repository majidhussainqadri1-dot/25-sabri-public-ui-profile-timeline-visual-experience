<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
require_once dirname(__DIR__) . '/includes/class-profile-repository.php';

$method = new ReflectionMethod(\Sabri\PublicExperience\Profile_Repository::class, 'canonical_contact');
$method->setAccessible(true);

$cases = [
    '+92 300 123 4567' => '+923001234567',
    '0300-1234567' => '03001234567',
    '(0300) 123.4567' => '03001234567',
    '+12025550123' => '+12025550123',
    '1234567' => '1234567',
    '12' => '',
    '++923001234567' => '',
    '+0923001234567' => '',
    '0300/1234567' => '',
    '0300abc1234567' => '',
    '1234567890123456' => '',
    "03001234567\nInjected" => '',
];
foreach ($cases as $input => $expected) {
    $actual = $method->invoke(null, $input);
    if ($actual !== $expected) {
        fwrite(STDERR, "Review 29 failed for {$input}: expected {$expected}, got {$actual}.\n");
        exit(1);
    }
}

foreach ([null, [], new stdClass()] as $input) {
    if ($method->invoke(null, $input) !== '') {
        fwrite(STDERR, "Review 29 failed: non-scalar contact input was accepted.\n");
        exit(1);
    }
}

echo "Review 29 strict public-contact canonicalization checks passed.\n";
