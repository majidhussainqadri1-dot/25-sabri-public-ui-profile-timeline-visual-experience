<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Machine-readable Definition-of-Done matrix and evidence validator. */
final class Visual_Acceptance
{
    public const CONTRACT_VERSION = '1.3.0';

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
    private const MEDIA_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'application/json',
        'application/pdf',
        'application/zip',
        'text/html',
        'text/plain',
    ];
    private const REQUIRED_SURFACES = [
        'founder-overview',
        'doctor-overview',
        'member-overview',
        'profile-timeline',
        'knowledge-section',
        'media-section',
        'marketplace-section',
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
            'target_commit_sha_required' => true,
            'artifact_root' => 'artifacts/',
            'artifact_media_types' => self::MEDIA_TYPES,
            'evidence_record_required_fields' => [
                'status',
                'artifact_ref',
                'sha256',
                'byte_size',
                'media_type',
                'commit_sha',
                'recorded_at',
                'reviewer',
            ],
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
        $target_commit = self::commit_sha($evidence['target_commit_sha'] ?? null);
        if ($target_commit === '') {
            $errors[] = 'Missing or invalid target commit SHA.';
        }

        self::validate_group($errors, $evidence, 'surfaces', self::REQUIRED_SURFACES, $target_commit);
        self::validate_group($errors, $evidence, 'viewports', array_keys(self::VIEWPORTS), $target_commit);
        self::validate_group($errors, $evidence, 'directions', self::DIRECTIONS, $target_commit);
        self::validate_group($errors, $evidence, 'color_modes', self::COLOR_MODES, $target_commit);
        self::validate_group($errors, $evidence, 'motion_modes', self::MOTION_MODES, $target_commit);
        self::validate_group($errors, $evidence, 'zoom_levels', array_map('strval', self::ZOOM_LEVELS), $target_commit);
        self::validate_group($errors, $evidence, 'input_modes', self::INPUT_MODES, $target_commit);
        self::validate_staging($errors, $evidence['staging_environment'] ?? null, $target_commit);
        self::validate_signoff($errors, $evidence['founder_signoff'] ?? null, $target_commit);

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
    private static function validate_group(array &$errors, array $evidence, string $group, array $required, string $target_commit): void
    {
        $records = $evidence[$group] ?? null;
        if (! is_array($records)) {
            $errors[] = 'Missing evidence group: ' . $group;
            return;
        }
        foreach ($required as $key) {
            self::validate_record($errors, $records[(string) $key] ?? null, $group . '.' . $key, $target_commit);
        }
    }

    /** @param list<string> $errors */
    private static function validate_record(array &$errors, mixed $record, string $path, string $target_commit): void
    {
        if (! is_array($record)) {
            $errors[] = 'Missing evidence record: ' . $path;
            return;
        }
        if (($record['status'] ?? '') !== 'pass') {
            $errors[] = 'Evidence status is not pass: ' . $path;
        }
        if (! self::safe_artifact_reference($record['artifact_ref'] ?? null)) {
            $errors[] = 'Invalid artifact reference: ' . $path;
        }
        if (! self::sha256($record['sha256'] ?? null)) {
            $errors[] = 'Invalid artifact SHA-256: ' . $path;
        }
        if (! self::byte_size($record['byte_size'] ?? null)) {
            $errors[] = 'Invalid artifact byte size: ' . $path;
        }
        if (! self::media_type($record['media_type'] ?? null)) {
            $errors[] = 'Invalid artifact media type: ' . $path;
        }
        $record_commit = self::commit_sha($record['commit_sha'] ?? null);
        if ($record_commit === '' || $target_commit === '' || ! hash_equals($target_commit, $record_commit)) {
            $errors[] = 'Evidence commit does not match the target commit: ' . $path;
        }
        if (! self::iso_time($record['recorded_at'] ?? null)) {
            $errors[] = 'Invalid evidence timestamp: ' . $path;
        }
        if (! self::bounded_text($record['reviewer'] ?? null, 190)) {
            $errors[] = 'Invalid evidence reviewer: ' . $path;
        }
    }

    /** @param list<string> $errors */
    private static function validate_staging(array &$errors, mixed $record, string $target_commit): void
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
        $record_commit = self::commit_sha($record['commit_sha'] ?? null);
        if ($record_commit === '' || $target_commit === '' || ! hash_equals($target_commit, $record_commit)) {
            $errors[] = 'Staging commit does not match the target commit.';
        }
        if (! self::bounded_text($record['wordpress_version'] ?? null, 40) || ! self::bounded_text($record['php_version'] ?? null, 40)) {
            $errors[] = 'Missing staging runtime versions.';
        }
        if (! self::iso_time($record['recorded_at'] ?? null)) {
            $errors[] = 'Invalid staging evidence timestamp.';
        }
    }

    /** @param list<string> $errors */
    private static function validate_signoff(array &$errors, mixed $record, string $target_commit): void
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
        $record_commit = self::commit_sha($record['commit_sha'] ?? null);
        if ($record_commit === '' || $target_commit === '' || ! hash_equals($target_commit, $record_commit)) {
            $errors[] = 'Founder sign-off commit does not match the target commit.';
        }
        if (! self::safe_artifact_reference($record['evidence_ref'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off evidence reference.';
        }
        if (! self::sha256($record['evidence_sha256'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off evidence SHA-256.';
        }
        if (! self::byte_size($record['evidence_byte_size'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off evidence byte size.';
        }
        if (! self::media_type($record['evidence_media_type'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off evidence media type.';
        }
        if (! self::iso_time($record['recorded_at'] ?? null)) {
            $errors[] = 'Invalid Founder sign-off timestamp.';
        }
    }

    private static function safe_artifact_reference(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }
        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 512 || preg_match('/[\x00-\x20\x7F]/', $value) === 1) {
            return false;
        }
        if (! str_starts_with($value, 'artifacts/') || str_contains($value, '\\') || str_contains($value, ':') || str_contains($value, '?') || str_contains($value, '#')) {
            return false;
        }
        if (preg_match('#^artifacts/[A-Za-z0-9][A-Za-z0-9._/-]*$#', $value) !== 1) {
            return false;
        }
        foreach (explode('/', $value) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private static function sha256(mixed $value): bool
    {
        return is_scalar($value) && preg_match('/^[a-f0-9]{64}$/i', trim((string) $value)) === 1;
    }

    private static function byte_size(mixed $value): bool
    {
        if (is_int($value)) {
            return $value >= 1 && $value <= 1073741824;
        }
        if (! is_string($value) || preg_match('/^[1-9]\d{0,9}$/', $value) !== 1) {
            return false;
        }
        $size = (int) $value;

        return $size >= 1 && $size <= 1073741824;
    }

    private static function media_type(mixed $value): bool
    {
        return is_scalar($value) && in_array(strtolower(trim((string) $value)), self::MEDIA_TYPES, true);
    }

    private static function bounded_text(mixed $value, int $maximum): bool
    {
        return is_scalar($value) && trim((string) $value) !== '' && strlen(trim((string) $value)) <= $maximum;
    }

    private static function commit_sha(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $value = strtolower(trim((string) $value));

        return preg_match('/^[a-f0-9]{40}$/', $value) === 1 ? $value : '';
    }

    private static function iso_time(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }
        $raw = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.\d{1,6})?(Z|[+\-](\d{2}):(\d{2}))$/', $raw, $match) !== 1) {
            return false;
        }

        $year = (int) $match[1];
        $month = (int) $match[2];
        $day = (int) $match[3];
        $hour = (int) $match[4];
        $minute = (int) $match[5];
        $second = (int) $match[6];
        if (! checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            return false;
        }
        if ($match[7] !== 'Z') {
            $offset_hour = (int) $match[8];
            $offset_minute = (int) $match[9];
            if ($offset_hour > 14 || $offset_minute > 59 || ($offset_hour === 14 && $offset_minute !== 0)) {
                return false;
            }
        }

        try {
            new \DateTimeImmutable($raw);
            $errors = \DateTimeImmutable::getLastErrors();
            return ! is_array($errors)
                || ((int) ($errors['warning_count'] ?? 0) === 0 && (int) ($errors['error_count'] ?? 0) === 0);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function https_url(mixed $value): bool
    {
        if (! is_scalar($value)) {
            return false;
        }
        $url = trim((string) $value);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7F]/', $url) === 1 || str_contains($url, '\\')) {
            return false;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && (string) ($parts['host'] ?? '') !== ''
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && (! isset($parts['port']) || ((int) $parts['port'] >= 1 && (int) $parts['port'] <= 65535));
    }
}
