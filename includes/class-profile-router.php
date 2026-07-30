<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

final class Profile_Router
{
    private const SECTION_PATTERN = 'overview|timeline|knowledge|books-research|media|clinic-contact|clinic|reviews|about';
    private const SECTIONS = ['overview', 'timeline', 'knowledge', 'books-research', 'media', 'clinic-contact', 'clinic', 'reviews', 'about'];
    private const TYPES = ['founder', 'doctor', 'member'];

    public function register(): void
    {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter('sabri_shell_layout_mode', [$this, 'shell_layout_mode'], 20, 2);
    }

    public function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^founder(?:/(' . self::SECTION_PATTERN . '))?/?$',
            'index.php?spux_profile_type=founder&spux_profile_section=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^doctors/([^/]+)(?:/(' . self::SECTION_PATTERN . '))?/?$',
            'index.php?spux_profile_type=doctor&spux_profile_slug=$matches[1]&spux_profile_section=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^profile/([^/]+)(?:/(' . self::SECTION_PATTERN . '))?/?$',
            'index.php?spux_profile_type=member&spux_profile_slug=$matches[1]&spux_profile_section=$matches[2]',
            'top'
        );
    }

    /** @param list<string> $vars @return list<string> */
    public function register_query_vars(array $vars): array
    {
        $vars[] = 'spux_profile_type';
        $vars[] = 'spux_profile_slug';
        $vars[] = 'spux_profile_section';

        return array_values(array_unique($vars));
    }

    /** @param array<string,mixed> $settings */
    public function shell_layout_mode(string $mode, array $settings = []): string
    {
        if (! $this->is_profile_request()) {
            return $mode;
        }

        return $this->context()['type'] === 'doctor' ? 'three' : 'two';
    }

    public function is_profile_request(): bool
    {
        $context = $this->context();
        if (! in_array($context['type'], self::TYPES, true)) {
            return false;
        }
        if (! in_array($context['section'], self::SECTIONS, true)) {
            return false;
        }
        if ($context['type'] === 'founder') {
            return true;
        }

        return $context['slug'] !== '' && ! ctype_digit($context['slug']);
    }

    /** @return array{type:string,slug:string,section:string} */
    public function context(): array
    {
        $section = sanitize_key((string) get_query_var('spux_profile_section'));

        return [
            'type' => sanitize_key((string) get_query_var('spux_profile_type')),
            'slug' => sanitize_title((string) get_query_var('spux_profile_slug')),
            'section' => $section !== '' ? $section : 'overview',
        ];
    }

    public static function flush(): void
    {
        (new self())->register_rewrite_rules();
        flush_rewrite_rules(false);
    }
}
