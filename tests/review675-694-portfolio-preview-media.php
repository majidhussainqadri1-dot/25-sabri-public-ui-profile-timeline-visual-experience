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
    $ledger = json_decode($read('config/review675-694-portfolio-preview-media-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Reviews 675-694 ledger invalid: ' . $exception->getMessage();
}
$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Fresh review ledger identity mismatch.');
$check(($ledger['review_range']['first'] ?? null) === 675 && ($ledger['review_range']['last'] ?? null) === 694 && ($ledger['review_range']['count'] ?? null) === 20, 'Fresh twenty-round range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === [677,678,679,680,681,682,683,693], 'Fresh defect rounds mismatch.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (is_array($row) && is_int($row['review'] ?? null)) { $numbers[] = $row['review']; }
}
$check($numbers === range(675, 694), 'Reviews 675-694 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth promoted in fresh ledger: ' . $gate);
}

$native = $read('includes/class-native-integration.php');
$profile = $read('includes/class-profile-repository.php');
$plan = $read('includes/class-plan-completion.php');
$template = $read('templates/public-profile.php');
$hero = $read('templates/partials/profile-hero.php');
$css = $read('assets/css/public.css');

$check(str_contains($native, "'has_focal' => \$has_focal"), 'File 03 media adapter must preserve whether focal coordinates were actually supplied.');
$check(str_contains($profile, "'cover_focal_x'") && str_contains($profile, "'cover_focal_y'") && str_contains($profile, "'cover_has_focal'"), 'Profile projection must carry canonical cover focal metadata.');
$check(str_contains($plan, "($profile['cover_has_focal'] ?? false) === true") && str_contains($plan, "? 'owner'"), 'Owner focal coordinates must override the administrator fallback preset.');
$check(str_contains($template, '--spux-cover-focal-x') && str_contains($template, '--spux-cover-focal-y'), 'Public template must emit bounded focal CSS variables.');
$check(str_contains($css, 'object-position: var(--spux-cover-focal-x, 50%) var(--spux-cover-focal-y, 50%)'), 'Cover CSS must consume canonical focal variables.');

$check(str_contains($plan, "'rtl'") && str_contains($template, "$rtl_preview = $preview_mode === 'rtl'"), 'Owner View-as-Public must include an RTL mode.');
$check(str_contains($template, "' dir="rtl"'") && str_contains($css, '.spux-preview--rtl'), 'RTL preview must change presentation direction without changing data ownership.');

$check(str_contains($hero, 'wp_trim_words((string) $profile[\'bio\'], 28)'), 'Profile Hero must render a bounded brief introduction.');
$check(str_contains($hero, 'profile_status_banner') && str_contains($hero, 'retired') && str_contains($hero, 'legacy'), 'Profile Hero must expose a bounded truthful public status-banner surface.');

$extended = $read('includes/contracts/interface-timeline-provider-plan-contract.php');
foreach (['normalize_public_item','get_canonical_url','get_visibility_state','get_public_actions','get_public_metrics','get_correction_state','register_sync_events'] as $method) {
    $check(str_contains($extended, 'function ' . $method . '('), 'Complete File 25 §46 provider method missing: ' . $method);
}
foreach ([
    'includes/providers/class-file-21-provider.php',
    'includes/providers/class-wordpress-posts-provider.php',
    'includes/class-section-timeline-provider.php',
] as $path) {
    $source = $read($path);
    $check(str_contains($source, 'implements Timeline_Provider_Plan_Contract'), 'Built-in timeline provider does not implement complete plan contract: ' . $path);
}
$registry = $read('includes/class-timeline-registry.php');
$system = $read('includes/class-system-check.php');
$check(str_contains($registry, "'plan_contract' => $provider instanceof Timeline_Provider_Plan_Contract"), 'Timeline registry must record complete-contract adoption.');
$check(str_contains($system, 'compatibility-floor contract'), 'System Check must report legacy compatibility-floor timeline providers.');

$plugin = $read('includes/class-plugin.php');
foreach ([
    "'file-06-knowledge')",
    "'file-10-video-media')",
    "'file-11-reels-media')",
    "'file-12-pdf-media')",
] as $provider) {
    $check(str_contains($plugin, 'optional_provider_enabled(' . $provider), 'Optional portfolio provider enablement missing: ' . $provider);
}
foreach (["'knowledge'","'video'","'reel'","'pdf'"] as $type) {
    $check(str_contains($plugin, 'Section_Timeline_Provider($provider, $profiles, ' . $type), 'Portfolio timeline adapter missing for content type ' . $type);
}
foreach (['Knowledge','Videos','Reels','PDFs'] as $label) {
    $check(str_contains($plugin, "__('{$label}', 'sabri-public-experience')"), 'Dynamic timeline filter label missing: ' . $label);
}

foreach ([
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
] as $path) {
    $source = $read($path);
    $check(str_contains($source, 'private const MAX_ITEMS = 24;'), 'Normal profile-section 24-card limit must remain preserved: ' . $path);
    $check(str_contains($source, 'private const MAX_TIMELINE_ITEMS = 500;'), 'Bounded deeper timeline limit missing: ' . $path);
    $check(str_contains($source, "'native_object_id'"), 'Internal native identity needed for timeline deduplication missing: ' . $path);
}

$normalized = $read('includes/class-normalized-timeline-item.php');
$timeline = $read('includes/class-timeline-service.php');
foreach (['pin_reference','pin_actor','pin_reason','pin_surface','pin_start_at','pin_end_at'] as $field) {
    $check(str_contains($normalized, "'{$field}'"), 'Audited pin field missing: ' . $field);
}
$check(str_contains($normalized, 'Pinned timeline items require actor, reason, reference, surface, start, and end audit metadata.'), 'Unaudited non-zero timeline pin must fail closed.');
$check(str_contains($timeline, 'effective_pin_weight') && str_contains($timeline, '$now >= $start_at && $now <= $end_at'), 'Timeline pin must be effective only inside its active UTC window.');
$check(str_contains($normalized, "'pin_reference',") && str_contains($normalized, "'pin_end_at',"), 'Pin audit metadata must be redacted from public output.');

try {
    $deps = json_decode($read('config/staging-dependencies.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $deps = [];
    $failures[] = 'Dependency matrix invalid: ' . $exception->getMessage();
}
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['07'] ?? '') === '2f4a89707724fd2b9946600afe10ddab27ec3c2d', 'Current File 07 head drift.');
$check(($heads['09'] ?? '') === 'cfc5f781a766330314dc98c42abeca0eb7786eba', 'Current File 09 head drift.');
$check(($heads['25'] ?? '') === 'e35563b7f3d8ebf0acbbc80982b7bcf2e1b78c0a', 'Fresh audit baseline File 25 head mismatch.');
$check(($heads['evidence_class'] ?? '') === 'repository-source-only-not-staging-live', 'Repository-head evidence must remain explicitly non-live.');
$audit = (array) ($deps['file25_review675_694_audit'] ?? []);
$check(($audit['review_range'] ?? '') === '675-694', 'Dependency matrix fresh audit marker missing.');
$check(($audit['exact_current_head_ci'] ?? '') === 'external-evidence-required-not-frozen-in-source', 'Current candidate CI must remain external mutable evidence.');

try {
    $central = json_decode($read('config/central-2026-file25-requirements.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $central = [];
    $failures[] = 'Central File 25 requirements invalid: ' . $exception->getMessage();
}
$centralIds = array_column((array) ($central['central_requirements'] ?? []), 'id');
foreach (['CV-019','CV-021','CV-032'] as $id) {
    $check(in_array($id, $centralIds, true), 'Current central requirement reconciliation missing: ' . $id);
}

try {
    $matrix = json_decode($read('config/source-completion-matrix.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $matrix = [];
    $failures[] = 'Source completion matrix invalid: ' . $exception->getMessage();
}
$check(($matrix['declaration']['latest_review_range'] ?? '') === '675-694', 'Source completion matrix latest review range must be 675-694.');
$check(($matrix['declaration']['review_ledger'] ?? '') === 'config/review675-694-portfolio-preview-media-ledger.json', 'Source completion matrix fresh ledger pointer missing.');
$check(($matrix['declaration']['known_unresolved_source_defects'] ?? null) === 0, 'Known File 25 source defects must be zero after corrections.');
foreach (['hostinger_staging_accepted','founder_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($matrix['declaration'][$gate] ?? null) === false, 'Source matrix promoted external gate: ' . $gate);
}

$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review675-694-portfolio-preview-media.php'), 'Fresh Reviews 675-694 test is not governed.');
$readme = $read('README.md');
$check(str_contains($readme, 'Reviews 675–694'), 'README current review lineage missing.');
$check(str_contains($readme, 'Known unresolved File 25 source defects after the recorded corrections: **0**'), 'README zero-known-source-defect statement missing.');
$check(str_contains($readme, 'Hostinger staging accepted: **No**') && str_contains($readme, 'Live deployment verified: **No**'), 'README external acceptance boundary missing.');

foreach ([
    'config/review675-694-portfolio-preview-media-ledger.json',
    'docs/REVIEWS-675-694-PORTFOLIO-PREVIEW-MEDIA-2026-10-08.md',
] as $evidence) {
    $check(file_exists($root . '/' . $evidence), 'Fresh audit evidence missing: ' . $evidence);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 675-694\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 675-694 portfolio/preview/media corrections preserved\n";
