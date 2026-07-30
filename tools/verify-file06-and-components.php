<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$required = [
    'includes/providers/class-file-06-knowledge-provider.php',
    'tests/file06-knowledge-provider.php',
    'assets/css/design-system-components.css',
    'docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md',
    'docs/FOURTH-REVIEW-AND-CORRECTION-2026-07-31.md',
];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing File 06/component completion artifact: ' . $path;
    }
}

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
if (! str_contains($main, 'class-file-06-knowledge-provider.php')) {
    $errors[] = 'File 06 provider is not loaded by the plugin bootstrap.';
}
if (! str_contains($main, 'sabri_visual_experience_render_notice')) {
    $errors[] = 'Canonical notice helper is missing.';
}

$plugin = file_get_contents($root . '/includes/class-plugin.php') ?: '';
foreach (['File_06_Knowledge_Provider', "get('file-06-knowledge')", 'register_section_providers'] as $marker) {
    if (! str_contains($plugin, $marker)) {
        $errors[] = 'File 06 provider registration marker missing: ' . $marker;
    }
}

$provider = file_get_contents($root . '/includes/providers/class-file-06-knowledge-provider.php') ?: '';
foreach (['HE_VERSION', 'HE_Content::TYPE', "'he_entry'", "'publish'", "'author' => \$user_id", "'has_password' => false", "return 'read-only'", 'owns_native_content'] as $marker) {
    if (! str_contains($provider, $marker)) {
        $errors[] = 'File 06 adapter boundary missing: ' . $marker;
    }
}
if (preg_match('/update_|insert_|delete_|wp_insert_post|wp_update_post|wp_delete_post|set_post_thumbnail/i', $provider)) {
    $errors[] = 'File 06 provider must remain strictly read-only.';
}
if (! str_contains($provider, "version_compare(\$version, self::MINIMUM_VERSION, '<')")
    || ! str_contains($provider, "version_compare(\$version, self::MAXIMUM_VERSION, '>=')")) {
    $errors[] = 'File 06 reviewed version range is not enforced.';
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

echo "PASS: File 06 adapter and component completion package boundaries\n";
