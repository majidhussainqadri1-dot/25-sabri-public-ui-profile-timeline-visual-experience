<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Fail-closed, read-only adapters for authoritative Sabri modules.
 *
 * File 25 never reads foreign tables and never derives membership, age,
 * guardian, doctor-verification, clinic, moderation, or transaction truth.
 */
final class Native_Integration
{
    public const FILE_00_MINIMUM_VERSION = '1.2.4';
    public const FILE_00_MAXIMUM_VERSION = '1.3.0';
    public const FILE_00_CONTRACT_VERSION = '1.1.2';
    public const FILE_09_MINIMUM_VERSION = '1.1.0';
    public const FILE_09_MAXIMUM_VERSION = '1.2.0';
    public const FILE_08_MINIMUM_VERSION = '0.2.0';
    public const FILE_08_MAXIMUM_VERSION = '0.3.0';
    public const FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0';
    public const FILE_18_MINIMUM_VERSION = '1.2.0-RC1';
    public const FILE_18_MAXIMUM_VERSION = '1.3.0';

    /** @var array<int,array<string,mixed>> */
    private array $assertion_cache = [];

    /** @var array<int,array<string,mixed>> */
    private array $doctor_decision_cache = [];

    /** @var array<int,array<string,mixed>> */
    private array $doctor_snapshot_cache = [];

    /** @var array<int,array<string,mixed>> */
    private array $clinic_cache = [];

    public function membership_available(): bool
    {
        $detected = defined('SMC_VERSION')
            && defined('SMC_CONTRACT_VERSION')
            && $this->version_in_range((string) SMC_VERSION, self::FILE_00_MINIMUM_VERSION, self::FILE_00_MAXIMUM_VERSION)
            && hash_equals(self::FILE_00_CONTRACT_VERSION, trim((string) SMC_CONTRACT_VERSION))
            && class_exists('SMC_Contracts')
            && method_exists('SMC_Contracts', 'assertions')
            && function_exists('smc_founder_user_id')
            && function_exists('smc_is_founder');

        $filtered = (bool) apply_filters(
            'sabri_public_experience/dependency/membership_core',
            $detected,
            defined('SMC_VERSION') ? (string) SMC_VERSION : '',
            defined('SMC_CONTRACT_VERSION') ? (string) SMC_CONTRACT_VERSION : ''
        );

        return $detected && $filtered;
    }

    public function profiles_available(): bool
    {
        $detected = defined('SPD_VERSION') && class_exists('SPD_Helpers');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/profiles', true);
    }

    public function doctor_verification_available(): bool
    {
        $detected = defined('GDO_VERSION')
            && $this->version_in_range((string) GDO_VERSION, self::FILE_09_MINIMUM_VERSION, self::FILE_09_MAXIMUM_VERSION)
            && function_exists('gdo_get_verification_decision')
            && function_exists('gdo_get_approved_snapshot')
            && function_exists('gdo_user_is_verified');

        $filtered = (bool) apply_filters(
            'sabri_public_experience/dependency/doctor_verification',
            $detected,
            defined('GDO_VERSION') ? (string) GDO_VERSION : ''
        );

        return $detected && $filtered;
    }

    public function clinic_available(): bool
    {
        $version_ok = defined('SWC_VERSION')
            && $this->version_in_range((string) SWC_VERSION, self::FILE_08_MINIMUM_VERSION, self::FILE_08_MAXIMUM_VERSION);
        $contract_available = function_exists('swc_get_public_clinic_projection')
            || (class_exists('SWC_Helpers') && method_exists('SWC_Helpers', 'public_clinic_projection'));
        $detected = $version_ok && $contract_available;

        $filtered = (bool) apply_filters(
            'sabri_public_experience/dependency/clinic_projection',
            $detected,
            defined('SWC_VERSION') ? (string) SWC_VERSION : '',
            self::FILE_08_PUBLIC_PROJECTION_CONTRACT
        );

        return $detected && $filtered;
    }

    public function marketplace_available(): bool
    {
        $version_ok = defined('SMP_VERSION')
            && $this->version_in_range((string) SMP_VERSION, self::FILE_18_MINIMUM_VERSION, self::FILE_18_MAXIMUM_VERSION);
        $owner_api = function_exists('smp_get_public_profile_listings')
            || (class_exists('SMP_Utils')
                && class_exists('SMP_REST')
                && class_exists('SMP_Activator')
                && method_exists('SMP_Utils', 'current_seller')
                && method_exists('SMP_REST', 'products')
                && method_exists('SMP_Activator', 'marketplace_url'));
        $detected = $version_ok && $owner_api;
        $filtered = (bool) apply_filters(
            'sabri_public_experience/dependency/marketplace_projection',
            $detected,
            defined('SMP_VERSION') ? (string) SMP_VERSION : ''
        );

        return $detected && $filtered;
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
        $detected = File_24_Integration::is_compatible();
        $filtered = (bool) apply_filters(
            'sabri_public_experience/dependency/security_center',
            $detected,
            File_24_Integration::current_version()
        );

        return $detected && $filtered;
    }

    public function founder_user_id(): int
    {
        if (! $this->membership_available()) {
            return 0;
        }

        try {
            $user_id = (int) smc_founder_user_id();
        } catch (\Throwable) {
            return 0;
        }

        return $user_id > 0
            && get_user_by('id', $user_id) instanceof WP_User
            && smc_is_founder($user_id)
                ? $user_id
                : 0;
    }

    /** @return array<string,mixed> */
    public function membership_assertions(int $user_id): array
    {
        if (array_key_exists($user_id, $this->assertion_cache)) {
            return $this->assertion_cache[$user_id];
        }
        if ($user_id <= 0 || ! $this->membership_available()) {
            return $this->assertion_cache[$user_id] = [];
        }

        try {
            $source = \SMC_Contracts::assertions($user_id);
        } catch (\Throwable) {
            return $this->assertion_cache[$user_id] = [];
        }
        if (! is_array($source)
            || ! hash_equals(self::FILE_00_CONTRACT_VERSION, trim((string) ($source['contract_version'] ?? '')))
            || (int) ($source['user_id'] ?? 0) !== $user_id
        ) {
            return $this->assertion_cache[$user_id] = [];
        }

        $assertions = [
            'contract_version' => self::FILE_00_CONTRACT_VERSION,
            'user_id' => $user_id,
            'account_class' => sanitize_key((string) ($source['account_class'] ?? 'member')),
            'membership_type' => sanitize_key((string) ($source['membership_type'] ?? '')),
            'status' => sanitize_key((string) ($source['status'] ?? '')),
        ];
        foreach ([
            'application_exists',
            'institutional_account',
            'approved',
            'suspended',
            'eligible',
            'guardian_verified',
            'professional_verified',
            'can_practice',
            'public_profile_allowed',
            'minor',
            'guardian_required',
        ] as $field) {
            if (array_key_exists($field, $source)) {
                $assertions[$field] = (bool) $source[$field];
            }
        }

        return $this->assertion_cache[$user_id] = $assertions;
    }

    public function membership_status(int $user_id): string
    {
        return sanitize_key((string) ($this->membership_assertions($user_id)['status'] ?? ''));
    }

    public function membership_is_approved(int $user_id): bool
    {
        $assertions = $this->membership_assertions($user_id);

        return ! empty($assertions['approved']) && empty($assertions['suspended']);
    }

    public function is_founder(int $user_id): bool
    {
        if ($user_id <= 0 || ! $this->membership_available()) {
            return false;
        }

        try {
            $detected = smc_is_founder($user_id);
        } catch (\Throwable) {
            $detected = false;
        }
        $filtered = (bool) apply_filters('sabri_public_experience/is_founder', $detected, $user_id);

        return $detected && $filtered;
    }

    public function is_minor(int $user_id): bool
    {
        if ($this->is_founder($user_id) || $this->is_verified_doctor($user_id)) {
            return false;
        }

        $assertions = $this->membership_assertions($user_id);
        if (array_key_exists('minor', $assertions)) {
            return (bool) $assertions['minor'];
        }
        if (array_key_exists('guardian_required', $assertions)) {
            return (bool) $assertions['guardian_required'];
        }

        // File 25 must not calculate age. Until File 00 exposes an explicit
        // minor assertion, ordinary-account contact projection fails closed.
        return true;
    }

    /** @return array<string,mixed> */
    public function doctor_verification_decision(int $user_id): array
    {
        if (array_key_exists($user_id, $this->doctor_decision_cache)) {
            return $this->doctor_decision_cache[$user_id];
        }
        if ($user_id <= 0 || ! $this->doctor_verification_available()) {
            return $this->doctor_decision_cache[$user_id] = [];
        }

        try {
            $source = gdo_get_verification_decision($user_id);
        } catch (\Throwable) {
            return $this->doctor_decision_cache[$user_id] = [];
        }
        if (! is_array($source)) {
            return $this->doctor_decision_cache[$user_id] = [];
        }

        return $this->doctor_decision_cache[$user_id] = [
            'state' => sanitize_key((string) ($source['state'] ?? '')),
            'verified' => ! empty($source['verified']),
            'verified_until' => substr(sanitize_text_field((string) ($source['verified_until'] ?? '')), 0, 32),
            'fingerprint' => preg_match('/^[a-f0-9]{64}$/', (string) ($source['fingerprint'] ?? '')) === 1
                ? (string) $source['fingerprint']
                : '',
        ];
    }

    /** @return array<string,mixed> */
    public function doctor_approved_snapshot(int $user_id): array
    {
        if (array_key_exists($user_id, $this->doctor_snapshot_cache)) {
            return $this->doctor_snapshot_cache[$user_id];
        }
        if (! $this->is_verified_doctor($user_id)) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }

        try {
            $source = gdo_get_approved_snapshot($user_id);
        } catch (\Throwable) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }
        if (! is_array($source) || ! is_array($source['profile'] ?? null)) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }

        $profile = [];
        foreach ([
            'display_name', 'country', 'city', 'clinic', 'qualification',
            'licensing_authority', 'experience_years', 'specialty', 'languages',
            'consultation_modes', 'bio',
        ] as $field) {
            if (isset($source['profile'][$field]) && is_scalar($source['profile'][$field])) {
                $profile[$field] = $this->plain_text((string) $source['profile'][$field], $field === 'bio' ? 4000 : 300);
            }
        }

        return $this->doctor_snapshot_cache[$user_id] = [
            'profile' => $profile,
            'fingerprint' => (string) ($this->doctor_verification_decision($user_id)['fingerprint'] ?? ''),
        ];
    }

    public function is_verified_doctor(int $user_id): bool
    {
        $assertions = $this->membership_assertions($user_id);
        $decision = $this->doctor_verification_decision($user_id);
        try {
            $owner_verified = $this->doctor_verification_available() && (bool) gdo_user_is_verified($user_id);
        } catch (\Throwable) {
            $owner_verified = false;
        }
        $eligible = $assertions !== []
            && ($assertions['membership_type'] ?? '') === 'doctor'
            && ! empty($assertions['approved'])
            && ! empty($assertions['eligible'])
            && empty($assertions['suspended'])
            && ! empty($assertions['professional_verified'])
            && ! empty($assertions['can_practice'])
            && $owner_verified
            && ! empty($decision['verified'])
            && in_array((string) ($decision['state'] ?? ''), ['verified', 'approved'], true);

        if ($eligible && class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'verification_status')) {
            try {
                $profiles_status = sanitize_key((string) \SPD_Helpers::verification_status($user_id));
            } catch (\Throwable) {
                $profiles_status = 'unavailable';
            }
            if (in_array($profiles_status, ['rejected', 'suspended', 'revoked', 'expired', 'unavailable'], true)) {
                $eligible = false;
            }
        }

        $filtered = (bool) apply_filters('sabri_public_experience/is_verified_doctor', $eligible, $user_id);

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
            $type = sanitize_key((string) ($this->membership_assertions($user_id)['membership_type'] ?? ''));
            $class = match ($type) {
                'teacher' => 'teacher',
                'researcher' => 'researcher',
                'student' => 'student',
                'patient' => 'patient',
                'pharmacy' => 'pharmacy',
                'clinic' => 'institution',
                'publisher' => 'publisher',
                default => 'member',
            };
        }

        $filtered = sanitize_key((string) apply_filters('sabri_public_experience/profile_class', $class, $user));
        if ($filtered === 'member' && ! in_array($class, ['founder', 'doctor'], true)) {
            return 'member';
        }

        return $class;
    }

    public function public_visibility(int $user_id): string
    {
        if ($this->is_founder($user_id)) {
            return 'public';
        }

        $assertions = $this->membership_assertions($user_id);
        if ($assertions === []
            || empty($assertions['approved'])
            || empty($assertions['eligible'])
            || ! empty($assertions['suspended'])
            || empty($assertions['public_profile_allowed'])
        ) {
            return 'private';
        }

        $visibility = 'public';
        $filtered = sanitize_key((string) apply_filters(
            'sabri_public_experience/profile_visibility',
            $visibility,
            $user_id
        ));
        if (! in_array($filtered, ['public', 'members', 'private'], true)) {
            return $visibility;
        }

        $rank = ['public' => 0, 'members' => 1, 'private' => 2];

        return $rank[$filtered] >= $rank[$visibility] ? $filtered : $visibility;
    }

    public function profile_value(int $user_id, string $key, string $default = ''): string
    {
        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'get')) {
            try {
                $value = \SPD_Helpers::get($user_id, $key, '');
            } catch (\Throwable) {
                $value = '';
            }
            if (is_scalar($value) && trim((string) $value) !== '') {
                return $this->plain_text((string) $value, $key === 'bio' ? 12000 : 300);
            }
        }

        $snapshot = $this->doctor_approved_snapshot($user_id);
        $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
        $aliases = [
            'specialty' => ['specialty'],
            'specialization' => ['specialty'],
            'consultation_mode' => ['consultation_modes'],
            'clinic_name' => ['clinic'],
        ];
        $candidates = $aliases[$key] ?? [$key];
        foreach ($candidates as $candidate) {
            if (isset($profile[$candidate]) && trim((string) $profile[$candidate]) !== '') {
                return (string) $profile[$candidate];
            }
        }

        return $default;
    }

    /** @return array<string,mixed> */
    public function professional_credentials(int $user_id): array
    {
        $snapshot = $this->doctor_approved_snapshot($user_id);
        $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
        if ($profile === []) {
            return [];
        }

        return array_filter([
            'qualification' => (string) ($profile['qualification'] ?? ''),
            'council' => (string) ($profile['licensing_authority'] ?? ''),
            'experience_years' => (string) ($profile['experience_years'] ?? ''),
            'specialization' => (string) ($profile['specialty'] ?? ''),
            'languages' => (string) ($profile['languages'] ?? ''),
            'consultation_mode' => (string) ($profile['consultation_modes'] ?? ''),
        ], static fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '');
    }

    /** @return array<string,mixed> */
    public function clinic(int $user_id): array
    {
        if (array_key_exists($user_id, $this->clinic_cache)) {
            return $this->clinic_cache[$user_id];
        }
        if ($user_id <= 0 || ! $this->clinic_available()) {
            return $this->clinic_cache[$user_id] = [];
        }

        try {
            if (function_exists('swc_get_public_clinic_projection')) {
                $source = swc_get_public_clinic_projection($user_id);
            } else {
                $source = \SWC_Helpers::public_clinic_projection($user_id);
            }
        } catch (\Throwable) {
            return $this->clinic_cache[$user_id] = [];
        }
        if (! is_array($source)
            || ! hash_equals(self::FILE_08_PUBLIC_PROJECTION_CONTRACT, trim((string) ($source['contract_version'] ?? '')))
        ) {
            return $this->clinic_cache[$user_id] = [];
        }

        $clinic_source = is_array($source['clinic'] ?? null) ? $source['clinic'] : $source;
        $clinic = [];
        foreach (['name', 'address', 'country', 'city', 'hours', 'timezone'] as $field) {
            if (isset($clinic_source[$field]) && is_scalar($clinic_source[$field])) {
                $value = $this->plain_text((string) $clinic_source[$field], $field === 'address' ? 500 : 240);
                if ($value !== '') {
                    $clinic[$field] = $value;
                }
            }
        }

        return $this->clinic_cache[$user_id] = $clinic;
    }

    /** @return array<string,mixed> */
    public function founder_profile(): array
    {
        if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'founder')) {
            try {
                $profile = \SPD_Helpers::founder();
            } catch (\Throwable) {
                $profile = [];
            }

            return is_array($profile) ? $profile : [];
        }

        return [];
    }

    private function version_in_range(string $version, string $minimum, string $maximum_exclusive): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', trim($version)) === 1
            && version_compare($version, $minimum, '>=')
            && version_compare($version, $maximum_exclusive, '<');
    }

    private function plain_text(string $value, int $limit): string
    {
        $value = sanitize_textarea_field($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return function_exists('mb_substr')
            ? mb_substr(trim($value), 0, $limit)
            : substr(trim($value), 0, $limit);
    }
}
