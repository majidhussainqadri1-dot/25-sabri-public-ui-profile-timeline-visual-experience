<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'sabri-public-experience.php',
    'readme.txt',
    'includes/class-plugin.php',
    'includes/class-native-integration.php',
    'includes/contracts/interface-timeline-provider.php',
    'templates/public-profile.php',
    'assets/css/public.css',
    'assets/js/public.js',
    'SECURITY.md',
    'PRIVACY.md',
    'docs/ARCHITECTURE.md',
];

$errors = [];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing required file: ' . $path;
    }
}

$css = file_get_contents($root . '/assets/css/public.css') ?: '';
if (preg_match('/@import\s+url|fonts\.googleapis|use\.typekit/i', $css)) {
    $errors[] = 'Remote font import detected.';
}

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
if (! str_contains($main, 'Version:     0.1.0')) {
    $errors[] = 'Plugin header version mismatch.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "PASS: File 25 package structure\n";
