<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Reusable, escaped, low-coupling public components for companion modules. */
final class Components
{
    private const STATE_TYPES = [
        'loading',
        'empty',
        'error',
        'success',
        'warning',
        'unavailable',
    ];

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => '1.0.0',
            'prefix' => 'sabri-ui-',
            'classes' => [
                'container' => 'sabri-ui-container',
                'card' => 'sabri-ui-card',
                'button' => 'sabri-ui-button',
                'badge' => 'sabri-ui-badge',
                'stack' => 'sabri-ui-stack',
                'cluster' => 'sabri-ui-cluster',
                'grid' => 'sabri-ui-grid',
                'state' => 'sabri-ui-state',
                'skeleton' => 'sabri-ui-skeleton',
                'visually_hidden' => 'sabri-ui-visually-hidden',
            ],
            'states' => self::STATE_TYPES,
            'renderers' => [
                'state' => [self::class, 'render_state'],
            ],
        ];
    }

    /**
     * Render one standard loading/empty/error/success/warning/unavailable state.
     *
     * @param array<string,mixed> $args
     */
    public static function render_state(array $args = []): string
    {
        $type = self::key((string) ($args['type'] ?? 'empty'));
        if (! in_array($type, self::STATE_TYPES, true)) {
            $type = 'empty';
        }

        $defaults = self::default_copy($type);
        $title = self::text($args['title'] ?? $defaults['title'], 240);
        $message = self::text($args['message'] ?? $defaults['message'], 1200);
        $action_label = self::text($args['action_label'] ?? '', 120);
        $action_url = self::safe_url($args['action_url'] ?? '');
        $compact = ! empty($args['compact']);
        $role = $type === 'error' ? 'alert' : 'status';
        $live = $type === 'error' ? 'assertive' : 'polite';
        $classes = 'sabri-ui-state sabri-ui-state--' . $type;
        if ($compact) {
            $classes .= ' sabri-ui-state--compact';
        }

        $attributes = ' class="' . self::escape_attr($classes) . '"'
            . ' data-sabri-visual-state="' . self::escape_attr($type) . '"'
            . ' role="' . $role . '"'
            . ' aria-live="' . $live . '"';
        if ($type === 'loading') {
            $attributes .= ' aria-busy="true"';
        }

        $html = '<section' . $attributes . '>';
        if ($title !== '') {
            $html .= '<h2 class="sabri-ui-state__title">' . self::escape_html($title) . '</h2>';
        }
        if ($message !== '') {
            $html .= '<p class="sabri-ui-state__message">' . self::escape_html($message) . '</p>';
        }
        if ($action_url !== '' && $action_label !== '') {
            $html .= '<a class="sabri-ui-button sabri-ui-button--secondary" href="'
                . self::escape_url($action_url)
                . '" rel="noopener noreferrer">'
                . self::escape_html($action_label)
                . '</a>';
        }
        $html .= '</section>';

        return $html;
    }

    /** @return array{title:string,message:string} */
    private static function default_copy(string $type): array
    {
        $copy = [
            'loading' => ['Loading', 'The requested content is being prepared.'],
            'empty' => ['Nothing here yet', 'Approved public content will appear here when it becomes available.'],
            'error' => ['Unable to load content', 'The requested public content could not be loaded safely.'],
            'success' => ['Completed', 'The requested action completed successfully.'],
            'warning' => ['Attention required', 'Some information may be incomplete or require review.'],
            'unavailable' => ['Temporarily unavailable', 'This public feature is not currently available.'],
        ];
        $selected = $copy[$type] ?? $copy['empty'];

        if (function_exists('__')) {
            return [
                'title' => __($selected[0], 'sabri-public-experience'),
                'message' => __($selected[1], 'sabri-public-experience'),
            ];
        }

        return ['title' => $selected[0], 'message' => $selected[1]];
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

    private static function text(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', (string) $value) ?? (string) $value;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return function_exists('mb_substr')
            ? mb_substr($text, 0, $limit)
            : substr($text, 0, $limit);
    }

    private static function safe_url(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $url = trim((string) $value);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return '';
        }
        if ((string) ($parts['host'] ?? '') === '' || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }

        return function_exists('esc_url_raw') ? esc_url_raw($url) : $url;
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
