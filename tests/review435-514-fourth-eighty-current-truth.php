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

$check(($ledger['review_range']['first'] ?? null) === 435, 'Fourth cycle must start at Review 435.');
$check(($ledger['review_range']['last'] ?? null) === 514, 'Fourth cycle must end at Review 514.');
$check(($ledger['review_range']['count'] ?? null) === 80, 'Fourth cycle must contain exactly 80 reviews.');
$expectedDefects = range(435, 454);
$check(($ledger['defect_rounds'] ?? null) === $expectedDefects, 'Fourth-cycle defect rounds must be exactly 435-454.');
$check(($ledger['clean_rounds'] ?? null) === range(455, 514), 'Fourth-cycle clean rounds must be exactly 455-514.');
$reviews = (array) ($ledger['reviews'] ?? []);
$numbers = [];
foreach ($reviews as $review) {
    if (! is_array($review) || ! is_int($review['review'] ?? null)) { continue; }
    $number = $review['review'];
    $numbers[] = $number;
    $expected = $number <= 454 ? 'defect-found-corrected' : 'reviewed-clean';
    $check(($review['status'] ?? '') === $expected, 'Unexpected fourth-cycle status at Review ' . $number . '.');
    $check(is_string($review['focus'] ?? null) && trim((string) $review['focus']) !== '', 'Every fourth-cycle review needs a focus.');
    if ($number <= 454) {
        $check(is_string($review['finding'] ?? null) && trim((string) $review['finding']) !== '', 'Defect review needs a finding: ' . $number);
        $check(is_string($review['correction'] ?? null) && trim((string) $review['correction']) !== '', 'Defect review needs a correction: ' . $number);
    }
}
$check($numbers === range(435, 514), 'Fourth-cycle reviews must be contiguous and unique.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified','migration_state_verified_live'] as $key) {
    $check(($ledger['external_truth'][$key] ?? null) === false, 'External truth must remain unclaimed: ' . $key);
}
$check(str_contains(implode(' ', (array) ($ledger['external_acceptance_blockers'] ?? [])), 'UUID'), 'File03/File25 route-parity blocker must remain explicit.');

$native = $read('includes/class-native-integration.php');
foreach ([
    "FILE_00_MINIMUM_VERSION = '1.2.38'",
    "FILE_00_MAXIMUM_VERSION = '1.3.0'",
    "FILE_00_CONTRACT_VERSION = '1.2.2'",
    'SMC_Contracts::assertions',
    "FILE_09_MINIMUM_VERSION = '1.3.0'",
    "FILE_09_CONTRACT_VERSION = '1.1.0'",
] as $marker) {
    $check(str_contains($native, $marker), 'Current native-authority marker missing: ' . $marker);
}

$file24 = $read('includes/class-file-24-integration.php');
foreach ([
    "CONTRACT_VERSION = '1.1.0'",
    "REVIEWED_MINIMUM_VERSION = '0.99.0'",
    "REVIEWED_MAXIMUM_VERSION = '1.0.0'",
    "REVIEWED_SOURCE_COMMIT = '0be43b3f424d7b53865587b2770479ca33f51a0b'",
    'spcrc/module_manifests',
    'spcrc/request_security_state',
    "'staging_probe_cli' => 'wp sabri file25 staging-probe --expected-commit=<sha>'",
] as $marker) {
    $check(str_contains($file24, $marker), 'Current File24 marker missing: ' . $marker);
}
$check(! str_contains($file24, "'wp-cli:sabri file25 staging-probe'"), 'File24 manifest must not contain CLI pseudo-route.');

$matrix = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Staging dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
$check(($modules[0]['reviewed_source_version'] ?? '') === '1.2.38' && ($modules[0]['reviewed_db_version'] ?? '') === '1.4.4' && ($modules[0]['required_contract_version'] ?? '') === '1.2.2', 'File00 current version/DB/contract truth mismatch.');
$check(($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0', 'File00 exact current main mismatch.');
$check(($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review' && ($modules[9]['reviewed_source_commit'] ?? '') === '58313a67e1d21ad17c9a066e9a29c34245a0763e', 'File09 current RC6 branch/head mismatch.');
$check(($modules[14]['reviewed_source_version'] ?? '') === '1.4.2' && ($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034', 'File14 current main mismatch.');
$check(($modules[24]['reviewed_source_version'] ?? '') === '0.99.0' && ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b', 'File24 current main mismatch.');
foreach ($modules as $module) {
    if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'No module may be promoted to staging acceptance by source evidence.'); }
}

$staging = json_decode($read('config/staging-test-plan.json'), true);
$ids = [];
foreach ((array) ($staging['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file00-assertions','file09-doctor-decision','file14-visual-consumer','file24-current-assurance-contract','file03-route-parity'] as $id) {
    $check(in_array($id, $ids, true), 'Fourth-cycle staging scenario missing: ' . $id);
}

$verifier = $read('tools/verify-staging-artifact.php');
foreach ([
    '(\$modules[0][\'reviewed_source_version\'] ?? \'\') === \'1.2.38\'',
    '(\$modules[9][\'reviewed_source_commit\'] ?? \'\') === \'58313a67e1d21ad17c9a066e9a29c34245a0763e\'',
    '(\$modules[14][\'reviewed_source_version\'] ?? \'\') === \'1.4.2\'',
    '(\$modules[24][\'reviewed_source_version\'] ?? \'\') === \'0.99.0\'',
    'MAX_INNER_TOTAL_BYTES',
    'Embedded manifest or dependency matrix differs from detached evidence.',
] as $marker) {
    $check(str_contains($verifier, $marker), 'Fourth-cycle artifact verifier marker missing: ' . $marker);
}

$structure = $read('tools/verify-structure.php');
foreach ([
    "FILE_00_MINIMUM_VERSION = '1.2.38'",
    '(\$modules[14][\'reviewed_source_version\'] ?? \'\') === \'1.4.2\'',
    '(\$modules[24][\'reviewed_source_version\'] ?? \'\') === \'0.99.0\'',
    'file24-current-assurance-contract',
] as $marker) {
    $check(str_contains($structure, $marker), 'Fourth-cycle structural verifier marker missing: ' . $marker);
}

$sourceMatrix = $read('config/source-completion-matrix.json');
$check(str_contains($sourceMatrix, 'Reviews 435-514'), 'Source-completion matrix must record fourth-cycle lineage through Reviews 435-514.');
$check(str_contains($sourceMatrix, 'review435-514-fourth-eighty-current-truth.php'), 'Source-completion matrix must cite fourth-cycle executable evidence.');

$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review435-514-fourth-eighty-current-truth.php'), 'Fourth-cycle executable gate must be in the governed test manifest.');
$check(is_file($root . '/docs/REVIEWS-435-514-FOURTH-EIGHTY-CURRENT-TRUTH-2026-08-10.md'), 'Fourth-cycle human record missing.');

$workflow = $read('.github/workflows/ci.yml');
$check(str_contains($workflow, 'actions/checkout@v5'), 'CI must keep checkout@v5.');
$check(! str_contains($workflow, 'actions/checkout@v4'), 'Deprecated checkout@v4 must stay absent.');
$check(str_contains($workflow, 'php: ["8.0", "8.3"]'), 'CI must preserve PHP 8.0 and 8.3 matrix.');
$check(str_contains($workflow, 'name: Deterministic staging candidate'), 'Deterministic staging package job must remain present.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 435-514\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 435-514 — fourth eighty-round current-truth closure\n";
