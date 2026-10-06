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
    $ledger = json_decode($read('config/review595-614-twenty-round-cross-file-completeness-ledger.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $ledger = [];
    $failures[] = 'Reviews 595-614 ledger JSON invalid: ' . $exception->getMessage();
}
$check(($ledger['schema_version'] ?? null) === 1 && ($ledger['file'] ?? null) === 25, 'Reviews 595-614 ledger identity mismatch.');
$check(($ledger['review_range']['first'] ?? null) === 595 && ($ledger['review_range']['last'] ?? null) === 614 && ($ledger['review_range']['count'] ?? null) === 20, 'Twenty-round range mismatch.');
$check(($ledger['defect_rounds'] ?? null) === [596, 599, 610, 611, 613, 614], 'Twenty-round defect history mismatch.');
$numbers = [];
foreach ((array) ($ledger['reviews'] ?? []) as $row) {
    if (! is_array($row) || ! is_int($row['review'] ?? null)) {
        $failures[] = 'Malformed Reviews 595-614 row.';
        continue;
    }
    $numbers[] = $row['review'];
}
$check($numbers === range(595, 614), 'Reviews 595-614 must be contiguous.');
foreach (['hostinger_staging_accepted','founder_staging_acceptance','production_accepted','live_deployed','operational','exact_deployed_code_verified','db_version_verified','migration_state_verified_live'] as $gate) {
    $check(($ledger['external_truth'][$gate] ?? null) === false, 'External truth must remain unpromoted: ' . $gate);
}

$native = $read('includes/class-native-integration.php');
foreach ([
    "FILE_00_CONTRACT_VERSION = '1.2.3'",
    "FILE_00_SUPPORTED_CONTRACT_VERSIONS = ['1.2.2', '1.2.3']",
    "FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.1.0'",
    "FILE_08_SUPPORTED_PUBLIC_PROJECTION_CONTRACTS = ['1.0.0', '1.1.0']",
] as $marker) {
    $check(str_contains($native, $marker), 'Current native compatibility marker missing: ' . $marker);
}
$check(str_contains($native, 'in_array(trim((string) SMC_CONTRACT_VERSION), self::FILE_00_SUPPORTED_CONTRACT_VERSIONS, true)'), 'File 00 current compatibility is not enforced.');
$check(str_contains($native, 'self::FILE_08_SUPPORTED_PUBLIC_PROJECTION_CONTRACTS'), 'File 08 current compatibility is not enforced.');

$completion = $read('includes/class-plan-completion.php');
$check(str_contains($completion, "apply_filters('sabri_public_experience/high_risk_authorized', false"), 'High-risk operations must default deny.');
$check(! str_contains($completion, "apply_filters('sabri_public_experience/high_risk_authorized', true"), 'High-risk operations must not default allow.');

$plugin = $read('includes/class-plugin.php');
$bootstrap = $read('sabri-public-experience.php');
$safePublic = $read('includes/class-safe-mode-public.php');
$safeTemplate = $read('templates/safe-mode-profile.php');
$check(preg_match('/^\\s*\\* Version:\\s*(\\d+\\.\\d+\\.\\d+)\\s*$/m', $bootstrap, $version_match) === 1 && version_compare((string) ($version_match[1] ?? '0.0.0'), '0.15.0', '>='), 'Runtime must preserve the 0.15.0 correction baseline or newer.');
$check(str_contains($bootstrap, "'includes/class-safe-mode-public.php'"), 'Safe Mode public class is not loaded.');
$check(str_contains($plugin, '(new Safe_Mode_Public($router))->register();'), 'Safe Mode does not register recoverable public routes.');
foreach (['status_header(503)', "Cache-Control: no-store", "Retry-After: 300", "templates/safe-mode-profile.php"] as $marker) {
    $check(str_contains($safePublic, $marker), 'Safe Mode recovery marker missing: ' . $marker);
}
$check(str_contains($safeTemplate, 'protected recovery state'), 'Safe Mode template must be data-free and explicit.');

try {
    $deps = json_decode($read('config/staging-dependencies.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $deps = [];
    $failures[] = 'Dependency matrix JSON invalid: ' . $exception->getMessage();
}
$check(version_compare((string) ($deps['runtime_version'] ?? '0.0.0'), '0.15.0', '>='), 'Dependency matrix must preserve the 0.15.0 correction baseline or newer.');
$check(($deps['environment']['integrated_candidate_wordpress_minimum'] ?? null) === '7.0', 'Integrated WordPress minimum must reflect current File 03.');
$check(($deps['environment']['integrated_candidate_php_minimum'] ?? null) === '8.1', 'Integrated PHP minimum must reflect current companions.');
$heads = (array) ($deps['observed_repository_heads'] ?? []);
foreach ([
    '00' => '2fa7c022ee9cd1b65432e900579512f304532442',
    '03' => '636e3ef965423887f810718abec3cd1c11c3659d',
    '08' => '70541974ce0ffb16aebef557c3016eb7447662f4',
    '20' => '8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca',
    '21' => 'f2eb7e95ddea327af36ea725ffb923b029f885e6',
    '24' => 'a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb',
] as $file => $sha) {
    $check(($heads[$file] ?? null) === $sha, 'Observed current repository head mismatch for File ' . $file . '.');
}
$check(($heads['evidence_class'] ?? null) === 'repository-source-only-not-staging-live', 'Repository observations must not imply staging/live truth.');

foreach (['ACCESSIBILITY.md','PERFORMANCE.md','MIGRATION.md','ROLLBACK.md','STAGING-ACCEPTANCE.md','docs/CROSS-FILE-HEAD-AUDIT-2026-10-05.md'] as $requiredDoc) {
    $check(is_file($root . '/' . $requiredDoc), 'Required release documentation missing: ' . $requiredDoc);
}

try {
    $matrix = json_decode($read('config/source-completion-matrix.json'), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    $matrix = [];
    $failures[] = 'Source completion matrix JSON invalid: ' . $exception->getMessage();
}
$check(version_compare((string) ($matrix['module']['runtime'] ?? '0.0.0'), '0.15.0', '>='), 'Source completion matrix must preserve the 0.15.0 correction baseline or newer.');
foreach (['hostinger_staging_accepted','founder_acceptance','production_accepted','live_deployed','operational'] as $gate) {
    $check(($matrix['declaration'][$gate] ?? null) === false, 'Source matrix must not promote external acceptance: ' . $gate);
}

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 595-614\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Reviews 595-614 — twenty-round cross-file completeness corrections preserved\n";
