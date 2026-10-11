<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-timeline-bootstrap.php';

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;
use Sabri\PublicExperience\Timeline_Registry;
use Sabri\PublicExperience\Timeline_Service;

final class R995_Atomic_Provider implements Timeline_Provider
{
    /** @param array<int,mixed> $items */
    public function __construct(
        private string $id,
        private array $items,
        private bool $throw_on_second_metric = false
    ) {}
    public function get_provider_id(): string { return $this->id; }
    public function get_provider_version(): string { return '1.0.0'; }
    public function is_available(): bool { return true; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function get_public_author_items(int $author_id, array $query): array
    {
        unset($author_id);
        return array_slice($this->items, 0, (int) ($query['candidate_limit'] ?? 20));
    }
    public function normalize_public_item(array $native_item, int $author_id): ?Normalized_Timeline_Item
    {
        unset($native_item, $author_id);
        return null; // Test supplies normalized candidates directly.
    }
    public function get_canonical_url(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('canonical_url');
    }
    public function get_visibility_state(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('visibility_state');
    }
    public function get_public_actions(Normalized_Timeline_Item $item): array
    {
        return (array) $item->get('available_actions');
    }
    public function get_public_metrics(Normalized_Timeline_Item $item): array
    {
        if ($this->throw_on_second_metric && (string) $item->get('native_object_id') === '2') {
            throw new RuntimeException('intentional late native metrics failure');
        }
        return ['views' => 101, 'privacy_safe' => true];
    }
    public function get_correction_state(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('correction_state');
    }
    public function register_sync_events(): void {}
    public function get_health_status(): array { return ['available' => true]; }
}

// A 500-candidate provider whose last row is malformed must not leave a
// phantom 500-item truncation or contaminated Most Viewed availability.
$invalid = [];
for ($i = 1; $i <= 499; $i++) {
    $invalid[] = r3953_item('invalid-batch', (string) $i);
}
$invalid[] = 'malformed final row';
$registry = new Timeline_Registry();
$registry->register(new R995_Atomic_Provider('invalid-batch', $invalid));
$result = (new Timeline_Service($registry))->get_for_author(7, ['sort' => 'most_viewed']);
r3953_assert($result['items'] === [], 'Failed 500-row batch must publish no items.');
r3953_assert($result['provider_errors'] === ['invalid-batch'], 'Failed provider must be reported.');
r3953_assert($result['truncated'] === false, 'Failed provider may not create phantom truncation.');
r3953_assert($result['available_metric_sorts'] === [], 'Failed provider may not advertise ranking metrics.');
r3953_assert($result['sort'] === 'latest', 'Unbacked metric sort must fall back to Latest.');

// Successful owner projections should survive another provider throwing
// partway through its metric validation; no partial failed-provider metrics.
$registry = new Timeline_Registry();
$registry->register(new R995_Atomic_Provider('good-owner', [r3953_item('good-owner', '11')]));
$registry->register(new R995_Atomic_Provider('late-metric-failure', [
    r3953_item('late-metric-failure', '1'),
    r3953_item('late-metric-failure', '2'),
], true));
$result = (new Timeline_Service($registry))->get_for_author(7, ['sort' => 'most_viewed']);
r3953_assert(count($result['items']) === 1, 'Successful provider must survive other provider rejection.');
r3953_assert($result['provider_errors'] === ['late-metric-failure'], 'Metrics failure must be isolated.');
r3953_assert($result['available_metric_sorts'] === ['most_viewed'], 'Only successful provider may expose metrics.');

// Authorization to publish is not proof that the underlying source is verified.
$registry = new Timeline_Registry();
$registry->register(new R995_Atomic_Provider('source-owner', [
    r3953_item('source-owner', '1', ['verification_state' => 'native-owner-authorized']),
    r3953_item('source-owner', '2', ['verification_state' => 'verified']),
    r3953_item('source-owner', '3', ['verification_state' => 'unverified']),
]));
$service = new Timeline_Service($registry);
$verified = $service->get_for_author(7, ['source_state' => 'verified']);
$unverified = $service->get_for_author(7, ['source_state' => 'unverified']);
r3953_assert(count($verified['items']) === 1
    && $verified['items'][0]['title'] === 'Public item 2',
    'Only explicit verified source evidence may pass verified filter.');
r3953_assert(count($unverified['items']) === 2,
    'Owner authorization and unknown provenance must remain unverified.');

echo "PASS: Review 995 provider truncation/metric atomicity and explicit source verification\n";
