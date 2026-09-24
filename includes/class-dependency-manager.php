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
                'minimum' => Native_Integration::FILE_00_MINIMUM_VERSION,
                'maximum_exclusive' => Native_Integration::FILE_00_MAXIMUM_VERSION,
                'contract_version' => defined('SMC_CONTRACT_VERSION') ? (string) SMC_CONTRACT_VERSION : '',
                'required_contract_version' => Native_Integration::FILE_00_CONTRACT_VERSION,
                'contract' => 'File 00 versioned membership assertions, Founder identity, suspension, eligibility, and public-profile authorization',
            ],
            'profiles' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->profiles_available(),
                'version' => defined('SPD_VERSION') ? (string) SPD_VERSION : '',
                'contract' => 'File 03 profile master data, same-origin media, and explicit public-contact consent',
            ],
            'doctor_verification' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->doctor_verification_available(),
                'version' => defined('GDO_VERSION') ? (string) GDO_VERSION : '',
                'minimum' => Native_Integration::FILE_09_MINIMUM_VERSION,
                'maximum_exclusive' => Native_Integration::FILE_09_MAXIMUM_VERSION,
                'contract' => 'File 09 verification decision and immutable approved professional snapshot',
            ],
            'clinic_projection' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->clinic_available(),
                'version' => defined('SWC_VERSION') ? (string) SWC_VERSION : '',
                'minimum' => Native_Integration::FILE_08_MINIMUM_VERSION,
                'maximum_exclusive' => Native_Integration::FILE_08_MAXIMUM_VERSION,
                'required_contract_version' => Native_Integration::FILE_08_PUBLIC_PROJECTION_CONTRACT,
                'contract' => 'File 08 versioned public clinic projection; File 25 performs no clinic-table reads',
            ],
            'marketplace_projection' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->marketplace_available(),
                'version' => defined('SMP_VERSION') ? (string) SMP_VERSION : '',
                'minimum' => Native_Integration::FILE_18_MINIMUM_VERSION,
                'maximum_exclusive' => Native_Integration::FILE_18_MAXIMUM_VERSION,
                'contract' => 'File 18 owner-executed public listing DTO; File 25 performs no Marketplace-table reads',
            ],
            'application_shell' => [
                'required' => false,
                'production_required' => true,
                'available' => $this->native->shell_available(),
                'version' => defined('SABRI_SHELL_VERSION') ? (string) SABRI_SHELL_VERSION : '',
                'contract' => 'File 20 sole structural shell, route, navigation, sidebar, drawer, and layout API; File 25 retains visual-token ownership',
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
                'available' => File_24_Integration::is_compatible(),
                'version' => File_24_Integration::current_version(),
                'minimum' => File_24_Integration::REVIEWED_MINIMUM_VERSION,
                'maximum_exclusive' => File_24_Integration::REVIEWED_MAXIMUM_VERSION,
                'contract' => 'File 24 SPCRC module-manifest, advisory security-state, privacy, audit, incident, and resilience contract',
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
