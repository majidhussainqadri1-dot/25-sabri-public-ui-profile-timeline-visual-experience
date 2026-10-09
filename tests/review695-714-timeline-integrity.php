<?php
declare(strict_types=1);
require __DIR__ . '/review39-53-timeline-bootstrap.php';

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;
use Sabri\PublicExperience\Timeline_Registry;
use Sabri\PublicExperience\Timeline_Service;

final class R695_Bounded_Provider implements Timeline_Provider
{
    public int $last_limit = 0;
    /** @param list<Normalized_Timeline_Item> $items */
    public function __construct(private string $id, private array $items) {}
    public function get_provider_id(): string { return $this->id; }
    public function get_provider_version(): string { return '1.0.0'; }
    public function is_available(): bool { return true; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function get_public_author_items(int $author_id, array $query): array
    {
        $this->last_limit = (int) ($query['candidate_limit'] ?? 0);
        return array_slice($this->items, 0, $this->last_limit);
    }
    public function get_health_status(): array { return ['available' => true]; }
}

$items = [];
for ($i = 1; $i <= 40; $i++) {
    $date = $i === 40 ? '2025-05-01T00:00:00Z'
        : gmdate('Y-m-d\TH:i:s\Z', strtotime('2026-06-30T00:00:00Z') - $i * 86400);
    $items[] = r3953_item('bounded-a', (string) $i, [
        'published_at' => $date,
        'updated_at' => $date,
        'topic' => $i === 40 ? 'RareTopic' : 'Common',
        'language' => $i === 40 ? 'ur-PK' : 'en-US',
    ]);
}
$provider = new R695_Bounded_Provider('bounded-a', $items);
$registry = new Timeline_Registry();
$registry->register($provider);
$service = new Timeline_Service($registry);

$first = $service->get_for_author(7, ['page' => 1, 'per_page' => 20]);
r3953_assert(count($first['items']) === 20 && $first['has_more'] === true,
    'Ordinary first-page look-ahead must be honored.');
r3953_assert($first['truncated'] === false,
    'Ordinary look-ahead must not falsely report the 500-item safety cap.');
r3953_assert($provider->last_limit === 21,
    'Unfiltered first page must not eagerly load the whole archive.');

$topic = $service->get_for_author(7, ['topic' => 'RareTopic']);
r3953_assert(count($topic['items']) === 1 && $topic['items'][0]['title'] === 'Public item 40',
    'Topic filtering must find older authorized matches inside the safe 500-item window.');
r3953_assert($provider->last_limit === 500 && $topic['truncated'] === false,
    'Refinement must retrieve a bounded full candidate window without false truncation.');

$language = $service->get_for_author(7, ['language' => 'ur-PK']);
r3953_assert(count($language['items']) === 1 && $language['items'][0]['title'] === 'Public item 40',
    'Language filtering must not exclude older matching native items.');

$year = $service->get_for_author(7, ['year' => '2025']);
r3953_assert(count($year['items']) === 1 && $year['items'][0]['title'] === 'Public item 40',
    'Year filtering must search the bounded source archive, not just page one.');

$oldest = $service->get_for_author(7, ['sort' => 'oldest']);
r3953_assert(count($oldest['items']) === 20 && $oldest['items'][0]['title'] === 'Public item 40',
    'Oldest sorting must use all bounded candidates, not the latest 21 only.');

$manyRegistry = new Timeline_Registry();
foreach (['many-a' => 1, 'many-b' => 301] as $id => $start) {
    $native = [];
    for ($i = $start; $i < $start + 300; $i++) {
        $native[] = r3953_item($id, (string) $i);
    }
    $manyRegistry->register(new R695_Bounded_Provider($id, $native));
}
$multi = (new Timeline_Service($manyRegistry))->get_for_author(7, ['page' => 26, 'per_page' => 20]);
r3953_assert(count($multi['items']) === 20 && $multi['has_more'] === true
    && $multi['truncated'] === false,
    'Cross-provider pagination beyond one provider cap must remain available.');

$overflow = (new Timeline_Service($manyRegistry))->get_for_author(7, [
    'page' => PHP_INT_MAX, 'per_page' => 20,
]);
r3953_assert($overflow['items'] === [] && $overflow['has_more'] === false
    && $overflow['truncated'] === true && $overflow['page'] === PHP_INT_MAX,
    'Unbounded syntactically valid page integers must never overflow offsets.');

$start = gmdate('Y-m-d\TH:i:s\Z', time() - 3600);
$end = gmdate('Y-m-d\TH:i:s\Z', time() + 3600);
$audit = [
    'reference' => 'review695-approved-pin',
    'actor_id' => 7,
    'reason' => 'Approved time-bound official highlight',
    'surface' => 'profile-timeline',
    'start_at' => $start,
    'end_at' => $end,
];
$approved = r3953_item('bounded-a', 'pin1', ['pin_weight' => 50, 'pin_audit' => $audit]);
r3953_assert($approved->get('pin_weight') === 50
    && ! array_key_exists('pin_audit', $approved->to_public_array()),
    'Active approved pin must retain ordering weight without exposing internal audit fields.');
$future = r3953_item('bounded-a', 'pin2', ['pin_weight' => 50,
    'pin_audit' => array_replace($audit, [
        'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 3600),
        'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 7200),
    ])]);
r3953_assert($future->get('pin_weight') === 0, 'Future pins must not rank as active.');
$expired = r3953_item('bounded-a', 'pin3', ['pin_weight' => 50,
    'pin_audit' => array_replace($audit, [
        'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 7200),
        'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600),
    ])]);
r3953_assert($expired->get('pin_weight') === 0, 'Expired pins must not rank as active.');
$wrongSurface = r3953_item('bounded-a', 'pin4', ['pin_weight' => 50,
    'pin_audit' => array_replace($audit, ['surface' => 'home-feed'])]);
r3953_assert($wrongSurface->get('pin_weight') === 0,
    'Pins approved for a foreign presentation surface must not rank this profile timeline.');

echo "PASS: Reviews 695-714 bounded timeline retrieval, pagination and audited pin windows\n";
