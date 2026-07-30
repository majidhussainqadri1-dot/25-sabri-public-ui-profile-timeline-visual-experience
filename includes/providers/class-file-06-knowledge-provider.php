<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Read-only File 06 Homeopathy Encyclopedia profile-knowledge adapter.
 *
 * Reviewed native source contract: Homeopathy Encyclopedia Foundation 0.1.x,
 * post type `he_entry`, taxonomy `he_type`, and WordPress `publish` status.
 */
final class File_06_Knowledge_Provider implements Profile_Section_Provider
{
    private const MINIMUM_VERSION = '0.1.0';
    private const MAXIMUM_VERSION = '0.2.0';
    private const MAX_ITEMS = 24;

    public function get_id(): string
    {
        return 'file-06-knowledge';
    }

    public function get_version(): string
    {
        return defined('HE_VERSION') ? trim((string) HE_VERSION) : '0.0.0';
    }

    public function get_section(): string
    {
        return 'knowledge';
    }

    public function get_maturity_level(): string
    {
        return 'read-only';
    }

    public function is_available(): bool
    {
        $version = $this->get_version();
        if (
            preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1
            || version_compare($version, self::MINIMUM_VERSION, '<')
            || version_compare($version, self::MAXIMUM_VERSION, '>=')
        ) {
            return false;
        }
        if (! class_exists('HE_Content') || ! defined('HE_Content::TYPE')) {
            return false;
        }

        $post_type = (string) \HE_Content::TYPE;
        if ($post_type !== 'he_entry') {
            return false;
        }

        return ! function_exists('post_type_exists') || post_type_exists($post_type);
    }

    /** @param array<string,mixed> $profile */
    public function supports_profile(array $profile): bool
    {
        if (! $this->is_available()) {
            return false;
        }

        $class = self::key((string) ($profile['class'] ?? ''));

        return $class !== '' && $class !== 'patient';
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $context
     * @return list<array<string,mixed>>
     */
    public function query(int $user_id, array $profile, array $context = []): array
    {
        if ($user_id <= 0 || ! $this->supports_profile($profile) || ! function_exists('get_posts')) {
            return [];
        }

        $requested = isset($context['limit']) && is_scalar($context['limit'])
            ? (int) $context['limit']
            : self::MAX_ITEMS;
        $limit = max(1, min(self::MAX_ITEMS, $requested));

        $posts = get_posts([
            'post_type' => (string) \HE_Content::TYPE,
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => $limit,
            'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
            'order' => 'DESC',
            'has_password' => false,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);
        if (! is_array($posts)) {
            return [];
        }

        $items = [];
        foreach (array_slice($posts, 0, $limit) as $post) {
            if (! is_object($post)) {
                continue;
            }
            $post_id = isset($post->ID) ? (int) $post->ID : 0;
            $author_id = isset($post->post_author) ? (int) $post->post_author : 0;
            $status = isset($post->post_status) ? (string) $post->post_status : '';
            $password = isset($post->post_password) ? (string) $post->post_password : '';
            if ($post_id <= 0 || $author_id !== $user_id || $status !== 'publish' || $password !== '') {
                continue;
            }
            if (function_exists('get_post_type') && get_post_type($post_id) !== (string) \HE_Content::TYPE) {
                continue;
            }

            $title = function_exists('get_the_title') ? (string) get_the_title($post_id) : (string) ($post->post_title ?? '');
            $url = function_exists('get_permalink') ? (string) get_permalink($post_id) : '';
            if (trim($title) === '' || trim($url) === '') {
                continue;
            }

            $excerpt = function_exists('get_the_excerpt')
                ? (string) get_the_excerpt($post_id)
                : (string) ($post->post_excerpt ?? '');
            $knowledge_type = '';
            if (method_exists('HE_Content', 'type')) {
                $knowledge_type = (string) \HE_Content::type($post_id, 'name');
            }
            $body_system = function_exists('get_post_meta')
                ? (string) get_post_meta($post_id, '_he_body_system', true)
                : '';
            $published_at = function_exists('get_post_time')
                ? (string) get_post_time(DATE_W3C, true, $post_id)
                : (string) ($post->post_date_gmt ?? '');
            $image_url = function_exists('get_the_post_thumbnail_url')
                ? (string) (get_the_post_thumbnail_url($post_id, 'medium_large') ?: '')
                : '';

            $meta = [];
            if ($knowledge_type !== '') {
                $meta[] = $knowledge_type;
            }
            if ($body_system !== '') {
                $meta[] = $body_system;
            }

            $items[] = [
                'type' => 'article',
                'title' => $title,
                'url' => $url,
                'excerpt' => $excerpt,
                'eyebrow' => self::encyclopedia_label(),
                'image_url' => $image_url,
                'image_alt' => $title,
                'badge' => $knowledge_type,
                'badge_tone' => 'info',
                'meta' => $meta,
                'published_at' => $published_at,
                'action_label' => self::read_label(),
            ];
        }

        return $items;
    }

    public function owns_native_content(): bool
    {
        return false;
    }

    private static function encyclopedia_label(): string
    {
        return function_exists('__')
            ? __('Homeopathy Encyclopedia', 'sabri-public-experience')
            : 'Homeopathy Encyclopedia';
    }

    private static function read_label(): string
    {
        return function_exists('__')
            ? __('Read entry', 'sabri-public-experience')
            : 'Read entry';
    }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
