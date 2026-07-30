<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

final class Dependency_Manager
{
    public const MINIMUM_PHP = '8.0';
    public const MINIMUM_WORDPRESS = '6.5';

    public function __construct(private Native_Integration $native)
    {
    }

    /** @return array<string, array<string, mixed>> */
    public function get_statuses(): array
    {
        global $wp_version;

        $statuses = [
            'php' => [
                'required' => true,
                'available' => version_compare(PHP_VERSION, self::MINIMUM_PHP, '>='),
                'version' => PHP_VERSION,
                'minimum' => self::MINIMUM_PHP,
            ],
            'wordpress' => [
                'required' => true,
                'available' => version_compare((string) $wp_version, self::MINIMUM_WORDPRESS, '>='),
                'version' => (string) $wp_version,
                'minimum' => self::MINIMUM_WORDPRESS,
            ],
            'membership_core' => [
                'required' => true,
                'available' => $this->native->membership_available(),
                'version' => defined('SMC_VERSION') ? (string) SMC_VERSION : '',
                'contract' => 'File 00 authoritative identity and visibility API',
            ],
            'profiles' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->profiles_available(),
                'version' => defined('SPD_VERSION') ? (string) SPD_VERSION : '',
                'contract' => 'File 03 profile master data API',
            ],
            'application_shell' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->shell_available(),
                'version' => defined('SABRI_SHELL_VERSION') ? (string) SABRI_SHELL_VERSION : '',
                'contract' => 'File 20 shell and design-token API',
            ],
            'home_news' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->home_news_available(),
                'version' => defined('SABRI_HNF_VERSION') ? (string) SABRI_HNF_VERSION : '',
                'contract' => 'File 21 publications and timeline bridge API',
            ],
            'security_center' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->security_center_available(),
                'contract' => 'File 24 security, privacy, compliance, and resilience API',
            ],
        ];

        /** @var array<string, array<string, mixed>> $statuses */
        return apply_filters('sabri_public_experience/dependency_statuses', $statuses);
    }

    public function runtime_is_supported(): bool
    {
        foreach ($this->get_statuses() as $status) {
            if (! empty($status['required']) && empty($status['available'])) {
                return false;
            }
        }
        return true;
    }

    /** @return list<string> */
    public function get_blockers(): array
    {
        $blockers = [];
        foreach ($this->get_statuses() as $id => $status) {
            if (! empty($status['required']) && empty($status['available'])) {
                $blockers[] = (string) $id;
            }
        }
        return $blockers;
    }

    /** @return list<string> */
    public function get_production_gaps(): array
    {
        $gaps = [];
        foreach ($this->get_statuses() as $id => $status) {
            if (! empty($status['production_required']) && empty($status['available'])) {
                $gaps[] = (string) $id;
            }
        }
        return $gaps;
    }
}
