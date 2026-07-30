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
        $allowed = $this->native->public_visibility((int) $user->ID) === 'public';
        return (bool) apply_filters('sabri_public_experience/can_render_profile', $allowed, $user);
    }

    public function can_show_contact(int $user_id, string $field): bool
    {
        if ($this->is_minor($user_id)) {
            return false;
        }

        if ($this->is_founder($user_id) || $this->is_verified_doctor($user_id)) {
            $allowed = in_array($field, ['phone', 'whatsapp'], true);
        } else {
            $allowed = false;
            if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'can_show_contact')) {
                $allowed = \SPD_Helpers::can_show_contact($user_id, false);
            }
            $meta = get_user_meta($user_id, 'sabri_public_contact_' . sanitize_key($field), true);
            $allowed = $allowed && $meta === '1';
        }

        return (bool) apply_filters('sabri_public_experience/can_show_contact', $allowed, $user_id, $field);
    }
}
