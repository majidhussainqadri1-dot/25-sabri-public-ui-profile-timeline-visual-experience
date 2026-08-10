<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) { $failures[] = $message; } };
$read = static fn (string $path): string => (string) (file_get_contents($root . '/' . $path) ?: '');

$main = $read('sabri-public-experience.php');
$native = $read('includes/class-native-integration.php');
$visibility = $read('includes/class-visibility-policy.php');
$repository = $read('includes/class-profile-repository.php');
$router = $read('includes/class-profile-router.php');
$renderer = $read('includes/class-profile-renderer.php');
$cards = $read('includes/class-content-cards.php');
$sections = $read('includes/class-section-service.php');
$rest = $read('includes/class-rest-controller.php');
$design = $read('includes/class-design-system.php');
$assets = $read('includes/class-assets.php');
$bridge = $read('assets/css/companion-token-bridge.css');
$marketplace = $read('includes/providers/class-file-18-marketplace-provider.php');
$dependencies = $read('includes/class-dependency-manager.php');
$matrix = json_decode($read('config/staging-dependencies.json'), true);
$plan = json_decode($read('config/staging-test-plan.json'), true);

$check(str_contains($main, "SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0'"), 'Runtime must remain 0.14.0 during current corrective cycle.');

foreach ([
    "FILE_00_MINIMUM_VERSION = '1.2.4'", "FILE_00_CONTRACT_VERSION = '1.1.2'",
    'SMC_Contracts::assertions', 'SMC_CONTRACT_VERSION', 'smc_founder_user_id',
] as $marker) {
    $check(str_contains($native, $marker), 'File 00 authoritative marker missing: ' . $marker);
}
foreach (['smc_get_profile', 'smc_professional_credentials', 'smc_clinics', 'SHOW TABLES', '$wpdb'] as $forbidden) {
    $check(! str_contains($native, $forbidden), 'Native integration contains forbidden File 00/table coupling: ' . $forbidden);
}
$check(! str_contains($native, 'calculated_age'), 'File 25 must not calculate age locally.');
$check(str_contains($native, 'public function is_minor'), 'Minor state must be owner-derived/fail-closed.');

foreach ([
    "FILE_03_MINIMUM_VERSION = '1.2.0-rc2'", "FILE_03_CONTRACT_VERSION = '1.4.0'",
    'spd_get_public_profile', 'spd_get_profile_contract_manifest', 'public_profile_projection',
] as $marker) {
    $check(str_contains($native, $marker), 'Current File 03 contract marker missing: ' . $marker);
}
foreach (['SPD_Helpers::get', 'SPD_Helpers::founder', 'SPD_Helpers::can_show_contact', 'SPD_Helpers::verification_status', "'_spd_profile_photo_id'", "'_spd_cover_photo_id'"] as $forbidden) {
    $check(! str_contains($native . $visibility . $repository, $forbidden), 'Removed/legacy File 03 integration remains: ' . $forbidden);
}
$check(str_contains($visibility, 'profile_contact($user_id, $field)'), 'File 25 must consume File 03 public-contact DTO truth.');
$check(str_contains($visibility, 'return $authoritative && $filtered === true;'), 'Contact hooks may revoke but never grant.');
$check(str_contains($repository, "profile_media_attachment_id($user_id, 'avatar')"), 'Avatar identity must come from current File 03 public DTO.');
$check(str_contains($repository, "profile_media_attachment_id($user_id, 'cover')"), 'Cover identity must come from current File 03 public DTO.');
$check(! str_contains($repository, 'get_avatar_url'), 'External avatar fallback is forbidden.');

foreach ([
    "FILE_09_MINIMUM_VERSION = '1.3.0'", "FILE_09_CONTRACT_VERSION = '1.1.0'",
    'GDO_Integration_Contracts::VERSION', 'gdo_file03_doctor_eligibility', 'can_practice',
] as $marker) {
    $check(str_contains($native, $marker), 'Current File 09 authoritative marker missing: ' . $marker);
}
$check(! str_contains($native, 'license_expiry'), 'File 25 must not derive doctor status from local license expiry.');
$check(str_contains($native, '$this->doctor_decision_is_current($decision)'), 'Doctor presentation must require current File 09 decision.');

foreach (['FILE_08_PUBLIC_PROJECTION_CONTRACT', 'swc_get_public_clinic_projection', 'swc_public_clinic_projection_contract'] as $marker) {
    $check(str_contains($native, $marker), 'File 08 public projection marker missing: ' . $marker);
}
$check(str_contains($native, "['name', 'address', 'country', 'city', 'hours', 'timezone']"), 'File 08 projection must use explicit allow-list.');
$check(str_contains($native, "['phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data']"), 'File 08 private exclusions must be required.');

$check(str_contains($native, 'smp_get_public_profile_listings'), 'File 18 public owner API must be required.');
foreach (['$wpdb', 'SMP_DB::table', 'SELECT p.*'] as $forbidden) {
    $check(! str_contains($marketplace, $forbidden), 'Marketplace adapter contains forbidden direct SQL: ' . $forbidden);
}

$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Founder spelling must remain frozen.');
$check(str_contains($repository, 'Public_URL::sanitize_same_site'), 'Profile media/canonical URLs need same-site enforcement.');
$check(str_contains($repository, 'monotonic_media_filter'), 'Profile media hooks must be revoke-only.');
$check(str_contains($router, '$non_file03_public_id'), 'File 25 slug rewrite must reserve File 03 UUID-like public-ID routes.');
$check(str_contains($router, 'profile_right_sidebar_available'), 'Three-column layout requires real right-sidebar content.');
$check(str_contains($renderer, "'@type' => 'BreadcrumbList'"), 'Profile SEO must include breadcrumbs.');
$check(str_contains($renderer, 'og:image:alt'), 'OpenGraph image needs safe alt text.');
$check(str_contains($cards, 'public static function normalize_public'), 'HTML and REST must share one card allow-list.');
$check(str_contains($sections, 'get_public_section'), 'Optional sections need structured public projections.');
$check(str_contains($sections, 'profile_cache_digest'), 'Section cache must bind to bounded profile projection.');
foreach (['/founder/knowledge','/founder/media','/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge','/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media','/providers/health'] as $route) {
    $check(str_contains($rest, $route), 'Public REST route missing: ' . $route);
}
$check(str_contains($rest, "'ETag'"), 'Public REST must emit deterministic ETags.');

foreach (['native-contact-consent','same-origin-profile-media','conditional-profile-layout','profile-breadcrumbs','structured-profile-section-rest'] as $scope) {
    $check(str_contains($design, $scope), 'Design contract scope missing: ' . $scope);
}
$check(str_contains($assets, 'companion-token-bridge.css'), 'Canonical File 25 style handle must include companion token bridge.');
foreach (['--sabri-primary: var(--sabri-visual-primary)', '--sabri-color-primary: var(--sabri-visual-primary)', '--sabri-text: var(--sabri-visual-text)', '--sabri-radius: var(--sabri-visual-radius-card)'] as $marker) {
    $check(str_contains($bridge, $marker), 'Companion token alias missing: ' . $marker);
}
$check(! str_contains(strtolower($bridge), '#ff8a1f') && ! str_contains(strtolower($bridge), '#15803d'), 'Companion bridge may not reintroduce stale primary colors.');

$check(is_array($matrix), 'Staging dependency matrix must be valid JSON.');
$check(($matrix['schema_version'] ?? null) === 3, 'Dependency matrix schema must be 3 after current companion reconciliation.');
$check(($matrix['governing_sources']['platform_master_plan'] ?? '') === 'Sabri Social Homeopathy Platform Definitive Master Plan 2026 v3.0', 'Master Plan v3.0 must remain governing constitution.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
foreach ([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25] as $file_number) {
    $check(isset($modules[$file_number]), 'Dependency/acceptance matrix missing File ' . $file_number . '.');
}
$check(($modules[3]['reviewed_source_version'] ?? '') === '1.2.0-rc2', 'File 03 current reviewed source mismatch.');
$check(($modules[3]['required_contract_version'] ?? '') === '1.4.0', 'File 03 contract must be 1.4.0.');
$check(($modules[3]['reviewed_source_commit'] ?? '') === 'b96f74457f54341701c6cdb1a57d42baa1100081', 'File 03 exact reviewed head mismatch.');
$check(($modules[7]['reviewed_source_version'] ?? '') === '1.2.0', 'File 07 current reviewed source mismatch.');
$check(($modules[7]['reviewed_source_commit'] ?? '') === '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844', 'File 07 exact head mismatch.');
$check(($modules[8]['required_public_contract_version'] ?? '') === '1.0.0', 'File 08 public contract must remain explicit.');
$check(($modules[9]['reviewed_source_version'] ?? '') === '1.3.0', 'File 09 current candidate must be 1.3.0.');
$check(($modules[9]['required_contract_version'] ?? '') === '1.1.0', 'File 09 integration contract must be 1.1.0.');
$check(($modules[9]['reviewed_source_commit'] ?? '') === '6d5c2850dbf86ce954e0c2fdef8adf36d2dbf1f1', 'File 09 exact reviewed candidate mismatch.');
$check(($modules[14]['reviewed_source_version'] ?? '') === '1.4.1' && ($modules[14]['required_primary_color'] ?? '') === '#087A4E', 'File 14 visual consumer baseline mismatch.');
$check(($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1', 'File 18 reviewed source mismatch.');
$check(($modules[20]['reviewed_source_version'] ?? '') === '1.2.0' && ($modules[20]['governing_plan_version'] ?? '') === '4.1', 'File 20 structural owner baseline mismatch.');
$check(($modules[22]['reviewed_source_version'] ?? '') === '1.0.0-rc.3', 'File 22 current candidate mismatch.');
$check(($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741', 'File 22 exact head mismatch.');
$check(($modules[22]['required_contract_versions']['rest_api'] ?? '') === '1.2.0', 'File 22 REST contract must be 1.2.0.');
$check(($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759', 'File 23 stable main baseline mismatch.');
$check(($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010', 'File 23 FPI24 feature head truth missing.');
$check(($modules[25]['candidate_version'] ?? '') === '0.14.0', 'File 25 candidate version mismatch.');
foreach ($modules as $module) {
    if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'No dependency may be promoted to staging-accepted by source evidence.'); }
}

$check(is_array($plan) && ($plan['schema_version'] ?? null) === 3, 'Staging test plan schema must be 3.');
$scenario_ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $scenario_ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file03-current-public-dto','file03-route-parity','file09-doctor-decision','file07-directory-visual-contract','file14-visual-consumer','file22-create-edit-contract','file23-private-management-contract','companion-token-bridge'] as $scenario) {
    $check(in_array($scenario, $scenario_ids, true), 'Current staging scenario missing: ' . $scenario);
}
$check(str_contains($dependencies, 'File 25 retains visual-token ownership'), 'Dependency status must preserve File 20/File 25 ownership boundary.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 current central/companion contract reconciliation\n";
