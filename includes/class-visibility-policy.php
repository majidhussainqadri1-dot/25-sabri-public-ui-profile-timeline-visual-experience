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

        // Extension hooks may only revoke File 00 + File 03 public eligibility.
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

        // File 03 is the canonical contact-value + audience/consent owner. Its
        // current 1.4.0 public DTO exposes a value only when display is allowed;
        // File 25 never reads File 03 tables or repeats its consent calculation.
        $canonical = $this->native->profile_contact($user_id, $field);
        $authoritative = $canonical !== '';

        $filtered = apply_filters(
            'sabri_public_experience/can_show_contact',
            $authoritative,
            $user_id,
            $field
        );

        // Presentation filters may revoke, never grant or replace owner truth.
        return $authoritative && $filtered === true;
    }
}
