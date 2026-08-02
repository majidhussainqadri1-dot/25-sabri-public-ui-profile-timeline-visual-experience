<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$required = [
    '.github/workflows/ci.yml', 'sabri-public-experience.php', 'readme.txt',
    'README.md', 'CHANGELOG.md', 'composer.json', 'uninstall.php',
    'includes/class-plugin.php', 'includes/class-safe-mode.php',
    'includes/class-public-url.php', 'includes/class-components.php',
    'includes/class-content-cards.php', 'includes/class-visual-acceptance.php',
    'includes/class-staging-probe.php', 'includes/class-staging-cli.php',
    'includes/class-upgrade-manager.php', 'includes/class-file-24-integration.php',
    'includes/class-native-integration.php', 'includes/class-dependency-manager.php',
    'includes/class-design-system.php', 'includes/class-profile-router.php',
    'includes/class-profile-renderer.php', 'includes/class-profile-repository.php',
    'includes/class-visibility-policy.php', 'includes/class-rest-controller.php',
    'includes/class-section-registry.php', 'includes/class-section-service.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'templates/public-profile.php', 'assets/css/design-system.css',
    'assets/css/design-system-components.css', 'assets/css/public.css',
    'assets/css/profile-sections.css', 'assets/js/public.js',
    'config/staging-dependencies.json', 'config/staging-test-plan.json',
    'tests/profile-router.php', 'tests/authoritative-native-contracts.php',
    'tests/master-plan-reconciliation.php', 'tests/file18-marketplace-provider.php',
    'tests/file24-integration.php', 'tests/staging-probe.php',
    'tests/upgrade-manager.php', 'tests/design-system.php',
    'tests/release-engineering.php', 'tools/build-staging-package.php',
    'tools/verify-staging-artifact.php',
    'docs/TENTH-REVIEW-FILE24-INTEGRATION-2026-07-31.md',
    'docs/ELEVENTH-REVIEW-MASTER-PLAN-AND-FILE25-2026-07-31.md',
];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing required file: ' . $path;
    }
}

$read = static function (string $path) use ($root, &$errors): string {
    $content = @file_get_contents($root . '/' . $path);
    if (! is_string($content)) {
        $errors[] = 'Unreadable required file: ' . $path;
        return '';
    }
    return $content;
};
$contains = static function (string $source, array $markers, string $label) use (&$errors): void {
    foreach ($markers as $marker) {
        if (! str_contains($source, $marker)) {
            $errors[] = $label . ' marker missing: ' . $marker;
        }
    }
};
$forbids = static function (string $source, array $markers, string $label) use (&$errors): void {
    foreach ($markers as $marker) {
        if (str_contains($source, $marker)) {
            $errors[] = $label . ' forbidden marker present: ' . $marker;
        }
    }
};

$main = $read('sabri-public-experience.php');
$readme = $read('readme.txt');
preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header);
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant);
preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable);
$versions = [$header[1] ?? '', $constant[1] ?? '', $stable[1] ?? ''];
if (count(array_unique($versions)) !== 1 || $versions[0] !== '0.14.0') {
    $errors[] = 'Plugin header, constant, and stable tag must all equal 0.14.0.';
}
$runtimeVersion = (string) ($constant[1] ?? '');
$contains($main, [
    "define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '2')",
    'class-native-integration.php', 'class-dependency-manager.php',
    'class-file-24-integration.php', 'class-section-service.php',
    'class-rest-controller.php', "version_compare(PHP_VERSION, '8.0', '<')",
], 'Runtime');

$design = $read('includes/class-design-system.php');
$contains($design, [
    "CONTRACT_VERSION = '1.8.0'", "'global_shell_owner' => 'file-20'",
    "'security_governance_owner' => 'file-24'",
    "'public_contact_consent_owner' => 'file-03'", 'native-contact-consent',
    'same-origin-profile-media', 'conditional-profile-layout',
    'profile-breadcrumbs', 'structured-profile-section-rest',
    "'creates_file_26' => false",
], 'Design system');

$file24 = $read('includes/class-file-24-integration.php');
$contains($file24, [
    "defined('SPCRC_VERSION')", "REVIEWED_MINIMUM_VERSION = '0.25.3'",
    "REVIEWED_MAXIMUM_VERSION = '0.26.0'", "add_filter('spcrc/module_manifests'",
    "'owns_security_governance' => false", "'owns_privacy_orchestration' => false",
], 'File 24 integration');
$forbids($file24, ['SABRI_SECURITY_CENTER_VERSION', 'SABRI_SPRC_VERSION'], 'File 24 integration');

$native = $read('includes/class-native-integration.php');
$contains($native, [
    "FILE_00_MINIMUM_VERSION = '1.2.4'", "FILE_00_MAXIMUM_VERSION = '1.3.0'",
    "FILE_00_CONTRACT_VERSION = '1.1.2'", 'SMC_Contracts::assertions',
    'SMC_CONTRACT_VERSION', 'smc_founder_user_id', 'gdo_get_verification_decision',
    'gdo_get_approved_snapshot', "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0'",
    'swc_get_public_clinic_projection', "FILE_18_MINIMUM_VERSION = '1.2.0-RC1'",
    'File 25 must not calculate age',
], 'Native authority adapter');
$forbids($native, [
    '$wpdb', 'SHOW TABLES', 'smc_get_profile', 'smc_professional_credentials',
    'smc_clinics', 'calculated_age', 'license_expiry',
], 'Native authority adapter');

$marketplace = $read('includes/providers/class-file-18-marketplace-provider.php');
$contains($marketplace, [
    "MINIMUM_VERSION = '1.2.0-RC1'", 'smp_get_public_profile_listings',
    'SMP_Utils::current_seller', 'SMP_REST::products',
    'SMP_Activator::marketplace_url', 'TRANSITIONAL_FETCH_LIMIT',
], 'File 18 adapter');
$forbids($marketplace, ['$wpdb', 'SMP_DB::table', 'SELECT p.*'], 'File 18 adapter');

$dependencies = $read('includes/class-dependency-manager.php');
$contains($dependencies, [
    'FILE_00_CONTRACT_VERSION', 'doctor_verification_available', 'clinic_available',
    'marketplace_available', 'File 25 retains visual-token ownership',
], 'Dependency manager');

$visibility = $read('includes/class-visibility-policy.php');
$contains($visibility, ['SPD_Helpers::can_show_contact', 'return $authoritative && $filtered'], 'Contact visibility');
$forbids($visibility, ['elseif ($this->is_verified_doctor'], 'Contact visibility');

$repository = $read('includes/class-profile-repository.php');
$contains($repository, [
    "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'",
    'Public_URL::sanitize_same_site', 'privacy-safe initials',
], 'Profile repository');
$forbids($repository, ['get_avatar_url'], 'Profile repository');

$router = $read('includes/class-profile-router.php');
$contains($router, [
    'Section_Registry::approved_sections()', 'profile_right_sidebar_available',
    'return $right_sidebar_available ? \'three\' : \'two\';',
], 'Profile router');
$renderer = $read('includes/class-profile-renderer.php');
$contains($renderer, ["'@type' => 'BreadcrumbList'", 'og:image:alt', 'missing_profile_status', 'status_header($status)'], 'Profile renderer');
$contains($read('templates/public-profile.php'), ['spux-breadcrumbs', 'aria-current="page"'], 'Profile template');

$cards = $read('includes/class-content-cards.php');
$contains($cards, ["CONTRACT_VERSION = '1.2.0'", 'public static function normalize_public', "'public_normalizer' => [self::class, 'normalize_public']"], 'Content cards');
$sections = $read('includes/class-section-service.php');
$contains($sections, ["PUBLIC_CONTRACT_VERSION = '1.0.0'", 'get_public_section', 'public_health', 'Content_Cards::normalize_public'], 'Section service');
$rest = $read('includes/class-rest-controller.php');
$contains($rest, [
    '/founder/knowledge', '/founder/media',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media', '/providers/health',
    "'ETag'", 'no-store, private, max-age=0',
], 'REST controller');
if (preg_match('/provider_id|native_id|projection_key/', $rest)) {
    $errors[] = 'Public REST controller must not expose internal identifiers.';
}

$plugin = $read('includes/class-plugin.php');
$contains($plugin, ['new File_24_Integration', 'new Staging_Probe', 'new Upgrade_Manager', 'new Rest_Controller($profiles, $timeline, $sections)'], 'Plugin bootstrap');

$css = '';
foreach (['assets/css/design-system.css', 'assets/css/design-system-components.css', 'assets/css/public.css', 'assets/css/profile-sections.css'] as $path) {
    $css .= "\n" . $read($path);
}
if (preg_match('/@import\s+url|fonts\.googleapis|use\.typekit|url\(\s*["\']?https?:/i', $css)) {
    $errors[] = 'Remote CSS or font dependency detected.';
}
foreach (['--sabri-visual-primary', '.sabri-ui-content-card', '.spux-breadcrumbs', '@media (forced-colors: active)', '@media print'] as $marker) {
    if (! str_contains($css, $marker)) {
        $errors[] = 'Visual-system CSS marker missing: ' . $marker;
    }
}
$javascript = $read('assets/js/public.js');
if (preg_match('/\b(eval|document\.write)\s*\(/i', $javascript) || preg_match('/https?:\/\//i', $javascript)) {
    $errors[] = 'Unsafe or remote JavaScript behavior detected.';
}

foreach ([
    'includes/class-content-cards.php', 'includes/class-section-service.php',
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'includes/providers/class-file-21-provider.php',
] as $path) {
    if (preg_match('/\b(wp_insert_post|wp_update_post|wp_delete_post|update_option|delete_option)\s*\(/i', $read($path))) {
        $errors[] = 'Read-only projection contains a write primitive: ' . $path;
    }
}

$matrix = json_decode($read('config/staging-dependencies.json'), true);
if (! is_array($matrix)
    || ($matrix['schema_version'] ?? null) !== 2
    || ($matrix['runtime_version'] ?? '') !== $runtimeVersion
    || ($matrix['governing_sources']['platform_master_plan'] ?? '') !== 'Sabri Social Homeopathy Platform Definitive Master Plan 2026 v3.0'
    || ($matrix['governing_sources']['file_20_plan'] ?? '') !== 'File 20 Harmonized Master Plan 2026 v4.1'
    || ($matrix['environment']['target_site'] ?? '') !== 'https://sabrisocialstaging.sabrihomeopathy.com/'
    || ($matrix['environment']['live_changes_allowed'] ?? true) !== false
) {
    $errors[] = 'Staging dependency matrix is invalid.';
}
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) {
        $modules[(int) $module['file']] = $module;
    }
}
foreach ([0, 3, 6, 8, 9, 10, 11, 12, 18, 20, 21, 24, 25] as $file) {
    if (! isset($modules[$file])) {
        $errors[] = 'Staging matrix missing File ' . $file . '.';
    }
}
if (($modules[0]['reviewed_package_version'] ?? '') !== '1.2.4'
    || ($modules[0]['accepted_source_range'] ?? '') !== '>=1.2.4 <1.3.0'
    || ($modules[0]['required_contract_version'] ?? '') !== '1.1.2'
    || ($modules[0]['foreign_table_reads_allowed'] ?? true) !== false
) {
    $errors[] = 'File 00 staging authority is stale or unsafe.';
}
if (($modules[3]['reviewed_package_version'] ?? '') !== '0.2.0'
    || ($modules[3]['accepted_source_range'] ?? '') !== '>=0.2.0 <0.3.0'
    || ! in_array('SPD_Helpers::can_show_contact', (array) ($modules[3]['required_symbols'] ?? []), true)
) {
    $errors[] = 'File 03 contact-consent staging authority is stale.';
}
if (($modules[8]['reviewed_package_version'] ?? '') !== '0.2.1'
    || ($modules[8]['reviewed_source_version'] ?? '') !== '0.2.1'
    || ($modules[8]['accepted_source_range'] ?? '') !== '>=0.2.1 <0.3.0'
    || ($modules[8]['required_public_contract_version'] ?? '') !== '1.0.0'
    || ($modules[8]['reviewed_source_commit'] ?? '') !== 'bd6a10b693991fc518788ef8e3cba49531454821'
    || ($modules[8]['reviewed_candidate_sha256'] ?? '') !== '36ce0c78aa51396b02bd0705021e66782bfc45b74636ac65b0826bd372103578'
    || ($modules[8]['contract_status'] ?? '') !== 'implemented-and-ci-verified-pending-hostinger-staging'
    || ($modules[8]['foreign_table_reads_allowed'] ?? true) !== false
    || ($modules[8]['staging_status'] ?? '') !== 'pending'
) {
    $errors[] = 'File 08 public clinic projection authority is stale, incomplete, or unsafe.';
}
foreach (['SWC_VERSION', 'SWC_PUBLIC_CLINIC_CONTRACT_VERSION', 'swc_get_public_clinic_projection', 'swc_public_clinic_projection_contract'] as $symbol) {
    if (! in_array($symbol, (array) ($modules[8]['required_symbols'] ?? []), true)) {
        $errors[] = 'File 08 matrix symbol missing: ' . $symbol;
    }
}
if (($modules[9]['reviewed_source_version'] ?? '') !== '1.1.0'
    || ($modules[9]['foreign_table_reads_allowed'] ?? true) !== false
) {
    $errors[] = 'File 09 Doctor verification authority is stale or unsafe.';
}
if (($modules[18]['reviewed_source_version'] ?? '') !== '1.2.0-RC1'
    || ($modules[18]['foreign_table_reads_allowed'] ?? true) !== false
) {
    $errors[] = 'File 18 Marketplace authority is stale or unsafe.';
}
if (($modules[20]['reviewed_source_version'] ?? '') !== '1.2.0'
    || ($modules[20]['governing_plan_version'] ?? '') !== '4.1'
) {
    $errors[] = 'File 20 shell authority is stale.';
}
if (($modules[24]['staging_status'] ?? '') !== 'pending'
    || ($modules[24]['accepted_runtime_contract'] ?? '') !== 'reviewed-source-contract-pending-staging'
) {
    $errors[] = 'File 24 pending staging state is inaccurate.';
}
if (($modules[25]['candidate_version'] ?? '') !== $runtimeVersion
    || ($modules[25]['schema_version'] ?? '') !== '2'
    || ($modules[25]['staging_status'] ?? '') !== 'pending'
) {
    $errors[] = 'File 25 candidate state is inaccurate.';
}

$plan = json_decode($read('config/staging-test-plan.json'), true);
$scenarioIds = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) {
        $scenarioIds[] = (string) ($scenario['id'] ?? '');
    }
}
if (! is_array($plan) || ($plan['schema_version'] ?? null) !== 2) {
    $errors[] = 'Staging test plan schema 2 is invalid.';
}
foreach (['file00-assertions', 'file08-clinic-projection', 'file09-doctor-decision', 'file18-owner-dto'] as $scenarioId) {
    if (! in_array($scenarioId, $scenarioIds, true)) {
        $errors[] = 'Staging test plan missing authoritative scenario: ' . $scenarioId;
    }
}

$composer = $read('composer.json');
foreach ([
    'tests/profile-router.php', 'tests/authoritative-native-contracts.php',
    'tests/master-plan-reconciliation.php', 'tests/file18-marketplace-provider.php',
    'tests/file24-integration.php', 'tests/staging-probe.php',
] as $test) {
    if (! str_contains($composer, $test)) {
        $errors[] = 'Composer suite missing: ' . $test;
    }
}
$workflow = $read('.github/workflows/ci.yml');
foreach ([
    'Authoritative File 00 08 09 native contracts',
    'Master Plan v3 authoritative-contract reconciliation',
    'File 18 owner-executed public DTO contract',
    'Reviewed File 24 integration contract',
    'Verify assembled candidate with the independent verifier',
] as $marker) {
    if (! str_contains($workflow, $marker)) {
        $errors[] = 'CI workflow marker missing: ' . $marker;
    }
}
if ($runtimeVersion !== '' && str_contains($workflow, 'sabri-public-experience-' . $runtimeVersion . '.zip')) {
    $errors[] = 'CI must not hard-code the current package version.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", array_values(array_unique($errors))) . "\n");
    exit(1);
}

echo "PASS: File 25 authoritative ownership, privacy, REST, staging, and package structure\n";
