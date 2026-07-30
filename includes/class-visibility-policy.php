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
        $filtered = (bool) apply_filters(
            'sabri_public_experience/can_render_profile',
            $authoritative,
            $user
        );

        // The extension hook may make a profile more restrictive, never publicize
        // a profile denied by File 00 or the minor/suspension rules.
        return $authoritative && $filtered;
    }

    public function can_show_contact(int $user_id, string $field): bool
    {
        $field = sanitize_key($field);
        if (! in_array($field, ['phone', 'whatsapp'], true)) {
            return false;
        }
        if ($this->is_minor($user_id)) {
            return false;
        }

        if ($this->is_founder($user_id)) {
            $authoritative = true;
        } elseif ($this->is_verified_doctor($user_id)) {
            $authoritative = true;
        } else {
            // General-member contact requires both a public approved profile and
            // the explicit File 03 public-contact opt-in. File 03's broad helper
            // also treats any legacy doctor as public, so the stored consent is
            // read directly instead.
            $authoritative = $this->native->public_visibility($user_id) === 'public';
            if ($authoritative && class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'get')) {
                $authoritative = (string) \SPD_Helpers::get($user_id, 'public_contact', '0') === '1';
            } else {
                $authoritative = false;
            }
        }

        $filtered = (bool) apply_filters(
            'sabri_public_experience/can_show_contact',
            $authoritative,
            $user_id,
            $field
        );

        // Privacy filters may revoke contact display but may not bypass a hard denial.
        return $authoritative && $filtered;
    }
}
