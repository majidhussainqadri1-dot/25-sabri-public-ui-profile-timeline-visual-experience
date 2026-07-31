<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/tools/build-staging-package.php';

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

try {
    $payload = File25_Staging_Package_Builder::discover_payload($root);
} catch (Throwable $exception) {
    $payload = [];
    $failures[] = 'Payload discovery failed: ' . $exception->getMessage();
}

foreach ([
    'sabri-public-experience.php',
    'uninstall.php',
    'readme.txt',
    'assets/css/design-system.css',
    'includes/class-plugin.php',
    'templates/public-profile.php',
    'config/staging-dependencies.json',
] as $required) {
    $check(in_array($required, $payload, true), 'Staging payload is missing: ' . $required);
}

foreach ($payload as $path) {
    $check(! preg_match('#(?:^|/)(?:\.git|\.github|build|coverage|docs|node_modules|tests|tools|vendor)(?:/|$)#', $path), 'Development-only path entered staging payload: ' . $path);
    $check(! str_contains($path, '..') && ! str_contains($path, '\\'), 'Unsafe staging payload path: ' . $path);
}

$matrix_raw = file_get_contents($root . '/config/staging-dependencies.json');
$matrix = is_string($matrix_raw) ? json_decode($matrix_raw, true) : null;
$check(is_array($matrix), 'Staging dependency matrix must be valid JSON.');
$check(($matrix['file'] ?? null) === 25, 'Staging dependency matrix must belong to File 25.');
$check(($matrix['runtime_version'] ?? '') === '0.10.0', 'Staging dependency matrix must match runtime 0.10.0.');
$check(($matrix['environment']['live_changes_allowed'] ?? true) === false, 'Staging matrix must prohibit live changes.');

$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) {
        $modules[(int) $module['file']] = $module;
    }
}
foreach ([0, 3, 6, 10, 11, 12, 18, 20, 21, 24, 25] as $file_number) {
    $check(isset($modules[$file_number]), 'Staging dependency matrix is missing File ' . $file_number . '.');
}
$check(($modules[24]['accepted_runtime_contract'] ?? '') === 'pending', 'File 24 must not be fabricated as accepted.');
$check(($modules[25]['staging_status'] ?? '') === 'candidate-package-pending-ci', 'File 25 must remain a staging candidate before evidence.');

$builder = file_get_contents($root . '/tools/build-staging-package.php') ?: '';
foreach ([
    'SOURCE_DATE_EPOCH',
    'STAGING-MANIFEST.json',
    'hash_file',
    'ZipArchive',
    'verify_archive',
    'payload_path_is_allowed',
    'archive_name_is_safe',
] as $marker) {
    $check(str_contains($builder, $marker), 'Deterministic package builder marker missing: ' . $marker);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 deterministic staging package and dependency matrix contracts\n";
