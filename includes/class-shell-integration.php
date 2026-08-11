<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

/** File 20 integration without duplicating its shell, navigation, or settings. */
final class Shell_Integration
{
    public function __construct(
        private Profile_Router $router,
        private Profile_Repository $profiles,
        private Native_Integration $native
    ) {
    }

    public function register(): void
    {
        add_filter('body_class', [$this, 'body_classes']);
        add_filter('author_link', [$this, 'canonical_author_link'], 20, 3);
        add_filter('sabri_public_experience/shell_contract', [$this, 'contract']);
    }

    /** @param list<string> $classes @return list<string> */
    public function body_classes(array $classes): array
    {
        if (! $this->router->is_profile_request()) {
            return $classes;
        }

        $context = $this->router->context();
        $classes[] = 'spux-public-profile-request';
        $classes[] = 'spux-route-' . sanitize_html_class($context['type']);
        $classes[] = 'spux-section-' . sanitize_html_class($context['section']);
        $classes[] = $this->native->shell_available()
            ? 'spux-shell-connected'
            : 'spux-shell-degraded';

        return array_values(array_unique($classes));
    }

    public function canonical_author_link(string $link, int $author_id, string $author_nicename = ''): string
    {
        unset($author_nicename);
        if ($author_id <= 0) {
            return $link;
        }

        $user = get_user_by('id', $author_id);
        if (! $user instanceof WP_User) {
            return $link;
        }

        $profile = $this->profiles->get_public_profile($user);
        if ($profile === null) {
            return $link;
        }

        $canonical = esc_url_raw((string) ($profile['canonical_url'] ?? ''));

        return $canonical !== '' ? $canonical : $link;
    }

    /** @param mixed $contract @return array<string,mixed> */
    public function contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];
        $base['file'] = 25;
        $base['version'] = SABRI_PUBLIC_EXPERIENCE_VERSION;
        $base['canonical_name'] = Design_System::CANONICAL_NAME;
        $base['shell_detected'] = $this->native->shell_available();
        $base['layout_filter'] = 'sabri_shell_layout_mode';
        $base['inherited_tokens'] = [
            '--sabri-shell-max-width',
        ];
        $base['visual_tokens_inherited_from_shell'] = false;
        $base['structural_geometry_owner'] = 'file-20';
        $base['overlay_stacking_owner'] = 'file-20';
        $base['file25_owns_overlay_z_index'] = false;
        $base['file25_requires_shell_stacking_acceptance'] = true;
        $base['visual_token_owner'] = 'file-25';
        $base['owns_global_shell'] = false;
        $base['owns_global_visual_system'] = true;
        $base['inline_token_bridge'] = false;

        return $base;
    }
}
