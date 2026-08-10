<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$matrixPath = $root . '/config/source-completion-matrix.json';
$futurePath = $root . '/config/future-public-experience-24.json';

if (! is_file($matrixPath)) {
    fwrite(STDERR, "Missing source completion matrix.\n");
    exit(1);
}

$raw = file_get_contents($matrixPath);
if (! is_string($raw) || $raw === '') {
    fwrite(STDERR, "Source completion matrix is unreadable.\n");
    exit(1);
}

try {
    /** @var array<string,mixed> $matrix */
    $matrix = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, 'Invalid source completion JSON: ' . $exception->getMessage() . "\n");
    exit(1);
}

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$assert(($matrix['schema'] ?? null) === 1, 'Matrix schema must be integer 1.');
$assert(($matrix['module']['file_number'] ?? null) === 25, 'Matrix must govern File 25.');
$assert(($matrix['module']['runtime'] ?? null) === '0.14.0', 'Matrix runtime must match 0.14.0.');
$assert(($matrix['declaration']['source_scope_status'] ?? null) === 'complete', 'Source scope must be declared complete.');
$assert(($matrix['declaration']['known_unresolved_source_defects'] ?? null) === 0, 'Known unresolved source defects must be integer zero.');
$assert(($matrix['declaration']['automated_qa_required'] ?? null) === true, 'Automated QA must remain mandatory.');

foreach (['hostinger_staging_accepted', 'founder_acceptance', 'production_accepted', 'live_deployed', 'operational'] as $externalGate) {
    $assert(($matrix['declaration'][$externalGate] ?? null) === false, sprintf('External gate %s must remain false until accepted evidence exists.', $externalGate));
}

$plugin = file_get_contents($root . '/sabri-public-experience.php');
$assert(is_string($plugin), 'Main plugin file must be readable.');
if (is_string($plugin)) {
    $assert(preg_match('/^\s*\* Version:\s*0\.14\.0\s*$/m', $plugin) === 1, 'Plugin header version must match matrix runtime.');
    $assert(str_contains($plugin, "define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0');"), 'Runtime constant must match matrix runtime.');
}

$requirements = $matrix['requirements'] ?? null;
$assert(is_array($requirements), 'Requirements must be an array.');
$requiredIds = [
    'MP-R03-R07-FILE25-IDENTITY-BOUNDARY',
    'MP-P04-P05-IDENTITY-OWNERSHIP',
    'MP-P06-PUBLIC-READING',
    'MP-P08-P15-SAFETY-PRIVACY',
    'MP-P09-TRUTHFUL-STATUS',
    'F25-DESIGN-SYSTEM-25-28',
    'F25-PUBLIC-ROUTES-29',
    'F25-PROFILE-CLASSES-HERO-30-38',
    'F25-TIMELINE-39-48',
    'F25-CONTENT-CARDS-49-54',
    'F25-STATES-55-58',
    'F25-RESPONSIVE-ACCESSIBILITY-RTL-59-61',
    'F25-PERFORMANCE-RELIABILITY-62-63',
    'F25-SECURITY-FILE24-64',
    'F25-SEO-METRICS-COMPLETION-68-70',
    'F25-ADMIN-CONFIG-71',
    'F25-REST-COMPONENT-API-72-73',
    'F25-OBSERVABILITY-SAFE-MODE-REPAIR-74-76',
    'F25-MIGRATION-STRUCTURE-UNINSTALL-77-79',
    'F25-QA-80-84',
    'F25-VISUAL-BROWSER-85-86',
    'F25-RELEASE-DELIVERABLES-87-88',
    'F25-STAGING-ACCEPTANCE-89',
    'F25-DEFINITION-OF-DONE-90-93',
    'REVIEW-CORRECTION-LINEAGE-20-93',
];
$seen = [];
$allowedStatuses = ['implemented', 'delegated', 'external_acceptance'];

if (is_array($requirements)) {
    foreach ($requirements as $index => $requirement) {
        $assert(is_array($requirement), sprintf('Requirement at index %d must be an object.', $index));
        if (! is_array($requirement)) {
            continue;
        }
        $id = $requirement['id'] ?? null;
        $status = $requirement['status'] ?? null;
        $summary = $requirement['summary'] ?? null;
        $evidence = $requirement['evidence'] ?? null;
        $assert(is_string($id) && preg_match('/^[A-Z0-9-]+$/', $id) === 1, sprintf('Requirement at index %d has an invalid ID.', $index));
        if (! is_string($id) || $id === '') {
            continue;
        }
        $assert(! isset($seen[$id]), sprintf('Duplicate requirement ID: %s.', $id));
        $seen[$id] = true;
        $assert(is_string($status) && in_array($status, $allowedStatuses, true), sprintf('Requirement %s has an invalid status.', $id));
        $assert(is_string($summary) && trim($summary) !== '', sprintf('Requirement %s needs a non-empty summary.', $id));
        $assert(is_array($evidence) && $evidence !== [], sprintf('Requirement %s needs evidence paths.', $id));
        if (is_array($evidence)) {
            foreach ($evidence as $path) {
                $assert(is_string($path) && $path !== '', sprintf('Requirement %s contains an invalid evidence path.', $id));
                if (! is_string($path) || $path === '') {
                    continue;
                }
                $assert(! str_starts_with($path, '/') && ! str_contains($path, '..'), sprintf('Requirement %s contains an unsafe evidence path: %s.', $id, $path));
                $assert(file_exists($root . '/' . $path), sprintf('Requirement %s evidence path does not exist: %s.', $id, $path));
            }
        }
        if ($status === 'external_acceptance') {
            $assert(($requirement['source_preparation_complete'] ?? null) === true, sprintf('External requirement %s must declare source preparation complete.', $id));
            $assert(($requirement['acceptance_pending'] ?? null) === true, sprintf('External requirement %s must remain acceptance-pending.', $id));
        } else {
            $assert(! array_key_exists('acceptance_pending', $requirement), sprintf('Non-external requirement %s must not carry an acceptance_pending flag.', $id));
        }
    }
}
foreach ($requiredIds as $requiredId) {
    $assert(isset($seen[$requiredId]), sprintf('Mandatory completion requirement is missing: %s.', $requiredId));
}
$assert(count($seen) === count($requiredIds), 'Completion matrix must contain exactly the governed base requirement groups.');

$future = null;
if (! is_file($futurePath)) {
    $failures[] = 'Future Public Experience 24-requirement matrix is missing.';
} else {
    try {
        $futureRaw = file_get_contents($futurePath);
        $future = is_string($futureRaw) ? json_decode($futureRaw, true, 512, JSON_THROW_ON_ERROR) : null;
    } catch (JsonException $exception) {
        $failures[] = 'Invalid Future Public Experience JSON: ' . $exception->getMessage();
    }
}
$futureSeen = [];
if (is_array($future)) {
    $assert(($future['schema_version'] ?? null) === 1, 'Future matrix schema must be integer 1.');
    $assert(($future['contract_version'] ?? null) === '1.0.0', 'Future matrix contract must be 1.0.0.');
    $assert(($future['governing_file'] ?? null) === 25, 'Future matrix must govern File 25.');
    $futureRequirements = $future['requirements'] ?? null;
    $assert(is_array($futureRequirements) && count($futureRequirements) === 24, 'Future matrix must contain exactly 24 approved requirements.');
    if (is_array($futureRequirements)) {
        foreach ($futureRequirements as $index => $requirement) {
            $assert(is_array($requirement), sprintf('Future requirement at index %d must be an object.', $index));
            if (! is_array($requirement)) {
                continue;
            }
            $id = $requirement['id'] ?? null;
            $assert(is_string($id) && preg_match('/^F25-FUT-(?:0[1-9]|1[0-9]|2[0-4])$/', $id) === 1, sprintf('Invalid future requirement ID at index %d.', $index));
            if (is_string($id) && $id !== '') {
                $assert(! isset($futureSeen[$id]), 'Duplicate future requirement ID: ' . $id);
                $futureSeen[$id] = true;
            }
            $assert(($requirement['presentation_owner'] ?? null) === 'file-25', sprintf('Future requirement %s must remain File 25 presentation-owned.', (string) $id));
            $assert(($requirement['source_status'] ?? null) === 'implemented-candidate', sprintf('Future requirement %s must be source-implemented candidate.', (string) $id));
        }
    }
    for ($i = 1; $i <= 24; $i++) {
        $id = sprintf('F25-FUT-%02d', $i);
        $assert(isset($futureSeen[$id]), 'Missing approved future requirement: ' . $id);
    }
    foreach (['hostinger_staging_accepted', 'founder_staging_acceptance', 'live_deployed', 'operational'] as $externalGate) {
        $assert(($future['release_truth'][$externalGate] ?? null) === false, sprintf('Future external gate %s must remain false.', $externalGate));
    }
    $assert(($future['invariants']['duplicate_backend_allowed'] ?? true) === false, 'Future enhancements may not create a duplicate backend.');
    $assert(($future['invariants']['quality_score_affects_public_ranking'] ?? true) === false, 'Future quality score may not affect public ranking.');
}
foreach ([
    'includes/class-future-public-experience.php',
    'assets/css/future-public-experience.css',
    'assets/js/future-public-experience.js',
    'docs/FUTURE-PUBLIC-EXPERIENCE-24-ENHANCEMENTS-2026-08-10.md',
    'tests/future-public-experience.php',
    'tests/review189-191-future-public-experience.php',
    'tests/review192-194-future-public-experience-post-review.php',
] as $futureEvidence) {
    $assert(file_exists($root . '/' . $futureEvidence), 'Future source evidence is missing: ' . $futureEvidence);
}

$readme = file_get_contents($root . '/README.md');
$assert(is_string($readme), 'README must be readable.');
if (is_string($readme)) {
    $assert(str_contains($readme, 'Source implementation status: **Complete**'), 'README must declare source implementation complete.');
    $assert(str_contains($readme, 'Hostinger staging accepted: **No**'), 'README must retain the Hostinger staging boundary.');
    $assert(str_contains($readme, 'Production approved: **No**'), 'README must retain the production boundary.');
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . "\n");
    }
    exit(1);
}

echo sprintf(
    "File 25 source completion verified: %d base groups + %d approved future requirements; zero known source defects, external acceptance deferred.\n",
    count($seen),
    count($futureSeen)
);
