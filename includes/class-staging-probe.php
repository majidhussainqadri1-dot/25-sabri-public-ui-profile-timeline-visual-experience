<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Privacy-safe, read-only Hostinger staging preflight.
 *
 * This probe verifies environment and installed-candidate integrity. It never
 * grants staging or production acceptance and never reads user/content rows.
 */
final class Staging_Probe
{
    public const CONTRACT_VERSION = '1.0.0';
    public const CANONICAL_STAGING_HOST = 'sabrisocialstaging.sabrihomeopathy.com';
    public const LIVE_HOST = 'sabrihomeopathy.com';

    private const MANIFEST = 'STAGING-MANIFEST.json';
    private const TEST_PLAN = 'config/staging-test-plan.json';
    private const MAX_FILES = 256;
    private const MAX_SINGLE_BYTES = 5_000_000;
    private const MAX_TOTAL_BYTES = 25_000_000;
    private const MAX_JSON_BYTES = 1_000_000;
    private const MAX_ERRORS = 50;

    public function __construct(
        private Dependency_Manager $dependencies,
        private Timeline_Registry $timeline_registry,
        private Section_Registry $section_registry
    ) {
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'owner' => 'file-25',
            'mode' => 'read-only',
            'canonical_staging_host' => self::CANONICAL_STAGING_HOST,
            'live_host' => self::LIVE_HOST,
            'expected_commit_source' => 'CLI --expected-commit or SABRI_PUBLIC_EXPERIENCE_EXPECTED_COMMIT',
            'registration_must_be_disabled' => true,
            'search_indexing_must_be_disabled' => true,
            'package_manifest_required' => self::MANIFEST,
            'test_plan' => self::TEST_PLAN,
            'contains_personal_data' => false,
            'writes_runtime_data' => false,
            'staging_acceptance_implied' => false,
            'production_acceptance_implied' => false,
        ];
    }

    /** @return array<string,mixed> */
    public function snapshot(string $expected_commit_sha = ''): array
    {
        $expected_commit = self::expected_commit($expected_commit_sha);
        $runtime_version = defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
            ? (string) SABRI_PUBLIC_EXPERIENCE_VERSION
            : '';
        $root = defined('SABRI_PUBLIC_EXPERIENCE_DIR')
            ? (string) SABRI_PUBLIC_EXPERIENCE_DIR
            : dirname(__DIR__) . '/';
        $site_url = function_exists('home_url') ? (string) home_url('/') : '';
        $parts = $site_url !== '' ? parse_url($site_url) : false;
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        $host = is_array($parts) ? strtolower(rtrim((string) ($parts['host'] ?? ''), '.')) : '';
        $environment_type = function_exists('wp_get_environment_type')
            ? sanitize_key((string) wp_get_environment_type())
            : '';
        $registration_disabled = function_exists('get_option')
            ? ! (bool) get_option('users_can_register', false)
            : false;
        $noindex = function_exists('get_option')
            ? (string) get_option('blog_public', '1') === '0'
            : false;
        $integrity = self::verify_package_integrity($root, $runtime_version, $expected_commit);
        $test_plan = self::verify_test_plan($root);
        $dependency_statuses = $this->dependency_snapshot();
        $required_available = true;
        foreach ($dependency_statuses as $dependency) {
            if (! empty($dependency['required']) && empty($dependency['available'])) {
                $required_available = false;
                break;
            }
        }

        $gates = [
            'canonical_staging_host' => $host !== '' && hash_equals(self::CANONICAL_STAGING_HOST, $host),
            'live_host_excluded' => $host !== '' && ! hash_equals(self::LIVE_HOST, $host),
            'https' => $scheme === 'https',
            'environment_type_safe' => $environment_type === ''
                || in_array($environment_type, ['staging', 'development', 'local'], true),
            'registration_disabled' => $registration_disabled,
            'search_indexing_disabled' => $noindex,
            'expected_commit_supplied' => $expected_commit !== '',
            'package_integrity' => ! empty($integrity['valid']),
            'test_plan_available' => ! empty($test_plan['valid']),
            'runtime_supported' => $this->dependencies->runtime_is_supported(),
            'required_dependencies_available' => $required_available,
            'safe_mode_inactive' => ! class_exists(Safe_Mode::class) || ! Safe_Mode::is_active(),
        ];

        global $wp_version;

        return [
            'schema_version' => 1,
            'contract' => self::contract(),
            'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'expected_commit_sha' => $expected_commit,
            'environment' => [
                'site_url' => self::bounded((string) $site_url, 2048),
                'scheme' => $scheme,
                'host' => $host,
                'environment_type' => $environment_type,
                'registration_disabled' => $registration_disabled,
                'search_indexing_disabled' => $noindex,
            ],
            'runtime' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => self::bounded((string) $wp_version, 40),
                'file_25_version' => $runtime_version,
                'schema_version' => defined('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION')
                    ? (string) SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION
                    : '',
            ],
            'package_integrity' => $integrity,
            'test_plan' => $test_plan,
            'dependencies' => $dependency_statuses,
            'production_gaps' => $this->dependencies->get_production_gaps(),
            'providers' => $this->provider_snapshot(),
            'gates' => $gates,
            'ready_for_manual_staging_tests' => ! in_array(false, $gates, true),
            'manual_staging_tests_pending' => true,
            'staging_accepted' => false,
            'production_accepted' => false,
        ];
    }

    /** @return array<string,mixed> */
    public static function verify_package_integrity(
        string $root,
        string $runtime_version,
        string $expected_commit_sha = ''
    ): array {
        $errors = [];
        $root_input = rtrim(str_replace('\\', '/', $root), '/');
        $root_real = realpath($root_input);
        if (! is_string($root_real) || ! is_dir($root_real) || is_link($root_input)) {
            return self::empty_integrity(['Plugin root is unavailable or symbolic.']);
        }
        $root_real = rtrim(str_replace('\\', '/', $root_real), '/');
        $manifest_path = $root_real . '/' . self::MANIFEST;
        if (! is_file($manifest_path) || is_link($manifest_path)) {
            return self::empty_integrity(['Embedded staging manifest is missing.']);
        }
        $manifest_bytes = filesize($manifest_path);
        if (! is_int($manifest_bytes) || $manifest_bytes < 2 || $manifest_bytes > self::MAX_JSON_BYTES) {
            return self::empty_integrity(['Embedded staging manifest size is invalid.']);
        }
        $raw = file_get_contents($manifest_path);
        try {
            $manifest = is_string($raw)
                ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR)
                : null;
        } catch (\Throwable) {
            $manifest = null;
        }
        if (! is_array($manifest)) {
            return self::empty_integrity(['Embedded staging manifest JSON is invalid.']);
        }

        $manifest_version = self::bounded($manifest['version'] ?? '', 64);
        $manifest_commit = self::commit_sha($manifest['commit_sha'] ?? '');
        $expected_commit = self::commit_sha($expected_commit_sha);
        if (($manifest['schema_version'] ?? null) !== 1
            || ($manifest['package'] ?? '') !== 'sabri-public-experience'
            || ($manifest['file_number'] ?? null) !== 25
        ) {
            self::error($errors, 'Manifest package identity is invalid.');
        }
        if ($runtime_version === '' || ! hash_equals($runtime_version, $manifest_version)) {
            self::error($errors, 'Manifest version does not match the runtime version.');
        }
        if ($manifest_commit === '') {
            self::error($errors, 'Manifest commit SHA is invalid.');
        }
        if ($expected_commit !== '' && ($manifest_commit === '' || ! hash_equals($expected_commit, $manifest_commit))) {
            self::error($errors, 'Manifest commit does not match the expected commit.');
        }

        $files = $manifest['files'] ?? null;
        if (! is_array($files) || $files === [] || count($files) > self::MAX_FILES) {
            self::error($errors, 'Manifest payload file list is invalid.');
            $files = [];
        }

        $expected = [];
        $verified_count = 0;
        $verified_bytes = 0;
        foreach ($files as $relative => $metadata) {
            $relative = is_string($relative) ? $relative : '';
            if (! self::safe_relative($relative) || $relative === self::MANIFEST || isset($expected[$relative])) {
                self::error($errors, 'Manifest contains an unsafe or duplicate payload path.');
                continue;
            }
            $expected[$relative] = true;
            if (! is_array($metadata)) {
                self::error($errors, 'Manifest payload metadata is invalid: ' . $relative);
                continue;
            }
            $sha = strtolower((string) ($metadata['sha256'] ?? ''));
            $bytes = $metadata['bytes'] ?? null;
            if (preg_match('/^[a-f0-9]{64}$/', $sha) !== 1 || ! is_int($bytes)
                || $bytes < 0 || $bytes > self::MAX_SINGLE_BYTES
            ) {
                self::error($errors, 'Manifest payload hash or byte size is invalid: ' . $relative);
                continue;
            }
            $path = $root_real . '/' . $relative;
            if (! is_file($path) || is_link($path)) {
                self::error($errors, 'Installed payload is missing or symbolic: ' . $relative);
                continue;
            }
            $real = realpath($path);
            if (! is_string($real) || ! self::within($real, $root_real)) {
                self::error($errors, 'Installed payload resolves outside the plugin root: ' . $relative);
                continue;
            }
            $actual_bytes = filesize($real);
            $actual_sha = hash_file('sha256', $real);
            if (! is_int($actual_bytes) || $actual_bytes !== $bytes
                || ! is_string($actual_sha) || ! hash_equals($sha, $actual_sha)
            ) {
                self::error($errors, 'Installed payload differs from the manifest: ' . $relative);
                continue;
            }
            $verified_count++;
            $verified_bytes += $actual_bytes;
            if ($verified_bytes > self::MAX_TOTAL_BYTES) {
                self::error($errors, 'Installed payload exceeds the expanded-size limit.');
                break;
            }
        }

        foreach (['sabri-public-experience.php', 'readme.txt', 'uninstall.php'] as $required) {
            if (! isset($expected[$required])) {
                self::error($errors, 'Required payload is absent from the manifest: ' . $required);
            }
        }

        $actual = [];
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root_real, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (! $file instanceof \SplFileInfo) {
                    continue;
                }
                if ($file->isLink()) {
                    self::error($errors, 'Installed plugin contains a symbolic link.');
                    continue;
                }
                if (! $file->isFile()) {
                    continue;
                }
                $real = $file->getRealPath();
                if (! is_string($real) || ! self::within($real, $root_real)) {
                    self::error($errors, 'Installed plugin file resolves outside the plugin root.');
                    continue;
                }
                $relative = ltrim(substr(str_replace('\\', '/', $real), strlen($root_real)), '/');
                if (! self::safe_relative($relative)) {
                    self::error($errors, 'Installed plugin contains an unsafe path.');
                    continue;
                }
                $actual[$relative] = true;
            }
        } catch (\Throwable) {
            self::error($errors, 'Installed plugin file set could not be enumerated safely.');
        }
        $expected[self::MANIFEST] = true;
        if (array_diff_key($expected, $actual) !== []) {
            self::error($errors, 'Installed plugin is missing one or more governed files.');
        }
        if (array_diff_key($actual, $expected) !== []) {
            self::error($errors, 'Installed plugin contains one or more ungoverned extra files.');
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'manifest_sha256' => hash_file('sha256', $manifest_path) ?: '',
            'manifest_version' => $manifest_version,
            'manifest_commit_sha' => $manifest_commit,
            'verified_file_count' => $verified_count,
            'verified_bytes' => $verified_bytes,
        ];
    }

    /** @return array{valid:bool,sha256:string,scenario_count:int} */
    public static function verify_test_plan(string $root): array
    {
        $root_real = realpath(rtrim($root, '/\\'));
        $path = is_string($root_real)
            ? rtrim(str_replace('\\', '/', $root_real), '/') . '/' . self::TEST_PLAN
            : '';
        if ($path === '' || ! is_file($path) || is_link($path)) {
            return ['valid' => false, 'sha256' => '', 'scenario_count' => 0];
        }
        $size = filesize($path);
        if (! is_int($size) || $size < 2 || $size > self::MAX_JSON_BYTES) {
            return ['valid' => false, 'sha256' => '', 'scenario_count' => 0];
        }
        try {
            $raw = file_get_contents($path);
            $plan = is_string($raw) ? json_decode($raw, true, 64, JSON_THROW_ON_ERROR) : null;
        } catch (\Throwable) {
            $plan = null;
        }
        $scenarios = is_array($plan) && is_array($plan['scenarios'] ?? null) ? $plan['scenarios'] : [];
        $valid = is_array($plan)
            && ($plan['schema_version'] ?? null) === 1
            && ($plan['owner'] ?? '') === 'file-25'
            && ($plan['canonical_staging_host'] ?? '') === self::CANONICAL_STAGING_HOST
            && ($plan['live_host_must_remain_untouched'] ?? null) === true
            && ($plan['staging_acceptance_implied'] ?? null) === false
            && count($scenarios) >= 10
            && count($scenarios) <= 100;

        return [
            'valid' => $valid,
            'sha256' => hash_file('sha256', $path) ?: '',
            'scenario_count' => count($scenarios),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function dependency_snapshot(): array
    {
        $result = [];
        foreach ($this->dependencies->get_statuses() as $id => $status) {
            $result[] = [
                'id' => sanitize_key((string) $id),
                'required' => ! empty($status['required']),
                'production_required' => ! empty($status['production_required']),
                'available' => ! empty($status['available']),
                'version' => self::bounded($status['version'] ?? '', 64),
            ];
        }

        return $result;
    }

    /** @return array<string,mixed> */
    private function provider_snapshot(): array
    {
        $timeline = ['registered' => 0, 'available' => 0, 'errors' => 0];
        foreach ($this->timeline_registry->all() as $id => $provider) {
            $timeline['registered']++;
            try {
                $metadata = $this->timeline_registry->validated_metadata($provider, (string) $id);
                if ($metadata === null) {
                    $timeline['errors']++;
                } elseif ($metadata['maturity'] !== 'disabled' && $provider->is_available()) {
                    $timeline['available']++;
                }
            } catch (\Throwable) {
                $timeline['errors']++;
            }
        }
        $sections = [];
        foreach (Section_Registry::approved_sections() as $section) {
            $summary = ['registered' => 0, 'available' => 0, 'errors' => 0];
            foreach ($this->section_registry->for_section($section) as $provider) {
                $summary['registered']++;
                try {
                    $metadata = $this->section_registry->validated_metadata($provider, $section);
                    if ($metadata === null) {
                        $summary['errors']++;
                    } elseif ($metadata['maturity'] !== 'disabled' && $provider->is_available()) {
                        $summary['available']++;
                    }
                } catch (\Throwable) {
                    $summary['errors']++;
                }
            }
            $sections[$section] = $summary;
        }

        return ['timeline' => $timeline, 'sections' => $sections];
    }

    private static function expected_commit(string $provided): string
    {
        $provided = self::commit_sha($provided);
        if ($provided !== '') {
            return $provided;
        }

        return defined('SABRI_PUBLIC_EXPERIENCE_EXPECTED_COMMIT')
            ? self::commit_sha((string) SABRI_PUBLIC_EXPERIENCE_EXPECTED_COMMIT)
            : '';
    }

    private static function commit_sha(mixed $value): string
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return preg_match('/^[a-f0-9]{40}$/', $value) === 1 ? $value : '';
    }

    private static function bounded(mixed $value, int $maximum): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return strlen($value) <= $maximum ? $value : substr($value, 0, $maximum);
    }

    private static function safe_relative(string $path): bool
    {
        if ($path === '' || strlen($path) > 512 || str_starts_with($path, '/')
            || str_contains($path, '\\') || str_contains($path, ':')
            || str_contains($path, '?') || str_contains($path, '#')
            || preg_match('/[\x00-\x20\x7F]/', $path) === 1
        ) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private static function within(string $path, string $root): bool
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';

        return str_starts_with($path, $root);
    }

    /** @param list<string> $errors */
    private static function error(array &$errors, string $message): void
    {
        if (count($errors) < self::MAX_ERRORS) {
            $errors[] = $message;
        }
    }

    /** @param list<string> $errors @return array<string,mixed> */
    private static function empty_integrity(array $errors): array
    {
        return [
            'valid' => false,
            'errors' => array_slice($errors, 0, self::MAX_ERRORS),
            'manifest_sha256' => '',
            'manifest_version' => '',
            'manifest_commit_sha' => '',
            'verified_file_count' => 0,
            'verified_bytes' => 0,
        ];
    }
}
