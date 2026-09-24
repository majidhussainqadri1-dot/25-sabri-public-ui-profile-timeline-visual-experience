<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Providers;

use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Read-only File 18 Marketplace public-DTO adapter.
 *
 * File 18 remains the sole owner of seller/listing queries and visibility.
 * File 25 consumes only File 18 public APIs and never reads Marketplace tables.
 */
final class File_18_Marketplace_Provider implements Profile_Section_Provider
{
    private const MINIMUM_VERSION = '1.2.0-RC1';
    private const MAXIMUM_VERSION = '1.3.0';
    private const PUBLIC_PROFILE_CONTRACT = '1.0.0';
    private const MAX_ITEMS = 24;
    private const TRANSITIONAL_FETCH_LIMIT = 50;

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

        if (function_exists('smp_get_public_profile_listings')) {
            return true;
        }

        return class_exists('SMP_Utils')
            && class_exists('SMP_REST')
            && class_exists('SMP_Activator')
            && class_exists('WP_REST_Request')
            && class_exists('WP_REST_Response')
            && method_exists('SMP_Utils', 'current_seller')
            && method_exists('SMP_REST', 'products')
            && method_exists('SMP_Activator', 'marketplace_url');
    }

    /** @param array<string,mixed> $profile */
    public function supports_profile(array $profile): bool
    {
        return $this->is_available()
            && in_array(self::key((string) ($profile['class'] ?? '')), ['founder', 'doctor', 'member'], true);
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

        $projection = $this->public_projection($user_id, $limit);
        $rows = is_array($projection['items'] ?? null) ? $projection['items'] : [];
        $marketplace_url = self::safe_url((string) ($projection['marketplace_url'] ?? ''));
        $items = [];

        foreach (array_slice($rows, 0, $limit) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $native_key = self::text($row['projectionKey'] ?? $row['projection_key'] ?? $row['id'] ?? $row['slug'] ?? '', 255);
            $title = self::text($row['title'] ?? '', 240);
            $listing_status = self::key((string) ($row['status'] ?? ''));
            $deal_status = self::key((string) ($row['dealStatus'] ?? $row['deal_status'] ?? ''));
            if (
                $native_key === ''
                || $title === ''
                || ! in_array($listing_status, ['published', 'approved'], true)
                || ! in_array($deal_status, ['available', 'reserved', 'sold'], true)
            ) {
                continue;
            }

            $price = self::finite_float($row['effectivePrice'] ?? $row['effective_price'] ?? $row['price'] ?? 0);
            $currency = self::text($row['currency'] ?? 'PKR', 10);
            $category = self::text($row['category'] ?? '', 100);
            $condition = self::text($row['condition'] ?? $row['condition_name'] ?? '', 60);
            $product_type = self::text($row['productType'] ?? $row['product_type'] ?? '', 60);
            $published_at = self::text(
                $row['publishedAt'] ?? $row['published_at'] ?? $row['updatedAt'] ?? $row['createdAt'] ?? $row['created_at'] ?? '',
                80
            );
            $image_url = self::first_image_url($row);
            $meta = [];
            foreach ([$product_type, $condition, self::price_label($price, $currency), self::status_label($deal_status)] as $value) {
                if ($value !== '' && ! in_array($value, $meta, true)) {
                    $meta[] = $value;
                }
            }

            $items[] = [
                'type' => 'marketplace-item',
                'title' => $title,
                'url' => $marketplace_url,
                'excerpt' => self::text($row['shortDescription'] ?? $row['short_description'] ?? '', 900),
                'eyebrow' => self::marketplace_label(),
                'image_url' => $image_url,
                'image_alt' => $title,
                'badge' => $category,
                'badge_tone' => $deal_status === 'available' ? 'success' : 'neutral',
                'meta' => $meta,
                'published_at' => $published_at,
                'action_label' => $marketplace_url !== '' ? self::open_label() : '',
                'projection_key' => hash('sha256', 'file-18-marketplace|' . $native_key),
            ];
        }

        return $items;
    }

    public function owns_native_content(): bool
    {
        return false;
    }

    /** @return array{items:list<array<string,mixed>>,marketplace_url:string} */
    private function public_projection(int $user_id, int $limit): array
    {
        if (function_exists('smp_get_public_profile_listings')) {
            try {
                $source = smp_get_public_profile_listings($user_id, [
                    'limit' => $limit,
                    'contract_version' => self::PUBLIC_PROFILE_CONTRACT,
                ]);
            } catch (\Throwable) {
                return ['items' => [], 'marketplace_url' => ''];
            }
            if (! is_array($source)
                || ! hash_equals(self::PUBLIC_PROFILE_CONTRACT, trim((string) ($source['contract_version'] ?? '')))
                || ! is_array($source['items'] ?? null)
            ) {
                return ['items' => [], 'marketplace_url' => ''];
            }

            return [
                'items' => array_values($source['items']),
                'marketplace_url' => self::safe_url((string) ($source['marketplace_url'] ?? '')),
            ];
        }

        // Transitional File 18 1.2.0-RC1 adapter. The owner module performs the
        // seller and public-listing queries; File 25 only filters returned DTOs.
        try {
            $seller = \SMP_Utils::current_seller($user_id);
        } catch (\Throwable) {
            return ['items' => [], 'marketplace_url' => ''];
        }
        if (! is_array($seller)
            || (int) ($seller['userId'] ?? 0) !== $user_id
            || self::key((string) ($seller['status'] ?? '')) !== 'approved'
            || (int) ($seller['id'] ?? 0) <= 0
        ) {
            return ['items' => [], 'marketplace_url' => ''];
        }

        try {
            $request = new \WP_REST_Request('GET', '/sabri-marketplace/v1/products');
            $request->set_param('limit', self::TRANSITIONAL_FETCH_LIMIT);
            $request->set_param('page', 1);
            $request->set_param('search', '');
            $response = \SMP_REST::products($request);
            $data = $response instanceof \WP_REST_Response ? $response->get_data() : [];
            $marketplace_url = self::safe_url((string) \SMP_Activator::marketplace_url());
        } catch (\Throwable) {
            return ['items' => [], 'marketplace_url' => ''];
        }
        $products = is_array($data) && is_array($data['products'] ?? null) ? $data['products'] : [];
        $seller_id = (int) $seller['id'];
        $items = [];
        foreach ($products as $product) {
            if (is_array($product) && (int) ($product['sellerId'] ?? 0) === $seller_id) {
                $items[] = $product;
                if (count($items) >= $limit) {
                    break;
                }
            }
        }

        return ['items' => $items, 'marketplace_url' => $marketplace_url];
    }

    /** @param array<string,mixed> $row */
    private static function first_image_url(array $row): string
    {
        if (is_scalar($row['image'] ?? null)) {
            $url = self::safe_url((string) $row['image']);
            if ($url !== '') {
                return $url;
            }
        }
        $images = is_array($row['images'] ?? null) ? $row['images'] : [];
        foreach ($images as $image) {
            $candidate = is_array($image) ? ($image['url'] ?? '') : $image;
            if (is_scalar($candidate)) {
                $url = self::safe_url((string) $candidate);
                if ($url !== '') {
                    return $url;
                }
            }
        }

        return '';
    }

    private static function finite_float(mixed $value): float
    {
        $number = is_numeric($value) ? (float) $value : 0.0;

        return is_finite($number) && $number >= 0 ? $number : 0.0;
    }

    private static function price_label(float $price, string $currency): string
    {
        if ($price <= 0) {
            return '';
        }
        $currency = $currency !== '' ? $currency : 'PKR';

        return trim($currency . ' ' . number_format($price, 2, '.', ','));
    }

    private static function status_label(string $status): string
    {
        return match ($status) {
            'available' => function_exists('__') ? __('Available', 'sabri-public-experience') : 'Available',
            'reserved' => function_exists('__') ? __('Reserved', 'sabri-public-experience') : 'Reserved',
            'sold' => function_exists('__') ? __('Sold', 'sabri-public-experience') : 'Sold',
            default => '',
        };
    }

    private static function marketplace_label(): string
    {
        return function_exists('__') ? __('Marketplace', 'sabri-public-experience') : 'Marketplace';
    }

    private static function open_label(): string
    {
        return function_exists('__') ? __('Open Marketplace', 'sabri-public-experience') : 'Open Marketplace';
    }

    private static function safe_url(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('#^https?://#i', $value) !== 1) {
            return '';
        }

        return function_exists('esc_url_raw') ? (string) esc_url_raw($value, ['http', 'https']) : $value;
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
