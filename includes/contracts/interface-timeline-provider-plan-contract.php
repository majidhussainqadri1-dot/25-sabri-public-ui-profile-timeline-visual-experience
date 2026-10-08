<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Contracts;

use Sabri\PublicExperience\Normalized_Timeline_Item;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * File 25 §46 complete provider surface.
 *
 * Timeline_Provider remains the compatibility floor for previously reviewed
 * integrations. New/built-in providers implement this extended contract so the
 * plan can evolve without silently breaking older third-party providers.
 */
interface Timeline_Provider_Plan_Contract extends Timeline_Provider
{
    public function normalize_public_item(mixed $item, int $author_id): ?Normalized_Timeline_Item;

    public function get_canonical_url(Normalized_Timeline_Item $item): string;

    public function get_visibility_state(Normalized_Timeline_Item $item): string;

    /** @return list<string> */
    public function get_public_actions(Normalized_Timeline_Item $item): array;

    /** @return array<string,int|float|string> */
    public function get_public_metrics(Normalized_Timeline_Item $item): array;

    public function get_correction_state(Normalized_Timeline_Item $item): string;

    public function register_sync_events(): void;
}
