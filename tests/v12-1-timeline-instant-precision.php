<?php
declare(strict_types=1);

/** Real normalized Timeline DTO: chronology before wire-second reduction. */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
require_once dirname(__DIR__) . '/includes/class-public-url.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';

$base = [
    'provider_id' => 'file-21',
    'provider_version' => '1.0.0',
    'native_object_type' => 'publication',
    'native_object_id' => 'source-1',
    'author_id' => 1,
    'public_profile_id' => 1,
    'title' => 'Approved public contribution',
    'safe_excerpt' => 'Public excerpt',
    'canonical_url' => 'https://example.test/publication/1/',
    'published_at' => '2026-08-03T12:00:00.900000Z',
    'visibility_state' => 'public',
    'native_status' => 'published',
    'review_state' => 'published',
    'content_type' => 'post',
];
$errors = [];
$check = static function (bool $ok, string $msg) use (&$errors): void {
    if (! $ok) { $errors[] = $msg; }
};
$instantiate = static function (array $override) use ($base): \Sabri\PublicExperience\Normalized_Timeline_Item {
    return new \Sabri\PublicExperience\Normalized_Timeline_Item(array_replace($base, $override));
};
$reject = static function (array $override, string $reason) use ($instantiate, $check): void {
    try {
        $instantiate($override);
        $check(false, $reason);
    } catch (\InvalidArgumentException) {
        $check(true, $reason);
    }
};

$reject(['updated_at' => '2026-08-03T12:00:00.100000Z'],
    'Earlier microsecond update must not pass after seconds truncation.');
$reject(['updated_at' => '2026-08-03T17:00:00.100000+05:00'],
    'Earlier offset microsecond update must be compared as an absolute instant.');
$reject(['updated_at' => '2026-08-03T11:59:59.999999Z'],
    'Earlier second update must remain prohibited.');
$reject(['updated_at' => '2026-02-30T12:00:00Z'],
    'Invalid calendar dates must remain rejected.');

$later = $instantiate(['updated_at' => '2026-08-03T12:00:00.900001Z']);
$check($later->get('published_at') === '2026-08-03T12:00:00Z'
    && $later->get('updated_at') === '2026-08-03T12:00:00Z',
    'Valid later microsecond update must preserve the existing second-level public wire format.');
$same = $instantiate(['updated_at' => '2026-08-03T12:00:00.900000Z']);
$check($same->get('updated_at') === '2026-08-03T12:00:00Z',
    'Equal absolute instant remains a valid source record.');

$legacy = $instantiate([
    'published_at' => '2026-08-03 12:00:00',
    'updated_at' => '2026-08-03T12:00:01Z',
]);
$check($legacy->get('published_at') === '2026-08-03T12:00:00Z'
    && $legacy->get('updated_at') === '2026-08-03T12:00:01Z',
    'Legacy timezone-naive WordPress timestamps remain UTC and interoperable.');
$reject(['published_at' => '2026-08-03 12:00:01',
    'updated_at' => '2026-08-03T12:00:00Z'],
    'Legacy WordPress timestamp chronology must remain strict.');

if ($errors !== []) {
    fwrite(STDERR, "FAILED V12.1 sub-second Timeline chronology\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS V12.1 native Timeline chronology preserves precise validated source ordering\n";
