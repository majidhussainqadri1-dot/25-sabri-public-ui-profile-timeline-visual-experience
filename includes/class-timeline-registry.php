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

    public function register(Timeline_Provider $provider): void
    {
        $raw_id = trim($provider->get_provider_id());
        $id = $this->provider_key($raw_id);
        if ($id === '' || $raw_id !== $id) {
            throw new InvalidArgumentException('Timeline provider ID must be a canonical lowercase safe key.');
        }
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException(sprintf('Timeline provider already registered: %s', $id));
        }

        $version = trim($provider->get_provider_version());
        if ($version === '' || strlen($version) > 64) {
            throw new InvalidArgumentException(sprintf('Timeline provider version is invalid: %s', $id));
        }

        $maturity = trim($provider->get_maturity_level());
        if (! in_array($maturity, self::MATURITY_LEVELS, true)) {
            throw new InvalidArgumentException(sprintf('Invalid timeline provider maturity: %s', $maturity));
        }

        $this->providers[$id] = $provider;
    }

    public function unregister(string $provider_id): void
    {
        unset($this->providers[$this->provider_key($provider_id)]);
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

    private function provider_key(string $provider_id): string
    {
        $provider_id = strtolower(trim($provider_id));
        $provider_id = preg_replace('/[^a-z0-9_\-]/', '-', $provider_id) ?? '';

        return substr(trim($provider_id, '-'), 0, 64);
    }
}
