<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! defined('SMC_VERSION')) { define('SMC_VERSION', '1.2.38'); }
    if (! defined('SMC_CONTRACT_VERSION')) { define('SMC_CONTRACT_VERSION', '1.2.2'); }
    if (! defined('SPD_VERSION')) { define('SPD_VERSION', '1.2.0-rc2'); }
    if (! defined('SPD_CONTRACT_VERSION')) { define('SPD_CONTRACT_VERSION', '1.4.0'); }
    if (! defined('GDO_VERSION')) { define('GDO_VERSION', '1.3.0'); }
    if (! defined('SWC_VERSION')) { define('SWC_VERSION', '0.2.1'); }
    if (! defined('SWC_PUBLIC_CLINIC_CONTRACT_VERSION')) { define('SWC_PUBLIC_CLINIC_CONTRACT_VERSION', '1.0.0'); }

    final class WP_User
    {
        public string $display_name = 'User';
        public string $user_nicename = 'user';
        public string $description = '';
        public array $roles = [];
        public function __construct(public int $ID)
        {
            $this->display_name = 'User ' . $ID;
            $this->user_nicename = 'user-' . $ID;
        }
    }

    final class SMC_Contracts
    {
        public static array $assertions = [];
        public static function assertions(int $user_id): array { return self::$assertions[$user_id] ?? []; }
    }

    final class GDO_Integration_Contracts { public const VERSION = '1.1.0'; }

    $r3953_founder_id = 1;
    $r3953_decisions = [];
    $r3953_snapshots = [];
    $r3953_helper = [];
    $r3953_profiles = [];

    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower(trim($value))), '-'); }
    function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
    function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function get_user_by(string $field, int|string $value): WP_User|false { return $field === 'id' && (int) $value > 0 ? new WP_User((int) $value) : false; }
    function home_url(string $path = '/'): string { return 'https://example.test' . '/' . ltrim($path, '/'); }
    function wp_parse_url(string $url): array|false { return parse_url($url); }
    function smc_founder_user_id(): int { global $r3953_founder_id; return $r3953_founder_id; }
    function smc_is_founder(int $user_id): bool { global $r3953_founder_id; return $user_id === $r3953_founder_id; }

    function spd_get_profile_contract_manifest(): array
    {
        return ['owner_key' => 'file03', 'contract_version' => '1.4.0', 'queries' => ['get_public_profile' => 'spd_get_public_profile']];
    }

    function spd_get_public_profile(int $user_id, int $viewer_id = 0): array
    {
        global $r3953_profiles, $r3953_snapshots;
        unset($viewer_id);
        if (isset($r3953_profiles[$user_id]) && is_array($r3953_profiles[$user_id])) {
            return $r3953_profiles[$user_id];
        }
        $assertions = SMC_Contracts::$assertions[$user_id] ?? [];
        if ($assertions === []) {
            return [];
        }
        $type = $user_id === 1 ? 'founder' : (($assertions['membership_type'] ?? '') === 'doctor' ? 'doctor' : 'member');
        $snapshot = is_array($r3953_snapshots[$user_id]['profile'] ?? null) ? $r3953_snapshots[$user_id]['profile'] : [];
        $professional = [];
        foreach (['qualification','institution','licensing_authority','experience_years','specialty','languages','consultation_modes'] as $field) {
            if (array_key_exists($field, $snapshot)) { $professional[$field] = $snapshot[$field]; }
        }
        return [
            'contract_version' => '1.4.0',
            'public_id' => sprintf('00000000-0000-4000-8000-%012d', $user_id),
            'canonical_url' => $type === 'founder' ? 'https://example.test/founder/' : 'https://example.test/profile/user-' . $user_id . '/',
            'timeline_url' => 'https://example.test/profile/user-' . $user_id . '/timeline/',
            'report_url' => 'https://example.test/profile/user-' . $user_id . '/report/',
            'profile_type' => $type,
            'state' => 'active',
            'version' => 1,
            'display_name' => 'User ' . $user_id,
            'badge' => ['verified' => in_array($type, ['founder','doctor'], true)],
            'fields' => [],
            'media' => [],
            'contacts' => [],
            'professional' => $professional,
            'founder' => [],
            'clinic' => [],
        ];
    }

    function gdo_file03_doctor_eligibility(int $user_id): array
    {
        global $r3953_decisions;
        $decision = $r3953_decisions[$user_id] ?? ['state' => 'not_applied', 'verified' => false, 'verified_until' => '', 'fingerprint' => '', 'eligible' => false];
        if (! is_array($decision)) { return []; }
        return array_merge([
            'version' => '1.1.0',
            'source_of_truth' => 'file09',
            'consumer' => 'file03',
            'eligible' => ! empty($decision['verified']),
            'limited' => false,
        ], $decision);
    }

    // Legacy test helper remains only so old review fixtures can construct an
    // approved snapshot. Native_Integration no longer treats this as the primary
    // verification-decision contract.
    function gdo_get_approved_snapshot(int $user_id): array { global $r3953_snapshots; return $r3953_snapshots[$user_id] ?? []; }
    function gdo_get_verification_decision(int $user_id): array { global $r3953_decisions; return $r3953_decisions[$user_id] ?? []; }
    function gdo_user_is_verified(int $user_id): bool { global $r3953_helper; return $r3953_helper[$user_id] ?? ! empty(gdo_get_verification_decision($user_id)['verified']); }

    function swc_get_public_clinic_projection(int $user_id): array { return []; }
    function swc_public_clinic_projection_contract(): array
    {
        return [
            'contract_version' => '1.0.0', 'owner' => 'file-08',
            'fields' => ['name','address','country','city','hours','timezone'],
            'excludes' => ['phone','whatsapp','email','user_id','native_id','appointments','patient_data'],
            'writes_data' => false,
        ];
    }

    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-file-24-integration.php';
    require_once dirname(__DIR__) . '/includes/class-native-integration.php';

    function r3953_assertions(int $id, string $type = 'member'): array
    {
        return [
            'contract_version' => '1.2.2', 'user_id' => $id,
            'application_exists' => true, 'institutional_account' => false,
            'account_class' => 'member', 'membership_type' => $type, 'status' => 'approved',
            'approved' => true, 'suspended' => false, 'eligible' => true,
            'guardian_verified' => true, 'professional_verified' => $type === 'doctor',
            'can_practice' => $type === 'doctor', 'public_profile_allowed' => true,
            'minor' => false, 'guardian_required' => false,
        ];
    }

    function r3953_valid_decision(string $char = 'a'): array
    {
        return [
            'version' => '1.1.0', 'source_of_truth' => 'file09', 'consumer' => 'file03',
            'state' => 'verified', 'verified' => true, 'eligible' => true, 'limited' => false,
            'verified_until' => '2099-12-31', 'fingerprint' => str_repeat($char, 64),
        ];
    }

    function r3953_snapshot(): array
    {
        return ['profile' => ['qualification' => 'DHMS', 'licensing_authority' => 'Council']];
    }

    function r3953_native_assert(bool $condition, string $message): void
    {
        if (! $condition) { fwrite(STDERR, "FAILED: {$message}\n"); exit(1); }
    }
}
