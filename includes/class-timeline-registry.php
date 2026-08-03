<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use InvalidArgumentException;
use Sabri\PublicExperience\Contracts\Timeline_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Timeline_Registry
{
    private const MAX_PROVIDERS = 25;
    private const MATURITY_LEVELS = [
        'detected',
        'read-only',
        'staging-accepted',
        'production-accepted',
        'degraded',
        'disabled',
    ];

    /** @var array<string,Timeline_Provider> */
    private array $providers = [];

    /** @var array<string,array{id:string,version:string,maturity:string,object_id:int}> */
    private array $registered_metadata = [];

    public function register(Timeline_Provider $provider): void
    {
        try {
            $raw_id = $provider->get_provider_id();
            $version = $provider->get_provider_version();
            $maturity = $provider->get_maturity_level();
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Timeline provider metadata could not be read safely.', 0, $exception);
        }

        if (! self::provider_id_is_exact($raw_id)) {
            throw new InvalidArgumentException('Timeline provider ID must be an exact canonical lowercase safe key.');
        }
        if (! self::version_is_valid($version) || trim($version) !== $version) {
            throw new InvalidArgumentException(sprintf('Timeline provider version is invalid: %s', $raw_id));
        }
        if (! self::maturity_is_exact($maturity)) {
            throw new InvalidArgumentException(sprintf('Invalid timeline provider maturity: %s', $maturity));
        }
        if (isset($this->providers[$raw_id])) {
            throw new InvalidArgumentException(sprintf('Timeline provider already registered: %s', $raw_id));
        }
        if (count($this->providers) >= self::MAX_PROVIDERS) {
            throw new InvalidArgumentException('Timeline provider registry reached its safe provider limit.');
        }

        $this->providers[$raw_id] = $provider;
        $this->registered_metadata[$raw_id] = [
            'id' => $raw_id,
            'version' => $version,
            'maturity' => $maturity,
            'object_id' => spl_object_id($provider),
        ];
        ksort($this->providers, SORT_STRING);
        ksort($this->registered_metadata, SORT_STRING);
    }

    public function unregister(string $provider_id): void
    {
        if (! self::provider_id_is_exact($provider_id)) {
            return;
        }
        unset($this->providers[$provider_id], $this->registered_metadata[$provider_id]);
    }

    /** @return array<string,Timeline_Provider> */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $provider_id): ?Timeline_Provider
    {
        return self::provider_id_is_exact($provider_id)
            ? ($this->providers[$provider_id] ?? null)
            : null;
    }

    /** @return array{id:string,version:string,maturity:string,object_id:int}|null */
    public function validated_metadata(Timeline_Provider $provider, string $expected_id): ?array
    {
        if (! self::provider_id_is_exact($expected_id)) {
            return null;
        }

        try {
            $current = [
                'id' => $provider->get_provider_id(),
                'version' => $provider->get_provider_version(),
                'maturity' => $provider->get_maturity_level(),
                'object_id' => spl_object_id($provider),
            ];
        } catch (\Throwable) {
            return null;
        }

        $registered = $this->registered_metadata[$expected_id] ?? null;
        if (! is_array($registered)) {
            return null;
        }

        $consistent = self::provider_id_is_exact($current['id'])
            && self::version_is_valid($current['version'])
            && trim($current['version']) === $current['version']
            && self::maturity_is_exact($current['maturity'])
            && hash_equals($registered['id'], $current['id'])
            && hash_equals($registered['version'], $current['version'])
            && hash_equals($registered['maturity'], $current['maturity'])
            && $registered['object_id'] === $current['object_id']
            && hash_equals($expected_id, $current['id']);

        return $consistent ? $registered : null;
    }

    private static function provider_id_is_exact(string $provider_id): bool
    {
        return $provider_id !== ''
            && strlen($provider_id) <= 64
            && preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $provider_id) === 1;
    }

    private static function maturity_is_exact(string $maturity): bool
    {
        return in_array($maturity, self::MATURITY_LEVELS, true);
    }

    private static function version_is_valid(string $version): bool
    {
        return $version !== ''
            && strlen($version) <= 64
            && preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1;
    }
}
