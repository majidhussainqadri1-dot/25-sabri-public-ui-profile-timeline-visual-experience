<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Read-only File 12 PDF Library adapter for the governed Media section. */
final class File_12_Pdf_Media_Provider implements Profile_Section_Provider
{
    private const MINIMUM_VERSION = '0.1.0';
    private const MAXIMUM_VERSION = '0.2.0';
    private const MAX_ITEMS = 24;

    public function get_id(): string { return 'file-12-pdf-media'; }
    public function get_version(): string { return defined('SPL_VERSION') ? trim((string) SPL_VERSION) : '0.0.0'; }
    public function get_section(): string { return 'media'; }
    public function get_maturity_level(): string { return 'read-only'; }
    public function owns_native_content(): bool { return false; }

    public function is_available(): bool
    {
        $version = $this->get_version();
        if (! self::version_supported($version) || ! class_exists('SPL_Helpers')) {
            return false;
        }
        if (! defined('SPL_Helpers::TYPE') || ! defined('SPL_Helpers::DOCTYPE')) {
            return false;
        }
        if ((string) \SPL_Helpers::TYPE !== 'spl_document' || (string) \SPL_Helpers::DOCTYPE !== 'spl_document_type') {
            return false;
        }

        return ! function_exists('post_type_exists') || post_type_exists((string) \SPL_Helpers::TYPE);
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
            'post_type' => (string) \SPL_Helpers::TYPE,
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
            $id = isset($post->ID) ? (int) $post->ID : 0;
            if (! self::public_post_matches($post, $id, $user_id)) {
                continue;
            }
            $title = function_exists('get_the_title') ? (string) get_the_title($id) : (string) ($post->post_title ?? '');
            $url = function_exists('get_permalink') ? (string) get_permalink($id) : '';
            if (trim($title) === '' || trim($url) === '') {
                continue;
            }
            $meta = [];
            $pages = absint(\SPL_Helpers::meta($id, 'pages', 0));
            if ($pages > 0) {
                $meta[] = sprintf(self::pages_format(), $pages);
            }
            $language = trim((string) \SPL_Helpers::meta($id, 'language', ''));
            if ($language !== '') {
                $meta[] = $language;
            }
            $items[] = [
                'type' => 'pdf',
                'title' => $title,
                'url' => $url,
                'excerpt' => function_exists('get_the_excerpt') ? (string) get_the_excerpt($id) : (string) ($post->post_excerpt ?? ''),
                'eyebrow' => self::library_label(),
                'image_url' => function_exists('get_the_post_thumbnail_url') ? (string) (get_the_post_thumbnail_url($id, 'medium_large') ?: '') : '',
                'image_alt' => $title,
                'badge' => method_exists('SPL_Helpers', 'term') ? (string) \SPL_Helpers::term($id, (string) \SPL_Helpers::DOCTYPE) : '',
                'badge_tone' => 'verified',
                'meta' => $meta,
                'published_at' => function_exists('get_post_time') ? (string) get_post_time(DATE_W3C, true, $id) : (string) ($post->post_date_gmt ?? ''),
                'action_label' => self::read_label(),
            ];
        }

        return $items;
    }

    private static function public_post_matches(object $post, int $id, int $user_id): bool
    {
        if ($id <= 0 || (int) ($post->post_author ?? 0) !== $user_id || (string) ($post->post_status ?? '') !== 'publish' || (string) ($post->post_password ?? '') !== '') {
            return false;
        }

        return ! function_exists('get_post_type') || get_post_type($id) === (string) \SPL_Helpers::TYPE;
    }

    private static function version_supported(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1
            && ! version_compare($version, self::MINIMUM_VERSION, '<')
            && ! version_compare($version, self::MAXIMUM_VERSION, '>=');
    }

    private static function library_label(): string { return function_exists('__') ? __('PDF Library', 'sabri-public-experience') : 'PDF Library'; }
    private static function read_label(): string { return function_exists('__') ? __('Read document', 'sabri-public-experience') : 'Read document'; }
    private static function pages_format(): string { return function_exists('__') ? __('%d pages', 'sabri-public-experience') : '%d pages'; }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) { return sanitize_key($value); }
        return trim((string) preg_replace('/[^a-z0-9_-]/', '-', strtolower(trim($value))), '-');
    }
}
