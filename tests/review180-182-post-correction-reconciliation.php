<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$plugin = file_get_contents($root . '/sabri-public-experience.php');
$central = file_get_contents($root . '/includes/class-central-plan-2026-corrections.php');
$stagingRaw = file_get_contents($root . '/config/staging-test-plan.json');
$completionRaw = file_get_contents($root . '/config/source-completion-matrix.json');

$check(is_string($plugin), 'Review 180: plugin bootstrap must be readable.');
$check(is_string($central), 'Review 180: latest correction layer must be readable.');
$check(is_string($stagingRaw), 'Review 181: staging plan must be readable.');
$check(is_string($completionRaw), 'Review 182: completion matrix must be readable.');

if (is_string($plugin)) {
    $check(str_contains($plugin, "'includes/class-central-plan-2026-corrections.php'"), 'Review 180: latest correction class must be loaded by the plugin.');
    $check(str_contains($plugin, 'Central_Plan_2026_Corrections::register();'), 'Review 180: latest correction class must be registered.');
    $check(str_contains($plugin, "apply_filters('sabri_visual_experience/contract', \$contract)"), 'Review 180: public visual contract must expose the final governed filter chain.');
}

if (is_string($central)) {
    $check(str_contains($central, '--sabri-visual-primary: #087A4E;'), 'Review 180: latest CSS layer must enforce #087A4E.');
    $check(! str_contains($central, '--sabri-visual-primary: #15803d;'), 'Review 180: stale #15803d must not be emitted as the latest primary token.');
    $check(str_contains($central, "'file_22_and_23_full_publishing_experience_traced' => true"), 'Review 181: File 22/23 full publishing-experience traceability must be explicit.');
    $check(str_contains($central, "'hostinger_staging_accepted' => false"), 'Review 182: source correction must not claim Hostinger acceptance.');
    $check(str_contains($central, "'live_deployed' => false"), 'Review 182: source correction must not claim live deployment.');
}

$staging = is_string($stagingRaw) ? json_decode($stagingRaw, true) : null;
$check(is_array($staging), 'Review 181: staging test plan must be valid JSON.');
$scenarioIds = [];
if (is_array($staging)) {
    foreach (($staging['scenarios'] ?? []) as $scenario) {
        if (is_array($scenario) && isset($scenario['id'])) {
            $scenarioIds[] = (string) $scenario['id'];
        }
    }
    $check(($staging['staging_acceptance_implied'] ?? true) === false, 'Review 182: staging plan must never imply acceptance.');
    $check(($staging['production_acceptance_implied'] ?? true) === false, 'Review 182: staging plan must never imply production acceptance.');
}
foreach (['file22-create-edit-contract', 'file23-private-management-contract', 'profile-owner-publishing-links', 'canonical-visual-token'] as $requiredScenario) {
    $check(in_array($requiredScenario, $scenarioIds, true), 'Review 181: missing staging scenario ' . $requiredScenario . '.');
}

$completion = is_string($completionRaw) ? json_decode($completionRaw, true) : null;
$check(is_array($completion), 'Review 182: source-completion matrix must remain valid JSON.');
if (is_array($completion)) {
    $declaration = (array) ($completion['declaration'] ?? []);
    foreach (['hostinger_staging_accepted', 'founder_acceptance', 'production_accepted', 'live_deployed', 'operational'] as $gate) {
        $check(($declaration[$gate] ?? null) === false, 'Review 182: external gate ' . $gate . ' must remain false.');
    }
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 180-182\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: Reviews 180-182 fresh post-correction reconciliation\n";
