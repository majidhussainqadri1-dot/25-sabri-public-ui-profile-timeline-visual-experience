<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'includes/class-plan-completion.php',
    'templates/partials/profile-hero.php',
    'uninstall.php',
    'sabri-public-experience.php',
];
foreach ($required as $path) {
    if (! is_file($root . '/' . $path)) {
        fwrite(STDERR, "Missing required source: {$path}\n");
        exit(1);
    }
}

$completion = file_get_contents($root . '/includes/class-plan-completion.php');
$bootstrap = file_get_contents($root . '/sabri-public-experience.php');
$hero = file_get_contents($root . '/templates/partials/profile-hero.php');
$uninstall = file_get_contents($root . '/uninstall.php');

$needles = [
    'admin_menu', 'register_setting', "'/preferences'", "'/admin/rebuild-index'",
    "'/admin/reconcile-profile'", 'Last-Modified', 'rate_limited',
    'profile_actions', 'view_public', 'publishing_dashboard', 'search_timeline',
    'public_metrics', 'completion_assistant', 'rebuild_index', 'repair',
    "migration('dry-run')", "migration('executed')", "migration('rolled-back')",
    'uninstall_report',
];
foreach ($needles as $needle) {
    if (! str_contains((string) $completion, $needle)) {
        fwrite(STDERR, "Missing dual-plan implementation marker: {$needle}\n");
        exit(1);
    }
}

$api_functions = [
    'sabri_public_render_profile_card', 'sabri_public_render_content_card',
    'sabri_public_render_badge', 'sabri_public_render_empty_state',
    'sabri_public_render_author_header', 'sabri_public_register_timeline_provider',
    'sabri_public_register_card_variant',
];
foreach ($api_functions as $function) {
    if (! str_contains((string) $bootstrap, "function {$function}")) {
        fwrite(STDERR, "Missing public component API: {$function}\n");
        exit(1);
    }
}

if (! str_contains((string) $hero, 'Plan_Completion::render_profile_actions')) {
    fwrite(STDERR, "Profile actions are not rendered.\n");
    exit(1);
}
if (! str_contains((string) $uninstall, 'sabri_public_experience_uninstall_report')) {
    fwrite(STDERR, "Uninstall report is not governed.\n");
    exit(1);
}

echo "Dual-plan source completion regression PASS\n";
