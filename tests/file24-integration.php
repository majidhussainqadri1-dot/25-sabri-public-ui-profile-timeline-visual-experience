<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}
if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
    define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0');
}
if (! defined('SPCRC_VERSION')) {
    define('SPCRC_VERSION', '0.99.0');
}
if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9_\-]/i', '', $value));
    }
}

require_once dirname(__DIR__) . '/includes/class-file-24-integration.php';

use Sabri\PublicExperience\File_24_Integration;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$contract = File_24_Integration::contract();
$check(($contract['contract_version'] ?? '') === '1.1.0', 'File 25/File 24 integration contract version must be current.');
$check(($contract['file_24_constant'] ?? '') === 'SPCRC_VERSION', 'The exact File 24 runtime constant must be SPCRC_VERSION.');
$check(($contract['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b', 'File 24 exact reviewed current-main source must remain pinned.');
$check(($contract['module_manifest_contract'] ?? '') === '1.0.0', 'Current File 24 module-manifest contract must be explicit.');
$check(($contract['module_key'] ?? '') === 'file-25-public-experience', 'File 25 must have one canonical File 24 module key.');
$check(($contract['staging_probe_cli'] ?? '') === 'wp sabri file25 staging-probe --expected-commit=<sha>', 'CLI staging probe must remain outside the File 24 route manifest.');
$check(($contract['owns_security_governance'] ?? true) === false, 'File 25 must not own security governance.');
$check(($contract['owns_privacy_orchestration'] ?? true) === false, 'File 25 must not own privacy orchestration.');
$check(($contract['owns_incidents'] ?? true) === false, 'File 25 must not own incidents.');
$check(($contract['cache_mode'] ?? '') === 'no-store-until-versioned-partition-contract', 'Shared profile caching must remain closed.');
$check(File_24_Integration::current_version() === '0.99.0', 'The exact active File 24 version must be read from SPCRC_VERSION.');
$check(File_24_Integration::is_compatible(), 'File 24 0.99.0 must be inside the reviewed range.');
$check(! File_24_Integration::version_is_compatible('0.98.9'), 'File 24 0.98.9 must be rejected.');
$check(File_24_Integration::version_is_compatible('0.99.9'), 'Compatible 0.99.x versions must be accepted.');
$check(! File_24_Integration::version_is_compatible('1.0.0'), 'File 24 1.0.0 must require a fresh review.');
$check(! File_24_Integration::version_is_compatible('not-a-version'), 'Malformed versions must fail closed.');

$manifest = File_24_Integration::manifest();
$check(($manifest['module_key'] ?? '') === File_24_Integration::MODULE_KEY, 'Manifest key must be canonical.');
$check(($manifest['version'] ?? '') === '0.14.0', 'Manifest version must match the File 25 runtime.');
$check(($manifest['owner'] ?? '') === 'File 25', 'Manifest ownership must remain File 25.');
$check(($manifest['posture'] ?? '') === 'foundation', 'Source integration must not claim operational acceptance.');
$check(($manifest['contract_version'] ?? '') === '1.0.0', 'Manifest contract version must match current File 24 validation.');
$check(($manifest['canonical_data_owner'] ?? '') === 'File 25', 'Manifest must publish File 25 data ownership explicitly.');
$check(str_contains((string) ($manifest['canonical_action_owner'] ?? ''), 'native domain owners'), 'Manifest must preserve native action ownership.');
$check(($manifest['privacy_operations'] ?? null) === [], 'File 25 must not claim privacy operations.');
$check(($manifest['capabilities'] ?? null) === [], 'File 25 must not claim File 24 operational capabilities.');
$check(in_array('/founder/', (array) ($manifest['public_routes'] ?? []), true), 'Founder public route must be declared.');
$check(in_array('/wp-admin/site-health.php', (array) ($manifest['private_routes'] ?? []), true), 'Site Health must be declared as a private operational route.');
foreach (array_merge((array) ($manifest['public_routes'] ?? []), (array) ($manifest['private_routes'] ?? [])) as $route) {
    $check(is_string($route) && str_starts_with($route, '/') && ! str_starts_with($route, '//'), 'File 24 manifest routes must be same-origin absolute paths.');
}
$check(! in_array('wp-cli:sabri file25 staging-probe', (array) ($manifest['private_routes'] ?? []), true), 'CLI pseudo-route must never enter the current File 24 route manifest.');

$incoming = [
    ['module_key' => 'other-module', 'name' => 'Other'],
    ['module_key' => File_24_Integration::MODULE_KEY, 'owner' => 'forged'],
    'invalid-entry',
];
$filtered = File_24_Integration::filter_manifests($incoming);
$check(count($filtered) === 3, 'A forged duplicate must be replaced without losing unrelated bounded entries.');
$check(($filtered[0]['module_key'] ?? '') === File_24_Integration::MODULE_KEY, 'Canonical File 25 manifest must be first in the bounded external collection.');
$check(($filtered[0]['owner'] ?? '') === 'File 25', 'Forged File 25 ownership must be removed.');
$duplicates = 0;
foreach ($filtered as $candidate) {
    if (is_array($candidate) && ($candidate['module_key'] ?? '') === File_24_Integration::MODULE_KEY) {
        $duplicates++;
    }
}
$check($duplicates === 1, 'Only one canonical File 25 manifest may remain.');

$many = [];
for ($index = 0; $index < 150; $index++) {
    $many[] = ['module_key' => 'module-' . $index];
}
$bounded = File_24_Integration::filter_manifests($many);
$check(count($bounded) === 99, 'File 25 plus external manifests must respect File 24 collection bounds.');
$check(($bounded[0]['module_key'] ?? '') === File_24_Integration::MODULE_KEY, 'File 25 manifest must remain present under collection pressure.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 current File 24 0.99.0 integration contract\n";
