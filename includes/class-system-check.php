<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

final class System_Check
{
    public function __construct(
        private Dependency_Manager $dependencies,
        private Timeline_Registry $timeline_registry,
        private Section_Registry $section_registry,
        private Staging_Probe $staging_probe,
        private File_24_Integration $file_24
    ) {
    }

    public function register(): void
    {
        add_filter('site_status_tests', [$this, 'site_health_tests']);
    }

    /** @param array<string,mixed> $tests @return array<string,mixed> */
    public function site_health_tests(array $tests): array
    {
        $tests['direct']['sabri_visual_design_system'] = [
            'label' => __('File 25 global design system', 'sabri-public-experience'),
            'test' => [$this, 'design_system_test'],
        ];
        $tests['direct']['sabri_public_experience_dependencies'] = [
            'label' => __('File 25 profile dependencies', 'sabri-public-experience'),
            'test' => [$this, 'dependency_test'],
        ];
        $tests['direct']['sabri_public_experience_file24'] = [
            'label' => __('File 25 and File 24 security integration', 'sabri-public-experience'),
            'test' => [$this, 'file_24_test'],
        ];
        $tests['direct']['sabri_public_experience_providers'] = [
            'label' => __('File 25 timeline providers', 'sabri-public-experience'),
            'test' => [$this, 'provider_test'],
        ];
        $tests['direct']['sabri_public_experience_section_providers'] = [
            'label' => __('File 25 optional profile-section providers', 'sabri-public-experience'),
            'test' => [$this, 'section_provider_test'],
        ];
        $tests['direct']['sabri_public_experience_staging_probe'] = [
            'label' => __('File 25 Hostinger staging preflight', 'sabri-public-experience'),
            'test' => [$this, 'staging_probe_test'],
        ];
        $tests['direct']['sabri_public_experience_safe_mode'] = [
            'label' => __('File 25 Safe Mode', 'sabri-public-experience'),
            'test' => [$this, 'safe_mode_test'],
        ];

        return $tests;
    }

    /** @return array<string,mixed> */
    public function design_system_test(): array
    {
        $contract = Design_System::contract();
        $cards = (array) ($contract['content_cards'] ?? []);
        $components = (array) ($contract['components'] ?? []);
        $acceptance = (array) ($contract['visual_acceptance'] ?? []);
        $sections = (array) ($contract['optional_sections'] ?? []);
        $file_24 = (array) ($contract['file_24_integration'] ?? []);
        $stylesheet = SABRI_PUBLIC_EXPERIENCE_DIR . 'assets/css/design-system.css';
        $component_stylesheet = SABRI_PUBLIC_EXPERIENCE_DIR . 'assets/css/design-system-components.css';
        $valid = ($contract['file'] ?? null) === 25
            && ($contract['contract_version'] ?? '') === Design_System::CONTRACT_VERSION
            && ($contract['visual_system_owner'] ?? '') === 'file-25'
            && ($contract['global_shell_owner'] ?? '') === 'file-20'
            && ($components['contract_version'] ?? '') === Components::CONTRACT_VERSION
            && ($cards['contract_version'] ?? '') === Content_Cards::CONTRACT_VERSION
            && ($cards['owns_native_data'] ?? true) === false
            && ($acceptance['contract_version'] ?? '') === Visual_Acceptance::CONTRACT_VERSION
            && ($acceptance['target_commit_sha_required'] ?? false) === true
            && ($acceptance['green_ci_is_acceptance'] ?? true) === false
            && ($sections['provider_metadata_bound_to_concrete_object'] ?? false) === true
            && (($sections['internal_projection_key']['rendered_publicly'] ?? true) === false)
            && ($file_24['contract_version'] ?? '') === File_24_Integration::CONTRACT_VERSION
            && ($file_24['owns_security_governance'] ?? true) === false
            && is_callable('sabri_visual_experience_contract')
            && is_callable('sabri_visual_experience_acceptance_contract')
            && is_callable('sabri_visual_experience_render_state')
            && is_callable('sabri_visual_experience_render_notice')
            && is_callable('sabri_visual_experience_render_card')
            && is_readable($stylesheet)
            && is_readable($component_stylesheet);

        if (! $valid) {
            return $this->result(
                'design_system',
                __('The File 25 global design-system contract is incomplete', 'sabri-public-experience'),
                'critical',
                __('The canonical contract, reusable renderer API, File 24 boundary, provider identity boundary, evidence contract, or local stylesheet is missing or inconsistent.', 'sabri-public-experience')
            );
        }

        return $this->result(
            'design_system',
            __('The File 25 global design-system contract is available', 'sabri-public-experience'),
            'good',
            __('File 25 owns the visual system, File 20 remains the shell owner, and File 24 remains the security-governance owner. Exact-commit staging evidence is still required.', 'sabri-public-experience')
        );
    }

    /** @return array<string,mixed> */
    public function dependency_test(): array
    {
        $blockers = $this->dependencies->get_blockers();
        $gaps = $this->dependencies->get_production_gaps();
        if ($blockers !== []) {
            $status = 'critical';
            $label = __('Required File 25 profile dependencies are missing', 'sabri-public-experience');
            $description = sprintf(
                __('Profile and timeline features are fail-closed. Missing dependencies: %s', 'sabri-public-experience'),
                implode(', ', $blockers)
            );
        } elseif ($gaps !== []) {
            $status = 'recommended';
            $label = __('File 25 may boot, but production integrations are incomplete', 'sabri-public-experience');
            $description = sprintf(
                __('Production integration gaps: %s', 'sabri-public-experience'),
                implode(', ', $gaps)
            );
        } else {
            $status = 'good';
            $label = __('Required runtime and production integrations are available', 'sabri-public-experience');
            $description = __('No dependency gap is currently detected. Staging acceptance is still required.', 'sabri-public-experience');
        }

        return $this->result('dependencies', $label, $status, $description);
    }

    /** @return array<string,mixed> */
    public function file_24_test(): array
    {
        $version = File_24_Integration::current_version();
        $manifest = File_24_Integration::manifest();
        $contract = File_24_Integration::contract();

        if ($version === '') {
            return $this->result(
                'file24',
                __('The reviewed File 24 runtime is not active', 'sabri-public-experience'),
                'recommended',
                __('File 25 remains operational with no-store profile responses, but production security, privacy, audit, incident, and resilience integration is incomplete.', 'sabri-public-experience')
            );
        }
        if (! File_24_Integration::is_compatible()) {
            return $this->result(
                'file24',
                __('The active File 24 version is outside the reviewed range', 'sabri-public-experience'),
                'critical',
                sprintf(
                    __('Active version: %1$s; reviewed range: %2$s', 'sabri-public-experience'),
                    $version,
                    (string) ($contract['reviewed_version_range'] ?? '')
                )
            );
        }
        if (($manifest['module_key'] ?? '') !== File_24_Integration::MODULE_KEY
            || ($manifest['owner'] ?? '') !== 'File 25'
            || ($manifest['privacy_operations'] ?? null) !== []
            || ($contract['owns_security_governance'] ?? true) !== false
        ) {
            return $this->result(
                'file24',
                __('The File 24 module boundary is inconsistent', 'sabri-public-experience'),
                'critical',
                __('File 25 must publish only its bounded manifest and must not claim security, privacy, incident, or native-content ownership.', 'sabri-public-experience')
            );
        }

        return $this->result(
            'file24',
            __('The reviewed File 24 integration contract is active', 'sabri-public-experience'),
            'good',
            sprintf(
                __('File 24 version %s is compatible. File 25 publishes a bounded module manifest and keeps profile responses no-store pending a later accepted cache-partition contract.', 'sabri-public-experience'),
                $version
            )
        );
    }

    /** @return array<string,mixed> */
    public function provider_test(): array
    {
        $available = [];
        $production = [];
        $errors = [];
        foreach ($this->timeline_registry->all() as $id => $provider) {
            try {
                $metadata = $this->timeline_registry->validated_metadata($provider, (string) $id);
                if ($metadata === null) {
                    $errors[] = (string) $id;
                    continue;
                }
                if ($metadata['maturity'] === 'disabled' || ! $provider->is_available()) {
                    continue;
                }
                $available[] = (string) $id;
                if ($metadata['maturity'] === 'production-accepted') {
                    $production[] = (string) $id;
                }
            } catch (\Throwable) {
                $errors[] = (string) $id;
            }
        }

        if ($errors !== []) {
            return $this->result(
                'providers',
                __('One or more timeline providers failed immutable health inspection', 'sabri-public-experience'),
                'critical',
                sprintf(__('Failed provider count: %d', 'sabri-public-experience'), count(array_unique($errors)))
            );
        }
        if (! in_array('file-21', $production, true)) {
            return $this->result(
                'providers',
                __('The production File 21 timeline provider is not accepted', 'sabri-public-experience'),
                'recommended',
                sprintf(
                    __('Available read providers: %d. Read-only source integration is not production evidence.', 'sabri-public-experience'),
                    count($available)
                )
            );
        }

        return $this->result(
            'providers',
            __('The production File 21 timeline provider is registered', 'sabri-public-experience'),
            'good',
            __('Immutable provider registration is healthy; real-content staging tests remain mandatory.', 'sabri-public-experience')
        );
    }

    /** @return array<string,mixed> */
    public function section_provider_test(): array
    {
        $registered = count($this->section_registry->all());
        $active = 0;
        $errors = 0;
        $seen = [];

        foreach (Section_Registry::approved_sections() as $section) {
            foreach ($this->section_registry->for_section($section) as $id => $provider) {
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                try {
                    $metadata = $this->section_registry->validated_metadata($provider, $section);
                    if ($metadata === null) {
                        $errors++;
                        continue;
                    }
                    if ($metadata['maturity'] !== 'disabled' && $provider->is_available()) {
                        $active++;
                    }
                } catch (\Throwable) {
                    $errors++;
                }
            }
        }

        if ($errors > 0) {
            return $this->result(
                'section_providers',
                __('One or more optional section providers failed immutable health inspection', 'sabri-public-experience'),
                'critical',
                sprintf(__('Failed provider count: %d', 'sabri-public-experience'), $errors)
            );
        }
        if ($registered === 0) {
            return $this->result(
                'section_providers',
                __('No optional profile-section provider is registered yet', 'sabri-public-experience'),
                'recommended',
                __('Knowledge, Media, Reviews, Research, and Marketplace tabs remain hidden until accepted native providers supply approved public cards.', 'sabri-public-experience')
            );
        }

        return $this->result(
            'section_providers',
            __('Optional profile-section provider registry is healthy', 'sabri-public-experience'),
            'good',
            sprintf(__('Registered providers: %1$d; currently available: %2$d. Staging content acceptance remains required.', 'sabri-public-experience'), $registered, $active)
        );
    }

    /** @return array<string,mixed> */
    public function staging_probe_test(): array
    {
        $report = $this->staging_probe->snapshot('');
        $gates = (array) ($report['gates'] ?? []);
        $hard_gates = [
            'canonical_staging_host',
            'live_host_excluded',
            'https',
            'environment_type_safe',
            'registration_disabled',
            'search_indexing_disabled',
            'package_integrity',
            'test_plan_available',
            'runtime_supported',
            'required_dependencies_available',
            'safe_mode_inactive',
        ];
        $failed = [];
        foreach ($hard_gates as $gate) {
            if (empty($gates[$gate])) {
                $failed[] = $gate;
            }
        }

        if ($failed !== []) {
            return $this->result(
                'staging_probe',
                __('File 25 is not ready for manual Hostinger staging tests', 'sabri-public-experience'),
                'critical',
                sprintf(
                    __('Fail-closed preflight gates: %s', 'sabri-public-experience'),
                    implode(', ', $failed)
                )
            );
        }
        if (empty($gates['expected_commit_supplied'])) {
            return $this->result(
                'staging_probe',
                __('File 25 staging environment is structurally ready for exact-commit verification', 'sabri-public-experience'),
                'recommended',
                __('Run the read-only WP-CLI staging probe with --expected-commit or define SABRI_PUBLIC_EXPERIENCE_EXPECTED_COMMIT. Manual staging acceptance remains pending.', 'sabri-public-experience')
            );
        }

        return $this->result(
            'staging_probe',
            __('File 25 passed the exact-commit staging preflight', 'sabri-public-experience'),
            'good',
            __('The installed candidate is ready for manual workflows, visual evidence, rollback testing, and Founder acceptance. This is not staging acceptance.', 'sabri-public-experience')
        );
    }

    /** @return array<string,mixed> */
    public function safe_mode_test(): array
    {
        if (Safe_Mode::is_active()) {
            return $this->result(
                'safe_mode',
                __('File 25 Safe Mode is active', 'sabri-public-experience'),
                'critical',
                __('File 25 public visual overrides are disabled. Review the incident and use the authenticated Retry File 25 control.', 'sabri-public-experience')
            );
        }

        return $this->result(
            'safe_mode',
            __('File 25 Safe Mode is inactive', 'sabri-public-experience'),
            'good',
            __('No File 25 recovery block is currently active.', 'sabri-public-experience')
        );
    }

    /** @return array<string,mixed> */
    private function result(string $test, string $label, string $status, string $description): array
    {
        return [
            'label' => $label,
            'status' => $status,
            'badge' => ['label' => 'File 25', 'color' => 'blue'],
            'description' => '<p>' . esc_html($description) . '</p>',
            'actions' => '',
            'test' => 'sabri_public_experience_' . sanitize_key($test),
        ];
    }
}
