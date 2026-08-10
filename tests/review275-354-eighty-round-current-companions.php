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

$ledgerRaw = $read('config/review275-354-eighty-round-ledger.json');
try { $ledger = json_decode($ledgerRaw, true, 512, JSON_THROW_ON_ERROR); }
catch (JsonException $e) { $ledger = []; $failures[] = 'Invalid review ledger JSON: ' . $e->getMessage(); }
$check(($ledger['review_range']['first'] ?? null) === 275, 'Ledger must start at Review 275.');
$check(($ledger['review_range']['last'] ?? null) === 354, 'Ledger must end at Review 354.');
$check(($ledger['review_range']['count'] ?? null) === 80, 'Ledger must contain exactly 80 reviews.');
$expectedDefects = array_merge(range(275, 316), [354]);
$check(($ledger['defect_rounds'] ?? null) === $expectedDefects, 'Defect rounds must be Reviews 275-316 and 354.');
$check(($ledger['clean_rounds'] ?? null) === range(317, 353), 'Clean rounds must be exactly Reviews 317-353.');
$reviews = is_array($ledger['reviews'] ?? null) ? $ledger['reviews'] : [];
$check(count($reviews) === 80, 'Ledger reviews array must contain exactly 80 entries.');
$seen = [];
foreach ($reviews as $row) {
    if (! is_array($row)) { $failures[] = 'Review ledger row is not an object.'; continue; }
    $number = $row['review'] ?? null;
    $check(is_int($number) && $number >= 275 && $number <= 354, 'Review number outside governed range.');
    if (is_int($number)) {
        $check(! isset($seen[$number]), 'Duplicate review number ' . $number . '.');
        $seen[$number] = true;
        $expected = ($number <= 316 || $number === 354) ? 'defect-found-corrected' : 'reviewed-clean';
        $check(($row['status'] ?? null) === $expected, 'Unexpected ledger status for Review ' . $number . '.');
    }
    $check(is_string($row['focus'] ?? null) && trim((string) $row['focus']) !== '', 'Every review needs a focus statement.');
}
$check(count($seen) === 80, 'All eighty review numbers must be unique and present.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth gate must remain false: ' . $gate);
}
$blockers = (array) ($ledger['external_acceptance_blockers'] ?? []);
$check(count($blockers) >= 2 && str_contains(implode(' ', $blockers), 'UUID'), 'File 03/File 25 canonical-route parity blocker must remain explicit.');

$native = $read('includes/class-native-integration.php');
$visibility = $read('includes/class-visibility-policy.php');
$repository = $read('includes/class-profile-repository.php');
$router = $read('includes/class-profile-router.php');
$timeline = $read('includes/class-timeline-service.php');
$normalized = $read('includes/class-normalized-timeline-item.php');
$sections = $read('includes/class-section-service.php');
$rest = $read('includes/class-rest-controller.php');
$cards = $read('includes/class-content-cards.php');
$future = $read('includes/class-future-public-experience.php');
$file24 = $read('includes/class-file-24-integration.php');
$bridge = $read('assets/css/companion-token-bridge.css');
$publicCss = $read('assets/css/public.css');
$futureCss = $read('assets/css/future-public-experience.css');
$futureJs = $read('assets/js/future-public-experience.js');
$safeMode = $read('includes/class-safe-mode.php');
$upgrade = $read('includes/class-upgrade-manager.php');
$uninstall = $read('uninstall.php');
$builder = $read('tools/build-staging-package.php');
$verifier = $read('tools/verify-staging-artifact.php');
$sourceVerifier = $read('tools/verify-source-completion.php');
$workflow = $read('.github/workflows/ci.yml');
$matrix = json_decode($read('config/staging-dependencies.json'), true);
$plan = json_decode($read('config/staging-test-plan.json'), true);

foreach (["FILE_00_MINIMUM_VERSION = '1.2.38'","FILE_00_CONTRACT_VERSION = '1.2.2'",'SMC_Contracts::assertions'] as $m) { $check(str_contains($native, $m), 'Current File 00 marker missing: ' . $m); }
foreach (["FILE_03_MINIMUM_VERSION = '1.2.0-rc2'","FILE_03_CONTRACT_VERSION = '1.4.0'",'spd_get_public_profile','spd_get_profile_contract_manifest'] as $m) { $check(str_contains($native, $m), 'Review 318 marker missing: ' . $m); }
$check(str_contains($visibility, 'profile_contact($user_id, $field)') && str_contains($visibility, 'return $authoritative && $filtered === true;'), 'Review 319 File 03 contact revoke-only authority missing.');
foreach (["profile_media_attachment_id(\$user_id, 'avatar')","profile_media_attachment_id(\$user_id, 'cover')",'monotonic_media_filter'] as $m) { $check(str_contains($repository, $m), 'Review 320 media marker missing: ' . $m); }
$check(! str_contains($repository, 'get_avatar_url'), 'Review 320 forbids external avatar fallback.');
foreach (["FILE_09_MINIMUM_VERSION = '1.3.0'","FILE_09_CONTRACT_VERSION = '1.1.0'",'gdo_file03_doctor_eligibility','doctor_decision_is_current'] as $m) { $check(str_contains($native, $m), 'Review 321 marker missing: ' . $m); }
foreach (['swc_get_public_clinic_projection',"['name', 'address', 'country', 'city', 'hours', 'timezone']","['phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data']"] as $m) { $check(str_contains($native, $m), 'Review 322 clinic marker missing: ' . $m); }

$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Current staging dependency matrix schema3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
$check(($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0' && ($modules[0]['required_contract_version'] ?? '') === '1.2.2', 'Current File 00 source/contract mismatch.');
$check(($modules[7]['reviewed_source_commit'] ?? '') === '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844', 'Review 323 File 07 exact head mismatch.');
$check(($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034' && ($modules[14]['reviewed_source_version'] ?? '') === '1.4.2' && ($modules[14]['required_primary_color'] ?? '') === '#087A4E', 'Current File 14 visual consumer mismatch.');
$marketplace = $read('includes/providers/class-file-18-marketplace-provider.php');
$check(str_contains($marketplace, 'smp_get_public_profile_listings'), 'Review 325 File 18 owner API missing.');
foreach (['$wpdb','SELECT ','INSERT ','UPDATE ','DELETE '] as $forbidden) { $check(! str_contains($marketplace, $forbidden), 'Review 325 direct Marketplace storage coupling: ' . $forbidden); }
$check(str_contains((string) ($modules[20]['role'] ?? ''), 'sole global shell') && ($modules[25]['design_token_owner'] ?? '') === 'file-25' && ($modules[25]['structural_shell_owner'] ?? '') === 'file-20', 'Review 326 File20/File25 ownership mismatch.');
$check(str_contains((string) ($modules[21]['role'] ?? ''), 'canonical Home, News, publication'), 'Review 327 File 21 authority missing.');
$check(($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741' && ($modules[22]['canonical_public_create_route'] ?? '') === '/create/', 'Review 328 File 22 current contract mismatch.');
$check(($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['canonical_private_management_route'] ?? '') === '/publishing-dashboard/' && ($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'Review 329 File 23 stable/FPI truth mismatch.');
$check(($modules[24]['reviewed_source_version'] ?? '') === '0.99.0' && ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b', 'Current File 24 source truth mismatch.');
$check(str_contains($file24, "REVIEWED_MINIMUM_VERSION = '0.99.0'") && ! str_contains($file24, "'wp-cli:sabri file25 staging-probe'"), 'Current File 24 manifest compatibility missing.');
$check(($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review' && ($modules[9]['reviewed_source_commit'] ?? '') === '58313a67e1d21ad17c9a066e9a29c34245a0763e', 'Current File 09 RC6 source truth mismatch.');
$check(str_contains($future, "'global_search_discovery_ranking_owner' => 'file-26'"), 'Review 331 File 26 ownership missing.');

$check(str_contains($router, '$non_file03_public_id'), 'Review 332 UUID route reservation missing.');
$check(is_array($plan) && ($plan['schema_version'] ?? null) === 3, 'Staging plan schema3 required.');
$scenarioIds = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $scenarioIds[] = (string) ($scenario['id'] ?? ''); } }
$check(in_array('file03-route-parity', $scenarioIds, true), 'Review 333 canonical route parity must remain external.');
$check(in_array('file24-current-assurance-contract', $scenarioIds, true), 'Current File 24 staging scenario must remain explicit.');
$slugTest = $read('tests/review35-profile-slug-exactness.php') . $read('tests/review42-rest-slug-exactness.php');
$check($slugTest !== '', 'Review 334 exact slug regression evidence missing.');
$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Review 335 Founder spelling freeze missing.');
$check(str_contains($native, '&& $this->doctor_decision_is_current($decision)') && str_contains($native, "&& (\$profile['profile_type'] ?? '') === 'doctor'"), 'Review 336 Doctor multi-owner gate missing.');
$check(str_contains($native, 'Unknown ordinary-account age remains minor-safe.') && str_contains($visibility, 'if ($this->is_minor($user_id)'), 'Review 337 minor/contact fail-closed behavior missing.');

foreach (['provider_errors','has_more','truncated'] as $m) { $check(str_contains($timeline, $m), 'Review 338/339 timeline marker missing: ' . $m); }
$check(str_contains($normalized, 'provider_id') && str_contains($normalized, 'author_id'), 'Review 338 provider/author identity markers missing.');
$timelineTests = $read('tests/review27-timeline-template-date.php') . $read('tests/review28-timeline-filter-allowlist.php') . $read('tests/review33-timeline-chronology.php') . $read('tests/review52-timeline-page-overflow.php');
$check($timelineTests !== '', 'Review 339 timeline regression evidence missing.');
$check(str_contains($sections, 'available'), 'Review 340 provider availability handling missing.');
foreach (['no-store, private, max-age=0',"'ETag'",'canonicalize_for_etag'] as $m) { $check(str_contains($rest, $m), 'Review 341 REST marker missing: ' . $m); }
$check(str_contains($rest, "preg_match('/[\\x00-\\x1F\\x7F]/', \$header) === 1"), 'Review 342 source-safe ETag regex missing.');
$check(str_contains($cards, 'normalize_public') && ! str_contains($cards, '$wpdb'), 'Review 343 content-card allow-list boundary missing.');

foreach (["'raw_sensitive_data_allowed' => false","'public_ranking_effect' => false",'public_profile_payload'] as $m) { $check(str_contains($future, $m), 'Review 344/349 FUT invariant missing: ' . $m); }
$check(str_contains($futureJs, 'url.username || url.password') && str_contains($futureJs, "url.origin !== location.origin"), 'Review 345 client URL defense missing.');
$check(str_contains($future, 'wp_localize_script') && str_contains($futureJs, 'const t = (key, fallback)'), 'Review 346 localization contract missing.');
foreach (['prefers-reduced-motion: reduce','forced-colors: active'] as $m) { $check(str_contains($futureCss . $publicCss, $m), 'Review 347 a11y CSS marker missing: ' . $m); }
$check(str_contains($futureJs, "profileRoot.querySelectorAll('video,audio')"), 'Review 347 low-data DOM scoping missing.');
foreach (['data-spux-action-sheet-toggle','data-spux-copy-url','data-spux-print-profile'] as $m) { $check(str_contains($futureJs, $m), 'Review 348 action/share marker missing: ' . $m); }
$check(str_contains($future, "'public_ranking_effect' => false"), 'Review 349 Quality Score isolation missing.');
$check(str_contains($future, 'owner_or_admin') && str_contains($future, "current_user_can('manage_options')"), 'Review 350 owner/admin gate missing.');

$check(str_contains($safeMode, 'enable') && str_contains($safeMode, 'is_active'), 'Review 351 Safe Mode markers missing.');
$check(str_contains($upgrade, 'schema') && $uninstall !== '', 'Review 351 upgrade/uninstall source missing.');
foreach (['SOURCE_DATE_EPOCH','ZipArchive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'] as $m) { $check(str_contains($builder, $m), 'Review 352 builder marker missing: ' . $m); }
foreach (['MAX_INNER_TOTAL_BYTES','Unsafe, duplicate or symbolic entry detected in inner ZIP.','staging_accepted','production_accepted'] as $m) { $check(str_contains($verifier, $m), 'Review 352 verifier marker missing: ' . $m); }
$check(str_contains($sourceVerifier, 'external acceptance deferred') || str_contains($sourceVerifier, 'external acceptance'), 'Review 353 source/external truth boundary missing.');

// Review 354 final exact-head CI lens: the first form searched for rendered job
// labels rather than literal YAML, so the QA assertion itself was corrected.
$check(str_contains($workflow, 'actions/checkout@v5'), 'Review 354 checkout runtime must be v5.');
$check(! str_contains($workflow, 'actions/checkout@v4'), 'Review 354 checkout v4 must be absent.');
$check(str_contains($workflow, 'php: ["8.0", "8.3"]'), 'Review 354 PHP 8.0/8.3 matrix evidence missing.');
$check(str_contains($workflow, 'name: Deterministic staging candidate'), 'Review 354 deterministic candidate job missing.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 275-354\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 Reviews 275-354 — prior eighty-round gate remains current after fourth-cycle companion reconciliation\n";
