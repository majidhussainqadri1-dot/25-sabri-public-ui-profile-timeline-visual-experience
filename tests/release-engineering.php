<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/tools/build-staging-package.php';
require_once dirname(__DIR__) . '/tools/verify-staging-artifact.php';

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (! $ok) { $failures[] = $message; } };
$read = static fn (string $path): string => (string) (file_get_contents($root . '/' . $path) ?: '');

$main = $read('sabri-public-experience.php');
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $match);
$runtime = (string) ($match[1] ?? '');
$check($runtime !== '', 'Runtime version missing.');

try { $payload = File25_Staging_Package_Builder::discover_payload($root); }
catch (Throwable $e) { $payload = []; $failures[] = 'Payload discovery failed: ' . $e->getMessage(); }
foreach (['sabri-public-experience.php','assets/css/companion-token-bridge.css','includes/class-native-integration.php','includes/class-current-companion-2026-08-10.php','config/staging-dependencies.json','config/staging-test-plan.json'] as $required) {
    $check(in_array($required, $payload, true), 'Staging payload missing ' . $required);
}
foreach ($payload as $path) {
    $check(! preg_match('#(?:^|/)(?:\.git|\.github|build|coverage|docs|node_modules|tests|tools|vendor)(?:/|$)#', $path), 'Development path entered staging payload: ' . $path);
}

$matrix = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($matrix), 'Dependency matrix invalid.');
$check(($matrix['schema_version'] ?? null) === 3, 'Dependency matrix schema 3 required.');
$check(($matrix['file'] ?? null) === 25 && ($matrix['runtime_version'] ?? '') === $runtime, 'Dependency matrix identity mismatch.');
$check(($matrix['environment']['live_changes_allowed'] ?? true) === false, 'Matrix must prohibit live changes.');
$check(($matrix['artifact_verification']['staging_acceptance_implied'] ?? true) === false, 'Artifact verification cannot imply staging acceptance.');

$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
foreach ([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25] as $file) {
    $check(isset($modules[$file]), 'Dependency matrix missing File ' . $file);
}
$checks = [
    0 => ($modules[0]['reviewed_source_version'] ?? '') === '1.2.38'
        && ($modules[0]['reviewed_db_version'] ?? '') === '1.4.4'
        && ($modules[0]['required_contract_version'] ?? '') === '1.2.2'
        && ($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0',
    3 => ($modules[3]['reviewed_source_version'] ?? '') === '1.2.0-rc2'
        && ($modules[3]['required_contract_version'] ?? '') === '1.4.0'
        && ($modules[3]['reviewed_source_branch'] ?? '') === 'codex/file-03-second-fresh-80-review-20260810'
        && ($modules[3]['reviewed_source_commit'] ?? '') === 'b862efb94be87980e96a5b864bc5c7aec49183d9',
    7 => ($modules[7]['reviewed_source_version'] ?? '') === '1.2.0' && ($modules[7]['required_contract_version'] ?? '') === '1.2.0',
    8 => ($modules[8]['reviewed_source_version'] ?? '') === '1.2.0'
        && ($modules[8]['reviewed_schema_version'] ?? '') === '3.1.0'
        && ($modules[8]['reviewed_source_branch'] ?? '') === 'codex/file08-new-governing-plans-completion-2026'
        && ($modules[8]['reviewed_source_commit'] ?? '') === 'cb302617d2a2def23de1883b8d9fead10bce7ef3'
        && ($modules[8]['required_canonical_contract_version'] ?? '') === '1.1.0'
        && ($modules[8]['required_public_contract_version'] ?? '') === '1.0.0',
    9 => ($modules[9]['reviewed_source_version'] ?? '') === '1.3.0'
        && ($modules[9]['required_contract_version'] ?? '') === '1.1.0'
        && ($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review'
        && ($modules[9]['reviewed_source_commit'] ?? '') === '9103310fc93d978b6e70661f024a079fc0971003',
    14 => ($modules[14]['reviewed_source_version'] ?? '') === '1.4.2'
        && ($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034'
        && ($modules[14]['required_primary_color'] ?? '') === '#087A4E',
    18 => ($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1',
    20 => ($modules[20]['governing_plan_version'] ?? '') === '4.1',
    21 => ($modules[21]['reviewed_package_version'] ?? '') === '1.0.5'
        && ($modules[21]['reviewed_runtime_version'] ?? '') === '1.0.3'
        && ($modules[21]['reviewed_schema_version'] ?? '') === '1.0.0'
        && ($modules[21]['reviewed_source_commit'] ?? '') === 'afeda8742d8e1ea62254823291a66f502058989c'
        && ($modules[21]['required_profile_timeline_api'] ?? '') === 'Sabri\\HomeNewsFeed\\ProfileTimeline::query',
    22 => ($modules[22]['reviewed_source_version'] ?? '') === '1.0.0-rc.3' && ($modules[22]['required_contract_versions']['rest_api'] ?? '') === '1.2.0',
    23 => ($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010',
    24 => ($modules[24]['reviewed_source_version'] ?? '') === '0.99.0'
        && ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b'
        && ($modules[24]['accepted_runtime_contract'] ?? '') === 'reviewed-current-0.99.0-source-contract-pending-staging',
    25 => ($modules[25]['candidate_version'] ?? '') === $runtime && ($modules[25]['canonical_primary_color'] ?? '') === '#087A4E' && ($modules[25]['design_token_owner'] ?? '') === 'file-25' && ($modules[25]['structural_shell_owner'] ?? '') === 'file-20',
];
foreach ($checks as $file => $ok) { $check($ok, 'Current reviewed contract mismatch for File ' . $file); }
foreach ($modules as $module) { if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'Source evidence must not promote staging acceptance.'); } }

$plan = json_decode($read('config/staging-test-plan.json'), true);
$check(is_array($plan) && ($plan['schema_version'] ?? null) === 3, 'Staging test plan schema 3 required.');
$ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file00-assertions','file03-current-public-dto','file03-future-public-experience','file03-component-provider','file03-route-parity','file08-clinic-projection','file07-directory-visual-contract','file09-doctor-decision','file14-visual-consumer','file21-current-profile-timeline-contract','file22-create-edit-contract','file23-private-management-contract','file24-current-assurance-contract','companion-token-bridge'] as $id) {
    $check(in_array($id, $ids, true), 'Staging plan missing ' . $id);
}

$verifier = $read('tools/verify-staging-artifact.php');
foreach (['File25_Staging_Artifact_Verifier','Embedded manifest or dependency matrix differs from detached evidence.','Unsafe, duplicate or symbolic entry detected in workflow artifact.','Unsafe, duplicate or symbolic entry detected in inner ZIP.','MAX_INNER_TOTAL_BYTES','staging_accepted','production_accepted'] as $marker) {
    $check(str_contains($verifier, $marker), 'Artifact verifier marker missing: ' . $marker);
}

$builder = $read('tools/build-staging-package.php');
foreach (['SOURCE_DATE_EPOCH','STAGING-MANIFEST.json','ZipArchive','verify_archive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'] as $marker) {
    $check(str_contains($builder, $marker), 'Package builder marker missing: ' . $marker);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 current release-engineering and dependency contracts\n";
