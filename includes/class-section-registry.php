<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use InvalidArgumentException;
use Sabri\PublicExperience\Contracts\Profile_Section_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Bounded registry for optional read-only public profile-section providers. */
final class Section_Registry
{
    public const MAX_PROVIDERS = 32;

    private const SECTIONS = ['knowledge', 'media', 'reviews', 'research', 'marketplace'];
    private const MATURITY_LEVELS = ['disabled', 'experimental', 'read-only', 'staging-accepted', 'production-accepted'];

    /** @var array<string,Profile_Section_Provider> */
    private array $providers = [];

    /** @var array<string,array{id:string,version:string,section:string,maturity:string,owns_native_content:bool,object_id:int}> */
    private array $registered_metadata = [];

    public function register(Profile_Section_Provider $provider): void
    {
        try {
            $id = $provider->get_id();
            $version = $provider->get_version();
            $section = $provider->get_section();
            $maturity = $provider->get_maturity_level();
            $owns_native_content = $provider->owns_native_content();
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Profile-section provider metadata could not be read safely.', 0, $exception);
        }

        if (! self::provider_id_is_exact($id)) {
            throw new InvalidArgumentException('Profile-section provider ID must be an exact canonical lowercase safe key.');
        }
        if (! self::version_is_valid($version) || trim($version) !== $version) {
            throw new InvalidArgumentException('Profile-section provider version is invalid.');
        }
        if (! self::section_is_approved($section)) {
            throw new InvalidArgumentException('Profile-section provider section is not approved or is non-canonical.');
        }
        if (! self::maturity_is_approved($maturity)) {
            throw new InvalidArgumentException('Profile-section provider maturity is invalid or is non-canonical.');
        }
        if ($owns_native_content !== false) {
            throw new InvalidArgumentException('File 25 profile-section providers may not own native content.');
        }
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException('Duplicate profile-section provider ID.');
        }
        if (count($this->providers) >= self::MAX_PROVIDERS) {
            throw new InvalidArgumentException('The File 25 profile-section provider limit has been reached.');
        }

        $this->providers[$id] = $provider;
        $this->registered_metadata[$id] = [
            'id' => $id,
            'version' => $version,
            'section' => $section,
            'maturity' => $maturity,
            'owns_native_content' => false,
            'object_id' => spl_object_id($provider),
        ];
        ksort($this->providers, SORT_STRING);
        ksort($this->registered_metadata, SORT_STRING);
    }

    public function get(string $id): ?Profile_Section_Provider
    {
        return self::provider_id_is_exact($id) ? ($this->providers[$id] ?? null) : null;
    }

    /** @return array<string,Profile_Section_Provider> */
    public function all(): array
    {
        return $this->providers;
    }

    /** @return array<string,Profile_Section_Provider> */
    public function for_section(string $section): array
    {
        if (! self::section_is_approved($section)) {
            return [];
        }

        $matches = [];
        foreach ($this->providers as $id => $provider) {
            if (($this->registered_metadata[$id]['section'] ?? '') === $section) {
                $matches[$id] = $provider;
            }
        }

        return $matches;
    }

    /** @return array{id:string,version:string,section:string,maturity:string,owns_native_content:bool,object_id:int}|null */
    public function validated_metadata(Profile_Section_Provider $provider, string $expected_section): ?array
    {
        if (! self::section_is_approved($expected_section)) {
            return null;
        }

        try {
            $current = [
                'id' => $provider->get_id(),
                'version' => $provider->get_version(),
                'section' => $provider->get_section(),
                'maturity' => $provider->get_maturity_level(),
                'owns_native_content' => $provider->owns_native_content(),
                'object_id' => spl_object_id($provider),
            ];
        } catch (\Throwable) {
            return null;
        }

        if (! self::provider_id_is_exact($current['id'])) {
            return null;
        }
        $registered = $this->registered_metadata[$current['id']] ?? null;
        if (! is_array($registered)) {
            return null;
        }

        $consistent = self::version_is_valid($current['version'])
            && trim($current['version']) === $current['version']
            && self::section_is_approved($current['section'])
            && self::maturity_is_approved($current['maturity'])
            && $current['owns_native_content'] === false
            && hash_equals($registered['id'], $current['id'])
            && hash_equals($registered['version'], $current['version'])
            && hash_equals($registered['section'], $current['section'])
            && hash_equals($registered['maturity'], $current['maturity'])
            && $registered['owns_native_content'] === $current['owns_native_content']
            && $registered['object_id'] === $current['object_id']
            && hash_equals($expected_section, $current['section']);

        return $consistent ? $registered : null;
    }

    public function provider_is_consistent(Profile_Section_Provider $provider, string $expected_section): bool
    {
        return $this->validated_metadata($provider, $expected_section) !== null;
    }

    public function metadata_fingerprint(string $section): string
    {
        if (! self::section_is_approved($section)) {
            return '';
        }
        $rows = [];
        foreach ($this->registered_metadata as $id => $metadata) {
            if ($metadata['section'] !== $section) {
                continue;
            }
            $rows[$id] = $metadata;
        }
        ksort($rows, SORT_STRING);
        $json = json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($json) ? hash('sha256', $json) : '';
    }

    /** @return list<string> */
    public static function approved_sections(): array
    {
        return self::SECTIONS;
    }

    public static function section_is_approved(string $section): bool
    {
        return in_array($section, self::SECTIONS, true);
    }

    public static function maturity_is_approved(string $maturity): bool
    {
        return in_array($maturity, self::MATURITY_LEVELS, true);
    }

    private static function provider_id_is_exact(string $id): bool
    {
        return $id !== '' && strlen($id) <= 64 && preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $id) === 1;
    }

    private static function version_is_valid(string $version): bool
    {
        return $version !== ''
            && strlen($version) <= 64
            && preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1;
    }
}
