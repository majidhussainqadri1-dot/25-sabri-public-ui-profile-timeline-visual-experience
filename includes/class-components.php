<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Reusable, escaped, low-coupling public components for companion modules. */
final class Components
{
    public const CONTRACT_VERSION = '1.2.0';

    private const STATE_TYPES = [
        'loading',
        'empty',
        'error',
        'success',
        'warning',
        'unavailable',
    ];

    private const NOTICE_TYPES = [
        'info',
        'success',
        'warning',
        'error',
    ];

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'prefix' => 'sabri-ui-',
            'classes' => [
                'container' => 'sabri-ui-container',
                'reading_width' => 'sabri-ui-reading-width',
                'card' => 'sabri-ui-card',
                'content_card' => 'sabri-ui-content-card',
                'button' => 'sabri-ui-button',
                'badge' => 'sabri-ui-badge',
                'stack' => 'sabri-ui-stack',
                'cluster' => 'sabri-ui-cluster',
                'grid' => 'sabri-ui-grid',
                'state' => 'sabri-ui-state',
                'notice' => 'sabri-ui-notice',
                'field' => 'sabri-ui-field',
                'label' => 'sabri-ui-label',
                'input' => 'sabri-ui-input',
                'select' => 'sabri-ui-select',
                'textarea' => 'sabri-ui-textarea',
                'help' => 'sabri-ui-help',
                'field_error' => 'sabri-ui-field-error',
                'table_wrap' => 'sabri-ui-table-wrap',
                'table' => 'sabri-ui-table',
                'skeleton' => 'sabri-ui-skeleton',
                'visually_hidden' => 'sabri-ui-visually-hidden',
            ],
            'states' => self::STATE_TYPES,
            'notices' => self::NOTICE_TYPES,
            'renderers' => [
                'state' => [self::class, 'render_state'],
                'notice' => [self::class, 'render_notice'],
                'content_card' => [Content_Cards::class, 'render'],
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

        $defaults = self::default_state_copy($type);
        $title = self::text($args['title'] ?? $defaults['title'], 240);
        $message = self::text($args['message'] ?? $defaults['message'], 1200);
        $action_label = self::text($args['action_label'] ?? '', 120);
        $action_url = Public_URL::sanitize_same_site($args['action_url'] ?? '');
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
            . ' aria-live="' . $live . '"'
            . ' aria-atomic="true"';
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
        $html .= self::action($action_url, $action_label);
        $html .= '</section>';

        return $html;
    }

    /**
     * Render one standard informational/success/warning/error notice.
     *
     * @param array<string,mixed> $args
     */
    public static function render_notice(array $args = []): string
    {
        $type = self::key((string) ($args['type'] ?? 'info'));
        if (! in_array($type, self::NOTICE_TYPES, true)) {
            $type = 'info';
        }

        $defaults = self::default_notice_copy($type);
        $title = self::text($args['title'] ?? $defaults['title'], 240);
        $message = self::text($args['message'] ?? $defaults['message'], 1200);
        $action_label = self::text($args['action_label'] ?? '', 120);
        $action_url = Public_URL::sanitize_same_site($args['action_url'] ?? '');
        $compact = ! empty($args['compact']);
        $classes = 'sabri-ui-notice sabri-ui-notice--' . $type;
        if ($compact) {
            $classes .= ' sabri-ui-notice--compact';
        }

        $role = $type === 'error' ? 'alert' : 'status';
        $live = $type === 'error' ? 'assertive' : 'polite';
        $html = '<aside class="' . self::escape_attr($classes) . '"'
            . ' data-sabri-visual-notice="' . self::escape_attr($type) . '"'
            . ' role="' . $role . '" aria-live="' . $live . '" aria-atomic="true">';
        if ($title !== '') {
            $html .= '<h3 class="sabri-ui-notice__title">' . self::escape_html($title) . '</h3>';
        }
        if ($message !== '') {
            $html .= '<p class="sabri-ui-notice__message">' . self::escape_html($message) . '</p>';
        }
        $html .= self::action($action_url, $action_label);
        $html .= '</aside>';

        return $html;
    }

    /** @return array{title:string,message:string} */
    private static function default_state_copy(string $type): array
    {
        if (! function_exists('__')) {
            return match ($type) {
                'loading' => ['title' => 'Loading', 'message' => 'The requested content is being prepared.'],
                'error' => ['title' => 'Unable to load content', 'message' => 'The requested public content could not be loaded safely.'],
                'success' => ['title' => 'Completed', 'message' => 'The requested action completed successfully.'],
                'warning' => ['title' => 'Attention required', 'message' => 'Some information may be incomplete or require review.'],
                'unavailable' => ['title' => 'Temporarily unavailable', 'message' => 'This public feature is not currently available.'],
                default => ['title' => 'Nothing here yet', 'message' => 'Approved public content will appear here when it becomes available.'],
            };
        }

        return match ($type) {
            'loading' => ['title' => __('Loading', 'sabri-public-experience'), 'message' => __('The requested content is being prepared.', 'sabri-public-experience')],
            'error' => ['title' => __('Unable to load content', 'sabri-public-experience'), 'message' => __('The requested public content could not be loaded safely.', 'sabri-public-experience')],
            'success' => ['title' => __('Completed', 'sabri-public-experience'), 'message' => __('The requested action completed successfully.', 'sabri-public-experience')],
            'warning' => ['title' => __('Attention required', 'sabri-public-experience'), 'message' => __('Some information may be incomplete or require review.', 'sabri-public-experience')],
            'unavailable' => ['title' => __('Temporarily unavailable', 'sabri-public-experience'), 'message' => __('This public feature is not currently available.', 'sabri-public-experience')],
            default => ['title' => __('Nothing here yet', 'sabri-public-experience'), 'message' => __('Approved public content will appear here when it becomes available.', 'sabri-public-experience')],
        };
    }

    /** @return array{title:string,message:string} */
    private static function default_notice_copy(string $type): array
    {
        if (! function_exists('__')) {
            return match ($type) {
                'success' => ['title' => 'Success', 'message' => 'The public operation completed successfully.'],
                'warning' => ['title' => 'Review required', 'message' => 'Some public information may need attention.'],
                'error' => ['title' => 'Error', 'message' => 'The public operation could not be completed safely.'],
                default => ['title' => 'Information', 'message' => 'Important public information is available.'],
            };
        }

        return match ($type) {
            'success' => ['title' => __('Success', 'sabri-public-experience'), 'message' => __('The public operation completed successfully.', 'sabri-public-experience')],
            'warning' => ['title' => __('Review required', 'sabri-public-experience'), 'message' => __('Some public information may need attention.', 'sabri-public-experience')],
            'error' => ['title' => __('Error', 'sabri-public-experience'), 'message' => __('The public operation could not be completed safely.', 'sabri-public-experience')],
            default => ['title' => __('Information', 'sabri-public-experience'), 'message' => __('Important public information is available.', 'sabri-public-experience')],
        };
    }

    private static function action(string $url, string $label): string
    {
        if ($url === '' || $label === '') {
            return '';
        }

        return '<a class="sabri-ui-button sabri-ui-button--secondary" href="'
            . self::escape_url($url) . '">' . self::escape_html($label) . '</a>';
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
        $limit = max(1, min(12000, $limit));

        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    }

    private static function escape_html(string $value): string
    {
        return function_exists('esc_html') ? esc_html($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_attr(string $value): string
    {
        return function_exists('esc_attr') ? esc_attr($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escape_url(string $value): string
    {
        return function_exists('esc_url') ? esc_url($value) : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
