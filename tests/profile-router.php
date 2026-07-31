<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

$GLOBALS['spux_test_query_vars'] = [];
$GLOBALS['spux_test_rewrite_rules'] = [];

if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)) ?? '';
    }
}
if (! function_exists('sanitize_title')) {
    function sanitize_title(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9\-]+/', '-', strtolower($value)) ?? '', '-');
    }
}
if (! function_exists('get_query_var')) {
    function get_query_var(string $key): mixed
    {
        return $GLOBALS['spux_test_query_vars'][$key] ?? '';
    }
}
if (! function_exists('add_rewrite_rule')) {
    function add_rewrite_rule(string $regex, string $query, string $position): void
    {
        $GLOBALS['spux_test_rewrite_rules'][] = [$regex, $query, $position];
    }
}
if (! function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        unset($hook, $callback, $priority, $accepted_args);
    }
}
if (! function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        unset($hook, $callback, $priority, $accepted_args);
    }
}
if (! function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules(bool $hard = true): void
    {
        unset($hard);
    }
}

require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
require_once dirname(__DIR__) . '/includes/class-section-registry.php';
require_once dirname(__DIR__) . '/includes/class-profile-router.php';

use Sabri\PublicExperience\Profile_Router;
use Sabri\PublicExperience\Section_Registry;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$sections = Profile_Router::route_sections();
foreach (Section_Registry::approved_sections() as $section) {
    $check(in_array($section, $sections, true), 'Approved provider section is not routable: ' . $section);
}
$check(in_array('books-research', $sections, true), 'Founder books-research route must remain available.');
$check(count($sections) === count(array_unique($sections)), 'Profile route sections must be unique.');

$router = new Profile_Router();
$router->register_rewrite_rules();
$check(count($GLOBALS['spux_test_rewrite_rules']) === 3, 'Founder, Doctor, and Member rewrite rules are required.');
foreach ($GLOBALS['spux_test_rewrite_rules'] as $rule) {
    $check(str_contains($rule[0], 'marketplace'), 'Marketplace must be included in every profile route pattern.');
    $check(str_contains($rule[0], 'research'), 'Research must be included in every profile route pattern.');
}

$GLOBALS['spux_test_query_vars'] = [
    'spux_profile_type' => 'doctor',
    'spux_profile_slug' => 'verified-doctor',
    'spux_profile_section' => 'marketplace',
];
$check($router->is_profile_request(), 'Marketplace Doctor profile route must be recognized.');

$GLOBALS['spux_test_query_vars']['spux_profile_section'] = 'research';
$check($router->is_profile_request(), 'Research Doctor profile route must be recognized.');

$GLOBALS['spux_test_query_vars']['spux_profile_section'] = 'unknown';
$check(! $router->is_profile_request(), 'Unknown profile section must fail closed.');

$GLOBALS['spux_test_query_vars'] = [
    'spux_profile_type' => 'member',
    'spux_profile_slug' => '12345',
    'spux_profile_section' => 'overview',
];
$check(! $router->is_profile_request(), 'Numeric member slugs must remain rejected.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 provider-route parity and fail-closed profile routing\n";
