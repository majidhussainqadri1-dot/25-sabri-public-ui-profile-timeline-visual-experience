<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}
if (! function_exists('sanitize_key')) {
    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
}
if (! function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
    }
}

require_once dirname(__DIR__) . '/includes/contracts/interface-timeline-provider.php';
require_once dirname(__DIR__) . '/includes/class-normalized-timeline-item.php';
require_once dirname(__DIR__) . '/includes/class-timeline-registry.php';
require_once dirname(__DIR__) . '/includes/class-timeline-service.php';

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;
use Sabri\PublicExperience\Timeline_Registry;
use Sabri\PublicExperience\Timeline_Service;

$failures = [];

function check(bool $condition, string $message): void
{
    global $failures;
    if (! $condition) {
        $failures[] = $message;
    }
}

/** @param array<string,mixed> $overrides */
function timeline_item(array $overrides = []): Normalized_Timeline_Item
{
    static $sequence = 0;
    $sequence++;

    return new Normalized_Timeline_Item(array_merge([
        'provider_id' => 'file-21',
        'provider_version' => '1.0.0',
        'native_object_type' => 'publication',
        'native_object_id' => (string) $sequence,
        'author_id' => 1,
        'public_profile_id' => 1,
        'title' => 'Public title ' . $sequence,
        'safe_excerpt' => 'Public excerpt',
        'canonical_url' => 'https://example.test/publication/' . $sequence . '/',
        'published_at' => sprintf('2026-07-%02dT12:00:00+00:00', min(30, $sequence)),
        'visibility_state' => 'public',
        'native_status' => 'published',
        'content_type' => 'post',
        'review_state' => 'published',
    ], $overrides));
}

$item = timeline_item([
    'native_object_id' => '77',
    'title' => '<b>Public title</b>',
    'safe_excerpt' => '<script>unsafe()</script><style>.bad{}</style>Excerpt',
    'canonical_url' => 'https://example.test/publication/77/',
]);
check($item->get('title') === 'Public title', 'Title must be reduced to plain presentation text.');
check($item->get('safe_excerpt') === 'Excerpt', 'Script and style bodies must be removed from excerpts.');
check($item->get('visibility_state') === 'public', 'Visibility must remain public.');
check(! array_key_exists('author_id', $item->to_public_array()), 'Public timeline arrays must redact author IDs.');
check(! array_key_exists('native_object_id', $item->to_public_array()), 'Public timeline arrays must redact native object IDs.');
check(! array_key_exists('provider_id', $item->jsonSerialize()), 'JSON serialization must redact provider internals.');

foreach ([
    ['field' => 'visibility_state', 'value' => 'private', 'message' => 'Private items must be rejected.'],
    ['field' => 'native_status', 'value' => 'draft', 'message' => 'Draft items must be rejected.'],
    ['field' => 'review_state', 'value' => 'pending', 'message' => 'Pending-review items must be rejected.'],
    ['field' => 'native_object_id', 'value' => '', 'message' => 'Blank native object IDs must be rejected.'],
    ['field' => 'title', 'value' => '', 'message' => 'Blank public titles must be rejected.'],
] as $case) {
    $rejected = false;
    try {
        timeline_item([$case['field'] => $case['value']]);
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    check($rejected, $case['message']);
}

foreach ([
    'ftp://example.test/file',
    'https://user:pass@example.test/private',
    'https://example.test/article#private-fragment',
] as $url) {
    $rejected = false;
    try {
        timeline_item(['canonical_url' => $url]);
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    check($rejected, 'Unsafe canonical URL must be rejected: ' . $url);
}

$bounded = timeline_item([
    'title' => str_repeat('A', 400),
    'safe_excerpt' => str_repeat('B', 1500),
    'pin_weight' => 9000,
]);
check(strlen((string) $bounded->get('title')) <= 300, 'Timeline title must be bounded.');
check(strlen((string) $bounded->get('safe_excerpt')) <= 1200, 'Timeline excerpt must be bounded.');
check($bounded->get('pin_weight') === 1000, 'Pin weight must be bounded.');

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

$invalid_maturity_rejected = false;
try {
    $registry->register(new class implements Timeline_Provider {
        public function get_provider_id(): string { return 'invalid-maturity'; }
        public function get_provider_version(): string { return '1.0.0'; }
        public function is_available(): bool { return true; }
        public function get_maturity_level(): string { return 'unknown'; }
        public function get_public_author_items(int $author_id, array $query): array { return []; }
        public function get_health_status(): array { return []; }
    });
} catch (InvalidArgumentException) {
    $invalid_maturity_rejected = true;
}
check($invalid_maturity_rejected, 'Provider registry must reject unknown maturity states.');

$duplicate_registry = new Timeline_Registry();
foreach (['first-provider', 'second-provider'] as $index => $provider_id) {
    $duplicate_registry->register(new class($provider_id, $index) implements Timeline_Provider {
        public function __construct(private string $id, private int $index) {}
        public function get_provider_id(): string { return $this->id; }
        public function get_provider_version(): string { return '1.0.0'; }
        public function is_available(): bool { return true; }
        public function get_maturity_level(): string { return 'read-only'; }
        public function get_health_status(): array { return []; }
        public function get_public_author_items(int $author_id, array $query): array
        {
            return [timeline_item([
                'provider_id' => $this->id,
                'native_object_id' => (string) ($this->index + 1),
                'author_id' => $author_id,
                'public_profile_id' => $author_id,
                'title' => 'Canonical duplicate',
                'canonical_url' => 'https://example.test/same-publication/',
            ])];
        }
    });
}
$deduped = (new Timeline_Service($duplicate_registry))->get_for_author(1);
check(count($deduped['items']) === 1, 'Timeline service must suppress cross-provider canonical duplicates.');

$mismatch_registry = new Timeline_Registry();
$mismatch_registry->register(new class implements Timeline_Provider {
    public function get_provider_id(): string { return 'wrong-author'; }
    public function get_provider_version(): string { return '1.0.0'; }
    public function is_available(): bool { return true; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function get_health_status(): array { return []; }
    public function get_public_author_items(int $author_id, array $query): array
    {
        return [timeline_item([
            'provider_id' => 'wrong-author',
            'author_id' => $author_id + 1,
            'public_profile_id' => $author_id + 1,
        ])];
    }
});
$mismatch = (new Timeline_Service($mismatch_registry))->get_for_author(1);
check($mismatch['items'] === [], 'Items belonging to another author must not enter the requested timeline.');
check($mismatch['provider_errors'] === ['wrong-author'], 'Author mismatch must be recorded as a provider error.');

$pagination_registry = new Timeline_Registry();
$pagination_provider = new class implements Timeline_Provider {
    /** @var array<string,mixed> */
    public array $last_query = [];
    public function get_provider_id(): string { return 'pagination-provider'; }
    public function get_provider_version(): string { return '1.0.0'; }
    public function is_available(): bool { return true; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function get_health_status(): array { return []; }
    public function get_public_author_items(int $author_id, array $query): array
    {
        $this->last_query = $query;
        $items = [];
        for ($index = 1; $index <= (int) $query['candidate_limit']; $index++) {
            $items[] = timeline_item([
                'provider_id' => 'pagination-provider',
                'native_object_id' => (string) $index,
                'author_id' => $author_id,
                'public_profile_id' => $author_id,
                'title' => 'Item ' . $index,
                'canonical_url' => 'https://example.test/pagination/' . $index . '/',
                'published_at' => sprintf('2026-07-30T12:%02d:00+00:00', min(59, $index)),
            ]);
        }
        return $items;
    }
};
$pagination_registry->register($pagination_provider);
$page_two = (new Timeline_Service($pagination_registry))->get_for_author(1, ['page' => 2, 'per_page' => 2]);
check($pagination_provider->last_query['page'] === 1, 'Providers must receive a first-page candidate query for global pagination.');
check($pagination_provider->last_query['candidate_limit'] === 5, 'Candidate limit must cover the global page offset plus look-ahead.');
check(count($page_two['items']) === 2, 'Global page two must contain the requested number of items.');
check($page_two['has_more'] === true, 'Global page two must preserve look-ahead state.');
check(! array_key_exists('provider_id', $page_two['items'][0]), 'Timeline service output must be public-safe.');

if ($failures !== []) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "PASS: File 25 reviewed foundation contract tests\n";
