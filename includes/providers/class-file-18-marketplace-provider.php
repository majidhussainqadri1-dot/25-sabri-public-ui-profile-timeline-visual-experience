<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Read-only File 18 Marketplace profile adapter.
 *
 * File 18 remains authoritative for sellers, listings, moderation, contacts,
 * chat, offers, files, metrics, reports, and direct-deal workflows. File 25
 * receives only bounded public card descriptors.
 */
final class File_18_Marketplace_Provider implements Profile_Section_Provider
{
    private const MINIMUM_VERSION = '1.1.0';
    private const MAXIMUM_VERSION = '1.2.0';
    private const MAX_ITEMS = 24;

    public function get_id(): string
    {
        return 'file-18-marketplace';
    }

    public function get_version(): string
    {
        return defined('SMP_VERSION') ? trim((string) SMP_VERSION) : '0.0.0';
    }

    public function get_section(): string
    {
        return 'marketplace';
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

        if (! class_exists('SMP_DB') || ! class_exists('SMP_Activator') || ! class_exists('SMP_Utils')) {
            return false;
        }
        foreach ([
            ['SMP_DB', 'table'],
            ['SMP_Activator', 'marketplace_url'],
            ['SMP_Utils', 'decode_json'],
        ] as [$class, $method]) {
            if (! method_exists($class, $method)) {
                return false;
            }
        }

        global $wpdb;

        return is_object($wpdb)
            && method_exists($wpdb, 'prepare')
            && method_exists($wpdb, 'get_results');
    }

    /** @param array<string,mixed> $profile */
    public function supports_profile(array $profile): bool
    {
        if (! $this->is_available()) {
            return false;
        }

        return in_array(self::key((string) ($profile['class'] ?? '')), ['founder', 'doctor', 'member'], true);
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $context
     * @return list<array<string,mixed>>
     */
    public function query(int $user_id, array $profile, array $context = []): array
    {
        if ($user_id <= 0 || ! $this->supports_profile($profile)) {
            return [];
        }

        $requested = isset($context['limit']) && is_scalar($context['limit'])
            ? (int) $context['limit']
            : self::MAX_ITEMS;
        $limit = max(1, min(self::MAX_ITEMS, $requested));

        global $wpdb;
        $products = (string) \SMP_DB::table('products');
        $sellers = (string) \SMP_DB::table('sellers');
        if ($products === '' || $sellers === '') {
            return [];
        }

        $sql = "SELECT p.*, s.user_id AS seller_user_id, s.status AS seller_status, s.store_name AS seller_store_name
            FROM {$products} p
            INNER JOIN {$sellers} s ON s.id = p.seller_id
            WHERE s.user_id = %d
              AND s.status = 'approved'
              AND p.status IN ('published','approved')
            ORDER BY COALESCE(NULLIF(p.published_at, ''), p.created_at) DESC, p.id DESC
            LIMIT %d";
        $prepared = $wpdb->prepare($sql, $user_id, $limit);
        if (! is_string($prepared) || $prepared === '') {
            return [];
        }

        $rows = $wpdb->get_results($prepared, ARRAY_A);
        if (! is_array($rows)) {
            return [];
        }

        $marketplace_url = (string) \SMP_Activator::marketplace_url();
        $items = [];
        foreach (array_slice($rows, 0, $limit) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $product_id = (int) ($row['id'] ?? 0);
            $seller_user_id = (int) ($row['seller_user_id'] ?? 0);
            $seller_status = self::key((string) ($row['seller_status'] ?? ''));
            $listing_status = self::key((string) ($row['status'] ?? ''));
            $deal_status = self::key((string) ($row['deal_status'] ?? ''));
            $title = self::text($row['title'] ?? '', 240);
            if (
                $product_id <= 0
                || $seller_user_id !== $user_id
                || $seller_status !== 'approved'
                || ! in_array($listing_status, ['published', 'approved'], true)
                || ! in_array($deal_status, ['available', 'reserved', 'sold'], true)
                || $title === ''
            ) {
                continue;
            }

            $images = \SMP_Utils::decode_json($row['images'] ?? '[]');
            $image_url = self::first_image_url($images);
            $price = (float) ($row['price'] ?? 0);
            $sale_price = (float) ($row['sale_price'] ?? 0);
            $effective_price = $sale_price > 0 && $sale_price < $price ? $sale_price : $price;
            $currency = self::text($row['currency'] ?? 'PKR', 10);
            $category = self::text($row['category'] ?? '', 100);
            $condition = self::text($row['condition_name'] ?? '', 60);
            $product_type = self::text($row['product_type'] ?? '', 60);
            $slug = self::text($row['slug'] ?? '', 255);
            $published_at = self::text(($row['published_at'] ?? '') ?: ($row['created_at'] ?? ''), 80);

            $meta = [];
            foreach ([$product_type, $condition, self::price_label($effective_price, $currency), self::status_label($deal_status)] as $value) {
                if ($value !== '' && ! in_array($value, $meta, true)) {
                    $meta[] = $value;
                }
            }

            $items[] = [
                'type' => 'marketplace-item',
                'title' => $title,
                'url' => $marketplace_url,
                'excerpt' => self::text($row['short_description'] ?? '', 900),
                'eyebrow' => self::marketplace_label(),
                'image_url' => $image_url,
                'image_alt' => $title,
                'badge' => $category,
                'badge_tone' => $deal_status === 'available' ? 'success' : 'neutral',
                'meta' => $meta,
                'published_at' => $published_at,
                'action_label' => $marketplace_url !== '' ? self::open_label() : '',
                // File 18 currently has one Marketplace application URL rather
                // than public item permalinks. This opaque server-only key keeps
                // distinct listings visible without exposing the native ID.
                'projection_key' => hash('sha256', 'file-18-marketplace|' . $product_id . '|' . $slug),
            ];
        }

        return $items;
    }

    public function owns_native_content(): bool
    {
        return false;
    }

    /** @param array<int|string,mixed> $images */
    private static function first_image_url(array $images): string
    {
        foreach ($images as $image) {
            if (is_array($image) && is_scalar($image['url'] ?? null)) {
                $url = trim((string) $image['url']);
                if ($url !== '') {
                    return $url;
                }
            }
            if (is_scalar($image)) {
                $url = trim((string) $image);
                if ($url !== '') {
                    return $url;
                }
            }
        }

        return '';
    }

    private static function price_label(float $price, string $currency): string
    {
        if ($price < 0 || ! is_finite($price)) {
            return '';
        }
        $currency = $currency !== '' ? $currency : 'PKR';

        return trim($currency . ' ' . number_format($price, 2, '.', ','));
    }

    private static function status_label(string $status): string
    {
        $label = match ($status) {
            'available' => 'Available',
            'reserved' => 'Reserved',
            'sold' => 'Sold',
            default => '',
        };

        return function_exists('__') && $label !== '' ? __($label, 'sabri-public-experience') : $label;
    }

    private static function marketplace_label(): string
    {
        return function_exists('__')
            ? __('Marketplace', 'sabri-public-experience')
            : 'Marketplace';
    }

    private static function open_label(): string
    {
        return function_exists('__')
            ? __('Open Marketplace', 'sabri-public-experience')
            : 'Open Marketplace';
    }

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
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
