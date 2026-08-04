<?php
/**
 * File 25 uninstall is deliberately non-destructive.
 * Native profiles, publications, comments, media, security evidence and
 * provider-owned records are never deleted here.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$report = [
    'generated_at_utc' => gmdate('Y-m-d H:i:s'),
    'runtime' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION') ? SABRI_PUBLIC_EXPERIENCE_VERSION : 'unknown',
    'preserved_canonical_data' => true,
    'preserved_preferences' => true,
    'foreign_owner_data_touched' => false,
    'rebuildable_derivatives' => [
        'sabri_public_experience_timeline_index',
        'sabri_public_experience_repair_state',
    ],
    'destructive_purge_performed' => false,
];

// Preserve the report and all canonical/preferences data. A separate, explicit,
// capability-gated platform-removal workflow may later purge approved File 25
// derivative records after export, backup and Founder approval.
if (function_exists('update_option')) {
    update_option('sabri_public_experience_uninstall_report', $report, false);
}
