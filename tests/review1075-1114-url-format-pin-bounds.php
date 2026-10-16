<?php

declare(strict_types=1);

use Sabri\PublicExperience\Normalized_Timeline_Item;
use Sabri\PublicExperience\Public_URL;

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
if (! function_exists('home_url')) {
    function home_url(string $path = '/'): string
    {
        return 'https://example.test' . ($path === '' ? '/' : $path);
    }
}
if (! function_exists('esc_url_raw')) {
    function esc_url_raw(string $url, array $protocols = []): string
    {
        return $url;
    }
}
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';

$failures = [];
$check = static function (bool $passed, string $label) use (&$failures): void {
    if (! $passed) {
        $failures[] = $label;
    }
};

// A path is an identity component: invisible bidi/format controls must not
// survive raw, once-encoded, or recursively percent-encoded URL spellings.
$bad_paths = [
    '/public/' . "\u{202E}" . 'private',
    '/public/%E2%80%AEprivate',
    '/public/%25E2%2580%25AEprivate',
    '/public/%E2%80%8Fhidden',
    '/public/%E2%81%A6hidden',
    '/public/%EF%BB%BFhidden',
    '/public/%C2%ADhidden',
];
foreach ($bad_paths as $path) {
    $check(Public_URL::sanitize_same_site($path, false) === '', 'Unsafe format-control path accepted: ' . $path);
    $check(Public_URL::canonical_same_site_identity($path, false) === '', 'Unsafe format-control canonical identity accepted: ' . $path);
}
$check(Public_URL::sanitize_same_site('/%D9%85%D9%82%D8%A7%D9%84%DB%81/', false) !== '', 'Legitimate percent-encoded Urdu path rejected.');
$check(Public_URL::sanitize_same_site('/مقالہ/', false) !== '', 'Legitimate literal Urdu path rejected.');
$check(Public_URL::sanitize_same_site('https://example.test/knowledge/', false) !== '', 'Safe same-origin absolute path rejected.');
$check(Public_URL::sanitize_same_site('/public/%252e%252e/private', false) === '', 'Recursively encoded dot segment accepted.');

$base = [
    'provider_id' => 'native-posts',
    'provider_version' => '1.0.0',
    'native_object_type' => 'post',
    'native_object_id' => 'sample-7',
    'author_id' => 7,
    'public_profile_id' => 7,
    'title' => 'Public sample',
    'safe_excerpt' => 'Approved excerpt',
    'canonical_url' => 'https://example.test/posts/sample-7/',
    'published_at' => '2026-10-16T00:00:00Z',
    'visibility_state' => 'public',
    'native_status' => 'published',
    'content_type' => 'post',
    'review_state' => 'published',
];

// Historical and already-tested contract: out-of-range integer pin weights
// are bounded, then lose all priority if their audit provenance is missing.
foreach ([1001, -1, PHP_INT_MAX] as $bounded_weight) {
    $item = new Normalized_Timeline_Item(array_merge($base, ['pin_weight' => $bounded_weight]));
    $check($item->get('pin_weight') === 0, 'Unaudited bounded pin retained priority: ' . (string) $bounded_weight);
}
foreach (['1001', 1.5] as $invalid_weight) {
    $rejected = false;
    try {
        new Normalized_Timeline_Item(array_merge($base, ['pin_weight' => $invalid_weight]));
    } catch (\InvalidArgumentException) {
        $rejected = true;
    }
    $check($rejected, 'Noninteger pin weight was accepted: ' . (string) $invalid_weight);
}

$unpinned = new Normalized_Timeline_Item(array_merge($base, ['pin_weight' => 0]));
$check($unpinned->get('pin_weight') === 0, 'Zero-weight unpinned item changed.');
$without_audit = new Normalized_Timeline_Item(array_merge($base, ['pin_weight' => 1000]));
$check($without_audit->get('pin_weight') === 0, 'A pin without provenance must lose its priority.');

$audit = [
    'reference' => 'approved-pin-7',
    'actor_id' => 1,
    'reason' => 'Time-bounded public editorial highlight',
    'surface' => 'profile-timeline',
    'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600),
    'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 3600),
];
$approved = new Normalized_Timeline_Item(array_merge($base, ['pin_weight' => 1000, 'pin_audit' => $audit]));
$check($approved->get('pin_weight') === 1000, 'Valid maximum bounded and audited pin rejected.');
$wrong_surface = $audit;
$wrong_surface['surface'] = 'global-feed';
$unapproved = new Normalized_Timeline_Item(array_merge($base, ['pin_weight' => 1000, 'pin_audit' => $wrong_surface]));
$check($unapproved->get('pin_weight') === 0, 'Wrong-surface pin was allowed to influence order.');

if ($failures !== []) {
    fwrite(STDERR, "Reviews 1075–1114 regression FAILED:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}
echo "Reviews 1075–1114: Unicode path identity and historical pin-weight contract passed.\n";
