<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void {
    if (! $ok) { $failures[] = $message; }
};
$read = static function (string $path) use ($root, &$failures): string {
    $value = @file_get_contents($root . '/' . $path);
    if (! is_string($value)) { $failures[] = 'Unreadable evidence: ' . $path; return ''; }
    return $value;
};
$has_all = static function (string $source, array $markers, string $label) use ($check): void {
    foreach ($markers as $marker) { $check(str_contains($source, $marker), $label . ' marker missing: ' . $marker); }
};

try {
    $ledger = json_decode($read('config/review355-434-third-eighty-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Third-cycle ledger JSON invalid: ' . $exception->getMessage();
}

$expectedDefects = [355,356,357,358,359,360,361,362,363,364,365,366,367,368,370,371,372,433,434];
$check(($ledger['review_range'] ?? null) === ['first' => 355, 'last' => 434, 'count' => 80], 'Review range must be exactly 355–434 / 80.');
$check(($ledger['defect_rounds'] ?? null) === $expectedDefects, 'Defect-bearing review ledger changed unexpectedly.');
$clean = (array) ($ledger['clean_rounds'] ?? []);
$check(count($clean) === 61 && $clean[0] === 369 && end($clean) === 432, 'Clean-review ledger must contain exactly 61 rounds.');
$reviewNumbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $review) {
    if (is_array($review) && is_int($review['review'] ?? null)) { $reviewNumbers[] = $review['review']; }
}
$check($reviewNumbers === range(355, 434), 'Third-cycle reviews must be contiguous and unique.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified','migration_state_verified_live'] as $key) {
    $check(($ledger['external_truth'][$key] ?? null) === false, 'External truth must remain unclaimed: ' . $key);
}

$file20 = $read('includes/class-file-20-integration.php');
$has_all($file20, [
    "REVIEWED_MINIMUM_VERSION = '1.4.12'",
    "REVIEWED_MAXIMUM_VERSION = '1.5.0'",
    "REVIEWED_CONTRACT_VERSION = '1.0.0'",
    "REVIEWED_SOURCE_COMMIT = '291486b22c7ed94b8be041192375b6d9b077fac5'",
    'sabri_shell_contract_registry',
    'sabri_public_experience/dependency/application_shell',
], 'File 20 integration');

$design = $read('includes/class-design-system.php');
$has_all($design, [
    "public const CONTRACT_VERSION = '1.9.0'",
    'sabri_shell_file25_visual_contract',
    "'primary_color' => '#087A4E'",
    "'owner' => 'file-25'",
    'file_20_contract()',
    'class_exists(File_20_Integration::class)',
], 'Design/File20 reconciliation');

$admin = $read('includes/class-admin-integration.php');
$has_all($admin, [
    "SHELL_PARENT_SLUG = 'sabri-shell'",
    "remove_submenu_page('sabri-public-experience', 'sabri-public-experience')",
    "remove_menu_page('sabri-public-experience')",
    "'standalone_menu_mode' => 'degraded-fallback-only'",
    "'canonical_admin_architecture_owner' => 'file-20'",
], 'Admin integration');

$component = $read('includes/class-component-api.php');
$has_all($component, [
    "CONTRACT_VERSION = '1.0.0'",
    'MAX_PENDING_TIMELINE_PROVIDERS = 25',
    'bind_timeline_registry',
    'register_card_variant',
    '$provider->get_provider_id()',
    'cannot bind a second timeline registry',
], 'Component API');
$check(! str_contains($component, '$provider->get_id()'), 'Component API must not call a non-contract provider ID method.');

$cards = $read('includes/class-content-cards.php');
$has_all($cards, [
    "'profile'", "VARIANT_CONTRACT_VERSION = '1.0.0'", 'MAX_VARIANTS = 20',
    'public static function register_variant', "'variant_input' => 'normalized-public-card-only'", 'wp_kses_post($custom)',
], 'Content cards');

$entry = $read('sabri-public-experience.php');
$has_all($entry, [
    "'includes/class-file-20-integration.php'", "'includes/class-component-api.php'", "'includes/class-admin-integration.php'",
    'Component_API::register_timeline_provider($provider)', 'Component_API::register_card_variant($variant, $renderer)',
    "'type' => 'profile'", "'canonical_url'", "'avatar_url'",
], 'Production bootstrap/public API');

$planCompletion = $read('includes/class-plan-completion.php');
$has_all($planCompletion, [
    "'/preferences'", "'/admin/rebuild-index'", "'/admin/reconcile-profile'", "'permission_callback'",
    'idempotency_key_required', 'rate_limited', "'owner_data_mutated' => false", 'no-store, private, max-age=0',
], 'Plan §72/repair safety');

$matrix = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
$check(($modules[20]['reviewed_source_version'] ?? '') === '1.4.12', 'File 20 matrix version stale.');
$check(($modules[20]['reviewed_source_commit'] ?? '') === '291486b22c7ed94b8be041192375b6d9b077fac5', 'File 20 matrix exact head stale.');
$check(($modules[20]['required_contract_version'] ?? '') === '1.0.0', 'File 20 matrix contract stale.');
$check(($modules[20]['staging_status'] ?? '') === 'pending', 'File 20 source evidence must not imply staging acceptance.');

$staging = json_decode($read('config/staging-test-plan.json'), true);
$scenarioIds = [];
foreach ((array) ($staging['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) { $scenarioIds[] = (string) ($scenario['id'] ?? ''); }
}
foreach (['file20-current-shell-contract','file20-admin-parent','component-api-runtime','file03-route-parity'] as $id) {
    $check(in_array($id, $scenarioIds, true), 'Third-cycle staging scenario missing: ' . $id);
}

$verifier = $read('tools/verify-staging-artifact.php');
$has_all($verifier, [
    'File25_Staging_Artifact_Verifier', 'MAX_INNER_TOTAL_BYTES',
    'Unsafe, duplicate or symbolic entry detected in workflow artifact.',
    'Unsafe, duplicate or symbolic entry detected in inner ZIP.',
    'Embedded manifest or dependency matrix differs from detached evidence.',
    "reviewed_source_version'] ?? '') === '1.4.12'",
], 'Artifact verifier');

$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review355-434-third-eighty-integration.php'), 'Third-cycle gate must be governed by Composer.');
$check(is_file($root . '/docs/REVIEWS-355-434-THIRD-EIGHTY-CURRENT-INTEGRATION-2026-08-10.md'), 'Third-cycle human audit record is missing.');

$workflow = $read('.github/workflows/ci.yml');
$check(str_contains($workflow, 'actions/checkout@v5'), 'CI must use checkout@v5.');
$check(! str_contains($workflow, 'actions/checkout@v4'), 'Deprecated checkout@v4 must not return.');
$check(str_contains($workflow, 'php: ["8.0", "8.3"]'), 'CI PHP matrix must preserve PHP 8.0 and 8.3.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 355-434\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 355-434 — third eighty-round current integration closure\n";
