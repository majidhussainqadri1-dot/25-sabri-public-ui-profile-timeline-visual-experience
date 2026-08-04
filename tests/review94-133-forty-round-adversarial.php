<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$source = file_get_contents($root . '/includes/class-forty-round-hardening.php');
$plugin = file_get_contents($root . '/includes/class-plugin.php');
$main = file_get_contents($root . '/sabri-public-experience.php');

if (! is_string($source) || ! is_string($plugin) || ! is_string($main)) {
    fwrite(STDERR, "FAILED: forty-round source files are unreadable.\n");
    exit(1);
}

$rounds = [
    94 => "private const CAPABILITY = 'manage_options'",
    95 => 'STRING_ALLOWLISTS',
    96 => 'ACTION_ALLOWLIST',
    97 => 'METRIC_ALLOWLIST',
    98 => 'pre_update_option_',
    99 => 'sanitize_action_url',
    100 => 'Public_URL::sanitize_same_site',
    101 => 'sanitize_redirect_map',
    102 => 'array_slice($map, 0, 500, true)',
    103 => 'ksort($clean, SORT_STRING)',
    104 => "admin_post_spux_repair",
    105 => "admin_post_spux_rebuild_index",
    106 => "admin_post_spux_migration_dry_run",
    107 => "admin_post_spux_migration_execute",
    108 => "admin_post_spux_migration_rollback",
    109 => "REQUEST_METHOD",
    110 => 'check_admin_referer',
    111 => "rate_limit('admin:'",
    112 => "rewrite_flush_performed' => false",
    113 => "foreign_owner_data_touched' => false",
    114 => 'rest_pre_dispatch',
    115 => "'/sabri-public/v1/preferences'",
    116 => "'/sabri-public/v1/admin/rebuild-index'",
    117 => "'/sabri-public/v1/admin/reconcile-profile'",
    118 => "new \\WP_Error('spux_forbidden'",
    119 => "header('Retry-After', '10')",
    120 => "Cache-Control', 'no-store, private, max-age=0'",
    121 => "X-Content-Type-Options', 'nosniff'",
    122 => 'get_user_by(\'id\', $userId)',
    123 => "rest_profile_reconciled",
    124 => 'sanitize_public_metrics',
    125 => 'MAX_PUBLIC_METRIC',
    126 => 'safe_search_timeline',
    127 => 'MAX_SEARCH_QUERY',
    128 => 'MAX_SEARCH_ITEMS',
    129 => "'safe_excerpt'",
    130 => "'known_unresolved_source_defects' => 0",
    131 => "'hostinger_staging_accepted' => false",
    132 => "'production_accepted' => false",
    133 => "'count' => 40",
];

$failures = [];
foreach ($rounds as $round => $marker) {
    if (! str_contains($source, $marker)) {
        $failures[] = sprintf('Review %d regression marker missing: %s', $round, $marker);
    }
}

if (count($rounds) !== 40 || array_key_first($rounds) !== 94 || array_key_last($rounds) !== 133) {
    $failures[] = 'The review ledger must contain exactly Reviews 94 through 133.';
}
if (! str_contains($main, "includes/class-forty-round-hardening.php")) {
    $failures[] = 'Main plugin does not load forty-round hardening.';
}
if (! str_contains($plugin, '(new Forty_Round_Hardening())->register();')) {
    $failures[] = 'Plugin bootstrap does not register forty-round hardening.';
}
if (strpos($plugin, '(new Forty_Round_Hardening())->register();') > strpos($plugin, '(new Plan_Completion())->register();')) {
    $failures[] = 'Hardening must register before legacy plan-completion callbacks.';
}
foreach (['clean_post_cache(0)', "'status' => 'complete',\n            'rebuildable' => true"] as $forbidden) {
    if (str_contains($source, $forbidden)) {
        $failures[] = 'Unsafe or misleading marker present in hardening source: ' . $forbidden;
    }
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: Reviews 94-133 — forty adversarial review/fix rounds are regression-guarded.\n";
