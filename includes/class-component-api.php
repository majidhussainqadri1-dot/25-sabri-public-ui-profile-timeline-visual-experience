<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use Sabri\PublicExperience\Contracts\Timeline_Provider;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Executable public component/provider registration boundary from File 25 §73.
 *
 * Providers may register before or after File 25 boots. Early registrations are
 * held only in request memory and are flushed into the one canonical
 * Timeline_Registry when it becomes available. No native content is copied.
 */
final class Component_API
{
    public const CONTRACT_VERSION = '1.0.0';
    private const MAX_PENDING_TIMELINE_PROVIDERS = 25;

    private static ?Timeline_Registry $timeline_registry = null;

    /** @var array<int,Timeline_Provider> */
    private static array $pending_timeline_providers = [];

    public static function register(): void
    {
        add_filter('sabri_visual_experience/contract', [self::class, 'filter_contract'], 50);
        add_filter('sabri_public_experience/design_system_contract', [self::class, 'filter_contract'], 50);
    }

    public static function register_timeline_provider(mixed $provider): bool
    {
        if (! $provider instanceof Timeline_Provider) {
            return false;
        }

        if (self::$timeline_registry instanceof Timeline_Registry) {
            return self::register_into_registry(self::$timeline_registry, $provider);
        }

        $key = spl_object_id($provider);
        if (isset(self::$pending_timeline_providers[$key])) {
            return true;
        }
        if (count(self::$pending_timeline_providers) >= self::MAX_PENDING_TIMELINE_PROVIDERS) {
            return false;
        }

        self::$pending_timeline_providers[$key] = $provider;
        return true;
    }

    public static function bind_timeline_registry(Timeline_Registry $registry): void
    {
        if (self::$timeline_registry instanceof Timeline_Registry
            && self::$timeline_registry !== $registry
        ) {
            throw new \LogicException('File 25 Component API cannot bind a second timeline registry in one request.');
        }

        self::$timeline_registry = $registry;
        $pending = self::$pending_timeline_providers;
        self::$pending_timeline_providers = [];
        foreach ($pending as $provider) {
            self::register_into_registry($registry, $provider);
        }
    }

    public static function register_card_variant(string $variant, callable $renderer): bool
    {
        return Content_Cards::register_variant($variant, $renderer);
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'owner' => 'file-25',
            'timeline_provider_interface' => Timeline_Provider::class,
            'timeline_provider_queue_limit' => self::MAX_PENDING_TIMELINE_PROVIDERS,
            'timeline_registry_owner' => 'file-25-read-projection-registry',
            'card_variant_registry' => 'bounded-request-local',
            'duplicate_backend_allowed' => false,
            'native_content_copy_allowed' => false,
            'registration_functions' => [
                'sabri_public_register_timeline_provider',
                'sabri_public_register_card_variant',
            ],
        ];
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];
        $base['component_api'] = self::contract();
        return $base;
    }

    private static function register_into_registry(Timeline_Registry $registry, Timeline_Provider $provider): bool
    {
        try {
            if (! $provider->is_available()) {
                return false;
            }
            $id = $provider->get_id();
            if ($registry->get($id) === $provider) {
                return true;
            }
            if ($registry->get($id) !== null) {
                return false;
            }
            $registry->register($provider);
            return true;
        } catch (\Throwable $exception) {
            if (function_exists('do_action')) {
                do_action('sabri_public_experience/provider_registration_error', $exception);
            }
            return false;
        }
    }
}
