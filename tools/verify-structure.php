<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $path) use ($root, &$errors): string {
    $value = @file_get_contents($root . '/' . $path);
    if (! is_string($value)) { $errors[] = 'Unreadable required file: ' . $path; return ''; }
    return $value;
};
$contains = static function (string $source, array $markers, string $label) use (&$errors): void {
    foreach ($markers as $marker) { if (! str_contains($source, $marker)) { $errors[] = $label . ' marker missing: ' . $marker; } }
};
$forbids = static function (string $source, array $markers, string $label) use (&$errors): void {
    foreach ($markers as $marker) { if (str_contains($source, $marker)) { $errors[] = $label . ' forbidden marker present: ' . $marker; } }
};

foreach ([
    '.github/workflows/ci.yml','sabri-public-experience.php','composer.json','uninstall.php',
    'includes/class-native-integration.php','includes/class-dependency-manager.php','includes/class-design-system.php',
    'includes/class-profile-router.php','includes/class-profile-renderer.php','includes/class-profile-repository.php',
    'includes/class-visibility-policy.php','includes/class-rest-controller.php','includes/class-file-24-integration.php',
    'assets/css/design-system.css','assets/css/companion-token-bridge.css','assets/js/public.js',
    'config/staging-dependencies.json','config/staging-test-plan.json','tools/build-staging-package.php','tools/verify-staging-artifact.php',
] as $path) {
    if (! is_file($root . '/' . $path)) { $errors[] = 'Missing required file: ' . $path; }
}

$main = $read('sabri-public-experience.php');
$readme = $read('readme.txt');
preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header);
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant);
preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable);
$versions = [$header[1] ?? '', $constant[1] ?? '', $stable[1] ?? ''];
if (count(array_unique($versions)) !== 1 || ($versions[0] ?? '') !== '0.14.0') { $errors[] = 'Runtime version metadata must all equal 0.14.0.'; }
$runtime = (string) ($constant[1] ?? '');

$native = $read('includes/class-native-integration.php');
$contains($native, [
    "FILE_00_MINIMUM_VERSION = '1.2.4'", "FILE_00_CONTRACT_VERSION = '1.1.2'", 'SMC_Contracts::assertions', 'smc_founder_user_id',
    "FILE_03_MINIMUM_VERSION = '1.2.0-rc2'", "FILE_03_CONTRACT_VERSION = '1.4.0'", 'spd_get_public_profile', 'spd_get_profile_contract_manifest',
    "FILE_09_MINIMUM_VERSION = '1.3.0'", "FILE_09_CONTRACT_VERSION = '1.1.0'", 'GDO_Integration_Contracts::VERSION', 'gdo_file03_doctor_eligibility',
    "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0'", 'swc_get_public_clinic_projection',
    "FILE_18_MINIMUM_VERSION = '1.2.0-RC1'", 'smp_get_public_profile_listings',
], 'Native authority adapter');
$forbids($native, ['$wpdb','SHOW TABLES','smc_get_profile','smc_professional_credentials','smc_clinics','calculated_age','license_expiry','SPD_Helpers::get','SPD_Helpers::can_show_contact'], 'Native authority adapter');

$visibility = $read('includes/class-visibility-policy.php');
$contains($visibility, ['profile_contact($user_id, $field)','return $authoritative && $filtered === true;'], 'Contact visibility');
$repository = $read('includes/class-profile-repository.php');
$contains($repository, [
    "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'",
    "profile_media_attachment_id(\$user_id, 'avatar')", "profile_media_attachment_id(\$user_id, 'cover')",
    "owned_media_url(\$photo_id, \$user_id, 'avatar', 'medium')", 'monotonic_media_filter', 'Public_URL::sanitize_same_site',
], 'Profile repository');
$forbids($repository, ['get_avatar_url', "'_spd_profile_photo_id'", "'_spd_cover_photo_id'"], 'Profile repository');

$router = $read('includes/class-profile-router.php');
$contains($router, ['$non_file03_public_id','profile_right_sidebar_available',"return 'two';","return 'three';"], 'Profile router');
$design = $read('includes/class-design-system.php');
$contains($design, ["CONTRACT_VERSION = '1.8.0'","'global_shell_owner' => 'file-20'","'security_governance_owner' => 'file-24'","'public_contact_consent_owner' => 'file-03'",'native-contact-consent','same-origin-profile-media','conditional-profile-layout'], 'Design system');
$bridge = $read('assets/css/companion-token-bridge.css');
$contains($bridge, ['--sabri-primary: var(--sabri-visual-primary)','--sabri-color-primary: var(--sabri-visual-primary)','--sabri-text: var(--sabri-visual-text)','--sabri-radius: var(--sabri-visual-radius-card)'], 'Companion token bridge');
$forbids(strtolower($bridge), ['#ff8a1f','#15803d'], 'Companion token bridge');

$rest = $read('includes/class-rest-controller.php');
$contains($rest, ['/founder/knowledge','/founder/media','/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge','/providers/health',"'ETag'",'no-store, private, max-age=0'], 'REST controller');
if (preg_match('/provider_id|native_id|projection_key/', $rest)) { $errors[] = 'Public REST controller must not expose internal identifiers.'; }

$matrix = json_decode($read('config/staging-dependencies.json'), true);
if (! is_array($matrix) || ($matrix['schema_version'] ?? null) !== 3 || ($matrix['runtime_version'] ?? '') !== $runtime || ($matrix['environment']['live_changes_allowed'] ?? true) !== false) {
    $errors[] = 'Staging dependency matrix is invalid.';
}
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
foreach ([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25] as $file) { if (! isset($modules[$file])) { $errors[] = 'Staging matrix missing File ' . $file . '.'; } }
$module_checks = [
    0 => ($modules[0]['reviewed_package_version'] ?? '') === '1.2.4' && ($modules[0]['required_contract_version'] ?? '') === '1.1.2',
    3 => ($modules[3]['reviewed_source_version'] ?? '') === '1.2.0-rc2' && ($modules[3]['required_contract_version'] ?? '') === '1.4.0' && ($modules[3]['reviewed_source_commit'] ?? '') === 'b96f74457f54341701c6cdb1a57d42baa1100081',
    7 => ($modules[7]['reviewed_source_version'] ?? '') === '1.2.0' && ($modules[7]['required_contract_version'] ?? '') === '1.2.0',
    8 => ($modules[8]['required_public_contract_version'] ?? '') === '1.0.0',
    9 => ($modules[9]['reviewed_source_version'] ?? '') === '1.3.0' && ($modules[9]['required_contract_version'] ?? '') === '1.1.0',
    14 => ($modules[14]['reviewed_source_version'] ?? '') === '1.4.1' && ($modules[14]['required_primary_color'] ?? '') === '#087A4E',
    18 => ($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1',
    20 => ($modules[20]['governing_plan_version'] ?? '') === '4.1',
    22 => ($modules[22]['reviewed_source_version'] ?? '') === '1.0.0-rc.3' && ($modules[22]['required_contract_versions']['rest_api'] ?? '') === '1.2.0',
    23 => ($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759' && ($modules[23]['future_intelligence_branch']['head'] ?? '') === '50b9489a4a058d4628ef5dda220837393dd32010',
    24 => ($modules[24]['reviewed_package_version'] ?? '') === '0.25.3',
    25 => ($modules[25]['candidate_version'] ?? '') === $runtime && ($modules[25]['canonical_primary_color'] ?? '') === '#087A4E' && ($modules[25]['design_token_owner'] ?? '') === 'file-25' && ($modules[25]['structural_shell_owner'] ?? '') === 'file-20',
];
foreach ($module_checks as $file => $ok) { if (! $ok) { $errors[] = 'Stale or unsafe reviewed contract for File ' . $file . '.'; } }
foreach ($modules as $module) { if (isset($module['staging_status']) && $module['staging_status'] !== 'pending') { $errors[] = 'Source evidence falsely promoted a staging status.'; } }

$plan = json_decode($read('config/staging-test-plan.json'), true);
if (! is_array($plan) || ($plan['schema_version'] ?? null) !== 3) { $errors[] = 'Staging test plan schema 3 is invalid.'; }
$ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file03-current-public-dto','file03-route-parity','file07-directory-visual-contract','file09-doctor-decision','file14-visual-consumer','file22-create-edit-contract','file23-private-management-contract','companion-token-bridge'] as $id) { if (! in_array($id, $ids, true)) { $errors[] = 'Staging test scenario missing: ' . $id; } }

$verifier = $read('tools/verify-staging-artifact.php');
$contains($verifier, ['File25_Staging_Artifact_Verifier','Embedded manifest or dependency matrix differs from detached evidence.','Unsafe, duplicate or symbolic entry detected in workflow artifact.','Unsafe, duplicate or symbolic entry detected in inner ZIP.','MAX_INNER_TOTAL_BYTES'], 'Artifact verifier');
$builder = $read('tools/build-staging-package.php');
$contains($builder, ['SOURCE_DATE_EPOCH','STAGING-MANIFEST.json','ZipArchive','verify_archive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'], 'Package builder');

if ($errors !== []) { fwrite(STDERR, "FAILED\n- " . implode("\n- ", $errors) . "\n"); exit(1); }
echo "File 25 structure and current companion-contract verification passed.\n";
