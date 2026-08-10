<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
    define('SMC_VERSION', '1.2.4');
    define('SMC_CONTRACT_VERSION', '1.1.2');
    define('SPD_VERSION', '1.2.0-rc2');
    define('SPD_CONTRACT_VERSION', '1.4.0');
    define('GDO_VERSION', '1.3.0');
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
        public function __construct(int $id) { $this->ID = $id; $this->display_name = 'User ' . $id; $this->user_nicename = 'user-' . $id; }
    }

    final class SMC_Contracts
    {
        /** @var array<int,array<string,mixed>> */
        public static array $assertions = [];
        /** @return array<string,mixed> */
        public static function assertions(int $user_id): array { return self::$assertions[$user_id] ?? []; }
    }

    final class GDO_Integration_Contracts { public const VERSION = '1.1.0'; }

    /** @var array<int,array<string,mixed>> */
    $spd_profiles = [];
    /** @var array<int,array<string,mixed>> */
    $gdo_eligibility = [];
    $force_grant_doctor_filter = false;

    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '-', strtolower($value)), '-'); }
    function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
    function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
    function is_wp_error(mixed $value): bool { return false; }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        global $force_grant_doctor_filter;
        if ($hook === 'sabri_public_experience/is_verified_doctor' && $force_grant_doctor_filter) { return true; }
        return $value;
    }
    function get_user_by(string $field, int|string $value): WP_User|false { return $field === 'id' && (int) $value > 0 ? new WP_User((int) $value) : false; }
    function smc_founder_user_id(): int { return 1; }
    function smc_is_founder(int $user_id): bool { return $user_id === 1; }

    function spd_get_profile_contract_manifest(): array
    {
        return [
            'owner_key' => 'file03',
            'contract_version' => '1.4.0',
            'queries' => ['get_public_profile' => 'spd_get_public_profile'],
        ];
    }
    function spd_get_public_profile(int $user_id, int $viewer_id = 0): array
    {
        global $spd_profiles;
        unset($viewer_id);
        return $spd_profiles[$user_id] ?? [];
    }
    function gdo_file03_doctor_eligibility(int $user_id): array
    {
        global $gdo_eligibility;
        return $gdo_eligibility[$user_id] ?? [
            'version' => '1.1.0', 'source_of_truth' => 'file09', 'consumer' => 'file03',
            'verified' => false, 'eligible' => false, 'state' => 'not_applied',
            'verified_until' => '', 'fingerprint' => '',
        ];
    }

    function swc_get_public_clinic_projection(int $user_id): array
    {
        if ($user_id !== 7) { return []; }
        return [
            'contract_version' => '1.0.0',
            'clinic' => [
                'name' => 'Global Clinic', 'address' => 'Main Road', 'country' => 'Pakistan',
                'city' => 'Gujrat', 'hours' => 'Mon–Fri 09:00–17:00', 'timezone' => 'Asia/Karachi',
                'phone' => '+923001234567',
            ],
        ];
    }
    function swc_public_clinic_projection_contract(): array
    {
        return [
            'contract_version' => '1.0.0', 'owner' => 'file-08',
            'fields' => ['name', 'address', 'country', 'city', 'hours', 'timezone'],
            'excludes' => ['phone','whatsapp','email','user_id','native_id','verification_evidence','appointments','patient_data','private_notes','analytics','payments','clinical_records','messages'],
            'writes_data' => false,
        ];
    }

    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-native-integration.php';

    use Sabri\PublicExperience\Native_Integration;

    $base = static function (int $user_id, string $type = 'member'): array {
        return [
            'contract_version' => '1.1.2', 'user_id' => $user_id, 'application_exists' => true,
            'institutional_account' => false, 'account_class' => 'member', 'membership_type' => $type,
            'status' => 'approved', 'approved' => true, 'suspended' => false, 'eligible' => true,
            'guardian_verified' => true, 'professional_verified' => $type === 'doctor',
            'can_practice' => $type === 'doctor', 'public_profile_allowed' => true, 'minor' => false,
            'guardian_required' => false,
        ];
    };
    SMC_Contracts::$assertions[1] = array_merge($base(1), ['institutional_account' => true, 'account_class' => 'founder', 'membership_type' => '']);
    foreach ([7,8,9,11,13,14,15] as $doctor_id) { SMC_Contracts::$assertions[$doctor_id] = $base($doctor_id, 'doctor'); }
    SMC_Contracts::$assertions[10] = $base(10, 'member');
    SMC_Contracts::$assertions[12] = array_merge($base(12), ['contract_version' => '1.1.1']);

    $public_doctor = static function (int $id, bool $verified = true, array $professional = []): array {
        return [
            'contract_version' => '1.4.0',
            'public_id' => sprintf('00000000-0000-4000-8000-%012d', $id),
            'canonical_url' => 'https://example.test/profile/doctor-' . $id . '/',
            'timeline_url' => 'https://example.test/profile/doctor-' . $id . '/timeline/',
            'report_url' => 'https://example.test/profile/doctor-' . $id . '/report/',
            'profile_type' => 'doctor', 'state' => 'active', 'version' => 1,
            'display_name' => 'Doctor ' . $id,
            'badge' => ['verified' => $verified, 'key' => $verified ? 'verified' : 'member'],
            'fields' => ['bio' => 'Public biography', 'city' => 'Gujrat', 'country' => 'Pakistan'],
            'media' => [], 'contacts' => [],
            'professional' => $professional,
            'founder' => [], 'clinic' => [],
        ];
    };
    $professional = [
        'qualification' => 'DHMS', 'institution' => 'Institute', 'licensing_authority' => 'National Council',
        'experience_years' => '12', 'specialty' => 'Classical Homeopathy',
        'languages' => ['Urdu','English'], 'consultation_modes' => ['Online','In person'],
    ];
    foreach ([7,8,9,13,14,15] as $id) { $spd_profiles[$id] = $public_doctor($id, true, $professional); }
    $spd_profiles[11] = $public_doctor(11, true, []);
    $spd_profiles[10] = [
        'contract_version'=>'1.4.0','public_id'=>'00000000-0000-4000-8000-000000000010',
        'canonical_url'=>'https://example.test/profile/member-10/','timeline_url'=>'https://example.test/profile/member-10/timeline/',
        'report_url'=>'https://example.test/profile/member-10/report/','profile_type'=>'member','state'=>'active','version'=>1,
        'display_name'=>'Member 10','badge'=>['verified'=>false],'fields'=>[],'media'=>[],'contacts'=>[],'professional'=>[],'founder'=>[],'clinic'=>[],
    ];
    $spd_profiles[1] = array_merge($spd_profiles[10], [
        'public_id'=>'00000000-0000-4000-8000-000000000001','canonical_url'=>'https://example.test/founder/',
        'timeline_url'=>'https://example.test/profile/founder/timeline/','report_url'=>'https://example.test/profile/founder/report/',
        'profile_type'=>'founder','display_name'=>'Founder','badge'=>['verified'=>true],
    ]);

    $valid = static fn (string $fingerprint): array => [
        'version'=>'1.1.0','source_of_truth'=>'file09','consumer'=>'file03','verified'=>true,'eligible'=>true,'limited'=>false,
        'state'=>'verified','verified_until'=>'2099-12-31','fingerprint'=>$fingerprint,
    ];
    $gdo_eligibility[7] = $valid(str_repeat('a', 64));
    $gdo_eligibility[8] = ['version'=>'1.1.0','source_of_truth'=>'file09','consumer'=>'file03','verified'=>false,'eligible'=>false,'state'=>'rejected','verified_until'=>'','fingerprint'=>''];
    $gdo_eligibility[9] = array_merge($valid(str_repeat('b', 64)), ['eligible'=>false]);
    $gdo_eligibility[11] = $valid(str_repeat('c', 64));
    $gdo_eligibility[13] = $valid('not-a-valid-fingerprint');
    $gdo_eligibility[14] = array_merge($valid(str_repeat('d', 64)), ['verified_until'=>'2000-01-01']);
    $gdo_eligibility[15] = array_merge($valid(str_repeat('e', 64)), ['verified_until'=>'2099-02-30']);

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) { $failures[] = $message; } };
    $native = new Native_Integration();

    $check($native->membership_available(), 'File 00 1.2.4 / contract 1.1.2 must be accepted.');
    $check($native->profiles_available(), 'File 03 1.2.0-rc2 / contract 1.4.0 must be accepted through current public DTO APIs.');
    $check($native->doctor_verification_available(), 'File 09 1.3.0 / integration contract 1.1.0 must be accepted.');
    $check($native->founder_user_id() === 1, 'Founder identity must come only from File 00.');
    $check($native->is_verified_doctor(7), 'Doctor requires File 00, File 09 1.1.0 eligibility and File 03 verified public professional projection.');
    $check(! $native->is_verified_doctor(8), 'Rejected File 09 decision must fail closed.');
    $check(! $native->is_verified_doctor(9), 'File 09 ineligible decision must fail closed.');
    $check(! $native->is_verified_doctor(11), 'Verified decision without a public professional projection must fail closed.');
    $check(! $native->is_verified_doctor(13), 'Malformed verification fingerprint must fail closed.');
    $check(! $native->is_verified_doctor(14), 'Expired verification must fail closed.');
    $check(! $native->is_verified_doctor(15), 'Invalid verification date must fail closed.');
    $check($native->membership_assertions(12) === [], 'Mismatched File 00 assertion contract must fail closed.');
    $check($native->clinic_available(), 'Exact File 08 0.2.1 owner projection must be available.');

    $credentials = $native->professional_credentials(7);
    $check(($credentials['qualification'] ?? '') === 'DHMS', 'Qualification must come from the current public professional projection.');
    $check(($credentials['council'] ?? '') === 'National Council', 'Licensing authority must be normalized from File 03/File 09 public projection.');
    $check(! array_key_exists('license_number', $credentials), 'Sensitive license number must not enter File 25 public credentials.');
    $check($native->profile_value(7, 'phone', '') === '', 'Contact must not bypass File 03 public-contact projection.');

    $clinic = $native->clinic(7);
    $check(($clinic['name'] ?? '') === 'Global Clinic', 'Clinic must come from File 08 public projection.');
    $check(! array_key_exists('phone', $clinic), 'File 08 contact must not bypass File 03 contact consent.');
    $check($native->public_visibility(7) === 'public', 'File 00 + File 03 must jointly control public visibility.');

    $force_grant_doctor_filter = true;
    $check(! $native->is_verified_doctor(8), 'A File 25 filter must not grant Doctor verification denied by native owners.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 current authoritative File 00/03/08/09 contracts\n";
}
