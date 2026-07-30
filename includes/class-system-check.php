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
        $tests['direct']['sabri_public_experience_dependencies'] = [
            'label' => __('Sabri Public Experience dependencies', 'sabri-public-experience'),
            'test' => [$this, 'dependency_test'],
        ];
        $tests['direct']['sabri_public_experience_providers'] = [
            'label' => __('Sabri Public Experience timeline providers', 'sabri-public-experience'),
            'test' => [$this, 'provider_test'],
        ];
        return $tests;
    }

    /** @return array<string,mixed> */
    public function dependency_test(): array
    {
        $blockers = $this->dependencies->get_blockers();
        $good = $blockers === [];
        $gaps = $this->dependencies->get_production_gaps();

        return [
            'label' => $good
                ? __('Required runtime dependencies are available', 'sabri-public-experience')
                : __('Required runtime dependencies are missing', 'sabri-public-experience'),
            'status' => $good ? 'good' : 'critical',
            'badge' => ['label' => 'File 25', 'color' => 'blue'],
            'description' => '<p>' . esc_html($good ? __('The public experience runtime may boot safely.', 'sabri-public-experience') : implode(', ', $blockers)) . '</p>'
                . ($good && $gaps !== [] ? '<p>' . esc_html(sprintf(__('Production integration gaps: %s', 'sabri-public-experience'), implode(', ', $gaps))) . '</p>' : ''),
            'actions' => '',
            'test' => 'sabri_public_experience_dependencies',
        ];
    }

    /** @return array<string,mixed> */
    public function provider_test(): array
    {
        $available = array_filter($this->registry->all(), static fn ($provider): bool => $provider->is_available());
        return [
            'label' => __('Timeline provider registry is operational', 'sabri-public-experience'),
            'status' => $available !== [] ? 'good' : 'recommended',
            'badge' => ['label' => 'File 25', 'color' => 'blue'],
            'description' => '<p>' . esc_html(sprintf(__('Available providers: %d', 'sabri-public-experience'), count($available))) . '</p>',
            'actions' => '',
            'test' => 'sabri_public_experience_providers',
        ];
    }
}
