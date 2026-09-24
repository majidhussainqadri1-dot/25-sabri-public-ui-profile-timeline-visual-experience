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
$css = (string) file_get_contents($root . '/assets/css/future-public-experience.css');
$js = (string) file_get_contents($root . '/assets/js/future-public-experience.js');
$docs = (string) file_get_contents($root . '/docs/FUTURE-PUBLIC-EXPERIENCE-24-ENHANCEMENTS-2026-08-10.md');

foreach (['file-20', 'file-24', 'file-26', 'file-03', 'file-00', 'file-09', 'file-21', 'file-22', 'file-23'] as $owner) {
    $check(str_contains($future, "'{$owner}'"), 'Ownership boundary missing: ' . $owner);
}
foreach (['duplicate_backend_allowed', 'raw_sensitive_data_allowed', 'raw_analytics_warehouse_allowed', 'third_party_tracking_qr_allowed', 'client_preferences_are_authorization'] as $marker) {
    $check(str_contains($future, "'{$marker}' => false"), 'Fail-closed invariant missing: ' . $marker);
}
$check(str_contains($future, "'native_action_execution_required' => true"), 'Native action execution invariant missing.');
$check(str_contains($future, "'public_ranking_effect' => false"), 'Quality score must be explicitly non-ranking.');
$check(str_contains($future, "current_user_can('manage_options')"), 'Owner/admin-sensitive visual tools require server-side admin gating.');
$check(str_contains($future, 'Public_URL::sanitize_same_site'), 'Future public links must use the same-site URL policy.');
$check(str_contains($js, 'localStorage'), 'Visual preferences must be browser-local.');
$check(str_contains($js, 'sessionStorage'), 'Scroll restoration must be session-local.');
$check(str_contains($js, 'navigator.connection'), 'Low-data browser hint integration missing.');
$check(str_contains($css, '@media (forced-colors: active)'), 'Forced-colors future styles missing.');
$check(str_contains($css, '@media (prefers-reduced-motion: reduce)'), 'Reduced-motion future styles missing.');
$check(str_contains($css, '@supports (view-transition-name: none)'), 'Progressive view-transition guard missing.');
$check(! preg_match('/https?:\/\//i', $css . "\n" . $js), 'Future client assets must not contain external remote URLs.');
for ($i = 1; $i <= 24; $i++) {
    $check(str_contains($docs, sprintf('F25-FUT-%02d', $i)), 'Written future addendum missing requirement ID ' . $i . '.');
}
$check(str_contains($docs, 'Hostinger staging'), 'Written addendum must preserve external staging truth boundary.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "PASS: Reviews 192-194 fresh post-review of File 25 Future Public Experience\n";
