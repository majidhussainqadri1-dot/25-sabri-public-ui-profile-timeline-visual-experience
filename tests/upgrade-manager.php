<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}
if (! defined('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION')) {
    define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '2');
}
if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
    define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.12.0');
}

$GLOBALS['spux_upgrade_options'] = [
    'sabri_public_experience_schema_version' => '1',
];
$GLOBALS['spux_upgrade_flushes'] = 0;
$GLOBALS['spux_upgrade_rules'] = [];

function get_option(string $name, mixed $default = false): mixed
{
    return $GLOBALS['spux_upgrade_options'][$name] ?? $default;
}
function add_option(string $name, mixed $value, string $deprecated = '', mixed $autoload = null): bool
{
    unset($deprecated, $autoload);
    if (array_key_exists($name, $GLOBALS['spux_upgrade_options'])) {
        return false;
    }
    $GLOBALS['spux_upgrade_options'][$name] = $value;
    return true;
}
function update_option(string $name, mixed $value, mixed $autoload = null): bool
{
    unset($autoload);
    $GLOBALS['spux_upgrade_options'][$name] = $value;
    return true;
}
function delete_option(string $name): bool
{
    unset($GLOBALS['spux_upgrade_options'][$name]);
    return true;
}
function wp_generate_uuid4(): string
{
    return '11111111-2222-4333-8444-555555555555';
}
function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
{
    unset($hook, $callback, $priority, $accepted_args);
}
function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
{
    unset($hook, $callback, $priority, $accepted_args);
}
function add_rewrite_rule(string $regex, string $query, string $position): void
{
    $GLOBALS['spux_upgrade_rules'][] = [$regex, $query, $position];
}
function flush_rewrite_rules(bool $hard = true): void
{
    unset($hard);
    $GLOBALS['spux_upgrade_flushes']++;
}
function sanitize_key(string $value): string
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)) ?? '';
}
function sanitize_title(string $value): string
{
    return trim(preg_replace('/[^a-z0-9\-]+/', '-', strtolower($value)) ?? '', '-');
}
function get_query_var(string $key): mixed
{
    unset($key);
    return '';
}

require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
require_once dirname(__DIR__) . '/includes/class-section-registry.php';
require_once dirname(__DIR__) . '/includes/class-profile-router.php';
require_once dirname(__DIR__) . '/includes/class-upgrade-manager.php';

use Sabri\PublicExperience\Upgrade_Manager;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

Upgrade_Manager::maybe_upgrade();
$check(($GLOBALS['spux_upgrade_options']['sabri_public_experience_schema_version'] ?? '') === '2', 'Schema upgrade must reach version 2.');
$check(($GLOBALS['spux_upgrade_options']['sabri_public_experience_runtime_version'] ?? '') === '0.12.0', 'Runtime version must be recorded after upgrade.');
$check($GLOBALS['spux_upgrade_flushes'] === 1, 'Rewrite rules must flush exactly once during the schema upgrade.');
$check(count($GLOBALS['spux_upgrade_rules']) === 3, 'Upgrade rewrite refresh must register all three profile route families.');
$check(! isset($GLOBALS['spux_upgrade_options']['sabri_public_experience_upgrade_lock']), 'Upgrade lock must be released after success.');

Upgrade_Manager::maybe_upgrade();
$check($GLOBALS['spux_upgrade_flushes'] === 1, 'Completed schema upgrade must be idempotent.');

$GLOBALS['spux_upgrade_options']['sabri_public_experience_schema_version'] = '1';
$GLOBALS['spux_upgrade_options']['sabri_public_experience_upgrade_lock'] = [
    'token' => 'stale',
    'created_at' => time() - 1000,
];
Upgrade_Manager::maybe_upgrade();
$check(($GLOBALS['spux_upgrade_options']['sabri_public_experience_schema_version'] ?? '') === '2', 'Stale upgrade lock must be recoverable.');
$check($GLOBALS['spux_upgrade_flushes'] === 2, 'Recovered stale lock must permit one bounded rewrite refresh.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 idempotent schema-2 route upgrade and stale-lock recovery\n";
