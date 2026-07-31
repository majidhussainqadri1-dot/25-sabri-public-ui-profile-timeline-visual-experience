<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
        define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.11.0');
    }
    if (! function_exists('home_url')) {
        function home_url(string $path = ''): string
        {
            return 'https://example.test' . $path;
        }
    }
    foreach (['esc_url_raw', 'esc_url', 'esc_html', 'esc_attr'] as $function) {
        if (! function_exists($function)) {
            eval('function ' . $function . '(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); }');
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-components.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';
    require_once dirname(__DIR__) . '/includes/contracts/interface-profile-section-provider.php';
    require_once dirname(__DIR__) . '/includes/class-section-registry.php';
    require_once dirname(__DIR__) . '/includes/class-visual-acceptance.php';
    require_once dirname(__DIR__) . '/includes/class-staging-probe.php';
    require_once dirname(__DIR__) . '/includes/class-design-system.php';

    use Sabri\PublicExperience\Components;
    use Sabri\PublicExperience\Content_Cards;
    use Sabri\PublicExperience\Design_System;
    use Sabri\PublicExperience\Staging_Probe;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $contract = Design_System::contract();
    $check(($contract['file'] ?? null) === 25, 'Design system contract must remain owned by File 25.');
    $check(($contract['canonical_name'] ?? '') === 'Sabri Unified Global Visual Experience and Design System', 'Canonical File 25 name must remain exact.');
    $check(($contract['contract_version'] ?? '') === '1.6.0', 'Design-system contract version must include installed staging preflight.');
    $check(($contract['runtime_version'] ?? '') === '0.11.0', 'Design-system runtime version must match File 25 0.11.0.');
    $check(($contract['creates_file_26'] ?? true) === false, 'Design system must reject a duplicate File 26.');
    $check(($contract['global_shell_owner'] ?? '') === 'file-20', 'File 20 must remain the global shell owner.');
    $check(($contract['visual_system_owner'] ?? '') === 'file-25', 'File 25 must remain the visual-system owner.');
    foreach (['visual-acceptance-evidence', 'deterministic-staging-packaging', 'installed-staging-preflight'] as $scope) {
        $check(in_array($scope, (array) ($contract['scope'] ?? []), true), 'Missing design-system scope: ' . $scope);
    }

    $acceptance = (array) ($contract['visual_acceptance'] ?? []);
    $check(($acceptance['green_ci_is_acceptance'] ?? true) === false, 'Green CI must not become visual acceptance.');
    $check(($acceptance['target_commit_sha_required'] ?? false) === true, 'Visual evidence must remain exact-commit bound.');
    $check(($acceptance['artifact_root'] ?? '') === 'artifacts/', 'Visual evidence must use the governed artifact root.');

    $cards = (array) ($contract['content_cards'] ?? []);
    $check(($cards['owns_native_data'] ?? true) === false, 'Global cards must preserve native-data ownership.');

    $sections = (array) ($contract['optional_sections'] ?? []);
    $check(($sections['provider_metadata_bound_to_concrete_object'] ?? false) === true, 'Provider metadata must remain object-bound.');
    $check(($sections['owns_native_content'] ?? true) === false, 'Optional sections must deny native-content ownership.');
    foreach (['knowledge', 'media', 'reviews', 'research', 'marketplace'] as $section) {
        $check(in_array($section, (array) ($sections['allowed_sections'] ?? []), true), 'Missing optional section: ' . $section);
    }

    $package = (array) ($contract['staging_package'] ?? []);
    $check(($package['contract_version'] ?? '') === '1.0.0', 'Staging package contract must remain declared.');
    $check(($package['builder'] ?? '') === 'tools/build-staging-package.php', 'Canonical staging builder must remain declared.');
    $check(($package['staging_acceptance_implied'] ?? true) === false, 'A package must never imply staging acceptance.');

    $probe = (array) ($contract['staging_probe'] ?? []);
    $check(($probe['contract_version'] ?? '') === Staging_Probe::CONTRACT_VERSION, 'Staging probe contract must be exposed by the design system.');
    $check(($probe['canonical_staging_host'] ?? '') === 'sabrisocialstaging.sabrihomeopathy.com', 'Canonical Hostinger staging host must be exact.');
    $check(($probe['live_host'] ?? '') === 'sabrihomeopathy.com', 'Live host exclusion must remain explicit.');
    $check(($probe['writes_runtime_data'] ?? true) === false, 'Staging probe must remain read-only.');
    $check(($probe['staging_acceptance_implied'] ?? true) === false, 'Staging probe must not imply acceptance.');

    $components = Components::contract();
    foreach (['content_card', 'notice', 'field', 'label', 'input', 'select', 'textarea', 'help', 'field_error', 'table_wrap', 'table'] as $class) {
        $check(isset($components['classes'][$class]), 'Missing reusable component class: ' . $class);
    }
    $check(($components['renderers']['content_card'] ?? null) === [Content_Cards::class, 'render'], 'Canonical content-card renderer must remain exposed.');
    $check(($components['renderers']['notice'] ?? null) === [Components::class, 'render_notice'], 'Canonical notice renderer must remain exposed.');

    $state = Components::render_state([
        'type' => 'error',
        'title' => '<script>bad()</script>Unavailable',
        'message' => '<b>Safe public message</b>',
        'action_url' => 'javascript:alert(1)',
        'action_label' => 'Retry',
    ]);
    $check(! str_contains($state, '<script'), 'State renderer must strip executable markup.');
    $check(! str_contains($state, 'javascript:'), 'State renderer must reject unsafe URLs.');
    $check(str_contains($state, 'role="alert"'), 'Error state must use alert semantics.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 global design system, staging package, and installed-preflight contracts\n";
}
