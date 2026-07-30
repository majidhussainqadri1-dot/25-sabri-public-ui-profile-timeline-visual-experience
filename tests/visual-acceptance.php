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
$check(($contract['contract_version'] ?? '') === '1.1.0', 'Visual acceptance contract version must be current.');
$check(($contract['green_ci_is_acceptance'] ?? true) === false, 'Green CI must not be treated as visual acceptance.');
$check(($contract['staging_required'] ?? false) === true, 'Staging evidence must remain mandatory.');
$check(($contract['founder_signoff_required'] ?? false) === true, 'Founder sign-off must remain mandatory.');
$check(count((array) ($contract['viewports'] ?? [])) === 6, 'Six canonical viewport classes are required.');
$check(in_array('media-section', (array) ($contract['required_surfaces'] ?? []), true), 'Media-section evidence is required.');
$check(in_array('rtl', (array) ($contract['directions'] ?? []), true), 'RTL evidence is required.');
$check(in_array('forced-colors', (array) ($contract['color_modes'] ?? []), true), 'Forced-colors evidence is required.');
$check(in_array(400, (array) ($contract['zoom_levels'] ?? []), true), 'Four-hundred-percent zoom evidence is required.');
$check(in_array('screen-reader', (array) ($contract['input_modes'] ?? []), true), 'Screen-reader evidence is required.');

$empty_errors = Visual_Acceptance::validate_evidence([]);
$check(count($empty_errors) >= 9, 'Empty evidence must fail every required evidence group.');

$record = static function (string $key): array {
    return [
        'status' => 'pass',
        'artifact_ref' => 'artifacts/visual/' . $key . '.png',
        'sha256' => str_repeat('a', 64),
        'recorded_at' => '2026-07-31T00:00:00Z',
        'reviewer' => 'QA Reviewer',
    ];
};

$complete = [
    'surfaces' => [],
    'viewports' => [],
    'directions' => [],
    'color_modes' => [],
    'motion_modes' => [],
    'zoom_levels' => [],
    'input_modes' => [],
    'staging_environment' => [
        'environment' => 'staging',
        'site_url' => 'https://staging.example.test/',
        'commit_sha' => str_repeat('b', 40),
        'wordpress_version' => '7.0.1',
        'php_version' => '8.3.30',
        'recorded_at' => '2026-07-31T00:00:00Z',
    ],
    'founder_signoff' => [
        'status' => 'accepted',
        'signer' => 'Founder',
        'commit_sha' => str_repeat('b', 40),
        'evidence_ref' => 'artifacts/signoff/founder.json',
        'recorded_at' => '2026-07-31T00:00:00Z',
    ],
];
foreach ((array) $contract['required_surfaces'] as $surface) {
    $complete['surfaces'][$surface] = $record('surface-' . $surface);
}
foreach (array_keys((array) $contract['viewports']) as $viewport) {
    $complete['viewports'][$viewport] = $record('viewport-' . $viewport);
}
foreach (['directions', 'color_modes', 'motion_modes', 'input_modes'] as $group) {
    foreach ((array) $contract[$group] as $value) {
        $complete[$group][$value] = $record($group . '-' . $value);
    }
}
foreach ((array) $contract['zoom_levels'] as $zoom) {
    $complete['zoom_levels'][(string) $zoom] = $record('zoom-' . $zoom);
}

$check(Visual_Acceptance::validate_evidence($complete) === [], 'A complete cryptographically referenced evidence manifest must validate.');
$summary = Visual_Acceptance::summarize($complete);
$check(($summary['accepted'] ?? false) === true && ($summary['error_count'] ?? 1) === 0, 'Complete evidence summary must be accepted.');

$forged = $complete;
$forged['viewports']['mobile-small'] = true;
$forged['founder_signoff']['commit_sha'] = 'not-a-sha';
$forged_errors = Visual_Acceptance::validate_evidence($forged);
$check($forged_errors !== [], 'Boolean placeholders and malformed sign-off evidence must be rejected.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 strict visual acceptance evidence contract\n";
