<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Machine-readable Definition-of-Done matrix and evidence validator. */
final class Visual_Acceptance
{
    public const CONTRACT_VERSION = '1.1.0';

    private const VIEWPORTS = [
        'mobile-small' => ['width' => 320, 'height' => 568],
        'mobile-modern' => ['width' => 390, 'height' => 844],
        'tablet-portrait' => ['width' => 768, 'height' => 1024],
        'tablet-landscape' => ['width' => 1024, 'height' => 768],
        'desktop' => ['width' => 1440, 'height' => 900],
        'desktop-wide' => ['width' => 1920, 'height' => 1080],
    ];

    private const DIRECTIONS = ['ltr', 'rtl'];
    private const COLOR_MODES = ['light', 'dark', 'forced-colors'];
    private const MOTION_MODES = ['normal', 'reduced'];
    private const ZOOM_LEVELS = [100, 200, 400];
    private const INPUT_MODES = ['keyboard', 'pointer', 'touch', 'screen-reader'];
    private const REQUIRED_SURFACES = [
        'founder-overview',
        'doctor-overview',
        'member-overview',
        'profile-timeline',
        'knowledge-section',
        'media-section',
        'loading-state',
        'empty-state',
        'error-state',
        'success-state',
        'warning-state',
        'unavailable-state',
        'content-card-grid',
        'form-controls',
        'responsive-table',
    ];

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'owner' => 'file-25',
            'shell_owner' => 'file-20',
            'viewports' => self::VIEWPORTS,
            'directions' => self::DIRECTIONS,
            'color_modes' => self::COLOR_MODES,
            'motion_modes' => self::MOTION_MODES,
            'zoom_levels' => self::ZOOM_LEVELS,
            'input_modes' => self::INPUT_MODES,
            'required_surfaces' => self::REQUIRED_SURFACES,
            'evidence_record_required_fields' => ['status', 'artifact_ref', 'sha256', 'recorded_at', 'reviewer'],
            'evidence_required' => true,
            'source_contract_is_acceptance' => false,
            'green_ci_is_acceptance' => false,
            'staging_required' => true,
            'founder_signoff_required' => true,
        ];
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];

        return array_merge($base, self::contract());
    }

    /** @param array<string,mixed> $evidence @return list<string> */
    public static function validate_evidence(array $evidence): array
    {
        $errors = [];
        self::validate_group($errors, $evidence, 'surfaces', self::REQUIRED_SURFACES);
        self::validate_group($errors, $evidence, 'viewports', array_keys(self::VIEWPORTS));
        self::validate_group($errors, $evidence, 'directions', self::DIRECTIONS);
        self::validate_group($errors, $evidence, 'color_modes', self::COLOR_MODES);
        self::validate_group($errors, $evidence, 'motion_modes', self::MOTION_MODES);
        self::validate_group($errors, $evidence, 'zoom_levels', array_map('strval', self::ZOOM_LEVELS));
        self::validate_group($errors, $evidence, 'input_modes', self::INPUT_MODES);
        self::validate_staging($errors, $evidence['staging_environment'] ?? null);
        self::validate_signoff($errors, $evidence['founder_signoff'] ?? null);

        return array_values(array_unique($errors));
    }

    /** @param array<string,mixed> $evidence @return array{accepted:bool,error_count:int,errors:list<string>} */
    public static function summarize(array $evidence): array
    {
        $errors = self::validate_evidence($evidence);

        return [
            'accepted' => $errors === [],
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /** @param list<string> $errors @param list<string> $required @param array<string,mixed> $evidence */
    private static function validate_group(array &$errors, array $evidence, string $group, array $required): void
    {
        $records = $evidence[$group] ?? null;
        if (! is_array($records)) {
            $errors[] = 'Missing evidence group: ' . $group;
            return;
        }
        foreach ($required as $key) {
            self::validate_record($errors, $records[(string) $key] ?? null, $group . '.' . $key);
        }
    }

    /** @param list<string> $errors */
    private static function validate_record(array &$errors, mixed $record, string $path): void
    {
        if (! is_array($record)) {
            $errors[] = 'Missing evidence record: ' . $path;
            return;
        }
        if (($record['status'] ?? '') !== 'pass') {
            $errors[] = 'Evidence status is not pass: ' . $path;
        }
        if (! self::safe_reference($record['artifact_ref'] ?? null)) {
            $errors[] = 'Invalid artifact reference: ' . $path;
        }
        if (! is_scalar($record['sha256'] ?? null) || preg_match('/^[a-f0-9]{64}$/i', (string) $record['sha256']) !== 1) {
            $errors[] = 'Invalid artifact SHA-256: ' . $path;
        }
        if (! self::iso_time($record['recorded_at'] ?? null)) {
            $errors[] = 'Invalid evidence timestamp: ' . $path;
        }
        if (! self::bounded_text($record['reviewer'] ?? null, 190)) {
            $errors[] = 'Invalid evidence reviewer: ' . $path;
        }
    }

    /** @param list<string> $errors */
    private static function validate_staging(array &$errors, mixed $record): void
    {
        if (! is_array($record)) {
            $errors[] = 'Missing staging environment evidence.';
            return;
        }
        if (($record['environment'] ?? '') !== 'staging') {
            $errors[] = 'Visual acceptance evidence must come from staging.';
        }
        if (! self::https_url($record['site_url'] ?? null)) {
            $errors[] = 'Invalid staging site URL.';
        }
        if (! is_scalar($record['commit_sha'] ?? null) || preg_match('/^[a-f0-9]{40}$/i', (string) $record['commit_sha']) !== 1) {
            $errors[] = 'Invalid staging commit SHA.';
        }
        if (! self::bounded_text($record['wordpress_version'] ?? null, 40) || ! self::bounded_text($record['php_version'] ?? null, 40)) {
            $errors[] = 'Missing staging runtime versions.';
        }
        if (! self::iso_time($record['recorded_at'] ?? null)) {
            $errors[] = 'Invalid staging evidence timestamp.';
        }
    }

    /** @param list<string> $errors */
    private static function validate_signoff(array &$errors, mixed $record): void
    {
        if (! is_array($record)) {
            $errors[] = 'Missing Founder sign-off evidence.';
            return;
        }
        if (($record['status'] ?? '') !== 'accepted') {
            $errors[] = 'Founder sign-off is not accepted.';
        }
        if (! self::bounded_text($record['signer'] ?? null, 190)) {
            $errors[] = 'Invalid Founder signer.';
        }
        if (! is_scalar($record['commit_sha'] ?? null) || preg_match('/^[a-f0-9]{40}$/i', (string) $record['commit_sha']) !== 1) {
            $errors[] = 'Invalid Founder sign-off commit SHA.';
        }
        if (! self::safe_reference($record['evidence_ref'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off evidence reference.';
        }
        if (! self::iso_time($record['recorded_at'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off timestamp.';
        }
    }

    private static function safe_reference(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }
        $value = trim((string) $value);

        return $value !== '' && strlen($value) <= 2048 && preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
    }

    private static function bounded_text(mixed $value, int $maximum): bool
    {
        return is_scalar($value) && trim((string) $value) !== '' && strlen(trim((string) $value)) <= $maximum;
    }

    private static function iso_time(mixed $value): bool
    {
        if (! is_scalar($value) || preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/', trim((string) $value)) !== 1) {
            return false;
        }
        try {
            new \DateTimeImmutable(trim((string) $value));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function https_url(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }
        $parts = parse_url(trim((string) $value));

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && (string) ($parts['host'] ?? '') !== ''
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }
}
