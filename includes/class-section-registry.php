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

    public function register(Profile_Section_Provider $provider): void
    {
        if (count($this->providers) >= self::MAX_PROVIDERS) {
            throw new InvalidArgumentException('The File 25 profile-section provider limit has been reached.');
        }

        $id = self::key($provider->get_id());
        if ($id === '' || preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $id) !== 1) {
            throw new InvalidArgumentException('Profile-section provider ID is invalid.');
        }
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException('Duplicate profile-section provider ID.');
        }

        $version = trim($provider->get_version());
        if (preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw new InvalidArgumentException('Profile-section provider version is invalid.');
        }

        $section = self::key($provider->get_section());
        if (! self::section_is_approved($section)) {
            throw new InvalidArgumentException('Profile-section provider section is not approved.');
        }

        $maturity = self::key($provider->get_maturity_level());
        if (! self::maturity_is_approved($maturity)) {
            throw new InvalidArgumentException('Profile-section provider maturity is invalid.');
        }

        if ($provider->owns_native_content()) {
            throw new InvalidArgumentException('File 25 profile-section providers may not own native content.');
        }

        $this->providers[$id] = $provider;
        ksort($this->providers, SORT_STRING);
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

        return array_filter(
            $this->providers,
            static fn (Profile_Section_Provider $provider): bool => self::key($provider->get_section()) === $section
        );
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
