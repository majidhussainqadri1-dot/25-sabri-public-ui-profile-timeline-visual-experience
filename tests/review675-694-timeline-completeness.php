<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $ok, string $msg) use (&$failures): void { if (! $ok) { $failures[] = $msg; } };
$read = static function (string $path) use ($root, &$failures): string {
    $v = @file_get_contents($root . '/' . $path);
    if (! is_string($v)) { $failures[] = 'Unreadable: ' . $path; return ''; }
    return $v;
};

$ledger = json_decode($read('config/review675-694-timeline-completeness-ledger.json'), true);
$check(is_array($ledger), 'Ledger JSON invalid.');
$check(($ledger['review_range']['first'] ?? null) === 675, 'Ledger first review mismatch.');
$check(($ledger['review_range']['last'] ?? null) === 694, 'Ledger last review mismatch.');
$check(($ledger['review_range']['count'] ?? null) === 20, 'Ledger count mismatch.');

$service = $read('includes/class-timeline-service.php');
foreach (['available_provider_filters','exact_optional_year','exact_optional_language','exact_optional_text','exact_sort','corrections_only'] as $needle) {
    $check(str_contains($service, $needle), 'Timeline service missing ' . $needle);
}

$renderer = $read('includes/class-profile-renderer.php');
foreach (['timeline_provider_filters','timeline_year','timeline_language','timeline_topic','timeline_sort','timeline_filtered_request'] as $needle) {
    $check(str_contains($renderer, $needle), 'Renderer missing ' . $needle);
}

$template = $read('templates/partials/timeline.php');
foreach (['spux-timeline-secondary-filters','name="provider"','name="year"','name="language"','name="topic"','name="sort"','aria-live="polite"'] as $needle) {
    $check(str_contains($template, $needle), 'Template missing ' . $needle);
}

$rest = $read('includes/class-rest-controller.php');
foreach (["'provider' => [","'year' => [","'language' => [","'topic' => [","'sort' => [","'search' => ["] as $needle) {
    $check(str_contains($rest, $needle), 'REST missing ' . $needle);
}

$item = $read('includes/class-normalized-timeline-item.php');
foreach (['normalize_pin_audit',"'reference'","'actor_id'","'reason'","'surface'","'start_at'","'end_at'"] as $needle) {
    $check(str_contains($item, $needle), 'Pin governance missing ' . $needle);
}
$check(str_contains($item, '$pin_weight = 0;'), 'Unaudited pins must fail closed.');

$deps = json_decode($read('config/staging-dependencies.json'), true);
$check(is_array($deps), 'Dependency matrix invalid.');
$heads = is_array($deps) ? (array) ($deps['observed_repository_heads'] ?? []) : [];
$check(($heads['09'] ?? '') === 'cfc5f781a766330314dc98c42abeca0eb7786eba', 'File 09 head drift.');
$check(($heads['14'] ?? '') === '080e2198d84dfb7491bb0b75946e14a5fe118b91', 'File 14 head drift.');
$check(($heads['25'] ?? '') === 'e35563b7f3d8ebf0acbbc80982b7bcf2e1b78c0a', 'File 25 baseline drift.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED Reviews 675-694\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: File 25 Reviews 675-694 timeline completeness corrections preserved\n";
