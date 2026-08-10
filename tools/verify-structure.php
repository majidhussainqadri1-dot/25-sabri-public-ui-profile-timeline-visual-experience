<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $path) use ($root, &$errors): string { $value = @file_get_contents($root . '/' . $path); if (! is_string($value)) { $errors[] = 'Unreadable required file: ' . $path; return ''; } return $value; };
$contains = static function (string $source, array $markers, string $label) use (&$errors): void { foreach ($markers as $marker) { if (! str_contains($source, $marker)) { $errors[] = $label . ' marker missing: ' . $marker; } } };
$forbids = static function (string $source, array $markers, string $label) use (&$errors): void { foreach ($markers as $marker) { if (str_contains($source, $marker)) { $errors[] = $label . ' forbidden marker present: ' . $marker; } } };

foreach (['.github/workflows/ci.yml','sabri-public-experience.php','composer.json','uninstall.php','includes/class-native-integration.php','includes/class-current-companion-2026-08-10.php','includes/class-design-system.php','includes/class-file-20-integration.php','includes/class-file-24-integration.php','includes/class-profile-repository.php','includes/class-rest-controller.php','assets/css/companion-token-bridge.css','config/staging-dependencies.json','config/staging-test-plan.json','tools/build-staging-package.php','tools/verify-staging-artifact.php'] as $path) { if (! is_file($root . '/' . $path)) { $errors[] = 'Missing required file: ' . $path; } }

$main = $read('sabri-public-experience.php'); $readme = $read('readme.txt');
preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header); preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant); preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable);
$versions = [$header[1] ?? '', $constant[1] ?? '', $stable[1] ?? '']; if (count(array_unique($versions)) !== 1 || ($versions[0] ?? '') !== '0.14.0') { $errors[] = 'Runtime version metadata must all equal 0.14.0.'; } $runtime = (string) ($constant[1] ?? '');

$native = $read('includes/class-native-integration.php');
$contains($native, ["FILE_00_MINIMUM_VERSION = '1.2.38'", "FILE_00_CONTRACT_VERSION = '1.2.2'", 'SMC_Contracts::assertions', "FILE_03_MINIMUM_VERSION = '1.2.0-rc2'", "FILE_03_CONTRACT_VERSION = '1.4.0'", 'spd_get_public_profile', "FILE_09_MINIMUM_VERSION = '1.3.0'", "FILE_09_CONTRACT_VERSION = '1.1.0'", 'gdo_file03_doctor_eligibility', "FILE_08_MINIMUM_VERSION = '1.2.0'", "FILE_08_CANONICAL_CONTRACT = '1.1.0'", "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0'", 'WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION', 'swc_get_public_clinic_projection', "FILE_18_MINIMUM_VERSION = '1.2.0-RC1'", 'smp_get_public_profile_listings'], 'Native authority adapter');
$forbids($native, ['$wpdb','SHOW TABLES','smc_get_profile','smc_professional_credentials','smc_clinics','calculated_age','license_expiry','SPD_Helpers::get','SPD_Helpers::can_show_contact'], 'Native authority adapter');
$current = $read('includes/class-current-companion-2026-08-10.php');
$contains($current, ['spd_get_personal_site_profile','sabri_file25_register_component_provider','professional_lifecycle','profile_translations','content_freshness','metadata_only','native_data_copy_allowed'], 'Current companion reconciliation');
$forbids($current, ['$wpdb','INSERT INTO','UPDATE ','DELETE FROM'], 'Current companion reconciliation');
$contains($main, ['Current_Companion_2026_08_10::register()', "'includes/class-current-companion-2026-08-10.php'"], 'Current companion bootstrap');

$file20 = $read('includes/class-file-20-integration.php'); $contains($file20, ["REVIEWED_MINIMUM_VERSION = '1.4.12'", "REVIEWED_CONTRACT_VERSION = '1.0.0'", "REVIEWED_SOURCE_COMMIT = '291486b22c7ed94b8be041192375b6d9b077fac5'", 'sabri_shell_contract_registry'], 'File 20 integration');
$file24 = $read('includes/class-file-24-integration.php'); $contains($file24, ["REVIEWED_MINIMUM_VERSION = '0.99.0'", "REVIEWED_SOURCE_COMMIT = '0be43b3f424d7b53865587b2770479ca33f51a0b'", 'spcrc/module_manifests'], 'File 24 integration');
$repository = $read('includes/class-profile-repository.php'); $contains($repository, ["FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'", "profile_media_attachment_id(\$user_id, 'avatar')", 'monotonic_media_filter', 'Public_URL::sanitize_same_site'], 'Profile repository');
$forbids($repository, ['get_avatar_url', "'_spd_profile_photo_id'", "'_spd_cover_photo_id'"], 'Profile repository');
$design = $read('includes/class-design-system.php'); $contains($design, ["'global_shell_owner' => 'file-20'", "'security_governance_owner' => 'file-24'", "'public_contact_consent_owner' => 'file-03'", "'primary_color' => '#087A4E'", "'owner' => 'file-25'"], 'Design system');
$bridge = strtolower($read('assets/css/companion-token-bridge.css')); $contains($bridge, ['--sabri-primary: var(--sabri-visual-primary)','--sabri-color-primary: var(--sabri-visual-primary)'], 'Companion token bridge'); $forbids($bridge, ['#ff8a1f','#15803d'], 'Companion token bridge');

$matrix = json_decode($read('config/staging-dependencies.json'), true); if (! is_array($matrix) || ($matrix['schema_version'] ?? null) !== 3 || ($matrix['runtime_version'] ?? '') !== $runtime || ($matrix['environment']['live_changes_allowed'] ?? true) !== false) { $errors[] = 'Staging dependency matrix is invalid.'; }
$modules = []; foreach ((array) ($matrix['modules'] ?? []) as $module) { if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; } }
foreach ([0,3,6,7,8,9,10,11,12,14,18,20,21,22,23,24,25] as $file) { if (! isset($modules[$file])) { $errors[] = 'Staging matrix missing File ' . $file . '.'; } }
$checks = [
0 => ($modules[0]['reviewed_source_commit'] ?? '') === 'c37d0b101d0912bef1f26d0daf51a414d67907c0',
3 => ($modules[3]['reviewed_source_branch'] ?? '') === 'codex/file-03-second-fresh-80-review-20260810' && ($modules[3]['reviewed_source_commit'] ?? '') === 'b862efb94be87980e96a5b864bc5c7aec49183d9' && ($modules[3]['required_contract_version'] ?? '') === '1.4.0',
7 => ($modules[7]['reviewed_source_commit'] ?? '') === '67c32ec4af45a7de6e3d9c1dbf0f8614d6b5a844',
8 => ($modules[8]['reviewed_source_version'] ?? '') === '1.2.0' && ($modules[8]['reviewed_schema_version'] ?? '') === '3.1.0' && ($modules[8]['reviewed_source_commit'] ?? '') === 'cb302617d2a2def23de1883b8d9fead10bce7ef3' && ($modules[8]['required_canonical_contract_version'] ?? '') === '1.1.0' && ($modules[8]['required_public_contract_version'] ?? '') === '1.0.0',
9 => ($modules[9]['reviewed_source_commit'] ?? '') === '9103310fc93d978b6e70661f024a079fc0971003' && ($modules[9]['required_contract_version'] ?? '') === '1.1.0',
14 => ($modules[14]['reviewed_source_commit'] ?? '') === 'b9045a4229d052103a5546477f664ac88b6ff034' && ($modules[14]['required_primary_color'] ?? '') === '#087A4E',
18 => ($modules[18]['reviewed_source_version'] ?? '') === '1.2.0-RC1',
20 => ($modules[20]['reviewed_source_commit'] ?? '') === '291486b22c7ed94b8be041192375b6d9b077fac5' && ($modules[20]['governing_plan_version'] ?? '') === '4.1',
21 => ($modules[21]['reviewed_source_commit'] ?? '') === 'afeda8742d8e1ea62254823291a66f502058989c',
22 => ($modules[22]['reviewed_source_commit'] ?? '') === 'c3b775b66fbbda4a9dd9891d63c08c74e2178741',
23 => ($modules[23]['reviewed_source_commit'] ?? '') === 'a8a8c805f4730998ccb44bd95c87591836561759',
24 => ($modules[24]['reviewed_source_commit'] ?? '') === '0be43b3f424d7b53865587b2770479ca33f51a0b',
25 => ($modules[25]['candidate_version'] ?? '') === $runtime && ($modules[25]['canonical_primary_color'] ?? '') === '#087A4E' && ($modules[25]['design_token_owner'] ?? '') === 'file-25' && ($modules[25]['structural_shell_owner'] ?? '') === 'file-20'];
foreach ($checks as $file => $ok) { if (! $ok) { $errors[] = 'Stale or unsafe reviewed contract for File ' . $file . '.'; } }
foreach ($modules as $module) { if (isset($module['staging_status']) && $module['staging_status'] !== 'pending') { $errors[] = 'Source evidence falsely promoted staging status.'; } }

$plan = json_decode($read('config/staging-test-plan.json'), true); if (! is_array($plan) || ($plan['schema_version'] ?? null) !== 3) { $errors[] = 'Staging test plan schema 3 is invalid.'; }
$ids = []; foreach ((array) ($plan['scenarios'] ?? []) as $scenario) { if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); } }
foreach (['file03-current-public-dto','file03-future-public-experience','file03-component-provider','file03-route-parity','file08-clinic-projection','file09-doctor-decision','file21-current-profile-timeline-contract','file22-create-edit-contract','file23-private-management-contract','file24-current-assurance-contract','responsive-viewports','urdu-rtl','accessibility-input','upgrade-rollback','founder-acceptance'] as $id) { if (! in_array($id, $ids, true)) { $errors[] = 'Staging plan missing scenario: ' . $id; } }
$verifier = $read('tools/verify-staging-artifact.php'); $contains($verifier, ['File25_Staging_Artifact_Verifier','MAX_INNER_TOTAL_BYTES','Unsafe, duplicate or symbolic entry detected in workflow artifact.','Unsafe, duplicate or symbolic entry detected in inner ZIP.','Embedded manifest or dependency matrix differs from detached evidence.','staging_accepted','production_accepted'], 'Artifact verifier');
$builder = $read('tools/build-staging-package.php'); $contains($builder, ['SOURCE_DATE_EPOCH','STAGING-MANIFEST.json','ZipArchive','zip_entry_is_symlink','MAX_PAYLOAD_BYTES'], 'Package builder');

if ($errors !== []) { fwrite(STDERR, "FAILED STRUCTURE\n- " . implode("\n- ", $errors) . "\n"); exit(1); }
echo "PASS: File 25 current structure, companion ownership and release truth\n";
