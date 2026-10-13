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
    $ledger = json_decode($read('config/review635-654-plan-completeness-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Reviews 635-654 ledger invalid: ' . $exception->getMessage();
}

$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Fresh review ledger identity mismatch.');
$check(($ledger['review_range']['first'] ?? null) === 635 && ($ledger['review_range']['last'] ?? null) === 654 && ($ledger['review_range']['count'] ?? null) === 20, 'Fresh twenty-round range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === [638, 639, 640, 641, 652, 653], 'Fresh defect rounds mismatch.');
$reviewNumbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) {
        $failures[] = 'Malformed Reviews 635-654 row.';
        continue;
    }
    $reviewNumbers[] = $row['review'];
}
$check($reviewNumbers === range(635, 654), 'Reviews 635-654 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified_live','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth promoted in fresh ledger: ' . $gate);
}

$plan = $read('includes/class-plan-completion.php');
$check(str_contains($plan, "'timeline_page_size' => ['type' => 'int', 'default' => 20, 'minimum' => 5, 'maximum' => 50]"), 'Governed timeline_page_size preference missing.');
$check(str_contains($plan, 'public static function preview_urls(array $profile): array'), 'Owner preview URL factory missing.');
$check(str_contains($plan, "['public', 'member', 'mobile', 'desktop', 'contact', 'search', 'social']"), 'Seven governed preview modes missing.');
$check(str_contains($plan, 'View-as-Public is a projection-only surface'), 'Projection-only preview rationale missing.');
$check(str_contains($plan, "\$profile['profile_template'] = (string) \$preferences['profile_template'];"), 'profile_template preference is not applied.');

$renderer = $read('includes/class-profile-renderer.php');
$check(str_contains($renderer, "\$timeline_page_size = max(5, min(50"), 'Renderer does not consume bounded timeline page size.');
$check(str_contains($renderer, "'per_page' => \$timeline_page_size"), 'Timeline service call does not use governed page size.');
$check(str_contains($renderer, "\$completion_assistant = \$preview_mode === ''"), 'Completion Assistant is not suppressed in preview.');
$check(str_contains($renderer, "\$context['preview_urls']"), 'Renderer does not expose authorized preview destinations.');

$native = $read('includes/class-native-integration.php');
$check(str_contains($native, 'public function profile_media(int $user_id, string $purpose): array'), 'Current File 03 public media DTO adapter missing.');
$check(str_contains($native, "['url']") && str_contains($native, "['alt']") && str_contains($native, "['focal_x']") && str_contains($native, "['focal_y']"), 'Bounded File 03 media fields are not consumed.');

$repository = $read('includes/class-profile-repository.php');
$check(str_contains($repository, "\$avatar_media = \$this->native->profile_media(\$user_id, 'avatar');"), 'Profile repository still fails to consume current File 03 avatar DTO.');
$check(str_contains($repository, "'avatar_alt' => \$avatar_alt"), 'Canonical File 03 avatar alternative text is not projected.');


$template = $read('templates/public-profile.php');
foreach (['spux-preview-toolbar','spux-preview-modes','Contact visibility preview','Search-engine preview','Social share-card preview','data-spux-template','data-spux-preview-mode'] as $marker) {
    $check(str_contains($template, $marker), 'Public preview/template marker missing: ' . $marker);
}
$check(str_contains($template, 'Previewing never changes authorization or public data.'), 'Preview authorization boundary text missing.');

$css = $read('assets/css/public.css');
foreach (['.spux-preview--mobile > .spux-container','.spux-preview--desktop > .spux-container','data-spux-template="compact"','data-spux-template="institutional"'] as $marker) {
    $check(str_contains($css, $marker), 'Preview/profile-template CSS marker missing: ' . $marker);
}

$js = $read('assets/js/public.js');
foreach (['scrollRestoration','sessionStorage','back_forward','pagehide'] as $marker) {
    $check(str_contains($js, $marker), 'Browser-Back scroll restoration marker missing: ' . $marker);
}

$current = $read('includes/class-current-companion-2026-08-10.php');
foreach (['sabri_file08_public_clinic_projection_v1','sabri_file17_profile_action_url_v1','sabri_network_message_profile_url'] as $invented) {
    $check(! str_contains($current, $invented), 'Unpublished profile-action dependency reintroduced: ' . $invented);
}
$check(str_contains($current, "(\$action === 'follow' || \$action === 'appointment')"), 'File 08/File 17 fail-closed action boundary missing.');

try {
    $staging = json_decode($read('config/staging-test-plan.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $staging = [];
    $failures[] = 'Staging plan invalid: ' . $exception->getMessage();
}
$scenarioIds = [];
foreach ((array) ($staging['scenarios'] ?? []) as $scenario) {
    if (is_array($scenario) && is_string($scenario['id'] ?? null)) {
        $scenarioIds[] = $scenario['id'];
    }
}
foreach (['owner-public-preview','timeline-config-and-back-navigation','profile-template-setting'] as $id) {
    $check(in_array($id, $scenarioIds, true), 'Fresh staging scenario missing: ' . $id);
}

try {
    $matrix = json_decode($read('config/source-completion-matrix.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $matrix = [];
    $failures[] = 'Source completion matrix invalid: ' . $exception->getMessage();
}
$latestRange = (string) ($matrix['declaration']['latest_review_range'] ?? '');
$check(in_array($latestRange, ['635-654', '655-674', '675-694', '695-714', '715-734'], true), 'Source completion matrix must preserve or advance beyond Reviews 635-654.');
$check(file_exists($root . '/config/review635-654-plan-completeness-ledger.json'), 'Historical Reviews 635-654 ledger must remain preserved.');
$check(($matrix['declaration']['exact_head_ci'] ?? '') === 'external-evidence-required-for-current-head-not-frozen-in-source', 'Mutable exact-head CI truth must not be frozen as self-certified source status.');
$check(($matrix['declaration']['known_unresolved_source_defects'] ?? null) === 0, 'Known File 25 source defects must be zero after corrections.');
foreach (['hostinger_staging_accepted','founder_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($matrix['declaration'][$gate] ?? null) === false, 'Source matrix promoted external gate: ' . $gate);
}

try {
    $deps = json_decode($read('config/staging-dependencies.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $deps = [];
    $failures[] = 'Dependency matrix invalid: ' . $exception->getMessage();
}
$audit = (array) ($deps['file25_review635_654_audit'] ?? []);
$check(($audit['baseline_main_sha'] ?? '') === '210b4b250fd0f996b1fb6b9eb5471dec831f6dfa', 'Fresh audit baseline main SHA mismatch.');
$check(($audit['baseline_main_ci']['run_number'] ?? null) === 1601 && ($audit['baseline_main_ci']['conclusion'] ?? '') === 'success', 'Fresh audit baseline CI evidence mismatch.');
$check(($audit['review_range'] ?? '') === '635-654', 'Fresh dependency audit review range mismatch.');
$check(($audit['exact_current_head_ci'] ?? '') === 'external-evidence-required-not-frozen-in-source', 'Dependency matrix exact-current-head CI boundary missing.');
$heads = (array) ($deps['observed_repository_heads'] ?? []);
$check(($heads['08'] ?? '') === '70541974ce0ffb16aebef557c3016eb7447662f4', 'File 08 repository-head drift.');
$check(($heads['17'] ?? '') === '8ae656e51796d1f05865d8be5dca2480443d79ca', 'File 17 repository-head drift.');
$check(($heads['21'] ?? '') === 'f2eb7e95ddea327af36ea725ffb923b029f885e6', 'File 21 repository-head drift.');
$check(($heads['23'] ?? '') === 'dcae138e6073f4d0ff596623deb05b9940b8271b', 'File 23 repository-head drift.');
$check(($heads['24'] ?? '') === 'a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb', 'File 24 repository-head drift.');
$check(($heads['26'] ?? '') === 'bbea3aad466792a4a6a62b53532bbd45c7c592de', 'File 26 repository-head drift.');
$check(($heads['evidence_class'] ?? '') === 'repository-source-only-not-staging-live', 'Repository-head evidence must remain non-live.');

$readme = $read('README.md');
$check((str_contains($readme, 'Reviews 715–734') || str_contains($readme, 'Reviews 695–714') || str_contains($readme, 'Reviews 675–694')) || str_contains($readme, 'Reviews 655–674') || str_contains($readme, 'Reviews 635–654'), 'README review lineage missing.');
$check(($ledger['basis']['baseline_main_ci']['run_number'] ?? null) === 1601 && ($ledger['basis']['baseline_main_ci']['conclusion'] ?? '') === 'success', 'Historical Reviews 635-654 baseline CI evidence missing from its ledger.');
$check(str_contains($readme, 'exact current GitHub HEAD must be checked against its current CI run'), 'README current-head CI evidence boundary missing.');
$check(! str_contains($readme, 'current candidate exact-head CI pending'), 'README contains stale pending-CI claim.');

$wpReadme = $read('readme.txt');
$check((str_contains($wpReadme, 'Reviews 715–734') || str_contains($wpReadme, 'Reviews 695–714') || str_contains($wpReadme, 'Reviews 675–694')) || str_contains($wpReadme, 'Reviews 655–674') || str_contains($wpReadme, 'Reviews 635–654'), 'WordPress readme review lineage missing.');
$check(str_contains($wpReadme, 'runtime `1.2.15`, schema `3.4.0`, public-clinic contract `1.1.0`'), 'WordPress readme current File 08 repository observation missing.');
$check(! str_contains($wpReadme, 'The exact File 08 staging input is runtime `0.2.1`'), 'Obsolete File 08 staging pin remains in WordPress readme.');
$check(! str_contains($wpReadme, 'Confirm File 00 `1.2.4`'), 'Obsolete installation version list remains in WordPress readme.');

$composer = $read('composer.json');
$check(str_contains($composer, 'tests/review635-654-plan-completeness.php'), 'Fresh audit test is not governed.');
$check((str_contains($composer, 'Reviews 715-734') || str_contains($composer, 'Reviews 695-714') || str_contains($composer, 'Reviews 675-694')) || str_contains($composer, 'Reviews 655-674') || str_contains($composer, 'Reviews 635-654'), 'Composer description does not identify current audit lineage.');

foreach ([
    'config/review635-654-plan-completeness-ledger.json',
    'docs/REVIEWS-635-654-PLAN-COMPLETENESS-2026-10-06.md',
] as $evidence) {
    $check(file_exists($root . '/' . $evidence), 'Fresh audit evidence missing: ' . $evidence);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 635-654\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 635-654 plan/code/cross-file completeness corrections preserved\n";
