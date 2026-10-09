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
        $year_filter = self::exact_optional_year($query['year'] ?? '');
        $language_filter = self::exact_optional_language($query['language'] ?? '');
        $topic_filter = self::exact_optional_text($query['topic'] ?? '', 120);
        $sort = self::exact_sort($query['sort'] ?? 'latest');
        $review_state = self::exact_review_state($query['review_state'] ?? '');
        $source_state = self::exact_source_state($query['source_state'] ?? '');
        $raw_search = $query['search'] ?? '';
        $search = is_string($raw_search) ? Plan_Completion::normalize_search_query($raw_search) : '';
        $search_invalid = $raw_search !== '' && (! is_string($raw_search) || $search === '');
        if ($per_page === null
            || $requested_page === null
            || $content_type === null
            || $provider_filter === null
            || $year_filter === null
            || $language_filter === null
            || $topic_filter === null
            || $sort === null
            || $review_state === null
            || $source_state === null
            || $search_invalid
        ) {
            return [
                'items' => [],
                'page' => 1,
                'per_page' => 20,
                'has_more' => false,
                'truncated' => false,
                'provider_errors' => [],
            ];
        }
        // The 500-item boundary belongs to each provider, not the merged
        // timeline. Two providers with 500 authorized items each may support
        // pages beyond the first provider's 500-item window.
        $provider_count = max(1, count($this->registry->all()));
        $max_page = max(1, (int) ceil(($provider_count * self::MAX_CANDIDATES_PER_PROVIDER) / $per_page));
        $per_provider_page_limit = max(1, (int) ceil(self::MAX_CANDIDATES_PER_PROVIDER / $per_page));
        $page = $requested_page;
        if ($page > $max_page) {
            // Check the page bound BEFORE calculating an offset, which would
            // overflow for a syntactically valid but enormous page integer.
            return [
                'items' => [],
                'page' => $page,
                'per_page' => $per_page,
                'has_more' => false,
                'truncated' => true,
                'provider_errors' => [],
            ];
        }
        $offset = ($page - 1) * $per_page;
        // Post-retrieval refinements and oldest ordering require the full
        // bounded candidate window. Otherwise matching older items can be
        // omitted and "oldest" sorts only the latest few records.
        $needs_full_window = $year_filter !== ''
            || $language_filter !== ''
            || $topic_filter !== ''
            || $content_type === 'corrections'
            || $review_state !== ''
            || $source_state !== ''
            || $search !== ''
            || in_array($sort, ['oldest', 'most_viewed', 'most_saved'], true);
        $candidate_limit = ($requested_page > $per_provider_page_limit || $needs_full_window)
            ? self::MAX_CANDIDATES_PER_PROVIDER
            : min(self::MAX_CANDIDATES_PER_PROVIDER, $offset + $per_page + 1);
        $items = [];
        $canonical_items = [];
        $errors = [];
        $provider_limit_reached = false;
        $metric_scores = [];
        $metric_sorts = ['most_viewed' => false, 'most_saved' => false];

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

        $corrections_only = $content_type === 'corrections';
        $provider_query = [
            'page' => 1,
            'per_page' => $candidate_limit,
            'candidate_limit' => $candidate_limit,
            'requested_page' => $requested_page,
            // Corrections are a File 25 projection filter over owner-authorized
            // items; do not require native owners to invent a content type.
            'content_type' => $corrections_only ? '' : $content_type,
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
                    || ! $provider->is_available()
                ) {
                    continue;
                }
                $provider_version = $metadata['version'];

                $provider_items = $provider->get_public_author_items($author_id, $provider_query);
                // Reaching the requested look-ahead (e.g. 21 for page 1)
                // is normal pagination, not a 500-item safety truncation.
                if ($candidate_limit === self::MAX_CANDIDATES_PER_PROVIDER
                    && count($provider_items) >= self::MAX_CANDIDATES_PER_PROVIDER
                ) {
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
                    $canonical_from_owner = $provider->get_canonical_url($item);
                    $visibility_from_owner = $provider->get_visibility_state($item);
                    $actions_from_owner = $provider->get_public_actions($item);
                    $correction_from_owner = $provider->get_correction_state($item);
                    if (! is_string($canonical_from_owner)
                        || ! hash_equals((string) $item->get('canonical_url'), $canonical_from_owner)
                        || ! hash_equals('public', $visibility_from_owner)
                        || ! is_array($actions_from_owner)
                        || $actions_from_owner !== (array) $item->get('available_actions')
                        || ! is_string($correction_from_owner)
                        || ! hash_equals((string) $item->get('correction_state'), $correction_from_owner)
                    ) {
                        throw new \UnexpectedValueException('Timeline provider owner projections disagree with the normalized public item.');
                    }

                    if ($corrections_only) {
                        if (! in_array((string) $item->get('correction_state'), ['corrected', 'retracted'], true)) {
                            continue;
                        }
                    } elseif ($content_type !== '' && $item->get('content_type') !== $content_type) {
                        continue;
                    }
                    if ($review_state !== '' && (string) $item->get('review_state') !== $review_state) {
                        continue;
                    }
                    if ($source_state !== '') {
                        $verified_source = (string) $item->get('verification_state') !== 'unverified';
                        if (($source_state === 'verified') !== $verified_source) {
                            continue;
                        }
                    }
                    if ($year_filter !== '' && substr((string) $item->get('published_at'), 0, 4) !== $year_filter) {
                        continue;
                    }
                    if ($language_filter !== '' && strcasecmp((string) $item->get('language'), $language_filter) !== 0) {
                        continue;
                    }
                    if ($topic_filter !== '' && strcasecmp(trim((string) $item->get('topic')), $topic_filter) !== 0) {
                        continue;
                    }
                    if ($search !== '' && ! Plan_Completion::timeline_item_matches_search($item->to_public_array(), $search)) {
                        continue;
                    }
                    if (! $this->canonical_is_allowed((string) $item->get('canonical_url'), $item)) {
                        throw new \UnexpectedValueException('Timeline provider returned a non-canonical external destination.');
                    }

                    $key = $provider_id . ':' . $item->get('native_object_type') . ':' . $item->get('native_object_id');
                    $metrics = self::public_metric_scores($provider->get_public_metrics($item));
                    if (isset($metrics['views'])) {
                        $metric_scores[$key]['most_viewed'] = $metrics['views'];
                        $metric_sorts['most_viewed'] = true;
                    }
                    if (isset($metrics['saves'])) {
                        $metric_scores[$key]['most_saved'] = $metrics['saves'];
                        $metric_sorts['most_saved'] = true;
                    }
                    $canonical_key = $this->canonical_key((string) $item->get('canonical_url'));
                    if (isset($provider_items_by_key[$key])
                        || isset($items[$key])
                        || ($canonical_key !== '' && (isset($provider_canonical_items[$canonical_key]) || isset($canonical_items[$canonical_key])))
                    ) {
                        continue;
                    }

                    $provider_items_by_key[$key] = $item;
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

        if (in_array($sort, ['most_viewed', 'most_saved'], true) && empty($metric_sorts[$sort])) {
            $sort = 'latest';
        }

        usort(
            $items,
            function (Normalized_Timeline_Item $left, Normalized_Timeline_Item $right) use ($sort, $metric_scores): int {
                $pin = (int) $right->get('pin_weight') <=> (int) $left->get('pin_weight');
                if ($pin !== 0) {
                    return $pin;
                }

                if (in_array($sort, ['most_viewed', 'most_saved'], true)) {
                    $left_key = (string) $left->get('provider_id') . ':' . $left->get('native_object_type') . ':' . $left->get('native_object_id');
                    $right_key = (string) $right->get('provider_id') . ':' . $right->get('native_object_type') . ':' . $right->get('native_object_id');
                    $metric = ($metric_scores[$right_key][$sort] ?? -1) <=> ($metric_scores[$left_key][$sort] ?? -1);
                    if ($metric !== 0) { return $metric; }
                }

                $date = $sort === 'oldest'
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

        $slice = array_slice($items, $offset, $per_page + 1);
        $has_more = count($slice) > $per_page;
        if ($has_more) {
            array_pop($slice);
        }
        $truncated = $provider_limit_reached;

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
            'sort' => $sort,
            'available_metric_sorts' => array_values(array_keys(array_filter($metric_sorts))),
        ];
    }


    /** @return array<string,string> */
    public function available_provider_filters(): array
    {
        $labels = [];
        foreach ($this->registry->all() as $provider_id => $provider) {
            try {
                $metadata = $this->registry->validated_metadata($provider, (string) $provider_id);
                if ($metadata === null
                    || ! in_array($metadata['maturity'], ['read-only', 'staging-accepted', 'production-accepted'], true)
                    || ! $provider->is_available()
                ) {
                    continue;
                }
                $label = ucwords(str_replace(['-', '_'], ' ', (string) $provider_id));
                if (preg_match('/^File (\d+)$/i', $label, $matches) === 1) {
                    $label = 'File ' . $matches[1];
                }
                $labels[(string) $provider_id] = $label;
            } catch (\Throwable) {
                continue;
            }
        }
        ksort($labels, SORT_STRING);

        return $labels;
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

    private static function exact_optional_year(mixed $value): ?string
    {
        if ($value === '') {
            return '';
        }
        if (! is_string($value) || preg_match('/^(?:19|20|21)\\d{2}$/', $value) !== 1) {
            return null;
        }
        $year = (int) $value;
        $maximum = (int) gmdate('Y') + 1;

        return $year >= 1900 && $year <= $maximum ? $value : null;
    }

    private static function exact_optional_language(mixed $value): ?string
    {
        if ($value === '') {
            return '';
        }
        if (! is_string($value) || strlen($value) > 35 || preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private static function exact_optional_text(mixed $value, int $maximum): ?string
    {
        if ($value === '') {
            return '';
        }
        if (! is_string($value)) {
            return null;
        }
        $value = trim(wp_strip_all_tags($value));
        if ($value === '' || strlen($value) > $maximum || preg_match('/[\\x00-\\x1F\\x7F]/', $value) === 1) {
            return null;
        }

        return $value;
    }

    private static function exact_sort(mixed $value): ?string
    {
        return is_string($value) && in_array($value, ['latest', 'oldest', 'most_viewed', 'most_saved'], true) ? $value : null;
    }

    private static function exact_review_state(mixed $value): ?string
    {
        if ($value === '') { return ''; }
        return is_string($value)
            && in_array($value, ['published', 'approved', 'reviewed', 'not-required', 'corrected', 'retracted'], true)
            ? $value
            : null;
    }

    private static function exact_source_state(mixed $value): ?string
    {
        if ($value === '') { return ''; }
        return is_string($value) && in_array($value, ['verified', 'unverified'], true) ? $value : null;
    }

    /** @return array{views?:int,saves?:int} */
    private static function public_metric_scores(mixed $value): array
    {
        if (! is_array($value) || ($value['privacy_safe'] ?? false) !== true) { return []; }
        $clean = [];
        foreach (['views', 'saves'] as $key) {
            $metric = $value[$key] ?? null;
            if (is_int($metric) && $metric >= 0) { $clean[$key] = $metric; }
        }
        return $clean;
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
