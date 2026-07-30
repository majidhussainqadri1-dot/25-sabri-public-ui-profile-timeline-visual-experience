<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
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
                if (! $provider->is_available() || $provider->get_maturity_level() === 'disabled') {
                    continue;
                }

                $provider_items = $provider->get_public_author_items($author_id, $provider_query);
                foreach ($provider_items as $item) {
                    if (! $item instanceof Normalized_Timeline_Item) {
                        throw new \UnexpectedValueException('Timeline providers must return normalized timeline items.');
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

                    $key = $item->get('provider_id') . ':' . $item->get('native_object_type') . ':' . $item->get('native_object_id');
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
            static function (Normalized_Timeline_Item $left, Normalized_Timeline_Item $right): int {
                $pin = (int) $right->get('pin_weight') <=> (int) $left->get('pin_weight');
                if ($pin !== 0) {
                    return $pin;
                }

                $right_time = strtotime((string) $right->get('published_at')) ?: 0;
                $left_time = strtotime((string) $left->get('published_at')) ?: 0;

                return $right_time <=> $left_time;
            }
        );

        $slice = array_slice($items, $offset, $per_page + 1);
        $has_more = count($slice) > $per_page;
        if ($has_more) {
            array_pop($slice);
        }
        $truncated = $requested_page > $max_page
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
        if (! function_exists('home_url')) {
            return true;
        }

        $site = wp_parse_url(home_url('/'));
        $target = wp_parse_url($url);
        $same_host = is_array($site)
            && is_array($target)
            && strtolower((string) ($site['host'] ?? '')) === strtolower((string) ($target['host'] ?? ''))
            && (int) ($site['port'] ?? 0) === (int) ($target['port'] ?? 0);

        $allowed = (bool) apply_filters(
            'sabri_public_experience/canonical_url_allowed',
            $same_host,
            $url,
            $item
        );

        // The default native contract is same-site. Extensions may only narrow it.
        return $same_host && $allowed;
    }

    private function canonical_key(string $url): string
    {
        $parts = function_exists('wp_parse_url') ? wp_parse_url($url) : parse_url($url);
        if (! is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = (string) ($parts['path'] ?? '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . $port . $path . $query;
    }
}
