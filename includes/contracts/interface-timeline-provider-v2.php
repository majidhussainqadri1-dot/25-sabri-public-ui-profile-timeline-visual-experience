<?php

declare(strict_types=1);

namespace Sabri\PublicExperience\Contracts;

use Sabri\PublicExperience\Normalized_Timeline_Item;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Current File 25 Timeline Provider Contract v2.
 *
 * V2 extends the stable v1 read contract without breaking previously compiled
 * v1 providers. New/updated native providers should implement V2. File 25 may
 * continue to render a v1 provider in compatibility mode, but v2-only filters,
 * metrics and synchronization capabilities are enabled only when V2 is present.
 */
interface Timeline_Provider_V2 extends Timeline_Provider
{
    /**
     * Convert one native owner item into File 25's bounded immutable public
     * projection. Return null when it is not publicly eligible.
     */
    public function normalize_public_item(mixed $native_item, int $author_id): ?Normalized_Timeline_Item;

    public function get_canonical_url(Normalized_Timeline_Item $item): string;

    public function get_visibility_state(Normalized_Timeline_Item $item): string;

    /** @return list<string> */
    public function get_public_actions(Normalized_Timeline_Item $item): array;

    /** @return array<string,int> */
    public function get_public_metrics(Normalized_Timeline_Item $item): array;

    public function get_correction_state(Normalized_Timeline_Item $item): string;

    /**
     * Register owner-published synchronization hooks when available.
     * Request-local read-through providers may intentionally no-op.
     */
    public function register_sync_events(): void;
}
