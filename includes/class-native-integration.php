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
    public const FILE_00_MINIMUM_VERSION = '1.2.38';
    public const FILE_00_MAXIMUM_VERSION = '1.3.0';
    public const FILE_00_CONTRACT_VERSION = '1.2.2';

    /** Current File 03 public profile contract — exact reviewed 2026-08-10 family. */
    public const FILE_03_MINIMUM_VERSION = '1.2.0-rc2';
    public const FILE_03_MAXIMUM_VERSION = '1.3.0';
    public const FILE_03_CONTRACT_VERSION = '1.4.0';

    /** Current File 09 public verification projection family. */
    public const FILE_09_MINIMUM_VERSION = '1.3.0';
    public const FILE_09_MAXIMUM_VERSION = '1.4.0';
    public const FILE_09_CONTRACT_VERSION = '1.1.0';

    /** Current File 08 runtime/contract family plus its bounded File 25 compatibility projection. */
    public const FILE_08_MINIMUM_VERSION = '1.2.0';
    public const FILE_08_MAXIMUM_VERSION = '1.3.0';
    public const FILE_08_CANONICAL_CONTRACT = '1.1.0';
    public const FILE_08_PUBLIC_PROJECTION_CONTRACT = '1.0.0';
    public const FILE_18_MINIMUM_VERSION = '1.2.0-RC1';
    public const FILE_18_MAXIMUM_VERSION = '1.3.0';

    /** @var array<int,array<string,mixed>> */
    private array $assertion_cache = [];

    /** @var array<string,array<string,mixed>> */
    private array $profile_projection_cache = [];

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

        return $detected && self::strict_boolean_filter(apply_filters(
            'sabri_public_experience/dependency/membership_core',
            $detected,
            defined('SMC_VERSION') ? (string) SMC_VERSION : '',
            defined('SMC_CONTRACT_VERSION') ? (string) SMC_CONTRACT_VERSION : ''
        ));
    }

    public function profiles_available(): bool
    {
        if (! defined('SPD_VERSION')
            || ! defined('SPD_CONTRACT_VERSION')
            || ! $this->version_in_range((string) SPD_VERSION, self::FILE_03_MINIMUM_VERSION, self::FILE_03_MAXIMUM_VERSION)
            || ! hash_equals(self::FILE_03_CONTRACT_VERSION, trim((string) SPD_CONTRACT_VERSION))
            || ! function_exists('spd_get_public_profile')
            || ! function_exists('spd_get_profile_contract_manifest')
        ) {
            return false;
        }

        try {
            $manifest = spd_get_profile_contract_manifest();
        } catch (\Throwable) {
            return false;
        }
        $queries = is_array($manifest['queries'] ?? null) ? $manifest['queries'] : [];
        $detected = is_array($manifest)
            && sanitize_key((string) ($manifest['owner_key'] ?? '')) === 'file03'
            && hash_equals(self::FILE_03_CONTRACT_VERSION, trim((string) ($manifest['contract_version'] ?? '')))
            && ($queries['get_public_profile'] ?? '') === 'spd_get_public_profile';

        return $detected && self::strict_boolean_filter(apply_filters(
            'sabri_public_experience/dependency/profiles',
            $detected,
            (string) SPD_VERSION,
            (string) SPD_CONTRACT_VERSION
        ));
    }

    /**
     * Consume only File 03's public DTO. No File 03 table, private repository row,
     * evidence object or private contact value may cross this boundary.
     *
     * @return array<string,mixed>
     */
    public function public_profile_projection(int $user_id, int $viewer_id = 0): array
    {
        $viewer_id = max(0, $viewer_id);
        $cache_key = $user_id . ':' . $viewer_id;
        if (array_key_exists($cache_key, $this->profile_projection_cache)) {
            return $this->profile_projection_cache[$cache_key];
        }
        if ($user_id <= 0 || ! $this->profiles_available()) {
            return $this->profile_projection_cache[$cache_key] = [];
        }

        try {
            $source = spd_get_public_profile($user_id, $viewer_id);
        } catch (\Throwable) {
            return $this->profile_projection_cache[$cache_key] = [];
        }
        if ((function_exists('is_wp_error') && is_wp_error($source)) || ! is_array($source)) {
            return $this->profile_projection_cache[$cache_key] = [];
        }
        if (! hash_equals(self::FILE_03_CONTRACT_VERSION, trim((string) ($source['contract_version'] ?? '')))) {
            return $this->profile_projection_cache[$cache_key] = [];
        }

        $profile_type = sanitize_key((string) ($source['profile_type'] ?? ''));
        $state = sanitize_key((string) ($source['state'] ?? ''));
        $canonical = Public_URL::sanitize_same_site($source['canonical_url'] ?? '', false);
        if (! in_array($profile_type, ['founder', 'doctor', 'teacher', 'researcher', 'student', 'patient', 'pharmacy', 'institution', 'publisher', 'member'], true)
            || $state === ''
            || $canonical === ''
        ) {
            return $this->profile_projection_cache[$cache_key] = [];
        }

        $projection = [
            'contract_version' => self::FILE_03_CONTRACT_VERSION,
            'public_id' => $this->plain_text((string) ($source['public_id'] ?? ''), 80),
            'canonical_url' => $canonical,
            'timeline_url' => Public_URL::sanitize_same_site($source['timeline_url'] ?? '', false),
            'report_url' => Public_URL::sanitize_same_site($source['report_url'] ?? '', false),
            'profile_type' => $profile_type,
            'state' => $state,
            'version' => max(0, (int) ($source['version'] ?? 0)),
            'display_name' => $this->plain_text((string) ($source['display_name'] ?? ''), 190),
            'locale' => $this->plain_text((string) ($source['locale'] ?? ''), 40),
            'badge' => is_array($source['badge'] ?? null) ? $source['badge'] : [],
            'fields' => is_array($source['fields'] ?? null) ? $source['fields'] : [],
            'media' => is_array($source['media'] ?? null) ? $source['media'] : [],
            'contacts' => is_array($source['contacts'] ?? null) ? $source['contacts'] : [],
            'professional' => is_array($source['professional'] ?? null) ? $source['professional'] : [],
            'founder' => is_array($source['founder'] ?? null) ? $source['founder'] : [],
            'clinic' => is_array($source['clinic'] ?? null) ? $source['clinic'] : [],
        ];

        return $this->profile_projection_cache[$cache_key] = $projection;
    }

    public function profile_canonical_url(int $user_id): string
    {
        return (string) ($this->public_profile_projection($user_id, 0)['canonical_url'] ?? '');
    }

    public function profile_media_attachment_id(int $user_id, string $purpose): int
    {
        $purpose = sanitize_key($purpose);
        if (! in_array($purpose, ['avatar', 'cover'], true)) {
            return 0;
        }
        $row = $this->public_profile_projection($user_id, 0)['media'][$purpose] ?? null;
        if (! is_array($row)) {
            return 0;
        }
        $attachment_id = (int) ($row['attachment_id'] ?? 0);
        $url = Public_URL::sanitize_same_site($row['url'] ?? '', false);

        return $attachment_id > 0 && $url !== '' ? $attachment_id : 0;
    }

    public function profile_contact(int $user_id, string $field): string
    {
        $field = sanitize_key($field);
        if (! in_array($field, ['phone', 'whatsapp'], true)) {
            return '';
        }
        $value = $this->public_profile_projection($user_id, 0)['contacts'][$field] ?? '';

        return self::canonical_contact($value);
    }

    public function doctor_verification_available(): bool
    {
        if (! defined('GDO_VERSION')
            || ! $this->version_in_range((string) GDO_VERSION, self::FILE_09_MINIMUM_VERSION, self::FILE_09_MAXIMUM_VERSION)
            || ! class_exists('GDO_Integration_Contracts')
            || ! defined('GDO_Integration_Contracts::VERSION')
            || ! hash_equals(self::FILE_09_CONTRACT_VERSION, trim((string) constant('GDO_Integration_Contracts::VERSION')))
            || ! function_exists('gdo_file03_doctor_eligibility')
        ) {
            return false;
        }

        $detected = true;
        return self::strict_boolean_filter(apply_filters(
            'sabri_public_experience/dependency/doctor_verification',
            $detected,
            (string) GDO_VERSION,
            self::FILE_09_CONTRACT_VERSION
        ));
    }

    public function clinic_available(): bool
    {
        $contract = $this->clinic_contract();
        $detected = $contract !== [];
        $runtime = defined('WCA_VERSION') ? (string) WCA_VERSION : (defined('SWC_VERSION') ? (string) SWC_VERSION : '');

        return $detected && self::strict_boolean_filter(apply_filters(
            'sabri_public_experience/dependency/clinic_projection',
            $detected,
            $runtime,
            self::FILE_08_CANONICAL_CONTRACT
        ));
    }

    /** @return array<string,mixed> */
    public function clinic_contract(): array
    {
        if ($this->clinic_contract_cache !== null) {
            return $this->clinic_contract_cache;
        }

        $this->clinic_contract_cache = [];
        $runtime = defined('WCA_VERSION') ? (string) WCA_VERSION : (defined('SWC_VERSION') ? (string) SWC_VERSION : '');
        if ($runtime === ''
            || ! $this->version_in_range($runtime, self::FILE_08_MINIMUM_VERSION, self::FILE_08_MAXIMUM_VERSION)
            || ! class_exists('WCA_Contracts')
            || ! defined('WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION')
            || ! hash_equals(self::FILE_08_CANONICAL_CONTRACT, trim((string) constant('WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION')))
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
            'canonical_contract_version' => self::FILE_08_CANONICAL_CONTRACT,
            'compatibility_projection_contract_version' => self::FILE_08_PUBLIC_PROJECTION_CONTRACT,
            'runtime_version' => $runtime,
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

        return $detected && self::strict_boolean_filter(apply_filters(
            'sabri_public_experience/dependency/marketplace_projection',
            $detected,
            defined('SMP_VERSION') ? (string) SMP_VERSION : ''
        ));
    }

    public function shell_available(): bool
    {
        $detected = defined('SABRI_SHELL_VERSION');
        return $detected && self::strict_boolean_filter(apply_filters('sabri_public_experience/dependency/application_shell', $detected));
    }

    public function home_news_available(): bool
    {
        $detected = defined('SABRI_HNF_VERSION') && function_exists('sabri_hnf_bootstrap');
        return $detected && self::strict_boolean_filter(apply_filters('sabri_public_experience/dependency/home_news', $detected));
    }

    public function security_center_available(): bool
    {
        $detected = File_24_Integration::is_compatible();
        return $detected && self::strict_boolean_filter(apply_filters(
            'sabri_public_experience/dependency/security_center',
            $detected,
            File_24_Integration::current_version()
        ));
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

        $account_class = (string) ($source['account_class'] ?? 'member');
        $membership_type = (string) ($source['membership_type'] ?? '');
        $status = (string) ($source['status'] ?? '');
        foreach ([$account_class, $membership_type, $status] as $index => $raw_key) {
            $canonical = sanitize_key($raw_key);
            $may_be_empty = $index === 1;
            if ((! $may_be_empty && $canonical === '') || strlen($raw_key) > 64 || ! hash_equals($canonical, $raw_key)) {
                return $this->assertion_cache[$user_id] = [];
            }
        }

        $assertions = [
            'contract_version' => self::FILE_00_CONTRACT_VERSION,
            'user_id' => $user_id,
            'account_class' => $account_class,
            'membership_type' => $membership_type,
            'status' => $status,
        ];
        foreach ([
            'application_exists', 'institutional_account', 'approved', 'suspended', 'eligible',
            'guardian_verified', 'professional_verified', 'can_practice', 'public_profile_allowed',
            'minor', 'guardian_required',
        ] as $field) {
            if (! array_key_exists($field, $source)) {
                continue;
            }
            if (! is_bool($source[$field])) {
                return $this->assertion_cache[$user_id] = [];
            }
            $assertions[$field] = $source[$field];
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

        return $detected && self::strict_boolean_filter(apply_filters('sabri_public_experience/is_founder', $detected, $user_id));
    }

    public function is_minor(int $user_id): bool
    {
        $assertions = $this->membership_assertions($user_id);
        if (! empty($assertions['minor']) || ! empty($assertions['guardian_required'])) {
            return true;
        }
        if (array_key_exists('minor', $assertions)) {
            return (bool) $assertions['minor'];
        }
        if (array_key_exists('guardian_required', $assertions)) {
            return (bool) $assertions['guardian_required'];
        }
        if ($this->is_founder($user_id) || $this->is_verified_doctor($user_id)) {
            return false;
        }

        // File 25 never infers age. Unknown ordinary-account age remains minor-safe.
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
            $source = gdo_file03_doctor_eligibility($user_id);
        } catch (\Throwable) {
            return $this->doctor_decision_cache[$user_id] = [];
        }
        if (! is_array($source)
            || ($source['source_of_truth'] ?? '') !== 'file09'
            || ($source['consumer'] ?? '') !== 'file03'
            || ! hash_equals(self::FILE_09_CONTRACT_VERSION, trim((string) ($source['version'] ?? '')))
            || ! array_key_exists('verified', $source)
            || ! is_bool($source['verified'])
        ) {
            return $this->doctor_decision_cache[$user_id] = [];
        }

        $raw_state = trim((string) ($source['state'] ?? 'unavailable'));
        $state = sanitize_key($raw_state);
        $verified_until = trim((string) ($source['verified_until'] ?? ''));
        $raw_fingerprint = trim((string) ($source['fingerprint'] ?? ''));
        $fingerprint = strtolower($raw_fingerprint);
        if ($state === ''
            || strlen($raw_state) > 64
            || ! hash_equals($state, $raw_state)
            || ! hash_equals($fingerprint, $raw_fingerprint)
        ) {
            return $this->doctor_decision_cache[$user_id] = [];
        }
        if ($source['verified']
            && (preg_match('/^[a-f0-9]{64}$/', $fingerprint) !== 1
                || preg_match('/^\d{4}-\d{2}-\d{2}$/', $verified_until) !== 1)
        ) {
            return $this->doctor_decision_cache[$user_id] = [];
        }

        return $this->doctor_decision_cache[$user_id] = [
            'state' => $state,
            'verified' => $source['verified'],
            'verified_until' => $verified_until,
            'fingerprint' => $fingerprint,
            'eligible' => ($source['eligible'] ?? false) === true,
            'limited' => ($source['limited'] ?? false) === true,
        ];
    }

    /** @return array<string,mixed> */
    public function doctor_approved_snapshot(int $user_id): array
    {
        if (array_key_exists($user_id, $this->doctor_snapshot_cache)) {
            return $this->doctor_snapshot_cache[$user_id];
        }
        if (! $this->doctor_decision_is_current($this->doctor_verification_decision($user_id))) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }

        // Prefer File 03's current public professional projection because it has
        // already consumed File 09 and applied profile visibility/privacy rules.
        $projection = $this->public_profile_projection($user_id, 0);
        $professional = is_array($projection['professional'] ?? null) ? $projection['professional'] : [];
        if ($professional !== []) {
            return $this->doctor_snapshot_cache[$user_id] = [
                'profile' => $this->normalize_professional($professional),
                'fingerprint' => (string) ($this->doctor_verification_decision($user_id)['fingerprint'] ?? ''),
            ];
        }

        if (! function_exists('gdo_get_approved_snapshot')) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }
        try {
            $source = gdo_get_approved_snapshot($user_id);
        } catch (\Throwable) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }
        if (! is_array($source)) {
            return $this->doctor_snapshot_cache[$user_id] = [];
        }
        $raw = is_array($source['profile'] ?? null) ? $source['profile'] : $source;
        $profile = $this->normalize_professional($raw);
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
        $snapshot = $this->doctor_approved_snapshot($user_id);
        $profile = $this->public_profile_projection($user_id, 0);
        $badge = is_array($profile['badge'] ?? null) ? $profile['badge'] : [];

        $eligible = $assertions !== []
            && ($assertions['membership_type'] ?? '') === 'doctor'
            && ! empty($assertions['approved'])
            && ! empty($assertions['eligible'])
            && empty($assertions['suspended'])
            && ! empty($assertions['professional_verified'])
            && ! empty($assertions['can_practice'])
            && empty($assertions['minor'])
            && empty($assertions['guardian_required'])
            && $this->doctor_decision_is_current($decision)
            && ! empty($decision['eligible'])
            && ! empty($snapshot['profile'])
            && hash_equals((string) ($decision['fingerprint'] ?? ''), (string) ($snapshot['fingerprint'] ?? ''))
            && ($profile['profile_type'] ?? '') === 'doctor'
            && ($badge['verified'] ?? false) === true;

        return $eligible && self::strict_boolean_filter(apply_filters('sabri_public_experience/is_verified_doctor', $eligible, $user_id));
    }

    /** @param array<string,mixed> $decision */
    private function doctor_decision_is_current(array $decision): bool
    {
        if (empty($decision['verified'])
            || ! in_array((string) ($decision['state'] ?? ''), ['verified', 'approved', 'reinstated', 'renewal_due'], true)
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

        $projection_type = sanitize_key((string) ($this->public_profile_projection($user_id, 0)['profile_type'] ?? ''));
        if (in_array($projection_type, ['teacher', 'researcher', 'student', 'patient', 'pharmacy', 'institution', 'publisher', 'member'], true)) {
            return $projection_type;
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
        $assertions = $this->membership_assertions($user_id);
        $status = (string) ($assertions['status'] ?? '');
        $hard_blocked = in_array($status, ['rejected', 'suspended', 'revoked', 'appeal_review', 'erasure_pending', 'deleted', 'invalid_application'], true);

        $file00_public = false;
        if ($this->is_founder($user_id)) {
            $file00_public = $assertions !== []
                && empty($assertions['suspended'])
                && ! $hard_blocked
                && (! array_key_exists('public_profile_allowed', $assertions) || $assertions['public_profile_allowed'] === true);
        } else {
            $file00_public = $assertions !== []
                && ! empty($assertions['approved'])
                && ! empty($assertions['eligible'])
                && empty($assertions['suspended'])
                && ! empty($assertions['public_profile_allowed']);
        }
        if (! $file00_public || ! $this->profiles_available() || $this->public_profile_projection($user_id, 0) === []) {
            return 'private';
        }

        $visibility = 'public';
        $filtered = sanitize_key((string) apply_filters('sabri_public_experience/profile_visibility', $visibility, $user_id));
        if (! in_array($filtered, ['public', 'members', 'private'], true)) {
            return $visibility;
        }
        $rank = ['public' => 0, 'members' => 1, 'private' => 2];

        return $rank[$filtered] >= $rank[$visibility] ? $filtered : $visibility;
    }

    public function profile_value(int $user_id, string $key, string $default = ''): string
    {
        $key = sanitize_key($key);
        $projection = $this->public_profile_projection($user_id, 0);
        $fields = is_array($projection['fields'] ?? null) ? $projection['fields'] : [];
        $professional = is_array($projection['professional'] ?? null) ? $projection['professional'] : [];

        $field_aliases = [
            'bio' => 'bio', 'country' => 'country', 'city' => 'city',
            'languages' => 'languages', 'studied_books' => 'studied_books', 'books_studied' => 'studied_books',
        ];
        if (isset($field_aliases[$key]) && array_key_exists($field_aliases[$key], $fields)) {
            $value = $this->scalar_or_list($fields[$field_aliases[$key]]);
            if ($value !== '') {
                return $this->plain_text($value, $key === 'bio' ? 12000 : 1000);
            }
        }
        if (in_array($key, ['phone', 'whatsapp'], true)) {
            $contact = $this->profile_contact($user_id, $key);
            return $contact !== '' ? $contact : $default;
        }

        $professional_aliases = [
            'qualification' => 'qualification',
            'licensing_authority' => 'licensing_authority',
            'council' => 'licensing_authority',
            'institution' => 'institution',
            'experience_years' => 'experience_years',
            'specialty' => 'specialty',
            'specialization' => 'specialty',
            'languages' => 'languages',
            'consultation_mode' => 'consultation_modes',
            'consultation_modes' => 'consultation_modes',
        ];
        if (isset($professional_aliases[$key]) && array_key_exists($professional_aliases[$key], $professional)) {
            $value = $this->scalar_or_list($professional[$professional_aliases[$key]]);
            if ($value !== '') {
                return $this->plain_text($value, 1000);
            }
        }

        return $default;
    }

    /** @return array<string,mixed> */
    public function professional_credentials(int $user_id): array
    {
        $projection = $this->public_profile_projection($user_id, 0);
        $source = is_array($projection['professional'] ?? null) ? $projection['professional'] : [];
        if ($source === []) {
            $source = (array) ($this->doctor_approved_snapshot($user_id)['profile'] ?? []);
        }
        if ($source === []) {
            return [];
        }

        return array_filter([
            'qualification' => $this->scalar_or_list($source['qualification'] ?? ''),
            'institution' => $this->scalar_or_list($source['institution'] ?? ''),
            'council' => $this->scalar_or_list($source['licensing_authority'] ?? ''),
            'experience_years' => $this->scalar_or_list($source['experience_years'] ?? ''),
            'specialization' => $this->scalar_or_list($source['specialty'] ?? ''),
            'languages' => $this->scalar_or_list($source['languages'] ?? ''),
            'consultation_mode' => $this->scalar_or_list($source['consultation_modes'] ?? ''),
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
        $user_id = $this->founder_user_id();
        $projection = $this->public_profile_projection($user_id, 0);
        if ($user_id <= 0 || $projection === [] || ($projection['profile_type'] ?? '') !== 'founder') {
            return [];
        }

        $founder = is_array($projection['founder'] ?? null) ? $projection['founder'] : [];
        $fields = is_array($projection['fields'] ?? null) ? $projection['fields'] : [];
        $media = is_array($projection['media'] ?? null) ? $projection['media'] : [];
        $contacts = is_array($projection['contacts'] ?? null) ? $projection['contacts'] : [];
        $out = $founder;
        $out['title'] = $this->plain_text((string) ($founder['professional_title'] ?? ''), 300);
        $out['introduction'] = $this->plain_text((string) ($fields['bio'] ?? ''), 12000);
        $location = array_filter([
            $this->plain_text((string) ($fields['city'] ?? ''), 100),
            $this->plain_text((string) ($fields['country'] ?? ''), 100),
        ]);
        if ($location !== []) {
            $out['location'] = implode(', ', $location);
        }
        $out['phone'] = self::canonical_contact($contacts['phone'] ?? '');
        $out['whatsapp'] = self::canonical_contact($contacts['whatsapp'] ?? '');
        $out['photo_id'] = is_array($media['avatar'] ?? null) ? max(0, (int) ($media['avatar']['attachment_id'] ?? 0)) : 0;
        $out['cover_id'] = is_array($media['cover'] ?? null) ? max(0, (int) ($media['cover']['attachment_id'] ?? 0)) : 0;

        return $out;
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

    /** @param array<string,mixed> $source @return array<string,string> */
    private function normalize_professional(array $source): array
    {
        $profile = [];
        foreach (['qualification', 'institution', 'licensing_authority', 'experience_years', 'specialty', 'languages', 'consultation_modes'] as $field) {
            if (! array_key_exists($field, $source)) {
                continue;
            }
            $value = $this->scalar_or_list($source[$field]);
            if ($value !== '') {
                $profile[$field] = $this->plain_text($value, 1000);
            }
        }

        return $profile;
    }

    private function scalar_or_list(mixed $value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }
        if (! is_array($value)) {
            return '';
        }
        $items = [];
        foreach (array_slice($value, 0, 50) as $item) {
            if (! is_scalar($item)) {
                continue;
            }
            $clean = trim((string) $item);
            if ($clean !== '' && ! in_array($clean, $items, true)) {
                $items[] = $clean;
            }
        }

        return implode(', ', $items);
    }

    private static function canonical_contact(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $raw = trim((string) $value);
        if ($raw === '' || preg_match('/[\x00-\x1F\x7F]/', $raw) === 1) {
            return '';
        }
        $clean = preg_replace('/[\s().-]+/u', '', $raw) ?? '';
        if (preg_match('/^\+?[0-9]{7,15}$/', $clean) !== 1) {
            return '';
        }
        if (str_starts_with($clean, '+') && preg_match('/^\+[1-9][0-9]{6,14}$/', $clean) !== 1) {
            return '';
        }

        return $clean;
    }

    private static function strict_boolean_filter(mixed $value): bool
    {
        return $value === true;
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
