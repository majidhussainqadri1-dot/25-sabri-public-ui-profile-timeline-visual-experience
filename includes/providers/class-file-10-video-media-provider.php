<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Read-only File 10 Video Wall adapter for the governed Media profile section. */
final class File_10_Video_Media_Provider implements Profile_Section_Provider
{
    private const MINIMUM_VERSION = '0.1.0';
    private const MAXIMUM_VERSION = '0.2.0';
    private const MAX_ITEMS = 24;

    public function get_id(): string { return 'file-10-video-media'; }
    public function get_version(): string { return defined('SVW_VERSION') ? trim((string) SVW_VERSION) : '0.0.0'; }
    public function get_section(): string { return 'media'; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function owns_native_content(): bool { return false; }

    public function is_available(): bool
    {
        $version = $this->get_version();
        if (! self::version_supported($version) || ! class_exists('SVW_Helpers')) {
            return false;
        }
        if (! defined('SVW_Helpers::TYPE') || ! defined('SVW_Helpers::TAX')) {
            return false;
        }
        if ((string) \SVW_Helpers::TYPE !== 'svw_video' || (string) \SVW_Helpers::TAX !== 'svw_category') {
            return false;
        }

        return ! function_exists('post_type_exists') || post_type_exists((string) \SVW_Helpers::TYPE);
    }

    public function supports_profile(array $profile): bool
    {
        return $this->is_available() && in_array(self::key((string) ($profile['class'] ?? '')), ['founder', 'doctor'], true);
    }

    public function query(int $user_id, array $profile, array $context = []): array
    {
        if ($user_id <= 0 || ! $this->supports_profile($profile) || ! function_exists('get_posts')) {
            return [];
        }
        $requested = isset($context['limit']) && is_scalar($context['limit']) ? (int) $context['limit'] : self::MAX_ITEMS;
        $limit = max(1, min(self::MAX_ITEMS, $requested));
        $posts = get_posts([
            'post_type' => (string) \SVW_Helpers::TYPE,
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => $limit,
            'orderby' => ['date' => 'DESC', 'ID' => 'DESC'],
            'order' => 'DESC',
            'has_password' => false,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'suppress_filters' => false,
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_svw_is_reel', 'compare' => 'NOT EXISTS'],
                ['key' => '_svw_is_reel', 'value' => '1', 'compare' => '!='],
            ],
        ]);
        if (! is_array($posts)) {
            return [];
        }

        $items = [];
        foreach (array_slice($posts, 0, $limit) as $post) {
            if (! is_object($post)) {
                continue;
            }
            $id = isset($post->ID) ? (int) $post->ID : 0;
            if (! self::public_post_matches($post, $id, $user_id)) {
                continue;
            }
            if ((string) \SVW_Helpers::meta($id, 'is_reel', '0') === '1') {
                continue;
            }
            $title = function_exists('get_the_title') ? (string) get_the_title($id) : (string) ($post->post_title ?? '');
            $url = function_exists('get_permalink') ? (string) get_permalink($id) : '';
            if (trim($title) === '' || trim($url) === '') {
                continue;
            }
            $duration = absint(\SVW_Helpers::meta($id, 'duration', 0));
            $meta = [];
            if ($duration > 0) {
                $meta[] = \SVW_Helpers::duration($duration);
            }
            $language = (string) \SVW_Helpers::meta($id, 'language', '');
            if (trim($language) !== '') {
                $meta[] = $language;
            }
            $items[] = [
                'type' => 'video',
                'title' => $title,
                'url' => $url,
                'excerpt' => function_exists('get_the_excerpt') ? (string) get_the_excerpt($id) : (string) ($post->post_excerpt ?? ''),
                'eyebrow' => self::wall_label(),
                'image_url' => function_exists('get_the_post_thumbnail_url') ? (string) (get_the_post_thumbnail_url($id, 'medium_large') ?: '') : '',
                'image_alt' => $title,
                'badge' => (string) \SVW_Helpers::category($id),
                'badge_tone' => 'info',
                'meta' => $meta,
                'published_at' => function_exists('get_post_time') ? (string) get_post_time(DATE_W3C, true, $id) : (string) ($post->post_date_gmt ?? ''),
                'action_label' => self::watch_label(),
            ];
        }

        return $items;
    }

    private static function public_post_matches(object $post, int $id, int $user_id): bool
    {
        if ($id <= 0 || (int) ($post->post_author ?? 0) !== $user_id || (string) ($post->post_status ?? '') !== 'publish' || (string) ($post->post_password ?? '') !== '') {
            return false;
        }

        return ! function_exists('get_post_type') || get_post_type($id) === (string) \SVW_Helpers::TYPE;
    }

    private static function version_supported(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1
            && ! version_compare($version, self::MINIMUM_VERSION, '<')
            && ! version_compare($version, self::MAXIMUM_VERSION, '>=');
    }

    private static function wall_label(): string { return function_exists('__') ? __('Video Wall', 'sabri-public-experience') : 'Video Wall'; }
    private static function watch_label(): string { return function_exists('__') ? __('Watch video', 'sabri-public-experience') : 'Watch video'; }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) { return sanitize_key($value); }
        return trim((string) preg_replace('/[^a-z0-9_-]/', '-', strtolower(trim($value))), '-');
    }
}
