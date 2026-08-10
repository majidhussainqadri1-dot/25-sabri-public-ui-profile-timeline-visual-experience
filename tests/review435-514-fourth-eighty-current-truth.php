<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) { $failures[] = $message; } };
$read = static function (string $path) use ($root, &$failures): string {
    $value = @file_get_contents($root . '/' . $path);
    if (! is_string($value)) { $failures[] = 'Unreadable evidence: ' . $path; return ''; }
    return $value;
};

try {
    $ledger = json_decode($read('config/review435-514-fourth-eighty-current-truth-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Fourth-cycle ledger JSON invalid: ' . $exception->getMessage();
}
$check(($ledger['review_range']['first'] ?? null) === 435 && ($ledger['review_range']['last'] ?? null) === 514 && ($ledger['review_range']['count'] ?? null) === 80, 'Fourth-cycle historical range/count changed.');
$check(($ledger['defect_rounds'] ?? null) === array_merge(range(435, 454), [514]), 'Fourth-cycle historical defect ledger changed.');
$check(($ledger['clean_rounds'] ?? null) === range(455, 513), 'Fourth-cycle historical clean ledger changed.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (is_array($row) && is_int($row['review'] ?? null)) { $numbers[] = $row['review']; }
}
$check($numbers === range(435, 514), 'Fourth-cycle historical reviews must remain contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified','migration_state_verified_live'] as $key) {
    $check(($ledger['external_truth'][$key] ?? null) === false, 'Fourth-cycle external truth must remain false: ' . $key . '.');
}
$check(str_contains(implode(' ', (array) ($ledger['external_acceptance_blockers'] ?? [])), 'UUID'), 'Fourth-cycle File03/File25 route-parity blocker must remain explicit.');

$native = $read('includes/class-native-integration.php');
foreach (["FILE_00_MINIMUM_VERSION = '1.2.38'", "FILE_00_CONTRACT_VERSION = '1.2.2'", "FILE_09_MINIMUM_VERSION = '1.3.0'", "FILE_09_CONTRACT_VERSION = '1.1.0'", 'SMC_Contracts::assertions', 'gdo_file03_doctor_eligibility'] as $marker) {
    $check(str_contains($native, $marker), 'Current native-authority marker missing: ' . $marker . '.');
}
$file24 = $read('includes/class-file-24-integration.php');
$check(str_contains($file24, "REVIEWED_MINIMUM_VERSION = '0.99.0'") && str_contains($file24, "REVIEWED_SOURCE_COMMIT = '0be43b3f424d7b53865587b2770479ca33f51a0b'") && ! str_contains($file24, "'wp-cli:sabri file25 staging-probe'"), 'Current File 24 assurance boundary mismatch.');

$matrix = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Current dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
$check(($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0' && ($modules[0]['required_contract_version'] ?? '') === '1.2.2', 'Current File 00 source/contract mismatch.');
$check(($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review' && ($modules[9]['reviewed_source_commit'] ?? '') === '6fa0a5cb7063b6b821bd50c105c735470f589b80', 'Current File 09 source truth mismatch.');
$check(($modules[14]['reviewed_source_version'] ?? '') === '1.4.2' && ($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034', 'Current File 14 source truth mismatch.');
$check(($modules[20]['reviewed_source_version'] ?? '') === '1.4.12' && ($modules[20]['reviewed_source_commit'] ?? '') === '291486b22c7ed94b8be041192375b6d9b077fac5', 'Current File 20 source truth mismatch.');
$check(($modules[21]['reviewed_package_version'] ?? '') === '1.0.5' && ($modules[21]['reviewed_runtime_version'] ?? '') === '1.0.3' && ($modules[21]['reviewed_schema_version'] ?? '') === '1.0.0' && ($modules[21]['reviewed_source_commit'] ?? '') === 'afeda8742d8e1ea62254823291a66f502058989c', 'Current File 21 source/runtime truth mismatch.');
$check(($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741', 'Current File 22 source truth mismatch.');
$check(($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'Current File 23 source truth mismatch.');
$check(($modules[24]['reviewed_source_version'] ?? '') === '0.99.0' && ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b', 'Current File 24 source truth mismatch.');
foreach ($modules as $module) { if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'Source evidence may not promote staging acceptance.'); } }

$staging = json_decode($read('config/staging-test-plan.json'), true);
$ids = [];
foreach ((array) ($staging['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file03-route-parity','file09-doctor-decision','file14-visual-consumer','file21-current-profile-timeline-contract','file24-current-assurance-contract'] as $id) {
    $check(in_array($id, $ids, true), 'Current staging scenario missing: ' . $id . '.');
}

$verifier = $read('tools/verify-staging-artifact.php');
foreach (['6fa0a5cb7063b6b821bd50c105c735470f589b80','afeda8742d8e1ea62254823291a66f502058989c','MAX_INNER_TOTAL_BYTES','Embedded manifest or dependency matrix differs from detached evidence.'] as $marker) {
    $check(str_contains($verifier, $marker), 'Current artifact verifier evidence missing: ' . $marker . '.');
}
$structure = $read('tools/verify-structure.php');
foreach (["FILE_00_MINIMUM_VERSION = '1.2.38'",'6fa0a5cb7063b6b821bd50c105c735470f589b80','afeda8742d8e1ea62254823291a66f502058989c','file24-current-assurance-contract'] as $marker) {
    $check(str_contains($structure, $marker), 'Current structural verifier evidence missing: ' . $marker . '.');
}

$sourceMatrix = $read('config/source-completion-matrix.json');
$check(str_contains($sourceMatrix, 'REVIEW-CORRECTION-LINEAGE-20-93'), 'Current source-completion review-lineage group missing.');
$check(str_contains($sourceMatrix, 'Reviews 515-594') && str_contains($sourceMatrix, 'tests/review515-594-fifth-eighty-current-truth.php'), 'Current source-completion matrix must point to the latest fifth-cycle evidence.');
$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review435-514-fourth-eighty-current-truth.php') && str_contains($composer, 'tests/review515-594-fifth-eighty-current-truth.php'), 'Governed manifest must preserve fourth and fifth review gates.');
$check(is_file($root . '/docs/REVIEWS-435-514-FOURTH-EIGHTY-CURRENT-TRUTH-2026-08-10.md'), 'Fourth-cycle human record missing.');

$workflow = $read('.github/workflows/ci.yml');
$check(str_contains($workflow, 'actions/checkout@v5') && ! str_contains($workflow, 'actions/checkout@v4'), 'CI checkout version drift.');
$check(str_contains($workflow, 'php: ["8.0", "8.3"]'), 'CI PHP matrix drift.');
$check(str_contains($workflow, 'name: Deterministic staging candidate'), 'Deterministic package job missing.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 435-514\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 435-514 — historical ledger preserved with latest current-truth evidence\n";