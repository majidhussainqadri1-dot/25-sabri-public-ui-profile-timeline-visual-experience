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
            $raw_id = trim($provider->get_provider_id());
            $id = $this->provider_key($raw_id);
            $version = trim($provider->get_provider_version());
            $maturity = trim($provider->get_maturity_level());
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Timeline provider metadata could not be read safely.', 0, $exception);
        }

        if ($id === '' || $raw_id !== $id) {
            throw new InvalidArgumentException('Timeline provider ID must be a canonical lowercase safe key.');
        }
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException(sprintf('Timeline provider already registered: %s', $id));
        }
        if (count($this->providers) >= self::MAX_PROVIDERS) {
            throw new InvalidArgumentException('Timeline provider registry reached its safe provider limit.');
        }
        if (! self::version_is_valid($version)) {
            throw new InvalidArgumentException(sprintf('Timeline provider version is invalid: %s', $id));
        }
        if (! in_array($maturity, self::MATURITY_LEVELS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid timeline provider maturity: %s', $maturity));
        }

        $this->providers[$id] = $provider;
        $this->registered_metadata[$id] = [
            'id' => $id,
            'version' => $version,
            'maturity' => $maturity,
            'object_id' => spl_object_id($provider),
        ];
        ksort($this->providers, SORT_STRING);
        ksort($this->registered_metadata, SORT_STRING);
    }

    public function unregister(string $provider_id): void
    {
        $id = $this->provider_key($provider_id);
        unset($this->providers[$id], $this->registered_metadata[$id]);
    }

    /** @return array<string,Timeline_Provider> */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $provider_id): ?Timeline_Provider
    {
        return $this->providers[$this->provider_key($provider_id)] ?? null;
    }

    /** @return array{id:string,version:string,maturity:string,object_id:int}|null */
    public function validated_metadata(Timeline_Provider $provider, string $expected_id): ?array
    {
        try {
            $raw_id = trim($provider->get_provider_id());
            $current = [
                'id' => $this->provider_key($raw_id),
                'version' => trim($provider->get_provider_version()),
                'maturity' => trim($provider->get_maturity_level()),
                'object_id' => spl_object_id($provider),
            ];
        } catch (\Throwable) {
            return null;
        }

        $expected_id = $this->provider_key($expected_id);
        $registered = $this->registered_metadata[$expected_id] ?? null;
        if (! is_array($registered)) {
            return null;
        }

        $consistent = $raw_id === $current['id']
            && hash_equals($registered['id'], $current['id'])
            && hash_equals($registered['version'], $current['version'])
            && hash_equals($registered['maturity'], $current['maturity'])
            && $registered['object_id'] === $current['object_id']
            && $current['id'] === $expected_id
            && self::version_is_valid($current['version'])
            && in_array($current['maturity'], self::MATURITY_LEVELS, true);

        return $consistent ? $registered : null;
    }

    private static function version_is_valid(string $version): bool
    {
        return $version !== ''
            && strlen($version) <= 64
            && preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1;
    }

    private function provider_key(string $provider_id): string
    {
        $provider_id = strtolower(trim($provider_id));
        $provider_id = preg_replace('/[^a-z0-9_\-]/', '-', $provider_id) ?? '';

        return substr(trim($provider_id, '-'), 0, 64);
    }
}
