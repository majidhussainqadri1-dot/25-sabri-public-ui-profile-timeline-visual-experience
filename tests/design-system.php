<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
        define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.10.0');
    }
    if (! function_exists('home_url')) {
        function home_url(string $path = ''): string
        {
            return 'https://example.test' . $path;
        }
    }
    if (! function_exists('esc_url_raw')) {
        function esc_url_raw(string $url, ?array $protocols = null): string
        {
            return $url;
        }
    }
    if (! function_exists('esc_url')) {
        function esc_url(string $url): string
        {
            return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    if (! function_exists('esc_html')) {
        function esc_html(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    if (! function_exists('esc_attr')) {
        function esc_attr(string $value): string
        {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    require_once dirname(__DIR__) . '/includes/class-design-system.php';

    use Sabri\PublicExperience\Components;
    use Sabri\PublicExperience\Content_Cards;
    use Sabri\PublicExperience\Design_System;

    $failures = [];
    $check = static function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $contract = Design_System::contract();
    $check(($contract['file'] ?? null) === 25, 'Design system contract must remain owned by File 25.');
    $check(($contract['canonical_name'] ?? '') === 'Sabri Unified Global Visual Experience and Design System', 'Canonical File 25 name must match the Founder-approved decision.');
    $check(($contract['contract_version'] ?? '') === '1.5.0', 'Design-system contract version must record deterministic staging packaging.');
    $check(($contract['runtime_version'] ?? '') === '0.10.0', 'Design-system runtime version must match File 25 0.10.0.');
    $check(($contract['creates_file_26'] ?? true) === false, 'Design system contract must explicitly reject a duplicate File 26.');
    $check(($contract['global_shell_owner'] ?? '') === 'file-20', 'File 20 must remain the global shell owner.');
    $check(($contract['visual_system_owner'] ?? '') === 'file-25', 'File 25 must remain the visual-system owner.');
    $check(in_array('visual-acceptance-evidence', (array) ($contract['scope'] ?? []), true), 'Visual acceptance evidence scope must be present.');
    $check(in_array('deterministic-staging-packaging', (array) ($contract['scope'] ?? []), true), 'Deterministic staging packaging scope must be present.');
    $check(($contract['visual_acceptance']['green_ci_is_acceptance'] ?? true) === false, 'Green CI must not become visual acceptance.');
    $check(($contract['visual_acceptance']['target_commit_sha_required'] ?? false) === true, 'Visual evidence must be bound to one target commit.');
    $check(($contract['visual_acceptance']['artifact_root'] ?? '') === 'artifacts/', 'Visual evidence must use the governed artifacts root.');
    $check(($contract['content_cards']['owns_native_data'] ?? true) === false, 'Global contract must preserve native card-data ownership.');
    $check(($contract['optional_sections']['contract_version'] ?? '') === '1.1.0', 'Optional-section contract must record atomic provider identity.');
    $check(($contract['optional_sections']['owns_native_content'] ?? true) === false, 'Optional sections must deny native content ownership.');
    $check(($contract['optional_sections']['provider_metadata_bound_to_concrete_object'] ?? false) === true, 'Provider metadata must be bound to the registered concrete object.');
    $projection = (array) ($contract['optional_sections']['internal_projection_key'] ?? []);
    $check(($projection['field'] ?? '') === 'projection_key', 'Internal projection-key field must be explicit.');
    $check(($projection['format'] ?? '') === 'sha256', 'Internal projection keys must use SHA-256 format.');
    $check(($projection['rendered_publicly'] ?? true) === false, 'Internal projection keys must never render publicly.');
    foreach (['knowledge', 'media', 'reviews', 'research', 'marketplace'] as $section) {
        $check(in_array($section, (array) ($contract['optional_sections']['allowed_sections'] ?? []), true), 'Missing optional section: ' . $section);
    }

    $package = (array) ($contract['staging_package'] ?? []);
    $check(($package['contract_version'] ?? '') === '1.0.0', 'Staging package contract version must be present.');
    $check(($package['builder'] ?? '') === 'tools/build-staging-package.php', 'Canonical staging package builder must be declared.');
    $check(($package['dependency_matrix'] ?? '') === 'config/staging-dependencies.json', 'Canonical dependency matrix must be declared.');
    $check(($package['deterministic_source_date_epoch'] ?? false) === true, 'Staging packages must use deterministic source timestamps.');
    $check(($package['development_files_excluded'] ?? false) === true, 'Development files must be excluded from staging packages.');
    $check(($package['staging_acceptance_implied'] ?? true) === false, 'A staging ZIP must not imply staging acceptance.');
    $check(($package['production_acceptance_implied'] ?? true) === false, 'A staging ZIP must not imply production acceptance.');

    $components = Components::contract();
    foreach (['content_card', 'notice', 'field', 'label', 'input', 'select', 'textarea', 'help', 'field_error', 'table_wrap', 'table'] as $class) {
        $check(isset($components['classes'][$class]), 'Missing reusable component class: ' . $class);
    }
    $check(($components['renderers']['content_card'] ?? null) === [Content_Cards::class, 'render'], 'Component contract must expose the canonical content-card renderer.');
    $check(($components['renderers']['notice'] ?? null) === [Components::class, 'render_notice'], 'Component contract must expose the canonical notice renderer.');

    $state = Components::render_state([
        'type' => 'error',
        'title' => '<script>bad()</script>Unavailable',
        'message' => '<b>Safe public message</b>',
        'action_url' => 'javascript:alert(1)',
        'action_label' => 'Retry',
    ]);
    $check(! str_contains($state, '<script'), 'State renderer must remove executable markup.');
    $check(! str_contains($state, 'javascript:'), 'State renderer must reject unsafe action URLs.');
    $check(str_contains($state, 'role="alert"'), 'Error state must use an alert role.');

    $notice = Components::render_notice([
        'type' => 'warning',
        'title' => '<b>Provider warning</b>',
        'message' => '<script>bad()</script>Available public data remains visible.',
        'action_url' => '//evil.example/phish',
        'action_label' => 'Review',
    ]);
    $check(str_contains($notice, 'sabri-ui-notice--warning'), 'Notice renderer must expose the canonical warning class.');
    $check(! str_contains($notice, 'evil.example'), 'Notice renderer must reject external actions.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 global design system and staging package contract\n";
}
