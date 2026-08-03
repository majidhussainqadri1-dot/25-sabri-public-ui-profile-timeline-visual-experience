<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    if (! defined('SMC_VERSION')) { define('SMC_VERSION', '1.2.4'); }
    if (! defined('SMC_CONTRACT_VERSION')) { define('SMC_CONTRACT_VERSION', '1.1.2'); }
    if (! defined('SPD_VERSION')) { define('SPD_VERSION', '0.2.0'); }
    if (! defined('GDO_VERSION')) { define('GDO_VERSION', '1.1.0'); }
    if (! defined('SWC_VERSION')) { define('SWC_VERSION', '0.2.1'); }
    if (! defined('SWC_PUBLIC_CLINIC_CONTRACT_VERSION')) { define('SWC_PUBLIC_CLINIC_CONTRACT_VERSION', '1.0.0'); }

    final class WP_User
    {
        public string $display_name = 'User';
        public string $user_nicename = 'user';
        public string $description = '';
        public array $roles = [];
        public function __construct(public int $ID) {}
    }

    final class SMC_Contracts
    {
        public static array $assertions = [];
        public static function assertions(int $user_id): array { return self::$assertions[$user_id] ?? []; }
    }

    final class SPD_Helpers
    {
        public static string $status = 'verified';
        public static function verification_status(int $user_id): string { return self::$status; }
        public static function get(int $user_id, string $key, string $default = ''): string { return $default; }
        public static function founder(): array { return []; }
        public static function can_show_contact(int $user_id, bool $founder = false): bool { return false; }
    }

    $r3953_founder_id = 1;
    $r3953_decisions = [];
    $r3953_snapshots = [];
    $r3953_helper = [];

    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower(trim($value))), '-'); }
    function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
    function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed { return $value; }
    function get_user_by(string $field, int|string $value): WP_User|false { return $field === 'id' && (int) $value > 0 ? new WP_User((int) $value) : false; }
    function smc_founder_user_id(): int { global $r3953_founder_id; return $r3953_founder_id; }
    function smc_is_founder(int $user_id): bool { global $r3953_founder_id; return $user_id === $r3953_founder_id; }
    function gdo_get_verification_decision(int $user_id): array { global $r3953_decisions; return $r3953_decisions[$user_id] ?? ['state' => 'not_applied', 'verified' => false, 'verified_until' => '', 'fingerprint' => '']; }
    function gdo_get_approved_snapshot(int $user_id): array { global $r3953_snapshots; return $r3953_snapshots[$user_id] ?? []; }
    function gdo_user_is_verified(int $user_id): bool { global $r3953_helper; return $r3953_helper[$user_id] ?? ! empty(gdo_get_verification_decision($user_id)['verified']); }
    function swc_get_public_clinic_projection(int $user_id): array { return []; }
    function swc_public_clinic_projection_contract(): array {
        return [
            'contract_version' => '1.0.0', 'owner' => 'file-08',
            'fields' => ['name','address','country','city','hours','timezone'],
            'excludes' => ['phone','whatsapp','email','user_id','native_id','appointments','patient_data'],
            'writes_data' => false,
        ];
    }

    require_once dirname(__DIR__) . '/includes/class-file-24-integration.php';
    require_once dirname(__DIR__) . '/includes/class-native-integration.php';

    function r3953_assertions(int $id, string $type = 'member'): array
    {
        return [
            'contract_version' => '1.1.2', 'user_id' => $id,
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
        return ['state' => 'verified', 'verified' => true, 'verified_until' => '2099-12-31', 'fingerprint' => str_repeat($char, 64)];
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
