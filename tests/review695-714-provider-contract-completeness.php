<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};
$read = static function (string $path) use ($root, &$failures): string {
    $value = @file_get_contents($root . '/' . $path);
    if (! is_string($value)) { $failures[] = 'Unreadable evidence: ' . $path; return ''; }
    return $value;
};

try {
    $ledger = json_decode($read('config/review695-714-provider-contract-completeness-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    $ledger = [];
    $failures[] = 'Reviews 695-714 ledger invalid: ' . $e->getMessage();
}
$check(($ledger['review_range'] ?? null) === ['first'=>695,'last'=>714,'count'=>20], 'Reviews 695-714 range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === range(697, 711), 'Reviews 695-714 defect rounds mismatch.');
$check(($ledger['clean_rounds'] ?? null) === [695,696,712,713,714], 'Reviews 695-714 clean rounds mismatch.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (is_array($row) && is_int($row['review'] ?? null)) { $numbers[] = $row['review']; }
}
$check($numbers === range(695,714), 'Reviews 695-714 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External gate promoted: ' . $gate);
}

$contract = $read('includes/contracts/interface-timeline-provider.php');
foreach ([
    'get_provider_id','get_provider_version','is_available','get_maturity_level','get_public_author_items',
    'normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions',
    'get_public_metrics','get_correction_state','register_sync_events','get_health_status'
] as $method) {
    $check(str_contains($contract, 'function ' . $method . '('), 'Timeline Provider contract method missing: ' . $method);
}

foreach (['includes/providers/class-file-21-provider.php','includes/providers/class-wordpress-posts-provider.php'] as $providerFile) {
    $source = $read($providerFile);
    foreach (['normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events'] as $method) {
        $check(str_contains($source, 'function ' . $method . '('), $providerFile . ' missing ' . $method);
    }
}
$file21 = $read('includes/providers/class-file-21-provider.php');
$check(str_contains($file21, 'return [];') && str_contains($file21, 'does not publish bounded'), 'File 21 provider must not fabricate metrics.');

$registry = $read('includes/class-timeline-registry.php');
$check(str_contains($registry, '$provider->register_sync_events();'), 'Registry must invoke provider sync registration.');

$service = $read('includes/class-timeline-service.php');
foreach (['most_viewed','most_saved','review_state','source_state','public_metric_scores','privacy_safe','available_metric_sorts'] as $marker) {
    $check(str_contains($service, $marker), 'Timeline service marker missing: ' . $marker);
}
$check(str_contains($service, "if (in_array(\$sort, ['most_viewed', 'most_saved'], true) && empty(\$metric_sorts[\$sort]))"), 'Metric sort must fall back if metrics are unavailable.');

$renderer = $read('includes/class-profile-renderer.php');
foreach (['timeline_review_state','timeline_source_state','timeline_metric_sorts',"['type', 'provider', 'year', 'language', 'topic', 'sort', 'review_state', 'source_state', 'profile_q']"] as $marker) {
    $check(str_contains($renderer, $marker), 'Renderer state/parity marker missing: ' . $marker);
}

$template = $read('templates/partials/timeline.php');
foreach (['Review state','Source state','Most viewed','Most saved','Load More','spux-pagination__next-fallback'] as $marker) {
    $check(str_contains($template, $marker), 'Timeline UI marker missing: ' . $marker);
}

$rest = $read('includes/class-rest-controller.php');
foreach (['most_viewed','most_saved',"'review_state' =>","'source_state' =>",'available_metric_sorts'] as $marker) {
    $check(str_contains($rest, $marker), 'REST parity marker missing: ' . $marker);
}

$deps = json_decode($read('config/staging-dependencies.json'), true);
$modules = [];
foreach ((array) ($deps['modules'] ?? []) as $module) {
    if (is_array($module) && isset($module['file'])) { $modules[(int) $module['file']] = $module; }
}
$check(($modules[7]['current_repository_head'] ?? '') === '2f4a89707724fd2b9946600afe10ddab27ec3c2d', 'File 07 current head mismatch.');
$check(($modules[7]['current_source_version_observed'] ?? '') === '1.2.1', 'File 07 current runtime mismatch.');
$check(($modules[7]['current_contract_version_observed'] ?? '') === '1.2.1', 'File 07 current contract mismatch.');
$check(($modules[7]['current_projection_schema_observed'] ?? null) === 3, 'File 07 current projection schema mismatch.');
$check(($deps['file25_review695_714_audit']['baseline_main_sha'] ?? '') === '3075224089506fed19af1441ebf3556c2d5230b5', 'New audit baseline main mismatch.');
$check(($deps['file25_review695_714_audit']['baseline_main_ci']['run_number'] ?? null) === 1784, 'New audit baseline CI mismatch.');

$plan = json_decode($read('config/staging-test-plan.json'), true);
$ids = [];
foreach ((array) ($plan['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario)) { $ids[] = (string) ($scenario['id'] ?? ''); }
}
foreach (['timeline-provider-plan-contract','timeline-metric-review-source-refinements'] as $id) {
    $check(in_array($id, $ids, true), 'New staging scenario missing: ' . $id);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 695-714\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 Reviews 695-714 provider-contract/plan/cross-file completeness corrections preserved\n";
