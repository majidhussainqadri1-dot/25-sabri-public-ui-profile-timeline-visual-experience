<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
require_once dirname(__DIR__) . '/includes/class-timeline-registry.php';

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;
use Sabri\PublicExperience\Timeline_Registry;

$failures = [];

function check(bool $condition, string $message): void
{
    global $failures;
    if (! $condition) {
        $failures[] = $message;
    }
}

$item = new Normalized_Timeline_Item([
    'provider_id' => 'file-21',
    'provider_version' => '1.0.0',
    'native_object_type' => 'publication',
    'native_object_id' => '77',
    'author_id' => 1,
    'public_profile_id' => 1,
    'title' => '<b>Public title</b>',
    'safe_excerpt' => '<script>unsafe()</script>Excerpt',
    'canonical_url' => 'https://example.test/publication/77/',
    'published_at' => '2026-07-30T12:00:00+00:00',
    'visibility_state' => 'public',
    'native_status' => 'published',
    'content_type' => 'post',
]);

check($item->get('title') === 'Public title', 'Title must be stripped to plain presentation text.');
check(! str_contains((string) $item->get('safe_excerpt'), '<script>'), 'Excerpt must not contain script markup.');
check($item->get('visibility_state') === 'public', 'Visibility must remain public.');

$rejected_private = false;
try {
    new Normalized_Timeline_Item([
        'provider_id' => 'bad', 'provider_version' => '1', 'native_object_type' => 'post',
        'native_object_id' => '1', 'author_id' => 1, 'public_profile_id' => 1,
        'title' => 'Private', 'safe_excerpt' => '', 'canonical_url' => 'https://example.test/private',
        'published_at' => '2026-07-30T12:00:00+00:00', 'visibility_state' => 'private',
        'native_status' => 'draft', 'content_type' => 'post',
    ]);
} catch (InvalidArgumentException) {
    $rejected_private = true;
}
check($rejected_private, 'Private items must be rejected by the normalized public contract.');

$rejected_non_http = false;
try {
    new Normalized_Timeline_Item([
        'provider_id' => 'bad', 'provider_version' => '1', 'native_object_type' => 'post',
        'native_object_id' => '2', 'author_id' => 1, 'public_profile_id' => 1,
        'title' => 'FTP', 'safe_excerpt' => '', 'canonical_url' => 'ftp://example.test/file',
        'published_at' => '2026-07-30T12:00:00+00:00', 'visibility_state' => 'public',
        'native_status' => 'publish', 'content_type' => 'post',
    ]);
} catch (InvalidArgumentException) {
    $rejected_non_http = true;
}
check($rejected_non_http, 'Non-HTTP canonical URLs must be rejected.');
check(strlen((string) (new Normalized_Timeline_Item([
    'provider_id' => 'bounded', 'provider_version' => '1', 'native_object_type' => 'post',
    'native_object_id' => '3', 'author_id' => 1, 'public_profile_id' => 1,
    'title' => str_repeat('A', 400), 'safe_excerpt' => '', 'canonical_url' => 'https://example.test/bounded',
    'published_at' => '2026-07-30T12:00:00+00:00', 'visibility_state' => 'public',
    'native_status' => 'publish', 'content_type' => 'post',
]))->get('title')) <= 300, 'Timeline title must be bounded.');

$provider = new class implements Timeline_Provider {
    public function get_provider_id(): string { return 'test-provider'; }
    public function get_provider_version(): string { return '1.0.0'; }
    public function is_available(): bool { return true; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function get_public_author_items(int $author_id, array $query): array { return []; }
    public function get_health_status(): array { return ['available' => true]; }
};

$registry = new Timeline_Registry();
$registry->register($provider);
check($registry->get('test-provider') === $provider, 'Provider registry must return the registered provider.');

$duplicate_rejected = false;
try {
    $registry->register($provider);
} catch (InvalidArgumentException) {
    $duplicate_rejected = true;
}
check($duplicate_rejected, 'Provider registry must reject duplicate IDs.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 foundation contract tests\n";
