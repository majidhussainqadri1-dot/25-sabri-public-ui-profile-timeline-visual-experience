<?php
declare(strict_types=1);

/**
 * R1795–1834: independently validate forty DISTINCT targeted source checks,
 * truthful status classification and the associated File25-only repair fix.
 * This is NOT real WordPress staging, production or live evidence.
 */
$root = dirname(__DIR__);
$errors = [];
$check = static function (bool $condition, string $message) use (&$errors): void {
    if (! $condition) { $errors[] = $message; }
};
$readJson = static function (string $path) use ($root, &$errors): array {
    $raw = @file_get_contents($root . '/' . $path);
    if (! is_string($raw)) {
        $errors[] = 'Unreadable source evidence: ' . $path;
        return [];
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        $errors[] = 'Invalid source evidence: ' . $path . ': ' . $e->getMessage();
        return [];
    }
    return is_array($data) ? $data : [];
};

$ledger = $readJson('config/review1795-1834-cross-owner-source-completeness-audit.json');
$checks = $ledger['checks'] ?? [];
$check(($ledger['schema'] ?? null) === 1 && ($ledger['module'] ?? null) === 'file-25',
    'Wrong forty-check ledger identity.');
$check(($ledger['baseline_main'] ?? null) === '347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e',
    'Repository baseline must remain the evidence-frozen main SHA.');
$check(is_array($checks) && count($checks) === 40,
    'File25 review ledger must have exactly forty distinct checks.');

$numbers = [];
$tally = [];
$allowed = [
    'source-witness', 'owner-contract-pending',
    'partial-owner-contract-pending', 'documented-contract-only',
    'confirmed-defect-corrected-unmerged-CI-pending',
];
foreach ((array) $checks as $row) {
    if (! is_array($row)) {
        $errors[] = 'Malformed review row.';
        continue;
    }
    $id = (string) ($row['review_id'] ?? '');
    if (preg_match('/^R([0-9]{4})$/', $id, $match) !== 1) {
        $errors[] = 'Invalid review identifier.';
        continue;
    }
    $numbers[] = (int) $match[1];
    $status = (string) ($row['evidence_class'] ?? '');
    $check(in_array($status, $allowed, true), 'Invalid evidence class for ' . $id);
    $tally[$status] = ($tally[$status] ?? 0) + 1;
    $source = (string) ($row['source'] ?? '');
    $check($source !== '' && ! str_contains($source, '..')
        && ! str_starts_with($source, '/')
        && is_file($root . '/' . $source),
        'Missing review source: ' . $id);
    $check(($row['production_proof'] ?? true) === false,
        'Source check promoted to production proof: ' . $id);
}
$check($numbers === range(1795, 1834),
    'Forty review IDs must be unique, contiguous and correctly sequenced.');
$expect = [
    'source-witness' => 34, 'owner-contract-pending' => 2,
    'partial-owner-contract-pending' => 1, 'documented-contract-only' => 2,
    'confirmed-defect-corrected-unmerged-CI-pending' => 1,
];
foreach ($expect as $status => $number) {
    $check(($tally[$status] ?? 0) === $number,
        'Actual forty-check classification mismatch: ' . $status);
}
$summary = (array) ($ledger['summary'] ?? []);
$check(($summary['checks'] ?? 0) === 40
    && ($summary['source_witnesses'] ?? -1) === 34
    && ($summary['confirmed_source_defect'] ?? -1) === 1,
    'Aggregate audit summary not reconciled with the individual records.');

$source = @file_get_contents($root . '/includes/class-plan-completion.php');
$check(is_string($source)
    && str_contains($source, 'private function reconcile_stale_index(): bool')
    && str_contains($source, 'Index_Atomic_Options::compare_replace(')
    && str_contains($source, 'Index_Atomic_Options::compare_delete(')
    && ! str_contains($source, 'update_option(self::INDEX_OPTION, $index, false)'),
    'Stale repair must use only the guarded index and lock CAS path.');

$matrix = $readJson('config/source-completion-matrix.json');
$declaration = (array) ($matrix['declaration'] ?? []);
$check(($declaration['latest_review_range'] ?? null) === '1795-1834'
    && ($declaration['review_ledger'] ?? null) === 'config/review1795-1834-cross-owner-source-completeness-audit.json',
    'Source matrix must point to this exact latest evidence.');
foreach (['hostinger_staging_accepted', 'founder_acceptance',
    'production_accepted', 'live_deployed', 'operational',
    'exact_deployed_code_verified', 'db_version_verified_live',
    'migration_state_verified_live'] as $gate) {
    $check(($declaration[$gate] ?? null) === false,
        'External evidence must not be silently promoted: ' . $gate);
}
$check(($ledger['live_verification_status'] ?? null) === 'not-tested'
    && array_key_exists('deployed_version', $ledger) && $ledger['deployed_version'] === null,
    'Audit ledger must remain explicit about unknown installed/live truth.');

if ($errors !== []) {
    fwrite(STDERR, "FAIL R1795-1834 source ledger:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS R1795-1834: 40 distinct source scopes, exact counts, evidence boundaries and CAS repair gate.\n";
