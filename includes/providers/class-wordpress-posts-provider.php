<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;
use WP_Post;
use WP_Query;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only compatibility provider.
 *
 * File 21 remains the canonical publication owner. This provider supplies a
 * safe baseline for already-public WordPress posts until File 21 registers its
 * richer production provider.
 */
final class WordPress_Posts_Provider implements Timeline_Provider
{
    public function get_provider_id(): string
    {
        return 'wordpress-posts';
    }

    public function get_provider_version(): string
    {
        return SABRI_PUBLIC_EXPERIENCE_VERSION;
    }

    public function is_available(): bool
    {
        return post_type_exists('post');
    }

    public function get_maturity_level(): string
    {
        return 'read-only';
    }

    public function get_public_author_items(int $author_id, array $query): array
    {
        $candidate_limit = min(500, max(1, (int) ($query['candidate_limit'] ?? $query['per_page'] ?? 20)));
        $requested_post_types = array_slice((array) apply_filters(
            'sabri_public_experience/wordpress_post_types',
            ['post']
        ), 0, 10);
        // This compatibility provider belongs only to WordPress core posts.
        // Native modules register their own owner-aware providers; a filter may
        // disable the fallback but cannot expand it into foreign post types.
        $post_types = [];
        foreach ($requested_post_types as $post_type) {
            if (is_string($post_type) && hash_equals('post', $post_type) && post_type_exists('post')) {
                $object = get_post_type_object('post');
                if ($object !== null && ! empty($object->public)) {
                    $post_types[] = 'post';
                }
            }
        }
        $post_types = array_values(array_unique($post_types));
        if ($post_types === []) {
            return [];
        }

        $wp_query = new WP_Query([
            'author' => $author_id,
            'post_type' => $post_types,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => $candidate_limit,
            'paged' => 1,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'suppress_filters' => false,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $items = [];
        foreach ($wp_query->posts as $post) {
            if (! $post instanceof WP_Post
                || (int) $post->post_author !== $author_id
                || $post->post_password !== ''
                || (string) $post->post_status !== 'publish'
                || ! in_array((string) $post->post_type, $post_types, true)
            ) {
                continue;
            }

            $approved = (bool) apply_filters(
                'sabri_public_experience/wordpress_post_is_public_approved',
                true,
                $post,
                $author_id
            );
            if (! $approved) {
                continue;
            }

            $url = get_permalink($post);
            if (! is_string($url) || $url === '') {
                continue;
            }

            try {
                $items[] = new Normalized_Timeline_Item([
                    'provider_id' => $this->get_provider_id(),
                    'provider_version' => $this->get_provider_version(),
                    'native_object_type' => $post->post_type,
                    'native_object_id' => (string) $post->ID,
                    'author_id' => (int) $post->post_author,
                    'public_profile_id' => $author_id,
                    'title' => get_the_title($post),
                    'safe_excerpt' => wp_trim_words(wp_strip_all_tags(get_the_excerpt($post)), 36),
                    'canonical_url' => $url,
                    'published_at' => get_post_time(DATE_ATOM, true, $post),
                    'updated_at' => get_post_modified_time(DATE_ATOM, true, $post),
                    'visibility_state' => 'public',
                    'native_status' => 'publish',
                    'content_type' => $post->post_type,
                    'topic' => '',
                    'language' => str_replace('_', '-', (string) get_locale()),
                    'thumbnail_reference' => function_exists('get_the_post_thumbnail_url')
                        ? (string) (get_the_post_thumbnail_url($post, 'medium_large') ?: '')
                        : '',
                    'media_type' => has_post_thumbnail($post) ? 'image' : 'none',
                    'verification_state' => 'native',
                    'review_state' => 'published',
                    'correction_state' => 'none',
                    'available_actions' => ['read', 'share'],
                ]);
            } catch (\Throwable $exception) {
                do_action('sabri_public_experience/provider_item_error', $this->get_provider_id(), (int) $post->ID, $exception);
            }
        }

        return $items;
    }

    public function get_health_status(): array
    {
        return [
            'provider' => $this->get_provider_id(),
            'available' => $this->is_available(),
            'maturity' => $this->get_maturity_level(),
        ];
    }
}
