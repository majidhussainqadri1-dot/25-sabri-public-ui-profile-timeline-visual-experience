<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

if (! class_exists(Public_URL::class)) {
    require_once __DIR__ . '/class-public-url.php';
}

final class Timeline_Service
{
    private const MAX_PER_PAGE = 50;
    private const MAX_CANDIDATES_PER_PROVIDER = 500;

    public function __construct(private Timeline_Registry $registry)
    {
    }

    /**
     * Providers receive a bounded candidate request beginning at their first
     * item. File 25 performs the global cross-provider sort and pagination.
     *
     * @param array<string,mixed> $query
     * @return array{items:list<array<string,mixed>>,page:int,per_page:int,has_more:bool,truncated:bool,provider_errors:list<string>}
     */
    public function get_for_author(int $author_id, array $query = []): array
    {
        $per_page = min(self::MAX_PER_PAGE, max(1, (int) ($query['per_page'] ?? 20)));
        $requested_page = max(1, (int) ($query['page'] ?? 1));
        $max_page = max(1, intdiv(self::MAX_CANDIDATES_PER_PROVIDER - 1, $per_page) + 1);
        $page = min($requested_page, $max_page);
        $offset = ($page - 1) * $per_page;
        $candidate_limit = min(self::MAX_CANDIDATES_PER_PROVIDER, $offset + $per_page + 1);
        $content_type = sanitize_key((string) ($query['content_type'] ?? ''));
        $provider_filter = sanitize_key((string) ($query['provider'] ?? ''));
        $items = [];
        $canonical_items = [];
        $errors = [];
        $provider_limit_reached = false;

        if ($author_id <= 0) {
            return [
                'items' => [],
                'page' => 1,
                'per_page' => $per_page,
                'has_more' => false,
                'truncated' => false,
                'provider_errors' => [],
            ];
        }

        $provider_query = [
            'page' => 1,
            'per_page' => $candidate_limit,
            'candidate_limit' => $candidate_limit,
            'requested_page' => $page,
            'content_type' => $content_type,
        ];

        foreach ($this->registry->all() as $provider_id => $provider) {
            if ($provider_filter !== '' && $provider_filter !== $provider_id) {
                continue;
            }

            try {
                $metadata = $this->registry->validated_metadata($provider, (string) $provider_id);
                if ($metadata === null) {
                    throw new \UnexpectedValueException('Timeline provider metadata or concrete identity changed after registration.');
                }
                if ($metadata['maturity'] === 'disabled' || ! $provider->is_available()) {
                    continue;
                }
                $provider_version = $metadata['version'];

                $provider_items = $provider->get_public_author_items($author_id, $provider_query);
                if (count($provider_items) > $candidate_limit) {
                    $provider_items = array_slice($provider_items, 0, $candidate_limit);
                    $provider_limit_reached = true;
                }

                foreach ($provider_items as $item) {
                    if (! $item instanceof Normalized_Timeline_Item) {
                        throw new \UnexpectedValueException('Timeline providers must return normalized timeline items.');
                    }
                    if ((string) $item->get('provider_id') !== $provider_id || (string) $item->get('provider_version') !== $provider_version) {
                        throw new \UnexpectedValueException('Timeline item provider identity does not match the registered provider.');
                    }
                    if ((int) $item->get('author_id') !== $author_id || (int) $item->get('public_profile_id') !== $author_id) {
                        throw new \UnexpectedValueException('Timeline provider returned an item for a different author or profile.');
                    }
                    if ($content_type !== '' && $item->get('content_type') !== $content_type) {
                        continue;
                    }
                    if (! $this->canonical_is_allowed((string) $item->get('canonical_url'), $item)) {
                        throw new \UnexpectedValueException('Timeline provider returned a non-canonical external destination.');
                    }

                    $key = $provider_id . ':' . $item->get('native_object_type') . ':' . $item->get('native_object_id');
                    $canonical_key = $this->canonical_key((string) $item->get('canonical_url'));
                    if (isset($items[$key]) || ($canonical_key !== '' && isset($canonical_items[$canonical_key]))) {
                        continue;
                    }

                    $items[$key] = $item;
                    if ($canonical_key !== '') {
                        $canonical_items[$canonical_key] = $key;
                    }
                }
            } catch (\Throwable $exception) {
                $errors[] = (string) $provider_id;
                do_action('sabri_public_experience/provider_error', (string) $provider_id, $exception);
            }
        }

        usort(
            $items,
            function (Normalized_Timeline_Item $left, Normalized_Timeline_Item $right): int {
                $pin = (int) $right->get('pin_weight') <=> (int) $left->get('pin_weight');
                if ($pin !== 0) {
                    return $pin;
                }

                $date = strcmp((string) $right->get('published_at'), (string) $left->get('published_at'));
                if ($date !== 0) {
                    return $date;
                }

                $left_identity = [
                    $this->canonical_key((string) $left->get('canonical_url')),
                    (string) $left->get('provider_id'),
                    (string) $left->get('native_object_type'),
                    (string) $left->get('native_object_id'),
                ];
                $right_identity = [
                    $this->canonical_key((string) $right->get('canonical_url')),
                    (string) $right->get('provider_id'),
                    (string) $right->get('native_object_type'),
                    (string) $right->get('native_object_id'),
                ];

                return strcmp(implode('|', $left_identity), implode('|', $right_identity));
            }
        );

        $slice = array_slice($items, $offset, $per_page + 1);
        $has_more = count($slice) > $per_page;
        if ($has_more) {
            array_pop($slice);
        }
        $truncated = $requested_page > $max_page
            || $provider_limit_reached
            || ($candidate_limit === self::MAX_CANDIDATES_PER_PROVIDER && count($items) >= self::MAX_CANDIDATES_PER_PROVIDER);

        return [
            'items' => array_map(
                static fn (Normalized_Timeline_Item $item): array => $item->to_public_array(),
                array_values($slice)
            ),
            'page' => $page,
            'per_page' => $per_page,
            'has_more' => $has_more,
            'truncated' => $truncated,
            'provider_errors' => array_values(array_unique($errors)),
        ];
    }

    private function canonical_is_allowed(string $url, Normalized_Timeline_Item $item): bool
    {
        $safe = Public_URL::sanitize_same_site($url, false);
        $parts = $safe !== '' ? parse_url($safe) : false;
        $detected = is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && trim((string) ($parts['host'] ?? '')) !== '';

        $filtered = (bool) apply_filters(
            'sabri_public_experience/canonical_url_allowed',
            $detected,
            $safe,
            $item
        );

        return $detected && $filtered;
    }

    private function canonical_key(string $url): string
    {
        return Public_URL::canonical_same_site_identity($url, false);
    }
}
