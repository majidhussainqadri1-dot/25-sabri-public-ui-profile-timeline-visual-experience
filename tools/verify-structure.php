<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    '.github/workflows/ci.yml',
    'sabri-public-experience.php',
    'readme.txt',
    'composer.json',
    'includes/class-plugin.php',
    'includes/class-safe-mode.php',
    'includes/class-native-integration.php',
    'includes/class-visibility-policy.php',
    'includes/class-profile-repository.php',
    'includes/class-profile-renderer.php',
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/contracts/interface-timeline-provider.php',
    'templates/public-profile.php',
    'templates/partials/profile-hero.php',
    'templates/partials/timeline.php',
    'assets/css/public.css',
    'assets/js/public.js',
    'SECURITY.md',
    'PRIVACY.md',
    'docs/ARCHITECTURE.md',
    'docs/GOVERNING-SCOPE.md',
    'docs/DECISION-LOG.md',
    'docs/REVIEW-AND-CORRECTION-2026-07-30.md',
];

$errors = [];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing required file: ' . $path;
    }
}

$css = file_get_contents($root . '/assets/css/public.css') ?: '';
if (preg_match('/@import\s+url|fonts\.googleapis|use\.typekit|url\(\s*["\']?https?:/i', $css)) {
    $errors[] = 'Remote CSS/font dependency detected.';
}
if (! str_contains($css, '.spux-profile *')) {
    $errors[] = 'Reduced-motion rules are not scoped to File 25.';
}

$javascript = file_get_contents($root . '/assets/js/public.js') ?: '';
if (preg_match('/\b(eval|document\.write)\s*\(/i', $javascript)) {
    $errors[] = 'Unsafe JavaScript execution primitive detected.';
}
if (preg_match('/https?:\/\//i', $javascript)) {
    $errors[] = 'Unexpected remote JavaScript endpoint detected.';
}

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
$readme = file_get_contents($root . '/readme.txt') ?: '';
preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header_match);
preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable_match);
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant_match);
$versions = array_filter([
    $header_match[1] ?? '',
    $stable_match[1] ?? '',
    $constant_match[1] ?? '',
]);
if (count($versions) !== 3 || count(array_unique($versions)) !== 1) {
    $errors[] = 'Plugin header, constant, and readme stable-tag versions do not match.';
}
if (! str_contains($main, "require_once SABRI_PUBLIC_EXPERIENCE_DIR . 'includes/class-safe-mode.php'")) {
    $errors[] = 'Safe Mode must load before the remaining runtime classes.';
}
if (! str_contains($main, "version_compare(PHP_VERSION, '8.0', '<')")) {
    $errors[] = 'Pre-require PHP runtime guard is missing.';
}

$plugin = file_get_contents($root . '/includes/class-plugin.php') ?: '';
if (! str_contains($plugin, 'home_news_available')) {
    $errors[] = 'The File 21 availability boundary is missing from plugin bootstrap.';
}

$timeline = file_get_contents($root . '/includes/class-timeline-service.php') ?: '';
foreach (['provider_version', 'canonical_is_allowed', 'to_public_array'] as $marker) {
    if (! str_contains($timeline, $marker)) {
        $errors[] = 'Reviewed timeline invariant missing: ' . $marker;
    }
}

$composer_raw = file_get_contents($root . '/composer.json') ?: '';
$composer = json_decode($composer_raw, true);
if (! is_array($composer) || ($composer['require']['php'] ?? '') !== '>=8.0') {
    $errors[] = 'Composer PHP requirement is invalid or missing.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "PASS: File 25 reviewed package structure\n";
