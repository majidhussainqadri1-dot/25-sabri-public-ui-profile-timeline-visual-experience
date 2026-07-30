<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$required = [
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
    'tests/file06-knowledge-provider.php',
    'tests/file10-12-media-providers.php',
    'assets/css/design-system-components.css',
    'docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md',
    'docs/FOURTH-REVIEW-AND-CORRECTION-2026-07-31.md',
];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing native-adapter/component artifact: ' . $path;
    }
}

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
foreach ([
    'class-file-06-knowledge-provider.php',
    'class-file-10-video-media-provider.php',
    'class-file-11-reels-media-provider.php',
    'class-file-12-pdf-media-provider.php',
] as $marker) {
    if (! str_contains($main, $marker)) {
        $errors[] = 'Native adapter is not loaded by the plugin bootstrap: ' . $marker;
    }
}
if (! str_contains($main, 'sabri_visual_experience_render_notice')) {
    $errors[] = 'Canonical notice helper is missing.';
}

$plugin = file_get_contents($root . '/includes/class-plugin.php') ?: '';
foreach ([
    'File_06_Knowledge_Provider',
    'File_10_Video_Media_Provider',
    'File_11_Reels_Media_Provider',
    'File_12_Pdf_Media_Provider',
    'register_section_providers',
] as $marker) {
    if (! str_contains($plugin, $marker)) {
        $errors[] = 'Native provider registration marker missing: ' . $marker;
    }
}

$provider_requirements = [
    'class-file-06-knowledge-provider.php' => ['HE_VERSION', 'HE_Content::TYPE', "'he_entry'", "'publish'", "'author' => \$user_id", "'has_password' => false", "return 'read-only'", 'owns_native_content'],
    'class-file-10-video-media-provider.php' => ['SVW_VERSION', 'SVW_Helpers::TYPE', "'svw_video'", "'publish'", "'author' => \$user_id", "'has_password' => false", "return 'read-only'", "'is_reel'"],
    'class-file-11-reels-media-provider.php' => ['SRL_VERSION', 'SVW_VERSION', 'SVW_Helpers::TYPE', "'svw_video'", "'meta_key' => '_svw_is_reel'", "'meta_value' => '1'", '60', '600', "return 'read-only'"],
    'class-file-12-pdf-media-provider.php' => ['SPL_VERSION', 'SPL_Helpers::TYPE', "'spl_document'", "'publish'", "'author' => \$user_id", "'has_password' => false", "return 'read-only'", 'owns_native_content'],
];
foreach ($provider_requirements as $file => $markers) {
    $provider = file_get_contents($root . '/includes/providers/' . $file) ?: '';
    foreach ($markers as $marker) {
        if (! str_contains($provider, $marker)) {
            $errors[] = $file . ' boundary missing: ' . $marker;
        }
    }
    if (preg_match('/update_|insert_|delete_|wp_insert_post|wp_update_post|wp_delete_post|set_post_thumbnail|media_handle_upload/i', $provider)) {
        $errors[] = $file . ' must remain strictly read-only.';
    }

    if ($file === 'class-file-11-reels-media-provider.php') {
        $file_11_gate = str_contains($provider, 'version_supported($this->get_version(), self::MINIMUM_VERSION, self::MAXIMUM_VERSION)');
        $file_10_gate = str_contains($provider, 'version_supported($file_10, self::FILE_10_MINIMUM, self::FILE_10_MAXIMUM)');
        $helper_bounds = str_contains($provider, "version_compare(\$version, \$minimum, '<')")
            && str_contains($provider, "version_compare(\$version, \$maximum, '>=')");
        if (! $file_11_gate || ! $file_10_gate || ! $helper_bounds) {
            $errors[] = $file . ' reviewed File 10/File 11 version ranges are not enforced.';
        }
        continue;
    }

    if (! str_contains($provider, "version_compare(\$version, self::MINIMUM_VERSION, '<')")
        || ! str_contains($provider, "version_compare(\$version, self::MAXIMUM_VERSION, '>=')")) {
        $errors[] = $file . ' reviewed version range is not enforced.';
    }
}

$registry = file_get_contents($root . '/includes/class-section-registry.php') ?: '';
foreach (["'maturity' => \$maturity", "'owns_native_content' => false", "hash_equals(\$registered['maturity'], \$maturity)"] as $marker) {
    if (! str_contains($registry, $marker)) {
        $errors[] = 'Complete provider metadata immutability marker missing: ' . $marker;
    }
}

$acceptance = file_get_contents($root . '/includes/class-visual-acceptance.php') ?: '';
foreach (['artifact_ref', 'sha256', 'recorded_at', 'reviewer', 'staging_environment', 'founder_signoff', 'media-section'] as $marker) {
    if (! str_contains($acceptance, $marker)) {
        $errors[] = 'Strict visual evidence marker missing: ' . $marker;
    }
}

$cards = file_get_contents($root . '/includes/class-content-cards.php') ?: '';
foreach (['deterministic_dates', "'date_timezone' => 'UTC'", "new \\DateTimeZone('UTC')", "createFromFormat('!Y-m-d'", 'getLastErrors'] as $marker) {
    if (! str_contains($cards, $marker)) {
        $errors[] = 'Deterministic card-date protection missing: ' . $marker;
    }
}

$assets = file_get_contents($root . '/includes/class-assets.php') ?: '';
foreach (['sabri-visual-design-system-core', 'design-system-components.css', "['sabri-visual-design-system-core']"] as $marker) {
    if (! str_contains($assets, $marker)) {
        $errors[] = 'Canonical component stylesheet dependency missing: ' . $marker;
    }
}

$component_css = file_get_contents($root . '/assets/css/design-system-components.css') ?: '';
foreach (['.sabri-ui-notice__title', '.sabri-ui-notice__message', '.sabri-ui-input:disabled', '@media (forced-colors: active)', '@media print'] as $marker) {
    if (! str_contains($component_css, $marker)) {
        $errors[] = 'Component-completion CSS marker missing: ' . $marker;
    }
}
if (preg_match('/@import\s+url|fonts\.googleapis|use\.typekit|url\(\s*["\']?https?:/i', $component_css)) {
    $errors[] = 'Remote dependency detected in component-completion CSS.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "PASS: native knowledge/media adapters and component package boundaries\n";
