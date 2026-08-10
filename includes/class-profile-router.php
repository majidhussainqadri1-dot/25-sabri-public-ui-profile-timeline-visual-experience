<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

final class Profile_Router
{
    private const CORE_SECTIONS = [
        'overview',
        'timeline',
        'books-research',
        'clinic-contact',
        'clinic',
        'about',
    ];

    private const TYPES = ['founder', 'doctor', 'member'];

    public function register(): void
    {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter('sabri_shell_layout_mode', [$this, 'shell_layout_mode'], 20, 2);
    }

    public function register_rewrite_rules(): void
    {
        $pattern = self::section_pattern();
        add_rewrite_rule(
            '^founder(?:/(' . $pattern . '))?/?$',
            'index.php?spux_profile_type=founder&spux_profile_section=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^doctors/([^/]+)(?:/(' . $pattern . '))?/?$',
            'index.php?spux_profile_type=doctor&spux_profile_slug=$matches[1]&spux_profile_section=$matches[2]',
            'top'
        );

        // File 03 currently owns a UUID-like /profile/{public_id}/ route family.
        // File 25's plan-approved public-slug family must never shadow it. The
        // negative look-ahead deliberately reserves UUID-shaped first segments
        // for File 03 while staging proves the final canonical/redirect parity.
        $non_file03_public_id = '(?![0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}(?:/|$))';
        add_rewrite_rule(
            '^profile/' . $non_file03_public_id . '([^/]+)(?:/(' . $pattern . '))?/?$',
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
        unset($settings);
        if (! $this->is_profile_request()) {
            return $mode;
        }

        $right_sidebar_available = apply_filters(
            'sabri_public_experience/profile_right_sidebar_available',
            false,
            $this->context()
        );

        if ($right_sidebar_available !== true) {
            return 'two';
        }

        return 'three';
    }

    public function is_profile_request(): bool
    {
        $context = $this->context();
        if (! in_array($context['type'], self::TYPES, true)) {
            return false;
        }
        if (! in_array($context['section'], self::route_sections(), true)) {
            return false;
        }
        if ($context['type'] === 'founder') {
            return true;
        }

        return $context['slug'] !== ''
            && strlen($context['slug']) <= 200
            && preg_match('/[\x00-\x20\x7F]/', $context['slug']) !== 1
            && ! ctype_digit($context['slug']);
    }

    /** @return array{type:string,slug:string,section:string} */
    public function context(): array
    {
        $raw_type = (string) get_query_var('spux_profile_type');
        $raw_section = (string) get_query_var('spux_profile_section');
        $raw_slug = (string) get_query_var('spux_profile_slug');

        return [
            'type' => self::exact_key($raw_type) ? $raw_type : '',
            'slug' => $raw_slug,
            'section' => $raw_section === '' ? 'overview' : (self::exact_key($raw_section) ? $raw_section : ''),
        ];
    }

    /** @return list<string> */
    public static function route_sections(): array
    {
        $sections = array_merge(self::CORE_SECTIONS, Section_Registry::approved_sections());

        return array_values(array_unique($sections));
    }

    public static function flush(): void
    {
        (new self())->register_rewrite_rules();
        flush_rewrite_rules(false);
    }

    private static function exact_key(string $value): bool
    {
        return $value !== ''
            && strlen($value) <= 64
            && preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $value) === 1;
    }

    private static function section_pattern(): string
    {
        return implode('|', array_map(
            static fn (string $section): string => preg_quote($section, '#'),
            self::route_sections()
        ));
    }
}
