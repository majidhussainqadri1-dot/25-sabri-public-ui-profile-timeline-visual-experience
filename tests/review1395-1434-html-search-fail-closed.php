<?php
declare(strict_types=1);

/**
 * Reviews 1395–1434: source-level HTML search fail-closed regression.
 * This does not replace a real WordPress/browser integration test.
 */
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';

use Sabri\PublicExperience\Plan_Completion;

$renderer = file_get_contents(dirname(__DIR__) . '/includes/class-profile-renderer.php');
if (! is_string($renderer)) {
    fwrite(STDERR, "R1395–1434: renderer source unavailable.\n");
    exit(1);
}

$failures = [];
$retrieval = '$this->timeline->get_for_author((int) $user->ID';
$guard = '/if\s*\(\s*\$search_error\s*===\s*\'\'\s*\)\s*\{\s*\$timeline\s*=\s*\$this->timeline->get_for_author\(/s';
if (preg_match($guard, $renderer) !== 1) {
    $failures[] = 'Invalid/disabled HTML searches must not invoke timeline retrieval.';
}
if (substr_count($renderer, $retrieval) !== 1) {
    $failures[] = 'Expected exactly one guarded HTML timeline retrieval.';
}
if (! str_contains($renderer, '$context[\'timeline_search_error\'] = $search_error;')) {
    $failures[] = 'Search error must be available to the view.';
}
if (! str_contains($renderer, '$search_error = isset($_GET[\'profile_q\']) ? \'search_disabled\' : \'\';')) {
    $failures[] = 'Disabled local search must retain an explicit error.';
}
if (! str_contains($renderer, '! is_scalar($_GET[\'profile_q\'])')) {
    $failures[] = 'Array search input must be rejected.';
}
foreach ([
    ['input' => str_repeat('a', 121), 'valid' => false, 'label' => 'oversize'],
    ['input' => "bad\u{202E}query", 'valid' => false, 'label' => 'bidi control'],
    ['input' => "اردو تلاش", 'valid' => true, 'label' => 'Urdu search'],
    ['input' => 'normal query', 'valid' => true, 'label' => 'ordinary search'],
] as $case) {
    $actual = Plan_Completion::normalize_search_query($case['input']);
    if (($actual !== '') !== $case['valid']) {
        $failures[] = 'Unexpected normalization: ' . $case['label'];
    }
}
if ($failures !== []) {
    fwrite(STDERR, "FAILED R1395–1434\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS R1395–1434: HTML timeline fail-closed source guard and search normalization.\n";
