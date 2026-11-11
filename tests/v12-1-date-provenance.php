<?php
declare(strict_types=1);

/** V12.1 V12-026: test actual private File25 projection method, not a mock DTO. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
if (! function_exists('home_url')) {
    function home_url(string $path = '/'): string { return 'https://example.test/' . ltrim($path, '/'); }
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $v): string {
        return trim((string) preg_replace('/[^a-z0-9_-]/', '', strtolower($v)), '-');
    }
}
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-future-public-experience.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';

$errors = [];
$check = static function (bool $ok, string $msg) use (&$errors): void {
    if (! $ok) { $errors[] = $msg; }
};
$method = new ReflectionMethod(\Sabri\PublicExperience\Future_Public_Experience::class, 'timeline_meta');
$method->setAccessible(true);
$published = gmdate('Y-m-d\TH:i:s\Z', time() - 172800);
$revised = gmdate('Y-m-d\TH:i:s\Z', time() - 86400);
$future = gmdate('Y-m-d\TH:i:s\Z', time() + 86400);
$old = gmdate('Y-m-d\TH:i:s\Z', time() - 259200);
$rows = [
    [
        'canonical_url' => 'https://example.test/publication/1/',
        'published_at' => $published,
        'verification_state' => 'verified',
        // This identifier is redacted by the actual public timeline DTO:
        'provider_id' => 'file-21',
    ],
    [
        'canonical_url' => 'https://example.test/publication/2/',
        'published_at' => $published,
        'updated_at' => $revised,
        'verification_state' => 'unverified',
        'provider_id' => 'file-21',
    ],
    [
        'canonical_url' => 'https://example.test/publication/3/',
        'published_at' => $published,
        'updated_at' => $published,
        'verification_state' => 'Verified',
    ],
    [
        'canonical_url' => 'https://example.test/publication/4/',
        'published_at' => $published,
        'updated_at' => $future,
    ],
    [
        'canonical_url' => 'https://example.test/publication/5/',
        'published_at' => $published,
        'updated_at' => 'invalid-date',
    ],
    [
        'canonical_url' => 'https://example.test/publication/6/',
        'published_at' => $published,
        'updated_at' => $old,
    ],
    [
        'canonical_url' => 'https://example.test/publication/7/',
        'published_at' => $future,
    ],
    [
        'canonical_url' => 'https://external.test/private/',
        'published_at' => $published,
        'verification_state' => 'verified',
    ],
];
$result = $method->invoke(null, ['items' => $rows]);
$check(is_array($result) && count($result) === 7, 'External URLs must be rejected without dropping valid same-site records.');
$check(($result[0]['published_at'] ?? '') !== '' && ($result[0]['updated_at'] ?? null) === '',
    'Publication-only record must be Published, not Updated.');
$check(($result[0]['source_verified'] ?? false) === true,
    'Explicit native public verified state must permit badge even when internal provider ID is absent.');
$check(($result[1]['published_at'] ?? '') !== '' && ($result[1]['updated_at'] ?? '') !== '',
    'A truly later modification must be separate from publication.');
$check(($result[1]['source_verified'] ?? true) === false,
    'An internal provider identifier alone is not verification authority.');
$check(($result[2]['updated_at'] ?? null) === '',
    'An update equal to the published instant must not show duplicate Updated status.');
$check(($result[2]['source_verified'] ?? true) === false,
    'A noncanonical verification-state case must fail closed.');
foreach ([3, 4, 5] as $i) {
    $check(($result[$i]['published_at'] ?? '') !== '' && ($result[$i]['updated_at'] ?? null) === '',
        'Invalid, future or earlier update must not erase the safe publication date: ' . $i);
}
$check(($result[6]['published_at'] ?? null) === '',
    'A clearly future-dated publication must not be falsely presented as already published.');
foreach ($result as $item) {
    $check(! array_key_exists('provider_id', $item) && ! array_key_exists('native_object_id', $item),
        'Ephemeral visual metadata must not expose private provider/native identifiers.');
}

// Use the actual canonical native normalized DTO rather than treating the
// visual method's fixture arrays as a substitute for native source authority.
$native = new \Sabri\PublicExperience\Normalized_Timeline_Item([
    'provider_id' => 'file-21',
    'provider_version' => '1.0.0',
    'native_object_type' => 'publication',
    'native_object_id' => 'publication-native-1',
    'author_id' => 1,
    'public_profile_id' => 1,
    'title' => 'Approved public post',
    'safe_excerpt' => 'Source excerpt',
    'canonical_url' => 'https://example.test/publication/native-1/',
    'published_at' => $published,
    'verification_state' => 'verified',
    'visibility_state' => 'public',
    'native_status' => 'published',
    'review_state' => 'published',
    'content_type' => 'post',
]);
$public = $native->to_public_array();
$check(! array_key_exists('provider_id', $public)
    && ! array_key_exists('native_object_id', $public)
    && ! array_key_exists('author_id', $public),
    'Actual normalized native public DTO must redact provider, object and actor identifiers.');
$check(($public['verification_state'] ?? '') === 'verified',
    'Actual native public DTO must retain the explicit public owner verification state.');
$fromRealDTO = $method->invoke(null, ['items' => [$public]]);
$check(count($fromRealDTO) === 1 && ($fromRealDTO[0]['source_verified'] ?? false) === true,
    'Exact approved native public DTO must produce a source-attributed badge without exposing private IDs.');

$css = (string) file_get_contents(dirname(__DIR__) . '/assets/css/design-system.css');
foreach (['--error', '--warning', '--permission-denied', '--success', '--offline'] as $kind) {
    $check(str_contains($css, '.sabri-ui-state' . $kind . ' .sabri-ui-state__icon'),
        'Visual status icon must have an explicit semantic tone: ' . $kind);
}
$check(str_contains($css, 'var(--sabri-visual-danger)')
    && str_contains($css, 'var(--sabri-visual-warning)')
    && str_contains($css, 'var(--sabri-visual-success)'),
    'State icon tones must follow the canonical semantic palette.');

if ($errors !== []) {
    fwrite(STDERR, "FAILED V12.1 Date and public provenance contract\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS V12.1: distinct publication/updated dates, server time, explicit verification and icon tones\n";
