<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};
$read = static function (string $path) use ($root, &$failures): string {
    $value = @file_get_contents($root . '/' . $path);
    if (! is_string($value)) { $failures[] = 'Unreadable evidence: ' . $path; return ''; }
    return $value;
};

try {
    $ledger = json_decode($read('config/review595-614-twenty-round-plan-companion-reconciliation.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $error) {
    $ledger = [];
    $failures[] = 'Twenty-round ledger JSON invalid: ' . $error->getMessage();
}
$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Twenty-round ledger identity mismatch.');
$check(($ledger['review_range'] ?? null) === ['first' => 595, 'last' => 614, 'count' => 20], 'Twenty-round review range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === range(595, 609), 'Twenty-round defect-round record changed.');
$check(($ledger['clean_rounds'] ?? null) === range(610, 614), 'Twenty-round clean-round record changed.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) { $failures[] = 'Malformed twenty-round row.'; continue; }
    $numbers[] = $row['review'];
    $defect = $row['review'] <= 609;
    $check(($row['status'] ?? '') === ($defect ? 'defect-found-corrected' : 'reviewed-clean'), 'Unexpected round status at ' . $row['review'] . '.');
}
$check($numbers === range(595, 614), 'Twenty-round review numbers must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'Repository evidence promoted external truth: ' . $gate);
}

$native = $read('includes/class-native-integration.php');
foreach ([
    "FILE_00_MINIMUM_CONTRACT_VERSION = '1.2.2'",
    "FILE_00_MAXIMUM_CONTRACT_VERSION = '1.3.0'",
    "FILE_03_CONTRACT_VERSION = '1.4.0'",
    "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.1.0'",
    "FILE_09_CONTRACT_VERSION = '1.1.0'",
    'gdo_file03_doctor_eligibility',
    'swc_get_public_clinic_projection',
] as $marker) {
    $check(str_contains($native, $marker), 'Current native contract marker missing: ' . $marker);
}

$current = $read('includes/class-current-companion-2026-08-10.php');
foreach ([
    'native_action_url',
    'sabri_network_message_profile_url',
    'sn_network_profile_action_state',
    'SN_Activator',
    'sabri_file08_public_clinic_projection_v1',
    "['appointment_url']",
    "file03_route_declared('/account/profile/')",
    'Sabri\\UniversalComposer\\Core\\Page_Resolver',
    'SPDB_Dashboard_Router',
    'SPDB_Membership_Guard',
    'SPDB_Capabilities',
    "in_array(\$action, ['appointment', 'message'], true)",
] as $marker) {
    $check(str_contains($current, $marker), 'Native action reconciliation marker missing: ' . $marker);
}

$matrix = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Current dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
$expected = [
    0 => '2fa7c022ee9cd1b65432e900579512f304532442',
    3 => '636e3ef965423887f810718abec3cd1c11c3659d',
    7 => '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844',
    8 => '70541974ce0ffb16aebef557c3016eb7447662f4',
    9 => 'a9ab697c671129be023414f5a3c32186567cb2bf',
    14 => 'db60c4bc5c37a5c88126b78c31b34c75236f33d7',
    17 => '8ae656e51796d1f05865d8be5dca2480443d79ca',
    19 => '04078025b643ab7696e4cb4e37826bf152defa18',
    20 => '8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca',
    21 => 'f2eb7e95ddea327af36ea725ffb923b029f885e6',
    22 => 'b7a7f2e69411cbd32f0574fd12d766fb70c01b7a',
    23 => 'dcae138e6073f4d0ff596623deb05b9940b8271b',
    24 => 'a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb',
    26 => 'bbea3aad466792a4a6a62b53532bbd45c7c592de',
];
foreach ($expected as $file => $sha) {
    $check(($modules[$file]['reviewed_source_commit'] ?? '') === $sha, 'Current source head mismatch for File ' . $file . '.');
}
$check(($modules[8]['required_public_contract_version'] ?? '') === '1.1.0', 'File 08 current public contract mismatch.');
$check(($modules[17]['role'] ?? '') !== '', 'File 17 ownership entry missing.');
$check(($modules[19]['role'] ?? '') !== '', 'File 19 ownership entry missing.');
$check(($modules[26]['role'] ?? '') !== '', 'File 26 ownership entry missing.');
foreach ($modules as $module) {
    if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'Source evidence may not promote staging acceptance.'); }
}

$plan = json_decode($read('config/staging-test-plan.json'), true);
$ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); }
}
foreach ([
    'file17-native-profile-actions',
    'file19-notification-owner',
    'file26-search-ranking-owner',
    'file08-clinic-projection',
    'file22-create-edit-contract',
    'file23-private-management-contract',
    'responsive-viewports',
    'urdu-rtl',
    'accessibility-input',
    'upgrade-rollback',
    'founder-acceptance',
] as $id) {
    $check(in_array($id, $ids, true), 'Current staging scenario missing: ' . $id);
}

$workflow = $read('.github/workflows/ci.yml');
$check(str_contains($workflow, 'node --check assets/js/public.js'), 'Primary JavaScript syntax gate missing.');
$check(str_contains($workflow, 'node --check assets/js/future-public-experience.js'), 'Future Public Experience JavaScript syntax gate missing.');
$check(str_contains($workflow, 'name: Deterministic staging candidate'), 'Deterministic package gate missing.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 595-614\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 Reviews 595-614 — twenty-round plan/central/companion source reconciliation\n";
