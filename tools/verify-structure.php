<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    '.github/workflows/ci.yml',
    'sabri-public-experience.php',
    'readme.txt',
    'composer.json',
    'includes/class-plugin.php',
    'includes/class-safe-mode.php',
    'includes/class-public-url.php',
    'includes/class-components.php',
    'includes/class-content-cards.php',
    'includes/class-design-system.php',
    'includes/class-native-integration.php',
    'includes/class-visibility-policy.php',
    'includes/class-profile-data.php',
    'includes/class-profile-repository.php',
    'includes/class-profile-renderer.php',
    'includes/class-shell-integration.php',
    'includes/class-system-check.php',
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/contracts/interface-timeline-provider.php',
    'includes/providers/class-file-21-provider.php',
    'templates/public-profile.php',
    'templates/partials/profile-hero.php',
    'templates/partials/timeline.php',
    'templates/partials/founder-overview.php',
    'templates/partials/doctor-overview.php',
    'templates/partials/books-research.php',
    'templates/partials/clinic-contact.php',
    'templates/partials/about.php',
    'assets/css/design-system.css',
    'assets/css/public.css',
    'assets/css/profile-sections.css',
    'assets/js/public.js',
    'tests/profile-data.php',
    'tests/file21-provider.php',
    'tests/design-system.php',
    'tests/content-cards.php',
    'tests/safe-mode.php',
    'SECURITY.md',
    'PRIVACY.md',
    'docs/ARCHITECTURE.md',
    'docs/GOVERNING-SCOPE.md',
    'docs/DECISION-LOG.md',
    'docs/DESIGN-SYSTEM-CONTRACT.md',
    'docs/CONTENT-CARD-CONTRACT.md',
    'docs/THIRD-REVIEW-AND-CORRECTION-2026-07-30.md',
    'docs/SECOND-REVIEW-AND-CORRECTION-2026-07-30.md',
    'docs/REVIEW-AND-CORRECTION-2026-07-30.md',
    'docs/PHASE-25C-25D-IMPLEMENTATION.md',
];

$errors = [];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        $errors[] = 'Missing required file: ' . $path;
    }
}

$css = '';
foreach (['assets/css/design-system.css', 'assets/css/public.css', 'assets/css/profile-sections.css'] as $stylesheet) {
    $css .= "\n" . (file_get_contents($root . '/' . $stylesheet) ?: '');
}
if (preg_match('/@import\s+url|fonts\.googleapis|use\.typekit|url\(\s*["\']?https?:/i', $css)) {
    $errors[] = 'Remote CSS/font dependency detected.';
}
if (! str_contains($css, '.spux-profile *')) {
    $errors[] = 'Profile reduced-motion rules are not scoped to File 25.';
}
if (! str_contains($css, '--sabri-shell-primary') || ! str_contains($css, '--sabri-visual-primary')) {
    $errors[] = 'File 20 inheritance or File 25 semantic design tokens are missing.';
}
foreach ([
    '.sabri-ui-card',
    '.sabri-ui-content-card',
    '.sabri-ui-button',
    '.sabri-ui-state',
    '.sabri-ui-notice',
    '.sabri-ui-field',
    '.sabri-ui-table-wrap',
    '.sabri-ui-skeleton',
] as $component) {
    if (! str_contains($css, $component)) {
        $errors[] = 'Global visual component missing: ' . $component;
    }
}
foreach (['--sabri-visual-on-primary', '--sabri-visual-on-danger', '--sabri-visual-primary-soft'] as $contrast_token) {
    if (! str_contains($css, $contrast_token)) {
        $errors[] = 'Contrast-safe semantic token missing: ' . $contrast_token;
    }
}
if (! str_contains($css, '@media (forced-colors: active)')) {
    $errors[] = 'Forced-colors accessibility support is missing.';
}
if (! str_contains($css, '@media print')) {
    $errors[] = 'Print-safe public component behavior is missing.';
}

$javascript = file_get_contents($root . '/assets/js/public.js') ?: '';
if (preg_match('/\b(eval|document\.write)\s*\(/i', $javascript)) {
    $errors[] = 'Unsafe JavaScript execution primitive detected.';
}
if (preg_match('/https?:\/\//i', $javascript)) {
    $errors[] = 'Unexpected remote JavaScript endpoint detected.';
}

$main = file_get_contents($root . '/sabri-public-experience.php') ?: '';
$readme = file_get_contents($root . '/readme.txt') ?: '';
preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header_match);
preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable_match);
preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant_match);
$versions = array_filter([
    $header_match[1] ?? '',
    $stable_match[1] ?? '',
    $constant_match[1] ?? '',
]);
if (count($versions) !== 3 || count(array_unique($versions)) !== 1) {
    $errors[] = 'Plugin header, constant, and readme stable-tag versions do not match.';
}
if (! str_contains($main, 'Sabri Unified Global Visual Experience and Design System')) {
    $errors[] = 'Founder-approved canonical File 25 name is missing from the plugin header.';
}
if (! str_contains($main, "require_once SABRI_PUBLIC_EXPERIENCE_DIR . 'includes/class-safe-mode.php'")) {
    $errors[] = 'Safe Mode must load before the remaining runtime classes.';
}
if (! str_contains($main, "version_compare(PHP_VERSION, '8.0', '<')")) {
    $errors[] = 'Pre-require PHP runtime guard is missing.';
}
foreach ([
    'class-public-url.php',
    'class-components.php',
    'class-content-cards.php',
    'class-design-system.php',
    'class-profile-data.php',
    'class-shell-integration.php',
    'class-file-21-provider.php',
] as $runtime_file) {
    if (! str_contains($main, $runtime_file)) {
        $errors[] = 'Required runtime file is not loaded: ' . $runtime_file;
    }
}
foreach ([
    'sabri_visual_experience_contract',
    'sabri_visual_experience_render_state',
    'sabri_visual_experience_render_card',
] as $function) {
    if (! str_contains($main, $function)) {
        $errors[] = 'Public design-system integration function missing: ' . $function;
    }
}

$plugin = file_get_contents($root . '/includes/class-plugin.php') ?: '';
foreach (['home_news_available', 'new Shell_Integration', 'new File_21_Provider', 'new Design_System', 'new Assets'] as $marker) {
    if (! str_contains($plugin, $marker)) {
        $errors[] = 'Plugin bootstrap boundary missing: ' . $marker;
    }
}

$shell = file_get_contents($root . '/includes/class-shell-integration.php') ?: '';
if (str_contains($shell, '<style') || str_contains($shell, 'wp_add_inline_style')) {
    $errors[] = 'File 20 token integration must not depend on an inline CSS bridge.';
}
if (! str_contains($shell, '$base[\'owns_global_shell\'] = false')) {
    $errors[] = 'File 20 shell ownership denial is missing.';
}

$design_system = file_get_contents($root . '/includes/class-design-system.php') ?: '';
foreach (['reusable-content-cards', 'Content_Cards::contract', 'creates_file_26', 'shell_is_available'] as $marker) {
    if (! str_contains($design_system, $marker)) {
        $errors[] = 'Design-system contract marker missing: ' . $marker;
    }
}

$public_url = file_get_contents($root . '/includes/class-public-url.php') ?: '';
foreach (["str_starts_with(\$url, '//')", "str_contains(\$url, '\\\\')", 'home_url', 'isset($parts[\'user\'])'] as $marker) {
    if (! str_contains($public_url, $marker)) {
        $errors[] = 'Same-origin URL protection marker missing: ' . $marker;
    }
}

$content_cards = file_get_contents($root . '/includes/class-content-cards.php') ?: '';
foreach (['owns_native_data', 'same_site_destinations', 'Public_URL::sanitize_same_site', 'sabri-ui-content-card'] as $marker) {
    if (! str_contains($content_cards, $marker)) {
        $errors[] = 'Content-card boundary marker missing: ' . $marker;
    }
}
if (preg_match('/update_|insert_|delete_|wp_insert_post|wp_update_post|wp_delete_post/i', $content_cards)) {
    $errors[] = 'Content-card renderer must remain presentation-only.';
}

$timeline = file_get_contents($root . '/includes/class-timeline-service.php') ?: '';
foreach (['provider_version', 'canonical_is_allowed', 'to_public_array'] as $marker) {
    if (! str_contains($timeline, $marker)) {
        $errors[] = 'Reviewed timeline invariant missing: ' . $marker;
    }
}

$file_21 = file_get_contents($root . '/includes/providers/class-file-21-provider.php') ?: '';
foreach (['ProfileTimeline::query', "'read-only'", 'NATIVE_PAGE_SIZE', 'owns_native_content'] as $marker) {
    if (! str_contains($file_21, $marker)) {
        $errors[] = 'File 21 adapter boundary missing: ' . $marker;
    }
}
if (preg_match('/update_|insert_|delete_|wp_insert_post|wp_update_post|wp_delete_post/i', $file_21)) {
    $errors[] = 'File 21 adapter must remain read-only.';
}

$repository = file_get_contents($root . '/includes/class-profile-repository.php') ?: '';
foreach (['Profile_Data::founder_details', 'Profile_Data::professional_details', 'Profile_Data::clinic'] as $marker) {
    if (! str_contains($repository, $marker)) {
        $errors[] = 'Structured public profile projection marker missing: ' . $marker;
    }
}

$safe_mode = file_get_contents($root . '/includes/class-safe-mode.php') ?: '';
foreach (['boundary_stack', 'current_boundary', 'array_pop'] as $marker) {
    if (! str_contains($safe_mode, $marker)) {
        $errors[] = 'Nested Safe Mode boundary protection missing: ' . $marker;
    }
}

$composer_raw = file_get_contents($root . '/composer.json') ?: '';
$composer = json_decode($composer_raw, true);
if (! is_array($composer) || ($composer['require']['php'] ?? '') !== '>=8.0') {
    $errors[] = 'Composer PHP requirement is invalid or missing.';
}

if ($errors !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "PASS: File 25 global visual system package structure\n";
