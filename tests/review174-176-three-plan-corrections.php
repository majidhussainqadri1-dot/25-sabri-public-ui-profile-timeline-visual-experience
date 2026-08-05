<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }
    if (! defined('SMC_VERSION')) {
        define('SMC_VERSION', '1.2.4');
    }
    if (! defined('SMC_CONTRACT_VERSION')) {
        define('SMC_CONTRACT_VERSION', '1.1.2');
    }
    if (! defined('SPD_VERSION')) {
        define('SPD_VERSION', '0.2.0');
    }

    $GLOBALS['spux_test_filters'] = [];

    function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        $GLOBALS['spux_test_filters'][$hook][$priority][] = [$callback, $acceptedArgs];
        return true;
    }

    function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        return add_filter($hook, $callback, $priority, $acceptedArgs);
    }

    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $callbacks = $GLOBALS['spux_test_filters'][$hook] ?? [];
        ksort($callbacks, SORT_NUMERIC);
        foreach ($callbacks as $priorityCallbacks) {
            foreach ($priorityCallbacks as [$callback, $acceptedArgs]) {
                $parameters = array_slice([$value, ...$args], 0, max(1, (int) $acceptedArgs));
                $value = $callback(...$parameters);
            }
        }
        return $value;
    }

    function wp_add_inline_style(string $handle, string $css): bool
    {
        unset($handle, $css);
        return true;
    }

    function home_url(string $path = '/'): string
    {
        return 'https://example.test' . '/' . ltrim($path, '/');
    }

    function sanitize_key(string $value): string
    {
        $value = strtolower($value);
        return preg_replace('/[^a-z0-9_\-]/', '', $value) ?? '';
    }

    function esc_url_raw(string $url, array $protocols = []): string
    {
        unset($protocols);
        return $url;
    }

    function esc_url(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function esc_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function esc_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    function __(string $value, string $domain = ''): string
    {
        unset($domain);
        return $value;
    }

    function is_user_logged_in(): bool
    {
        return true;
    }

    function current_user_can(string $capability): bool
    {
        unset($capability);
        return true;
    }

    function get_current_user_id(): int
    {
        return 42;
    }

    function smc_founder_user_id(): int
    {
        return 42;
    }

    function smc_is_founder(int $userId): bool
    {
        return $userId === 42;
    }

    final class SMC_Contracts
    {
        /** @return array<string,mixed> */
        public static function assertions(int $userId): array
        {
            return [
                'contract_version' => '1.1.2',
                'user_id' => $userId,
                'account_class' => 'institutional',
                'membership_type' => 'founder',
                'status' => 'approved',
                'approved' => true,
                'suspended' => false,
                'eligible' => true,
                'public_profile_allowed' => true,
                'minor' => false,
                'guardian_required' => false,
            ];
        }
    }

    final class SPD_Helpers
    {
        public static function can_show_contact(int $userId, bool $founder): bool
        {
            return $userId === 42 && $founder;
        }

        public static function get(int $userId, string $key, string $default = ''): string
        {
            unset($userId, $key);
            return $default;
        }

        /** @return array<string,mixed> */
        public static function founder(): array
        {
            return [];
        }

        public static function verification_status(int $userId): string
        {
            unset($userId);
            return 'approved';
        }
    }
}

namespace Sabri\PublicExperience {
    if (! function_exists(__NAMESPACE__ . '\\wp_strip_all_tags')) {
        function wp_strip_all_tags(string $text, bool $removeBreaks = false): string
        {
            $text = strip_tags($text);
            return $removeBreaks ? (preg_replace('/[\r\n\t ]+/', ' ', $text) ?? '') : $text;
        }
    }

    $root = dirname(__DIR__);
    require_once $root . '/includes/class-plan-completion.php';
    require_once $root . '/includes/class-public-url.php';
    require_once $root . '/includes/class-native-integration.php';
    require_once $root . '/includes/class-visibility-policy.php';
    require_once $root . '/includes/class-profile-repository.php';
    require_once $root . '/includes/class-three-plan-corrections.php';
    Three_Plan_Corrections::register();

    $failures = [];
    $assert = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $native = new Native_Integration();
    $visibility = new Visibility_Policy($native);
    $repository = new Profile_Repository($visibility, $native);
    $contactsMethod = new \ReflectionMethod(Profile_Repository::class, 'public_contacts');
    $contactsMethod->setAccessible(true);

    $founderContacts = [
        'phone' => '+923001234567',
        'whatsapp' => '+923009876543',
    ];

    $defaultContacts = $contactsMethod->invoke($repository, 42, [], $founderContacts);
    $assert(
        $defaultContacts === $founderContacts,
        'Default File 03-owned contact strings must remain visible after consent approval.'
    );

    add_filter('sabri_public_experience/public_contacts', static fn (array $decisions): array => [
        'phone' => true,
        'whatsapp' => false,
    ], 10, 1);
    $revokedContacts = $contactsMethod->invoke($repository, 42, [], $founderContacts);
    $assert(
        $revokedContacts === ['phone' => '+923001234567'],
        'A filter may retain an exact canonical field and revoke another field.'
    );

    $GLOBALS['spux_test_filters']['sabri_public_experience/public_contacts'][10] = [];
    add_filter('sabri_public_experience/public_contacts', static fn (array $decisions): array => [
        'phone' => '+923001111111',
        'whatsapp' => true,
    ], 10, 1);
    $substitutionContacts = $contactsMethod->invoke($repository, 42, [], $founderContacts);
    $assert(
        $substitutionContacts === ['whatsapp' => '+923009876543'],
        'A filter must not substitute a different public contact destination.'
    );

    $operateMethod = new \ReflectionMethod(Plan_Completion::class, 'can_operate');
    $operateMethod->setAccessible(true);
    $assert(
        $operateMethod->invoke(null, 'migration_execute') === false,
        'High-risk migration execution must fail closed without explicit approval.'
    );
    add_filter('sabri_public_experience/high_risk_authorized', static fn (bool $authorized): bool => true, 10, 1);
    $assert(
        $operateMethod->invoke(null, 'migration_execute') === true,
        'High-risk migration execution may proceed only after strict explicit approval.'
    );

    $assert(
        Three_Plan_Corrections::render_welcome_panel([
            'title' => 'Welcome',
            'message' => 'One coherent Sabri platform experience.',
            'continue_url' => '/home/',
        ]) === '',
        'File 25 must not render the welcome primitive unless File 20 explicitly invokes it.'
    );
    $welcome = Three_Plan_Corrections::render_welcome_panel([
        'invoked_by_file_20' => true,
        'title' => 'Welcome',
        'message' => 'One coherent Sabri platform experience.',
        'continue_url' => '/home/',
        'skip_label' => 'Skip',
    ]);
    $assert(str_contains($welcome, 'data-directive="CHAT-UX-001"'), 'Welcome markup must bind to CHAT-UX-001.');
    $assert(str_contains($welcome, 'data-frequency-owner="file-20"'), 'File 20 must remain the frequency owner.');
    $assert(str_contains($welcome, 'data-visual-owner="file-25"'), 'File 25 must remain the visual owner only.');
    $assert(str_contains($welcome, 'sabri-ui-welcome-panel'), 'Welcome visual primitive must be rendered accessibly.');

    $security = file_get_contents($root . '/SECURITY.md');
    $assert(is_string($security), 'SECURITY.md must be readable.');
    if (is_string($security)) {
        $assert(! str_contains($security, 'has not completed File 24 integration'), 'Stale File 24 integration contradiction must be removed.');
        $assert(str_contains($security, 'reviewed File 24 source-contract integration is implemented'), 'Security status must state the reviewed source-contract boundary truthfully.');
    }

    $matrixRaw = file_get_contents($root . '/config/all-chats-directive-matrix.json');
    $assert(is_string($matrixRaw) && $matrixRaw !== '', 'All-Chats directive matrix must be readable.');
    if (is_string($matrixRaw) && $matrixRaw !== '') {
        try {
            $matrix = json_decode($matrixRaw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $matrix = [];
        }
        $ids = [];
        foreach (($matrix['directives'] ?? []) as $directive) {
            if (is_array($directive) && is_string($directive['id'] ?? null)) {
                $ids[] = $directive['id'];
            }
        }
        foreach (['CHAT-UX-001', 'CHAT-UX-002', 'CHAT-UX-003', 'CHAT-UX-004', 'CHAT-DOC-001', 'CHAT-DL-001', 'CHAT-QA-001', 'CHAT-DOC-021', 'CHAT-REC-024', 'RCD-001'] as $requiredId) {
            $assert(in_array($requiredId, $ids, true), 'Missing All-Chats traceability ID: ' . $requiredId);
        }
        $assert(($matrix['external_acceptance']['hostinger_staging'] ?? null) === false, 'Hostinger acceptance must remain false.');
        $assert(($matrix['external_acceptance']['founder_acceptance'] ?? null) === false, 'Founder acceptance must remain false.');
    }

    $completionRaw = file_get_contents($root . '/config/source-completion-matrix.json');
    $assert(is_string($completionRaw) && str_contains($completionRaw, 'All-Chats Recovered Directive Register 2026 v2.1'), 'Source completion matrix must name All-Chats v2.1.');
    $assert(is_string($completionRaw) && str_contains($completionRaw, 'Digital Supermarket / One-Roof Homeopathy Ecosystem'), 'Source completion matrix must name the one-roof ecosystem principle.');

    if ($failures !== []) {
        foreach ($failures as $failure) {
            fwrite(STDERR, '[FAIL] ' . $failure . "\n");
        }
        exit(1);
    }

    echo "Reviews 174-176 three-plan corrective behavioral checks passed.\n";
}
