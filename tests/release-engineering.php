<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/tools/build-staging-package.php';
require_once dirname(__DIR__) . '/tools/verify-staging-artifact.php';

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $runtime_match);
$runtime_version = (string) ($runtime_match[1] ?? '');
$check($runtime_version !== '', 'Runtime version must be discoverable from the canonical plugin constant.');

try {
    $payload = File25_Staging_Package_Builder::discover_payload($root);
} catch (Throwable $exception) {
    $payload = [];
    $failures[] = 'Payload discovery failed: ' . $exception->getMessage();
}

foreach ([
    'sabri-public-experience.php', 'uninstall.php', 'readme.txt',
    'assets/css/design-system.css', 'includes/class-plugin.php',
    'includes/class-native-integration.php', 'includes/class-dependency-manager.php',
    'includes/class-file-24-integration.php', 'includes/class-staging-probe.php',
    'includes/class-staging-cli.php', 'includes/class-upgrade-manager.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'templates/public-profile.php', 'config/staging-dependencies.json',
    'config/staging-test-plan.json',
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
$check(($matrix['schema_version'] ?? null) === 2, 'Staging dependency matrix schema 2 is required.');
$check(($matrix['file'] ?? null) === 25, 'Staging dependency matrix must belong to File 25.');
$check(($matrix['runtime_version'] ?? '') === $runtime_version, 'Staging dependency matrix must match the canonical runtime version.');
$check(($matrix['governing_sources']['platform_master_plan'] ?? '') === 'Sabri Social Homeopathy Platform Definitive Master Plan 2026 v3.0', 'Master Plan v3.0 must be declared.');
$check(($matrix['governing_sources']['file_20_plan'] ?? '') === 'File 20 Harmonized Master Plan 2026 v4.1', 'File 20 plan v4.1 must be declared.');
$check(($matrix['environment']['target_site'] ?? '') === 'https://sabrisocialstaging.sabrihomeopathy.com/', 'Exact canonical Hostinger staging site must be declared.');
$check(($matrix['environment']['live_changes_allowed'] ?? true) === false, 'Staging matrix must prohibit live changes.');
$check(($matrix['environment']['registration_disabled_required'] ?? false) === true, 'Staging matrix must require disabled registration.');
$check(($matrix['environment']['search_indexing_disabled_required'] ?? false) === true, 'Staging matrix must require noindex.');
$check(($matrix['staging_test_plan'] ?? '') === 'config/staging-test-plan.json', 'Staging test plan must be declared.');
$check(($matrix['artifact_verification']['verifier'] ?? '') === 'tools/verify-staging-artifact.php', 'Independent artifact verifier must be declared.');
$check(($matrix['artifact_verification']['staging_acceptance_implied'] ?? true) === false, 'Artifact verification must not imply staging acceptance.');

$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
foreach ([0, 3, 6, 8, 9, 10, 11, 12, 18, 20, 21, 24, 25] as $file_number) {
    $check(isset($modules[$file_number]), 'Staging dependency matrix is missing File ' . $file_number . '.');
}
$check(($modules[0]['reviewed_package_version'] ?? '') === '1.2.4', 'File 00 exact reviewed package must be declared.');
$check(($modules[0]['accepted_source_range'] ?? '') === '>=1.2.4 <1.3.0', 'File 00 reviewed source range must be exact.');
$check(($modules[0]['required_contract_version'] ?? '') === '1.1.2', 'File 00 contract 1.1.2 must be declared.');
$check(($modules[0]['foreign_table_reads_allowed'] ?? true) === false, 'File 00 foreign-table reads must be prohibited.');
$check(($modules[3]['reviewed_package_version'] ?? '') === '0.2.0', 'File 03 exact reviewed package must be declared.');
$check(in_array('SPD_Helpers::can_show_contact', (array) ($modules[3]['required_symbols'] ?? []), true), 'File 03 contact-consent symbol must be required.');
$check(($modules[8]['reviewed_package_version'] ?? '') === '0.2.1', 'File 08 exact reviewed package must be 0.2.1.');
$check(($modules[8]['reviewed_source_version'] ?? '') === '0.2.1', 'File 08 exact reviewed source must be 0.2.1.');
$check(($modules[8]['accepted_source_range'] ?? '') === '>=0.2.1 <0.3.0', 'File 08 accepted source range must begin at 0.2.1.');
$check(($modules[8]['required_public_contract_version'] ?? '') === '1.0.0', 'File 08 public projection contract must be declared.');
$check(($modules[8]['reviewed_source_commit'] ?? '') === 'bd6a10b693991fc518788ef8e3cba49531454821', 'File 08 exact reviewed commit must be declared.');
$check(($modules[8]['reviewed_candidate_sha256'] ?? '') === '36ce0c78aa51396b02bd0705021e66782bfc45b74636ac65b0826bd372103578', 'File 08 exact reviewed candidate digest must be declared.');
foreach (['SWC_VERSION', 'SWC_PUBLIC_CLINIC_CONTRACT_VERSION', 'swc_get_public_clinic_projection', 'swc_public_clinic_projection_contract'] as $symbol) {
    $check(in_array($symbol, (array) ($modules[8]['required_symbols'] ?? []), true), 'File 08 staging matrix is missing symbol: ' . $symbol);
}
$check(($modules[8]['contract_status'] ?? '') === 'implemented-and-ci-verified-pending-hostinger-staging', 'File 08 source verification must remain distinct from Hostinger acceptance.');
$check(($modules[8]['staging_status'] ?? '') === 'pending', 'File 08 staging must remain pending.');
$check(($modules[9]['reviewed_source_version'] ?? '') === '1.1.0', 'File 09 reviewed source must be declared.');
$check(($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1', 'File 18 reviewed source must be declared.');
$check(($modules[18]['foreign_table_reads_allowed'] ?? true) === false, 'File 18 foreign-table reads must be prohibited.');
$check(($modules[20]['reviewed_source_version'] ?? '') === '1.2.0', 'File 20 corrective source must be declared.');
$check(($modules[20]['governing_plan_version'] ?? '') === '4.1', 'File 20 governing plan v4.1 must be declared.');
$check(($modules[24]['reviewed_package_version'] ?? '') === '0.25.3', 'File 24 exact reviewed package must be declared.');
$check(($modules[24]['accepted_source_range'] ?? '') === '>=0.25.3 <0.26.0', 'File 24 reviewed source range must be exact.');
$check(($modules[24]['accepted_runtime_contract'] ?? '') === 'reviewed-source-contract-pending-staging', 'File 24 source review must remain distinct from staging acceptance.');
$check(($modules[24]['cache_partition_contract'] ?? '') === 'not-yet-versioned', 'File 24 cache partitioning must not be fabricated.');
$check(($modules[24]['staging_status'] ?? '') === 'pending', 'File 24 staging must remain pending.');
$check(($modules[25]['candidate_version'] ?? '') === $runtime_version, 'File 25 candidate version must match runtime.');
$check(($modules[25]['schema_version'] ?? '') === '2', 'File 25 schema version 2 must be declared.');
$check(($modules[25]['staging_status'] ?? '') === 'pending', 'File 25 staging must remain pending.');
$check(($modules[25]['package_status'] ?? '') === 'build-input-not-acceptance', 'File 25 package status must not claim acceptance.');

$plan_raw = file_get_contents($root . '/config/staging-test-plan.json');
$plan = is_string($plan_raw) ? json_decode($plan_raw, true) : null;
$check(is_array($plan) && ($plan['schema_version'] ?? null) === 2, 'Staging test plan schema 2 must be valid.');
$scenario_ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) { $scenario_ids[] = (string) ($scenario['id'] ?? ''); }
}
foreach (['file00-assertions', 'file08-clinic-projection', 'file09-doctor-decision', 'file18-owner-dto'] as $scenario_id) {
    $check(in_array($scenario_id, $scenario_ids, true), 'Staging test plan is missing authoritative scenario: ' . $scenario_id);
}

$builder = file_get_contents($root . '/tools/build-staging-package.php') ?: '';
foreach (['SOURCE_DATE_EPOCH', 'STAGING-MANIFEST.json', 'hash_file', 'ZipArchive', 'verify_archive', 'payload_path_is_allowed', 'archive_name_is_safe', 'validated_output_dir', 'Output path ancestor may not be a symbolic link', 'Refusing to remove a path outside build/', 'zip_entry_is_symlink', 'MAX_PAYLOAD_BYTES'] as $marker) {
    $check(str_contains($builder, $marker), 'Deterministic package builder marker missing: ' . $marker);
}
$verifier = file_get_contents($root . '/tools/verify-staging-artifact.php') ?: '';
foreach (['File25_Staging_Artifact_Verifier', 'outer_artifact_sha256', 'verify_inner_zip', 'Embedded and detached staging manifests differ', 'Outer and embedded dependency matrices differ', 'Duplicate entry detected', 'Symbolic link detected', 'MAX_INNER_TOTAL_BYTES', 'staging_accepted', 'production_accepted'] as $marker) {
    $check(str_contains($verifier, $marker), 'Independent staging artifact verifier marker missing: ' . $marker);
}

$workflow = file_get_contents($root . '/.github/workflows/ci.yml') ?: '';
foreach (['version=$(php -r', 'steps.source.outputs.version', 'tools/verify-staging-artifact.php', 'Authoritative File 00 08 09 native contracts', 'Master Plan v3 authoritative-contract reconciliation', 'Reviewed File 24 integration contract', 'Verify extracted installed candidate against embedded manifest', 'Staging_Probe::verify_package_integrity', 'Verify assembled candidate with the independent verifier'] as $marker) {
    $check(str_contains($workflow, $marker), 'Dynamic staging workflow marker missing: ' . $marker);
}
$check(! str_contains($workflow, 'sabri-public-experience-' . $runtime_version . '.zip'), 'Staging workflow must not hard-code the current release filename.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 deterministic builder and authoritative dependency release contracts\n";
