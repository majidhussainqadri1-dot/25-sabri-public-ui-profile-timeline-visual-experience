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
$file20 = $read('includes/class-file-20-integration.php');
$file24 = $read('includes/class-file-24-integration.php');
$admin = $read('includes/class-admin-integration.php');
$component = $read('includes/class-component-api.php');
$assets = $read('includes/class-assets.php');
$bridge = $read('assets/css/companion-token-bridge.css');
$marketplace = $read('includes/providers/class-file-18-marketplace-provider.php');
$dependencies = $read('includes/class-dependency-manager.php');
$matrix = json_decode($read('config/staging-dependencies.json'), true);
$plan = json_decode($read('config/staging-test-plan.json'), true);

$check(str_contains($main, "SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0'"), 'Runtime must remain 0.14.0.');

foreach ([
    "FILE_00_MINIMUM_VERSION = '1.2.38'", "FILE_00_CONTRACT_VERSION = '1.2.2'", 'SMC_Contracts::assertions', 'smc_founder_user_id',
    "FILE_03_MINIMUM_VERSION = '1.2.0-rc2'", "FILE_03_CONTRACT_VERSION = '1.4.0'", 'spd_get_public_profile', 'spd_get_profile_contract_manifest',
    "FILE_09_MINIMUM_VERSION = '1.3.0'", "FILE_09_CONTRACT_VERSION = '1.1.0'", 'GDO_Integration_Contracts::VERSION', 'gdo_file03_doctor_eligibility',
    "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0'", 'swc_get_public_clinic_projection',
    "FILE_18_MINIMUM_VERSION = '1.2.0-RC1'", 'smp_get_public_profile_listings',
] as $marker) {
    $check(str_contains($native, $marker), 'Authoritative native marker missing: ' . $marker);
}
foreach (['$wpdb','SHOW TABLES','smc_get_profile','smc_professional_credentials','smc_clinics','calculated_age','license_expiry','SPD_Helpers::get','SPD_Helpers::can_show_contact'] as $forbidden) {
    $check(! str_contains($native, $forbidden), 'Forbidden native coupling remains: ' . $forbidden);
}

$check(str_contains($visibility, 'profile_contact($user_id, $field)'), 'File 03 public-contact DTO must remain authoritative.');
$check(str_contains($visibility, 'return $authoritative && $filtered === true;'), 'Contact filter may revoke but not grant.');
$check(str_contains($repository, "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'"), 'Founder spelling drift.');
$check(str_contains($repository, 'profile_media_attachment_id($user_id, \'avatar\')'), 'Avatar must come from public DTO.');
$check(str_contains($repository, 'profile_media_attachment_id($user_id, \'cover\')'), 'Cover must come from public DTO.');
$check(str_contains($repository, 'Public_URL::sanitize_same_site'), 'Profile media URLs require same-site enforcement.');
$check(! str_contains($repository, 'get_avatar_url'), 'External avatar fallback is forbidden.');
$check(str_contains($router, '$non_file03_public_id'), 'File 03 UUID/public-ID route family must remain reserved.');
$check(str_contains($router, 'profile_right_sidebar_available'), 'Three-column layout needs real right-sidebar content.');
$check(str_contains($renderer, "'@type' => 'BreadcrumbList'"), 'Breadcrumb structured data missing.');
$check(str_contains($renderer, 'og:image:alt'), 'OpenGraph alt text missing.');
$check(str_contains($cards, 'public static function normalize_public'), 'Cards require shared public allow-list.');
$check(str_contains($sections, 'get_public_section') && str_contains($sections, 'profile_cache_digest'), 'Structured section/cache binding missing.');

foreach (['/founder/knowledge','/founder/media','/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge','/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media','/providers/health'] as $route) {
    $check(str_contains($rest, $route), 'Public REST route missing: ' . $route);
}
foreach (["'ETag'", "'Last-Modified'", 'allow_public_request', 'sabri_public_experience/rest_rate_limit', "'status' => 429"] as $marker) {
    $check(str_contains($rest, $marker), 'REST §72 contract marker missing: ' . $marker);
}

foreach (['native-contact-consent','same-origin-profile-media','conditional-profile-layout','profile-breadcrumbs','structured-profile-section-rest'] as $scope) {
    $check(str_contains($design, $scope), 'Design contract scope missing: ' . $scope);
}
$check(str_contains($file20, "REVIEWED_MINIMUM_VERSION = '1.4.12'") && str_contains($file20, "REVIEWED_SOURCE_COMMIT = '291486b22c7ed94b8be041192375b6d9b077fac5'"), 'File 20 exact current structural contract missing.');
$check(str_contains($file24, "REVIEWED_MINIMUM_VERSION = '0.99.0'") && str_contains($file24, "REVIEWED_SOURCE_COMMIT = '0be43b3f424d7b53865587b2770479ca33f51a0b'"), 'File 24 exact current assurance contract missing.');
$check(! str_contains($file24, "'wp-cli:sabri file25 staging-probe'"), 'File 24 manifest contains forbidden CLI pseudo-route.');
$check(str_contains($design, 'sabri_shell_file25_visual_contract') && str_contains($design, "'primary_color' => '#087A4E'"), 'File 25 canonical visual bridge/green missing.');
$check(str_contains($admin, "SHELL_PARENT_SLUG = 'sabri-shell'"), 'File 25 admin must attach to File 20 when compatible.');
$check(str_contains($component, 'Component API cannot bind a second timeline registry'), 'Component API must preserve one timeline registry.');
$check(str_contains($assets, 'companion-token-bridge.css'), 'Companion token bridge must load.');
foreach (['--sabri-primary: var(--sabri-visual-primary)','--sabri-color-primary: var(--sabri-visual-primary)','--sabri-text: var(--sabri-visual-text)','--sabri-radius: var(--sabri-visual-radius-card)'] as $marker) {
    $check(str_contains($bridge, $marker), 'Companion token alias missing: ' . $marker);
}
$check(! str_contains(strtolower($bridge), '#ff8a1f') && ! str_contains(strtolower($bridge), '#15803d'), 'Stale primary colors reintroduced.');
foreach (['$wpdb','SMP_DB::table','SELECT p.*'] as $forbidden) {
    $check(! str_contains($marketplace, $forbidden), 'Marketplace adapter contains forbidden direct SQL: ' . $forbidden);
}

$check(is_array($matrix) && ($matrix['schema_version'] ?? null) === 3, 'Staging dependency matrix schema 3 required.');
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
foreach ([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25] as $file) {
    $check(isset($modules[$file]), 'Dependency matrix missing File ' . $file . '.');
}
$checks = [
    0 => ($modules[0]['reviewed_source_version'] ?? '') === '1.2.38' && ($modules[0]['reviewed_db_version'] ?? '') === '1.4.4' && ($modules[0]['required_contract_version'] ?? '') === '1.2.2' && ($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0',
    3 => ($modules[3]['reviewed_source_version'] ?? '') === '1.2.0-rc2' && ($modules[3]['required_contract_version'] ?? '') === '1.4.0' && ($modules[3]['reviewed_source_commit'] ?? '') === 'b96f74457f54341701c6cdb1a57d42baa1100081',
    7 => ($modules[7]['reviewed_source_version'] ?? '') === '1.2.0' && ($modules[7]['reviewed_source_commit'] ?? '') === '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844',
    8 => ($modules[8]['required_public_contract_version'] ?? '') === '1.0.0',
    9 => ($modules[9]['reviewed_source_version'] ?? '') === '1.3.0' && ($modules[9]['required_contract_version'] ?? '') === '1.1.0' && ($modules[9]['reviewed_source_branch'] ?? '') === 'codex/file09-1.3.0-rc6-80-round-review' && ($modules[9]['reviewed_source_commit'] ?? '') === '6fa0a5cb7063b6b821bd50c105c735470f589b80',
    14 => ($modules[14]['reviewed_source_version'] ?? '') === '1.4.2' && ($modules[14]['required_primary_color'] ?? '') === '#087A4E' && ($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034',
    18 => ($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1',
    20 => ($modules[20]['reviewed_source_version'] ?? '') === '1.4.12' && ($modules[20]['required_contract_version'] ?? '') === '1.0.0' && ($modules[20]['reviewed_source_commit'] ?? '') === '291486b22c7ed94b8be041192375b6d9b077fac5' && ($modules[20]['governing_plan_version'] ?? '') === '4.1',
    21 => ($modules[21]['reviewed_package_version'] ?? '') === '1.0.5' && ($modules[21]['reviewed_runtime_version'] ?? '') === '1.0.3' && ($modules[21]['reviewed_schema_version'] ?? '') === '1.0.0' && ($modules[21]['reviewed_source_commit'] ?? '') === 'afeda8742d8e1ea62254823291a66f502058989c' && ($modules[21]['required_profile_timeline_api'] ?? '') === 'Sabri\\HomeNewsFeed\\ProfileTimeline::query',
    22 => ($modules[22]['reviewed_source_version'] ?? '') === '1.0.0-rc.3' && ($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741' && ($modules[22]['required_contract_versions']['rest_api'] ?? '') === '1.2.0',
    23 => ($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010',
    24 => ($modules[24]['reviewed_source_version'] ?? '') === '0.99.0' && ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b',
    25 => ($modules[25]['candidate_version'] ?? '') === '0.14.0' && ($modules[25]['canonical_primary_color'] ?? '') === '#087A4E' && ($modules[25]['design_token_owner'] ?? '') === 'file-25' && ($modules[25]['structural_shell_owner'] ?? '') === 'file-20',
];
foreach ($checks as $file => $ok) { $check($ok, 'Current reviewed companion mismatch for File ' . $file . '.'); }
foreach ($modules as $module) {
    if (isset($module['staging_status'])) { $check($module['staging_status'] === 'pending', 'Source evidence may not promote staging acceptance.'); }
}

$check(is_array($plan) && ($plan['schema_version'] ?? null) === 3, 'Staging test plan schema 3 required.');
$scenario_ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) { $scenario_ids[] = (string) ($scenario['id'] ?? ''); }
}
foreach (['file00-assertions','file03-current-public-dto','file03-route-parity','file07-directory-visual-contract','file09-doctor-decision','file14-visual-consumer','file20-current-shell-contract','file20-admin-parent','file21-current-profile-timeline-contract','component-api-runtime','file22-create-edit-contract','file23-private-management-contract','file24-current-assurance-contract','companion-token-bridge'] as $scenario) {
    $check(in_array($scenario, $scenario_ids, true), 'Current staging scenario missing: ' . $scenario);
}
$check(str_contains($dependencies, "'visual_token_owner'") === false || str_contains($dependencies, 'File 20 current 1.4.12 structural-shell/central-plan contract'), 'Dependency status must preserve File 20 structural/File 25 visual ownership.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 current central/companion contract reconciliation\n";