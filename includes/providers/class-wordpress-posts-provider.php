<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Timeline_Provider;
use Sabri\PublicExperience\Normalized_Timeline_Item;
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
        $per_page = min(50, max(1, (int) ($query['per_page'] ?? 20)));
        $page = max(1, (int) ($query['page'] ?? 1));
        $candidate_limit = min(500, ($page * $per_page) + 1);
        $post_types = (array) apply_filters('sabri_public_experience/wordpress_post_types', ['post']);

        $wp_query = new WP_Query([
            'author' => $author_id,
            'post_type' => array_map('sanitize_key', $post_types),
            'post_status' => 'publish',
            'posts_per_page' => $candidate_limit,
            'paged' => 1,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $items = [];
        foreach ($wp_query->posts as $post) {
            $url = get_permalink($post);
            if (! is_string($url) || $url === '') {
                continue;
            }

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
                'content_type' => 'post',
                'topic' => '',
                'language' => get_locale(),
                'thumbnail_reference' => get_post_thumbnail_id($post),
                'media_type' => has_post_thumbnail($post) ? 'image' : 'none',
                'verification_state' => 'native',
                'review_state' => 'published',
                'correction_state' => 'none',
                'available_actions' => ['read', 'share'],
            ]);
        }

        wp_reset_postdata();
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
