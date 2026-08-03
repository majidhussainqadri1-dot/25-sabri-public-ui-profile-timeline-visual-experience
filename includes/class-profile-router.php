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
        add_rewrite_rule(
            '^profile/([^/]+)(?:/(' . $pattern . '))?/?$',
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

        // File 20 remains the sole shell/sidebar owner. File 25 requests the
        // two-column public profile layout by default and may use a third column
        // only when an integration explicitly confirms that real sidebar content
        // exists. This prevents an empty right rail on every Doctor profile.
        $right_sidebar_available = (bool) apply_filters(
            'sabri_public_experience/profile_right_sidebar_available',
            false,
            $this->context()
        );

        return $right_sidebar_available ? 'three' : 'two';
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
        $section = sanitize_key((string) get_query_var('spux_profile_section'));
        $raw_slug = (string) get_query_var('spux_profile_slug');

        // Preserve the exact routed slug. Profile_Repository performs the
        // canonical identity check; sanitizing here would turn aliases such as
        // uppercase, whitespace, or punctuation variants into a valid account.
        return [
            'type' => sanitize_key((string) get_query_var('spux_profile_type')),
            'slug' => $raw_slug,
            'section' => $section !== '' ? $section : 'overview',
        ];
    }

    /**
     * Keep profile routing in exact parity with every approved optional section.
     * A provider may still expose a tab only when it supplies accepted public
     * cards; route capability alone never creates a dead tab.
     *
     * @return list<string>
     */
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

    private static function section_pattern(): string
    {
        return implode('|', array_map(
            static fn (string $section): string => preg_quote($section, '#'),
            self::route_sections()
        ));
    }
}
