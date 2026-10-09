<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};

$read = static function (string $path) use ($root, &$failures): string {
    $value = @file_get_contents($root . '/' . $path);
    if (! is_string($value)) {
        $failures[] = 'Unreadable evidence: ' . $path;
        return '';
    }
    return $value;
};

try {
    $ledger = json_decode($read('config/review655-674-admin-plan-completeness-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Reviews 655-674 ledger invalid: ' . $exception->getMessage();
}

$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Fresh review ledger identity mismatch.');
$check(($ledger['review_range']['first'] ?? null) === 655 && ($ledger['review_range']['last'] ?? null) === 674 && ($ledger['review_range']['count'] ?? null) === 20, 'Fresh twenty-round range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === [657, 658, 659, 660, 662, 673], 'Fresh defect rounds mismatch.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) {
        $failures[] = 'Malformed Reviews 655-674 row.';
        continue;
    }
    $numbers[] = $row['review'];
}
$check($numbers === range(655, 674), 'Reviews 655-674 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth promoted in fresh ledger: ' . $gate);
}

$plan = $read('includes/class-plan-completion.php');
foreach (['timeline_provider_mode','accessibility_mode','cache_ttl','safe_mode_controls','diagnostics_enabled'] as $dead) {
    $check(! str_contains($plan, "'{$dead}' =>"), 'Dead/no-op preference remains in PREFERENCE_SCHEMA: ' . $dead);
}
$check(str_contains($plan, "'responsive_preview' => ['type' => 'enum', 'default' => 'desktop', 'allowed' => ['mobile', 'desktop']]"), 'Responsive preview must expose only implemented mobile/desktop values.');
$check(str_contains($plan, "'default_timeline_filter' => ['type' => 'key'"), 'Bounded default_timeline_filter preference missing.');
$check(str_contains($plan, "'enabled_optional_providers' => ['type' => 'list'") && str_contains($plan, "'allow_empty' => true"), 'Optional provider allowlist/disable-all setting missing.');
$check(str_contains($plan, 'public static function optional_provider_enabled(string $provider_id): bool'), 'Optional provider runtime gate missing.');
foreach (['Public Experience Overview','Profile Templates','Timeline Providers','Content Cards','Public Visibility','Responsive Preview','Accessibility','SEO Presentation','Cache and Index','Adapter Health','Migration','System Check','Repair','Safe Mode','Diagnostics'] as $section) {
    $check(str_contains($plan, $section), 'Governed File 25 admin section missing: ' . $section);
}
$check(str_contains($plan, 'Accessibility') && str_contains($plan, 'cannot be disabled by a preference'), 'Mandatory accessibility law not stated in admin source.');
$check(str_contains($plan, 'profile responses remain no-store') || str_contains($plan, 'Public profile responses remain no-store'), 'No-store cache policy boundary missing from admin source.');

$plugin = $read('includes/class-plugin.php');
foreach (['file-06-knowledge','file-10-video-media','file-11-reels-media','file-12-pdf-media','file-18-marketplace'] as $provider) {
    $check(str_contains($plugin, "Plan_Completion::optional_provider_enabled('{$provider}')"), 'Optional provider runtime gate missing: ' . $provider);
}

$renderer = $read('includes/class-profile-renderer.php');
$check(str_contains($renderer, "Plan_Completion::preferences()['default_timeline_filter']"), 'Renderer does not consume default_timeline_filter.');
$check(str_contains($renderer, "array_key_exists(\$default, \$filters)"), 'Default timeline filter must be validated against active filters.');
$check(! str_contains($renderer, "\$completion_profile['avatar_alt'] = (string)"), 'Completion Assistant alt evidence is still being fabricated.');
$check(str_contains($renderer, "\$avatar_alt = trim((string) (\$profile['avatar_alt'] ?? ''))"), 'OpenGraph/public rendering canonical-alt fallback path missing.');

try {
    $staging = json_decode($read('config/staging-test-plan.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $staging = [];
    $failures[] = 'Staging plan invalid: ' . $exception->getMessage();
}
$scenarioIds = [];
foreach ((array) ($staging['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario) && is_string($scenario['id'] ?? null)) { $scenarioIds[] = $scenario['id']; }
}
foreach (['admin-config-observable-controls','timeline-default-filter','optional-provider-enablement','completion-assistant-alt-evidence'] as $id) {
    $check(in_array($id, $scenarioIds, true), 'Fresh staging scenario missing: ' . $id);
}
$scenarioById = [];
foreach ((array) ($staging['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario) && is_string($scenario['id'] ?? null)) { $scenarioById[$scenario['id']] = (string) ($scenario['requirement'] ?? ''); }
}
$file09Scenario = $scenarioById['file09-doctor-decision'] ?? '';
$check(str_contains($file09Scenario, '448d41f34586369ca5875693583b9cd8a6133167') || str_contains($file09Scenario, 'cfc5f781a766330314dc98c42abeca0eb7786eba'), 'File 09 staging scenario must preserve or advance beyond the Reviews 655-674 baseline.');
$file14Scenario = $scenarioById['file14-visual-consumer'] ?? '';
$check(str_contains($file14Scenario, 'f64e7d17268daff4e3097c18ad510116e6eaf105') || str_contains($file14Scenario, '080e2198d84dfb7491bb0b75946e14a5fe118b91'), 'File 14 staging scenario must preserve or advance beyond the Reviews 655-674 baseline.');
$check(str_contains($scenarioById['file20-current-shell-contract'] ?? '', '8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca'), 'File 20 current staging scenario is stale.');
$check(str_contains($scenarioById['file21-current-profile-timeline-contract'] ?? '', 'f2eb7e95ddea327af36ea725ffb923b029f885e6'), 'File 21 current staging scenario is stale.');
$check(str_contains($scenarioById['file22-create-edit-contract'] ?? '', 'b7a7f2e69411cbd32f0574fd12d766fb70c01b7a'), 'File 22 current staging scenario is stale.');
$check(str_contains($scenarioById['file24-current-assurance-contract'] ?? '', 'a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb'), 'File 24 current staging scenario is stale.');

try {
    $matrix = json_decode($read('config/source-completion-matrix.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $matrix = [];
    $failures[] = 'Source completion matrix invalid: ' . $exception->getMessage();
}
$latestRange = (string) ($matrix['declaration']['latest_review_range'] ?? '');
$check(in_array($latestRange, ['655-674', '675-694'], true), 'Source completion matrix must preserve or advance beyond Reviews 655-674.');
$check(file_exists($root . '/config/review655-674-admin-plan-completeness-ledger.json'), 'Historical Reviews 655-674 ledger must remain preserved.');
$check(($matrix['declaration']['known_unresolved_source_defects'] ?? null) === 0, 'Known File 25 source defects must be zero after corrections.');
$check(($matrix['declaration']['exact_head_ci'] ?? '') === 'external-evidence-required-for-current-head-not-frozen-in-source', 'Mutable exact-head CI truth must remain external.');
foreach (['hostinger_staging_accepted','founder_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($matrix['declaration'][$gate] ?? null) === false, 'Source matrix promoted external gate: ' . $gate);
}

try {
    $deps = json_decode($read('config/staging-dependencies.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $deps = [];
    $failures[] = 'Dependency matrix invalid: ' . $exception->getMessage();
}
$audit = (array) ($deps['file25_review655_674_audit'] ?? []);
$check(($audit['baseline_main_sha'] ?? '') === '2d02c93356b050313e30e29aeceb57080771c2a5', 'Fresh audit baseline main SHA mismatch.');
$check(($audit['baseline_main_ci']['run_number'] ?? null) === 1638 && ($audit['baseline_main_ci']['conclusion'] ?? '') === 'success', 'Fresh audit baseline CI evidence mismatch.');
$check(($audit['review_range'] ?? '') === '655-674', 'Fresh dependency audit review range mismatch.');
$check(($audit['exact_current_head_ci'] ?? '') === 'external-evidence-required-not-frozen-in-source', 'Dependency matrix current-head CI boundary missing.');
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['evidence_class'] ?? '') === 'repository-source-only-not-staging-live', 'Repository heads must remain non-live evidence.');
$check(($audit['review_range'] ?? '') === '655-674', 'Historical Reviews 655-674 dependency audit must remain preserved.');

$readme = $read('README.md');
$check(str_contains($readme, 'Reviews 675–694') || str_contains($readme, 'Reviews 655–674'), 'README review lineage missing.');
$check(str_contains($readme, 'Known unresolved File 25 source defects after the recorded corrections: **0**'), 'README zero-known-source-defect statement missing.');
$check(str_contains($readme, 'Hostinger staging accepted: **No**') && str_contains($readme, 'Live deployment verified: **No**'), 'README external acceptance boundary missing.');

$wpReadme = $read('readme.txt');
$check(str_contains($wpReadme, 'Reviews 675–694') || str_contains($wpReadme, 'Reviews 655–674'), 'WordPress readme review marker missing.');

$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review655-674-admin-plan-completeness.php'), 'Fresh audit test is not governed.');

foreach ([
    'config/review655-674-admin-plan-completeness-ledger.json',
    'docs/REVIEWS-655-674-ADMIN-PLAN-COMPLETENESS-2026-10-07.md',
] as $evidence) {
    $check(file_exists($root . '/' . $evidence), 'Fresh audit evidence missing: ' . $evidence);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 655-674\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 655-674 admin/plan/cross-file completeness corrections preserved\n";
