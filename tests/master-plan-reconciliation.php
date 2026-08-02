<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
$native = file_get_contents($root . '/includes/class-native-integration.php') ?: '';
$visibility = file_get_contents($root . '/includes/class-visibility-policy.php') ?: '';
$repository = file_get_contents($root . '/includes/class-profile-repository.php') ?: '';
$router = file_get_contents($root . '/includes/class-profile-router.php') ?: '';
$renderer = file_get_contents($root . '/includes/class-profile-renderer.php') ?: '';
$template = file_get_contents($root . '/templates/public-profile.php') ?: '';
$cards = file_get_contents($root . '/includes/class-content-cards.php') ?: '';
$sections = file_get_contents($root . '/includes/class-section-service.php') ?: '';
$rest = file_get_contents($root . '/includes/class-rest-controller.php') ?: '';
$design = file_get_contents($root . '/includes/class-design-system.php') ?: '';
$marketplace = file_get_contents($root . '/includes/providers/class-file-18-marketplace-provider.php') ?: '';
$dependencies = file_get_contents($root . '/includes/class-dependency-manager.php') ?: '';
$matrix_raw = file_get_contents($root . '/config/staging-dependencies.json');
$matrix = is_string($matrix_raw) ? json_decode($matrix_raw, true) : null;

$check(str_contains($main, "SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0'"), 'Authoritative-contract correction must carry runtime 0.14.0.');

foreach ([
    "FILE_00_MINIMUM_VERSION = '1.2.4'",
    "FILE_00_CONTRACT_VERSION = '1.1.2'",
    'SMC_Contracts::assertions',
    'SMC_CONTRACT_VERSION',
    'smc_founder_user_id',
] as $marker) {
    $check(str_contains($native, $marker), 'File 00 authoritative contract marker is missing: ' . $marker);
}
foreach (['smc_get_profile', 'smc_professional_credentials', 'smc_clinics', 'SHOW TABLES', '$wpdb'] as $forbidden) {
    $check(! str_contains($native, $forbidden), 'Native integration contains forbidden foreign authority/table access: ' . $forbidden);
}
$check(! str_contains($native, 'calculated_age'), 'File 25 must not calculate or consume a locally derived age field.');
$check(str_contains($native, 'File 25 must not calculate age'), 'Unknown minor state must be documented as fail closed.');

foreach (['GDO_VERSION', 'gdo_get_verification_decision', 'gdo_get_approved_snapshot', 'can_practice'] as $marker) {
    $check(str_contains($native, $marker), 'File 09 authoritative Doctor marker is missing: ' . $marker);
}
$check(! str_contains($native, 'license_expiry'), 'File 25 must not derive Doctor status from a locally queried license-expiry field.');
$check(str_contains($native, 'elseif ($this->is_verified_doctor($user_id))'), 'Displayed Doctor class must be tied to the authoritative verification policy.');

foreach (['FILE_08_PUBLIC_PROJECTION_CONTRACT', 'swc_get_public_clinic_projection', 'public_clinic_projection'] as $marker) {
    $check(str_contains($native, $marker), 'File 08 public clinic projection marker is missing: ' . $marker);
}
$check(str_contains($native, "['name', 'address', 'country', 'city', 'hours', 'timezone']"), 'Clinic public projection must use an explicit field allow-list.');

foreach (['SMP_REST::products', 'SMP_Utils::current_seller', 'smp_get_public_profile_listings'] as $marker) {
    $check(str_contains($marketplace, $marker), 'File 18 owner public API marker is missing: ' . $marker);
}
foreach (['$wpdb', 'SMP_DB::table', 'SELECT p.*'] as $forbidden) {
    $check(! str_contains($marketplace, $forbidden), 'Marketplace adapter contains forbidden direct query coupling: ' . $forbidden);
}
$check(str_contains($marketplace, "MINIMUM_VERSION = '1.2.0-RC1'"), 'Marketplace adapter must target the reviewed File 18 corrective source.');

$check(str_contains($visibility, 'SPD_Helpers::can_show_contact'), 'File 25 must consume File 03 public-contact consent.');
$check(! str_contains($visibility, 'elseif ($this->is_verified_doctor'), 'Verified Doctor status must not bypass public-contact consent.');
$check(str_contains($visibility, 'return $authoritative && $filtered'), 'Contact filters may revoke but never grant a denied contact projection.');
$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Canonical Founder spelling must be frozen in code.');
$check(! str_contains($repository, 'get_avatar_url'), 'File 25 must not silently use external avatar services.');
$check(str_contains($repository, 'Public_URL::sanitize_same_site'), 'Profile media must use same-origin URL enforcement.');
$check(str_contains($router, 'profile_right_sidebar_available'), 'Three-column profile layout must require real right-sidebar content.');
$check(str_contains($router, 'return $right_sidebar_available ? \'three\' : \'two\';'), 'Profile layout must default to two columns.');
$check(str_contains($renderer, "'@type' => 'BreadcrumbList'"), 'Profile SEO must include BreadcrumbList structured data.');
$check(str_contains($renderer, 'og:image:alt'), 'Profile OpenGraph media must include safe alternative text.');
$check(str_contains($renderer, 'missing_profile_status'), 'Deleted-profile policy must support explicit 410 tombstones while defaulting to 404.');
$check(str_contains($template, 'spux-breadcrumbs'), 'Public profiles must render visible accessible breadcrumbs.');
$check(str_contains($cards, 'public static function normalize_public'), 'HTML and REST must share one public card allow-list.');
$check(str_contains($sections, 'get_public_section'), 'Optional sections must expose structured public projections.');
$check(str_contains($sections, 'public_health'), 'Provider health must have a bounded aggregate public projection.');
$check(str_contains($sections, 'Provider IDs, versions'), 'Public health must explicitly keep provider identifiers private.');

foreach ([
    '/founder/knowledge', '/founder/media',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media', '/providers/health',
] as $route) {
    $check(str_contains($rest, $route), 'Planned public REST route is missing: ' . $route);
}
$check(str_contains($rest, "'ETag'"), 'Public REST responses must emit deterministic ETags.');
$check(! preg_match('/provider_id|native_id|projection_key/', $rest), 'Public REST controller must not expose provider/native/projection identifiers.');

foreach (['native-contact-consent', 'same-origin-profile-media', 'conditional-profile-layout', 'profile-breadcrumbs', 'structured-profile-section-rest'] as $scope) {
    $check(str_contains($design, $scope), 'Design contract is missing reconciled scope: ' . $scope);
}

$check(is_array($matrix), 'Staging dependency matrix must be valid JSON.');
$check(($matrix['schema_version'] ?? null) === 2, 'Dependency matrix schema must be upgraded for authoritative contracts.');
$check(($matrix['governing_sources']['platform_master_plan'] ?? '') === 'Sabri Social Homeopathy Platform Definitive Master Plan 2026 v3.0', 'Master Plan v3.0 must be the governing product constitution.');
$check(($matrix['governing_sources']['file_20_plan'] ?? '') === 'File 20 Harmonized Master Plan 2026 v4.1', 'File 20 plan v4.1 must govern the shell boundary.');

$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
foreach ([0, 3, 6, 8, 9, 10, 11, 12, 18, 20, 21, 24, 25] as $file_number) {
    $check(isset($modules[$file_number]), 'Dependency matrix is missing File ' . $file_number . '.');
}
$check(($modules[0]['reviewed_package_version'] ?? '') === '1.2.4', 'File 00 reviewed package must be 1.2.4.');
$check(($modules[0]['required_contract_version'] ?? '') === '1.1.2', 'File 00 public contract must be 1.1.2.');
$check(($modules[0]['foreign_table_reads_allowed'] ?? true) === false, 'File 00 foreign table reads must be prohibited.');
$check(($modules[8]['required_public_contract_version'] ?? '') === '1.0.0', 'File 08 public clinic contract must be explicit.');
$check(($modules[9]['reviewed_source_version'] ?? '') === '1.1.0', 'File 09 reviewed source must be 1.1.0.');
$check(($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1', 'File 18 reviewed source must be 1.2.0-RC1.');
$check(($modules[20]['reviewed_source_version'] ?? '') === '1.2.0', 'File 20 central-plan corrective source must be 1.2.0.');
$check(($modules[20]['governing_plan_version'] ?? '') === '4.1', 'File 20 governing plan must be v4.1.');
$check(($modules[25]['candidate_version'] ?? '') === '0.14.0', 'File 25 matrix version must match the correction candidate.');
$check(str_contains($dependencies, 'File 25 retains visual-token ownership'), 'Dependency status must preserve the File 20/File 25 ownership boundary.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Master Plan v3.0 authoritative-contract reconciliation\n";
