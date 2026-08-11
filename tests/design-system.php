<?php

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/fixtures/');
    }
    if (! defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
        define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.13.0');
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
    require_once dirname(__DIR__) . '/includes/class-section-service.php';
    require_once dirname(__DIR__) . '/includes/class-profile-repository.php';
    require_once dirname(__DIR__) . '/includes/class-visual-acceptance.php';
    require_once dirname(__DIR__) . '/includes/class-staging-probe.php';
    require_once dirname(__DIR__) . '/includes/class-file-24-integration.php';
    require_once dirname(__DIR__) . '/includes/class-design-system.php';

    use Sabri\PublicExperience\Components;
    use Sabri\PublicExperience\Content_Cards;
    use Sabri\PublicExperience\Design_System;
    use Sabri\PublicExperience\File_24_Integration;
    use Sabri\PublicExperience\Profile_Repository;
    use Sabri\PublicExperience\Section_Service;
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
    $check(($contract['contract_version'] ?? '') === '1.9.0', 'Design-system contract version must include the newest governing-plan reconciliation.');
    $check(($contract['runtime_version'] ?? '') === '0.13.0', 'Design-system runtime version must match File 25 0.13.0.');
    $check(($contract['creates_file_26'] ?? true) === false, 'Design system must reject a duplicate File 26.');
    $check(($contract['global_shell_owner'] ?? '') === 'file-20', 'File 20 must remain the global shell owner.');
    $check(($contract['visual_system_owner'] ?? '') === 'file-25', 'File 25 must remain the visual-system owner.');
    $check(($contract['design_token_owner'] ?? '') === 'file-25', 'File 25 must remain the canonical visual-token owner.');
    $check(($contract['structural_layout_owner'] ?? '') === 'file-20', 'File 20 must remain the structural layout owner.');
    $check(($contract['security_governance_owner'] ?? '') === 'file-24', 'File 24 must remain the security-governance owner.');
    $check(($contract['public_contact_consent_owner'] ?? '') === 'file-03', 'File 03 must remain the public-contact consent owner.');
    foreach ([
        'visual-acceptance-evidence',
        'deterministic-staging-packaging',
        'installed-staging-preflight',
        'file-24-module-manifest',
        'no-store-profile-boundary',
        'native-contact-consent',
        'same-origin-profile-media',
        'conditional-profile-layout',
        'profile-breadcrumbs',
        'structured-profile-section-rest',
        'latest-central-requirement-ledger',
        'file20-shell-screenshot-regression',
        'tracking-free-profile-deep-link-qr-presentation',
    ] as $scope) {
        $check(in_array($scope, (array) ($contract['scope'] ?? []), true), 'Missing design-system scope: ' . $scope);
    }

    $file24 = (array) ($contract['file_24_integration'] ?? []);
    $check(($file24['contract_version'] ?? '') === File_24_Integration::CONTRACT_VERSION, 'File 24 integration contract must be exposed.');
    $check(($file24['file_24_constant'] ?? '') === 'SPCRC_VERSION', 'The exact File 24 runtime constant must be published.');
    $check(($file24['owns_security_governance'] ?? true) === false, 'File 25 must not claim security governance.');
    $check(($file24['owns_privacy_orchestration'] ?? true) === false, 'File 25 must not claim privacy orchestration.');
    $check(($file24['cache_mode'] ?? '') === 'no-store-until-versioned-partition-contract', 'Shared profile caching must remain closed.');

    $acceptance = (array) ($contract['visual_acceptance'] ?? []);
    $check(($acceptance['green_ci_is_acceptance'] ?? true) === false, 'Green CI must not become visual acceptance.');
    $check(($acceptance['target_commit_sha_required'] ?? false) === true, 'Visual evidence must remain exact-commit bound.');
    $check(($acceptance['artifact_root'] ?? '') === 'artifacts/', 'Visual evidence must use the governed artifact root.');
    $check(in_array('file20-shell-integration', (array) ($acceptance['required_surfaces'] ?? []), true), 'Visual acceptance must include File 20 shell integration.');
    $check(in_array('qr-share-state', (array) ($acceptance['required_surfaces'] ?? []), true), 'Visual acceptance must include the provider-owned QR/share state.');

    $cards = (array) ($contract['content_cards'] ?? []);
    $check(($cards['contract_version'] ?? '') === '1.2.0', 'Content-card contract must expose the shared public normalizer.');
    $check(($cards['public_normalizer'] ?? null) === [Content_Cards::class, 'normalize_public'], 'Public card normalizer must remain exposed.');
    $check(($cards['owns_native_data'] ?? true) === false, 'Global cards must preserve native-data ownership.');

    $sections = (array) ($contract['optional_sections'] ?? []);
    $check(($sections['provider_metadata_bound_to_concrete_object'] ?? false) === true, 'Provider metadata must remain object-bound.');
    $check(($sections['owns_native_content'] ?? true) === false, 'Optional sections must deny native-content ownership.');
    $check(($sections['public_projection_contract'] ?? '') === Section_Service::PUBLIC_CONTRACT_VERSION, 'Structured section projection contract must remain exposed.');
    foreach (['knowledge', 'media', 'reviews', 'research', 'marketplace'] as $section) {
        $check(in_array($section, (array) ($sections['allowed_sections'] ?? []), true), 'Missing optional section: ' . $section);
    }

    $rest = (array) ($contract['public_rest'] ?? []);
    $check(($rest['knowledge'] ?? '') === '/profiles/{public-slug}/knowledge', 'Knowledge REST route must remain canonical.');
    $check(($rest['media'] ?? '') === '/profiles/{public-slug}/media', 'Media REST route must remain canonical.');
    $check(($rest['provider_health'] ?? '') === '/providers/health', 'Provider-health REST route must remain canonical.');
    $check(($rest['provider_ids_public'] ?? true) === false, 'Provider identifiers must remain private.');
    $check(($rest['native_ids_public'] ?? true) === false, 'Native identifiers must remain private.');
    $check(($rest['etag'] ?? false) === true, 'Public REST contract must declare deterministic ETags.');

    $presentation = (array) ($contract['profile_presentation'] ?? []);
    $check(($presentation['founder_display_name'] ?? '') === Profile_Repository::FOUNDER_DISPLAY_NAME, 'Founder public spelling must remain immutable.');
    $check(($presentation['external_avatar_fallback'] ?? true) === false, 'External avatar fallback must remain disabled.');
    $check(($presentation['doctor_contact_requires_file_03_consent'] ?? false) === true, 'Doctor contact must require File 03 consent.');
    $check(($presentation['right_sidebar_requires_real_content'] ?? false) === true, 'Profile right sidebar must require real content.');
    $check(($presentation['explicit_tombstone_status'] ?? 0) === 410, 'Explicit deleted-profile tombstones must use 410.');
    $check(($presentation['profile_qr_image_filter'] ?? '') === 'sabri_public_experience/profile_qr_image_url', 'Profile QR presentation must use the native/provider filter contract.');
    $check(($presentation['profile_qr_same_site_only'] ?? false) === true, 'Profile QR image must remain same-site only.');
    $check(($presentation['profile_qr_tracking_free_required'] ?? false) === true, 'Profile QR presentation must remain tracking-free.');

    $latest = (array) ($contract['latest_governing_requirements'] ?? []);
    $check(($latest['global_search_discovery_ranking_owner'] ?? '') === 'file-26', 'File 26 must remain the global search/discovery/ranking owner.');
    foreach (['F25-CEN-01', 'F25-CEN-02'] as $requirementId) {
        $check(in_array($requirementId, (array) ($latest['file_specific'] ?? []), true), 'Missing latest File 25 requirement: ' . $requirementId);
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

    echo "PASS: File 25 global design system and newest governing-plan profile contracts\n";
}
