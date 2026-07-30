<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
        define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.5.0');
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
}

namespace {
    require_once dirname(__DIR__) . '/includes/class-public-url.php';
    require_once dirname(__DIR__) . '/includes/class-components.php';
    require_once dirname(__DIR__) . '/includes/class-content-cards.php';
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
    $check(
        ($contract['canonical_name'] ?? '') === 'Sabri Unified Global Visual Experience and Design System',
        'Canonical File 25 name must match the Founder-approved decision.'
    );
    $check(($contract['contract_version'] ?? '') === '1.1.0', 'Design-system contract version must record the reusable card expansion.');
    $check(($contract['creates_file_26'] ?? true) === false, 'Design system contract must explicitly reject a duplicate File 26.');
    $check(($contract['global_shell_owner'] ?? '') === 'file-20', 'File 20 must remain the global shell owner.');
    $check(($contract['visual_system_owner'] ?? '') === 'file-25', 'File 25 must remain the visual-system owner.');
    $check(in_array('global-design-system', (array) ($contract['scope'] ?? []), true), 'Global design-system scope must be present.');
    $check(in_array('reusable-content-cards', (array) ($contract['scope'] ?? []), true), 'Reusable content-card scope must be present.');
    $check(isset($contract['tokens']['color-primary']), 'Primary semantic token must be registered.');
    $check(isset($contract['tokens']['color-on-danger']), 'Contrast-safe danger foreground token must be registered.');
    $check(isset($contract['tokens']['space-layout']), 'Layout spacing token must be registered.');
    $check(($contract['content_cards']['owns_native_data'] ?? true) === false, 'Global contract must preserve native card-data ownership.');

    $components = Components::contract();
    $check(($components['contract_version'] ?? '') === '1.1.0', 'Component contract version must record the expanded catalog.');
    $check(($components['prefix'] ?? '') === 'sabri-ui-', 'Reusable components must use the canonical low-collision prefix.');
    foreach (['loading', 'empty', 'error', 'success', 'warning', 'unavailable'] as $state) {
        $check(in_array($state, (array) ($components['states'] ?? []), true), 'Missing visual state: ' . $state);
    }
    foreach (['content_card', 'notice', 'field', 'input', 'table_wrap', 'table'] as $class) {
        $check(isset($components['classes'][$class]), 'Missing reusable component class: ' . $class);
    }
    $check(
        ($components['renderers']['content_card'] ?? null) === [Content_Cards::class, 'render'],
        'Component contract must expose the canonical content-card renderer.'
    );

    $state = Components::render_state([
        'type' => 'error',
        'title' => '<script>bad()</script>Unavailable',
        'message' => '<b>Safe public message</b>',
        'action_url' => 'javascript:alert(1)',
        'action_label' => 'Retry',
    ]);
    $check(! str_contains($state, '<script'), 'State renderer must remove executable markup.');
    $check(! str_contains($state, 'javascript:'), 'State renderer must reject unsafe action URLs.');
    $check(str_contains($state, 'sabri-ui-state--error'), 'State renderer must expose the canonical error class.');
    $check(str_contains($state, 'role="alert"'), 'Error state must use an alert role.');
    $check(str_contains($state, 'aria-atomic="true"'), 'State announcement must be atomic.');

    if ($failures !== []) {
        fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failures) . "\n");
        exit(1);
    }

    echo "PASS: File 25 global design system contract\n";
}
