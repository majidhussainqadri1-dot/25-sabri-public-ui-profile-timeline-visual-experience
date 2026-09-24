<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

$captured_options = [];
$captured_actions = [];

if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
}
if (! function_exists('update_option')) {
    function update_option(string $name, mixed $value, bool $autoload = false): bool
    {
        global $captured_options;
        unset($autoload);
        $captured_options[$name] = $value;
        return true;
    }
}
if (! function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        global $captured_actions;
        $captured_actions[] = [$hook, $args];
    }
}

require_once dirname(__DIR__) . '/includes/class-safe-mode.php';

use Sabri\PublicExperience\Safe_Mode;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

Safe_Mode::begin('outer-bootstrap');
$check(Safe_Mode::current_boundary() === 'outer-bootstrap', 'Outer Safe Mode boundary must become current.');
Safe_Mode::begin('inner-provider');
$check(Safe_Mode::current_boundary() === 'inner-provider', 'Nested Safe Mode boundary must become current.');
Safe_Mode::enable('nested-test');
$incident = $captured_options['sabri_public_experience_safe_mode_incident'] ?? [];
$check(($incident['boundary'] ?? '') === 'inner-provider', 'Nested incident must retain the innermost boundary.');

Safe_Mode::end();
$check(Safe_Mode::current_boundary() === 'outer-bootstrap', 'Ending a nested boundary must restore the outer boundary.');
Safe_Mode::enable('outer-test');
$incident = $captured_options['sabri_public_experience_safe_mode_incident'] ?? [];
$check(($incident['boundary'] ?? '') === 'outer-bootstrap', 'Outer incident must remain protected after nested end.');

Safe_Mode::end();
$check(Safe_Mode::current_boundary() === '', 'Ending the final boundary must clear the stack.');
Safe_Mode::end();
$check(Safe_Mode::current_boundary() === '', 'Extra end calls must remain bounded and harmless.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: nested Safe Mode boundaries\n";
