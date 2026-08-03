<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
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
    public const FILE_03_MINIMUM_VERSION = '0.2.0';
    public const FILE_03_MAXIMUM_VERSION = '0.3.0';
    public const FILE_09_MINIMUM_VERSION = '1.1.0';
    public const FILE_09_MAXIMUM_VERSION = '1.2.0';
    public const FILE_08_MINIMUM_VERSION = '0.2.1';
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

    /** @var array<string,mixed>|null */
    private ?array $clinic_contract_cache = null;

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

        return $detected && (bool) apply_filters(
            'sabri_public_experience/dependency/membership_core',
            $detected,
            defined('SMC_VERSION') ? (string) SMC_VERSION : '',
            defined('SMC_CONTRACT_VERSION') ? (string) SMC_CONTRACT_VERSION : ''
        );
    }

    public function profiles_available(): bool
    {
        $detected = defined('SPD_VERSION')
            && $this->version_in_range((string) SPD_VERSION, self::FILE_03_MINIMUM_VERSION, self::FILE_03_MAXIMUM_VERSION)
            && class_exists('SPD_Helpers')
            && method_exists('SPD_Helpers', 'get')
            && method_exists('SPD_Helpers', 'founder')
            && method_exists('SPD_Helpers', 'can_show_contact')
            && method_exists('SPD_Helpers', 'verification_status');

        return $detected && (bool) apply_filters(
            'sabri_public_experience/dependency/profiles',
            $detected,
            defined('SPD_VERSION') ? (string) SPD_VERSION : ''
        );
    }

    public function doctor_verification_available(): bool
    {
        $detected = defined('GDO_VERSION')
            && $this->version_in_range((string) GDO_VERSION, self::FILE_09_MINIMUM_VERSION, self::FILE_09_MAXIMUM_VERSION)
            && function_exists('gdo_get_verification_decision')
            && function_exists('gdo_get_approved_snapshot')
            && function_exists('gdo_user_is_verified');

        return $detected && (bool) apply_filters(
            'sabri_public_experience/dependency/doctor_verification',
            $detected,
            defined('GDO_VERSION') ? (string) GDO_VERSION : ''
        );
    }

    public function clinic_available(): bool
    {
        $contract = $this->clinic_contract();
        $detected = $contract !== [];

        return $detected && (bool) apply_filters(
            'sabri_public_experience/dependency/clinic_projection',
            $detected,
            defined('SWC_VERSION') ? (string) SWC_VERSION : '',
            self::FILE_08_PUBLIC_PROJECTION_CONTRACT
        );
    }

    /** @return array<string,mixed> */
    public function clinic_contract(): array
    {
        if ($this->clinic_contract_cache !== null) {
            return $this->clinic_contract_cache;
        }

        $this->clinic_contract_cache = [];
        if (! defined('SWC_VERSION')
            || ! defined('SWC_PUBLIC_CLINIC_CONTRACT_VERSION')
            || ! $this->version_in_range((string) SWC_VERSION, self::FILE_08_MINIMUM_VERSION, self::FILE_08_MAXIMUM_VERSION)
            || ! hash_equals(self::FILE_08_PUBLIC_PROJECTION_CONTRACT, trim((string) SWC_PUBLIC_CLINIC_CONTRACT_VERSION))
            || ! function_exists('swc_get_public_clinic_projection')
            || ! function_exists('swc_public_clinic_projection_contract')
        ) {
            return [];
        }

        try {
            $source = swc_public_clinic_projection_contract();
        } catch (\Throwable) {
            return [];
        }
        if (! is_array($source)) {
            return [];
        }

        $fields = $this->normalized_keys($source['fields'] ?? []);
        $excludes = $this->normalized_keys($source['excludes'] ?? []);
        $required_fields = ['name', 'address', 'country', 'city', 'hours', 'timezone'];
        $required_excludes = ['phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data'];

        sort($fields);
        $expected_fields = $required_fields;
        sort($expected_fields);

        if (! hash_equals(self::FILE_08_PUBLIC_PROJECTION_CONTRACT, trim((string) ($source['contract_version'] ?? '')))
            || sanitize_key((string) ($source['owner'] ?? '')) !== 'file-08'
            || $fields !== $expected_fields
            || array_diff($required_excludes, $excludes) !== []
            || ! array_key_exists('writes_data', $source)
            || (bool) $source['writes_data'] !== false
        ) {
            return [];
        }

        return $this->clinic_contract_cache = [
            'contract_version' => self::FILE_08_PUBLIC_PROJECTION_CONTRACT,
            'owner' => 'file-08',
            'fields' => $required_fields,
            'excludes' => $excludes,
            'writes_data' => false,
        ];
    }

    public function marketplace_available(): bool
    {
        $detected = defined('SMP_VERSION')
            && $this->version_in_range((string) SMP_VERSION, self::FILE_18_MINIMUM_VERSION, self::FILE_18_MAXIMUM_VERSION)
            && function_exists('smp_get_public_profile_listings');

        return $detected && (bool) apply_filters(
            'sabri_public_experience/dependency/marketplace_projection',
            $detected,
            defined('SMP_VERSION') ? (string) SMP_VERSION : ''
        );
    }

    public function shell_available(): bool
    {
        $detected = defined('SABRI_SHELL_VERSION');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/application_shell', $detected);
    }

    public function home_news_available(): bool
    {
        $detected = defined('SABRI_HNF_VERSION') && function_exists('sabri_hnf_bootstrap');

        return $detected && (bool) apply_filters('sabri_public_experience/dependency/home_news', $detected);
    }

    public function security_center_available(): bool
    {
        $detected = File_24_Integration::is_compatible();

        return $detected && (bool) apply_filters(
            'sabri_public_experience/dependency/security_center',
            $detected,
            File_24_Integration::current_version()
        );
    }

    public function founder_user_id(): int
    {
        if (! $this->membership_available()) {
            return 0;
        }

        try {
            $user_id = (int) smc_founder_user_id();
            $founder = $user_id > 0 && (bool) smc_is_founder($user_id);
        } catch (\Throwable) {
            return 0;
        }

        return $founder && get_user_by('id', $user_id) instanceof WP_User ? $user_id : 0;
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
            'application_exists', 'institutional_account', 'approved', 'suspended',
            'eligible', 'guardian_verified', 'professional_verified', 'can_practice',
            'public_profile_allowed', 'minor', 'guardian_required',
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

        return $assertions !== [] && ! empty($assertions['approved']) && empty($assertions['suspended']);
    }

    public function is_founder(int $user_id): bool
    {
        if ($user_id <= 0 || ! $this->membership_available()) {
            return false;
        }

        try {
            $detected = (bool) smc_is_founder($user_id);
        } catch (\Throwable) {
            return false;
        }

        return $detected && (bool) apply_filters('sabri_public_experience/is_founder', $detected, $user_id);
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

        $fingerprint = strtolower(trim((string) ($source['fingerprint'] ?? '')));

        return $this->doctor_decision_cache[$user_id] = [
            'state' => sanitize_key((string) ($source['state'] ?? '')),
            'verified' => ! empty($source['verified']),
            'verified_until' => substr(sanitize_text_field((string) ($source['verified_until'] ?? '')), 0, 10),
            'fingerprint' => preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1 ? $fingerprint : '',
        ];
    }

    /** @return array<string,mixed> */
    public function doctor_approved_snapshot(int $user_id): array
    {
        return $this->is_verified_doctor($user_id) ? $this->raw_doctor_approved_snapshot($user_id) : [];
    }

    /** @return array<string,mixed> */
    private function raw_doctor_approved_snapshot(int $user_id): array
    {
        if (array_key_exists($user_id, $this->doctor_snapshot_cache)) {
            return $this->doctor_snapshot_cache[$user_id];
        }
        if ($user_id <= 0 || ! $this->doctor_verification_available()) {
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
            'qualification', 'licensing_authority', 'experience_years',
            'specialty', 'languages', 'consultation_modes',
        ] as $field) {
            if (! isset($source['profile'][$field]) || ! is_scalar($source['profile'][$field])) {
                continue;
            }
            $value = $this->plain_text((string) $source['profile'][$field], 300);
            if ($value !== '') {
                $profile[$field] = $value;
            }
        }
        if ($profile === []) {
            return $this->doctor_snapshot_cache[$user_id] = [];
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
        $snapshot = $this->raw_doctor_approved_snapshot($user_id);

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
            && $this->doctor_decision_is_current($decision)
            && ! empty($snapshot['profile'])
            && hash_equals((string) ($decision['fingerprint'] ?? ''), (string) ($snapshot['fingerprint'] ?? ''));

        if ($eligible && $this->profiles_available()) {
            try {
                $status = sanitize_key((string) \SPD_Helpers::verification_status($user_id));
            } catch (\Throwable) {
                $status = 'unavailable';
            }
            if (in_array($status, ['rejected', 'suspended', 'revoked', 'expired', 'unavailable'], true)) {
                $eligible = false;
            }
        }

        return $eligible && (bool) apply_filters('sabri_public_experience/is_verified_doctor', $eligible, $user_id);
    }

    /** @param array<string,mixed> $decision */
    private function doctor_decision_is_current(array $decision): bool
    {
        if (empty($decision['verified'])
            || ! in_array((string) ($decision['state'] ?? ''), ['verified', 'approved'], true)
            || preg_match('/^[a-f0-9]{64}$/', (string) ($decision['fingerprint'] ?? '')) !== 1
        ) {
            return false;
        }

        $until = trim((string) ($decision['verified_until'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $until) !== 1) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $until, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if (! $date instanceof \DateTimeImmutable
            || (is_array($errors) && ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0))
            || $date->format('Y-m-d') !== $until
        ) {
            return false;
        }

        return $until >= gmdate('Y-m-d');
    }

    public function profile_class(WP_User $user): string
    {
        $user_id = (int) $user->ID;
        if ($this->is_founder($user_id)) {
            return 'founder';
        }
        if ($this->is_verified_doctor($user_id)) {
            return 'doctor';
        }

        $type = sanitize_key((string) ($this->membership_assertions($user_id)['membership_type'] ?? ''));

        return match ($type) {
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
        $key = sanitize_key($key);
        if ($this->profiles_available()) {
            try {
                $value = \SPD_Helpers::get($user_id, $key, '');
            } catch (\Throwable) {
                $value = '';
            }
            if (is_scalar($value) && trim((string) $value) !== '') {
                return $this->plain_text((string) $value, $key === 'bio' ? 12000 : 300);
            }
        }

        $professional_aliases = [
            'qualification' => 'qualification',
            'licensing_authority' => 'licensing_authority',
            'experience_years' => 'experience_years',
            'specialty' => 'specialty',
            'specialization' => 'specialty',
            'languages' => 'languages',
            'consultation_mode' => 'consultation_modes',
            'consultation_modes' => 'consultation_modes',
        ];
        if (! isset($professional_aliases[$key])) {
            return $default;
        }

        $snapshot = $this->doctor_approved_snapshot($user_id);
        $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
        $candidate = $professional_aliases[$key];

        return isset($profile[$candidate]) ? (string) $profile[$candidate] : $default;
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
            $source = swc_get_public_clinic_projection($user_id);
        } catch (\Throwable) {
            return $this->clinic_cache[$user_id] = [];
        }
        if (! is_array($source)
            || ! hash_equals(self::FILE_08_PUBLIC_PROJECTION_CONTRACT, trim((string) ($source['contract_version'] ?? '')))
            || ! is_array($source['clinic'] ?? null)
        ) {
            return $this->clinic_cache[$user_id] = [];
        }

        $clinic = [];
        foreach (['name', 'address', 'country', 'city', 'hours', 'timezone'] as $field) {
            if (! isset($source['clinic'][$field]) || ! is_scalar($source['clinic'][$field])) {
                continue;
            }
            $value = $this->plain_text((string) $source['clinic'][$field], $field === 'address' || $field === 'hours' ? 500 : 240);
            if ($value !== '') {
                $clinic[$field] = $value;
            }
        }

        return $this->clinic_cache[$user_id] = $clinic;
    }

    /** @return array<string,mixed> */
    public function founder_profile(): array
    {
        if (! $this->profiles_available()) {
            return [];
        }

        try {
            $profile = \SPD_Helpers::founder();
        } catch (\Throwable) {
            return [];
        }

        return is_array($profile) ? $profile : [];
    }

    /** @return list<string> */
    private function normalized_keys(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $keys = [];
        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }
            $key = sanitize_key((string) $item);
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
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
