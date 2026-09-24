<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class Visibility_Policy
{
    public function __construct(private Native_Integration $native)
    {
    }

    public function is_founder(int $user_id): bool
    {
        return $this->native->is_founder($user_id);
    }

    public function is_verified_doctor(int $user_id): bool
    {
        return $this->native->is_verified_doctor($user_id);
    }

    public function is_minor(int $user_id): bool
    {
        return $this->native->is_minor($user_id);
    }

    public function profile_class(WP_User $user): string
    {
        return $this->native->profile_class($user);
    }

    public function can_render_publicly(WP_User $user): bool
    {
        $authoritative = $this->native->public_visibility((int) $user->ID) === 'public';
        $filtered = apply_filters(
            'sabri_public_experience/can_render_profile',
            $authoritative,
            $user
        );

        // The extension hook may make a profile more restrictive, never publicize
        // a profile denied by File 00 or the minor/suspension rules.
        return $authoritative && $filtered === true;
    }

    public function can_show_contact(int $user_id, string $field): bool
    {
        $field = sanitize_key($field);
        if (! in_array($field, ['phone', 'whatsapp'], true) || $user_id <= 0) {
            return false;
        }
        if ($this->is_minor($user_id) || $this->native->public_visibility($user_id) !== 'public') {
            return false;
        }

        // File 03 is the canonical public-contact consent owner. A verified Doctor
        // does not become contact-public merely by being verified. File 25 consumes
        // the exact File 03 helper and fails closed when that contract is absent or
        // throws. The Founder flag is accepted only for the canonical File 00 Founder.
        $authoritative = false;
        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'can_show_contact')) {
            try {
                $decision = \SPD_Helpers::can_show_contact(
                    $user_id,
                    $this->is_founder($user_id)
                );
                $authoritative = $decision === true;
            } catch (\Throwable) {
                $authoritative = false;
            }
        }

        $filtered = apply_filters(
            'sabri_public_experience/can_show_contact',
            $authoritative,
            $user_id,
            $field
        );

        // Privacy filters may revoke contact display but may never grant it after
        // File 03 or the minor/public-profile authority denied the projection.
        return $authoritative && $filtered === true;
    }
}
