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
    $ledger = json_decode($read('config/review275-354-eighty-round-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Invalid Reviews 275-354 ledger JSON: ' . $exception->getMessage();
}
$check(($ledger['review_range']['first'] ?? null) === 275, 'Historical cycle must start at Review 275.');
$check(($ledger['review_range']['last'] ?? null) === 354, 'Historical cycle must end at Review 354.');
$check(($ledger['review_range']['count'] ?? null) === 80, 'Historical cycle must contain 80 reviews.');
$check(($ledger['defect_rounds'] ?? null) === array_merge(range(275, 316), [354]), 'Historical Reviews 275-354 defect ledger changed.');
$check(($ledger['clean_rounds'] ?? null) === range(317, 353), 'Historical Reviews 275-354 clean ledger changed.');
$reviews = (array) ($ledger['reviews'] ?? []);
$numbers = [];
foreach ($reviews as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) { continue; }
    $numbers[] = $row['review'];
}
$check($numbers === range(275, 354), 'Historical Reviews 275-354 must remain contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'Historical external gate must remain false: ' . $gate);
}
$check(str_contains(implode(' ', (array) ($ledger['external_acceptance_blockers'] ?? [])), 'UUID'), 'Canonical route-parity blocker must remain explicit.');

$native = $read('includes/class-native-integration.php');
$visibility = $read('includes/class-visibility-policy.php');
$repository = $read('includes/class-profile-repository.php');
$router = $read('includes/class-profile-router.php');
$timeline = $read('includes/class-timeline-service.php');
$sections = $read('includes/class-section-service.php');
$rest = $read('includes/class-rest-controller.php');
$cards = $read('includes/class-content-cards.php');
$future = $read('includes/class-future-public-experience.php');
$file24 = $read('includes/class-file-24-integration.php');
$publicCss = $read('assets/css/public.css');
$futureCss = $read('assets/css/future-public-experience.css');
$futureJs = $read('assets/js/future-public-experience.js');
$safeMode = $read('includes/class-safe-mode.php');
$upgrade = $read('includes/class-upgrade-manager.php');
$uninstall = $read('uninstall.php');
$builder = $read('tools/build-staging-package.php');
$verifier = $read('tools/verify-staging-artifact.php');
$workflow = $read('.github/workflows/ci.yml');
$matrix = json_decode($read('config/staging-dependencies.json'), true);
$plan = json_decode($read('config/staging-test-plan.json'), true);

foreach (["FILE_00_MINIMUM_VERSION = '1.2.38'","FILE_00_CONTRACT_VERSION = '1.2.2'","FILE_03_CONTRACT_VERSION = '1.4.0'","FILE_09_CONTRACT_VERSION = '1.1.0'",'SMC_Contracts::assertions','spd_get_public_profile','gdo_file03_doctor_eligibility'] as $marker) {
    $check(str_contains($native, $marker), 'Current native contract marker missing: ' . $marker);
}
$check(str_contains($visibility, 'return $authoritative && $filtered === true;'), 'Contact/profile visibility must remain revoke-only.');
$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Founder spelling freeze missing.');
$check(! str_contains($repository, 'get_avatar_url'), 'External avatar fallback remains forbidden.');
$check(str_contains($router, '$non_file03_public_id'), 'File 03 UUID route reservation missing.');
$check(str_contains($timeline, 'provider_errors') && str_contains($timeline, 'truncated') && str_contains($timeline, 'has_more'), 'Timeline truth markers missing.');
$check(str_contains($sections, 'profile_cache_digest'), 'Section cache/profile binding missing.');
foreach (["'ETag'","'Last-Modified'",'allow_public_request',"'status' => 429",'no-store, private, max-age=0'] as $marker) {
    $check(str_contains($rest, $marker), 'Current REST contract marker missing: ' . $marker);
}
$check(str_contains($cards, 'normalize_public') && ! str_contains($cards, '$wpdb'), 'Card public projection boundary missing.');
$check(str_contains($future, "'global_search_discovery_ranking_owner' => 'file-26'"), 'File 26 global search/ranking ownership missing.');
$check(str_contains($file24, "REVIEWED_MINIMUM_VERSION = '0.99.0'") && ! str_contains($file24, "'wp-cli:sabri file25 staging-probe'"), 'Current File 24 boundary mismatch.');
foreach (['prefers-reduced-motion: reduce','forced-colors: active'] as $marker) {
    $check(str_contains($publicCss . $futureCss, $marker), 'Accessibility CSS marker missing: ' . $marker);
}
$check(str_contains($futureJs, 'url.username || url.password') && str_contains($futureJs, 'url.origin !== location.origin'), 'Client same-origin defense missing.');
$check(str_contains($safeMode, 'enable') && str_contains($safeMode, 'is_active'), 'Safe Mode markers missing.');
$check(str_contains($upgrade, 'schema') && $uninstall !== '', 'Upgrade/uninstall evidence missing.');
foreach (['SOURCE_DATE_EPOCH','ZipArchive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'] as $marker) { $check(str_contains($builder, $marker), 'Builder defense missing: ' . $marker); }
foreach (['MAX_INNER_TOTAL_BYTES','Unsafe, duplicate or symbolic entry detected in inner ZIP.','staging_accepted','production_accepted'] as $marker) { $check(str_contains($verifier, $marker), 'Verifier defense missing: ' . $marker); }

$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Current dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
$check(($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0', 'Current File 00 exact source mismatch.');
$check(($modules[7]['reviewed_source_commit'] ?? '') === '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844', 'Current File 07 exact source mismatch.');
$check(($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review' && ($modules[9]['reviewed_source_commit'] ?? '') === '6fa0a5cb7063b6b821bd50c105c735470f589b80', 'Current File 09 RC6 source truth mismatch.');
$check(($modules[14]['reviewed_source_version'] ?? '') === '1.4.2' && ($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034', 'Current File 14 exact source mismatch.');
$check(($modules[20]['reviewed_source_version'] ?? '') === '1.4.12' && ($modules[20]['reviewed_source_commit'] ?? '') === '291486b22c7ed94b8be041192375b6d9b077fac5', 'Current File 20 exact source mismatch.');
$check(($modules[21]['reviewed_package_version'] ?? '') === '1.0.5' && ($modules[21]['reviewed_runtime_version'] ?? '') === '1.0.3' && ($modules[21]['reviewed_schema_version'] ?? '') === '1.0.0' && ($modules[21]['reviewed_source_commit'] ?? '') === 'afeda8742d8e1ea62254823291a66f502058989c', 'Current File 21 exact source/runtime truth mismatch.');
$check(($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741' && ($modules[22]['canonical_public_create_route'] ?? '') === '/create/', 'Current File 22 contract mismatch.');
$check(($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'Current File 23 contract mismatch.');
$check(($modules[24]['reviewed_source_version'] ?? '') === '0.99.0' && ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b', 'Current File 24 exact source mismatch.');
foreach ($modules as $module) { if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'Source evidence may not promote staging acceptance.'); } }

$check(is_array($plan) && ($plan['schema_version'] ?? null) === 3, 'Current staging plan schema 3 required.');
$scenarioIds = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $scenarioIds[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file03-route-parity','file09-doctor-decision','file21-current-profile-timeline-contract','file24-current-assurance-contract'] as $id) {
    $check(in_array($id, $scenarioIds, true), 'Current staging scenario missing: ' . $id);
}
$check(str_contains($workflow, 'actions/checkout@v5') && ! str_contains($workflow, 'actions/checkout@v4'), 'CI checkout version drift.');
$check(str_contains($workflow, 'php: ["8.0", "8.3"]'), 'PHP matrix drift.');
$check(str_contains($workflow, 'name: Deterministic staging candidate'), 'Deterministic packaging job missing.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 275-354\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 275-354 — historical ledger preserved with current companion truth\n";