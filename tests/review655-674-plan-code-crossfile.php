<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
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
    $ledger = json_decode($read('config/review655-674-plan-code-crossfile-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Reviews 655-674 ledger invalid: ' . $exception->getMessage();
}
$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Reviews 655-674 ledger identity mismatch.');
$check(($ledger['review_range'] ?? null) === ['first' => 655, 'last' => 674, 'count' => 20], 'Reviews 655-674 range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === range(657, 664), 'Reviews 655-674 defect rounds mismatch.');
$check(($ledger['clean_rounds'] ?? null) === [655,656,665,666,667,668,669,670,671,672,673,674], 'Reviews 655-674 clean rounds mismatch.');
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
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth promoted in Reviews 655-674: ' . $gate);
}

$template = $read('templates/public-profile.php');
$check(! str_contains($template, 'SabriPublicExperiencePublic_URL'), 'Broken preview Public_URL class reference remains.');
$check(substr_count($template, '\\Sabri\\PublicExperience\\Public_URL::sanitize_same_site') >= 3, 'Canonical namespaced preview Public_URL calls missing.');
$check(str_contains($template, '$preview_mode_labels = ['), 'Translation-ready preview labels missing.');
$check(str_contains($template, 'data-spux-connection-status'), 'Weak-connection public status surface missing.');

$v1 = $read('includes/contracts/interface-timeline-provider.php');
$v2 = $read('includes/contracts/interface-timeline-provider-v2.php');
$check(str_contains($v2, 'interface Timeline_Provider_V2 extends Timeline_Provider'), 'Backwards-compatible Timeline Provider v2 contract missing.');
foreach (['normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events'] as $method) {
    $check(str_contains($v2, 'function ' . $method . '('), 'Timeline Provider v2 method missing: ' . $method);
}
$check(! str_contains($v1, 'function normalize_public_item('), 'Stable Timeline Provider v1 was broken instead of extended.');

foreach ([
    'includes/providers/class-file-21-provider.php',
    'includes/providers/class-wordpress-posts-provider.php',
] as $providerPath) {
    $provider = $read($providerPath);
    $check(str_contains($provider, 'implements Timeline_Provider_V2'), 'Built-in provider does not implement v2: ' . $providerPath);
    foreach (['normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events'] as $method) {
        $check(str_contains($provider, 'function ' . $method . '('), 'Built-in v2 provider method missing: ' . $providerPath . '::' . $method);
    }
}

$registry = $read('includes/class-timeline-registry.php');
$check(str_contains($registry, '$provider instanceof Timeline_Provider_V2'), 'Timeline registry does not capability-gate v2 sync behavior.');
$check(str_contains($registry, '$provider->register_sync_events();'), 'Timeline registry v2 sync registration missing.');

$service = $read('includes/class-timeline-service.php');
foreach (["['topic']", "['year']", "['language']", "['provider']", "['sort']", "['review_state']", "['source_state']"] as $marker) {
    $check(str_contains($service, $marker), 'Timeline secondary filter plumbing missing: ' . $marker);
}
foreach (['most-viewed','most-saved','latest','oldest'] as $sort) {
    $check(str_contains($service, $sort), 'Timeline sort missing: ' . $sort);
}
$check(str_contains($service, '$provider instanceof Timeline_Provider_V2'), 'Metric sort must require v2 provider capability.');
$check(str_contains($service, '$provider->get_public_metrics($item)'), 'Metric-aware sorting does not consume provider metrics.');

$renderer = $read('includes/class-profile-renderer.php');
foreach (['Articles','Knowledge','Lessons','Research','Successful cases','Videos','Reels','PDFs','Clinic Updates','Corrections'] as $label) {
    $check(str_contains($renderer, "__('" . $label . "'"), 'Governed primary timeline filter missing: ' . $label);
}
$check(str_contains($renderer, 'requested_timeline_secondary_filters'), 'Renderer secondary-filter parser missing.');

$timeline = $read('templates/partials/timeline.php');
foreach (['data-spux-load-more','Timeline pagination fallback','aria-live="polite"','Apply filters','Reset filters'] as $marker) {
    $check(str_contains($timeline, $marker), 'Accessible timeline control missing: ' . $marker);
}
$check(str_contains($timeline, "in_array('most-viewed', \$available_sorts, true)"), 'Most-viewed control is not capability-gated.');
$check(str_contains($timeline, "in_array('most-saved', \$available_sorts, true)"), 'Most-saved control is not capability-gated.');

$plan = $read('includes/class-plan-completion.php');
foreach ([
    "INDEX_PAUSE_OPTION",
    "/admin/rebuild-index/pause",
    "/admin/rebuild-index/resume",
    "handle_pause_index",
    "handle_resume_index",
    "index_rebuild_paused",
    "set_index_pause",
] as $marker) {
    $check(str_contains($plan, $marker), 'Index pause/resume contract missing: ' . $marker);
}
$check(str_contains($plan, 'preference_label(') && str_contains($plan, 'preference_option_label('), 'Translation-ready admin preference labels missing.');
$check(! str_contains($plan, "ucwords(str_replace('_', ' ', \$key))"), 'Raw internal preference keys still rendered as labels.');

$assets = $read('includes/class-assets.php');
$js = $read('assets/js/public.js');
foreach (['offline','onlineRestored'] as $marker) {
    $check(str_contains($assets, "'" . $marker . "'"), 'Localized connection message missing: ' . $marker);
}
foreach (["addEventListener('offline'", "addEventListener('online'", 'navigator.onLine'] as $marker) {
    $check(str_contains($js, $marker), 'Weak-connection JS behavior missing: ' . $marker);
}

try {
    $deps = json_decode($read('config/staging-dependencies.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $deps = [];
    $failures[] = 'Dependency matrix invalid: ' . $exception->getMessage();
}
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['09'] ?? '') === '448d41f34586369ca5875693583b9cd8a6133167', 'Current File 09 observed head mismatch.');
$check(($heads['14'] ?? '') === '55e44d38b23304d50fad22b4d3a2c67fe4721209', 'Current File 14 observed head mismatch.');
$check(($heads['25'] ?? '') === '2d02c93356b050313e30e29aeceb57080771c2a5', 'Reviews 655-674 baseline File 25 head mismatch.');
$check(($heads['evidence_class'] ?? '') === 'repository-source-only-not-staging-live', 'Repository heads must not imply staging/live truth.');
$audit = (array) ($deps['file25_review655_674_audit'] ?? []);
$check(($audit['review_range'] ?? '') === '655-674', 'Dependency matrix fresh review range missing.');
$check(($audit['baseline_main_ci']['run_number'] ?? null) === 1638 && ($audit['baseline_main_ci']['conclusion'] ?? '') === 'success', 'Baseline CI 1638 evidence missing.');

$staging = json_decode($read('config/staging-test-plan.json'), true);
$scenarioText = is_array($staging) ? json_encode($staging, JSON_UNESCAPED_SLASHES) : '';
$check(is_string($scenarioText) && str_contains($scenarioText, '448d41f34586369ca5875693583b9cd8a6133167'), 'File 09 current staging observation missing.');
$check(is_string($scenarioText) && str_contains($scenarioText, '55e44d38b23304d50fad22b4d3a2c67fe4721209'), 'File 14 current staging observation missing.');

foreach ([
    'config/review655-674-plan-code-crossfile-ledger.json',
    'docs/REVIEWS-655-674-PLAN-CODE-CROSSFILE-2026-10-07.md',
] as $evidence) {
    $check(is_file($root . '/' . $evidence), 'Reviews 655-674 evidence missing: ' . $evidence);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 655-674\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 655-674 plan/code/cross-file completeness corrections preserved\n";
