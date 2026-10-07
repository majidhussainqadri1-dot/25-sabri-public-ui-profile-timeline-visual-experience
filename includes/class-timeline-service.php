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
        $per_page = self::exact_positive_integer($query['per_page'] ?? 20, self::MAX_PER_PAGE);
        $requested_page = self::exact_positive_integer($query['page'] ?? 1, PHP_INT_MAX);
        $content_type = self::exact_optional_key($query['content_type'] ?? '');
        $provider_filter = self::exact_optional_key($query['provider'] ?? '');
        $topic = self::exact_optional_text($query['topic'] ?? '', 120);
        $year = self::exact_optional_year($query['year'] ?? '');
        $language = self::exact_optional_language($query['language'] ?? '');
        $sort = self::exact_optional_choice($query['sort'] ?? 'latest', ['latest', 'oldest', 'most-viewed', 'most-saved']);
        $review_state = self::exact_optional_key($query['review_state'] ?? '');
        $source_state = self::exact_optional_choice($query['source_state'] ?? '', ['', 'detected', 'read-only', 'staging-accepted', 'production-accepted', 'degraded']);
        $raw_search = $query['search'] ?? '';
        $search = is_string($raw_search) ? Plan_Completion::normalize_search_query($raw_search) : '';
        $search_invalid = $raw_search !== '' && (! is_string($raw_search) || $search === '');
        if ($per_page === null || $requested_page === null || $content_type === null || $provider_filter === null
            || $topic === null || $year === null || $language === null || $sort === null
            || $review_state === null || $source_state === null || $search_invalid
        ) {
            return [
                'items' => [],
                'page' => 1,
                'per_page' => 20,
                'has_more' => false,
                'truncated' => false,
                'provider_errors' => [],
                'available_sorts' => ['latest', 'oldest'],
                'effective_sort' => 'latest',
            ];
        }
        $max_page = max(1, intdiv(self::MAX_CANDIDATES_PER_PROVIDER - 1, $per_page) + 1);
        $page = $requested_page;
        $offset = ($page - 1) * $per_page;
        $candidate_limit = $requested_page > $max_page
            ? self::MAX_CANDIDATES_PER_PROVIDER
            : min(self::MAX_CANDIDATES_PER_PROVIDER, $offset + $per_page + 1);
        $items = [];
        $canonical_items = [];
        $errors = [];
        $provider_limit_reached = false;
        $metric_cache = [];
        $available_sorts = ['latest', 'oldest'];

        if ($author_id <= 0) {
            return [
                'items' => [],
                'page' => 1,
                'per_page' => $per_page,
                'has_more' => false,
                'truncated' => false,
                'provider_errors' => [],
                'available_sorts' => ['latest', 'oldest'],
                'effective_sort' => 'latest',
            ];
        }

        $provider_query = [
            'page' => 1,
            'per_page' => $candidate_limit,
            'candidate_limit' => $candidate_limit,
            'requested_page' => $requested_page,
            'content_type' => $content_type,
            'topic' => $topic,
            'year' => $year,
            'language' => $language,
            'sort' => $sort,
            'review_state' => $review_state,
            'source_state' => $source_state,
            'search' => $search,
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
                if ($metadata['maturity'] === 'disabled'
                    || ! in_array($metadata['maturity'], ['read-only', 'staging-accepted', 'production-accepted'], true)
                    || ($source_state !== '' && $metadata['maturity'] !== $source_state)
                    || ! $provider->is_available()
                ) {
                    continue;
                }
                $provider_version = $metadata['version'];

                $provider_items = $provider->get_public_author_items($author_id, $provider_query);
                if (count($provider_items) >= $candidate_limit) {
                    $provider_limit_reached = true;
                }
                if (count($provider_items) > $candidate_limit) {
                    $provider_items = array_slice($provider_items, 0, $candidate_limit);
                }

                // Treat one provider response as an atomic public projection. A
                // malformed late item must invalidate the complete untrusted batch;
                // otherwise earlier items would survive a provider contract failure.
                $provider_items_by_key = [];
                $provider_canonical_items = [];
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
                    if ($topic !== '' && ! self::text_equals((string) $item->get('topic'), $topic)) {
                        continue;
                    }
                    if ($year > 0 && substr((string) $item->get('published_at'), 0, 4) !== (string) $year) {
                        continue;
                    }
                    if ($language !== '' && strcasecmp((string) $item->get('language'), $language) !== 0) {
                        continue;
                    }
                    if ($review_state !== '' && (string) $item->get('review_state') !== $review_state) {
                        continue;
                    }
                    if ($search !== '' && ! Plan_Completion::timeline_item_matches_search($item->to_public_array(), $search)) {
                        continue;
                    }
                    if (! $this->canonical_is_allowed((string) $item->get('canonical_url'), $item)) {
                        throw new \UnexpectedValueException('Timeline provider returned a non-canonical external destination.');
                    }

                    $key = $provider_id . ':' . $item->get('native_object_type') . ':' . $item->get('native_object_id');
                    $canonical_key = $this->canonical_key((string) $item->get('canonical_url'));
                    if (isset($provider_items_by_key[$key])
                        || isset($items[$key])
                        || ($canonical_key !== '' && (isset($provider_canonical_items[$canonical_key]) || isset($canonical_items[$canonical_key])))
                    ) {
                        continue;
                    }

                    $provider_items_by_key[$key] = $item;
                    try {
                        $metrics = $provider->get_public_metrics($item);
                    } catch (\Throwable) {
                        $metrics = [];
                    }
                    $clean_metrics = [];
                    foreach (['views', 'saves'] as $metric_key) {
                        if (isset($metrics[$metric_key]) && is_int($metrics[$metric_key]) && $metrics[$metric_key] >= 0 && $metrics[$metric_key] <= 1000000000) {
                            $clean_metrics[$metric_key] = $metrics[$metric_key];
                            $sort_key = $metric_key === 'views' ? 'most-viewed' : 'most-saved';
                            if (! in_array($sort_key, $available_sorts, true)) {
                                $available_sorts[] = $sort_key;
                            }
                        }
                    }
                    if ($clean_metrics !== []) {
                        $metric_cache[$key] = $clean_metrics;
                    }
                    if ($canonical_key !== '') {
                        $provider_canonical_items[$canonical_key] = $key;
                    }
                }
                foreach ($provider_items_by_key as $key => $item) {
                    $items[$key] = $item;
                }
                foreach ($provider_canonical_items as $canonical_key => $key) {
                    $canonical_items[$canonical_key] = $key;
                }
            } catch (\Throwable $exception) {
                $errors[] = (string) $provider_id;
                do_action('sabri_public_experience/provider_error', (string) $provider_id, $exception);
            }
        }

        $effective_sort = in_array($sort, $available_sorts, true) ? $sort : 'latest';
        $metric_name = $effective_sort === 'most-viewed' ? 'views' : ($effective_sort === 'most-saved' ? 'saves' : '');

        uasort(
            $items,
            function (Normalized_Timeline_Item $left, Normalized_Timeline_Item $right) use ($effective_sort, $metric_name, $metric_cache): int {
                if ($metric_name !== '') {
                    $left_key = (string) $left->get('provider_id') . ':' . (string) $left->get('native_object_type') . ':' . (string) $left->get('native_object_id');
                    $right_key = (string) $right->get('provider_id') . ':' . (string) $right->get('native_object_type') . ':' . (string) $right->get('native_object_id');
                    $left_metric = $metric_cache[$left_key][$metric_name] ?? null;
                    $right_metric = $metric_cache[$right_key][$metric_name] ?? null;
                    if ($left_metric !== null || $right_metric !== null) {
                        if ($left_metric === null) { return 1; }
                        if ($right_metric === null) { return -1; }
                        $metric_cmp = $right_metric <=> $left_metric;
                        if ($metric_cmp !== 0) { return $metric_cmp; }
                    }
                }

                $pin = (int) $right->get('pin_weight') <=> (int) $left->get('pin_weight');
                if ($pin !== 0) {
                    return $pin;
                }

                $date = $effective_sort === 'oldest'
                    ? strcmp((string) $left->get('published_at'), (string) $right->get('published_at'))
                    : strcmp((string) $right->get('published_at'), (string) $left->get('published_at'));
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

        $slice =        $slice = $requested_page > $max_page ? [] : array_slice($items, $offset, $per_page + 1);
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
            'available_sorts' => array_values($available_sorts),
            'effective_sort' => $effective_sort,
        ];
    }


    private static function exact_positive_integer(mixed $value, int $maximum): ?int
    {
        if (is_int($value)) {
            return $value >= 1 && $value <= $maximum ? $value : null;
        }
        if (! is_string($value)
            || preg_match('/^[1-9][0-9]*$/', $value) !== 1
            || strlen($value) > 19
        ) {
            return null;
        }
        $integer = (int) $value;

        return (string) $integer === $value && $integer <= $maximum ? $integer : null;
    }

    private static function exact_optional_key(mixed $value): ?string
    {
        if (! is_string($value) || strlen($value) > 64) {
            return null;
        }
        if ($value === '') {
            return '';
        }
        $canonical = function_exists('sanitize_key')
            ? sanitize_key($value)
            : trim(preg_replace('/[^a-z0-9_-]/', '-', strtolower(trim($value))) ?? '', '-');

        return hash_equals($canonical, $value) ? $value : null;
    }

    private static function exact_optional_text(mixed $value, int $maximum): ?string
    {
        if (! is_string($value) || strlen($value) > $maximum) {
            return null;
        }
        $clean = trim(function_exists('wp_strip_all_tags') ? wp_strip_all_tags($value, true) : strip_tags($value));
        if ($clean === '') {
            return '';
        }
        return preg_match('/[\x00-\x1F\x7F]/', $clean) === 1 ? null : $clean;
    }

    private static function exact_optional_year(mixed $value): ?int
    {
        if ($value === '' || $value === null) {
            return 0;
        }
        if (is_int($value)) {
            $year = $value;
        } elseif (is_string($value) && preg_match('/^[12][0-9]{3}$/', $value) === 1) {
            $year = (int) $value;
        } else {
            return null;
        }
        return $year >= 1900 && $year <= (int) gmdate('Y') + 1 ? $year : null;
    }

    private static function exact_optional_language(mixed $value): ?string
    {
        if (! is_string($value) || strlen($value) > 35) {
            return null;
        }
        if ($value === '') {
            return '';
        }
        return preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/', $value) === 1 ? $value : null;
    }

    /** @param list<string> $allowed */
    private static function exact_optional_choice(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    private static function text_equals(string $left, string $right): bool
    {
        $left = trim($left);
        $right = trim($right);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($left, 'UTF-8') === mb_strtolower($right, 'UTF-8');
        }
        return strtolower($left) === strtolower($right);
    }

    private function canonical_is_allowed(string $url, Normalized_Timeline_Item $item): bool
    {
        $safe = Public_URL::sanitize_same_site($url, false);
        $parts = $safe !== '' ? parse_url($safe) : false;
        $detected = is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && trim((string) ($parts['host'] ?? '')) !== '';

        $filtered = apply_filters(
            'sabri_public_experience/canonical_url_allowed',
            $detected,
            $safe,
            $item
        );

        if ($filtered !== true) {
            return false;
        }

        return $detected && $filtered;
    }

    private function canonical_key(string $url): string
    {
        return Public_URL::canonical_same_site_identity($url, false);
    }
}
