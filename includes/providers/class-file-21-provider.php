<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Read-only adapter for File 21's visibility-safe ProfileTimeline contract.
 *
 * File 21 remains the publication owner and performs the authoritative public,
 * review-state, object-authorization, password, and Safe Mode checks. File 25
 * only converts File 21's already-public serializer into its bounded display
 * pointer. No post body, private metadata, interaction ledger, or write path is
 * copied into File 25.
 */
final class File_21_Provider implements Timeline_Provider
{
    private const PROVIDER_ID = 'file-21';
    private const MINIMUM_SUPPORTED_VERSION = '1.0.3';
    private const NEXT_INCOMPATIBLE_VERSION = '2.0.0';
    private const MAX_CANDIDATES = 500;
    private const NATIVE_PAGE_SIZE = 20;

    public function get_provider_id(): string
    {
        return self::PROVIDER_ID;
    }

    public function get_provider_version(): string
    {
        $version = defined('SABRI_HNF_VERSION') ? trim((string) SABRI_HNF_VERSION) : '';

        return $version !== '' ? substr($version, 0, 64) : 'unknown';
    }

    public static function supports_version(string $version): bool
    {
        $version = trim($version);
        if ($version === '' || preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1) {
            return false;
        }

        return version_compare($version, self::MINIMUM_SUPPORTED_VERSION, '>=')
            && version_compare($version, self::NEXT_INCOMPATIBLE_VERSION, '<');
    }

    public function is_available(): bool
    {
        return class_exists('Sabri\\HomeNewsFeed\\ProfileTimeline')
            && is_callable(['Sabri\\HomeNewsFeed\\ProfileTimeline', 'query'])
            && defined('SABRI_HNF_VERSION')
            && self::supports_version($this->get_provider_version());
    }

    public function get_maturity_level(): string
    {
        if (! $this->is_available()) {
            return 'disabled';
        }

        // Source-contract integration is intentionally not staging or
        // production acceptance. Promotion requires exact multi-plugin staging.
        $requested = function_exists('apply_filters')
            ? (string) apply_filters(
                'sabri_public_experience/file_21_provider_maturity',
                'read-only',
                $this->get_provider_version()
            )
            : 'read-only';
        $requested = strtolower(trim($requested));

        // Extensions may degrade or disable this provider, never self-promote it.
        return in_array($requested, ['detected', 'read-only', 'degraded', 'disabled'], true)
            ? $requested
            : 'read-only';
    }

    /**
     * @param array<string,mixed> $query
     * @return list<Normalized_Timeline_Item>
     */
    public function get_public_author_items(int $author_id, array $query): array
    {
        if ($author_id <= 0 || ! $this->is_available() || $this->get_maturity_level() === 'disabled') {
            return [];
        }

        $candidate_limit = max(1, min(
            self::MAX_CANDIDATES,
            (int) ($query['candidate_limit'] ?? $query['per_page'] ?? self::NATIVE_PAGE_SIZE)
        ));
        $content_type = function_exists('sanitize_key')
            ? sanitize_key((string) ($query['content_type'] ?? ''))
            : preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) ($query['content_type'] ?? '')));
        if ($content_type !== '' && $content_type !== 'post') {
            return [];
        }

        $items = [];
        $native_page = 1;
        $maximum_pages = (int) ceil($candidate_limit / self::NATIVE_PAGE_SIZE);
        $maximum_pages = max(1, min(25, $maximum_pages));

        while (count($items) < $candidate_limit && $native_page <= $maximum_pages) {
            /** @var array<string,mixed> $result */
            $result = \Sabri\HomeNewsFeed\ProfileTimeline::query($author_id, [
                'page' => $native_page,
                // File 21 calculates its offset from page * per_page. Keep this
                // size stable across every native page, then trim locally.
                'per_page' => self::NATIVE_PAGE_SIZE,
            ]);

            if (($result['status'] ?? '') !== 'ok') {
                break;
            }

            $native_items = isset($result['items']) && is_array($result['items'])
                ? $result['items']
                : [];
            foreach ($native_items as $native_item) {
                if (! is_array($native_item)) {
                    continue;
                }
                $normalized = $this->normalize_public_item($native_item, $author_id);
                if ($normalized !== null) {
                    $items[] = $normalized;
                }
                if (count($items) >= $candidate_limit) {
                    break;
                }
            }

            if (empty($result['has_more']) || $native_items === []) {
                break;
            }
            $native_page++;
        }

        return $items;
    }

    public function get_canonical_url(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('canonical_url');
    }

    public function get_visibility_state(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('visibility_state');
    }

    /** @return list<string> */
    public function get_public_actions(Normalized_Timeline_Item $item): array
    {
        $actions = $item->get('available_actions');

        return is_array($actions) ? array_values(array_filter($actions, 'is_string')) : [];
    }

    /** @return array{views?:int,saves?:int,privacy_safe?:bool} */
    public function get_public_metrics(Normalized_Timeline_Item $item): array
    {
        unset($item);

        // File 21's current ProfileTimeline contract does not publish bounded
        // public views/saves metrics. Returning an empty projection keeps the
        // File 25 Most Viewed/Most Saved controls hidden instead of inventing
        // counters or reading File 21 storage directly.
        return [];
    }

    public function get_correction_state(Normalized_Timeline_Item $item): string
    {
        return (string) $item->get('correction_state');
    }

    public function register_sync_events(): void
    {
        // Current File 21 ProfileTimeline::query exposes no versioned public
        // sync-event contract. File 25 therefore performs read-through queries
        // only and must not guess native hooks or mutate File 21 state.
    }

    /** @return array<string,mixed> */
    public function get_health_status(): array
    {
        $version = $this->get_provider_version();

        return [
            'available' => $this->is_available(),
            'compatible_version' => self::supports_version($version),
            'provider_id' => self::PROVIDER_ID,
            'provider_version' => $version,
            'minimum_supported_version' => self::MINIMUM_SUPPORTED_VERSION,
            'next_incompatible_version' => self::NEXT_INCOMPATIBLE_VERSION,
            'maturity' => $this->get_maturity_level(),
            'contract' => 'Sabri\\HomeNewsFeed\\ProfileTimeline::query',
            'read_only' => true,
            'owns_native_content' => false,
        ];
    }

    /** @param array<string,mixed> $item */
    public function normalize_public_item(array $item, int $author_id): ?Normalized_Timeline_Item
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;
        $title = isset($item['title']) && is_scalar($item['title']) ? (string) $item['title'] : '';
        $excerpt = isset($item['excerpt']) && is_scalar($item['excerpt']) ? (string) $item['excerpt'] : '';
        $url = isset($item['url']) && is_scalar($item['url']) ? (string) $item['url'] : '';
        $published = isset($item['date_gmt']) && is_scalar($item['date_gmt']) ? (string) $item['date_gmt'] : '';
        if ($id <= 0 || trim($title) === '' || trim($url) === '' || trim($published) === '') {
            return null;
        }

        try {
            return new Normalized_Timeline_Item([
                'provider_id' => self::PROVIDER_ID,
                'provider_version' => $this->get_provider_version(),
                'native_object_type' => 'publication',
                'native_object_id' => (string) $id,
                'author_id' => $author_id,
                'public_profile_id' => $author_id,
                'title' => $title,
                'safe_excerpt' => $excerpt,
                'canonical_url' => $url,
                'published_at' => $published,
                'visibility_state' => 'public',
                'native_status' => 'publish',
                'content_type' => 'post',
                'language' => function_exists('get_locale') ? str_replace('_', '-', (string) get_locale()) : 'en-US',
                'media_type' => 'none',
                'verification_state' => 'native-owner-authorized',
                'review_state' => 'published',
                'correction_state' => 'none',
                'available_actions' => ['view', 'share'],
            ]);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
