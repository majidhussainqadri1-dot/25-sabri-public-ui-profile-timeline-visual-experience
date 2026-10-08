<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;
use Sabri\PublicExperience\Contracts\Timeline_Provider;
use WP_User;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Reuses an already-reviewed optional public section projection as a bounded
 * author-centric timeline provider. Native modules remain authoritative; File
 * 25 does not query private tables or create a second content backend.
 */
final class Section_Timeline_Provider implements Timeline_Provider
{
    private const MAX_CANDIDATES = 500;

    public function __construct(
        private Profile_Section_Provider $section_provider,
        private Profile_Repository $profiles,
        private string $content_type
    ) {
        $this->content_type = sanitize_key($this->content_type);
    }

    public function get_provider_id(): string
    {
        return $this->section_provider->get_id();
    }

    public function get_provider_version(): string
    {
        return $this->section_provider->get_version();
    }

    public function is_available(): bool
    {
        return $this->content_type !== '' && $this->section_provider->is_available();
    }

    public function get_maturity_level(): string
    {
        return $this->section_provider->get_maturity_level();
    }

    public function get_content_type(): string
    {
        return $this->content_type;
    }

    /** @return list<Normalized_Timeline_Item> */
    public function get_public_author_items(int $author_id, array $query): array
    {
        if ($author_id <= 0 || ! $this->is_available()) {
            return [];
        }
        $requested_type = sanitize_key((string) ($query['content_type'] ?? ''));
        if ($requested_type !== '' && $requested_type !== $this->content_type) {
            return [];
        }

        $user = function_exists('get_user_by') ? get_user_by('id', $author_id) : null;
        if (! $user instanceof WP_User) {
            return [];
        }
        $profile = $this->profiles->get_public_profile($user);
        if (! is_array($profile) || ! $this->section_provider->supports_profile($profile)) {
            return [];
        }

        $requested = isset($query['candidate_limit']) && is_scalar($query['candidate_limit'])
            ? (int) $query['candidate_limit']
            : (int) ($query['per_page'] ?? 20);
        $limit = max(1, min(self::MAX_CANDIDATES, $requested));

        $rows = $this->section_provider->query($author_id, $profile, [
            'section' => $this->section_provider->get_section(),
            'limit' => $limit,
            'surface' => 'timeline',
        ]);
        if (! is_array($rows)) {
            return [];
        }

        $items = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $native_id = $row['native_object_id'] ?? null;
            $native_type = sanitize_key((string) ($row['native_object_type'] ?? $row['type'] ?? 'public-item'));
            $title = is_scalar($row['title'] ?? null) ? (string) $row['title'] : '';
            $url = is_scalar($row['url'] ?? null) ? (string) $row['url'] : '';
            $published = is_scalar($row['published_at'] ?? null) ? (string) $row['published_at'] : '';
            if ((! is_int($native_id) && ! is_string($native_id))
                || trim((string) $native_id) === ''
                || $native_type === ''
                || trim($title) === ''
                || trim($url) === ''
                || trim($published) === ''
            ) {
                continue;
            }

            $actions = match ($this->content_type) {
                'video', 'reel' => ['watch', 'share'],
                'pdf' => ['read', 'download', 'share'],
                default => ['read', 'share'],
            };
            $media_type = match ($this->content_type) {
                'video' => 'video',
                'reel' => 'reel',
                'pdf' => 'document',
                default => ! empty($row['image_url']) ? 'image' : 'none',
            };

            try {
                $items[] = new Normalized_Timeline_Item([
                    'provider_id' => $this->get_provider_id(),
                    'provider_version' => $this->get_provider_version(),
                    'native_object_type' => $native_type,
                    'native_object_id' => (string) $native_id,
                    'author_id' => $author_id,
                    'public_profile_id' => $author_id,
                    'title' => $title,
                    'safe_excerpt' => is_scalar($row['excerpt'] ?? null) ? (string) $row['excerpt'] : '',
                    'canonical_url' => $url,
                    'published_at' => $published,
                    'visibility_state' => 'public',
                    'native_status' => 'published',
                    'content_type' => $this->content_type,
                    'topic' => is_scalar($row['badge'] ?? null) ? (string) $row['badge'] : '',
                    'language' => function_exists('get_locale') ? str_replace('_', '-', (string) get_locale()) : 'en-US',
                    'thumbnail_reference' => is_scalar($row['image_url'] ?? null) ? (string) $row['image_url'] : '',
                    'media_type' => $media_type,
                    'verification_state' => 'native-owner-public-projection',
                    'review_state' => 'published',
                    'correction_state' => 'none',
                    'available_actions' => $actions,
                ]);
            } catch (\Throwable $exception) {
                if (function_exists('do_action')) {
                    do_action('sabri_public_experience/provider_item_error', $this->get_provider_id(), (string) $native_id, $exception);
                }
            }
        }

        return $items;
    }

    /** @return array<string,mixed> */
    public function get_health_status(): array
    {
        return [
            'provider_id' => $this->get_provider_id(),
            'provider_version' => $this->get_provider_version(),
            'available' => $this->is_available(),
            'maturity' => $this->get_maturity_level(),
            'content_type' => $this->content_type,
            'read_only' => true,
            'owns_native_content' => false,
            'source' => 'optional-public-section-projection',
        ];
    }
}
