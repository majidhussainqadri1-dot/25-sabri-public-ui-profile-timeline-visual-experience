<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$required = [
    'includes/class-future-public-experience.php',
    'assets/css/future-public-experience.css',
    'assets/js/future-public-experience.js',
    'config/future-public-experience-24.json',
    'docs/FUTURE-PUBLIC-EXPERIENCE-24-ENHANCEMENTS-2026-08-10.md',
    'tests/future-public-experience.php',
    'tests/review189-191-future-public-experience.php',
    'tests/review192-194-future-public-experience-post-review.php',
];
foreach ($required as $path) {
    $assert(is_file($root . '/' . $path), 'Missing Future Public Experience structural file: ' . $path);
}

$main = @file_get_contents($root . '/sabri-public-experience.php');
$plugin = @file_get_contents($root . '/includes/class-plugin.php');
$future = @file_get_contents($root . '/includes/class-future-public-experience.php');
$css = @file_get_contents($root . '/assets/css/future-public-experience.css');
$js = @file_get_contents($root . '/assets/js/future-public-experience.js');

$assert(is_string($main) && str_contains($main, "'includes/class-future-public-experience.php'"), 'Production loader must require the future runtime class.');
$assert(is_string($plugin) && str_contains($plugin, '(new Future_Public_Experience())->register();'), 'Plugin boot must register the future runtime class.');
$assert(is_string($future) && str_contains($future, "'feature_count' => count(self::FEATURES)"), 'Future contract feature count marker is missing.');
$assert(is_string($future) && substr_count($future, "'F25-FUT-") >= 24, 'Future runtime must declare all 24 stable feature IDs.');
$assert(is_string($future) && str_contains($future, "add_filter('sabri_public_experience/design_system_contract'"), 'Future contract must bind to both public visual contract filters.');
$assert(is_string($css) && str_contains($css, '@container'), 'Future adaptive CSS container query is missing.');
$assert(is_string($css) && str_contains($css, '@media (prefers-reduced-motion: reduce)'), 'Future reduced-motion contract is missing.');
$assert(is_string($css) && ! str_contains($css, 'z-index: 9999'), 'File 25 must not own a hard-coded shell overlay z-index.');
$assert(is_string($js) && str_contains($js, "const profileRoot = document.querySelector('.spux-profile');"), 'Future client behavior must scope DOM mutations to the File 25 profile root.');
$assert(is_string($js) && str_contains($js, 'url.username || url.password'), 'Client same-origin URL defense must reject credential-bearing URLs.');
$assert(is_string($js) && ! preg_match('/\b(?:eval|document\.write)\s*\(/i', $js), 'Future JavaScript must not use eval or document.write.');
$assert(is_string($js) && ! preg_match('/https?:\/\//i', $js), 'Future JavaScript must not embed remote destinations.');

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . "\n");
    }
    exit(1);
}

echo "File 25 Future Public Experience structural verification PASS.\n";
