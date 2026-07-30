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

    /** @return array<string,array<string,mixed>> */
    public function get_statuses(): array
    {
        global $wp_version;

        $base = [
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
                'version' => defined('SABRI_SECURITY_CENTER_VERSION')
                    ? (string) SABRI_SECURITY_CENTER_VERSION
                    : (defined('SABRI_SPRC_VERSION') ? (string) SABRI_SPRC_VERSION : ''),
                'contract' => 'File 24 security, privacy, compliance, and resilience API',
            ],
        ];

        /** @var array<string,array<string,mixed>> $filtered */
        $filtered = (array) apply_filters('sabri_public_experience/dependency_statuses', $base);
        $statuses = [];
        foreach ($base as $id => $authoritative) {
            $candidate = isset($filtered[$id]) && is_array($filtered[$id]) ? $filtered[$id] : [];
            $statuses[$id] = array_merge($authoritative, $candidate);
            $statuses[$id]['required'] = ! empty($authoritative['required']);
            $statuses[$id]['production_required'] = ! empty($authoritative['production_required']);
            // Integration filters may revoke availability, never fabricate a native contract.
            $statuses[$id]['available'] = ! empty($authoritative['available']) && ! empty($candidate['available'] ?? true);
            $statuses[$id]['version'] = substr(sanitize_text_field((string) ($statuses[$id]['version'] ?? '')), 0, 64);
        }

        // Additional dependencies may be registered, but are normalized and can
        // only make File 25 more restrictive.
        foreach ($filtered as $id => $candidate) {
            $id = sanitize_key((string) $id);
            if ($id === '' || isset($statuses[$id]) || ! is_array($candidate)) {
                continue;
            }
            $statuses[$id] = [
                'required' => ! empty($candidate['required']),
                'production_required' => ! empty($candidate['production_required']),
                'available' => ! empty($candidate['available']),
                'version' => substr(sanitize_text_field((string) ($candidate['version'] ?? '')), 0, 64),
                'contract' => substr(sanitize_text_field((string) ($candidate['contract'] ?? '')), 0, 300),
            ];
        }

        return $statuses;
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
