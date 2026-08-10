<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
    define('SMC_VERSION', '1.2.4');
    define('SMC_CONTRACT_VERSION', '1.1.2');
    define('SPD_VERSION', '1.2.0-rc2');
    define('SPD_CONTRACT_VERSION', '1.4.0');
    define('GDO_VERSION', '1.3.0');
    define('SWC_VERSION', '0.2.1');
    define('SWC_PUBLIC_CLINIC_CONTRACT_VERSION', '1.0.0');

    $GLOBALS['spux_test_filters'] = [];
    $GLOBALS['spux_file03_contacts'] = ['phone' => '+923001234567', 'whatsapp' => '+923009876543'];

    function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    { $GLOBALS['spux_test_filters'][$hook][$priority][] = [$callback, $acceptedArgs]; return true; }
    function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    { return add_filter($hook, $callback, $priority, $acceptedArgs); }
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $callbacks = $GLOBALS['spux_test_filters'][$hook] ?? [];
        ksort($callbacks, SORT_NUMERIC);
        foreach ($callbacks as $rows) {
            foreach ($rows as [$callback, $accepted]) {
                $value = $callback(...array_slice([$value, ...$args], 0, max(1, (int) $accepted)));
            }
        }
        return $value;
    }
    function wp_add_inline_style(string $handle, string $css): bool { return true; }
    function home_url(string $path = '/'): string { return 'https://example.test/' . ltrim($path, '/'); }
    function wp_parse_url(string $url): array|false { return parse_url($url); }
    function sanitize_key(string $value): string { return trim((string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($value)), '-'); }
    function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
    function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
    function esc_url_raw(string $url, array $protocols = []): string { return $url; }
    function esc_url(string $url): string { return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_attr(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_html(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function __(string $value, string $domain = ''): string { return $value; }
    function is_user_logged_in(): bool { return true; }
    function current_user_can(string $capability): bool { return true; }
    function get_current_user_id(): int { return 42; }
    function get_user_by(string $field, int|string $value): WP_User|false { return $field === 'id' ? new WP_User((int) $value) : false; }
    function smc_founder_user_id(): int { return 42; }
    function smc_is_founder(int $userId): bool { return $userId === 42; }

    final class WP_User { public string $display_name='Founder'; public string $user_nicename='founder'; public string $description=''; public array $roles=[]; public function __construct(public int $ID) {} }
    final class SMC_Contracts
    {
        public static function assertions(int $userId): array
        {
            return ['contract_version'=>'1.1.2','user_id'=>$userId,'account_class'=>'institutional','membership_type'=>'','status'=>'approved','approved'=>true,'suspended'=>false,'eligible'=>true,'public_profile_allowed'=>true,'minor'=>false,'guardian_required'=>false];
        }
    }
    final class GDO_Integration_Contracts { public const VERSION = '1.1.0'; }

    function spd_get_profile_contract_manifest(): array
    { return ['owner_key'=>'file03','contract_version'=>'1.4.0','queries'=>['get_public_profile'=>'spd_get_public_profile']]; }
    function spd_get_public_profile(int $userId, int $viewerId = 0): array
    {
        return [
            'contract_version'=>'1.4.0','public_id'=>'00000000-0000-4000-8000-000000000042','canonical_url'=>'https://example.test/founder/','timeline_url'=>'https://example.test/founder/timeline/','report_url'=>'https://example.test/founder/report/','profile_type'=>'founder','state'=>'active','version'=>1,'display_name'=>'Founder','badge'=>['verified'=>true],'fields'=>[],'media'=>[],'contacts'=>$GLOBALS['spux_file03_contacts'],'professional'=>[],'founder'=>[],'clinic'=>[],
        ];
    }
    function gdo_file03_doctor_eligibility(int $userId): array { return ['version'=>'1.1.0','source_of_truth'=>'file09','consumer'=>'file03','verified'=>false,'eligible'=>false,'state'=>'not_applied','verified_until'=>'','fingerprint'=>'']; }
    function swc_get_public_clinic_projection(int $userId): array { return []; }
    function swc_public_clinic_projection_contract(): array { return ['contract_version'=>'1.0.0','owner'=>'file-08','fields'=>['name','address','country','city','hours','timezone'],'excludes'=>['phone','whatsapp','email','user_id','native_id','appointments','patient_data'],'writes_data'=>false]; }
}

namespace Sabri\PublicExperience {
    if (! function_exists(__NAMESPACE__ . '\\wp_strip_all_tags')) {
        function wp_strip_all_tags(string $text, bool $removeBreaks = false): string { $text = strip_tags($text); return $removeBreaks ? (preg_replace('/[\r\n\t ]+/', ' ', $text) ?? '') : $text; }
    }

    $root = dirname(__DIR__);
    require_once $root . '/includes/class-plan-completion.php';
    require_once $root . '/includes/class-public-url.php';
    require_once $root . '/includes/class-file-24-integration.php';
    require_once $root . '/includes/class-native-integration.php';
    require_once $root . '/includes/class-visibility-policy.php';
    require_once $root . '/includes/class-profile-repository.php';
    require_once $root . '/includes/class-three-plan-corrections.php';
    Three_Plan_Corrections::register();

    $failures = [];
    $assert = static function (bool $condition, string $message) use (&$failures): void { if (! $condition) { $failures[] = $message; } };
    $native = new Native_Integration();
    $visibility = new Visibility_Policy($native);
    $repository = new Profile_Repository($visibility, $native);
    $contactsMethod = new \ReflectionMethod(Profile_Repository::class, 'public_contacts');
    $contactsMethod->setAccessible(true);

    $canonical = ['phone'=>'+923001234567','whatsapp'=>'+923009876543'];
    $assert($contactsMethod->invoke($repository, 42) === $canonical, 'Current File 03 public contacts must remain visible by default after owner consent.');

    add_filter('sabri_public_experience/public_contacts', static fn (array $decisions): array => ['phone'=>true,'whatsapp'=>false], 10, 1);
    $assert($contactsMethod->invoke($repository, 42) === ['phone'=>'+923001234567'], 'Presentation hook may revoke a canonical contact field.');

    $GLOBALS['spux_test_filters']['sabri_public_experience/public_contacts'][10] = [];
    add_filter('sabri_public_experience/public_contacts', static fn (array $decisions): array => ['phone'=>'+923001111111','whatsapp'=>true], 10, 1);
    $assert($contactsMethod->invoke($repository, 42) === ['whatsapp'=>'+923009876543'], 'Presentation hook must never substitute File 03 canonical contact destination.');

    $operate = new \ReflectionMethod(Plan_Completion::class, 'can_operate');
    $operate->setAccessible(true);
    $assert($operate->invoke(null, 'migration_execute') === false, 'High-risk migration must fail closed without approval.');
    add_filter('sabri_public_experience/high_risk_authorized', static fn (bool $authorized): bool => true, 10, 1);
    $assert($operate->invoke(null, 'migration_execute') === true, 'High-risk migration may proceed only after explicit approval.');

    $assert(Three_Plan_Corrections::render_welcome_panel(['title'=>'Welcome','message'=>'One coherent Sabri platform experience.','continue_url'=>'/home/']) === '', 'File 25 must not render welcome unless File 20 invokes it.');
    $welcome = Three_Plan_Corrections::render_welcome_panel(['invoked_by_file_20'=>true,'title'=>'Welcome','message'=>'One coherent Sabri platform experience.','continue_url'=>'/home/','skip_label'=>'Skip']);
    foreach (['data-directive="CHAT-UX-001"','data-frequency-owner="file-20"','data-visual-owner="file-25"','sabri-ui-welcome-panel'] as $marker) { $assert(str_contains($welcome, $marker), 'Welcome ownership marker missing: ' . $marker); }

    $matrixRaw = file_get_contents($root . '/config/all-chats-directive-matrix.json');
    $matrix = is_string($matrixRaw) ? json_decode($matrixRaw, true) : [];
    $ids = array_column((array) ($matrix['directives'] ?? []), 'id');
    foreach (['CHAT-UX-001','CHAT-UX-002','CHAT-UX-003','CHAT-UX-004','CHAT-DOC-001','CHAT-DL-001','CHAT-QA-001','CHAT-DOC-021','CHAT-REC-024','RCD-001'] as $id) { $assert(in_array($id, $ids, true), 'Missing All-Chats traceability ID: ' . $id); }
    $assert(($matrix['external_acceptance']['hostinger_staging'] ?? null) === false, 'Hostinger acceptance must remain false.');
    $assert(($matrix['external_acceptance']['founder_acceptance'] ?? null) === false, 'Founder acceptance must remain false.');

    if ($failures !== []) { foreach ($failures as $failure) { fwrite(STDERR, '[FAIL] ' . $failure . "\n"); } exit(1); }
    echo "Reviews 174-176 current three-plan corrective behavioral checks passed.\n";
}
