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

$current = $read('includes/class-current-companion-2026-08-10.php');
foreach ([
    'native_action_url',
    "file03_route_declared('/account/profile/')",
    'Sabri\\UniversalComposer\\Core\\Page_Resolver',
    'SPDB_Dashboard_Router',
    'SPDB_Membership_Guard',
    'SPDB_Capabilities',
] as $marker) {
    $check(str_contains($current, $marker), 'Post-614 native action marker missing: ' . $marker);
}
$check(str_contains($current, "if (in_array(\$action, ['appointment', 'message'], true))"), 'Professional lifecycle action suppression missing.');
$check(str_contains($current, 'Current File 17 owns Follow/Connect state'), 'Follow owner-boundary rationale missing.');
$check(str_contains($current, 'Current File 08') && str_contains($current, 'no owner API that maps a profile user'), 'File 08 appointment owner-boundary rationale missing.');
$check(str_contains($current, "($action === 'follow' || $action === 'appointment')") && str_contains($current, "return '';"), 'Follow/appointment must fail closed while owner destination contracts are unpublished.');
foreach (['sabri_file17_profile_action_url_v1','sabri_network_message_profile_url','sabri_file08_public_clinic_projection_v1','SN_Activator::network_url()'] as $invented) {
    $check(! str_contains($current, $invented), 'File 25 must not depend on unpublished or misleading owner action hooks: ' . $invented);
}

$deps = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($deps), 'Dependency matrix JSON invalid.');
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['09'] ?? '') === '9639f75ba046ac1a36e39d5e9aae56c7bae3279b', 'Current File 09 head drift.');
$check(($heads['23'] ?? '') === 'dcae138e6073f4d0ff596623deb05b9940b8271b', 'Current File 23 head drift.');
$check(($heads['17'] ?? '') === '8ae656e51796d1f05865d8be5dca2480443d79ca', 'Current File 17 head drift.');
$check(($heads['19'] ?? '') === '04078025b643ab7696e4cb4e37826bf152defa18', 'Current File 19 head drift.');
$check(($heads['26'] ?? '') === 'bbea3aad466792a4a6a62b53532bbd45c7c592de', 'Current File 26 head drift.');
$check(($heads['evidence_class'] ?? '') === 'repository-source-only-not-staging-live', 'Repository observations must not become staging/live truth.');

$modules = [];
foreach ((array) ($deps['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
$check(($modules[9]['current_repository_head'] ?? '') === '9639f75ba046ac1a36e39d5e9aae56c7bae3279b', 'File 09 module current head mismatch.');
$check(($modules[23]['current_repository_head'] ?? '') === 'dcae138e6073f4d0ff596623deb05b9940b8271b', 'File 23 module current head mismatch.');
$check(str_contains((string) ($modules[23]['current_future_intelligence_status'] ?? ''), 'merged to current main'), 'File 23 Future Publishing Intelligence current source status missing.');

$plan = json_decode($read('config/staging-test-plan.json'), true);
$ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); }
}
$check(in_array('profile-native-action-routing', $ids, true), 'Native profile-action staging scenario missing.');

$workflow = $read('.github/workflows/ci.yml');
$check(str_contains($workflow, 'node --check assets/js/public.js'), 'Primary JavaScript syntax check missing.');
$check(str_contains($workflow, 'node --check assets/js/future-public-experience.js'), 'Future Public Experience JavaScript syntax check missing.');

foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $ledger = json_decode($read('config/review595-614-twenty-round-cross-file-completeness-ledger.json'), true);
    $check(is_array($ledger) && ($ledger['external_truth'][$gate] ?? null) === false, 'Post-614 correction must not promote external gate: ' . $gate);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED post-614 current-head/action reconciliation\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 post-614 current-head and owner-native action reconciliation\n";
