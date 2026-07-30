<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

require_once dirname(__DIR__) . '/includes/class-visual-acceptance.php';

use Sabri\PublicExperience\Visual_Acceptance;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$contract = Visual_Acceptance::contract();
$check(($contract['owner'] ?? '') === 'file-25', 'File 25 must own visual acceptance.');
$check(($contract['shell_owner'] ?? '') === 'file-20', 'File 20 must remain the shell owner.');
$check(($contract['green_ci_is_acceptance'] ?? true) === false, 'Green CI must not be treated as visual acceptance.');
$check(($contract['staging_required'] ?? false) === true, 'Staging evidence must remain mandatory.');
$check(($contract['founder_signoff_required'] ?? false) === true, 'Founder sign-off must remain mandatory.');
$check(count((array) ($contract['viewports'] ?? [])) === 6, 'Six canonical viewport classes are required.');
$check(in_array('rtl', (array) ($contract['directions'] ?? []), true), 'RTL evidence is required.');
$check(in_array('forced-colors', (array) ($contract['color_modes'] ?? []), true), 'Forced-colors evidence is required.');
$check(in_array(400, (array) ($contract['zoom_levels'] ?? []), true), 'Four-hundred-percent zoom evidence is required.');
$check(in_array('screen-reader', (array) ($contract['input_modes'] ?? []), true), 'Screen-reader evidence is required.');

$empty_errors = Visual_Acceptance::validate_evidence([]);
$check(count($empty_errors) > 20, 'Empty evidence must fail the complete visual acceptance matrix.');

$complete = [
    'surfaces' => [],
    'viewports' => [],
    'directions' => [],
    'color_modes' => [],
    'motion_modes' => [],
    'zoom_levels' => [],
    'input_modes' => [],
    'staging_environment' => 'staging-build-1',
    'founder_signoff' => 'approved-evidence-reference',
];
foreach ((array) $contract['required_surfaces'] as $surface) {
    $complete['surfaces'][$surface] = ['evidence' => 'local-reference'];
}
foreach (array_keys((array) $contract['viewports']) as $viewport) {
    $complete['viewports'][$viewport] = true;
}
foreach (['directions', 'color_modes', 'motion_modes', 'input_modes'] as $group) {
    foreach ((array) $contract[$group] as $value) {
        $complete[$group][$value] = true;
    }
}
foreach ((array) $contract['zoom_levels'] as $zoom) {
    $complete['zoom_levels'][(string) $zoom] = true;
}
$check(Visual_Acceptance::validate_evidence($complete) === [], 'A complete bounded evidence manifest must validate.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 visual acceptance Definition-of-Done contract\n";
