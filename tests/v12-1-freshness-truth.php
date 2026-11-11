<?php
declare(strict_types=1);

/** V12-026: current content freshness requires a real, nonfuture source date. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
$GLOBALS['v12_freshness_payload'] = [];
if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
        return $hook === 'sabri_public_experience/content_freshness'
            ? $GLOBALS['v12_freshness_payload'] : $value;
    }
}
require_once dirname(__DIR__) . '/includes/class-future-public-experience.php';

$method = new ReflectionMethod(\Sabri\PublicExperience\Future_Public_Experience::class, 'freshness');
$method->setAccessible(true);
$errors = [];
$check = static function (bool $ok, string $name) use (&$errors): void {
    if (! $ok) { $errors[] = $name; }
};

$past = gmdate('Y-m-d\TH:i:s\Z', time() - 86400);
$GLOBALS['v12_freshness_payload'] = ['updated_at' => $past, 'label' => 'Content source'];
$accepted = $method->invoke(null, ['class' => 'founder']);
$check(is_array($accepted) && ! empty($accepted['updated_at']), 'Past owner date must remain available.');
$check(($accepted['label'] ?? '') === 'Content source', 'Owner note should remain an auxiliary label.');

$GLOBALS['v12_freshness_payload'] = ['updated_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400), 'label' => 'Live now'];
$check($method->invoke(null, []) === [], 'Far-future update must never appear as already refreshed.');

$GLOBALS['v12_freshness_payload'] = ['updated_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 120), 'label' => 'Clock skew'];
$check(($method->invoke(null, [])['updated_at'] ?? '') !== '', 'Allow small server clock skew.');

$GLOBALS['v12_freshness_payload'] = ['updated_at' => '2026-02-30T12:00:00Z'];
$check($method->invoke(null, []) === [], 'Reject invalid calendar date.');

$GLOBALS['v12_freshness_payload'] = null;
$check($method->invoke(null, []) === [], 'Reject malformed owner payload.');

$js = file_get_contents(dirname(__DIR__) . '/assets/js/future-public-experience.js');
$check(is_string($js), 'Client source is readable.');
if (is_string($js)) {
    $check(! str_contains($js, 'fresh.label || '), 'Owner label must not replace a visible date.');
    $check(str_contains($js, "const time = element('time', '', ")
        && str_contains($js, "t('publicInformationUpdated', 'Public information updated')")
        && str_contains($js, 'date.toLocaleDateString()'),
        'Visible profile updated date must remain present.');
    $check(substr_count($js, 'date.getTime() <= Date.now() + 300000') >= 2,
        'Profile and timeline presentation must reject far-future timestamps.');
}
if ($errors !== []) {
    fwrite(STDERR, "FAILED File25 V12-026 freshness\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS File25 V12-026: date truth, future skew, source note and visible date\n";
