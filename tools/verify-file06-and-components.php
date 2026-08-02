<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$required = [
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'tests/file06-knowledge-provider.php',
    'tests/file10-12-media-providers.php',
    'tests/file18-marketplace-provider.php',
    'assets/css/design-system-components.css',
    'docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md',
    'docs/FOURTH-REVIEW-AND-CORRECTION-2026-07-31.md',
    'docs/FIFTH-REVIEW-AND-NATIVE-MEDIA-2026-07-31.md',
    'docs/SIXTH-REVIEW-AND-MARKETPLACE-2026-07-31.md',
    'docs/FILE18-MARKETPLACE-ADAPTER-IMPLEMENTATION.md',
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
    'class-file-18-marketplace-provider.php',
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
    'File_18_Marketplace_Provider',
    "'file-18-marketplace'",
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
    'class-file-18-marketplace-provider.php' => [
        'SMP_VERSION',
        "MINIMUM_VERSION = '1.2.0-RC1'",
        'smp_get_public_profile_listings',
        'SMP_Utils::current_seller',
        'SMP_REST::products',
        'SMP_Activator::marketplace_url',
        'TRANSITIONAL_FETCH_LIMIT',
        "return 'read-only'",
        'projection_key',
        'owns_native_content',
    ],
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

    if ($file === 'class-file-18-marketplace-provider.php') {
        foreach (['$wpdb', 'SMP_DB::table', 'SELECT p.*'] as $forbidden) {
            if (str_contains($provider, $forbidden)) {
                $errors[] = $file . ' must consume File 18 owner APIs, not direct table/query marker: ' . $forbidden;
            }
        }
        if (! str_contains($provider, "version_compare(\$version, self::MINIMUM_VERSION, '<')")
            || ! str_contains($provider, "version_compare(\$version, self::MAXIMUM_VERSION, '>=')")) {
            $errors[] = $file . ' reviewed File 18 version range is not enforced.';
        }
        continue;
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
foreach ([
    "'maturity' => \$maturity",
    "'owns_native_content' => false",
    "'object_id' => spl_object_id(\$provider)",
    'validated_metadata',
    "hash_equals(\$registered['maturity'], \$current['maturity'])",
    "\$registered['object_id'] === \$current['object_id']",
] as $marker) {
    if (! str_contains($registry, $marker)) {
        $errors[] = 'Complete provider identity/metadata immutability marker missing: ' . $marker;
    }
}

$section_service = file_get_contents($root . '/includes/class-section-service.php') ?: '';
foreach (['validated_metadata', 'projection_key', "preg_match('/^[a-f0-9]{64}$/', \$projection_key)", "hash('sha256', 'projection|' . \$projection_key)"] as $marker) {
    if (! str_contains($section_service, $marker)) {
        $errors[] = 'Atomic provider or opaque projection-key marker missing: ' . $marker;
    }
}

$acceptance = file_get_contents($root . '/includes/class-visual-acceptance.php') ?: '';
foreach (['target_commit_sha', 'commit_sha', 'artifact_ref', 'sha256', 'recorded_at', 'reviewer', 'staging_environment', 'founder_signoff', 'media-section', 'marketplace-section', 'checkdate', 'getLastErrors'] as $marker) {
    if (! str_contains($acceptance, $marker)) {
        $errors[] = 'Commit-bound strict visual evidence marker missing: ' . $marker;
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

echo "PASS: native Knowledge/Media/File 18 owner-DTO adapters, atomic providers, and component boundaries\n";
