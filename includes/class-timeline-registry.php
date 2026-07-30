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
    /** @var array<string, Timeline_Provider> */
    private array $providers = [];

    public function register(Timeline_Provider $provider): void
    {
        $id = $provider->get_provider_id();
        if ($id === '') {
            throw new InvalidArgumentException('Timeline provider ID cannot be empty.');
        }
        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException(sprintf('Timeline provider already registered: %s', $id));
        }
        $this->providers[$id] = $provider;
    }

    public function unregister(string $provider_id): void
    {
        unset($this->providers[$provider_id]);
    }

    /** @return array<string, Timeline_Provider> */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $provider_id): ?Timeline_Provider
    {
        return $this->providers[$provider_id] ?? null;
    }
}
