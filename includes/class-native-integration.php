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
 * without taking ownership of their data. File 00 remains authoritative for
 * identity, account approval, age, and professional eligibility.
 */
final class Native_Integration
{
    /** @var array<int, array<string,mixed>> */
    private array $profile_cache = [];

    /** @var array<int, array<string,mixed>> */
    private array $credentials_cache = [];

    /** @var array<int, array<string,mixed>> */
    private array $clinic_cache = [];

    /** @var array<string,bool> */
    private array $table_cache = [];

    public function membership_available(): bool
    {
        $detected = defined('SMC_VERSION')
            && function_exists('smc_get_profile')
            && function_exists('smc_user_status')
            && function_exists('smc_is_founder');

        // A filter may narrow a detected dependency, but may not fabricate it.
        return $detected && (bool) apply_filters('sabri_public_experience/dependency/membership_core', true);
    }

    public function profiles_available(): bool
    {
        $detected = defined('SPD_VERSION') && class_exists('SPD_Helpers');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/profiles', true);
    }

    public function shell_available(): bool
    {
        $detected = defined('SABRI_SHELL_VERSION');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/application_shell', true);
    }

    public function home_news_available(): bool
    {
        $detected = defined('SABRI_HNF_VERSION') && function_exists('sabri_hnf_bootstrap');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/home_news', true);
    }

    public function security_center_available(): bool
    {
        $detected = defined('SABRI_SECURITY_CENTER_VERSION') || defined('SABRI_SPRC_VERSION');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/security_center', true);
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
                'meta_compare' => 'EXISTS',
                'number' => 10,
                'fields' => 'ids',
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);
            foreach ($ids as $user_id) {
                if (smc_is_founder((int) $user_id)) {
                    return (int) $user_id;
                }
            }
        }

        return 0;
    }

    /** @return array<string,mixed> */
    public function membership_profile(int $user_id): array
    {
        if (array_key_exists($user_id, $this->profile_cache)) {
            return $this->profile_cache[$user_id];
        }
        if (! function_exists('smc_get_profile')) {
            return $this->profile_cache[$user_id] = [];
        }

        $profile = smc_get_profile($user_id);

        return $this->profile_cache[$user_id] = is_array($profile) ? $profile : [];
    }

    public function membership_status(int $user_id): string
    {
        if (function_exists('smc_user_status')) {
            return sanitize_key((string) smc_user_status($user_id));
        }

        return sanitize_key((string) get_user_meta($user_id, '_smc_status', true));
    }

    public function membership_is_approved(int $user_id): bool
    {
        return in_array($this->membership_status($user_id), ['approved', 'verified'], true);
    }

    public function age(int $user_id): ?int
    {
        $profile = $this->membership_profile($user_id);
        if (! isset($profile['calculated_age'])) {
            return null;
        }

        $age = (int) $profile['calculated_age'];

        return $age > 0 && $age <= 130 ? $age : null;
    }

    public function is_founder(int $user_id): bool
    {
        $detected = function_exists('smc_is_founder') && smc_is_founder($user_id);
        if (! $detected) {
            $detected = $this->founder_user_id() === $user_id;
        }

        // Integrations may revoke presentation, but may not elevate a user.
        return $detected && (bool) apply_filters('sabri_public_experience/is_founder', true, $user_id);
    }

    public function is_minor(int $user_id): bool
    {
        $age = $this->age($user_id);
        $detected = $age !== null && $age < 18;
        $additional_restriction = (bool) apply_filters(
            'sabri_public_experience/additional_minor_restriction',
            false,
            $user_id,
            $age
        );

        return $detected || $additional_restriction;
    }

    public function is_verified_doctor(int $user_id): bool
    {
        $profile = $this->membership_profile($user_id);
        $account_type = sanitize_key((string) ($profile['account_type'] ?? ''));
        $age = $this->age($user_id);
        $eligible = $account_type === 'sabri_doctor'
            && $this->membership_is_approved($user_id)
            && $age !== null
            && $age >= 18
            && ! $this->professional_document_is_expired($user_id);

        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'verification_status')) {
            $profiles_status = sanitize_key((string) \SPD_Helpers::verification_status($user_id));
            if (in_array($profiles_status, ['rejected', 'suspended'], true)) {
                $eligible = false;
            }
        }

        // A provider may narrow eligibility, but File 00 prerequisites remain final.
        $filtered = (bool) apply_filters(
            'sabri_public_experience/is_verified_doctor',
            $eligible,
            $user_id
        );

        return $eligible && $filtered;
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
                'sabri_pharmacy' => 'pharmacy',
                'sabri_clinic' => 'institution',
                'sabri_publisher' => 'publisher',
                default => 'member',
            };
        }

        $filtered = sanitize_key((string) apply_filters('sabri_public_experience/profile_class', $class, $user));
        $allowed = ['founder', 'doctor', 'teacher', 'researcher', 'student', 'patient', 'pharmacy', 'institution', 'publisher', 'member'];
        if (! in_array($filtered, $allowed, true)) {
            return $class;
        }
        if (in_array($filtered, ['founder', 'doctor'], true) && $filtered !== $class) {
            return $class;
        }

        return $filtered;
    }

    public function public_visibility(int $user_id): string
    {
        if ($this->is_founder($user_id)) {
            return 'public';
        }

        // Public presentation requires an approved File 00 identity and known adult age.
        $age = $this->age($user_id);
        if (! $this->membership_is_approved($user_id) || $age === null || $age < 18) {
            return 'private';
        }

        if ($this->is_verified_doctor($user_id)) {
            return 'public';
        }

        $profile = $this->membership_profile($user_id);
        $visibility = sanitize_key((string) ($profile['profile_visibility'] ?? ''));
        if ($visibility === '') {
            $visibility = sanitize_key((string) get_user_meta($user_id, 'sabri_profile_visibility', true));
        }
        if (! in_array($visibility, ['public', 'members', 'private'], true)) {
            $visibility = 'members';
        }

        // A filter may make an already-public profile more restrictive, never wider.
        $filtered = sanitize_key((string) apply_filters(
            'sabri_public_experience/profile_visibility',
            $visibility,
            $user_id
        ));
        if ($visibility !== 'public') {
            return $visibility;
        }

        return in_array($filtered, ['public', 'members', 'private'], true) ? $filtered : 'public';
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

    /**
     * Return only fields needed by public presentation policy.
     *
     * @return array<string,mixed>
     */
    public function professional_credentials(int $user_id): array
    {
        if (array_key_exists($user_id, $this->credentials_cache)) {
            return $this->credentials_cache[$user_id];
        }
        if (! $this->membership_available()) {
            return $this->credentials_cache[$user_id] = [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smc_professional_credentials';
        if (! $this->table_exists($table)) {
            return $this->credentials_cache[$user_id] = [];
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT qualification, institution, council, license_expiry, experience_years, specialization, books_studied, consultation_mode, fee, currency, languages FROM {$table} WHERE user_id = %d LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );

        return $this->credentials_cache[$user_id] = is_array($row) ? $row : [];
    }

    /**
     * Return only an explicitly public/approved clinic projection.
     *
     * @return array<string,mixed>
     */
    public function clinic(int $user_id): array
    {
        if (array_key_exists($user_id, $this->clinic_cache)) {
            return $this->clinic_cache[$user_id];
        }
        if (! $this->membership_available()) {
            return $this->clinic_cache[$user_id] = [];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'smc_clinics';
        if (! $this->table_exists($table)) {
            return $this->clinic_cache[$user_id] = [];
        }

        $public_statuses = (array) apply_filters(
            'sabri_public_experience/public_clinic_statuses',
            ['approved', 'verified', 'active', 'public']
        );
        $public_statuses = array_values(array_filter(array_map('sanitize_key', $public_statuses)));
        if ($public_statuses === []) {
            return $this->clinic_cache[$user_id] = [];
        }

        $placeholders = implode(', ', array_fill(0, count($public_statuses), '%s'));
        $sql = "SELECT id, name, address, country, city, phone, whatsapp, hours, timezone, status FROM {$table} WHERE owner_user_id = %d AND status IN ({$placeholders}) ORDER BY id DESC LIMIT 1";
        $parameters = array_merge([$user_id], $public_statuses);
        $row = $wpdb->get_row($wpdb->prepare($sql, $parameters), ARRAY_A);

        return $this->clinic_cache[$user_id] = is_array($row) ? $row : [];
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

    private function professional_document_is_expired(int $user_id): bool
    {
        $credentials = $this->professional_credentials($user_id);
        $expiry = trim((string) ($credentials['license_expiry'] ?? ''));
        if ($expiry === '') {
            return false;
        }

        $today = function_exists('current_time') ? (string) current_time('Y-m-d') : gmdate('Y-m-d');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry) === 1 && $expiry < $today;
    }

    private function table_exists(string $table): bool
    {
        if (array_key_exists($table, $this->table_cache)) {
            return $this->table_cache[$table];
        }

        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));

        return $this->table_cache[$table] = is_string($exists) && $exists === $table;
    }
}
