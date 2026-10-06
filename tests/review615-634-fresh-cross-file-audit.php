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
    $ledger = json_decode($read('config/review615-634-fresh-cross-file-audit-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    $ledger = [];
    $failures[] = 'Reviews 615-634 ledger invalid: ' . $e->getMessage();
}
$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Review ledger identity mismatch.');
$check(($ledger['review_range']['first'] ?? null) === 615 && ($ledger['review_range']['last'] ?? null) === 634 && ($ledger['review_range']['count'] ?? null) === 20, 'Review range/count mismatch.');
$check(($ledger['defect_rounds'] ?? null) === [621, 623, 634], 'Fresh defect rounds mismatch.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) { $failures[] = 'Malformed review row.'; continue; }
    $numbers[] = $row['review'];
}
$check($numbers === range(615, 634), 'Reviews 615-634 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth promoted: ' . $gate);
}

$current = $read('includes/class-current-companion-2026-08-10.php');
foreach (['sabri_file08_public_clinic_projection_v1','sabri_file17_profile_action_url_v1','sabri_network_message_profile_url'] as $invented) {
    $check(! str_contains($current, $invented), 'Unpublished owner action hook remains: ' . $invented);
}
$check(str_contains($current, "(\$action === 'follow' || \$action === 'appointment')"), 'Follow/Appointment explicit fail-closed gate missing.');
$check(str_contains($current, "['internal_message_url']"), 'Concrete File 03-projected message destination path missing.');
$check(str_contains($current, 'no owner API that maps a profile user'), 'File 08 owner-contract limitation rationale missing.');
$check(str_contains($current, 'Current File 17 main owns messaging but does not publish'), 'File 17 message boundary rationale missing.');

try {
    $plan = json_decode($read('config/staging-test-plan.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    $plan = [];
    $failures[] = 'Staging plan invalid: ' . $e->getMessage();
}
$scenarios = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario) && isset($scenario['id'])) { $scenarios[(string) $scenario['id']] = $scenario; }
}
$actionText = (string) ($scenarios['profile-native-action-routing']['requirement'] ?? '');
$check(str_contains($actionText, 'must be hidden') && str_contains($actionText, 'publishes no target-bound Follow URL'), 'Staging action scenario must reflect current owner gaps.');
$clinicText = (string) ($scenarios['file08-clinic-projection']['requirement'] ?? '');
$check(str_contains($clinicText, '1.2.15') && str_contains($clinicText, 'profile-user→clinic public_ref/booking-destination contract'), 'File 08 current source/action boundary not recorded.');

try {
    $matrix = json_decode($read('config/source-completion-matrix.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    $matrix = [];
    $failures[] = 'Source matrix invalid: ' . $e->getMessage();
}
$check(($matrix['declaration']['latest_review_range'] ?? '') === '615-634', 'Source matrix latest review range must be 615-634.');
$check(($matrix['declaration']['known_unresolved_source_defects'] ?? null) === 0, 'File 25 known unresolved source defects must remain zero after corrections.');
foreach (['hostinger_staging_accepted','founder_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($matrix['declaration'][$gate] ?? null) === false, 'Source matrix promoted external gate: ' . $gate);
}

$deps = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($deps), 'Dependency matrix invalid.');
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['25'] ?? '') === '19009a0934970d63ca60fda1fe9df1ac7288d42a', 'Fresh audit baseline File 25 main SHA missing.');
$check(($heads['08'] ?? '') === '70541974ce0ffb16aebef557c3016eb7447662f4', 'File 08 current main SHA drift.');
$check(($heads['17'] ?? '') === '8ae656e51796d1f05865d8be5dca2480443d79ca', 'File 17 current main SHA drift.');
$check(($heads['evidence_class'] ?? '') === 'repository-source-only-not-staging-live', 'Repository observations must stay non-live evidence.');

$readme = $read('README.md');
$check(str_contains($readme, 'Reviews 615–634'), 'README fresh review lineage missing.');
$check(str_contains($readme, 'File 08') && str_contains($readme, 'Appointment action remains hidden'), 'README File 08 action limitation missing.');
$check(str_contains($readme, 'File 17') && str_contains($readme, 'Follow remains hidden'), 'README File 17 action limitation missing.');

$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review615-634-fresh-cross-file-audit.php'), 'Fresh audit test must be governed.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 615-634\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 615-634 fresh cross-file audit corrections preserved\n";
