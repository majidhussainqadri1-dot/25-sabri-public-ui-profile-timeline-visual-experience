<?php
/**
 * File 25 uninstall is deliberately non-destructive.
 *
 * Native profiles, publications, comments, media, and provider data are never
 * deleted here. Rebuildable indexes will be introduced only with an explicit,
 * documented uninstall decision and administrator confirmation path.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Preserve settings by default. The platform removal workflow owns deletion.
