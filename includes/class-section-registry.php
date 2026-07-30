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

    private const SECTIONS = [
        'knowledge',
        'media',
        'reviews',
        'research',
        'marketplace',
    ];

    private const MATURITY_LEVELS = [
        'disabled',
        'experimental',
        'read-only',
        'staging-accepted',
        'production-accepted',
    ];

    /** @var array<string,Profile_Section_Provider> */
    private array $providers = [];

    /** @var array<string,array{id:string,version:string,section:string,maturity:string,owns_native_content:bool}> */
    private array $registered_metadata = [];

    public function register(Profile_Section_Provider $provider): void
    {
        try {
            $id = self::key($provider->get_id());
            $version = trim($provider->get_version());
            $section = self::key($provider->get_section());
            $maturity = self::key($provider->get_maturity_level());
            $owns_native_content = $provider->owns_native_content();
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Profile-section provider metadata could not be read safely.', 0, $exception);
        }

        if ($id === '' || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $id) !== 1) {
            throw new InvalidArgumentException('Profile-section provider ID is invalid.');
        }
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException('Duplicate profile-section provider ID.');
        }
        if (count($this->providers) >= self::MAX_PROVIDERS) {
            throw new InvalidArgumentException('The File 25 profile-section provider limit has been reached.');
        }
        if (! self::version_is_valid($version)) {
            throw new InvalidArgumentException('Profile-section provider version is invalid.');
        }
        if (! self::section_is_approved($section)) {
            throw new InvalidArgumentException('Profile-section provider section is not approved.');
        }
        if (! self::maturity_is_approved($maturity)) {
            throw new InvalidArgumentException('Profile-section provider maturity is invalid.');
        }
        if ($owns_native_content) {
            throw new InvalidArgumentException('File 25 profile-section providers may not own native content.');
        }

        $this->providers[$id] = $provider;
        $this->registered_metadata[$id] = [
            'id' => $id,
            'version' => $version,
            'section' => $section,
            'maturity' => $maturity,
            'owns_native_content' => false,
        ];
        ksort($this->providers, SORT_STRING);
        ksort($this->registered_metadata, SORT_STRING);
    }

    public function get(string $id): ?Profile_Section_Provider
    {
        return $this->providers[self::key($id)] ?? null;
    }

    /** @return array<string,Profile_Section_Provider> */
    public function all(): array
    {
        return $this->providers;
    }

    /** @return array<string,Profile_Section_Provider> */
    public function for_section(string $section): array
    {
        $section = self::key($section);
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

    public function provider_is_consistent(Profile_Section_Provider $provider, string $expected_section): bool
    {
        try {
            $id = self::key($provider->get_id());
            $version = trim($provider->get_version());
            $section = self::key($provider->get_section());
            $maturity = self::key($provider->get_maturity_level());
            $owns_native_content = $provider->owns_native_content();
        } catch (\Throwable) {
            return false;
        }

        $registered = $this->registered_metadata[$id] ?? null;
        if (! is_array($registered)) {
            return false;
        }

        return hash_equals($registered['id'], $id)
            && hash_equals($registered['version'], $version)
            && hash_equals($registered['section'], $section)
            && hash_equals($registered['maturity'], $maturity)
            && $registered['owns_native_content'] === $owns_native_content
            && $owns_native_content === false
            && $section === self::key($expected_section)
            && self::version_is_valid($version)
            && self::section_is_approved($section)
            && self::maturity_is_approved($maturity);
    }

    /** @return list<string> */
    public static function approved_sections(): array
    {
        return self::SECTIONS;
    }

    public static function section_is_approved(string $section): bool
    {
        return in_array(self::key($section), self::SECTIONS, true);
    }

    public static function maturity_is_approved(string $maturity): bool
    {
        return in_array(self::key($maturity), self::MATURITY_LEVELS, true);
    }

    private static function version_is_valid(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1;
    }

    private static function key(string $value): string
    {
        if (function_exists('sanitize_key')) {
            return sanitize_key($value);
        }

        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
