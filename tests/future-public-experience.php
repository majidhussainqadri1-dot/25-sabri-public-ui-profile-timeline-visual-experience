<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook_name, mixed $value, mixed ...$args): mixed
    {
        return $value;
    }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\\-]/', '-', strtolower($value)), '-');
    }
}
if (! function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed
    {
        return $value;
    }
}

require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-future-public-experience.php';

use Sabri\PublicExperience\Future_Public_Experience;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$contract = Future_Public_Experience::contract();
$features = (array) ($contract['future_enhancements'] ?? []);
$check(($contract['contract_version'] ?? '') === '1.0.0', 'Future public experience contract version must be current.');
$check(($contract['owner'] ?? '') === 'file-25', 'File 25 must own the future public presentation layer.');
$check(($contract['shell_owner'] ?? '') === 'file-20', 'File 20 must remain shell owner.');
$check(($contract['security_governance_owner'] ?? '') === 'file-24', 'File 24 must remain security governance owner.');
$check(($contract['global_search_discovery_ranking_owner'] ?? '') === 'file-26', 'File 26 must remain global search/discovery/ranking owner.');
$check(($contract['duplicate_backend_allowed'] ?? true) === false, 'Future enhancements must not create a duplicate backend.');
$check(($contract['raw_sensitive_data_allowed'] ?? true) === false, 'Future enhancements must not expose raw sensitive data.');
$check(($contract['raw_analytics_warehouse_allowed'] ?? true) === false, 'File 25 must not own a raw analytics warehouse.');
$check(($contract['third_party_tracking_qr_allowed'] ?? true) === false, 'Tracking QR services must remain forbidden.');
$check(($contract['native_action_execution_required'] ?? false) === true, 'Native action execution must remain mandatory.');
$check(count($features) === 24, 'Exactly 24 approved future enhancements are required.');
for ($i = 1; $i <= 24; $i++) {
    $id = sprintf('F25-FUT-%02d', $i);
    $check(isset($features[$id]), 'Missing future enhancement ID: ' . $id);
}

$quality = Future_Public_Experience::quality_report([
    'display_name' => 'Example Doctor',
    'canonical_url' => 'https://example.test/doctors/example/',
    'headline' => 'Homeopathic Doctor',
    'bio' => 'Public professional biography.',
    'contacts' => [],
    'available_sections' => ['overview', 'timeline'],
    'class' => 'doctor',
    'verified' => true,
]);
$check(($quality['score'] ?? 0) === 100, 'Complete safe profile fixture should score 100.');
$check(($quality['public_ranking_effect'] ?? true) === false, 'Quality score must not affect public ranking.');

$css = (string) file_get_contents(dirname(__DIR__) . '/assets/css/future-public-experience.css');
$js = (string) file_get_contents(dirname(__DIR__) . '/assets/js/future-public-experience.js');
$check(! preg_match('/https?:\/\//i', $css), 'Future CSS must not load remote assets.');
$check(! preg_match('/https?:\/\//i', $js), 'Future JavaScript must not embed remote destinations.');
$check(! preg_match('/\b(?:eval|document\.write)\s*\(/i', $js), 'Future JavaScript must not use eval/document.write.');
$check(str_contains($css, '@container'), 'Adaptive public experience must include container-query styling.');
$check(str_contains($css, 'prefers-reduced-motion'), 'Reduced-motion styling must remain present.');
$check(str_contains($js, 'sessionStorage'), 'Back/forward restoration must remain session-scoped.');
$check(str_contains($js, 'saveData'), 'Low-data mode must consume the browser data-saving hint when available.');

$_GET = [];
$year = Future_Public_Experience::timeline_year_request();
$check(($year['year'] ?? -1) === 0 && ($year['error'] ?? 'x') === '', 'Absent timeline year must be neutral.');

$_GET['spux_year'] = '2026';
$year = Future_Public_Experience::timeline_year_request();
$check(($year['year'] ?? 0) === 2026 && ($year['error'] ?? 'x') === '', 'Valid timeline year must be accepted.');

$_GET['spux_year'] = '../2026';
$year = Future_Public_Experience::timeline_year_request();
$check(($year['year'] ?? -1) === 0 && ($year['error'] ?? '') === 'invalid_year', 'Malformed timeline year must fail closed.');

// V12.1 V12-026: publication must never masquerade as a content update.
$timelineMetaMethod = new ReflectionMethod(Future_Public_Experience::class, 'timeline_meta');
$timelineMetaMethod->setAccessible(true); // PHP 8.0 requires explicit access for private-method regression.
$timelineFixtures = ['items' => [
    ['canonical_url' => 'https://example.test/publication/a/', 'published_at' => '2026-10-01T10:00:00Z'],
    ['canonical_url' => 'https://example.test/publication/b/', 'published_at' => '2026-09-01T10:00:00Z', 'updated_at' => '2026-10-03T11:30:00Z'],
    ['canonical_url' => 'https://example.test/publication/c/', 'published_at' => '2026-10-05T10:00:00Z', 'updated_at' => 'not-a-timestamp'],
]];
$dateRows = $timelineMetaMethod->invoke(null, $timelineFixtures);
$check(count($dateRows) === 3, 'Public timeline metadata should preserve three valid canonical URLs.');
$check(($dateRows[0]['published_at'] ?? '') === '2026-10-01T10:00:00+00:00' && ($dateRows[0]['updated_at'] ?? null) === '', 'Publication-only timestamp must not be projected as updated_at.');
$check(($dateRows[1]['published_at'] ?? '') === '2026-09-01T10:00:00+00:00' && ($dateRows[1]['updated_at'] ?? '') === '2026-10-03T11:30:00+00:00', 'Distinct genuine publication and update timestamps must survive projection.');
$check(($dateRows[2]['updated_at'] ?? null) === '' && ($dateRows[2]['published_at'] ?? '') === '2026-10-05T10:00:00+00:00', 'Invalid update timestamp must not erase trustworthy publication time.');
$check(str_contains($js, "t('publishedLabel', 'Published')"), 'The timeline JS must label a publication date as Published.');
$check(str_contains($js, 'const revised = validDate(meta.updated_at)'), 'JS must select a credible update date separately from published date.');
$check(str_contains($js, 'existingPublished.textContent') && str_contains($js, 'existingDate.getTime() === date.getTime()'), 'Publication dates must label the server-rendered time rather than duplicate it.');
$check(str_contains((string) file_get_contents(dirname(__DIR__) . '/includes/class-future-public-experience.php'), "'publishedLabel' => __('Published'"), 'Published must be an internationalizable UI label.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Future Public Experience 24-enhancement contract\n";
