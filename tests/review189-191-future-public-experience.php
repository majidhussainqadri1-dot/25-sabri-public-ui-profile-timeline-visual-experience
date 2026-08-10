<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};
$future = (string) file_get_contents($root . '/includes/class-future-public-experience.php');
$central = (string) file_get_contents($root . '/includes/class-central-plan-2026-corrections.php');
$config = json_decode((string) file_get_contents($root . '/config/future-public-experience-24.json'), true);
$css = (string) file_get_contents($root . '/assets/css/future-public-experience.css');
$js = (string) file_get_contents($root . '/assets/js/future-public-experience.js');

$check(is_array($config), 'Future enhancement machine-readable matrix must decode.');
$check(($config['contract_version'] ?? '') === '1.0.0', 'Future matrix contract version must be 1.0.0.');
$check(count((array) ($config['requirements'] ?? [])) === 24, 'Future matrix must contain exactly 24 requirements.');
$ids = array_column((array) ($config['requirements'] ?? []), 'id');
for ($i = 1; $i <= 24; $i++) {
    $id = sprintf('F25-FUT-%02d', $i);
    $check(in_array($id, $ids, true), 'Future matrix missing ' . $id . '.');
    $check(str_contains($future, "'{$id}'"), 'Source contract missing ' . $id . '.');
}
$check(str_contains($central, 'class-future-public-experience.php'), 'Central correction layer must load the future experience class.');
$check(str_contains($central, 'Future_Public_Experience::contract()'), 'Central visual contract must expose the future experience contract.');
$check(str_contains($css, '.spux-trust-capsule'), 'Trust capsule CSS missing.');
$check(str_contains($css, '.spux-relationship-explorer'), 'Knowledge relationship explorer CSS missing.');
$check(str_contains($css, '.spux-snapshot-card'), 'Snapshot card CSS missing.');
$check(str_contains($js, 'spux-future-public-payload'), 'Progressive public payload layer missing.');
$check(str_contains($js, 'spux-time-navigator'), 'Timeline time navigation missing.');
$check(str_contains($js, 'spux:visual-integrity'), 'Visual integrity local event missing.');
$check(! str_contains($future, '$wpdb'), 'Future presentation layer must not directly access database tables.');
$check(! preg_match('/\b(?:wp_insert_post|wp_update_post|wp_delete_post)\s*\(/i', $future), 'Future presentation layer must not own publication writes.');
$check(($config['release_truth']['hostinger_staging_accepted'] ?? true) === false, 'Staging must remain truthfully pending.');
$check(($config['release_truth']['live_deployed'] ?? true) === false, 'Live must remain truthfully pending.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: Reviews 189-191 File 25 Future Public Experience ownership and source closure\n";
