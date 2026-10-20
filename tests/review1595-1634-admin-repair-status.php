<?php
declare(strict_types=1);
/**
 * File25 R1600–1603 compatibility plus R1795–1834: actual Repair status,
 * atomic stale-index generation fencing, and truthful admin notice contract.
 * Fake SQL CAS tests are source-level evidence, NOT installed MySQL tests.
 */
namespace Sabri\PublicExperience {
    final class Profile_Router {
        public static function flush(): void { ++$GLOBALS['r1600_flushes']; }
    }
}
namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    $GLOBALS['r1600_options'] = [];
    $GLOBALS['r1600_fail_stale'] = true;
    $GLOBALS['r1600_repair_race'] = '';
    $GLOBALS['r1600_flushes'] = 0;

    function get_option(string $key, mixed $fallback = false): mixed {
        return array_key_exists($key, $GLOBALS['r1600_options'])
            ? $GLOBALS['r1600_options'][$key] : $fallback;
    }
    function add_option(string $key, mixed $value, string $deprecated = '', mixed $autoload = null): bool {
        if (array_key_exists($key, $GLOBALS['r1600_options'])) { return false; }
        $GLOBALS['r1600_options'][$key] = $value;
        return true;
    }
    function update_option(string $key, mixed $value, mixed $autoload = null): bool {
        $GLOBALS['r1600_options'][$key] = $value;
        return true;
    }
    function delete_transient(string $key): bool { return true; }
    function get_transient(string $key): mixed { return false; }
    function sanitize_key(string $value): string {
        return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower($value));
    }
    function wp_generate_uuid4(): string { return '16000000-aaaa-4bbb-8ccc-000000000001'; }
    function do_action(string $hook, mixed ...$args): void {}

    require_once __DIR__ . '/fixtures/fake-atomic-index-wpdb.php';
    $GLOBALS['wpdb'] = new F25_Fake_Atomic_Wpdb('r1600_options');
    require_once dirname(__DIR__) . '/includes/class-index-atomic-options.php';
    require_once dirname(__DIR__) . '/includes/class-plan-completion.php';

    $service = new \Sabri\PublicExperience\Plan_Completion();
    $repair = new \ReflectionMethod($service, 'repair');
    $repair->setAccessible(true);
    $errors = [];
    $check = static function (bool $ok, string $message) use (&$errors): void {
        if (! $ok) { $errors[] = $message; }
    };
    $index_key = 'sabri_public_experience_timeline_index';
    $lock_key = 'sabri_public_experience_timeline_index_lock';
    $stale = [
        'status' => 'running',
        'generation' => 'stale-worker',
        'started_at_utc' => gmdate('Y-m-d H:i:s', time() - 1000),
        'updated_at_utc' => gmdate('Y-m-d H:i:s', time() - 1000),
    ];
    $reset = static function () use ($index_key, $stale): void {
        $GLOBALS['r1600_options'] = [
            'sabri_public_experience_preferences' => [],
            $index_key => $stale,
        ];
        $GLOBALS['r1600_fail_stale'] = false;
        $GLOBALS['r1600_repair_race'] = '';
    };

    // Failed CAS must preserve the actual running index and tell the operator.
    $reset();
    $GLOBALS['r1600_fail_stale'] = true;
    $failed = $repair->invoke($service);
    $check(($failed['status'] ?? '') === 'partial'
        && ($failed['operations']['stale_index'] ?? null) === false,
        'Rejected atomic stale-index write must report partial repair.');
    $check(get_option($index_key) === $stale,
        'Rejected repair must not claim a persisted failed index state.');
    $check(get_option($lock_key, null) === null,
        'Failed repair must release its own exact lease safely.');

    // Normal persisted CAS can repair abandoned stale state.
    $reset();
    $ok = $repair->invoke($service);
    $check(($ok['status'] ?? '') === 'complete'
        && ($ok['operations']['stale_index'] ?? false) === true,
        'Correctly fenced stale repair must report complete.');
    $check((get_option($index_key)['error_code'] ?? '') === 'stale_rebuild_reconciled',
        'Atomic repair must persist the correct reason.');
    $check(get_option($lock_key, null) === null,
        'Successful repair must release only its own lease.');

    // An apparently old index still has a valid worker-owned lease.
    $reset();
    $live_lock = ['generation' => 'live-worker', 'expires' => time() + 200];
    $GLOBALS['r1600_options'][$lock_key] = $live_lock;
    $busy = $repair->invoke($service);
    $check(($busy['status'] ?? '') === 'partial'
        && ($busy['operations']['stale_index'] ?? null) === false,
        'Active rebuild lease must block competing repair.');
    $check(get_option($index_key) === $stale && get_option($lock_key) === $live_lock,
        'Active rebuild state and lease may not be touched.');

    // Atomic SQL detects a new worker installed after the old repair read.
    $reset();
    $GLOBALS['r1600_repair_race'] = 'replace_on_repair';
    $raced = $repair->invoke($service);
    $check(($raced['status'] ?? '') === 'partial',
        'Race-lost repair may not report success.');
    $check((get_option($index_key)['generation'] ?? '') === 'newer-worker'
        && (get_option($lock_key)['generation'] ?? '') === 'newer-worker',
        'Race-lost repair must not overwrite newer index or release its lease.');

    // Only an expired exact lock is reclaimable.
    $reset();
    $GLOBALS['r1600_options'][$lock_key] = ['generation' => 'expired', 'expires' => time() - 20];
    $reclaimed = $repair->invoke($service);
    $check(($reclaimed['status'] ?? '') === 'complete'
        && (get_option($index_key)['error_code'] ?? '') === 'stale_rebuild_reconciled'
        && get_option($lock_key, null) === null,
        'Expired exact lease must be reclaimed atomically before repair.');

    // No index exists: repair is a no-op, not an invented failure.
    $reset();
    unset($GLOBALS['r1600_options'][$index_key]);
    $none = $repair->invoke($service);
    $check(($none['status'] ?? '') === 'complete',
        'No stale derived index requires no CAS mutation.');

    $source = file_get_contents(dirname(__DIR__) . '/includes/class-plan-completion.php');
    $check(is_string($source) && str_contains($source, "'busy' => 'index_rebuild_busy'")
        && str_contains($source, "default => 'index_rebuild_failed'"),
        'Admin rebuild must distinguish busy/failed from success.');
    $check(is_string($source) && str_contains($source, "? 'repair_complete'")
        && str_contains($source, ": 'repair_partial'")
        && str_contains($source, 'get_option(self::REPAIR_OPTION, null) === $result'),
        'Admin repair notice must be bound to persisted repair results.');

    if ($errors !== []) {
        fwrite(STDERR, "FAILED File25 R1600–1603 / R1795–1834:\n- " . implode("\n- ", $errors) . "\n");
        exit(1);
    }
    echo "PASS File25 R1600–1603 / R1795–1834: atomic stale repair, active lock, race, expiry and truthful notices.\n";
}
