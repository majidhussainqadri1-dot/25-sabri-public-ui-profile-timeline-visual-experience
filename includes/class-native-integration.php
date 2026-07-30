<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Bounded compatibility layer for the currently shipped Sabri modules.
 *
 * It consumes public functions, constants, options, and documented tables
 * without taking ownership of their data.
 */
final class Native_Integration
{
    public function membership_available(): bool
    {
        $detected = defined('SMC_VERSION') && function_exists('smc_get_profile');
        return (bool) apply_filters('sabri_public_experience/dependency/membership_core', $detected);
    }

    public function profiles_available(): bool
    {
        $detected = defined('SPD_VERSION') && class_exists('SPD_Helpers');
        return (bool) apply_filters('sabri_public_experience/dependency/profiles', $detected);
    }

    public function shell_available(): bool
    {
        $detected = defined('SABRI_SHELL_VERSION');
        return (bool) apply_filters('sabri_public_experience/dependency/application_shell', $detected);
    }

    public function home_news_available(): bool
    {
        $detected = defined('SABRI_HNF_VERSION') && function_exists('sabri_hnf_bootstrap');
        return (bool) apply_filters('sabri_public_experience/dependency/home_news', $detected);
    }

    public function security_center_available(): bool
    {
        $detected = defined('SABRI_SECURITY_CENTER_VERSION') || defined('SABRI_SPRC_VERSION');
        return (bool) apply_filters('sabri_public_experience/dependency/security_center', $detected);
    }

    public function founder_user_id(): int
    {
        $configured = (int) get_option('sabri_public_experience_founder_user_id', 0);
        $configured = (int) apply_filters('sabri_public_experience/founder_user_id', $configured);
        if ($configured > 0 && get_user_by('id', $configured) instanceof WP_User) {
            return $configured;
        }

        if (function_exists('smc_is_founder')) {
            $ids = get_users([
                'meta_key' => '_smc_official_founder',
                'meta_value' => '1',
                'number' => 1,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);
            if (! empty($ids[0]) && smc_is_founder((int) $ids[0])) {
                return (int) $ids[0];
            }
        }

        return 0;
    }

    /** @return array<string,mixed> */
    public function membership_profile(int $user_id): array
    {
        if (! function_exists('smc_get_profile')) {
            return [];
        }
        $profile = smc_get_profile($user_id);
        return is_array($profile) ? $profile : [];
    }

    public function membership_status(int $user_id): string
    {
        if (function_exists('smc_user_status')) {
            return sanitize_key((string) smc_user_status($user_id));
        }
        return sanitize_key((string) get_user_meta($user_id, '_smc_status', true));
    }

    public function is_founder(int $user_id): bool
    {
        $detected = function_exists('smc_is_founder') && smc_is_founder($user_id);
        if (! $detected) {
            $detected = $this->founder_user_id() === $user_id;
        }
        return (bool) apply_filters('sabri_public_experience/is_founder', $detected, $user_id);
    }

    public function is_minor(int $user_id): bool
    {
        $profile = $this->membership_profile($user_id);
        $age = isset($profile['calculated_age']) ? (int) $profile['calculated_age'] : 0;
        $detected = $age > 0 && $age < 18;
        return (bool) apply_filters('sabri_membership_core/is_minor', $detected, $user_id);
    }

    public function is_verified_doctor(int $user_id): bool
    {
        $profile = $this->membership_profile($user_id);
        $account_type = sanitize_key((string) ($profile['account_type'] ?? ''));
        $status = $this->membership_status($user_id);
        $membership_verified = $account_type === 'sabri_doctor' && in_array($status, ['approved', 'verified'], true);
        $membership_denied = in_array($status, ['rejected', 'suspended', 'expired_document'], true);

        $profiles_verified = false;
        $profiles_denied = false;
        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'is_doctor') && method_exists('SPD_Helpers', 'verification_status')) {
            $profiles_status = sanitize_key((string) \SPD_Helpers::verification_status($user_id));
            $profiles_verified = \SPD_Helpers::is_doctor($user_id) && $profiles_status === 'verified';
            $profiles_denied = in_array($profiles_status, ['rejected', 'suspended'], true);
        }

        $verified = ! $membership_denied && ! $profiles_denied && ($membership_verified || $profiles_verified);
        return (bool) apply_filters('sabri_public_experience/is_verified_doctor', $verified, $user_id);
    }

    public function profile_class(WP_User $user): string
    {
        $user_id = (int) $user->ID;
        if ($this->is_founder($user_id)) {
            $class = 'founder';
        } elseif ($this->is_verified_doctor($user_id)) {
            $class = 'doctor';
        } else {
            $profile = $this->membership_profile($user_id);
            $account_type = sanitize_key((string) ($profile['account_type'] ?? ''));
            $class = match ($account_type) {
                'sabri_teacher' => 'teacher',
                'sabri_researcher' => 'researcher',
                'sabri_student' => 'student',
                'sabri_patient' => 'patient',
                default => 'member',
            };
        }
        return (string) apply_filters('sabri_public_experience/profile_class', $class, $user);
    }

    public function public_visibility(int $user_id): string
    {
        if ($this->is_founder($user_id) || $this->is_verified_doctor($user_id)) {
            return 'public';
        }
        if ($this->is_minor($user_id)) {
            return 'private';
        }

        $profile = $this->membership_profile($user_id);
        $visibility = sanitize_key((string) ($profile['profile_visibility'] ?? ''));
        if ($visibility === '') {
            $visibility = sanitize_key((string) get_user_meta($user_id, 'sabri_profile_visibility', true));
        }
        return (string) apply_filters('sabri_public_experience/profile_visibility', $visibility ?: 'members', $user_id);
    }

    public function profile_value(int $user_id, string $key, string $default = ''): string
    {
        $profile = $this->membership_profile($user_id);
        if (isset($profile[$key]) && $profile[$key] !== '') {
            return (string) $profile[$key];
        }

        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'get')) {
            $value = \SPD_Helpers::get($user_id, $key, '');
            if ($value !== '') {
                return (string) $value;
            }
        }

        return $default;
    }

    /** @return array<string,mixed> */
    public function professional_credentials(int $user_id): array
    {
        if (! $this->membership_available()) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smc_professional_credentials';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($exists !== $table) {
            return [];
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d LIMIT 1", $user_id), ARRAY_A);
        return is_array($row) ? $row : [];
    }

    /** @return array<string,mixed> */
    public function clinic(int $user_id): array
    {
        if (! $this->membership_available()) {
            return [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smc_clinics';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($exists !== $table) {
            return [];
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE owner_user_id = %d ORDER BY id DESC LIMIT 1", $user_id), ARRAY_A);
        return is_array($row) ? $row : [];
    }

    /** @return array<string,mixed> */
    public function founder_profile(): array
    {
        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'founder')) {
            $profile = \SPD_Helpers::founder();
            return is_array($profile) ? $profile : [];
        }
        return [];
    }
}
