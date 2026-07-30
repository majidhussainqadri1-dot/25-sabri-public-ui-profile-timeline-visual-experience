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
        private Timeline_Registry $registry
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
        $tests['direct']['sabri_public_experience_providers'] = [
            'label' => __('File 25 timeline providers', 'sabri-public-experience'),
            'test' => [$this, 'provider_test'],
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
        $stylesheet = SABRI_PUBLIC_EXPERIENCE_DIR . 'assets/css/design-system.css';
        $valid = ($contract['file'] ?? null) === 25
            && ($contract['contract_version'] ?? '') === Design_System::CONTRACT_VERSION
            && ($contract['visual_system_owner'] ?? '') === 'file-25'
            && ($contract['global_shell_owner'] ?? '') === 'file-20'
            && ($components['contract_version'] ?? '') === Components::CONTRACT_VERSION
            && ($cards['contract_version'] ?? '') === Content_Cards::CONTRACT_VERSION
            && ($cards['owns_native_data'] ?? true) === false
            && is_callable('sabri_visual_experience_contract')
            && is_callable('sabri_visual_experience_render_state')
            && is_callable('sabri_visual_experience_render_card')
            && is_readable($stylesheet);

        if (! $valid) {
            return $this->result(
                'design_system',
                __('The File 25 global design-system contract is incomplete', 'sabri-public-experience'),
                'critical',
                __('The canonical contract, reusable renderer API, ownership boundary, or local stylesheet is missing or inconsistent.', 'sabri-public-experience')
            );
        }

        return $this->result(
            'design_system',
            __('The File 25 global design-system contract is available', 'sabri-public-experience'),
            'good',
            __('File 25 owns the visual system and reusable cards while File 20 remains the sole application-shell owner. Visual staging acceptance is still required.', 'sabri-public-experience')
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
    public function provider_test(): array
    {
        $available = [];
        $production = [];
        $errors = [];
        foreach ($this->registry->all() as $id => $provider) {
            try {
                if (! $provider->is_available() || $provider->get_maturity_level() === 'disabled') {
                    continue;
                }
                $available[] = (string) $id;
                if ($provider->get_maturity_level() === 'production-accepted') {
                    $production[] = (string) $id;
                }
            } catch (\Throwable) {
                $errors[] = (string) $id;
            }
        }

        if ($errors !== []) {
            return $this->result(
                'providers',
                __('One or more timeline providers failed health inspection', 'sabri-public-experience'),
                'critical',
                sprintf(__('Failed provider count: %d', 'sabri-public-experience'), count($errors))
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
            __('Provider registration is healthy; real-content staging tests remain mandatory.', 'sabri-public-experience')
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
