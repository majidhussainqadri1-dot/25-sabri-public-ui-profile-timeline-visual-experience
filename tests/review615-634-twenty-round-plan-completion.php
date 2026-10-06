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
    $ledger = json_decode($read('config/review615-634-twenty-round-plan-completion-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Reviews 615-634 ledger invalid: ' . $exception->getMessage();
}
$check(($ledger['review_range']['first'] ?? null) === 615 && ($ledger['review_range']['last'] ?? null) === 634 && ($ledger['review_range']['count'] ?? null) === 20, 'Reviews 615-634 range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === [616, 628, 630, 631, 632, 634], 'Reviews 615-634 defect history mismatch.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (is_array($row) && is_int($row['review'] ?? null)) {
        $numbers[] = $row['review'];
    }
}
$check($numbers === range(615, 634), 'Reviews 615-634 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth promoted by source review: ' . $gate);
}

$bootstrap = $read('sabri-public-experience.php');
$check(str_contains($bootstrap, '* Version:     0.16.0') && str_contains($bootstrap, "SABRI_PUBLIC_EXPERIENCE_VERSION', '0.16.0'"), 'File 25 runtime must be 0.16.0.');
$check(str_contains($bootstrap, "'includes/class-observability.php'"), 'Observability source must load before runtime use.');

$providerContract = $read('includes/contracts/interface-timeline-provider.php');
$registry = $read('includes/class-timeline-registry.php');
$component = $read('includes/class-component-api.php');
foreach (['normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events'] as $method) {
    $check(str_contains($providerContract, 'function ' . $method), 'Provider plan default missing: ' . $method);
    $check(str_contains($registry, "'" . $method . "'"), 'Provider registration enforcement missing: ' . $method);
}
$check(str_contains($component, 'Timeline_Registry::meets_plan_contract'), 'Component API must reject incomplete timeline providers.');
$check(str_contains($component, "'timeline_provider_required_methods'"), 'Component contract must publish required provider methods.');

$plan = $read('includes/class-plan-completion.php');
$renderer = $read('includes/class-profile-renderer.php');
$template = $read('templates/public-profile.php');
$check(str_contains($plan, "'timeline_page_size' => ['type' => 'int', 'default' => 20, 'minimum' => 5, 'maximum' => 50]"), 'Governed timeline page-size preference missing.');
$check(str_contains($renderer, "'per_page' => \$timeline_page_size"), 'Renderer must consume governed timeline page size.');
$check(str_contains($plan, "\$has_clinic_action = \$is_doctor || \$profile_class === 'founder';"), 'Founder native appointment/clinic action gate missing.');
$check(str_contains($plan, "'preview_urls' => \$preview_urls"), 'Owner preview URLs missing from completion assistant.');
foreach (['public','member','mobile','desktop','contact','search','social'] as $mode) {
    $check(str_contains($template, "'" . $mode . "' =>"), 'Rendered owner preview mode missing: ' . $mode);
}
$check(str_contains($template, 'data-spux-preview-mode='), 'Preview mode must be exposed only as presentation context.');

$observability = $read('includes/class-observability.php');
foreach (['provider_unavailable','normalization_failure','duplicate_projection','stale_index','broken_canonical_url','privacy_field_rejection','cache_mismatch','visual_component_failure','slow_query','rest_authorization_rejection','rebuild_failure'] as $event) {
    $check(str_contains($observability, "'" . $event . "'"), 'Required diagnostic event missing: ' . $event);
}
$check(str_contains($observability, 'contains_sensitive_data') && str_contains($observability, 'spcrc/request_security_state'), 'Diagnostics must remain privacy-minimized and use the reviewed File 24 advisory bridge.');

try {
    $deps = json_decode($read('config/staging-dependencies.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $deps = [];
    $failures[] = 'Dependency matrix invalid: ' . $exception->getMessage();
}
$check(($deps['runtime_version'] ?? null) === '0.16.0', 'Dependency matrix runtime must be 0.16.0.');
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['09'] ?? null) === '9639f75ba046ac1a36e39d5e9aae56c7bae3279b', 'Current File 09 repository head observation is stale.');
$check(($heads['23'] ?? null) === 'dcae138e6073f4d0ff596623deb05b9940b8271b', 'Current File 23 repository head observation is stale.');
$check(($heads['evidence_class'] ?? null) === 'repository-source-only-not-staging-live', 'Repository evidence must not imply staging/live truth.');

try {
    $matrix = json_decode($read('config/source-completion-matrix.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $matrix = [];
    $failures[] = 'Source completion matrix invalid: ' . $exception->getMessage();
}
$check(($matrix['module']['runtime'] ?? null) === '0.16.0', 'Source completion runtime must be 0.16.0.');
$check(($matrix['declaration']['latest_review_range'] ?? null) === '615-634', 'Source completion latest review range must be Reviews 615-634.');
$check(($matrix['declaration']['review_ledger'] ?? null) === 'config/review615-634-twenty-round-plan-completion-ledger.json', 'Source completion ledger pointer is stale.');
foreach (['hostinger_staging_accepted','founder_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($matrix['declaration'][$gate] ?? null) === false, 'Source matrix promoted external truth: ' . $gate);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 615-634\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 615-634 — twenty-round plan/cross-file completion corrections preserved\n";
