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

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 Future Public Experience 24-enhancement contract\n";
