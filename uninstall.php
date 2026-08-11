<?php
/**
 * File 25 uninstall is deliberately non-destructive.
 *
 * Native profiles, publications, comments, media, security evidence and all
 * provider-owned records remain untouched. File 25 preferences and reversible
 * migration evidence are preserved by default for rollback and reinstall.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$report = [
    'schema' => 1,
    'generated_at_utc' => gmdate('Y-m-d H:i:s'),
    'runtime' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
        ? SABRI_PUBLIC_EXPERIENCE_VERSION
        : 'unknown',
    'preserved_canonical_data' => true,
    'preserved_preferences' => true,
    'preserved_preference_history' => true,
    'preserved_migration_and_rollback_evidence' => true,
    'foreign_owner_data_touched' => false,
    'rebuildable_derivatives' => [
        'sabri_public_experience_timeline_index',
        'sabri_public_experience_timeline_index_lock',
        'sabri_public_experience_repair_state',
    ],
    'reversible_file25_options' => [
        'sabri_public_experience_preferences',
        'sabri_public_experience_preferences_meta',
        'sabri_public_experience_preferences_history',
        'sabri_public_experience_migration_state',
        'sabri_public_experience_active_redirect_map',
        'sabri_public_experience_review_94_133_audit',
    ],
    'destructive_purge_performed' => false,
];

if (function_exists('update_option')) {
    update_option('sabri_public_experience_uninstall_report', $report, false);
}
