<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * File25-only, guarded database compare-and-swap for its own derived index.
 *
 * Important: WordPress update_option/delete_option are not atomic CAS.
 * Both the expected lock value and the expected index row are compared in
 * ONE InnoDB UPDATE; the lease release is a conditional DELETE. This never
 * changes a File21 publication record or any other native owner's data.
 */
final class Index_Atomic_Options
{
    /**
     * Replace an existing File25 index option only while the specified lock
     * option and the previous index value both match exactly in the database.
     */
    public static function compare_replace(
        string $index_option,
        mixed $old_index,
        mixed $new_index,
        string $lock_option,
        array $expected_lock
    ): bool {
        $db = $GLOBALS['wpdb'] ?? null;
        if (! is_object($db)
            || ! isset($db->options)
            || ! is_string($db->options)
            || ! method_exists($db, 'prepare')
            || ! method_exists($db, 'query')
            || ! function_exists('maybe_serialize')
        ) {
            return false;
        }

        // option_name is unique and indexed in WordPress. BINARY is essential
        // because regular MySQL collations can compare case-insensitively.
        $query = $db->prepare(
            "UPDATE {$db->options} AS current_index
             INNER JOIN {$db->options} AS lease
                 ON lease.option_name = %s
             SET current_index.option_value = %s
             WHERE current_index.option_name = %s
               AND BINARY current_index.option_value = BINARY %s
               AND BINARY lease.option_value = BINARY %s",
            $lock_option,
            maybe_serialize($new_index),
            $index_option,
            maybe_serialize($old_index),
            maybe_serialize($expected_lock)
        );
        if (! is_string($query) || $query === '') {
            return false;
        }
        $updated = $db->query($query);
        if ($updated !== 1) {
            return false;
        }
        self::invalidate_cache($index_option);
        return true;
    }

    /** Delete only the exact originally observed lock, never a new lease. */
    public static function compare_delete(string $option, array $observed_lock): bool
    {
        $db = $GLOBALS['wpdb'] ?? null;
        if (! is_object($db)
            || ! isset($db->options)
            || ! is_string($db->options)
            || ! method_exists($db, 'prepare')
            || ! method_exists($db, 'query')
            || ! function_exists('maybe_serialize')
        ) {
            return false;
        }
        $query = $db->prepare(
            "DELETE FROM {$db->options}
              WHERE option_name = %s
                AND BINARY option_value = BINARY %s",
            $option,
            maybe_serialize($observed_lock)
        );
        if (! is_string($query) || $query === '') {
            return false;
        }
        $deleted = $db->query($query);
        if ($deleted !== 1) {
            return false;
        }
        self::invalidate_cache($option);
        return true;
    }

    private static function invalidate_cache(string $option): void
    {
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete($option, 'options');
            wp_cache_delete('alloptions', 'options');
            wp_cache_delete('notoptions', 'options');
        }
    }
}
