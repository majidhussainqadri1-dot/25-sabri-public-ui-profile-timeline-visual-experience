<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    define('SMC_VERSION', '1.2.4');
    define('SMC_CONTRACT_VERSION', '1.1.2');
    define('SPD_VERSION', '0.2.0');
    define('GDO_VERSION', '1.1.0');
    define('SWC_VERSION', '0.2.1');
    define('SWC_PUBLIC_CLINIC_CONTRACT_VERSION', '1.0.0');

    final class WP_User
    {
        public int $ID;
        public string $display_name;
        public string $user_nicename;
        public string $description = '';
        /** @var list<string> */
        public array $roles = [];

        public function __construct(int $id)
        {
            $this->ID = $id;
            $this->display_name = 'User ' . $id;
            $this->user_nicename = 'user-' . $id;
        }
    }

    final class SMC_Contracts
    {
        /** @var array<int,array<string,mixed>> */
        public static array $assertions = [];

        /** @return array<string,mixed> */
        public static function assertions(int $user_id): array
        {
            return self::$assertions[$user_id] ?? [];
        }
    }

    final class SPD_Helpers
    {
        public static string $status = 'verified';
        public static function verification_status(int $user_id): string { return self::$status; }
        public static function get(int $user_id, string $key, string $default = ''): string { return $default; }
        public static function founder(): array { return []; }
    }

    /** @var array<int,array<string,mixed>> */
    $gdo_decisions = [];
    /** @var array<int,array<string,mixed>> */
    $gdo_snapshots = [];
    /** @var array<int,bool> */
    $gdo_helper_verified_overrides = [];
    $force_grant_doctor_filter = false;

    function sanitize_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-');
    }
    function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
    function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        global $force_grant_doctor_filter;
        if ($hook === 'sabri_public_experience/is_verified_doctor' && $force_grant_doctor_filter) { return true; }
        return $value;
    }
    function get_user_by(string $field, int|string $value): WP_User|false
    {
        return $field === 'id' && (int) $value > 0 ? new WP_User((int) $value) : false;
    }
    function smc_founder_user_id(): int { return 1; }
    function smc_is_founder(int $user_id): bool { return $user_id === 1; }
    function gdo_get_verification_decision(int $user_id): array
    {
        global $gdo_decisions;
        return $gdo_decisions[$user_id] ?? ['state' => 'not_applied', 'verified' => false];
    }
    function gdo_get_approved_snapshot(int $user_id): array
    {
        global $gdo_snapshots;
        return $gdo_snapshots[$user_id] ?? [];
    }
    function gdo_user_is_verified(int $user_id): bool
    {
        global $gdo_helper_verified_overrides;
        if (array_key_exists($user_id, $gdo_helper_verified_overrides)) {
            return $gdo_helper_verified_overrides[$user_id];
        }
        return ! empty(gdo_get_verification_decision($user_id)['verified']);
    }
    function swc_get_public_clinic_projection(int $user_id): array
    {
        if ($user_id !== 7) { return []; }
        return [
            'contract_version' => '1.0.0',
            'clinic' => [
                'name' => 'Global Clinic',
                'address' => 'Main Road',
                'country' => 'Pakistan',
                'city' => 'Gujrat',
                'hours' => 'Mon–Fri 09:00–17:00',
                'timezone' => 'Asia/Karachi',
                'phone' => '+923001234567',
            ],
        ];
    }
    function swc_public_clinic_projection_contract(): array
    {
        return [
            'contract_version' => '1.0.0',
            'owner' => 'file-08',
            'fields' => ['name', 'address', 'country', 'city', 'hours', 'timezone'],
            'writes_data' => false,
        ];
    }

    require_once dirname(__DIR__) . '/includes/class-native-integration.php';

    use Sabri\PublicExperience\Native_Integration;

    $base = static function (int $user_id, string $type = 'member'): array {
        return [
            'contract_version' => '1.1.2',
            'user_id' => $user_id,
            'application_exists' => true,
            'institutional_account' => false,
            'account_class' => 'member',
            'membership_type' => $type,
            'status' => 'approved',
            'approved' => true,
            'suspended' => false,
            'eligible' => true,
            'guardian_verified' => true,
            'professional_verified' => $type === 'doctor',
            'can_practice' => $type === 'doctor',
            'public_profile_allowed' => true,
        ];
    };

    SMC_Contracts::$assertions[1] = array_merge($base(1), [
        'institutional_account' => true,
        'account_class' => 'founder',
        'membership_type' => '',
    ]);
    foreach ([7, 8, 9, 11, 13, 14, 15] as $doctor_id) {
        SMC_Contracts::$assertions[$doctor_id] = $base($doctor_id, 'doctor');
    }
    SMC_Contracts::$assertions[10] = $base(10, 'member');
    SMC_Contracts::$assertions[12] = array_merge($base(12), ['contract_version' => '1.1.1']);

    $approved_profile = [
        'profile' => [
            'qualification' => 'DHMS',
            'licensing_authority' => 'National Council',
            'experience_years' => '12',
            'specialty' => 'Classical Homeopathy',
            'languages' => 'Urdu, English',
            'consultation_modes' => 'Online, In person',
            'phone' => '+923001234567',
            'whatsapp' => '+923001234567',
            'bio' => 'Approved public professional biography.',
        ],
        'evidence' => ['qualification' => ['decision' => 'approved']],
    ];
    $valid_decision = static fn (string $fingerprint): array => [
        'state' => 'verified',
        'verified' => true,
        'verified_until' => '2099-12-31',
        'fingerprint' => $fingerprint,
    ];

    $gdo_decisions[7] = $valid_decision(str_repeat('a', 64));
    $gdo_snapshots[7] = $approved_profile;
    $gdo_decisions[8] = ['state' => 'rejected', 'verified' => false];
    $gdo_decisions[9] = $valid_decision(str_repeat('b', 64));
    $gdo_snapshots[9] = $approved_profile;
    $gdo_helper_verified_overrides[9] = false;
    $gdo_decisions[11] = $valid_decision(str_repeat('c', 64));
    $gdo_decisions[13] = $valid_decision('not-a-valid-fingerprint');
    $gdo_snapshots[13] = $approved_profile;
    $gdo_decisions[14] = [
        'state' => 'verified',
        'verified' => true,
        'verified_until' => '2000-01-01',
        'fingerprint' => str_repeat('d', 64),
    ];
    $gdo_snapshots[14] = $approved_profile;
    $gdo_decisions[15] = [
        'state' => 'verified',
        'verified' => true,
        'verified_until' => '2099-02-30',
        'fingerprint' => str_repeat('e', 64),
    ];
    $gdo_snapshots[15] = $approved_profile;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) { $failures[] = $message; }
    };

    $native = new Native_Integration();
    $check($native->membership_available(), 'File 00 1.2.4 / contract 1.1.2 must be accepted.');
    $check($native->founder_user_id() === 1, 'Founder identity must come only from File 00.');
    $check($native->is_verified_doctor(7), 'Doctor requires File 00 eligibility, File 09 helper/decision, current validity, and a non-empty approved snapshot.');
    $check(! $native->is_verified_doctor(8), 'Rejected File 09 decision must fail closed.');
    $check(! $native->is_verified_doctor(9), 'File 09 helper/decision disagreement must fail closed.');
    $check(! $native->is_verified_doctor(11), 'A verified decision without a non-empty approved snapshot must fail closed.');
    $check(! $native->is_verified_doctor(13), 'A malformed approved-snapshot fingerprint must fail closed.');
    $check(! $native->is_verified_doctor(14), 'An expired File 09 verification must fail closed even when another helper reports verified.');
    $check(! $native->is_verified_doctor(15), 'An invalid File 09 validity date must fail closed.');
    $check($native->membership_assertions(12) === [], 'Mismatched File 00 assertion contract must fail closed.');
    $check($native->clinic_available(), 'Exact File 08 0.2.1 owner projection must be available.');
    $check((swc_public_clinic_projection_contract()['contract_version'] ?? '') === '1.0.0', 'File 08 contract introspection must report 1.0.0.');

    $credentials = $native->professional_credentials(7);
    $check(($credentials['qualification'] ?? '') === 'DHMS', 'Qualification must come from the File 09 approved snapshot.');
    $check(($credentials['council'] ?? '') === 'National Council', 'Licensing authority must be normalized from File 09.');
    $check(! array_key_exists('license_number', $credentials), 'Sensitive license number must not enter File 25 public credentials.');
    $check($native->profile_value(7, 'phone', '') === '', 'File 09 contact values must not bypass File 03 profile/contact ownership.');
    $check($native->profile_value(7, 'whatsapp', '') === '', 'File 09 WhatsApp values must not bypass File 03 profile/contact ownership.');

    $clinic = $native->clinic(7);
    $check(($clinic['name'] ?? '') === 'Global Clinic', 'Clinic must come from the File 08 public projection.');
    $check(! array_key_exists('phone', $clinic), 'File 08 clinic contact must not bypass File 03 contact consent.');
    $check($native->public_visibility(7) === 'public', 'File 00 public-profile authorization must control public visibility.');
    $check($native->is_minor(10), 'Unknown ordinary minor state must fail closed for contact projection without local age calculation.');

    $force_grant_doctor_filter = true;
    $check(! $native->is_verified_doctor(8), 'A filter must not grant Doctor verification denied by authoritative owners.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 authoritative File 00/08/09 current-decision and snapshot contracts\n";
}
