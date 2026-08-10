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
    $ledger = json_decode($read('config/review515-594-fifth-eighty-current-truth-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Fifth-cycle ledger JSON invalid: ' . $exception->getMessage();
}

$check(($ledger['schema_version'] ?? null) === 1, 'Fifth-cycle ledger schema must be 1.');
$check(($ledger['file'] ?? null) === 25, 'Fifth-cycle ledger must govern File 25.');
$check(($ledger['review_range']['first'] ?? null) === 515, 'Fifth cycle must start at Review 515.');
$check(($ledger['review_range']['last'] ?? null) === 594, 'Fifth cycle must end at Review 594.');
$check(($ledger['review_range']['count'] ?? null) === 80, 'Fifth cycle must contain exactly 80 reviews.');
$expectedDefects = array_merge(range(515, 519), [594]);
$check(($ledger['defect_rounds'] ?? null) === $expectedDefects, 'Fifth-cycle defect rounds must be Reviews 515-519 plus reopened Review 594.');
$check(($ledger['clean_rounds'] ?? null) === range(520, 593), 'Fifth-cycle clean rounds must be exactly Reviews 520-593.');

$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) {
        $failures[] = 'Fifth-cycle review row is malformed.';
        continue;
    }
    $number = $row['review'];
    $numbers[] = $number;
    $defect = ($number >= 515 && $number <= 519) || $number === 594;
    $expected = $defect ? 'defect-found-corrected' : 'reviewed-clean';
    $check(($row['status'] ?? '') === $expected, 'Unexpected fifth-cycle status at Review ' . $number . '.');
    $check(is_string($row['focus'] ?? null) && trim((string) $row['focus']) !== '', 'Every fifth-cycle review requires a focus: ' . $number . '.');
    if ($defect) {
        $check(is_string($row['finding'] ?? null) && trim((string) $row['finding']) !== '', 'Defect review requires a finding: ' . $number . '.');
        $check(is_string($row['correction'] ?? null) && trim((string) $row['correction']) !== '', 'Defect review requires a correction: ' . $number . '.');
    }
}
$check($numbers === range(515, 594), 'Fifth-cycle review numbers must be contiguous, unique and complete.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth must remain unclaimed: ' . $gate . '.');
}
$check(str_contains(implode(' ', (array) ($ledger['external_acceptance_blockers'] ?? [])), 'UUID'), 'File03/File25 route-parity blocker must remain explicit.');

$matrix = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Current dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
$expectedCompanions = [
    0 => 'c37d0b101d0912bef1f26d0daf51a414d67907c0',
    3 => 'b96f74457f54341701c6cdb1a57d42baa1100081',
    7 => '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844',
    9 => '6fa0a5cb7063b6b821bd50c105c735470f589b80',
    14 => 'b9045a4229d052103a5546477f664ac88b6ff034',
    20 => '291486b22c7ed94b8be041192375b6d9b077fac5',
    21 => 'afeda8742d8e1ea62254823291a66f502058989c',
    22 => 'c3b775b66fbbda4a9dd9891d63c08c74e2178741',
    23 => 'a8a8c805f4730998ccb44bd95c87591836561759',
    24 => '0be43b3f424d7b53865587b2770479ca33f51a0b',
];
foreach ($expectedCompanions as $file => $commit) {
    $check(($modules[$file]['reviewed_source_commit'] ?? '') === $commit, 'Current exact source mismatch for File ' . $file . '.');
}
$check(($modules[0]['reviewed_source_version'] ?? '') === '1.2.38' && ($modules[0]['reviewed_db_version'] ?? '') === '1.4.4' && ($modules[0]['required_contract_version'] ?? '') === '1.2.2', 'File 00 current version/DB/contract truth mismatch.');
$check(($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review' && ($modules[9]['required_contract_version'] ?? '') === '1.1.0', 'File 09 current branch/contract truth mismatch.');
$check(($modules[21]['reviewed_package_version'] ?? '') === '1.0.5' && ($modules[21]['reviewed_runtime_version'] ?? '') === '1.0.3' && ($modules[21]['reviewed_schema_version'] ?? '') === '1.0.0' && ($modules[21]['required_profile_timeline_api'] ?? '') === 'Sabri\\HomeNewsFeed\\ProfileTimeline::query', 'File 21 current package/runtime/schema/API truth mismatch.');
$check(($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'File 23 Future Publishing Intelligence branch truth mismatch.');
foreach ($modules as $module) {
    if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'Source evidence may not promote staging acceptance.'); }
}

$rest = $read('includes/class-rest-controller.php');
foreach (['allow_public_request', "'Last-Modified'", 'request_matches_last_modified', 'request_has_if_none_match', 'wp_using_ext_object_cache', 'get_transient', 'set_transient', "'status' => 429", "'retry_after' => \$window", 'hash_hmac'] as $marker) {
    $check(str_contains($rest, $marker), 'Current REST contract marker missing: ' . $marker . '.');
}
$check(str_contains($rest, 'if ($status === 200 && $last_modified !== \'\' && ! self::request_has_if_none_match($request)'), 'If-None-Match must retain precedence over If-Modified-Since.');

$design = $read('includes/class-design-system.php');
foreach (["'design_token_owner' => 'file-25'", "'structural_layout_owner' => 'file-20'", "'last_modified' => true", "'conditional_get' => true", "'rate_limited' => true", "'rate_limit_identity_persisted_raw' => false", '#087A4E'] as $marker) {
    $check(str_contains($design, $marker), 'Current File 25 design/REST contract marker missing: ' . $marker . '.');
}

$publicUrl = $read('includes/class-public-url.php');
$check(str_contains($publicUrl, 'for ($depth = 0; $depth < 6; $depth++)') && str_contains($publicUrl, 'has_format_controls'), 'Recursive same-site URL hardening must remain present.');
$safeMode = $read('includes/class-safe-mode.php');
$check(str_contains($safeMode, 'public static function enable') && str_contains($safeMode, 'public static function is_active'), 'Safe Mode source contract missing.');
$upgrade = $read('includes/class-upgrade-manager.php');
$check(str_contains($upgrade, 'private const LOCK_TTL = 300;') && str_contains($upgrade, '(time() - $created_at) <= self::LOCK_TTL'), 'Upgrade stale-lock recovery contract missing.');
$uninstall = $read('uninstall.php');
$check(str_contains($uninstall, 'WP_UNINSTALL_PLUGIN') && ! str_contains($uninstall, 'DROP TABLE'), 'Uninstall must remain non-destructive.');

$plan = json_decode($read('config/staging-test-plan.json'), true);
$ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file03-route-parity','file09-doctor-decision','file21-current-profile-timeline-contract','file22-create-edit-contract','file23-private-management-contract','file24-current-assurance-contract','responsive-viewports','urdu-rtl','accessibility-input','upgrade-rollback','founder-acceptance'] as $id) {
    $check(in_array($id, $ids, true), 'Current staging scenario missing: ' . $id . '.');
}

$builder = $read('tools/build-staging-package.php');
foreach (['SOURCE_DATE_EPOCH','STAGING-MANIFEST.json','ZipArchive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'] as $marker) {
    $check(str_contains($builder, $marker), 'Deterministic builder/archive defense missing: ' . $marker . '.');
}
$artifactVerifier = $read('tools/verify-staging-artifact.php');
foreach (['MAX_INNER_TOTAL_BYTES','Unsafe, duplicate or symbolic entry detected in workflow artifact.','Unsafe, duplicate or symbolic entry detected in inner ZIP.','Embedded manifest or dependency matrix differs from detached evidence.','staging_accepted','production_accepted'] as $marker) {
    $check(str_contains($artifactVerifier, $marker), 'Independent artifact verifier defense missing: ' . $marker . '.');
}

$sourceMatrix = $read('config/source-completion-matrix.json');
$check(str_contains($sourceMatrix, 'Reviews 515-594'), 'Source-completion matrix must record Reviews 515-594.');
$check(str_contains($sourceMatrix, 'tests/review515-594-fifth-eighty-current-truth.php'), 'Source-completion matrix must cite fifth-cycle executable evidence.');
$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review515-594-fifth-eighty-current-truth.php'), 'Fifth-cycle gate must be listed in the governed Composer manifest.');
$check(is_file($root . '/docs/REVIEWS-515-594-FIFTH-EIGHTY-CURRENT-TRUTH-2026-08-10.md'), 'Fifth-cycle human record missing.');

$workflow = $read('.github/workflows/ci.yml');
$check(str_contains($workflow, 'actions/checkout@v5') && ! str_contains($workflow, 'actions/checkout@v4'), 'CI checkout version drift.');
$check(str_contains($workflow, 'php: ["8.0", "8.3"]'), 'CI must retain PHP 8.0 and PHP 8.3.');
$check(str_contains($workflow, 'name: Deterministic staging candidate'), 'Deterministic staging package job missing.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 515-594\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 515-594 — fifth eighty-round current-truth closure\n";