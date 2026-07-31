<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$required = [
    '.github/workflows/ci.yml',
    'sabri-public-experience.php',
    'readme.txt',
    'README.md',
    'CHANGELOG.md',
    'composer.json',
    'uninstall.php',
    'includes/class-plugin.php',
    'includes/class-safe-mode.php',
    'includes/class-public-url.php',
    'includes/class-components.php',
    'includes/class-content-cards.php',
    'includes/class-visual-acceptance.php',
    'includes/class-staging-probe.php',
    'includes/class-staging-cli.php',
    'includes/class-upgrade-manager.php',
    'includes/class-file-24-integration.php',
    'includes/class-design-system.php',
    'includes/class-profile-router.php',
    'includes/class-profile-renderer.php',
    'includes/class-profile-repository.php',
    'includes/class-visibility-policy.php',
    'includes/class-rest-controller.php',
    'includes/class-system-check.php',
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/class-section-registry.php',
    'includes/class-section-service.php',
    'includes/contracts/interface-timeline-provider.php',
    'includes/contracts/interface-profile-section-provider.php',
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'includes/providers/class-file-21-provider.php',
    'assets/css/design-system.css',
    'assets/css/design-system-components.css',
    'assets/css/public.css',
    'assets/css/profile-sections.css',
    'assets/js/public.js',
    'templates/public-profile.php',
    'config/staging-dependencies.json',
    'config/staging-test-plan.json',
    'tests/profile-router.php',
    'tests/master-plan-reconciliation.php',
    'tests/file24-integration.php',
    'tests/staging-probe.php',
    'tests/upgrade-manager.php',
    'tests/design-system.php',
    'tests/release-engineering.php',
    'tools/build-staging-package.php',
    'tools/verify-staging-artifact.php',
    'docs/NINTH-REVIEW-ROUTE-PARITY-AND-HOSTINGER-PREFLIGHT-2026-07-31.md',
    'docs/HOSTINGER-STAGING-PROBE-AND-RUNBOOK.md',
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
$contains = static function (string $content, array $markers, string $label) use (&$errors): void {
    foreach ($markers as $marker) {
        if (! str_contains($content, $marker)) {
            $errors[] = $label . ' marker missing: ' . $marker;
        }
    }
};

$main = $read('sabri-public-experience.php');
$readme = $read('readme.txt');
preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header);
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant);
preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable);
$versions = [$header[1] ?? '', $constant[1] ?? '', $stable[1] ?? ''];
if (count(array_unique($versions)) !== 1 || $versions[0] !== '0.13.0') {
    $errors[] = 'Plugin header, constant, and stable tag must all equal 0.13.0.';
}
$contains($main, [
    "define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '2')",
    'class-staging-probe.php',
    'class-staging-cli.php',
    'class-upgrade-manager.php',
    'class-file-24-integration.php',
    'Sabri Unified Global Visual Experience and Design System',
    "version_compare(PHP_VERSION, '8.0', '<')",
], 'Runtime');

$design = $read('includes/class-design-system.php');
$contains($design, [
    "CONTRACT_VERSION = '1.8.0'",
    'installed-staging-preflight',
    'file-24-module-manifest',
    'native-contact-consent',
    'same-origin-profile-media',
    'conditional-profile-layout',
    'profile-breadcrumbs',
    'structured-profile-section-rest',
    "'file_24_integration' => File_24_Integration::contract()",
    "'security_governance_owner' => 'file-24'",
    "'global_shell_owner' => 'file-20'",
    "'visual_system_owner' => 'file-25'",
    "'public_contact_consent_owner' => 'file-03'",
    "'creates_file_26' => false",
], 'Design system');

$file24 = $read('includes/class-file-24-integration.php');
$contains($file24, [
    "CONTRACT_VERSION = '1.0.0'",
    "MODULE_KEY = 'file-25-public-experience'",
    "REVIEWED_MINIMUM_VERSION = '0.25.3'",
    "REVIEWED_MAXIMUM_VERSION = '0.26.0'",
    "defined('SPCRC_VERSION')",
    "add_filter('spcrc/module_manifests'",
    "do_action('spcrc/request_security_state'",
    'no-store, private, max-age=0, must-revalidate',
    "'owns_security_governance' => false",
    "'owns_privacy_orchestration' => false",
], 'File 24 integration');
if (str_contains($file24, 'SABRI_SECURITY_CENTER_VERSION') || str_contains($file24, 'SABRI_SPRC_VERSION')) {
    $errors[] = 'File 24 integration must use the exact reviewed SPCRC_VERSION contract.';
}

$visibility = $read('includes/class-visibility-policy.php');
$contains($visibility, [
    'SPD_Helpers::can_show_contact',
    'return $authoritative && $filtered',
    "['phone', 'whatsapp']",
], 'Contact visibility');
if (str_contains($visibility, 'elseif ($this->is_verified_doctor')) {
    $errors[] = 'Verified Doctor status must not bypass File 03 public-contact consent.';
}

$repository = $read('includes/class-profile-repository.php');
$contains($repository, [
    "FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed'",
    'Public_URL::sanitize_same_site',
    'privacy-safe initials',
], 'Profile repository');
if (str_contains($repository, 'get_avatar_url')) {
    $errors[] = 'External avatar-service fallback must remain disabled.';
}

$router = $read('includes/class-profile-router.php');
$contains($router, [
    'Section_Registry::approved_sections()',
    'route_sections',
    'section_pattern',
    'profile_right_sidebar_available',
    "return $right_sidebar_available ? 'three' : 'two'",
    "private const TYPES = ['founder', 'doctor', 'member']",
], 'Profile router');
if (str_contains($router, "SECTION_PATTERN = 'overview|timeline")) {
    $errors[] = 'Profile router must not keep a stale independent provider-section pattern.';
}

$renderer = $read('includes/class-profile-renderer.php');
$contains($renderer, [
    "'@type' => 'BreadcrumbList'",
    'og:image:alt',
    'missing_profile_status',
    'status_header($status)',
], 'Profile renderer');
$template = $read('templates/public-profile.php');
$contains($template, ['spux-breadcrumbs', "aria-current=\"page\""], 'Public profile template');

$cards = $read('includes/class-content-cards.php');
$contains($cards, [
    "CONTRACT_VERSION = '1.2.0'",
    'public static function normalize_public',
    "'public_normalizer' => [self::class, 'normalize_public']",
], 'Content cards');
$sections = $read('includes/class-section-service.php');
$contains($sections, [
    "PUBLIC_CONTRACT_VERSION = '1.0.0'",
    'get_public_section',
    'public_health',
    'Content_Cards::normalize_public',
], 'Section service');
$rest = $read('includes/class-rest-controller.php');
$contains($rest, [
    '/founder/knowledge',
    '/founder/media',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge',
    '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media',
    '/providers/health',
    "'ETag'",
    'no-store, private, max-age=0',
], 'REST controller');
if (preg_match('/provider_id|native_id|projection_key/', $rest)) {
    $errors[] = 'Public REST controller must not expose provider, native, or projection identifiers.';
}

$probe = $read('includes/class-staging-probe.php');
$contains($probe, [
    "CONTRACT_VERSION = '1.0.0'",
    'sabrisocialstaging.sabrihomeopathy.com',
    "LIVE_HOST = 'sabrihomeopathy.com'",
    'verify_package_integrity',
    'verify_test_plan',
    "'writes_runtime_data' => false",
    "'staging_accepted' => false",
    "'production_accepted' => false",
], 'Staging probe');
if (preg_match('/\b(update_option|add_option|delete_option|wp_insert_post|wp_update_post|wp_delete_post)\s*\(/', $probe)) {
    $errors[] = 'Staging probe must remain read-only.';
}

$upgrade = $read('includes/class-upgrade-manager.php');
$contains($upgrade, [
    'sabri_public_experience_upgrade_lock',
    'LOCK_TTL',
    'Profile_Router::flush()',
    'release_lock',
    "Safe_Mode::enable('upgrade-exception'",
], 'Upgrade manager');

$plugin = $read('includes/class-plugin.php');
$contains($plugin, [
    'new File_24_Integration',
    '$file_24->register()',
    'new Staging_Probe',
    'Staging_CLI::register',
    'new Upgrade_Manager',
    'new Rest_Controller($profiles, $timeline, $sections)',
    'new System_Check($dependencies, $timeline_registry, $section_registry, $staging_probe, $file_24)',
], 'Plugin bootstrap');

$dependencies = $read('includes/class-dependency-manager.php');
$contains($dependencies, [
    'File_24_Integration::is_compatible()',
    'File_24_Integration::current_version()',
    'File_24_Integration::REVIEWED_MINIMUM_VERSION',
    'File_24_Integration::REVIEWED_MAXIMUM_VERSION',
], 'Dependency manager');

$system_check = $read('includes/class-system-check.php');
$contains($system_check, [
    'sabri_public_experience_file24',
    'file_24_test',
    'sabri_public_experience_staging_probe',
    'staging_probe_test',
    'This is not staging acceptance',
], 'Site Health');

$acceptance = $read('includes/class-visual-acceptance.php');
$contains($acceptance, [
    "CONTRACT_VERSION = '1.3.0'",
    'target_commit_sha',
    'artifact_ref',
    'byte_size',
    'media_type',
    'founder_signoff',
], 'Visual acceptance');

$css = '';
foreach (['assets/css/design-system.css', 'assets/css/design-system-components.css', 'assets/css/public.css', 'assets/css/profile-sections.css'] as $path) {
    $css .= "\n" . $read($path);
}
if (preg_match('/@import\s+url|fonts\.googleapis|use\.typekit|url\(\s*["\']?https?:/i', $css)) {
    $errors[] = 'Remote CSS or font dependency detected.';
}
foreach (['--sabri-shell-primary', '--sabri-visual-primary', '.sabri-ui-content-card', '.sabri-ui-notice', '.spux-breadcrumbs', '@media (forced-colors: active)', '@media print'] as $marker) {
    if (! str_contains($css, $marker)) {
        $errors[] = 'Visual-system CSS marker missing: ' . $marker;
    }
}
$javascript = $read('assets/js/public.js');
if (preg_match('/\b(eval|document\.write)\s*\(/i', $javascript) || preg_match('/https?:\/\//i', $javascript)) {
    $errors[] = 'Unsafe or remote JavaScript behavior detected.';
}

foreach ([
    'includes/class-content-cards.php',
    'includes/class-section-service.php',
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'includes/providers/class-file-21-provider.php',
] as $path) {
    $source = $read($path);
    if (preg_match('/\b(wp_insert_post|wp_update_post|wp_delete_post|update_option|delete_option)\s*\(/i', $source)) {
        $errors[] = 'Read-only projection contains a write primitive: ' . $path;
    }
}

$matrix = json_decode($read('config/staging-dependencies.json'), true);
if (! is_array($matrix)
    || ($matrix['runtime_version'] ?? '') !== '0.13.0'
    || ($matrix['environment']['target_site'] ?? '') !== 'https://sabrisocialstaging.sabrihomeopathy.com/'
    || ($matrix['environment']['live_changes_allowed'] ?? true) !== false
    || ($matrix['environment']['registration_disabled_required'] ?? false) !== true
    || ($matrix['environment']['search_indexing_disabled_required'] ?? false) !== true
    || ($matrix['staging_test_plan'] ?? '') !== 'config/staging-test-plan.json'
) {
    $errors[] = 'Staging dependency matrix is invalid or not bound to the exact private Hostinger target.';
}
$modules = [];
foreach ((array) ($matrix['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) {
        $modules[(int) $module['file']] = $module;
    }
}
foreach ([0, 3, 6, 10, 11, 12, 18, 20, 21, 24, 25] as $file) {
    if (! isset($modules[$file])) {
        $errors[] = 'Staging matrix missing File ' . $file . '.';
    }
}
if (($modules[0]['reviewed_package_version'] ?? '') !== '1.1.13'
    || ($modules[0]['accepted_source_range'] ?? '') !== '>=1.1.13 <1.2.0'
) {
    $errors[] = 'File 00 staging authority must match reviewed 1.1.13 source expectations.';
}
if (($modules[3]['reviewed_package_version'] ?? '') !== '0.2.0'
    || ($modules[3]['accepted_source_range'] ?? '') !== '>=0.2.0 <0.3.0'
    || ! in_array('SPD_Helpers::can_show_contact', (array) ($modules[3]['required_symbols'] ?? []), true)
) {
    $errors[] = 'File 03 staging authority must require reviewed 0.2.0 contact-consent contract.';
}
if (($modules[24]['staging_status'] ?? '') !== 'pending'
    || ($modules[24]['reviewed_package_version'] ?? '') !== '0.25.3'
    || ($modules[24]['accepted_source_range'] ?? '') !== '>=0.25.3 <0.26.0'
    || ($modules[24]['accepted_runtime_contract'] ?? '') !== 'reviewed-source-contract-pending-staging'
    || ($modules[24]['cache_partition_contract'] ?? '') !== 'not-yet-versioned'
) {
    $errors[] = 'File 24 reviewed contract or pending staging state is not truthful.';
}
if (($modules[25]['staging_status'] ?? '') !== 'pending'
    || ($modules[25]['candidate_version'] ?? '') !== '0.13.0'
    || ($modules[25]['schema_version'] ?? '') !== '2'
) {
    $errors[] = 'File 25 staging state, version, or schema is not truthful.';
}

$plan = json_decode($read('config/staging-test-plan.json'), true);
if (! is_array($plan)
    || ($plan['owner'] ?? '') !== 'file-25'
    || ($plan['canonical_staging_host'] ?? '') !== 'sabrisocialstaging.sabrihomeopathy.com'
    || ($plan['live_host_must_remain_untouched'] ?? false) !== true
    || ($plan['staging_acceptance_implied'] ?? true) !== false
    || count((array) ($plan['scenarios'] ?? [])) < 10
) {
    $errors[] = 'Governed Hostinger staging test plan is invalid.';
}

$composer_raw = $read('composer.json');
$composer = json_decode($composer_raw, true);
if (! is_array($composer) || ($composer['require']['php'] ?? '') !== '>=8.0') {
    $errors[] = 'Composer PHP contract is invalid.';
}
foreach (['tests/profile-router.php', 'tests/master-plan-reconciliation.php', 'tests/file24-integration.php', 'tests/staging-probe.php', 'tests/upgrade-manager.php'] as $test) {
    if (! str_contains($composer_raw, $test)) {
        $errors[] = 'Composer suite missing: ' . $test;
    }
}

$workflow = $read('.github/workflows/ci.yml');
$contains($workflow, [
    'Provider route parity and fail-closed profile routing',
    'Master-plan and File 25 final-specification reconciliation',
    'Reviewed File 24 integration contract',
    'Installed-package integrity and Hostinger staging-probe contract',
    'Idempotent schema-2 profile-route upgrade contract',
    'Verify extracted installed candidate against embedded manifest',
    'Staging_Probe::verify_package_integrity',
    'Verify assembled candidate with the independent verifier',
], 'CI workflow');
if (str_contains($workflow, 'sabri-public-experience-0.13.0.zip')) {
    $errors[] = 'CI must not hard-code one staging package version.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", array_values(array_unique($errors))) . "\n");
    exit(1);
}

echo "PASS: File 25 master-plan, privacy, REST, File 24, staging, and package structure\n";
