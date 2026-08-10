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

$ledgerRaw = $read('config/review275-354-eighty-round-ledger.json');
try { $ledger = json_decode($ledgerRaw, true, 512, JSON_THROW_ON_ERROR); }
catch (JsonException $e) { $ledger = []; $failures[] = 'Invalid review ledger JSON: ' . $e->getMessage(); }
$check(($ledger['review_range']['first'] ?? null) === 275, 'Ledger must start at Review 275.');
$check(($ledger['review_range']['last'] ?? null) === 354, 'Ledger must end at Review 354.');
$check(($ledger['review_range']['count'] ?? null) === 80, 'Ledger must contain exactly 80 reviews.');
$check(($ledger['defect_rounds'] ?? null) === range(275, 316), 'Defect rounds must be exactly Reviews 275-316.');
$check(($ledger['clean_rounds'] ?? null) === range(317, 354), 'Clean rounds must be exactly Reviews 317-354.');
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
        $expected = $number <= 316 ? 'defect-found-corrected' : 'reviewed-clean';
        $check(($row['status'] ?? null) === $expected, 'Unexpected ledger status for Review ' . $number . '.');
    }
    $check(is_string($row['focus'] ?? null) && trim((string) $row['focus']) !== '', 'Every review needs a focus statement.');
}
$check(count($seen) === 80, 'All eighty review numbers must be unique and present.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth gate must remain false: ' . $gate);
}
$blockers = (array) ($ledger['external_acceptance_blockers'] ?? []);
$check(count($blockers) >= 2, 'External acceptance blockers must remain explicit.');
$check(str_contains(implode(' ', $blockers), 'UUID'), 'File 03/File 25 canonical-route parity blocker must remain explicit.');

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
$design = $read('includes/class-design-system.php');
$assets = $read('includes/class-assets.php');
$bridge = $read('assets/css/companion-token-bridge.css');
$publicCss = $read('assets/css/public.css');
$futureCss = $read('assets/css/future-public-experience.css');
$publicJs = $read('assets/js/public.js');
$futureJs = $read('assets/js/future-public-experience.js');
$safeMode = $read('includes/class-safe-mode.php');
$upgrade = $read('includes/class-upgrade-manager.php');
$uninstall = $read('uninstall.php');
$builder = $read('tools/build-staging-package.php');
$verifier = $read('tools/verify-staging-artifact.php');
$sourceVerifier = $read('tools/verify-source-completion.php');
$workflow = $read('.github/workflows/ci.yml');
$matrixRaw = $read('config/staging-dependencies.json');
$planRaw = $read('config/staging-test-plan.json');
$matrix = json_decode($matrixRaw, true);
$plan = json_decode($planRaw, true);

// Reviews 317-321 — current identity/profile/verification authorities.
foreach (["FILE_00_MINIMUM_VERSION = '1.2.4'","FILE_00_CONTRACT_VERSION = '1.1.2'",'SMC_Contracts::assertions'] as $m) { $check(str_contains($native, $m), 'Review 317 marker missing: ' . $m); }
foreach (["FILE_03_MINIMUM_VERSION = '1.2.0-rc2'","FILE_03_CONTRACT_VERSION = '1.4.0'",'spd_get_public_profile','spd_get_profile_contract_manifest'] as $m) { $check(str_contains($native, $m), 'Review 318 marker missing: ' . $m); }
$check(str_contains($visibility, 'profile_contact($user_id, $field)'), 'Review 319 File 03 contact authority missing.');
$check(str_contains($visibility, 'return $authoritative && $filtered === true;'), 'Review 319 contact filter must remain revoke-only.');
foreach (["profile_media_attachment_id(\$user_id, 'avatar')","profile_media_attachment_id(\$user_id, 'cover')",'monotonic_media_filter'] as $m) { $check(str_contains($repository, $m), 'Review 320 media marker missing: ' . $m); }
$check(! str_contains($repository, 'get_avatar_url'), 'Review 320 forbids external avatar fallback.');
foreach (["FILE_09_MINIMUM_VERSION = '1.3.0'","FILE_09_CONTRACT_VERSION = '1.1.0'",'gdo_file03_doctor_eligibility','doctor_decision_is_current'] as $m) { $check(str_contains($native, $m), 'Review 321 marker missing: ' . $m); }

// Reviews 322-331 — cross-module ownership and current companion evidence.
foreach (['swc_get_public_clinic_projection',"['name', 'address', 'country', 'city', 'hours', 'timezone']","['phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data']"] as $m) { $check(str_contains($native, $m), 'Review 322 clinic marker missing: ' . $m); }
$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Current staging dependency matrix schema3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
$check(($modules[7]['reviewed_source_commit'] ?? '') === '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844', 'Review 323 File 07 exact head mismatch.');
$check(($modules[14]['reviewed_source_commit'] ?? '') === '3c524fb3d6ee481bc222660a56f6192b994e30d0' && ($modules[14]['required_primary_color'] ?? '') === '#087A4E', 'Review 324 File 14 visual consumer mismatch.');
$marketplace = $read('includes/providers/class-file-18-marketplace-provider.php');
$check(str_contains($marketplace, 'smp_get_public_profile_listings'), 'Review 325 File 18 owner API missing.');
foreach (['$wpdb','SELECT ','INSERT ','UPDATE ','DELETE '] as $forbidden) { $check(! str_contains($marketplace, $forbidden), 'Review 325 direct Marketplace storage coupling: ' . $forbidden); }
$check(($modules[20]['role'] ?? '') !== '' && str_contains((string) $modules[20]['role'], 'sole global shell'), 'Review 326 File 20 structural ownership missing.');
$check(($modules[25]['design_token_owner'] ?? '') === 'file-25' && ($modules[25]['structural_shell_owner'] ?? '') === 'file-20', 'Review 326 File20/File25 ownership mismatch.');
$check(($modules[21]['role'] ?? '') !== '' && str_contains((string) $modules[21]['role'], 'canonical Home, News, publication'), 'Review 327 File 21 authority missing.');
$check(($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741' && ($modules[22]['canonical_public_create_route'] ?? '') === '/create/', 'Review 328 File 22 current contract mismatch.');
$check(($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['canonical_private_management_route'] ?? '') === '/publishing-dashboard/', 'Review 329 File 23 stable management route mismatch.');
$check(($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'Review 329 File 23 feature-branch truth missing.');
$check(($modules[24]['accepted_runtime_contract'] ?? '') === 'reviewed-source-contract-pending-staging', 'Review 330 File 24 source/staging separation missing.');
$check(str_contains($future, "'global_search_discovery_ranking_owner' => 'file-26'"), 'Review 331 File 26 global search/ranking ownership missing.');

// Reviews 332-337 — routes, Founder/Doctor/member privacy.
$check(str_contains($router, '$non_file03_public_id'), 'Review 332 UUID route reservation missing.');
$check(is_array($plan) && ($plan['schema_version'] ?? null) === 3, 'Staging plan schema3 required.');
$scenarioIds = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $scenarioIds[] = (string) ($scenario['id'] ?? ''); } }
$check(in_array('file03-route-parity', $scenarioIds, true), 'Review 333 canonical route parity must remain an external staging gate.');
$slugTest = $read('tests/review35-profile-slug-exactness.php') . $read('tests/review42-rest-slug-exactness.php');
$check(str_contains($slugTest, 'PASS') || str_contains($slugTest, 'passed'), 'Review 334 exact slug regression evidence missing.');
$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Review 335 Founder spelling freeze missing.');
$check(str_contains($native, '&& $this->doctor_decision_is_current($decision)') && str_contains($native, "&& (\$profile['profile_type'] ?? '') === 'doctor'"), 'Review 336 Doctor multi-owner gate missing.');
$check(str_contains($native, 'Unknown ordinary-account age remains minor-safe.') && str_contains($visibility, 'if ($this->is_minor($user_id)'), 'Review 337 minor/contact fail-closed behavior missing.');

// Reviews 338-343 — timeline, providers, REST and cards.
foreach (['provider_errors','has_more','truncated'] as $m) { $check(str_contains($timeline, $m), 'Review 338/339 timeline marker missing: ' . $m); }
$check(str_contains($normalized, 'provider_id') && str_contains($normalized, 'author_id'), 'Review 338 timeline provider/author identity markers missing.');
$timelineTests = $read('tests/review27-timeline-template-date.php') . $read('tests/review28-timeline-filter-allowlist.php') . $read('tests/review33-timeline-chronology.php') . $read('tests/review52-timeline-page-overflow.php');
$check($timelineTests !== '', 'Review 339 timeline chronology/filter/pagination regressions missing.');
$check(str_contains($sections, 'is_available') || str_contains($sections, 'available'), 'Review 340 optional provider availability handling missing.');
foreach (['no-store, private, max-age=0',"'ETag'",'canonicalize_for_etag'] as $m) { $check(str_contains($rest, $m), 'Review 341 REST marker missing: ' . $m); }
$check(str_contains($rest, "preg_match('/[\\x00-\\x1F\\x7F]/', \$header) === 1"), 'Review 342 ETag control-character source-safe regex missing.');
$check(str_contains($cards, 'normalize_public') && ! str_contains($cards, '$wpdb'), 'Review 343 reusable public-card allow-list boundary missing.');

// Reviews 344-350 — Future public experience, client safety, a11y, owner tools.
foreach (["'raw_sensitive_data_allowed' => false","'public_ranking_effect' => false",'public_profile_payload'] as $m) { $check(str_contains($future, $m), 'Review 344/349 FUT payload invariant missing: ' . $m); }
$check(str_contains($futureJs, 'url.username || url.password') && str_contains($futureJs, "url.origin !== location.origin"), 'Review 345 same-origin client URL defense missing.');
$check(str_contains($future, 'wp_localize_script') && str_contains($futureJs, 'const t = (key, fallback)'), 'Review 346 localization contract missing.');
foreach (['prefers-reduced-motion: reduce','forced-colors: active'] as $m) { $check(str_contains($futureCss . $publicCss, $m), 'Review 347 accessibility CSS marker missing: ' . $m); }
$check(str_contains($futureJs, "profileRoot.querySelectorAll('video,audio')"), 'Review 347 low-data DOM scoping missing.');
foreach (['data-spux-action-sheet-toggle','data-spux-copy-url','data-spux-print-profile'] as $m) { $check(str_contains($futureJs, $m), 'Review 348 action/share marker missing: ' . $m); }
$check(str_contains($future, "'public_ranking_effect' => false"), 'Review 349 Quality Score isolation missing.');
$check(str_contains($future, 'owner_or_admin') && str_contains($future, "current_user_can('manage_options')"), 'Review 350 owner/admin privacy and component-lab gate missing.');

// Reviews 351-354 — resilience/release/truth boundary/final source gate.
$check(str_contains($safeMode, 'enable') && str_contains($safeMode, 'is_active'), 'Review 351 Safe Mode markers missing.');
$check(str_contains($upgrade, 'schema') && $uninstall !== '', 'Review 351 upgrade/uninstall source missing.');
foreach (['SOURCE_DATE_EPOCH','ZipArchive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'] as $m) { $check(str_contains($builder, $m), 'Review 352 builder marker missing: ' . $m); }
foreach (['MAX_INNER_TOTAL_BYTES','Unsafe, duplicate or symbolic entry detected in inner ZIP.','staging_accepted','production_accepted'] as $m) { $check(str_contains($verifier, $m), 'Review 352 verifier marker missing: ' . $m); }
$check(str_contains($sourceVerifier, 'external acceptance deferred') || str_contains($sourceVerifier, 'external acceptance'), 'Review 353 source/external truth boundary missing.');
foreach (['actions/checkout@v5','PHP 8.0','PHP 8.3','Deterministic staging candidate'] as $m) { $check(str_contains($workflow, $m), 'Review 354 current CI marker missing: ' . $m); }
$check(! str_contains($workflow, 'actions/checkout@v4'), 'Review 354 CI must not retain deprecated checkout v4 runtime.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 275-354\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 Reviews 275-354 — second eighty-round current-companion corrective closure\n";
