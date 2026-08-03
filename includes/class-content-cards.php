<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Reusable public card presentation for native module data.
 *
 * The renderer accepts only an explicit display allow-list. It never receives
 * ownership of native objects, metrics, comments, reactions, patient data, or
 * internal identifiers.
 */
final class Content_Cards
{
    public const CONTRACT_VERSION = '1.2.0';

    private const TYPES = [
        'article',
        'post',
        'news',
        'video',
        'reel',
        'book',
        'pdf',
        'doctor',
        'clinic',
        'event',
        'marketplace-item',
    ];

    private const TONES = [
        'neutral',
        'primary',
        'verified',
        'info',
        'success',
        'warning',
        'danger',
    ];

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'types' => self::TYPES,
            'badge_tones' => self::TONES,
            'class' => 'sabri-ui-content-card',
            'required_fields' => ['title'],
            'allowed_fields' => [
                'type',
                'title',
                'url',
                'excerpt',
                'eyebrow',
                'image_url',
                'image_alt',
                'badge',
                'badge_tone',
                'meta',
                'published_at',
                'date_display',
                'action_label',
                'compact',
            ],
            'renderer' => [self::class, 'render'],
            'public_normalizer' => [self::class, 'normalize_public'],
            'owns_native_data' => false,
            'same_site_destinations' => true,
            'date_timezone' => 'UTC',
            'deterministic_dates' => true,
        ];
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];

        return array_merge($base, self::contract());
    }

    /** @param array<string,mixed> $args */
    public static function render(array $args = []): string
    {
        $card = self::normalize_public($args);
        if ($card === null) {
            return '';
        }

        $type = $card['type'];
        $classes = 'sabri-ui-card sabri-ui-content-card sabri-ui-content-card--' . $type;
        if ($card['compact']) {
            $classes .= ' sabri-ui-content-card--compact';
        }
        if ($card['image_url'] !== '') {
            $classes .= ' sabri-ui-content-card--has-media';
        }

        $html = '<article class="' . self::escape_attr($classes) . '" data-sabri-card-type="'
            . self::escape_attr($type) . '">';

        if ($card['image_url'] !== '') {
            $html .= '<div class="sabri-ui-content-card__media">'
                . '<img src="' . self::escape_url($card['image_url']) . '" alt="'
                . self::escape_attr($card['image_alt'])
                . '" loading="lazy" decoding="async">'
                . '</div>';
        }

        $html .= '<div class="sabri-ui-content-card__body">';
        if ($card['eyebrow'] !== '') {
            $html .= '<p class="sabri-ui-content-card__eyebrow">'
                . self::escape_html($card['eyebrow'])
                . '</p>';
        }

        $html .= '<div class="sabri-ui-content-card__heading">';
        if ($card['url'] !== '' && $card['action_label'] === '') {
            $html .= '<h3 class="sabri-ui-content-card__title"><a href="'
                . self::escape_url($card['url']) . '">'
                . self::escape_html($card['title'])
                . '</a></h3>';
        } else {
            $html .= '<h3 class="sabri-ui-content-card__title">'
                . self::escape_html($card['title'])
                . '</h3>';
        }
        if ($card['badge'] !== '') {
            $html .= '<span class="sabri-ui-badge sabri-ui-badge--'
                . self::escape_attr($card['badge_tone']) . '">'
                . self::escape_html($card['badge'])
                . '</span>';
        }
        $html .= '</div>';

        if ($card['published_at'] !== '' || $card['meta'] !== []) {
            $html .= '<ul class="sabri-ui-content-card__meta" aria-label="'
                . self::escape_attr(self::details_label()) . '">';
            if ($card['published_at'] !== '') {
                $html .= '<li><time datetime="' . self::escape_attr($card['published_at']) . '">'
                    . self::escape_html($card['date_display'])
                    . '</time></li>';
            }
            foreach ($card['meta'] as $meta) {
                $html .= '<li>' . self::escape_html($meta) . '</li>';
            }
            $html .= '</ul>';
        }

        if ($card['excerpt'] !== '') {
            $html .= '<p class="sabri-ui-content-card__excerpt">'
                . self::escape_html($card['excerpt'])
                . '</p>';
        }

        if ($card['url'] !== '' && $card['action_label'] !== '') {
            $html .= '<div class="sabri-ui-content-card__footer">'
                . '<a class="sabri-ui-button sabri-ui-button--secondary" href="'
                . self::escape_url($card['url'])
                . '" aria-label="'
                . self::escape_attr(sprintf(self::action_label_format(), $card['action_label'], $card['title']))
                . '">'
                . self::escape_html($card['action_label'])
                . '</a></div>';
        }

        $html .= '</div></article>';

        return $html;
    }

    /**
     * Produce the exact public card projection used by both HTML and REST.
     * Internal provider IDs, projection keys, native object IDs, metrics,
     * moderation notes, private metadata, and unknown fields are discarded.
     *
     * @param array<string,mixed> $args
     * @return array<string,mixed>|null
     */
    public static function normalize_public(array $args): ?array
    {
        $type = self::key((string) ($args['type'] ?? 'article'));
        if (! in_array($type, self::TYPES, true)) {
            $type = 'article';
        }

        $title = self::text($args['title'] ?? '', 240);
        if ($title === '') {
            return null;
        }

        $badge_tone = self::key((string) ($args['badge_tone'] ?? 'neutral'));
        if (! in_array($badge_tone, self::TONES, true)) {
            $badge_tone = 'neutral';
        }

        $published_at = '';
        $date_display = '';
        $timestamp = self::timestamp($args['published_at'] ?? '');
        if ($timestamp !== null) {
            $published_at = gmdate('c', $timestamp);
            $date_display = self::text($args['date_display'] ?? '', 120);
            if ($date_display === '') {
                $format = function_exists('get_option') ? (string) get_option('date_format') : 'M j, Y';
                if ($format === '') {
                    $format = 'M j, Y';
                }
                $date_display = function_exists('wp_date')
                    ? wp_date($format, $timestamp, new \DateTimeZone('UTC'))
                    : gmdate($format, $timestamp);
            }
        }

        $url = Public_URL::sanitize_same_site($args['url'] ?? '', false);
        $image_url = Public_URL::sanitize_same_site($args['image_url'] ?? '', false);
        $image_alt = self::text($args['image_alt'] ?? '', 240);
        if ($image_url !== '' && $image_alt === '') {
            $image_alt = $title;
        }
        if ($image_url === '') {
            $image_alt = '';
        }

        return [
            'type' => $type,
            'title' => $title,
            'url' => $url,
            'excerpt' => self::text($args['excerpt'] ?? '', 900),
            'eyebrow' => self::text($args['eyebrow'] ?? '', 100),
            'image_url' => $image_url,
            'image_alt' => $image_alt,
            'badge' => self::text($args['badge'] ?? '', 100),
            'badge_tone' => $badge_tone,
            'meta' => self::text_list($args['meta'] ?? [], 6, 140),
            'published_at' => $published_at,
            'date_display' => $date_display,
            'action_label' => self::text($args['action_label'] ?? '', 100),
            'compact' => ! empty($args['compact']),
        ];
    }

    private static function timestamp(mixed $value): ?int
    {
        if (! is_scalar($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '' || preg_match('/^\d{4}-\d{2}-\d{2}(?:[T ][0-9:.+\-Z]+)?$/', $raw) !== 1) {
            return null;
        }

        try {
            $utc = new \DateTimeZone('UTC');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw, $utc);
                if (! $date instanceof \DateTimeImmutable || $date->format('Y-m-d') !== $raw) {
                    return null;
                }
            } else {
                $has_timezone = preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/i', $raw) === 1;
                $date = new \DateTimeImmutable($raw, $has_timezone ? null : $utc);
                $errors = \DateTimeImmutable::getLastErrors();
                if (is_array($errors) && ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0)) {
                    return null;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        $timestamp = $date->getTimestamp();

        return $timestamp > 0 ? $timestamp : null;
    }

    /** @return list<string> */
    private static function text_list(mixed $value, int $maximum, int $limit): array
    {
        if (is_array($value)) {
            $source = $value;
        } elseif (is_scalar($value)) {
            $source = preg_split('/[\r\n,;]+/u', (string) $value) ?: [];
        } else {
            return [];
        }

        $result = [];
        foreach ($source as $item) {
            $clean = self::text($item, $limit);
            if ($clean === '' || in_array($clean, $result, true)) {
                continue;
            }
            $result[] = $clean;
            if (count($result) >= $maximum) {
                break;
            }
        }

        return $result;
    }

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $value) ?? (string) $value;
        $text = function_exists('wp_strip_all_tags')
            ? wp_strip_all_tags($text, true)
            : strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);
        $limit = max(1, min(12000, $limit));

        return function_exists('mb_substr')
            ? mb_substr($text, 0, $limit)
            : substr($text, 0, $limit);
    }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }

        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private static function details_label(): string
    {
        return function_exists('__')
            ? __('Content details', 'sabri-public-experience')
            : 'Content details';
    }

    private static function action_label_format(): string
    {
        return function_exists('__')
            ? __('%1$s: %2$s', 'sabri-public-experience')
            : '%1$s: %2$s';
    }

    private static function escape_html(string $value): string
    {
        return function_exists('esc_html')
            ? esc_html($value)
            : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_attr(string $value): string
    {
        return function_exists('esc_attr')
            ? esc_attr($value)
            : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_url(string $value): string
    {
        return function_exists('esc_url')
            ? esc_url($value)
            : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
