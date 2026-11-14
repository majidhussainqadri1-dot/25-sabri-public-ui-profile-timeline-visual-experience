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
$check(($contract['contract_version'] ?? '') === '1.6.0', 'Visual acceptance contract must include V12.3 six-scene evidence obligations.');
$check(($contract['green_ci_is_acceptance'] ?? true) === false, 'Green CI must not be treated as visual acceptance.');
$check(($contract['source_contract_is_acceptance'] ?? true) === false, 'Source contracts must not be treated as visual acceptance.');
$check(($contract['target_commit_sha_required'] ?? false) === true, 'All evidence must be bound to one target commit.');
$check(($contract['artifact_root'] ?? '') === 'artifacts/', 'Evidence references must use the governed artifacts root.');
$check(in_array('byte_size', (array) ($contract['evidence_record_required_fields'] ?? []), true), 'Artifact byte size must be mandatory.');
$check(in_array('media_type', (array) ($contract['evidence_record_required_fields'] ?? []), true), 'Artifact media type must be mandatory.');
$check(($contract['staging_required'] ?? false) === true, 'Staging evidence must remain mandatory.');
$check(($contract['founder_signoff_required'] ?? false) === true, 'Founder sign-off must remain mandatory.');
$check(($contract['shell_screenshot_regression_required'] ?? false) === true, 'File 20 shell screenshot regression must remain mandatory.');
$check(($contract['slow_network_evidence_required'] ?? false) === true, 'Slow-network evidence must remain mandatory.');
$check(count((array) ($contract['viewports'] ?? [])) === 6, 'Six canonical viewport classes are required.');
$check(in_array('file20-shell-integration', (array) ($contract['required_surfaces'] ?? []), true), 'File 20 shell integration evidence is required.');
$check(in_array('profile-hero-actions', (array) ($contract['required_surfaces'] ?? []), true), 'Profile hero/action evidence is required.');
$check(in_array('qr-share-state', (array) ($contract['required_surfaces'] ?? []), true), 'QR/share state evidence is required.');
$check(in_array('media-section', (array) ($contract['required_surfaces'] ?? []), true), 'Media-section evidence is required.');
$check(in_array('marketplace-section', (array) ($contract['required_surfaces'] ?? []), true), 'Marketplace-section evidence is required.');
foreach (['filtered-empty-state', 'permission-denied-state', 'offline-state'] as $requiredV12State) {
    $check(in_array($requiredV12State, (array) ($contract['required_surfaces'] ?? []), true), 'V12-012 visual evidence is mandatory: ' . $requiredV12State);
}
$scene_owners = [
    'scene-home-first-view' => ['file-20', 'file-21', 'file-25'],
    'scene-search-results' => ['file-20', 'file-26', 'file-25'],
    'scene-settings-preferences' => ['file-20', 'file-19', 'file-25'],
    'scene-doctor-booking' => ['file-03', 'file-08', 'file-09', 'file-25'],
    'scene-book-lesson-reader' => ['file-05', 'file-12', 'file-25'],
    'scene-publication-composer' => ['file-21', 'file-22', 'file-25'],
];
foreach ($scene_owners as $scene => $owners) {
    $check(in_array($scene, (array)($contract['required_surfaces'] ?? []), true), 'Missing V12.3 mandatory visual scene: ' . $scene);
    $check(($contract['v12_3_scene_owners'][$scene] ?? []) === $owners, 'Visual ownership must remain distinct: ' . $scene);
}
$check(($contract['v12_3_scenes_have_real_artifact_evidence'] ?? true) === false, 'Scene declarations must never assert collected visual artifacts.');
$check(($contract['v12_3_approval_requires_independent_file24'] ?? false) === true, 'V12.3 visual release must require independent File24 review.');
$check(in_array('rtl', (array) ($contract['directions'] ?? []), true), 'RTL evidence is required.');
$check(in_array('forced-colors', (array) ($contract['color_modes'] ?? []), true), 'Forced-colors evidence is required.');
$check(in_array(400, (array) ($contract['zoom_levels'] ?? []), true), 'Four-hundred-percent zoom evidence is required.');
$check(in_array('screen-reader', (array) ($contract['input_modes'] ?? []), true), 'Screen-reader evidence is required.');
foreach (['AJ-04', 'AJ-31', 'AJ-32', 'AJ-33', 'AJ-39', 'AJ-40'] as $journey) {
    $check(in_array($journey, (array) ($contract['governing_acceptance_journeys'] ?? []), true), 'Missing governing acceptance journey: ' . $journey);
}
foreach (['F25-CEN-01', 'F25-CEN-02'] as $requirementId) {
    $check(in_array($requirementId, (array) ($contract['file_specific_requirements'] ?? []), true), 'Missing File 25 governing requirement: ' . $requirementId);
}

$empty_errors = Visual_Acceptance::validate_evidence([]);
$check(count($empty_errors) >= 10, 'Empty evidence must fail every required evidence group and target commit.');

$target = str_repeat('b', 40);
$record = static function (string $key) use ($target): array {
    return [
        'status' => 'pass',
        'artifact_ref' => 'artifacts/visual/' . $key . '.png',
        'sha256' => str_repeat('a', 64),
        'byte_size' => 4096,
        'media_type' => 'image/png',
        'commit_sha' => $target,
        'recorded_at' => '2026-07-31T00:00:00Z',
        'reviewer' => 'QA Reviewer',
    ];
};

$complete = [
    'target_commit_sha' => $target,
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
        'commit_sha' => $target,
        'wordpress_version' => '7.0.1',
        'php_version' => '8.3.30',
        'recorded_at' => '2026-07-31T00:00:00Z',
    ],
    'founder_signoff' => [
        'status' => 'accepted',
        'signer' => 'Founder',
        'commit_sha' => $target,
        'evidence_ref' => 'artifacts/signoff/founder.json',
        'evidence_sha256' => str_repeat('c', 64),
        'evidence_byte_size' => 2048,
        'evidence_media_type' => 'application/json',
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

$check(Visual_Acceptance::validate_evidence($complete) === [], 'A complete commit-bound evidence manifest must validate.');
$missing_scene = $complete;
unset($missing_scene['surfaces']['scene-settings-preferences']);
$check(Visual_Acceptance::validate_evidence($missing_scene) !== [], 'V12.3 six-scene acceptance must fail if even one scene is missing.');
$missingV12 = $complete;
unset($missingV12['surfaces']['permission-denied-state']);
$check(Visual_Acceptance::validate_evidence($missingV12) !== [], 'A staging manifest missing permission-denied evidence must not be accepted.');
$summary = Visual_Acceptance::summarize($complete);
$check(($summary['accepted'] ?? false) === true && ($summary['error_count'] ?? 1) === 0, 'Complete evidence summary must be accepted.');

// V12.1 chronology: an older approval cannot authorize a later test.
// Both timestamps are valid and in the past; it is the order that fails.
$chronologyReference = gmdate('Y-m-d\\TH:i:s\\Z', time() - 86400);
$chronologyLater = gmdate('Y-m-d\\TH:i:s\\Z', time() - 3600);
$chronology = $complete;
$chronology['founder_signoff']['recorded_at'] = $chronologyReference;
$chronology['surfaces']['founder-overview']['recorded_at'] = $chronologyLater;
$check(Visual_Acceptance::validate_evidence($chronology) !== [],
    'A Founder sign-off from yesterday cannot approve a screenshot recorded today.');
$chronologyStaging = $complete;
$chronologyStaging['founder_signoff']['recorded_at'] = $chronologyReference;
$chronologyStaging['staging_environment']['recorded_at'] = $chronologyLater;
$check(Visual_Acceptance::validate_evidence($chronologyStaging) !== [],
    'A Founder sign-off cannot precede the recorded staging evidence.');
$chronologyWithinSkew = $complete;
$chronologyWithinSkew['founder_signoff']['recorded_at'] = $chronologyReference;
$chronologyWithinSkew['surfaces']['founder-overview']['recorded_at'] =
    gmdate('Y-m-d\\TH:i:s\\Z', strtotime($chronologyReference) + 120);
$check(Visual_Acceptance::validate_evidence($chronologyWithinSkew) === [],
    'A bounded two-minute clock offset may not falsely reject valid evidence metadata.');

$forged = $complete;
$forged['viewports']['mobile-small'] = true;
$forged['founder_signoff']['commit_sha'] = str_repeat('d', 40);
$forged['surfaces']['marketplace-section']['recorded_at'] = '2026-02-30T25:61:61Z';
$forged['staging_environment']['site_url'] = 'https://staging.example.test/?preview=1';
$forged_errors = Visual_Acceptance::validate_evidence($forged);
$check($forged_errors !== [], 'Placeholders, commit mismatch, normalized dates, and noncanonical staging URLs must be rejected.');

// Source-level proof that an impossible future record cannot pass the
// metadata acceptance gate. This is not screenshot artifact-byte validation.
$futureTime = gmdate('Y-m-d\\TH:i:s\\Z', time() + 86400);
$futureArtifact = $complete;
$futureArtifact['surfaces']['founder-overview']['recorded_at'] = $futureTime;
$check(Visual_Acceptance::validate_evidence($futureArtifact) !== [],
    'Future-dated screenshot metadata cannot count as a completed test.');
$futureStaging = $complete;
$futureStaging['staging_environment']['recorded_at'] = $futureTime;
$check(Visual_Acceptance::validate_evidence($futureStaging) !== [],
    'Future staging evidence cannot be accepted as performed.');
$futureSignoff = $complete;
$futureSignoff['founder_signoff']['recorded_at'] = $futureTime;
$check(Visual_Acceptance::validate_evidence($futureSignoff) !== [],
    'Future Founder signoff cannot masquerade as an existing approval.');

$mixed_commit = $complete;
$mixed_commit['surfaces']['founder-overview']['commit_sha'] = str_repeat('e', 40);
$check(Visual_Acceptance::validate_evidence($mixed_commit) !== [], 'Evidence from another commit may not be mixed into the target manifest.');

$external_ref = $complete;
$external_ref['surfaces']['founder-overview']['artifact_ref'] = 'https://evil.example/evidence.png';
$check(Visual_Acceptance::validate_evidence($external_ref) !== [], 'External artifact references must be rejected.');

$traversal_ref = $complete;
$traversal_ref['surfaces']['founder-overview']['artifact_ref'] = 'artifacts/../private/evidence.png';
$check(Visual_Acceptance::validate_evidence($traversal_ref) !== [], 'Artifact path traversal must be rejected.');

$missing_metadata = $complete;
unset($missing_metadata['surfaces']['founder-overview']['byte_size'], $missing_metadata['surfaces']['founder-overview']['media_type']);
$check(Visual_Acceptance::validate_evidence($missing_metadata) !== [], 'Evidence without byte size and media type must be rejected.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 newest commit-bound artifact-integrity visual acceptance contract\n";
