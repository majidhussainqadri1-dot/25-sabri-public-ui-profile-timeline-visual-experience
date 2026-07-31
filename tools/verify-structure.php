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
    'includes/class-section-registry.php',
    'includes/class-section-service.php',
    'templates/public-profile.php',
    'assets/css/design-system.css',
    'assets/css/design-system-components.css',
    'assets/css/public.css',
    'assets/css/profile-sections.css',
    'assets/js/public.js',
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
    'class-file-24-integration.php',
    'class-section-service.php',
    'class-rest-controller.php',
    "version_compare(PHP_VERSION, '8.0', '<')",
], 'Runtime');

$design = $read('includes/class-design-system.php');
$contains($design, [
    "CONTRACT_VERSION = '1.8.0'",
    "'global_shell_owner' => 'file-20'",
    "'security_governance_owner' => 'file-24'",
    "'public_contact_consent_owner' => 'file-03'",
    'native-contact-consent',
    'same-origin-profile-media',
    'conditional-profile-layout',
    'profile-breadcrumbs',
    'structured-profile-section-rest',
    "'creates_file_26' => false",
], 'Design system');

$file24 = $read('includes/class-file-24-integration.php');
$contains($file24, [
    "defined('SPCRC_VERSION')",
    "REVIEWED_MINIMUM_VERSION = '0.25.3'",
    "REVIEWED_MAXIMUM_VERSION = '0.26.0'",
    "add_filter('spcrc/module_manifests'",
    "'owns_security_governance' => false",
    "'owns_privacy_orchestration' => false",
], 'File 24 integration');
if (str_contains($file24, 'SABRI_SECURITY_CENTER_VERSION') || str_contains($file24, 'SABRI_SPRC_VERSION')) {
    $errors[] = 'File 24 integration must use only the reviewed SPCRC_VERSION contract.';
}

$visibility = $read('includes/class-visibility-policy.php');
$contains($visibility, [
    'SPD_Helpers::can_show_contact',
    'return $authoritative && $filtered',
], 'Contact visibility');
if (str_contains($visibility, 'elseif ($this->is_verified_doctor')) {
    $errors[] = 'Verified Doctor status must not bypass File 03 contact consent.';
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
    'profile_right_sidebar_available',
    'return $right_sidebar_available ? \'three\' : \'two\';',
], 'Profile router');

$renderer = $read('includes/class-profile-renderer.php');
$contains($renderer, [
    "'@type' => 'BreadcrumbList'",
    'og:image:alt',
    'missing_profile_status',
    'status_header($status)',
], 'Profile renderer');
$contains($read('templates/public-profile.php'), [
    'spux-breadcrumbs',
    'aria-current="page"',
], 'Profile template');

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
    $errors[] = 'Public REST controller must not expose internal identifiers.';
}

$plugin = $read('includes/class-plugin.php');
$contains($plugin, [
    'new File_24_Integration',
    'new Staging_Probe',
    'new Upgrade_Manager',
    'new Rest_Controller($profiles, $timeline, $sections)',
], 'Plugin bootstrap');

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
    'includes/class-content-cards.php',
    'includes/class-section-service.php',
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
    || ($matrix['runtime_version'] ?? '') !== '0.13.0'
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
foreach ([0, 3, 6, 10, 11, 12, 18, 20, 21, 24, 25] as $file) {
    if (! isset($modules[$file])) {
        $errors[] = 'Staging matrix missing File ' . $file . '.';
    }
}
if (($modules[0]['reviewed_package_version'] ?? '') !== '1.1.13'
    || ($modules[0]['accepted_source_range'] ?? '') !== '>=1.1.13 <1.2.0'
) {
    $errors[] = 'File 00 staging authority is stale.';
}
if (($modules[3]['reviewed_package_version'] ?? '') !== '0.2.0'
    || ($modules[3]['accepted_source_range'] ?? '') !== '>=0.2.0 <0.3.0'
    || ! in_array('SPD_Helpers::can_show_contact', (array) ($modules[3]['required_symbols'] ?? []), true)
) {
    $errors[] = 'File 03 contact-consent staging authority is stale.';
}
if (($modules[24]['staging_status'] ?? '') !== 'pending'
    || ($modules[24]['accepted_runtime_contract'] ?? '') !== 'reviewed-source-contract-pending-staging'
) {
    $errors[] = 'File 24 pending staging state is inaccurate.';
}
if (($modules[25]['candidate_version'] ?? '') !== '0.13.0'
    || ($modules[25]['schema_version'] ?? '') !== '2'
    || ($modules[25]['staging_status'] ?? '') !== 'pending'
) {
    $errors[] = 'File 25 candidate state is inaccurate.';
}

$composer = $read('composer.json');
foreach (['tests/profile-router.php', 'tests/master-plan-reconciliation.php', 'tests/file24-integration.php', 'tests/staging-probe.php'] as $test) {
    if (! str_contains($composer, $test)) {
        $errors[] = 'Composer suite missing: ' . $test;
    }
}
$workflow = $read('.github/workflows/ci.yml');
foreach ([
    'Master-plan and File 25 final-specification reconciliation',
    'Reviewed File 24 integration contract',
    'Verify assembled candidate with the independent verifier',
] as $marker) {
    if (! str_contains($workflow, $marker)) {
        $errors[] = 'CI workflow marker missing: ' . $marker;
    }
}
if (str_contains($workflow, 'sabri-public-experience-0.13.0.zip')) {
    $errors[] = 'CI must not hard-code the current package version.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", array_values(array_unique($errors))) . "\n");
    exit(1);
}

echo "PASS: File 25 master-plan, privacy, REST, staging, and package structure\n";
