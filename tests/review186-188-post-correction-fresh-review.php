<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) { $failures[] = $message; }
};
$read = static fn (string $path): string => (string) file_get_contents($root . '/' . $path);

$visualFiles = [
    'assets/css/design-system.css',
    'assets/css/public.css',
    'includes/class-design-system.php',
    'includes/class-central-plan-2026-corrections.php',
    'includes/class-shell-integration.php',
];
$active = '';
foreach ($visualFiles as $path) { $active .= "\n/* $path */\n" . $read($path); }
foreach (['#ff8a1f', '#ffbd80', '#b94700', '#9a3d00', '#fff3e8', '#ffc48c', '#065F46', '#E7F5EF'] as $stale) {
    $check(! str_contains(strtolower($active), strtolower($stale)), 'Review 186: stale visual value remains after correction: ' . $stale);
}
$check(substr_count($read('assets/css/design-system.css'), 'var(--sabri-shell-max-width') === 1, 'Review 186: only one structural shell max-width bridge is expected in canonical design CSS.');

$ledger = json_decode($read('config/central-2026-file25-requirements.json'), true);
$check(is_array($ledger), 'Review 187: latest governing requirement ledger must be valid JSON.');
if (is_array($ledger)) {
    $fileIds = array_column((array) ($ledger['file_specific_requirements'] ?? []), 'id');
    foreach (['F25-CEN-01','F25-CEN-02'] as $id) {
        $check(in_array($id, $fileIds, true), 'Review 187: latest governing ledger missing ' . $id . '.');
    }
    $journeys = array_column((array) ($ledger['acceptance_journeys'] ?? []), 'id');
    foreach (['AJ-04','AJ-31','AJ-32','AJ-33','AJ-39','AJ-40'] as $id) {
        $check(in_array($id, $journeys, true), 'Review 187: latest governing ledger missing ' . $id . '.');
    }
}
if (is_array($ledger)) {
    foreach (['hostinger_staging','real_role_browser_rtl_accessibility','founder_acceptance','production','live','operational'] as $gate) {
        $check(($ledger['external_acceptance'][$gate] ?? null) === false, 'Review 188: external gate must remain truthfully unaccepted: ' . $gate . '.');
    }
}
$check(str_contains($read('includes/class-visual-acceptance.php'), "'source_contract_is_acceptance' => false"), 'Review 188: source contract must not claim staging/browser acceptance.');
$check(str_contains($read('includes/class-visual-acceptance.php'), "'green_ci_is_acceptance' => false"), 'Review 188: green CI must not claim staging/browser acceptance.');
$check(str_contains($read('includes/class-design-system.php'), "'global_search_discovery_ranking_owner' => 'file-26'"), 'Review 188: File 26 global search/discovery/ranking ownership must remain explicit.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 186-188\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: Reviews 186-188 fresh post-correction review and truthful external gates\n";
